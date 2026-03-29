<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CollaborationEntry extends Model
{
    public const TYPE_NOTE = 'note';

    public const TYPE_MESSAGE = 'message';

    public const TYPES = [
        self::TYPE_NOTE,
        self::TYPE_MESSAGE,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'user_id',
        'recipient_user_id',
        'notable_type',
        'notable_id',
        'entry_type',
        'body',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
