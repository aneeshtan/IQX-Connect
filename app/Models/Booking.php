<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_REQUESTED = 'Requested';

    public const STATUS_CONFIRMED = 'Confirmed';

    public const STATUS_ROLLED = 'Rolled';

    public const STATUS_IN_TRANSIT = 'In Transit';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REQUESTED,
        self::STATUS_CONFIRMED,
        self::STATUS_ROLLED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'account_id',
        'contact_id',
        'carrier_id',
        'shipment_job_id',
        'quote_id',
        'opportunity_id',
        'lead_id',
        'assigned_user_id',
        'booking_number',
        'external_reference',
        'carrier_confirmation_ref',
        'customer_name',
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
        'requested_etd',
        'requested_eta',
        'confirmed_etd',
        'confirmed_eta',
        'status',
        'notes',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'container_count' => 'integer',
            'weight_kg' => 'decimal:2',
            'volume_cbm' => 'decimal:3',
            'requested_etd' => 'datetime',
            'requested_eta' => 'datetime',
            'confirmed_etd' => 'datetime',
            'confirmed_eta' => 'datetime',
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

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function shipmentJob(): BelongsTo
    {
        return $this->belongsTo(ShipmentJob::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
