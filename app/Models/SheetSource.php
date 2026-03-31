<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SheetSource extends Model
{
    public const SOURCE_KIND_GOOGLE_SHEET_CSV = 'google_sheet_csv';

    public const SOURCE_KIND_GOOGLE_SHEETS_API = 'google_sheets_api';

    public const SOURCE_KIND_UPLOADED_CSV = 'uploaded_csv';

    public const SOURCE_KIND_CARGOWISE_API = 'cargowise_api';

    public const SOURCE_KIND_WORDPRESS_FORM_WEBHOOK = 'wordpress_form_webhook';

    public const WORDPRESS_PROVIDER_FLUENT_FORMS = 'fluent_forms';

    public const WORDPRESS_PROVIDER_CONTACT_FORM_7 = 'contact_form_7';

    public const TYPE_LEADS = 'leads';

    public const TYPE_OPPORTUNITIES = 'opportunities';

    public const TYPE_CONTACTS = 'contacts';

    public const TYPE_CUSTOMERS = 'customers';

    public const TYPE_QUOTES = 'quotes';

    public const TYPE_SHIPMENTS = 'shipments';

    public const TYPE_CARRIERS = 'carriers';

    public const TYPE_BOOKINGS = 'bookings';

    public const TYPE_REPORTS = 'reports';

    public const TYPE_GOOGLE_ADS = 'google_ads';

    public const TYPES = [
        self::TYPE_LEADS,
        self::TYPE_OPPORTUNITIES,
        self::TYPE_CONTACTS,
        self::TYPE_CUSTOMERS,
        self::TYPE_QUOTES,
        self::TYPE_SHIPMENTS,
        self::TYPE_CARRIERS,
        self::TYPE_BOOKINGS,
        self::TYPE_REPORTS,
        self::TYPE_GOOGLE_ADS,
    ];

    public const SOURCE_KINDS = [
        self::SOURCE_KIND_GOOGLE_SHEET_CSV,
        self::SOURCE_KIND_GOOGLE_SHEETS_API,
        self::SOURCE_KIND_UPLOADED_CSV,
        self::SOURCE_KIND_CARGOWISE_API,
        self::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK,
    ];

    public static function isGoogleSheetUrl(?string $url): bool
    {
        return filled($url) && str_contains((string) $url, 'docs.google.com/spreadsheets/');
    }

    public static function normalizeSourceKind(string $requestedKind, ?string $url, bool $preferApi = false): string
    {
        if ($requestedKind === self::SOURCE_KIND_CARGOWISE_API) {
            return self::SOURCE_KIND_CARGOWISE_API;
        }

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

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function shipmentJobs(): HasMany
    {
        return $this->hasMany(ShipmentJob::class);
    }

    public function carriers(): HasMany
    {
        return $this->hasMany(Carrier::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    public static function availableTypes(): array
    {
        $templateModules = collect(config('workspace_templates.templates', []))
            ->pluck('modules')
            ->flatten()
            ->filter(fn ($module) => ! in_array($module, ['analytics', 'sources', 'access', 'settings', 'exports'], true))
            ->values()
            ->all();

        return array_values(array_unique([
            ...self::TYPES,
            ...$templateModules,
        ]));
    }

    public static function availableTypesForWorkspace(?Workspace $workspace): array
    {
        $types = [
            self::TYPE_LEADS,
            self::TYPE_OPPORTUNITIES,
            self::TYPE_CONTACTS,
            self::TYPE_CUSTOMERS,
        ];

        if ($workspace) {
            $types = [
                ...$types,
                ...array_values(array_filter(
                    $workspace->templateModules(),
                    fn ($module) => ! in_array($module, ['leads', 'opportunities', 'contacts', 'customers', 'analytics', 'sources', 'access', 'settings', 'exports'], true),
                )),
            ];
        }

        $types[] = self::TYPE_REPORTS;
        $types[] = self::TYPE_GOOGLE_ADS;

        return collect(array_values(array_unique($types)))
            ->mapWithKeys(fn (string $type) => [$type => self::typeLabel($type)])
            ->all();
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_GOOGLE_ADS => 'Google Ads',
            self::TYPE_CUSTOMERS => 'Customers',
            self::TYPE_CONTACTS => 'Contacts',
            self::TYPE_QUOTES => 'Quotes',
            self::TYPE_SHIPMENTS => 'Shipments',
            self::TYPE_CARRIERS => 'Carriers',
            self::TYPE_BOOKINGS => 'Bookings',
            default => Str::of($type)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function sourceKindLabel(string $kind): string
    {
        return match ($kind) {
            self::SOURCE_KIND_GOOGLE_SHEET_CSV => 'Public Google Sheet / CSV',
            self::SOURCE_KIND_GOOGLE_SHEETS_API => 'Google Sheets API',
            self::SOURCE_KIND_UPLOADED_CSV => 'Uploaded CSV',
            self::SOURCE_KIND_CARGOWISE_API => 'CargoWise API',
            self::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK => 'WordPress Form Webhook',
            default => Str::of($kind)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function wordpressProviders(): array
    {
        return [
            self::WORDPRESS_PROVIDER_FLUENT_FORMS => 'Fluent Forms',
            self::WORDPRESS_PROVIDER_CONTACT_FORM_7 => 'Contact Form 7',
        ];
    }

    public static function cargoWiseAuthModes(): array
    {
        return [
            'basic' => 'Basic Auth',
            'bearer' => 'Bearer Token',
            'none' => 'No Auth',
        ];
    }

    public static function cargoWiseFormats(): array
    {
        return [
            'json' => 'JSON',
            'csv' => 'CSV',
            'xml' => 'XML',
        ];
    }

    public static function supportsSync(string $type, ?string $sourceKind = null): bool
    {
        if ($sourceKind === self::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK) {
            return false;
        }

        return in_array($type, [
            self::TYPE_LEADS,
            self::TYPE_OPPORTUNITIES,
            self::TYPE_CONTACTS,
            self::TYPE_CUSTOMERS,
            self::TYPE_QUOTES,
            self::TYPE_SHIPMENTS,
            self::TYPE_CARRIERS,
            self::TYPE_BOOKINGS,
            self::TYPE_REPORTS,
            self::TYPE_GOOGLE_ADS,
        ], true);
    }
}
