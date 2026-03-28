<?php

namespace App\Services;

use App\Models\SheetSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

class CargoWiseSourceService
{
    public function readRows(SheetSource $source): array
    {
        $config = $this->config($source);
        $response = $this->request($config)->get($config['endpoint']);

        $response->throw();

        return match ($config['format']) {
            'csv' => $this->parseCsv($response->body()),
            'xml' => $this->parseXml($response->body(), $config['data_path']),
            default => $this->parseJson($response->json(), $config['data_path']),
        };
    }

    protected function config(SheetSource $source): array
    {
        $mapping = (array) ($source->mapping ?? []);
        $config = (array) data_get($mapping, 'cargowise', []);

        $endpoint = $source->url ?: data_get($config, 'endpoint');

        if (! filled($endpoint)) {
            throw new RuntimeException('CargoWise endpoint URL is required.');
        }

        $authMode = (string) (data_get($config, 'auth_mode') ?: 'basic');
        $format = (string) (data_get($config, 'format') ?: 'json');

        if (! array_key_exists($authMode, SheetSource::cargoWiseAuthModes())) {
            throw new RuntimeException('Unsupported CargoWise auth mode.');
        }

        if (! array_key_exists($format, SheetSource::cargoWiseFormats())) {
            throw new RuntimeException('Unsupported CargoWise payload format.');
        }

        if ($authMode === 'basic' && (! filled(data_get($config, 'username')) || ! filled(data_get($config, 'password')))) {
            throw new RuntimeException('CargoWise basic auth requires a username and password.');
        }

        if ($authMode === 'bearer' && ! filled(data_get($config, 'token'))) {
            throw new RuntimeException('CargoWise bearer auth requires an access token.');
        }

        return [
            'endpoint' => $endpoint,
            'auth_mode' => $authMode,
            'username' => (string) data_get($config, 'username', ''),
            'password' => (string) data_get($config, 'password', ''),
            'token' => (string) data_get($config, 'token', ''),
            'format' => $format,
            'data_path' => (string) data_get($config, 'data_path', ''),
        ];
    }

    protected function request(array $config)
    {
        $request = Http::timeout(45)->retry(1, 500)->acceptJson();

        return match ($config['auth_mode']) {
            'basic' => $request->withBasicAuth($config['username'], $config['password']),
            'bearer' => $request->withToken($config['token']),
            default => $request,
        };
    }

    protected function parseJson(mixed $payload, string $dataPath): array
    {
        $rows = $dataPath !== '' ? data_get($payload, $dataPath) : $payload;
        $rows = Arr::wrap($rows);

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->normalizeRow($row))
            ->values()
            ->all();
    }

    protected function parseXml(string $xml, string $dataPath): array
    {
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);

        if (! $document) {
            throw new RuntimeException('CargoWise XML response could not be parsed.');
        }

        $target = $document;

        if ($dataPath !== '') {
            $parts = collect(explode('.', $dataPath))
                ->map(fn ($part) => trim($part))
                ->filter()
                ->values();

            foreach ($parts as $part) {
                if (! isset($target->{$part})) {
                    throw new RuntimeException("CargoWise XML path [{$dataPath}] was not found.");
                }

                $target = $target->{$part};
            }
        }

        $nodes = [];

        foreach ($target as $node) {
            $nodes[] = json_decode(json_encode($node, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return collect($nodes)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->normalizeRow($row))
            ->values()
            ->all();
    }

    protected function parseCsv(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $headers = fgetcsv($stream, 0, ',', '"', '\\');

        if (! $headers) {
            fclose($stream);

            return [];
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $rows = [];

        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $rows[] = $this->normalizeRow(array_combine($headers, array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                array_slice($row, 0, count($headers)),
            )));
        }

        fclose($stream);

        return $rows;
    }

    protected function normalizeRow(array $row, string $prefix = ''): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalizedKey = trim($prefix.($prefix !== '' ? ' ' : '').Str::of((string) $key)->replace(['_', '-'], ' ')->title()->toString());

            if (is_array($value)) {
                $normalized += $this->normalizeRow($value, $normalizedKey);

                continue;
            }

            $normalized[$normalizedKey] = is_scalar($value) || $value === null
                ? (is_string($value) ? trim($value) : $value)
                : json_encode($value);
        }

        return $normalized;
    }
}
