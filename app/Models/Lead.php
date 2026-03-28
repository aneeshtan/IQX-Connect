<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUS_IN_PROGRESS = 'In-progress';

    public const STATUS_SALES_QUALIFIED = 'Sales Qualified';

    public const STATUS_DISQUALIFIED = 'Disqualified';

    public const STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_SALES_QUALIFIED,
        self::STATUS_DISQUALIFIED,
    ];

    public const DISQUALIFICATION_REASON_NO_ANSWER = 'No Answer (ONLY SYSTEM)';

    public const DISQUALIFICATION_REASON_MISMATCH = 'Mismatch of Needs';

    public const DISQUALIFICATION_REASON_DUPLICATE = 'Duplicate Lead';

    public const DISQUALIFICATION_REASON_GEO = 'Geo Limitations';

    public const DISQUALIFICATION_REASONS = [
        self::DISQUALIFICATION_REASON_NO_ANSWER,
        self::DISQUALIFICATION_REASON_MISMATCH,
        self::DISQUALIFICATION_REASON_DUPLICATE,
        self::DISQUALIFICATION_REASON_GEO,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'assigned_user_id',
        'external_key',
        'lead_id',
        'rfid',
        'lead_key',
        'contact_name',
        'company_name',
        'email',
        'phone',
        'service',
        'submission_date',
        'lead_source',
        'status',
        'disqualification_reason',
        'notes',
        'nurture_minutes',
        'nurture_hours',
        'lead_value',
        'hashed_email',
        'hashed_phone',
        'is_converted',
        'manual_entry',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'datetime',
            'lead_value' => 'decimal:2',
            'nurture_hours' => 'decimal:2',
            'is_converted' => 'boolean',
            'manual_entry' => 'boolean',
            'source_payload' => 'array',
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

    public function sheetSource(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(LeadStatusLog::class);
    }
}
