<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    public const STAGE_INITIAL_CONTACT = 'Initial Contact';

    public const STAGE_PROPOSAL_SENT = 'Proposal Sent';

    public const STAGE_CLOSED_WON = 'Closed Won';

    public const STAGE_CLOSED_LOST = 'Closed Lost';

    public const STAGE_NO_RESPONSE = 'No response';

    public const STAGE_DRAWINGS_SUBMITTED = 'Drawings submitted';

    public const STAGE_DECISION_MAKING = 'Decision Making';

    public const STAGE_PROJECT_DELAY = 'Project delay';

    public const STAGES = [
        self::STAGE_INITIAL_CONTACT,
        self::STAGE_PROPOSAL_SENT,
        self::STAGE_CLOSED_WON,
        self::STAGE_CLOSED_LOST,
        self::STAGE_NO_RESPONSE,
        self::STAGE_DRAWINGS_SUBMITTED,
        self::STAGE_DECISION_MAKING,
        self::STAGE_PROJECT_DELAY,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'lead_id',
        'assigned_user_id',
        'external_key',
        'rfid',
        'lead_reference',
        'company_name',
        'contact_email',
        'lead_source',
        'required_service',
        'revenue_potential',
        'project_timeline_days',
        'sales_stage',
        'reason_for_loss',
        'notes',
        'submission_date',
        'year_month',
        'manual_entry',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'datetime',
            'revenue_potential' => 'decimal:2',
            'manual_entry' => 'boolean',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
