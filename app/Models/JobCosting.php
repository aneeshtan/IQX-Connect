<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCosting extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_IN_PROGRESS = 'In Progress';

    public const STATUS_READY_TO_INVOICE = 'Ready To Invoice';

    public const STATUS_FINALIZED = 'Finalized';

    public const STATUS_CLOSED = 'Closed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_IN_PROGRESS,
        self::STATUS_READY_TO_INVOICE,
        self::STATUS_FINALIZED,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'shipment_job_id',
        'quote_id',
        'opportunity_id',
        'lead_id',
        'assigned_user_id',
        'costing_number',
        'customer_name',
        'service_mode',
        'currency',
        'total_cost_amount',
        'total_sell_amount',
        'margin_amount',
        'margin_percent',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_cost_amount' => 'decimal:2',
            'total_sell_amount' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'margin_percent' => 'decimal:2',
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

    public function lines(): HasMany
    {
        return $this->hasMany(JobCostingLine::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
