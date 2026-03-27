<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\MonthlyReport;
use App\Models\Opportunity;
use App\Models\SheetSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SheetSourceSyncService
{
    public function importLeadCsvForWorkspace(SheetSource $source, string $contents): int
    {
        return $this->importCsvForSource($source, $contents);
    }

    public function importCsvForSource(SheetSource $source, string $contents): int
    {
        $source->forceFill([
            'sync_status' => 'syncing',
            'last_error' => null,
        ])->save();

        try {
            $rows = $this->parseCsv($contents);

            $count = DB::transaction(fn () => match ($source->type) {
                SheetSource::TYPE_LEADS => $this->syncLeads($source, $rows),
                SheetSource::TYPE_OPPORTUNITIES => $this->syncOpportunities($source, $rows),
                SheetSource::TYPE_REPORTS => $this->syncMonthlyReports($source, $rows),
                default => throw new RuntimeException("Unsupported uploaded CSV source type [{$source->type}]."),
            });

            $source->forceFill([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();

            return $count;
        } catch (Throwable $exception) {
            $source->forceFill([
                'sync_status' => 'failed',
                'last_error' => Str::limit($exception->getMessage(), 4000),
            ])->save();

            report($exception);

            throw $exception;
        }
    }

    public function sync(SheetSource $source): int
    {
        $source->forceFill([
            'sync_status' => 'syncing',
            'last_error' => null,
        ])->save();

        try {
            $rows = $this->downloadRowsForSource($source);

            $count = DB::transaction(fn () => match ($source->type) {
                SheetSource::TYPE_LEADS => $this->syncLeads($source, $rows),
                SheetSource::TYPE_OPPORTUNITIES => $this->syncOpportunities($source, $rows),
                SheetSource::TYPE_REPORTS => $this->syncMonthlyReports($source, $rows),
                SheetSource::TYPE_GOOGLE_ADS => 0,
                default => throw new RuntimeException("Unsupported sheet source type [{$source->type}]."),
            });

            $source->forceFill([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();

            return $count;
        } catch (Throwable $exception) {
            $source->forceFill([
                'sync_status' => 'failed',
                'last_error' => Str::limit($exception->getMessage(), 4000),
            ])->save();

            report($exception);

            throw $exception;
        }
    }

    protected function syncLeads(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Lead sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $leadId = $this->value($row, ['Lead ID']);
            $rfid = $this->value($row, ['RFID']);
            $leadKey = $this->value($row, ['LeadKey']);

            $externalKey = $leadId
                ?: $rfid
                ?: $leadKey
                ?: sha1(json_encode([
                    $this->value($row, ['Email']),
                    $this->value($row, ['Submission Create Date']),
                    $this->value($row, ['Company name', 'Company Name', 'Company']),
                ]));

            if (! $externalKey) {
                continue;
            }

            $lead = Lead::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'external_key' => $externalKey,
            ]);

            $incomingStatus = $this->normalizeLeadStatus($this->value($row, ['Lead Status']));

            $lead->fill([
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'lead_id' => $leadId,
                'rfid' => $rfid,
                'lead_key' => $leadKey,
                'contact_name' => $this->value($row, ['Column 1', 'Name', 'Contact Name', 'Full Name']),
                'company_name' => $this->value($row, ['Company name', 'Company Name', 'Company']),
                'email' => $this->value($row, ['Email']),
                'phone' => $this->value($row, ['Phone number']),
                'service' => $this->normalizeService($this->value($row, ['Service'])),
                'submission_date' => $this->parseDate($this->value($row, ['Submission Create Date'])),
                'lead_source' => $this->value($row, ['Lead Source']),
                'status' => $lead->exists ? $lead->status : $incomingStatus,
                'disqualification_reason' => $lead->disqualification_reason ?: $this->value($row, ['Reason of Disqualification']),
                'notes' => $lead->notes ?: $this->value($row, ['Note']),
                'nurture_minutes' => $this->parseInteger($this->value($row, ['Time to Nurture (minutes)'])),
                'nurture_hours' => $this->parseMoney($this->value($row, ['Time to Nurture (hours)'])),
                'lead_value' => $this->parseMoney($this->value($row, ['Lead Value'])),
                'hashed_email' => $this->value($row, ['Hashed Email']),
                'hashed_phone' => $this->value($row, ['Hashed Phone']),
                'is_converted' => $this->parseBoolean($this->value($row, ['Is Coverted'])),
                'manual_entry' => false,
                'source_payload' => $row,
            ]);

            $lead->save();

            $count++;
        }

        return $count;
    }

    protected function syncOpportunities(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Opportunity sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $rfid = $this->value($row, ['RFID']);
            $leadReference = $this->value($row, ['Lead ID']);

            $externalKey = $rfid
                ?: $leadReference
                ?: sha1(json_encode([
                    $this->value($row, ['Company Name']),
                    $this->value($row, ['Contact Email']),
                    $this->value($row, ['Submission Date']),
                ]));

            if (! $externalKey) {
                continue;
            }

            $opportunity = Opportunity::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'external_key' => $externalKey,
            ]);

            $incomingStage = $this->normalizeOpportunityStage($this->value($row, ['Sales Stage']));

            $opportunity->fill([
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'lead_id' => $this->resolveLeadId($source, $leadReference, $rfid),
                'rfid' => $rfid,
                'lead_reference' => $leadReference,
                'company_name' => $this->value($row, ['Company Name']),
                'contact_email' => $this->value($row, ['Contact Email']),
                'lead_source' => $this->value($row, ['Lead Source']),
                'required_service' => $this->normalizeService($this->value($row, ['Required Service'])),
                'revenue_potential' => $this->parseMoney($this->value($row, ['Revenue Potential (AED)'])),
                'project_timeline_days' => $this->parseInteger($this->value($row, ['Project Timeline (days)'])),
                'sales_stage' => $opportunity->exists ? $opportunity->sales_stage : $incomingStage,
                'reason_for_loss' => $opportunity->reason_for_loss ?: $this->value($row, ['Reason for Loss']),
                'notes' => $opportunity->notes ?: $this->value($row, ['Notes']),
                'submission_date' => $this->parseDate($this->value($row, ['Submission Date'])),
                'year_month' => $this->normalizeYearMonth($this->value($row, ['YearMonth'])),
                'manual_entry' => false,
                'source_payload' => $row,
            ]);

            $opportunity->save();

            $count++;
        }

        return $count;
    }

    protected function syncMonthlyReports(SheetSource $source, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $yearMonth = $this->normalizeYearMonth($this->value($row, ['YearMonth']));

            if (! $yearMonth) {
                continue;
            }

            MonthlyReport::updateOrCreate(
                [
                    'company_id' => $source->company_id,
                    'workspace_id' => $source->workspace_id,
                    'year_month' => $yearMonth,
                ],
                [
                    'sheet_source_id' => $source->id,
                    'month_start' => $this->parseMonth($yearMonth),
                    'linkedin_ads_leads' => $this->parseInteger($this->value($row, ['LinkedIn Ads Leads'])) ?? 0,
                    'organic_leads' => $this->parseInteger($this->value($row, ['Organic Leads'])) ?? 0,
                    'email_leads' => $this->parseInteger($this->value($row, ['Email Leads'])) ?? 0,
                    'google_ads_leads' => $this->parseInteger($this->value($row, ['Google Ads Leads'])) ?? 0,
                    'total_leads' => $this->parseInteger($this->value($row, ['Total Leads'])) ?? 0,
                    'linkedin_ads_cost' => $this->parseMoney($this->value($row, ['LinkedIn Ads Cost (AED)'])) ?? 0,
                    'google_ads_cost' => $this->parseMoney($this->value($row, ['Google Ads Cost (AED)'])) ?? 0,
                    'total_ads_cost' => $this->parseMoney($this->value($row, ['Total Ads Cost (AED)'])) ?? 0,
                    'cost_per_conversion' => $this->parseMoney($this->value($row, ['Cost Per Conversion (AED)'])) ?? 0,
                    'total_revenue_potential' => $this->parseMoney($this->value($row, ['Total Revenue Potential (AED)'])) ?? 0,
                    'won_revenue_potential' => $this->parseMoney($this->value($row, ['Won Revenue Potential (AED)'])) ?? 0,
                    'closed_won_count' => $this->parseInteger($this->value($row, ['No of Closed Won'])) ?? 0,
                    'closed_lost_count' => $this->parseInteger($this->value($row, ['No of Closed Last'])) ?? 0,
                    'proposal_sent_count' => $this->parseInteger($this->value($row, ['No of Proposal Sent'])) ?? 0,
                    'initial_contact_count' => $this->parseInteger($this->value($row, ['No of Initial Contact'])) ?? 0,
                    'organic_opportunities_count' => $this->parseInteger($this->value($row, ['Total Organic Opportunities'])) ?? 0,
                    'total_opportunities_count' => $this->parseInteger($this->value($row, ['Total Opportunities'])) ?? 0,
                    'mql_to_sql_rate' => $this->parseMoney($this->value($row, ['MQL to SQL Rate'])) ?? 0,
                    'sql_conversion_rate' => $this->parseMoney($this->value($row, ['SQL conversion rate'])) ?? 0,
                    'total_revenue_potential_2025' => $this->parseMoney($this->value($row, ['Total Revenue Potential (AED) in 2025'])) ?? 0,
                    'won_revenue_potential_2025' => $this->parseMoney($this->value($row, ['Won Revenue Potential (AED) in 2025'])) ?? 0,
                    'total_deals_2026' => $this->parseInteger($this->value($row, ['Total Deals 2026'])) ?? 0,
                    'total_deals_2025' => $this->parseInteger($this->value($row, ['Total Deals 2025'])) ?? 0,
                    'total_deals_2024' => $this->parseInteger($this->value($row, ['Total Deals 2024'])) ?? 0,
                    'romi_2025' => $this->parseMoney($this->value($row, ['Romi 2025'])) ?? 0,
                    'source_payload' => $row,
                ],
            );

            $count++;
        }

        return $count;
    }

    protected function downloadRows(string $url): array
    {
        $response = Http::timeout(30)
            ->retry(2, 500)
            ->get($this->normalizeUrl($url))
            ->throw()
            ->body();

        return $this->parseCsv($response);
    }

    protected function downloadRowsForSource(SheetSource $source): array
    {
        $googleSheets = app(GoogleSheetsService::class);

        if ($source->source_kind === SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API) {
            try {
                return $googleSheets->readRows($source);
            } catch (RuntimeException $exception) {
                if ($this->canFallbackToPublicCsv($source, $exception)) {
                    $rows = $this->downloadRows($source->url);

                    if (! filled(data_get($source->mapping, 'spreadsheet_id'))) {
                        $source->forceFill([
                            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
                        ])->save();
                    }

                    return $rows;
                }

                throw $exception;
            }
        }

        return $this->downloadRows($source->url);
    }

    protected function parseCsv(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');

        fwrite($stream, $contents);
        rewind($stream);

        $headers = fgetcsv($stream, 0, ',', '"', '\\');

        if (! $headers) {
            return [];
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        $rows = [];

        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $rows[] = array_combine($headers, array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                array_slice($row, 0, count($headers)),
            ));
        }

        fclose($stream);

        return $rows;
    }

    protected function normalizeUrl(string $url): string
    {
        if (! str_contains($url, 'docs.google.com/spreadsheets/d/')) {
            return $url;
        }

        preg_match('~/spreadsheets/d/([^/]+)~', $url, $spreadsheetMatches);

        if (! isset($spreadsheetMatches[1])) {
            return $url;
        }

        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $gid = $query['gid'] ?? '0';

        if ($gid === '0') {
            $fragment = parse_url($url, PHP_URL_FRAGMENT) ?: '';

            if (preg_match('/gid=(\d+)/', $fragment, $gidMatches) === 1) {
                $gid = $gidMatches[1];
            }
        }

        return "https://docs.google.com/spreadsheets/d/{$spreadsheetMatches[1]}/export?format=csv&gid={$gid}";
    }

    protected function canFallbackToPublicCsv(SheetSource $source, RuntimeException $exception): bool
    {
        if (! str_contains($source->url, 'docs.google.com/spreadsheets/d/')) {
            return false;
        }

        return in_array($exception->getMessage(), [
            'Google OAuth client ID and secret must be saved first.',
            'Google is not connected for this company.',
            'Google access expired and no refresh token is available. Reconnect Google.',
        ], true);
    }

    protected function resolveLeadId(SheetSource $source, ?string $leadReference, ?string $rfid): ?int
    {
        return Lead::query()
            ->where('workspace_id', $source->workspace_id)
            ->where(function ($query) use ($leadReference, $rfid) {
                if ($leadReference) {
                    $query->orWhere('lead_id', $leadReference);
                }

                if ($rfid) {
                    $query->orWhere('rfid', $rfid);
                }
            })
            ->value('id');
    }

    protected function normalizeLeadStatus(?string $status): string
    {
        return in_array($status, Lead::STATUSES, true)
            ? $status
            : Lead::STATUS_IN_PROGRESS;
    }

    protected function normalizeOpportunityStage(?string $stage): string
    {
        return in_array($stage, Opportunity::STAGES, true)
            ? $stage
            : Opportunity::STAGE_INITIAL_CONTACT;
    }

    protected function normalizeService(?string $service): ?string
    {
        if (! $service) {
            return null;
        }

        return Str::of($service)
            ->lower()
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    protected function normalizeYearMonth(?string $value): ?string
    {
        if (! $value || in_array($value, ['0', '#REF!', 'Submission Date'], true)) {
            return null;
        }

        return $value;
    }

    protected function value(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($row as $rowKey => $value) {
                if (mb_strtolower(trim((string) $rowKey)) === mb_strtolower(trim($key))) {
                    return trim((string) $value) !== '' ? trim((string) $value) : null;
                }
            }
        }

        return null;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseMonth(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('M-y', $value)->startOfMonth();
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseMoney(?string $value): ?float
    {
        if (! $value) {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\\-]/', '', str_ireplace('dh', '', $value));

        return $normalized === '' ? null : (float) $normalized;
    }

    protected function parseInteger(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/-?\\d+/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    protected function parseBoolean(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
    }
}
