<?php

namespace App\Services;

use App\Models\CollaborationEntry;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\ShipmentJob;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

        WorkspaceNotification::query()->create([
            'company_id' => $record->company_id,
            'workspace_id' => $record->workspace_id,
            'user_id' => $assignee->id,
            'actor_user_id' => $actor->id,
            'notable_type' => $record::class,
            'notable_id' => $record->getKey(),
            'notification_type' => WorkspaceNotification::TYPE_ASSIGNMENT,
            'action_tab' => $this->recordTab($record),
            'title' => 'Assigned to '.$this->recordLabel($record),
            'body' => $actor->name.' assigned you to '.$this->recordReference($record).'.',
            'is_read' => false,
            'data' => [
                'record_reference' => $this->recordReference($record),
            ],
        ]);
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
        });
    }

    public function recordLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Lead => 'lead',
            $record instanceof Opportunity => 'opportunity',
            $record instanceof Quote => 'quote',
            $record instanceof ShipmentJob => 'shipment',
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
