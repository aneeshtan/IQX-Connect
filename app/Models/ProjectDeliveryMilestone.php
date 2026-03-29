<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDeliveryMilestone extends Model
{
    public const STATUS_PENDING = 'Pending';

    public const STATUS_SCHEDULED = 'Scheduled';

    public const STATUS_IN_PROGRESS = 'In Progress';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_DELAYED = 'Delayed';

    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SCHEDULED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_DELAYED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'project_id',
        'assigned_user_id',
        'milestone_label',
        'sequence',
        'planned_date',
        'actual_date',
        'status',
        'site_location',
        'requires_crane',
        'installation_required',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'planned_date' => 'date',
            'actual_date' => 'date',
            'requires_crane' => 'boolean',
            'installation_required' => 'boolean',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
