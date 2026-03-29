<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSegmentAssignment extends Model
{
    protected $fillable = [
        'company_id',
        'workspace_id',
        'account_id',
        'segment_definition_id',
        'account_metric_snapshot_id',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function segmentDefinition(): BelongsTo
    {
        return $this->belongsTo(CustomerSegmentDefinition::class, 'segment_definition_id');
    }

    public function metricSnapshot(): BelongsTo
    {
        return $this->belongsTo(AccountMetricSnapshot::class, 'account_metric_snapshot_id');
    }
}
