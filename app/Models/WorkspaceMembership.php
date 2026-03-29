<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WorkspaceMembership extends Pivot
{
    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    public const EVENT_ASSIGNMENT = WorkspaceNotification::TYPE_ASSIGNMENT;

    public const EVENT_NOTE = WorkspaceNotification::TYPE_NOTE;

    public const EVENT_MESSAGE = WorkspaceNotification::TYPE_MESSAGE;

    public const CHANNELS = [
        self::CHANNEL_IN_APP,
        self::CHANNEL_EMAIL,
    ];

    public const EVENTS = [
        self::EVENT_ASSIGNMENT,
        self::EVENT_NOTE,
        self::EVENT_MESSAGE,
    ];

    protected $table = 'workspace_user';

    public $incrementing = true;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'job_title',
        'is_owner',
        'notification_preferences',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultNotificationPreferences(): array
    {
        return [
            'channels' => [
                self::CHANNEL_IN_APP => true,
                self::CHANNEL_EMAIL => false,
            ],
            'events' => [
                self::EVENT_ASSIGNMENT => true,
                self::EVENT_NOTE => true,
                self::EVENT_MESSAGE => true,
            ],
        ];
    }

    public static function normalizeNotificationPreferences(?array $preferences): array
    {
        $defaults = static::defaultNotificationPreferences();

        $channels = collect(static::CHANNELS)
            ->mapWithKeys(fn (string $channel) => [
                $channel => (bool) data_get($preferences, "channels.{$channel}", $defaults['channels'][$channel] ?? false),
            ])
            ->all();

        $events = collect(static::EVENTS)
            ->mapWithKeys(fn (string $event) => [
                $event => (bool) data_get($preferences, "events.{$event}", $defaults['events'][$event] ?? false),
            ])
            ->all();

        return [
            'channels' => $channels,
            'events' => $events,
        ];
    }

    public function notificationPreferences(): array
    {
        return static::normalizeNotificationPreferences($this->notification_preferences);
    }

    public function allows(string $channel, string $event): bool
    {
        $preferences = $this->notificationPreferences();

        return (bool) data_get($preferences, "channels.{$channel}", false)
            && (bool) data_get($preferences, "events.{$event}", false);
    }
}
