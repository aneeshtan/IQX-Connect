<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentMilestone extends Model
{
    public const STATUS_PENDING = 'Pending';

    public const STATUS_IN_PROGRESS = 'In Progress';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_EXCEPTION = 'Exception';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_EXCEPTION,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'shipment_job_id',
        'event_key',
        'label',
        'sequence',
        'status',
        'planned_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'planned_at' => 'datetime',
            'completed_at' => 'datetime',
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
}
