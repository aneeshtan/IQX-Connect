<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkspaceNotification extends Model
{
    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_NOTE = 'note';

    public const TYPE_MESSAGE = 'message';

    public const TYPES = [
        self::TYPE_ASSIGNMENT,
        self::TYPE_NOTE,
        self::TYPE_MESSAGE,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'user_id',
        'actor_user_id',
        'notable_type',
        'notable_id',
        'notification_type',
        'action_tab',
        'title',
        'body',
        'is_read',
        'read_at',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
