<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    public const MODE_OCEAN = 'Ocean';

    public const MODE_AIR = 'Air';

    public const MODE_ROAD = 'Road';

    public const MODE_RAIL = 'Rail';

    public const MODE_MULTIMODAL = 'Multimodal';

    public const MODES = [
        self::MODE_OCEAN,
        self::MODE_AIR,
        self::MODE_ROAD,
        self::MODE_RAIL,
        self::MODE_MULTIMODAL,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'name',
        'mode',
        'code',
        'scac_code',
        'iata_code',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'service_lanes',
        'notes',
        'is_active',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
