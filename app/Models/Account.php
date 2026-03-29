<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    protected $fillable = [
        'company_id',
        'workspace_id',
        'assigned_user_id',
        'name',
        'slug',
        'primary_email',
        'primary_phone',
        'latest_service',
        'notes',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->orderBy('full_name');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function shipmentJobs(): HasMany
    {
        return $this->hasMany(ShipmentJob::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(AccountMetricSnapshot::class);
    }

    public function currentMetricSnapshot(): HasOne
    {
        return $this->hasOne(AccountMetricSnapshot::class)->where('snapshot_key', 'current');
    }

    public function segmentAssignments(): HasMany
    {
        return $this->hasMany(AccountSegmentAssignment::class);
    }
}
