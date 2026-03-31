<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SheetSource;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class WordPressLeadWebhookService
{
    public function ingest(SheetSource $source, array $payload, ?string $token = null): Lead
    {
        if ($source->source_kind !== SheetSource::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK) {
            throw new RuntimeException('This source is not configured for WordPress form webhooks.');
        }

        if ($source->type !== SheetSource::TYPE_LEADS) {
            throw new RuntimeException('WordPress form webhooks currently support lead ingestion only.');
        }

        if (! $source->workspace_id) {
            throw new RuntimeException('WordPress webhook sources must be attached to a workspace.');
        }

        $expectedToken = (string) data_get($source->mapping, 'wordpress.secret', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, (string) $token)) {
            throw new RuntimeException('Invalid source token.');
        }

        $provider = (string) data_get($source->mapping, 'wordpress.provider', SheetSource::WORDPRESS_PROVIDER_FLUENT_FORMS);
        $submittedAt = $this->parseDate($this->findValue($payload, [
            'submission_date',
            'submission_create_date',
            'created_at',
            'date_created',
            'submitted_at',
            '_date',
        ])) ?? now();

        $contactName = $this->findValue($payload, [
            'name',
            'full_name',
            'contact_name',
            'your_name',
            'your-name',
            'field_name',
        ]);

        $companyName = $this->findValue($payload, [
            'company',
            'company_name',
            'company-name',
            'organization',
            'business_name',
        ]);

        $email = $this->findValue($payload, [
            'email',
            'email_address',
            'your_email',
            'your-email',
        ]);

        $phone = $this->findValue($payload, [
            'phone',
            'phone_number',
            'telephone',
            'tel',
            'mobile',
            'your_phone',
            'your-phone',
        ]);

        $service = $this->findValue($payload, [
            'service',
            'required_service',
            'service_type',
            'subject',
            'your_subject',
            'your-subject',
        ]);

        $notes = $this->findValue($payload, [
            'message',
            'notes',
            'comment',
            'comments',
            'your_message',
            'your-message',
            'description',
        ]);

        $leadSource = $this->findValue($payload, ['lead_source', 'source'])
            ?: match ($provider) {
                SheetSource::WORDPRESS_PROVIDER_CONTACT_FORM_7 => 'Contact Form 7',
                default => 'Fluent Forms',
            };

        if (! $contactName && ! $companyName && ! $email && ! $phone && ! $notes) {
            throw new RuntimeException('No recognizable lead fields were found in the webhook payload.');
        }

        $externalKey = $this->findValue($payload, [
            'entry_id',
            'submission_id',
            'response_id',
            '_entry_id',
            'id',
        ]);

        if (! $externalKey) {
            $externalKey = sha1(json_encode([
                'workspace' => $source->workspace_id,
                'provider' => $provider,
                'email' => $email,
                'phone' => $phone,
                'name' => $contactName,
                'company' => $companyName,
                'submitted_at' => $submittedAt?->toIso8601String(),
                'payload' => $payload,
            ]));
        } else {
            $externalKey = 'wp-'.$provider.'-'.$externalKey;
        }

        $lead = Lead::firstOrNew([
            'workspace_id' => $source->workspace_id,
            'external_key' => $externalKey,
        ]);

        $lead->fill([
            'company_id' => $source->company_id,
            'workspace_id' => $source->workspace_id,
            'sheet_source_id' => $source->id,
            'contact_name' => $contactName,
            'company_name' => $companyName,
            'email' => $email,
            'phone' => $phone,
            'service' => $this->normalizeService($service),
            'submission_date' => $submittedAt,
            'lead_source' => $leadSource,
            'status' => $lead->exists ? $lead->status : Lead::STATUS_IN_PROGRESS,
            'notes' => $lead->notes ?: $notes,
            'manual_entry' => false,
            'source_payload' => [
                'provider' => $provider,
                'payload' => $payload,
            ],
        ]);

        $lead->save();

        $source->forceFill([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'last_error' => null,
        ])->save();

        return $lead;
    }

    protected function findValue(array $payload, array $candidates): ?string
    {
        $flattened = $this->flattenPayload($payload);

        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeKey($candidate);

            foreach ($flattened as $key => $value) {
                if ($value === null || is_array($value)) {
                    continue;
                }

                if ($this->normalizeKey($key) !== $normalizedCandidate) {
                    continue;
                }

                $stringValue = trim((string) $value);

                if ($stringValue !== '') {
                    return $stringValue;
                }
            }
        }

        return null;
    }

    protected function flattenPayload(array $payload, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flattened += $this->flattenPayload($value, $path);
                continue;
            }

            $flattened[$path] = $value;
            $flattened[(string) $key] = $value;
        }

        return $flattened;
    }

    protected function normalizeKey(string $key): string
    {
        return Str::of($key)
            ->lower()
            ->replace(['.', '-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->trim('_')
            ->toString();
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

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
