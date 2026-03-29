<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_BRIEF_RECEIVED = 'Brief Received';

    public const STATUS_DESIGN_REVIEW = 'Design Review';

    public const STATUS_DRAWINGS_SUBMITTED = 'Drawings Submitted';

    public const STATUS_DRAWINGS_APPROVED = 'Drawings Approved';

    public const STATUS_FABRICATION = 'Fabrication';

    public const STATUS_READY_FOR_DELIVERY = 'Ready For Delivery';

    public const STATUS_DELIVERED = 'Delivered';

    public const STATUS_INSTALLED = 'Installed';

    public const STATUS_CLOSED = 'Closed';

    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_BRIEF_RECEIVED,
        self::STATUS_DESIGN_REVIEW,
        self::STATUS_DRAWINGS_SUBMITTED,
        self::STATUS_DRAWINGS_APPROVED,
        self::STATUS_FABRICATION,
        self::STATUS_READY_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_INSTALLED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'account_id',
        'contact_id',
        'opportunity_id',
        'lead_id',
        'assigned_user_id',
        'project_number',
        'project_name',
        'customer_name',
        'contact_name',
        'contact_email',
        'service_type',
        'container_type',
        'unit_quantity',
        'scope_summary',
        'site_location',
        'target_delivery_date',
        'target_installation_date',
        'actual_delivery_date',
        'actual_installation_date',
        'estimated_value',
        'status',
        'notes',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'unit_quantity' => 'integer',
            'target_delivery_date' => 'date',
            'target_installation_date' => 'date',
            'actual_delivery_date' => 'date',
            'actual_installation_date' => 'date',
            'estimated_value' => 'decimal:2',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function drawings(): HasMany
    {
        return $this->hasMany(ProjectDrawing::class)->orderByDesc('submitted_at')->orderByDesc('id');
    }

    public function deliveryMilestones(): HasMany
    {
        return $this->hasMany(ProjectDeliveryMilestone::class)->orderBy('sequence')->orderBy('id');
    }

    public function collaborationEntries(): MorphMany
    {
        return $this->morphMany(CollaborationEntry::class, 'notable')->latest();
    }
}
