<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quote extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_SENT = 'Sent';

    public const STATUS_ACCEPTED = 'Accepted';

    public const STATUS_DECLINED = 'Declined';

    public const STATUS_EXPIRED = 'Expired';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_EXPIRED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'account_id',
        'contact_id',
        'rate_card_id',
        'opportunity_id',
        'lead_id',
        'assigned_user_id',
        'quote_number',
        'company_name',
        'contact_name',
        'contact_email',
        'service_mode',
        'origin',
        'destination',
        'incoterm',
        'commodity',
        'equipment_type',
        'weight_kg',
        'volume_cbm',
        'buy_amount',
        'sell_amount',
        'margin_amount',
        'currency',
        'status',
        'valid_until',
        'quoted_at',
        'notes',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'volume_cbm' => 'decimal:3',
            'buy_amount' => 'decimal:2',
            'sell_amount' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'valid_until' => 'date',
            'quoted_at' => 'datetime',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function shipmentJobs(): HasMany
    {
        return $this->hasMany(ShipmentJob::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function jobCostings(): HasMany
    {
        return $this->hasMany(JobCosting::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function collaborationEntries(): MorphMany
    {
        return $this->morphMany(CollaborationEntry::class, 'notable')->latest();
    }
}
