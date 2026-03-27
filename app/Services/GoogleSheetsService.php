<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SheetSource;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSheetsService
{
    public const META_ROW_NUMBER = '_sheet_row_number';

    public const META_SHEET_TITLE = '_sheet_title';

    public const META_SPREADSHEET_ID = '_spreadsheet_id';

    public const META_SHEET_GID = '_sheet_gid';

    protected array $serviceCache = [];

    protected array $spreadsheetCache = [];

    protected array $headerCache = [];

    public function shouldUseForSource(SheetSource $source): bool
    {
        return $source->source_kind === SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API
            && ($this->isGoogleSheetUrl($source->url) || filled(data_get($source->mapping, 'spreadsheet_id')));
    }

    public function readRows(SheetSource $source): array
    {
        $spreadsheetId = $this->spreadsheetIdFromSource($source);
        $sheetGid = $this->sheetGidFromSource($source);
        $sheetTitle = $this->sheetTitleFromSource($source, $spreadsheetId, $sheetGid);
        $range = data_get($source->mapping, 'read_range') ?: $this->sheetOnlyRange($sheetTitle);
        $startRow = $this->startingRowFromRange($range);

        $values = $this->sheets($source->company)
            ->spreadsheets_values
            ->get($spreadsheetId, $range)
            ->getValues() ?? [];

        if ($values === []) {
            return [];
        }

        $headerRowOffset = max(0, ((int) data_get($source->mapping, 'header_row', 1)) - 1);
        $headers = $this->normalizeHeaders($values[$headerRowOffset] ?? []);

        if ($headers === []) {
            return [];
        }

        $dataStartOffset = max(
            $headerRowOffset + 1,
            ((int) data_get($source->mapping, 'data_start_row', $headerRowOffset + 2)) - 1,
        );

        $rows = [];

        foreach ($values as $index => $row) {
            if ($index < $dataStartOffset) {
                continue;
            }

            if ($this->isBlankRow($row)) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $rows[] = array_merge(
                array_combine($headers, array_slice($row, 0, count($headers))),
                [
                    self::META_ROW_NUMBER => $startRow + $index,
                    self::META_SHEET_TITLE => $sheetTitle,
                    self::META_SPREADSHEET_ID => $spreadsheetId,
                    self::META_SHEET_GID => $sheetGid,
                ],
            );
        }

        return $rows;
    }

    public function writeLeadStatus(Lead $lead, string $status): bool
    {
        return $this->writeRowValue(
            $lead->sheetSource,
            $lead->source_payload ?? [],
            $status,
            Arr::wrap(data_get($lead->sheetSource?->mapping, 'status_column')) ?: ['Lead Status'],
        );
    }

    public function writeOpportunityStage(Opportunity $opportunity, string $stage): bool
    {
        return $this->writeRowValue(
            $opportunity->sheetSource,
            $opportunity->source_payload ?? [],
            $stage,
            Arr::wrap(data_get($opportunity->sheetSource?->mapping, 'status_column')) ?: ['Sales Stage'],
        );
    }

    protected function writeRowValue(?SheetSource $source, array $payload, string $value, array $headerCandidates): bool
    {
        if (! $source || ! $this->shouldUseForSource($source)) {
            return false;
        }

        $rowNumber = (int) ($payload[self::META_ROW_NUMBER] ?? 0);

        if ($rowNumber < 1) {
            return false;
        }

        $spreadsheetId = $payload[self::META_SPREADSHEET_ID] ?? $this->spreadsheetIdFromSource($source);
        $sheetTitle = $payload[self::META_SHEET_TITLE]
            ?? $this->sheetTitleFromSource($source, $spreadsheetId, $payload[self::META_SHEET_GID] ?? null);

        $headers = $this->headerRow(
            $source->company,
            $spreadsheetId,
            $sheetTitle,
            (int) data_get($source->mapping, 'header_row', 1),
        );

        $columnIndex = $this->findHeaderIndex($headers, $headerCandidates);

        if ($columnIndex === null) {
            throw new RuntimeException('Unable to locate the status column in the Google Sheet.');
        }

        $columnLetter = $this->columnLetter($columnIndex + 1);
        $range = $this->sheetCellRange($sheetTitle, $columnLetter, $rowNumber);

        $body = new ValueRange([
            'range' => $range,
            'majorDimension' => 'ROWS',
            'values' => [[$value]],
        ]);

        $this->sheets($source->company)
            ->spreadsheets_values
            ->update($spreadsheetId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);

        return true;
    }

    protected function sheets(?Company $company): Sheets
    {
        if (! $company) {
            throw new RuntimeException('Google Sheets API sources must belong to a company.');
        }

        if (isset($this->serviceCache[$company->id])) {
            return $this->serviceCache[$company->id];
        }

        return $this->serviceCache[$company->id] = new Sheets(
            app(GoogleOAuthService::class)->authorizedClient($company)
        );
    }

    protected function spreadsheetIdFromSource(SheetSource $source): string
    {
        $spreadsheetId = data_get($source->mapping, 'spreadsheet_id') ?: $this->extractSpreadsheetId($source->url);

        if (! $spreadsheetId) {
            throw new RuntimeException('Unable to determine the spreadsheet ID from the source URL.');
        }

        return $spreadsheetId;
    }

    protected function sheetGidFromSource(SheetSource $source): ?int
    {
        $mappingGid = data_get($source->mapping, 'sheet_gid');

        if ($mappingGid !== null && $mappingGid !== '') {
            return (int) $mappingGid;
        }

        return $this->extractSheetGid($source->url);
    }

    protected function sheetTitleFromSource(SheetSource $source, string $spreadsheetId, ?int $gid): string
    {
        $title = data_get($source->mapping, 'sheet_title');

        if ($title) {
            return $title;
        }

        foreach ($this->spreadsheetMetadata($source->company, $spreadsheetId) as $sheet) {
            $properties = $sheet->getProperties();

            if ($gid !== null && (int) $properties?->getSheetId() === $gid) {
                return (string) $properties->getTitle();
            }
        }

        $firstSheet = $this->spreadsheetMetadata($source->company, $spreadsheetId)[0] ?? null;

        if (! $firstSheet?->getProperties()?->getTitle()) {
            throw new RuntimeException('Unable to determine the Google Sheet tab title.');
        }

        return (string) $firstSheet->getProperties()->getTitle();
    }

    protected function spreadsheetMetadata(?Company $company, string $spreadsheetId): array
    {
        $cacheKey = ($company?->id ?? 'none').":{$spreadsheetId}";

        if (isset($this->spreadsheetCache[$cacheKey])) {
            return $this->spreadsheetCache[$cacheKey];
        }

        $spreadsheet = $this->sheets($company)
            ->spreadsheets
            ->get($spreadsheetId, ['fields' => 'sheets.properties(sheetId,title,index)']);

        return $this->spreadsheetCache[$cacheKey] = $spreadsheet->getSheets() ?? [];
    }

    protected function headerRow(?Company $company, string $spreadsheetId, string $sheetTitle, int $headerRow): array
    {
        $cacheKey = ($company?->id ?? 'none').":{$spreadsheetId}:{$sheetTitle}:{$headerRow}";

        if (isset($this->headerCache[$cacheKey])) {
            return $this->headerCache[$cacheKey];
        }

        $range = $this->sheetRowRange($sheetTitle, $headerRow);
        $values = $this->sheets($company)
            ->spreadsheets_values
            ->get($spreadsheetId, $range)
            ->getValues() ?? [];

        return $this->headerCache[$cacheKey] = $this->normalizeHeaders($values[0] ?? []);
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($header) => trim((string) $header), $headers);
    }

    protected function findHeaderIndex(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            foreach ($headers as $index => $header) {
                if (mb_strtolower(trim($header)) === mb_strtolower(trim((string) $candidate))) {
                    return $index;
                }
            }
        }

        return null;
    }

    protected function columnLetter(int $columnNumber): string
    {
        $column = '';

        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $column = chr(65 + $remainder).$column;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $column;
    }

    protected function sheetOnlyRange(string $sheetTitle): string
    {
        return $this->quotedSheetTitle($sheetTitle);
    }

    protected function sheetRowRange(string $sheetTitle, int $rowNumber): string
    {
        return sprintf('%s!%d:%d', $this->quotedSheetTitle($sheetTitle), $rowNumber, $rowNumber);
    }

    protected function sheetCellRange(string $sheetTitle, string $columnLetter, int $rowNumber): string
    {
        return sprintf('%s!%s%d', $this->quotedSheetTitle($sheetTitle), $columnLetter, $rowNumber);
    }

    protected function quotedSheetTitle(string $sheetTitle): string
    {
        return "'".str_replace("'", "''", $sheetTitle)."'";
    }

    protected function startingRowFromRange(string $range): int
    {
        if (preg_match('/![A-Z]+(\\d+)/i', $range, $matches) === 1) {
            return (int) $matches[1];
        }

        return 1;
    }

    protected function extractSpreadsheetId(string $url): ?string
    {
        if (preg_match('~/spreadsheets/d/([^/?#]+)~', $url, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    protected function extractSheetGid(string $url): ?int
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        if (isset($query['gid']) && is_numeric($query['gid'])) {
            return (int) $query['gid'];
        }

        $fragment = parse_url($url, PHP_URL_FRAGMENT) ?: '';

        if (preg_match('/gid=(\\d+)/', $fragment, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function isGoogleSheetUrl(string $url): bool
    {
        return Str::contains($url, 'docs.google.com/spreadsheets/d/');
    }

    protected function isBlankRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }
}
