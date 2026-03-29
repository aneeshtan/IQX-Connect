<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCostingLine extends Model
{
    public const TYPE_COST = 'Cost';

    public const TYPE_REVENUE = 'Revenue';

    public const TYPES = [
        self::TYPE_COST,
        self::TYPE_REVENUE,
    ];

    protected $fillable = [
        'job_costing_id',
        'line_type',
        'charge_code',
        'description',
        'vendor_name',
        'quantity',
        'unit_amount',
        'total_amount',
        'is_billable',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_billable' => 'boolean',
        ];
    }

    public function jobCosting(): BelongsTo
    {
        return $this->belongsTo(JobCosting::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
