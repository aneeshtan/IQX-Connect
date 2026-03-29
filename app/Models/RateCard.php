<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateCard extends Model
{
    public const MODE_AIR = 'Air Freight';

    public const MODE_OCEAN = 'Ocean Freight';

    public const MODE_ROAD = 'Road Freight';

    public const MODE_CUSTOMS = 'Customs Clearance';

    public const MODE_WAREHOUSING = 'Warehousing';

    public const MODES = [
        self::MODE_AIR,
        self::MODE_OCEAN,
        self::MODE_ROAD,
        self::MODE_CUSTOMS,
        self::MODE_WAREHOUSING,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'carrier_id',
        'assigned_user_id',
        'rate_code',
        'customer_name',
        'service_mode',
        'origin',
        'destination',
        'via_port',
        'incoterm',
        'commodity',
        'equipment_type',
        'transit_days',
        'buy_amount',
        'sell_amount',
        'margin_amount',
        'currency',
        'valid_from',
        'valid_until',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transit_days' => 'integer',
            'buy_amount' => 'decimal:2',
            'sell_amount' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
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

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
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
