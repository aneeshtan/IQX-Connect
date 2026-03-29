<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentJob extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_BOOKING_REQUESTED = 'Booking Requested';

    public const STATUS_BOOKED = 'Booked';

    public const STATUS_IN_TRANSIT = 'In Transit';

    public const STATUS_CUSTOMS_CLEARANCE = 'Customs Clearance';

    public const STATUS_DELIVERED = 'Delivered';

    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_BOOKING_REQUESTED,
        self::STATUS_BOOKED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_CUSTOMS_CLEARANCE,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'account_id',
        'contact_id',
        'opportunity_id',
        'quote_id',
        'lead_id',
        'assigned_user_id',
        'job_number',
        'external_reference',
        'company_name',
        'contact_name',
        'contact_email',
        'service_mode',
        'origin',
        'destination',
        'incoterm',
        'commodity',
        'equipment_type',
        'container_count',
        'weight_kg',
        'volume_cbm',
        'carrier_name',
        'vessel_name',
        'voyage_number',
        'house_bill_no',
        'master_bill_no',
        'estimated_departure_at',
        'estimated_arrival_at',
        'actual_departure_at',
        'actual_arrival_at',
        'status',
        'buy_amount',
        'sell_amount',
        'margin_amount',
        'currency',
        'notes',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'container_count' => 'integer',
            'weight_kg' => 'decimal:2',
            'volume_cbm' => 'decimal:3',
            'buy_amount' => 'decimal:2',
            'sell_amount' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'estimated_departure_at' => 'datetime',
            'estimated_arrival_at' => 'datetime',
            'actual_departure_at' => 'datetime',
            'actual_arrival_at' => 'datetime',
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

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
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

    public function milestones(): HasMany
    {
        return $this->hasMany(ShipmentMilestone::class)->orderBy('sequence')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ShipmentDocument::class)->orderByDesc('uploaded_at')->orderByDesc('created_at');
    }
}
