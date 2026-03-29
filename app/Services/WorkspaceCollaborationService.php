<?php

namespace App\Services;

use App\Mail\WorkspaceActivityMail;
use App\Models\Account;
use App\Models\Booking;
use App\Models\CollaborationEntry;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\JobCosting;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\ShipmentJob;
use App\Models\User;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WorkspaceCollaborationService
{
    public function addEntry(
        Model $record,
        User $actor,
        string $entryType,
        string $body,
        ?User $recipient = null,
    ): CollaborationEntry {
        /** @var Company|null $company */
        $company = $record->company;

        $entry = CollaborationEntry::query()->create([
            'company_id' => $company?->id,
            'workspace_id' => $record->workspace_id,
            'user_id' => $actor->id,
            'recipient_user_id' => $recipient?->id,
            'notable_type' => $record::class,
            'notable_id' => $record->getKey(),
            'entry_type' => $entryType,
            'body' => trim($body),
            'metadata' => [
                'record_reference' => $this->recordReference($record),
            ],
        ]);

        $this->createNotificationsForEntry($record, $actor, $entry, $recipient);

        return $entry;
    }

    public function notifyAssignment(Model $record, User $actor, ?User $assignee): void
    {
        if (! $assignee || $assignee->id === $actor->id) {
            return;
        }

        $membership = $this->workspaceMembership((int) $record->workspace_id, $assignee->id);
        $title = 'Assigned to '.$this->recordLabel($record);
        $body = $actor->name.' assigned you to '.$this->recordReference($record).'.';

        if ($this->allowsChannel($membership, WorkspaceMembership::CHANNEL_IN_APP, WorkspaceNotification::TYPE_ASSIGNMENT)) {
            WorkspaceNotification::query()->create([
                'company_id' => $record->company_id,
                'workspace_id' => $record->workspace_id,
                'user_id' => $assignee->id,
                'actor_user_id' => $actor->id,
                'notable_type' => $record::class,
                'notable_id' => $record->getKey(),
                'notification_type' => WorkspaceNotification::TYPE_ASSIGNMENT,
                'action_tab' => $this->recordTab($record),
                'title' => $title,
                'body' => $body,
                'is_read' => false,
                'data' => [
                    'record_reference' => $this->recordReference($record),
                ],
            ]);
        }

        $this->sendEmailNotificationIfEnabled(
            $membership,
            $assignee,
            WorkspaceNotification::TYPE_ASSIGNMENT,
            $record,
            $actor,
            $title,
            $body,
        );
    }

    protected function createNotificationsForEntry(Model $record, User $actor, CollaborationEntry $entry, ?User $recipient): void
    {
        $recipientIds = collect();

        if ($recipient && $recipient->id !== $actor->id) {
            $recipientIds->push($recipient->id);
        }

        $assignedUserId = (int) ($record->assigned_user_id ?? 0);

        if ($assignedUserId > 0 && $assignedUserId !== $actor->id) {
            $recipientIds->push($assignedUserId);
        }

        $recipientIds = $recipientIds->unique()->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $notificationType = $entry->entry_type === CollaborationEntry::TYPE_MESSAGE
            ? WorkspaceNotification::TYPE_MESSAGE
            : WorkspaceNotification::TYPE_NOTE;

        $title = $entry->entry_type === CollaborationEntry::TYPE_MESSAGE
            ? 'New message on '.$this->recordLabel($record)
            : 'New note on '.$this->recordLabel($record);

        $body = $entry->entry_type === CollaborationEntry::TYPE_MESSAGE
            ? $actor->name.' sent a message on '.$this->recordReference($record).'.'
            : $actor->name.' added a note on '.$this->recordReference($record).'.';

        $recipientIds->each(function (int $userId) use ($record, $actor, $notificationType, $title, $body, $entry): void {
            $recipient = User::query()->find($userId);

            if (! $recipient) {
                return;
            }

            $membership = $this->workspaceMembership((int) $record->workspace_id, $recipient->id);

            if ($this->allowsChannel($membership, WorkspaceMembership::CHANNEL_IN_APP, $notificationType)) {
                WorkspaceNotification::query()->create([
                    'company_id' => $record->company_id,
                    'workspace_id' => $record->workspace_id,
                    'user_id' => $userId,
                    'actor_user_id' => $actor->id,
                    'notable_type' => $record::class,
                    'notable_id' => $record->getKey(),
                    'notification_type' => $notificationType,
                    'action_tab' => $this->recordTab($record),
                    'title' => $title,
                    'body' => $body,
                    'is_read' => false,
                    'data' => [
                        'entry_id' => $entry->id,
                        'record_reference' => $this->recordReference($record),
                        'entry_preview' => Str::limit($entry->body, 160),
                    ],
                ]);
            }

            $this->sendEmailNotificationIfEnabled(
                $membership,
                $recipient,
                $notificationType,
                $record,
                $actor,
                $title,
                $body,
            );
        });
    }

    protected function workspaceMembership(int $workspaceId, int $userId): ?WorkspaceMembership
    {
        return WorkspaceMembership::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();
    }

    protected function allowsChannel(?WorkspaceMembership $membership, string $channel, string $notificationType): bool
    {
        if (! $membership) {
            $defaults = WorkspaceMembership::defaultNotificationPreferences();

            return (bool) data_get($defaults, "channels.{$channel}", false)
                && (bool) data_get($defaults, "events.{$notificationType}", false);
        }

        return $membership->allows($channel, $notificationType);
    }

    protected function sendEmailNotificationIfEnabled(
        ?WorkspaceMembership $membership,
        User $recipient,
        string $notificationType,
        Model $record,
        User $actor,
        string $title,
        string $body,
    ): void {
        if (! $this->allowsChannel($membership, WorkspaceMembership::CHANNEL_EMAIL, $notificationType)) {
            return;
        }

        if (! filled($recipient->email)) {
            return;
        }

        Mail::to($recipient->email)->send(new WorkspaceActivityMail(
            workspaceName: $record->workspace?->name ?: 'IQX Connect',
            title: $title,
            body: $body,
            recordReference: $this->recordReference($record),
            recordLabel: $this->recordLabel($record),
            actor: $actor,
            actionUrl: route('dashboard', ['tab' => $this->recordTab($record)]),
        ));
    }

    public function recordLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Lead => 'lead',
            $record instanceof Opportunity => 'opportunity',
            $record instanceof Quote => 'quote',
            $record instanceof ShipmentJob => 'shipment',
            $record instanceof Booking => 'booking',
            $record instanceof JobCosting => 'job costing',
            $record instanceof Invoice => 'invoice',
            $record instanceof Contact => 'contact',
            $record instanceof Account => 'customer',
            default => 'record',
        };
    }

    public function recordTab(Model $record): string
    {
        return match (true) {
            $record instanceof Lead => 'leads',
            $record instanceof Opportunity => 'opportunities',
            $record instanceof Quote => 'quotes',
            $record instanceof ShipmentJob => 'shipments',
            $record instanceof Booking => 'bookings',
            $record instanceof JobCosting => 'costings',
            $record instanceof Invoice => 'invoices',
            $record instanceof Contact => 'contacts',
            $record instanceof Account => 'customers',
            default => 'leads',
        };
    }

    public function recordReference(Model $record): string
    {
        return match (true) {
            $record instanceof Lead => $record->lead_id ?: ($record->company_name ?: 'Lead #'.$record->getKey()),
            $record instanceof Opportunity => $record->external_key ?: ($record->company_name ?: 'Opportunity #'.$record->getKey()),
            $record instanceof Quote => $record->quote_number ?: ($record->company_name ?: 'Quote #'.$record->getKey()),
            $record instanceof ShipmentJob => $record->job_number ?: ($record->company_name ?: 'Shipment #'.$record->getKey()),
            $record instanceof Booking => $record->booking_number ?: ($record->customer_name ?: 'Booking #'.$record->getKey()),
            $record instanceof JobCosting => $record->costing_number ?: ($record->customer_name ?: 'Job costing #'.$record->getKey()),
            $record instanceof Invoice => $record->invoice_number ?: ($record->bill_to_name ?: 'Invoice #'.$record->getKey()),
            $record instanceof Contact => $record->full_name ?: ($record->email ?: 'Contact #'.$record->getKey()),
            $record instanceof Account => $record->name ?: 'Customer #'.$record->getKey(),
            default => class_basename($record).' #'.$record->getKey(),
        };
    }

    public function recentEntries(Model $record): Collection
    {
        return CollaborationEntry::query()
            ->with(['user', 'recipientUser'])
            ->where('workspace_id', $record->workspace_id)
            ->where('notable_type', $record::class)
            ->where('notable_id', $record->getKey())
            ->latest()
            ->limit(12)
            ->get();
    }
}
