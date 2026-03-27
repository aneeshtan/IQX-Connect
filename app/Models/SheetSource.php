<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SheetSource extends Model
{
    public const SOURCE_KIND_GOOGLE_SHEET_CSV = 'google_sheet_csv';

    public const SOURCE_KIND_GOOGLE_SHEETS_API = 'google_sheets_api';

    public const SOURCE_KIND_UPLOADED_CSV = 'uploaded_csv';

    public const TYPE_LEADS = 'leads';

    public const TYPE_OPPORTUNITIES = 'opportunities';

    public const TYPE_REPORTS = 'reports';

    public const TYPE_GOOGLE_ADS = 'google_ads';

    public const TYPES = [
        self::TYPE_LEADS,
        self::TYPE_OPPORTUNITIES,
        self::TYPE_REPORTS,
        self::TYPE_GOOGLE_ADS,
    ];

    public const SOURCE_KINDS = [
        self::SOURCE_KIND_GOOGLE_SHEET_CSV,
        self::SOURCE_KIND_GOOGLE_SHEETS_API,
        self::SOURCE_KIND_UPLOADED_CSV,
    ];

    public static function isGoogleSheetUrl(?string $url): bool
    {
        return filled($url) && str_contains((string) $url, 'docs.google.com/spreadsheets/');
    }

    public static function normalizeSourceKind(string $requestedKind, ?string $url, bool $preferApi = false): string
    {
        if ($requestedKind === self::SOURCE_KIND_UPLOADED_CSV) {
            return self::SOURCE_KIND_UPLOADED_CSV;
        }

        if (! self::isGoogleSheetUrl($url)) {
            return $requestedKind;
        }

        return $preferApi ? self::SOURCE_KIND_GOOGLE_SHEETS_API : self::SOURCE_KIND_GOOGLE_SHEET_CSV;
    }

    protected $fillable = [
        'company_id',
        'workspace_id',
        'type',
        'name',
        'url',
        'source_kind',
        'description',
        'is_active',
        'sync_status',
        'last_synced_at',
        'last_error',
        'mapping',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'mapping' => 'array',
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

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }
}
