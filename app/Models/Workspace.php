<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Workspace extends Model
{
    public const CRM_VOCABULARY_KEY = 'crm_vocabulary';

    public const TEMPLATE_KEY = 'template_key';

    public const TEMPLATE_MODULES_KEY = 'template_modules';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'is_default',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->using(WorkspaceMembership::class)
            ->withPivot(['job_title', 'is_owner', 'notification_preferences'])
            ->withTimestamps();
    }

    public function sheetSources(): HasMany
    {
        return $this->hasMany(SheetSource::class);
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

    public function jobCostings(): HasMany
    {
        return $this->hasMany(JobCosting::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    public function segmentDefinitions(): HasMany
    {
        return $this->hasMany(CustomerSegmentDefinition::class);
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(AccountMetricSnapshot::class);
    }

    public function segmentAssignments(): HasMany
    {
        return $this->hasMany(AccountSegmentAssignment::class);
    }

    public function collaborationEntries(): HasMany
    {
        return $this->hasMany(CollaborationEntry::class);
    }

    public function workspaceNotifications(): HasMany
    {
        return $this->hasMany(WorkspaceNotification::class);
    }

    public static function workspaceTemplates(): array
    {
        return config('workspace_templates.templates', []);
    }

    public static function defaultTemplateKey(): string
    {
        return (string) config('workspace_templates.default', 'general_maritime');
    }

    public static function templateDefinitionFor(?string $templateKey): array
    {
        $templates = static::workspaceTemplates();
        $resolvedKey = $templateKey && array_key_exists($templateKey, $templates)
            ? $templateKey
            : static::defaultTemplateKey();

        return $templates[$resolvedKey] ?? [];
    }

    public static function defaultLeadStatusLabels(?string $templateKey = null): array
    {
        return data_get(static::templateDefinitionFor($templateKey), 'vocabulary.lead_status_labels', [
            Lead::STATUS_IN_PROGRESS => 'In-progress',
            Lead::STATUS_SALES_QUALIFIED => 'Sales Qualified',
            Lead::STATUS_DISQUALIFIED => 'Disqualified',
        ]);
    }

    public static function defaultOpportunityStageLabels(?string $templateKey = null): array
    {
        return data_get(static::templateDefinitionFor($templateKey), 'vocabulary.opportunity_stage_labels', [
            Opportunity::STAGE_INITIAL_CONTACT => 'Initial Contact',
            Opportunity::STAGE_PROPOSAL_SENT => 'Proposal Sent',
            Opportunity::STAGE_CLOSED_WON => 'Closed Won',
            Opportunity::STAGE_CLOSED_LOST => 'Closed Lost',
            Opportunity::STAGE_NO_RESPONSE => 'No response',
            Opportunity::STAGE_DRAWINGS_SUBMITTED => 'Drawings submitted',
            Opportunity::STAGE_DECISION_MAKING => 'Decision Making',
            Opportunity::STAGE_PROJECT_DELAY => 'Project delay',
        ]);
    }

    public static function defaultLeadSources(?string $templateKey = null): array
    {
        return data_get(static::templateDefinitionFor($templateKey), 'vocabulary.lead_sources', [
            'Email',
            'Google Ads',
            'Website Quote Form',
            'Website Contact Form',
            'Referral',
            'Partner',
        ]);
    }

    public static function defaultLeadServices(?string $templateKey = null): array
    {
        return data_get(static::templateDefinitionFor($templateKey), 'vocabulary.lead_services', [
            'Container Conversion',
            'Freight Services',
            'Air Freight',
            'Ocean Freight',
            'Warehousing',
            'Customs Clearance',
            'Transport',
        ]);
    }

    public static function defaultDisqualificationReasons(?string $templateKey = null): array
    {
        return data_get(static::templateDefinitionFor($templateKey), 'vocabulary.disqualification_reasons', Lead::DISQUALIFICATION_REASONS);
    }

    public static function defaultTemplateModules(?string $templateKey = null): array
    {
        return data_get(static::templateDefinitionFor($templateKey), 'modules', [
            'leads',
            'opportunities',
            'contacts',
            'customers',
            'sources',
            'analytics',
            'access',
            'settings',
            'exports',
        ]);
    }

    public static function applyTemplateSettings(?array $settings, string $templateKey, bool $overrideVocabulary = true): array
    {
        $settings ??= [];

        data_set($settings, static::TEMPLATE_KEY, $templateKey);
        data_set($settings, static::TEMPLATE_MODULES_KEY, static::defaultTemplateModules($templateKey));

        if ($overrideVocabulary) {
            data_set($settings, static::CRM_VOCABULARY_KEY, [
                'lead_status_labels' => static::defaultLeadStatusLabels($templateKey),
                'opportunity_stage_labels' => static::defaultOpportunityStageLabels($templateKey),
                'disqualification_reasons' => static::defaultDisqualificationReasons($templateKey),
                'lead_sources' => static::defaultLeadSources($templateKey),
                'lead_services' => static::defaultLeadServices($templateKey),
            ]);
        }

        return $settings;
    }

    public function templateKey(): string
    {
        $key = data_get($this->settings ?? [], static::TEMPLATE_KEY);

        return array_key_exists($key, static::workspaceTemplates())
            ? $key
            : static::defaultTemplateKey();
    }

    public function templateDefinition(): array
    {
        return static::templateDefinitionFor($this->templateKey());
    }

    public function templateName(): string
    {
        return (string) data_get($this->templateDefinition(), 'name', 'General Maritime');
    }

    public function templateDescription(): string
    {
        return (string) data_get($this->templateDefinition(), 'description', '');
    }

    public function templateModules(): array
    {
        return $this->normalizedList(
            data_get($this->settings ?? [], static::TEMPLATE_MODULES_KEY, static::defaultTemplateModules($this->templateKey())),
            static::defaultTemplateModules($this->templateKey()),
        );
    }

    public function crmVocabulary(): array
    {
        return data_get($this->settings ?? [], self::CRM_VOCABULARY_KEY, []);
    }

    public function leadStatusLabels(): array
    {
        return array_replace(
            self::defaultLeadStatusLabels($this->templateKey()),
            $this->normalizedLabelMap(
                data_get($this->crmVocabulary(), 'lead_status_labels', []),
                array_keys(self::defaultLeadStatusLabels($this->templateKey())),
            ),
        );
    }

    public function opportunityStageLabels(): array
    {
        return array_replace(
            self::defaultOpportunityStageLabels($this->templateKey()),
            $this->normalizedLabelMap(
                data_get($this->crmVocabulary(), 'opportunity_stage_labels', []),
                array_keys(self::defaultOpportunityStageLabels($this->templateKey())),
            ),
        );
    }

    public function disqualificationReasons(): array
    {
        return $this->normalizedList(
            data_get($this->crmVocabulary(), 'disqualification_reasons', self::defaultDisqualificationReasons($this->templateKey())),
            self::defaultDisqualificationReasons($this->templateKey()),
        );
    }

    public function leadSourcesCatalog(): array
    {
        return $this->normalizedList(
            data_get($this->crmVocabulary(), 'lead_sources', self::defaultLeadSources($this->templateKey())),
            self::defaultLeadSources($this->templateKey()),
        );
    }

    public function leadServicesCatalog(): array
    {
        return $this->normalizedList(
            data_get($this->crmVocabulary(), 'lead_services', self::defaultLeadServices($this->templateKey())),
            self::defaultLeadServices($this->templateKey()),
        );
    }

    protected function normalizedLabelMap(array $values, array $allowedKeys): array
    {
        return collect($values)
            ->only($allowedKeys)
            ->map(fn ($label) => is_string($label) ? trim($label) : '')
            ->filter()
            ->all();
    }

    protected function normalizedList(array $values, array $fallback): array
    {
        $normalized = Collection::make($values)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : $fallback;
    }
}
