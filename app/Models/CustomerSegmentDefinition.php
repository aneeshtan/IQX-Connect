<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSegmentDefinition extends Model
{
    public const COLOR_EMERALD = 'emerald';

    public const COLOR_AMBER = 'amber';

    public const COLOR_ROSE = 'rose';

    public const COLOR_SKY = 'sky';

    public const COLOR_VIOLET = 'violet';

    public const COLORS = [
        self::COLOR_EMERALD,
        self::COLOR_AMBER,
        self::COLOR_ROSE,
        self::COLOR_SKY,
        self::COLOR_VIOLET,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'name',
        'slug',
        'description',
        'color',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
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

    public function rules(): HasMany
    {
        return $this->hasMany(CustomerSegmentRule::class, 'segment_definition_id')->orderBy('sort_order')->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AccountSegmentAssignment::class, 'segment_definition_id');
    }
}
