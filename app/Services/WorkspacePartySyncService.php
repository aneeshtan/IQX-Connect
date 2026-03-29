<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\ShipmentJob;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkspacePartySyncService
{
    public function syncRecord(Model $record): void
    {
        if (! $record->workspace_id) {
            return;
        }

        [$accountName, $contactName, $email, $phone, $service, $activityAt] = $this->extractIdentity($record);

        $account = $this->resolveAccount(
            workspaceId: (int) $record->workspace_id,
            companyId: $record->company_id ? (int) $record->company_id : null,
            accountName: $accountName,
            email: $email,
            phone: $phone,
            service: $service,
            activityAt: $activityAt,
        );

        $contact = $this->resolveContact(
            workspaceId: (int) $record->workspace_id,
            companyId: $record->company_id ? (int) $record->company_id : null,
            account: $account,
            contactName: $contactName,
            email: $email,
            phone: $phone,
            activityAt: $activityAt,
        );

        $updates = [];

        if ($account && (($record->account_id ?? null) !== $account->id)) {
            $updates['account_id'] = $account->id;
        }

        if ($contact && (($record->contact_id ?? null) !== $contact->id)) {
            $updates['contact_id'] = $contact->id;
        }

        if ($updates !== []) {
            $record->forceFill($updates)->saveQuietly();
        }
    }

    protected function extractIdentity(Model $record): array
    {
        $activityAt = null;

        if ($record instanceof Lead) {
            $activityAt = $record->submission_date;

            return [
                $record->company_name,
                $record->contact_name,
                $record->email,
                $record->phone,
                $record->service,
                $activityAt,
            ];
        }

        if ($record instanceof Opportunity) {
            $record->loadMissing('lead');
            $activityAt = $record->submission_date;

            return [
                $record->company_name,
                $record->lead?->contact_name,
                $record->contact_email ?: $record->lead?->email,
                $record->lead?->phone,
                $record->required_service,
                $activityAt,
            ];
        }

        if ($record instanceof Quote) {
            $activityAt = $record->quoted_at;

            return [
                $record->company_name,
                $record->contact_name,
                $record->contact_email,
                null,
                $record->service_mode,
                $activityAt,
            ];
        }

        if ($record instanceof ShipmentJob) {
            $activityAt = $record->estimated_departure_at ?: $record->created_at;

            return [
                $record->company_name,
                $record->contact_name,
                $record->contact_email,
                null,
                $record->service_mode,
                $activityAt,
            ];
        }

        if ($record instanceof Booking) {
            $activityAt = $record->requested_etd ?: $record->created_at;

            return [
                $record->customer_name,
                $record->contact_name,
                $record->contact_email,
                null,
                $record->service_mode,
                $activityAt,
            ];
        }

        if ($record instanceof Invoice) {
            $activityAt = $record->issue_date;

            return [
                $record->bill_to_name,
                null,
                $record->contact_email,
                null,
                null,
                $activityAt,
            ];
        }

        return [null, null, null, null, null, null];
    }

    protected function resolveAccount(
        int $workspaceId,
        ?int $companyId,
        ?string $accountName,
        ?string $email,
        ?string $phone,
        ?string $service,
        CarbonInterface|string|null $activityAt,
    ): ?Account {
        $accountName = $this->normalize($accountName);

        if ($accountName === null) {
            return null;
        }

        $account = Account::query()
            ->where('workspace_id', $workspaceId)
            ->get()
            ->first(fn (Account $candidate) => Str::lower(trim($candidate->name)) === Str::lower($accountName));

        if (! $account) {
            $account = Account::create([
                'company_id' => $companyId,
                'workspace_id' => $workspaceId,
                'name' => $accountName,
                'slug' => $this->nextUniqueSlug($workspaceId, $accountName),
                'primary_email' => $this->normalize($email),
                'primary_phone' => $this->normalize($phone),
                'latest_service' => $this->normalize($service),
                'last_activity_at' => $activityAt,
            ]);

            return $account;
        }

        $updates = [];

        if (! $account->primary_email && filled($email)) {
            $updates['primary_email'] = $this->normalize($email);
        }

        if (! $account->primary_phone && filled($phone)) {
            $updates['primary_phone'] = $this->normalize($phone);
        }

        if (filled($service)) {
            $updates['latest_service'] = $this->normalize($service);
        }

        if ($activityAt && (! $account->last_activity_at || $account->last_activity_at->lt($activityAt))) {
            $updates['last_activity_at'] = $activityAt;
        }

        if ($updates !== []) {
            $account->forceFill($updates)->saveQuietly();
        }

        return $account;
    }

    protected function resolveContact(
        int $workspaceId,
        ?int $companyId,
        ?Account $account,
        ?string $contactName,
        ?string $email,
        ?string $phone,
        CarbonInterface|string|null $activityAt,
    ): ?Contact {
        $contactName = $this->normalize($contactName);
        $email = $this->normalize($email);
        $phone = $this->normalize($phone);

        if ($contactName === null && $email === null && $phone === null) {
            return null;
        }

        $query = Contact::query()->where('workspace_id', $workspaceId);
        $contact = null;

        if ($email) {
            $contact = (clone $query)
                ->whereRaw('lower(email) = ?', [Str::lower($email)])
                ->first();
        }

        if (! $contact && $account && $contactName) {
            $contact = (clone $query)
                ->where('account_id', $account->id)
                ->get()
                ->first(fn (Contact $candidate) => Str::lower(trim($candidate->full_name)) === Str::lower($contactName));
        }

        if (! $contact && $phone) {
            $contact = (clone $query)->where('phone', $phone)->first();
        }

        if (! $contact) {
            $contact = Contact::create([
                'company_id' => $companyId,
                'workspace_id' => $workspaceId,
                'account_id' => $account?->id,
                'full_name' => $contactName ?: ($email ?: 'Unnamed contact'),
                'email' => $email,
                'phone' => $phone,
                'last_activity_at' => $activityAt,
            ]);

            return $contact;
        }

        $updates = [];

        if ($account && $contact->account_id !== $account->id) {
            $updates['account_id'] = $account->id;
        }

        if (! $contact->full_name && $contactName) {
            $updates['full_name'] = $contactName;
        }

        if (! $contact->email && $email) {
            $updates['email'] = $email;
        }

        if (! $contact->phone && $phone) {
            $updates['phone'] = $phone;
        }

        if ($activityAt && (! $contact->last_activity_at || $contact->last_activity_at->lt($activityAt))) {
            $updates['last_activity_at'] = $activityAt;
        }

        if ($updates !== []) {
            $contact->forceFill($updates)->saveQuietly();
        }

        return $contact;
    }

    protected function nextUniqueSlug(int $workspaceId, string $name): string
    {
        $base = Str::slug($name) ?: 'account';
        $slug = $base;
        $iteration = 2;

        while (Account::query()->where('workspace_id', $workspaceId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$iteration}";
            $iteration++;
        }

        return $slug;
    }

    protected function normalize(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
