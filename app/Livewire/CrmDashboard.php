<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatusLog;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\SheetSource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\GoogleSheetsService;
use App\Services\LeadScoringService;
use App\Services\SheetSourceSyncService;
use App\Services\WorkspaceEnrichmentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use jeremykenedy\LaravelRoles\Models\Permission;
use jeremykenedy\LaravelRoles\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CrmDashboard extends Component
{
    use WithPagination;

    protected array $leadScoreCache = [];

    public $workspaceId = null;

    public ?int $editingSourceId = null;

    public ?int $editingWorkspaceUserId = null;

    public ?int $selectedLeadId = null;

    public ?int $selectedContactId = null;

    public ?int $selectedCustomerId = null;

    public ?int $selectedOpportunityId = null;

    public ?int $selectedQuoteId = null;

    public ?int $editingOpportunityId = null;

    public ?int $editingQuoteId = null;

    public ?int $pendingDisqualificationLeadId = null;

    public string $activeTab = 'leads';

    public string $search = '';

    public string $leadStatusFilter = '';

    public string $leadSourceFilter = '';

    public string $opportunityStageFilter = '';

    public string $contactSearch = '';

    public string $customerSearch = '';

    public string $quoteSearch = '';

    public string $leadSort = 'newest';

    public string $opportunitySort = 'newest';

    public string $contactSort = 'newest';

    public string $customerSort = 'newest';

    public string $quoteSort = 'newest';

    public string $quoteStatusFilter = '';

    public string $analyticsRange = 'last_month';

    public string $analyticsBreakdown = 'source';

    public string $analyticsMonth = '';

    public int $leadPerPage = 15;

    public int $opportunityPerPage = 15;

    public int $contactPerPage = 12;

    public int $customerPerPage = 12;

    public int $quotePerPage = 15;

    public array $companyForm = [];

    public array $workspaceForm = [];

    public array $workspaceSettingsForm = [];

    public array $sourceForm = [];

    public array $editingSourceForm = [];

    public array $userForm = [];

    public array $roleForm = [];

    public array $permissionForm = [];

    public array $editingWorkspaceUserForm = [];

    public array $manualLeadForm = [];

    public array $manualOpportunityForm = [];

    public array $manualQuoteForm = [];

    public array $opportunityEditForm = [];

    public array $quoteEditForm = [];

    public function mount(): void
    {
        $this->resetForms();

        $workspace = $this->resolveCurrentWorkspace($this->accessibleWorkspaces());

        $this->workspaceId = $workspace?->id;
        $requestedTab = request()->query('tab');

        if (is_string($requestedTab) && $requestedTab !== '') {
            $this->activeTab = $requestedTab;
        }

        $this->primeForms($workspace);
    }

    public function updatedWorkspaceId(): void
    {
        $this->primeForms($this->currentWorkspace());
        $this->resetPage('leadsPage');
        $this->resetPage('opportunitiesPage');
        $this->resetPage('contactsPage');
        $this->resetPage('customersPage');
        $this->resetPage('quotesPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
        $this->selectedContactId = null;
        $this->selectedCustomerId = null;
        $this->selectedOpportunityId = null;
        $this->selectedQuoteId = null;
        $this->editingWorkspaceUserId = null;
        $this->editingOpportunityId = null;
        $this->editingQuoteId = null;
        $this->resetManualOpportunityForm();
        $this->resetManualQuoteForm();
        $this->opportunityEditForm = [];
        $this->quoteEditForm = [];
        $this->editingWorkspaceUserForm = [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage('leadsPage');
        $this->resetPage('opportunitiesPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
        $this->selectedOpportunityId = null;
    }

    public function updatedContactSearch(): void
    {
        $this->resetPage('contactsPage');
        $this->selectedContactId = null;
    }

    public function updatedCustomerSearch(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedQuoteSearch(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedLeadStatusFilter(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedLeadSourceFilter(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedOpportunityStageFilter(): void
    {
        $this->resetPage('opportunitiesPage');
        $this->selectedOpportunityId = null;
    }

    public function updatedQuoteStatusFilter(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedLeadSort(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedOpportunitySort(): void
    {
        $this->resetPage('opportunitiesPage');
        $this->selectedOpportunityId = null;
    }

    public function updatedQuoteSort(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedLeadPerPage(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedActiveTab(string $value): void
    {
        if ($value !== 'leads') {
            $this->selectedLeadId = null;
        }

        if ($value !== 'contacts') {
            $this->selectedContactId = null;
        }

        if ($value !== 'customers') {
            $this->selectedCustomerId = null;
        }

        if ($value !== 'opportunities') {
            $this->selectedOpportunityId = null;
            $this->opportunityEditForm = [];
        }

        if (! in_array($value, ['quotes', 'manual-quote'], true)) {
            $this->selectedQuoteId = null;
            $this->quoteEditForm = [];
        }

        if ($value !== 'access') {
            $this->editingWorkspaceUserId = null;
            $this->editingWorkspaceUserForm = [];
        }

        if ($value !== 'leads') {
            $this->pendingDisqualificationLeadId = null;
        }
    }

    public function openTemplateModule(string $module): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace) {
            return;
        }

        $allowedTabs = array_keys($this->availableTabsForWorkspace(
            $workspace,
            $this->canManageWorkspaceAccess($workspace),
            $this->canManageWorkspaceAccess($workspace) || auth()->user()->hasRole(['admin', 'manager']),
        ));

        if (! in_array($module, $allowedTabs, true)) {
            return;
        }

        $this->activeTab = $module;
    }

    public function updatedOpportunityPerPage(): void
    {
        $this->resetPage('opportunitiesPage');
        $this->selectedOpportunityId = null;
    }

    public function updatedQuotePerPage(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedContactSort(): void
    {
        $this->resetPage('contactsPage');
        $this->selectedContactId = null;
    }

    public function updatedCustomerSort(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedContactPerPage(): void
    {
        $this->resetPage('contactsPage');
        $this->selectedContactId = null;
    }

    public function updatedCustomerPerPage(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedAnalyticsRange(): void
    {
        if ($this->analyticsRange === 'month' && $this->analyticsMonth === '') {
            $this->analyticsMonth = $this->defaultAnalyticsMonth();
        }
    }

    public function updatedAnalyticsBreakdown(): void
    {
        // Analytics uses in-memory cards and tables, so no paginator reset is needed.
    }

    public function updatedWorkspaceSettingsFormTemplateKey(string $value): void
    {
        if (! array_key_exists($value, Workspace::workspaceTemplates())) {
            return;
        }

        $this->workspaceSettingsForm = [
            ...$this->workspaceSettingsForm,
            'template_key' => $value,
            'lead_status_labels' => Workspace::defaultLeadStatusLabels($value),
            'opportunity_stage_labels' => Workspace::defaultOpportunityStageLabels($value),
            'disqualification_reasons' => Workspace::defaultDisqualificationReasons($value),
            'lead_sources' => Workspace::defaultLeadSources($value),
            'lead_services' => Workspace::defaultLeadServices($value),
        ];
    }

    public function saveCompany(): void
    {
        abort_unless(auth()->user()->isAdmin() || $this->canBootstrapWorkspace(), 403);

        $validated = validator($this->companyForm, [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:100'],
        ])->validate();

        $company = Company::create([
            ...$validated,
            'slug' => $this->uniqueSlug(Company::class, $validated['name']),
            'is_active' => true,
        ]);

        $this->companyForm = [
            ...$this->companyForm,
            'name' => '',
            'contact_email' => '',
            'contact_phone' => '',
        ];

        $this->workspaceForm['company_id'] = $company->id;
        $this->flash("Company {$company->name} created.");
    }

    public function saveWorkspace(): void
    {
        abort_unless(auth()->user()->isAdmin() || $this->canBootstrapWorkspace(), 403);

        $validated = validator($this->workspaceForm, [
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'template_key' => ['required', Rule::in(array_keys(Workspace::workspaceTemplates()))],
        ])->validate();

        $workspace = Workspace::create([
            ...collect($validated)->except('template_key')->all(),
            'slug' => $this->uniqueSlug(
                Workspace::class,
                $validated['name'],
                ['company_id' => $validated['company_id']],
            ),
            'is_default' => Workspace::where('company_id', $validated['company_id'])->doesntExist(),
            'settings' => Workspace::applyTemplateSettings(null, $validated['template_key']),
        ]);

        if ($this->canBootstrapWorkspace()) {
            $this->bootstrapWorkspaceOwner($workspace);
        }

        $this->workspaceId = $workspace->id;
        $this->workspaceForm['name'] = '';
        $this->workspaceForm['description'] = '';
        $this->workspaceForm['template_key'] = Workspace::defaultTemplateKey();
        $this->primeForms($workspace);
        $this->flash("Workspace {$workspace->name} created.");
    }

    public function startWorkspace(): void
    {
        abort_unless($this->canBootstrapWorkspace(), 403);

        $validated = validator([
            'company' => $this->companyForm,
            'workspace' => $this->workspaceForm,
        ], [
            'company.name' => ['required', 'string', 'max:255'],
            'company.industry' => ['required', 'string', 'max:255'],
            'company.contact_email' => ['nullable', 'email'],
            'company.contact_phone' => ['nullable', 'string', 'max:255'],
            'company.timezone' => ['required', 'string', 'max:100'],
            'workspace.name' => ['required', 'string', 'max:255'],
            'workspace.description' => ['nullable', 'string', 'max:255'],
            'workspace.template_key' => ['required', Rule::in(array_keys(Workspace::workspaceTemplates()))],
        ])->validate();

        $company = Company::create([
            'name' => $validated['company']['name'],
            'slug' => $this->uniqueSlug(Company::class, $validated['company']['name']),
            'industry' => $validated['company']['industry'],
            'contact_email' => $validated['company']['contact_email'] ?: null,
            'contact_phone' => $validated['company']['contact_phone'] ?: null,
            'timezone' => $validated['company']['timezone'],
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => $validated['workspace']['name'],
            'slug' => $this->uniqueSlug(
                Workspace::class,
                $validated['workspace']['name'],
                ['company_id' => $company->id],
            ),
            'description' => $validated['workspace']['description'] ?: null,
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, $validated['workspace']['template_key']),
        ]);

        $this->bootstrapWorkspaceOwner($workspace);

        $this->workspaceId = $workspace->id;
        $this->resetForms();
        $this->primeForms($workspace);
        $this->flash("Workspace {$workspace->name} is ready. Add your first source next.");
    }

    public function saveSheetSource(): void
    {
        $this->ensureWorkspaceManager();

        $validated = validator($this->sourceForm, [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'type' => ['required', Rule::in(SheetSource::TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'description' => ['nullable', 'string', 'max:255'],
            'source_kind' => ['required', Rule::in(SheetSource::SOURCE_KINDS)],
            'is_active' => ['boolean'],
        ])->validate();

        $workspace = Workspace::query()->with('company')->findOrFail($validated['workspace_id']);

        $this->ensureWorkspaceVisible($workspace->id);

        $sourceKind = SheetSource::normalizeSourceKind(
            $validated['source_kind'],
            $validated['url'],
        );

        SheetSource::create([
            ...$validated,
            'company_id' => $workspace->company_id,
            'source_kind' => $sourceKind,
            'sync_status' => 'idle',
        ]);

        $this->sourceForm['name'] = '';
        $this->sourceForm['url'] = '';
        $this->sourceForm['description'] = '';
        $this->flash('Sheet source added.');
    }

    public function createRole(): void
    {
        $this->ensureWorkspaceOwner();

        $validated = validator($this->roleForm, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:99'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ])->validate();

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'level' => $validated['level'],
        ]);

        $role->syncPermissions(
            collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $this->roleForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'level' => 3,
            'permission_ids' => [],
        ];

        $this->flash('Role created.');
    }

    public function createUser(): void
    {
        $this->ensureWorkspaceOwner();
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->userForm, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('slug')->all())],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ])->validate();

        $user = User::create([
            'company_id' => $workspace->company_id,
            'default_workspace_id' => $workspace->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'job_title' => $validated['job_title'],
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->workspaces()->sync([
            $workspace->id => [
                'job_title' => $validated['job_title'],
                'is_owner' => false,
            ],
        ]);

        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->syncRoles([$role]);
        $user->syncPermissions(
            collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'permission_ids' => [],
        ];

        $this->flash("User {$user->email} created.");
    }

    public function createPermission(): void
    {
        $this->ensureWorkspaceOwner();

        $validated = validator($this->permissionForm, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', Rule::unique('permissions', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
        ])->validate();

        Permission::create($validated);

        $this->permissionForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'model' => 'User',
        ];

        $this->flash('Permission created.');
    }

    public function saveWorkspaceSettings(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless($this->canManageWorkspaceAccess($workspace), 403);

        $validated = validator($this->workspaceSettingsForm, [
            'template_key' => ['required', Rule::in(array_keys(Workspace::workspaceTemplates()))],
            'lead_status_labels' => ['required', 'array'],
            'lead_status_labels.*' => ['required', 'string', 'max:255'],
            'opportunity_stage_labels' => ['required', 'array'],
            'opportunity_stage_labels.*' => ['required', 'string', 'max:255'],
            'disqualification_reasons' => ['required', 'array', 'min:1'],
            'disqualification_reasons.*' => ['required', 'string', 'max:255'],
            'lead_sources' => ['required', 'array', 'min:1'],
            'lead_sources.*' => ['required', 'string', 'max:255'],
            'lead_services' => ['required', 'array', 'min:1'],
            'lead_services.*' => ['required', 'string', 'max:255'],
        ])->validate();

        $currentTemplateKey = $workspace->templateKey();
        $templateChanged = $validated['template_key'] !== $currentTemplateKey;

        $settings = Workspace::applyTemplateSettings(
            $workspace->settings ?? [],
            $validated['template_key'],
            $templateChanged,
        );

        if (! $templateChanged) {
            data_set($settings, Workspace::CRM_VOCABULARY_KEY, [
                'lead_status_labels' => $this->sanitizeLabelMap(
                    $validated['lead_status_labels'],
                    array_keys(Workspace::defaultLeadStatusLabels($validated['template_key'])),
                ),
                'opportunity_stage_labels' => $this->sanitizeLabelMap(
                    $validated['opportunity_stage_labels'],
                    array_keys(Workspace::defaultOpportunityStageLabels($validated['template_key'])),
                ),
                'disqualification_reasons' => $this->sanitizeList($validated['disqualification_reasons']),
                'lead_sources' => $this->sanitizeList($validated['lead_sources']),
                'lead_services' => $this->sanitizeList($validated['lead_services']),
            ]);
        }

        $workspace->forceFill(['settings' => $settings])->save();

        $this->primeForms($workspace->fresh('company'));
        $this->flash("Workspace settings updated for {$workspace->name}.");
    }

    public function exportLeadsCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyLeadSorting($this->buildLeadQuery($workspace))
            ->get()
            ->map(fn (Lead $lead) => [
                $lead->lead_id ?: $lead->external_key,
                $lead->company_name ?: '',
                $lead->contact_name ?: '',
                $lead->email ?: '',
                $lead->phone ?: '',
                $lead->lead_source ?: '',
                $lead->service ?: '',
                $this->leadStatusLabel($lead->status, $workspace),
                $lead->disqualification_reason ?: '',
                $lead->lead_value !== null ? (string) $lead->lead_value : '',
                optional($lead->submission_date)->format('Y-m-d H:i:s') ?: '',
                $lead->assignedUser?->name ?: '',
                (string) $lead->opportunities_count,
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'leads'),
            ['Lead ID', 'Company', 'Contact', 'Email', 'Phone', 'Source', 'Service', 'Status', 'Disqualification Reason', 'Lead Value', 'Submission Date', 'Owner', 'Linked Opportunities'],
            $rows,
        );
    }

    public function exportOpportunitiesCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyOpportunitySorting($this->buildOpportunityQuery($workspace))
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                $opportunity->external_key ?: '',
                $opportunity->company_name ?: '',
                $opportunity->contact_email ?: '',
                $opportunity->lead_source ?: '',
                $opportunity->required_service ?: '',
                $opportunity->revenue_potential !== null ? (string) $opportunity->revenue_potential : '',
                $opportunity->project_timeline_days !== null ? (string) $opportunity->project_timeline_days : '',
                $this->opportunityStageLabel($opportunity->sales_stage, $workspace),
                optional($opportunity->submission_date)->format('Y-m-d H:i:s') ?: '',
                $opportunity->assignedUser?->name ?: '',
                $opportunity->notes ?: '',
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'opportunities'),
            ['Opportunity Key', 'Company', 'Contact Email', 'Source', 'Required Service', 'Revenue Potential', 'Timeline Days', 'Stage', 'Submission Date', 'Owner', 'Notes'],
            $rows,
        );
    }

    public function exportContactsCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyContactSorting($this->buildContactsQuery($workspace))
            ->get()
            ->map(fn (Lead $contact) => [
                $contact->lead_id ?: $contact->external_key,
                $contact->contact_name ?: '',
                $contact->company_name ?: '',
                $contact->email ?: '',
                $contact->phone ?: '',
                $this->leadStatusLabel($contact->status, $workspace),
                $contact->lead_source ?: '',
                $contact->service ?: '',
                optional($contact->submission_date)->format('Y-m-d H:i:s') ?: '',
                $contact->assignedUser?->name ?: '',
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'contacts'),
            ['Lead ID', 'Contact', 'Company', 'Email', 'Phone', 'Status', 'Source', 'Service', 'Submission Date', 'Owner'],
            $rows,
        );
    }

    public function exportCustomersCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyCustomerSorting($this->buildCustomersQuery($workspace))
            ->get()
            ->map(fn (Opportunity $customer) => [
                $customer->external_key ?: '',
                $customer->company_name ?: '',
                $customer->contact_email ?: '',
                $customer->lead_source ?: '',
                $customer->required_service ?: '',
                $customer->revenue_potential !== null ? (string) $customer->revenue_potential : '',
                $customer->project_timeline_days !== null ? (string) $customer->project_timeline_days : '',
                $this->opportunityStageLabel($customer->sales_stage, $workspace),
                optional($customer->submission_date)->format('Y-m-d H:i:s') ?: '',
                $customer->assignedUser?->name ?: '',
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'customers'),
            ['Opportunity Key', 'Customer', 'Contact Email', 'Source', 'Service', 'Revenue Potential', 'Timeline Days', 'Stage', 'Submission Date', 'Owner'],
            $rows,
        );
    }

    public function addWorkspaceSettingItem(string $field): void
    {
        $this->ensureWorkspaceOwner();

        if (! in_array($field, ['disqualification_reasons', 'lead_sources', 'lead_services'], true)) {
            abort(404);
        }

        $this->workspaceSettingsForm[$field][] = '';
    }

    public function removeWorkspaceSettingItem(string $field, int $index): void
    {
        $this->ensureWorkspaceOwner();

        if (! in_array($field, ['disqualification_reasons', 'lead_sources', 'lead_services'], true)) {
            abort(404);
        }

        $items = $this->workspaceSettingsForm[$field] ?? [];
        unset($items[$index]);
        $items = array_values($items);

        if ($items === []) {
            $items = match ($field) {
                'disqualification_reasons' => Lead::DISQUALIFICATION_REASONS,
                'lead_sources' => Workspace::defaultLeadSources(),
                'lead_services' => Workspace::defaultLeadServices(),
                default => [''],
            };
        }

        $this->workspaceSettingsForm[$field] = $items;
    }

    public function startEditingWorkspaceUser(int $userId): void
    {
        $this->ensureWorkspaceOwner();

        $workspace = $this->currentWorkspaceOrFail();

        $user = $workspace->users()
            ->with(['roles', 'userPermissions'])
            ->where('users.id', $userId)
            ->firstOrFail();

        $this->editingWorkspaceUserId = $user->id;
        $this->editingWorkspaceUserForm = [
            'job_title' => $user->pivot->job_title ?? '',
            'role' => $user->roles->pluck('slug')->first() ?: 'sales',
            'permission_ids' => $user->userPermissions->pluck('id')->map(fn ($id) => (string) $id)->all(),
        ];
    }

    public function cancelEditingWorkspaceUser(): void
    {
        $this->editingWorkspaceUserId = null;
        $this->editingWorkspaceUserForm = [];
    }

    public function updateWorkspaceUserAccess(): void
    {
        $this->ensureWorkspaceOwner();

        abort_if(! $this->editingWorkspaceUserId, 404);

        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->editingWorkspaceUserForm, [
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('slug')->all())],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ])->validate();

        $user = $workspace->users()
            ->where('users.id', $this->editingWorkspaceUserId)
            ->firstOrFail();

        $workspace->users()->updateExistingPivot($user->id, [
            'job_title' => $validated['job_title'],
        ]);

        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->syncRoles([$role]);
        $user->syncPermissions(
            collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $this->cancelEditingWorkspaceUser();
        $this->flash("Workspace access updated for {$user->email}.");
    }

    public function addManualLead(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualLeadForm, [
            'contact_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'service' => ['required', 'string', 'max:255'],
            'lead_source' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys($this->leadStatusOptions($workspace)))],
            'lead_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        Lead::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => auth()->id(),
            'external_key' => 'manual-'.Str::ulid(),
            'lead_id' => 'MANUAL-'.Str::upper(Str::random(6)),
            'submission_date' => now(),
            'manual_entry' => true,
            ...$validated,
        ]);

        $this->manualLeadForm = [
            ...$this->manualLeadForm,
            'contact_name' => '',
            'company_name' => '',
            'email' => '',
            'phone' => '',
            'notes' => '',
            'lead_value' => '',
        ];

        $this->flash('Manual lead added.');
    }

    public function addManualOpportunity(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualOpportunityForm, [
            'lead_id' => ['nullable', 'exists:leads,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'lead_source' => ['required', 'string', 'max:255'],
            'required_service' => ['required', 'string', 'max:255'],
            'revenue_potential' => ['nullable', 'numeric', 'min:0'],
            'project_timeline_days' => ['nullable', 'integer', 'min:0'],
            'sales_stage' => ['required', Rule::in(array_keys($this->opportunityStageOptions($workspace)))],
            'notes' => ['nullable', 'string'],
        ])->validate();

        if ($this->editingOpportunityId) {
            $opportunity = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingOpportunityId);

            $opportunity->update([
                ...$validated,
                'assigned_user_id' => $opportunity->assigned_user_id ?: auth()->id(),
                'manual_entry' => true,
                'year_month' => $opportunity->year_month ?: now()->format('M-y'),
            ]);

            $message = 'Opportunity updated.';
        } else {
            Opportunity::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'assigned_user_id' => auth()->id(),
                'external_key' => 'manual-'.Str::ulid(),
                'submission_date' => now(),
                'year_month' => now()->format('M-y'),
                'manual_entry' => true,
                ...$validated,
            ]);

            $message = 'Manual opportunity added.';
        }

        $this->editingOpportunityId = null;
        $this->resetManualOpportunityForm();
        $this->activeTab = 'opportunities';

        $this->flash($message);
    }

    public function addManualQuote(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualQuoteForm, [
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(Quote::STATUSES)],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            ...$validated,
            'opportunity_id' => $validated['opportunity_id'] ?: null,
            'lead_id' => $validated['lead_id'] ?: null,
            'assigned_user_id' => auth()->id(),
            'quoted_at' => now(),
            'margin_amount' => $this->quoteMarginFromPayload($validated),
        ];

        if ($this->editingQuoteId) {
            $quote = Quote::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingQuoteId);

            $quote->update($payload);
            $message = 'Quote updated.';
        } else {
            Quote::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'quote_number' => $this->nextQuoteNumber($workspace),
                ...$payload,
            ]);

            $message = 'Quote added.';
        }

        $this->editingQuoteId = null;
        $this->resetManualQuoteForm();
        $this->activeTab = 'quotes';

        $this->flash($message);
    }

    public function selectLead(int $leadId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $lead = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($leadId);

        $this->selectedLeadId = $lead->id;
        $this->activeTab = 'leads';
    }

    public function closeLeadDetails(): void
    {
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function selectContact(int $contactId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $contact = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($contactId);

        $this->selectedContactId = $contact->id;
        $this->activeTab = 'contacts';
    }

    public function closeContactDetails(): void
    {
        $this->selectedContactId = null;
    }

    public function selectCustomer(int $customerId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $customer = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($customerId);

        $this->selectedCustomerId = $customer->id;
        $this->activeTab = 'customers';
    }

    public function closeCustomerDetails(): void
    {
        $this->selectedCustomerId = null;
    }

    public function selectOpportunity(int $opportunityId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($opportunityId);

        $this->selectedOpportunityId = $opportunity->id;
        $this->fillOpportunityEditForm($opportunity);
        $this->activeTab = 'opportunities';
    }

    public function selectQuote(int $quoteId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $quote = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($quoteId);

        $this->selectedQuoteId = $quote->id;
        $this->fillQuoteEditForm($quote);
        $this->activeTab = 'quotes';
    }

    public function closeOpportunityDetails(): void
    {
        $this->selectedOpportunityId = null;
        $this->opportunityEditForm = [];
    }

    public function closeQuoteDetails(): void
    {
        $this->selectedQuoteId = null;
        $this->quoteEditForm = [];
    }

    public function saveOpportunityDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedOpportunityId, 404);

        $validated = validator($this->opportunityEditForm, [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'lead_source' => ['required', 'string', 'max:255'],
            'required_service' => ['required', 'string', 'max:255'],
            'revenue_potential' => ['nullable', 'numeric', 'min:0'],
            'project_timeline_days' => ['nullable', 'integer', 'min:0'],
            'sales_stage' => ['required', Rule::in(array_keys($this->opportunityStageOptions($workspace)))],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedOpportunityId);

        $opportunity->update($validated);

        $this->fillOpportunityEditForm($opportunity->fresh(['lead', 'assignedUser']));

        $this->flash('Opportunity updated.');
    }

    public function saveQuoteDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedQuoteId, 404);

        $validated = validator($this->quoteEditForm, [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(Quote::STATUSES)],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $quote = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedQuoteId);

        $quote->update([
            ...$validated,
            'margin_amount' => $this->quoteMarginFromPayload($validated),
        ]);

        $this->fillQuoteEditForm($quote->fresh(['lead', 'opportunity', 'assignedUser']));

        $this->flash("Quote {$quote->quote_number} updated.");
    }

    public function updateLeadStatus(int $leadId, string $status): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(in_array($status, Lead::STATUSES, true), 422);

        $lead = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($leadId);

        if ($lead->status === $status) {
            if ($status === Lead::STATUS_DISQUALIFIED && blank($lead->disqualification_reason)) {
                $this->pendingDisqualificationLeadId = $lead->id;
            }

            return;
        }

        if ($status === Lead::STATUS_DISQUALIFIED) {
            $this->pendingDisqualificationLeadId = $lead->id;

            return;
        }

        if ($this->pendingDisqualificationLeadId === $lead->id) {
            $this->pendingDisqualificationLeadId = null;
        }

        $this->persistLeadStatus($lead, $status);
    }

    public function saveDisqualificationReason(int $leadId, string $reason): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator([
            'lead_id' => $leadId,
            'reason' => $reason,
        ], [
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'reason' => ['required', Rule::in($this->disqualificationReasonOptions($workspace))],
        ])->validate();

        $lead = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($validated['lead_id']);

        if ($lead->status === Lead::STATUS_DISQUALIFIED) {
            $lead->update([
                'disqualification_reason' => $validated['reason'],
            ]);

            $this->pendingDisqualificationLeadId = null;
            $this->flash('Disqualification reason updated.');

            return;
        }

        $this->persistLeadStatus($lead, Lead::STATUS_DISQUALIFIED, $validated['reason']);
        $this->pendingDisqualificationLeadId = null;
    }

    protected function persistLeadStatus(Lead $lead, string $status, ?string $disqualificationReason = null): void
    {
        $fromStatus = $lead->status;

        $lead->update([
            'status' => $status,
            'disqualification_reason' => $status === Lead::STATUS_DISQUALIFIED
                ? $disqualificationReason
                : null,
        ]);
        $lead->loadMissing('sheetSource');

        LeadStatusLog::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'from_status' => $fromStatus,
            'to_status' => $status,
            'change_context' => 'dashboard',
            'note' => $status === Lead::STATUS_DISQUALIFIED ? $disqualificationReason : null,
        ]);

        $leadLabel = $lead->lead_id ?: $lead->external_key;

        $message = "Lead {$leadLabel} updated to {$status}.";

        if ($status === Lead::STATUS_SALES_QUALIFIED) {
            $opportunity = $this->draftOpportunityFromQualifiedLead($lead);

            $this->editingOpportunityId = $opportunity->id;
            $this->fillManualOpportunityFormFromOpportunity($opportunity);
            $this->activeTab = 'manual-opportunity';

            $message .= ' Opportunity draft is ready to complete.';
        }

        try {
            if (app(GoogleSheetsService::class)->writeLeadStatus($lead, $status)) {
                $message .= ' Synced to Google Sheets.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $message .= ' Google Sheets write-back failed.';
        }

        $this->flash($message);
    }

    public function updateOpportunityStage(int $opportunityId, string $stage): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(in_array($stage, Opportunity::STAGES, true), 422);

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($opportunityId);

        $opportunity->update(['sales_stage' => $stage]);
        $opportunity->loadMissing('sheetSource');

        $message = "Opportunity {$opportunity->external_key} moved to {$stage}.";

        try {
            if (app(GoogleSheetsService::class)->writeOpportunityStage($opportunity, $stage)) {
                $message .= ' Synced to Google Sheets.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $message .= ' Google Sheets write-back failed.';
        }

        $this->flash($message);
    }

    public function syncSource(int $sourceId): void
    {
        $this->ensureWorkspaceManager();

        $workspaceIds = $this->accessibleWorkspaces()->pluck('id')->all();

        $source = SheetSource::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->findOrFail($sourceId);

        try {
            $rows = app(SheetSourceSyncService::class)->sync($source);

            $this->flash("Source {$source->name} synced with {$rows} imported rows.");
        } catch (Throwable $exception) {
            $this->flash($this->friendlySyncError($exception));
        }
    }

    public function startEditingSource(int $sourceId): void
    {
        $this->ensureWorkspaceManager();

        $workspaceIds = $this->accessibleWorkspaces()->pluck('id')->all();

        $source = SheetSource::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->findOrFail($sourceId);

        $this->editingSourceId = $source->id;
        $this->editingSourceForm = [
            'type' => $source->type,
            'name' => $source->name,
            'url' => $source->url,
            'source_kind' => $source->source_kind,
            'description' => $source->description ?? '',
            'is_active' => $source->is_active,
        ];
        $this->activeTab = 'sources';
    }

    public function cancelEditingSource(): void
    {
        $this->editingSourceId = null;
        $this->editingSourceForm = [];
    }

    public function updateSheetSource(): void
    {
        $this->ensureWorkspaceManager();

        abort_if(! $this->editingSourceId, 404);

        $workspaceIds = $this->accessibleWorkspaces()->pluck('id')->all();

        $source = SheetSource::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->findOrFail($this->editingSourceId);

        $validated = validator($this->editingSourceForm, [
            'type' => ['required', Rule::in(SheetSource::TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string'],
            'source_kind' => ['required', Rule::in(SheetSource::SOURCE_KINDS)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ])->validate();

        $sourceKind = SheetSource::normalizeSourceKind(
            $validated['source_kind'],
            $validated['url'],
        );

        $source->fill([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'url' => $validated['url'],
            'source_kind' => $sourceKind,
            'description' => $validated['description'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        $this->cancelEditingSource();

        $this->flash("Source {$source->name} updated.");
    }

    public function syncWorkspaceSources(): void
    {
        $this->ensureWorkspaceManager();

        $workspace = $this->currentWorkspaceOrFail();

        $totalRows = 0;

        try {
            foreach ($workspace->sheetSources()->where('is_active', true)->get() as $source) {
                $totalRows += app(SheetSourceSyncService::class)->sync($source);
            }

            $this->flash("Workspace synced. Imported {$totalRows} rows.");
        } catch (Throwable $exception) {
            $this->flash($this->friendlySyncError($exception));
        }
    }

    protected function draftOpportunityFromQualifiedLead(Lead $lead): Opportunity
    {
        $opportunity = Opportunity::query()
            ->where('workspace_id', $lead->workspace_id)
            ->where('lead_id', $lead->id)
            ->first();

        if (! $opportunity) {
            $opportunity = Opportunity::create([
                'company_id' => $lead->company_id,
                'workspace_id' => $lead->workspace_id,
                'lead_id' => $lead->id,
                'assigned_user_id' => $lead->assigned_user_id ?: auth()->id(),
                'external_key' => 'qualified-'.Str::ulid(),
                'company_name' => $lead->company_name,
                'contact_email' => $lead->email,
                'lead_source' => $lead->lead_source ?: 'Qualified lead',
                'required_service' => $lead->service ?: 'Container Conversion',
                'revenue_potential' => $lead->lead_value,
                'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
                'notes' => $lead->notes,
                'submission_date' => now(),
                'year_month' => now()->format('M-y'),
                'manual_entry' => true,
            ]);
        } else {
            $opportunity->fill([
                'company_name' => $opportunity->company_name ?: $lead->company_name,
                'contact_email' => $opportunity->contact_email ?: $lead->email,
                'lead_source' => $opportunity->lead_source ?: ($lead->lead_source ?: 'Qualified lead'),
                'required_service' => $opportunity->required_service ?: ($lead->service ?: 'Container Conversion'),
                'revenue_potential' => $opportunity->revenue_potential ?: $lead->lead_value,
                'notes' => $opportunity->notes ?: $lead->notes,
                'assigned_user_id' => $opportunity->assigned_user_id ?: ($lead->assigned_user_id ?: auth()->id()),
            ])->save();
        }

        return $opportunity;
    }

    protected function fillManualOpportunityFormFromOpportunity(Opportunity $opportunity): void
    {
        $this->manualOpportunityForm = [
            'lead_id' => $opportunity->lead_id ?: '',
            'company_name' => $opportunity->company_name ?: '',
            'contact_email' => $opportunity->contact_email ?: '',
            'lead_source' => $opportunity->lead_source ?: 'Email',
            'required_service' => $opportunity->required_service ?: 'Container Conversion',
            'revenue_potential' => $opportunity->revenue_potential ? (string) $opportunity->revenue_potential : '',
            'project_timeline_days' => $opportunity->project_timeline_days ? (string) $opportunity->project_timeline_days : '',
            'sales_stage' => $opportunity->sales_stage ?: Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => $opportunity->notes ?: '',
        ];
    }

    protected function fillOpportunityEditForm(Opportunity $opportunity): void
    {
        $this->opportunityEditForm = [
            'company_name' => $opportunity->company_name ?: '',
            'contact_email' => $opportunity->contact_email ?: '',
            'lead_source' => $opportunity->lead_source ?: 'Email',
            'required_service' => $opportunity->required_service ?: 'Container Conversion',
            'revenue_potential' => $opportunity->revenue_potential ? (string) $opportunity->revenue_potential : '',
            'project_timeline_days' => $opportunity->project_timeline_days ? (string) $opportunity->project_timeline_days : '',
            'sales_stage' => $opportunity->sales_stage ?: Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => $opportunity->notes ?: '',
        ];
    }

    protected function fillQuoteEditForm(Quote $quote): void
    {
        $this->quoteEditForm = [
            'company_name' => $quote->company_name ?: '',
            'contact_name' => $quote->contact_name ?: '',
            'contact_email' => $quote->contact_email ?: '',
            'service_mode' => $quote->service_mode ?: 'Ocean Freight',
            'origin' => $quote->origin ?: '',
            'destination' => $quote->destination ?: '',
            'incoterm' => $quote->incoterm ?: '',
            'commodity' => $quote->commodity ?: '',
            'equipment_type' => $quote->equipment_type ?: '',
            'weight_kg' => $quote->weight_kg !== null ? (string) $quote->weight_kg : '',
            'volume_cbm' => $quote->volume_cbm !== null ? (string) $quote->volume_cbm : '',
            'buy_amount' => $quote->buy_amount !== null ? (string) $quote->buy_amount : '',
            'sell_amount' => $quote->sell_amount !== null ? (string) $quote->sell_amount : '',
            'currency' => $quote->currency ?: 'AED',
            'status' => $quote->status ?: Quote::STATUS_DRAFT,
            'valid_until' => $quote->valid_until?->format('Y-m-d') ?: '',
            'notes' => $quote->notes ?: '',
        ];
    }

    protected function resetManualOpportunityForm(): void
    {
        $this->editingOpportunityId = null;
        $this->manualOpportunityForm = [
            'lead_id' => '',
            'company_name' => '',
            'contact_email' => '',
            'lead_source' => 'Email',
            'required_service' => 'Container Conversion',
            'revenue_potential' => '',
            'project_timeline_days' => '',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => '',
        ];

    }

    protected function resetManualQuoteForm(): void
    {
        $this->editingQuoteId = null;
        $this->manualQuoteForm = [
            'opportunity_id' => '',
            'lead_id' => '',
            'company_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'status' => Quote::STATUS_DRAFT,
            'valid_until' => '',
            'notes' => '',
        ];
    }

    public function render()
    {
        $workspaces = $this->accessibleWorkspaces();
        $workspace = $this->resolveCurrentWorkspace($workspaces);
        $this->workspaceId = $workspace?->id;

        $companies = $this->visibleCompanies($workspaces);
        $roles = Role::query()->orderByDesc('level')->get();
        $permissions = Permission::query()->orderBy('name')->get();

        $sheetSources = collect();
        $workspaceUsers = collect();
        $canManageAccess = false;
        $canViewWorkspaceTools = false;
        $leads = Lead::query()->whereRaw('1 = 0')->paginate(
            $this->leadPerPage,
            ['*'],
            'leadsPage',
        );
        $opportunities = Opportunity::query()->whereRaw('1 = 0')->paginate(
            $this->opportunityPerPage,
            ['*'],
            'opportunitiesPage',
        );
        $contacts = Lead::query()->whereRaw('1 = 0')->paginate(
            $this->contactPerPage,
            ['*'],
            'contactsPage',
        );
        $customers = Opportunity::query()->whereRaw('1 = 0')->paginate(
            $this->customerPerPage,
            ['*'],
            'customersPage',
        );
        $quotes = Quote::query()->whereRaw('1 = 0')->paginate(
            $this->quotePerPage,
            ['*'],
            'quotesPage',
        );
        $sourceBreakdown = collect();
        $selectedLead = null;
        $selectedOpportunity = null;
        $selectedContact = null;
        $selectedCustomer = null;
        $selectedQuote = null;
        $leadInsights = [];
        $opportunityInsights = [];
        $contactInsights = [];
        $customerInsights = [];
        $latestReport = null;
        $monthlyReports = collect();
        $kpis = [];
        $leadOptions = collect();
        $analyticsKpis = [];
        $analyticsBreakdownRows = collect();
        $analyticsMonthlyRows = collect();
        $analyticsSnapshot = [];
        $analyticsSqlChartRows = collect();
        $analyticsAdsChartRows = collect();
        $analyticsWonCustomers = collect();
        $analyticsDealSummary = [];
        $analyticsEfficiency = [];
        $analyticsAvailableMonths = collect();

        if ($workspace) {
            $canManageAccess = $this->canManageWorkspaceAccess($workspace);
            $canViewWorkspaceTools = $canManageAccess || auth()->user()->hasRole(['admin', 'manager']);

            $availableTabs = [
                ...$this->availableTabsForWorkspace($workspace, $canManageAccess, $canViewWorkspaceTools),
                'manual-lead' => 'Add Lead',
                'manual-opportunity' => 'Add Opportunity',
                'manual-quote' => 'New Quote',
            ];

            if ($canViewWorkspaceTools) {
                $availableTabs['sources'] = 'Sources';
            }

            if ($canManageAccess) {
                $availableTabs['access'] = 'Access';
            }

            if (! array_key_exists($this->activeTab, $availableTabs)) {
                $this->activeTab = 'leads';
            }

            $leadQuery = $this->buildLeadQuery($workspace);

            $opportunityQuery = $this->buildOpportunityQuery($workspace);

            $leads = $this->applyLeadSorting($leadQuery)
                ->paginate($this->leadPerPage, ['*'], 'leadsPage');

            $opportunities = $this->applyOpportunitySorting($opportunityQuery)
                ->paginate($this->opportunityPerPage, ['*'], 'opportunitiesPage');

            $contactsQuery = $this->buildContactsQuery($workspace);

            $customersQuery = $this->buildCustomersQuery($workspace);

            $contacts = $this->applyContactSorting($contactsQuery)
                ->paginate($this->contactPerPage, ['*'], 'contactsPage');

            $customers = $this->applyCustomerSorting($customersQuery)
                ->paginate($this->customerPerPage, ['*'], 'customersPage');

            $quotes = $this->applyQuoteSorting($this->buildQuoteQuery($workspace))
                ->paginate($this->quotePerPage, ['*'], 'quotesPage');

            $sheetSources = $workspace->sheetSources()->latest()->get();
            $workspaceUsers = $workspace->users()->with(['roles.permissions', 'userPermissions'])->orderBy('name')->get();
            $monthlyReports = $workspace->monthlyReports()->orderByDesc('month_start')->limit(6)->get();
            $latestReport = $monthlyReports->first();
            $leadOptions = Lead::query()
                ->where('workspace_id', $workspace->id)
                ->orderByDesc('submission_date')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(['id', 'company_name', 'lead_id', 'external_key']);
            $opportunityOptions = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->orderByDesc('submission_date')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(['id', 'company_name', 'external_key']);

            $liveLeadBase = Lead::query()->where('workspace_id', $workspace->id);
            $liveOpportunityBase = Opportunity::query()->where('workspace_id', $workspace->id);

            $kpis = [
                [
                    'label' => 'Total Leads',
                    'value' => (clone $liveLeadBase)->count(),
                    'detail' => 'Across all sources',
                ],
                [
                    'label' => 'Sales Qualified',
                    'value' => (clone $liveLeadBase)->where('status', Lead::STATUS_SALES_QUALIFIED)->count(),
                    'detail' => 'Ready for deal work',
                ],
                [
                    'label' => 'Open Opportunities',
                    'value' => (clone $liveOpportunityBase)->count(),
                    'detail' => 'Pipeline records',
                ],
                [
                    'label' => 'Closed Won Revenue',
                    'value' => number_format((float) (clone $liveOpportunityBase)->where('sales_stage', Opportunity::STAGE_CLOSED_WON)->sum('revenue_potential'), 0),
                    'detail' => 'AED won',
                ],
            ];

            $sourceBreakdown = Lead::query()
                ->select('lead_source', DB::raw('count(*) as total'))
                ->where('workspace_id', $workspace->id)
                ->groupBy('lead_source')
                ->orderByDesc('total')
                ->get();

            $analyticsLeadBase = $this->applyAnalyticsRange(
                Lead::query()->where('workspace_id', $workspace->id),
                'submission_date',
            );
            $analyticsOpportunityBase = $this->applyAnalyticsRange(
                Opportunity::query()->where('workspace_id', $workspace->id),
                'submission_date',
            );
            $analyticsReportBase = $this->applyAnalyticsRange(
                $workspace->monthlyReports(),
                'month_start',
            );
            $analyticsAvailableMonths = $this->analyticsAvailableMonths($workspace);

            if ($this->analyticsMonth === '') {
                $this->analyticsMonth = $analyticsAvailableMonths->keys()->first() ?? $this->defaultAnalyticsMonth();
            }

            $analyticsReportRows = (clone $analyticsReportBase)
                ->orderByDesc('month_start')
                ->limit(6)
                ->get()
                ->sortBy('month_start')
                ->values();

            $analyticsLeadCount = (clone $analyticsLeadBase)->count();
            $analyticsQualifiedCount = (clone $analyticsLeadBase)
                ->where('status', Lead::STATUS_SALES_QUALIFIED)
                ->count();
            $analyticsOpportunityCount = (clone $analyticsOpportunityBase)->count();
            $analyticsPotentialRevenue = (float) (clone $analyticsOpportunityBase)->sum('revenue_potential');
            $analyticsWonRevenue = (float) (clone $analyticsOpportunityBase)
                ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                ->sum('revenue_potential');
            $analyticsWonCount = (clone $analyticsOpportunityBase)
                ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                ->count();

            $analyticsKpis = [
                [
                    'label' => 'Leads In Range',
                    'value' => number_format($analyticsLeadCount),
                    'detail' => $this->analyticsRangeLabel(),
                ],
                [
                    'label' => 'Qualified Rate',
                    'value' => $analyticsLeadCount > 0
                        ? number_format(($analyticsQualifiedCount / $analyticsLeadCount) * 100, 1).'%' : '0%',
                    'detail' => number_format($analyticsQualifiedCount).' sales qualified leads',
                ],
                [
                    'label' => 'Opportunities',
                    'value' => number_format($analyticsOpportunityCount),
                    'detail' => 'In the selected reporting window',
                ],
                [
                    'label' => 'Won Revenue',
                    'value' => 'AED '.number_format($analyticsWonRevenue, 0),
                    'detail' => number_format($analyticsWonCount).' closed-won deals',
                ],
            ];

            $analyticsBreakdownRows = match ($this->analyticsBreakdown) {
                'service' => (clone $analyticsLeadBase)
                    ->selectRaw('COALESCE(service, ?) as label, COUNT(*) as total', ['Unknown'])
                    ->groupBy('service')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get(),
                'status' => (clone $analyticsLeadBase)
                    ->selectRaw('COALESCE(status, ?) as label, COUNT(*) as total', ['Unknown'])
                    ->groupBy('status')
                    ->orderByDesc('total')
                    ->get(),
                'stage' => (clone $analyticsOpportunityBase)
                    ->selectRaw('COALESCE(sales_stage, ?) as label, COUNT(*) as total, COALESCE(SUM(revenue_potential), 0) as revenue', ['Unknown'])
                    ->groupBy('sales_stage')
                    ->orderByDesc('total')
                    ->get(),
                default => (clone $analyticsLeadBase)
                    ->selectRaw('COALESCE(lead_source, ?) as label, COUNT(*) as total', ['Unknown'])
                    ->groupBy('lead_source')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get(),
            };

            if ($this->analyticsBreakdown === 'status') {
                $analyticsBreakdownRows = $analyticsBreakdownRows->map(function ($row) use ($workspace) {
                    $row->label = $row->label === 'Unknown'
                        ? $row->label
                        : $this->leadStatusLabel($row->label, $workspace);

                    return $row;
                });
            }

            if ($this->analyticsBreakdown === 'stage') {
                $analyticsBreakdownRows = $analyticsBreakdownRows->map(function ($row) use ($workspace) {
                    $row->label = $row->label === 'Unknown'
                        ? $row->label
                        : $this->opportunityStageLabel($row->label, $workspace);

                    return $row;
                });
            }

            $analyticsMonthlyRows = $analyticsReportRows->sortByDesc('month_start')->values();

            $analyticsSnapshot = [
                'range_label' => $this->analyticsRangeLabel(),
                'top_source' => (clone $analyticsLeadBase)
                    ->selectRaw('COALESCE(lead_source, ?) as label, COUNT(*) as total', ['Unknown'])
                    ->groupBy('lead_source')
                    ->orderByDesc('total')
                    ->first(),
                'top_service' => (clone $analyticsLeadBase)
                    ->selectRaw('COALESCE(service, ?) as label, COUNT(*) as total', ['Unknown'])
                    ->groupBy('service')
                    ->orderByDesc('total')
                    ->first(),
                'avg_revenue' => (float) (clone $analyticsOpportunityBase)->avg('revenue_potential'),
            ];

            $analyticsSqlChartRows = $analyticsReportRows->map(fn ($report) => [
                'label' => $report->year_month,
                'sqls' => (int) $report->total_opportunities_count,
                'closed_won' => (int) $report->closed_won_count,
                'won_revenue' => (float) $report->won_revenue_potential,
            ]);

            $analyticsAdsChartRows = $analyticsReportRows->map(function ($report) {
                $spend = (float) $report->google_ads_cost;
                $leads = (int) $report->google_ads_leads;
                $cpl = $leads > 0 ? $spend / $leads : (float) $report->cost_per_conversion;

                return [
                    'label' => $report->year_month,
                    'spend' => $spend,
                    'leads' => $leads,
                    'cpl' => $cpl,
                ];
            });

            $analyticsWonCustomers = (clone $analyticsOpportunityBase)
                ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                ->whereNotNull('company_name')
                ->orderByDesc('revenue_potential')
                ->limit(5)
                ->get(['company_name', 'revenue_potential']);

            $analyticsDealSummary = [
                'potential_value' => $analyticsPotentialRevenue,
                'converted_value' => $analyticsWonRevenue,
                'total_deals' => $analyticsOpportunityCount,
                'won_leads' => $analyticsWonCount,
            ];

            $adsSpend = (float) $analyticsReportRows->sum('total_ads_cost');
            $adsRevenue = (float) $analyticsReportRows->sum('won_revenue_potential');
            $googleAdsSpend = (float) $analyticsReportRows->sum('google_ads_cost');
            $googleAdsLeads = (int) $analyticsReportRows->sum('google_ads_leads');
            $romi = $adsSpend > 0 ? (($adsRevenue - $adsSpend) / $adsSpend) * 100 : null;
            $roas = $adsSpend > 0 ? $adsRevenue / $adsSpend : null;

            $analyticsEfficiency = [
                'ads_spend' => $adsSpend,
                'google_ads_spend' => $googleAdsSpend,
                'google_ads_leads' => $googleAdsLeads,
                'revenue' => $adsRevenue,
                'romi' => $romi,
                'roas' => $roas,
                'romi_band' => $this->romiBand($romi),
            ];

            $selectedLead = $this->selectedLeadId
                ? Lead::query()
                    ->with(['assignedUser', 'sheetSource'])
                    ->withCount('opportunities')
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedLeadId)
                : null;

            $selectedOpportunity = $this->selectedOpportunityId
                ? Opportunity::query()
                    ->with(['lead', 'assignedUser', 'sheetSource'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedOpportunityId)
                : null;

            $selectedContact = $this->selectedContactId
                ? Lead::query()
                    ->with(['assignedUser'])
                    ->withCount('opportunities')
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedContactId)
                : null;

            $selectedCustomer = $this->selectedCustomerId
                ? Opportunity::query()
                    ->with(['lead', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedCustomerId)
                : null;

            $selectedQuote = $this->selectedQuoteId
                ? Quote::query()
                    ->with(['lead', 'opportunity', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedQuoteId)
                : null;

            $enrichment = app(WorkspaceEnrichmentService::class);

            $leadInsights = $selectedLead
                ? $enrichment->contactInsights($selectedLead)
                : [];

            $opportunityInsights = $selectedOpportunity && $selectedOpportunity->lead
                ? $enrichment->customerInsights($selectedOpportunity)
                : [];

            $contactInsights = $selectedContact
                ? $enrichment->contactInsights($selectedContact)
                : [];

            $customerInsights = $selectedCustomer
                ? $enrichment->customerInsights($selectedCustomer)
                : [];
        }

        return view('livewire.crm-dashboard', [
            'analyticsAdsChartRows' => $analyticsAdsChartRows,
            'analyticsAvailableMonths' => $analyticsAvailableMonths,
            'analyticsBreakdownRows' => $analyticsBreakdownRows,
            'analyticsDealSummary' => $analyticsDealSummary,
            'analyticsEfficiency' => $analyticsEfficiency,
            'analyticsKpis' => $analyticsKpis,
            'analyticsMonthlyRows' => $analyticsMonthlyRows,
            'analyticsSnapshot' => $analyticsSnapshot,
            'analyticsSqlChartRows' => $analyticsSqlChartRows,
            'analyticsWonCustomers' => $analyticsWonCustomers,
            'canManageAccess' => $canManageAccess,
            'canViewWorkspaceTools' => $canViewWorkspaceTools,
            'leadInsights' => $leadInsights,
            'opportunityInsights' => $opportunityInsights,
            'contactInsights' => $contactInsights,
            'contacts' => $contacts,
            'companies' => $companies,
            'currentWorkspace' => $workspace,
            'currentWorkspaceTemplateDescription' => $workspace?->templateDescription() ?? data_get(Workspace::templateDefinitionFor(Workspace::defaultTemplateKey()), 'description', ''),
            'currentWorkspaceTemplateModules' => $workspace?->templateModules() ?? Workspace::defaultTemplateModules(),
            'currentWorkspaceTemplateName' => $workspace?->templateName() ?? data_get(Workspace::templateDefinitionFor(Workspace::defaultTemplateKey()), 'name', 'General Maritime'),
            'currentWorkspaceExtraModules' => $workspace
                ? collect($workspace->templateModules())
                    ->reject(fn (string $module) => array_key_exists($module, $this->coreTabDefinitions()))
                    ->reject(fn (string $module) => in_array($module, ['sources', 'access', 'settings', 'exports'], true))
                    ->values()
                : collect(),
            'customerInsights' => $customerInsights,
            'customers' => $customers,
            'disqualificationReasons' => $workspace ? $this->disqualificationReasonOptions($workspace) : Workspace::defaultDisqualificationReasons(),
            'kpis' => $kpis,
            'latestReport' => $latestReport,
            'leads' => $leads,
            'leadServices' => $workspace ? $this->leadServiceOptions($workspace) : Workspace::defaultLeadServices(),
            'leadSources' => $workspace ? $this->leadSourceOptions($workspace) : Workspace::defaultLeadSources(),
            'leadStatusOptions' => $workspace ? $this->leadStatusOptions($workspace) : Workspace::defaultLeadStatusLabels(),
            'leadOptions' => $leadOptions,
            'monthlyReports' => $monthlyReports,
            'opportunities' => $opportunities,
            'opportunityStageOptions' => $workspace ? $this->opportunityStageOptions($workspace) : Workspace::defaultOpportunityStageLabels(),
            'permissions' => $permissions,
            'opportunityOptions' => $opportunityOptions ?? collect(),
            'quotes' => $quotes,
            'quoteStatusOptions' => Quote::STATUSES,
            'roles' => $roles,
            'selectedLead' => $selectedLead,
            'selectedOpportunity' => $selectedOpportunity,
            'selectedQuote' => $selectedQuote,
            'selectedContact' => $selectedContact,
            'selectedCustomer' => $selectedCustomer,
            'sheetSources' => $sheetSources,
            'sourceBreakdown' => $sourceBreakdown,
            'templateModuleMeta' => $this->templateModuleMeta(),
            'tabs' => $workspace
                ? $this->availableTabsForWorkspace($workspace, $canManageAccess, $canViewWorkspaceTools)
                : $this->coreTabDefinitions(),
            'workspaceUsers' => $workspaceUsers,
            'workspaces' => $workspaces,
            'workspaceTemplates' => Workspace::workspaceTemplates(),
        ]);
    }

    protected function resetForms(): void
    {
        $this->companyForm = [
            'name' => '',
            'industry' => 'Maritime',
            'contact_email' => '',
            'contact_phone' => '',
            'timezone' => 'Asia/Dubai',
        ];

        $this->workspaceForm = [
            'company_id' => '',
            'name' => '',
            'description' => '',
            'template_key' => Workspace::defaultTemplateKey(),
        ];

        $this->workspaceSettingsForm = $this->defaultWorkspaceSettingsForm();

        $this->sourceForm = [
            'workspace_id' => '',
            'type' => SheetSource::TYPE_LEADS,
            'name' => '',
            'url' => '',
            'description' => '',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
            'is_active' => true,
        ];

        $this->editingSourceForm = [];

        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'permission_ids' => [],
        ];

        $this->roleForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'level' => 3,
            'permission_ids' => [],
        ];

        $this->permissionForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'model' => 'User',
        ];

        $this->editingWorkspaceUserForm = [];

        $this->manualLeadForm = [
            'contact_name' => '',
            'company_name' => '',
            'email' => '',
            'phone' => '',
            'service' => 'Container Conversion',
            'lead_source' => 'Email',
            'status' => Lead::STATUS_IN_PROGRESS,
            'lead_value' => '',
            'notes' => '',
        ];

        $this->manualOpportunityForm = [
            'lead_id' => '',
            'company_name' => '',
            'contact_email' => '',
            'lead_source' => 'Email',
            'required_service' => 'Container Conversion',
            'revenue_potential' => '',
            'project_timeline_days' => '',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => '',
        ];

        $this->manualQuoteForm = [
            'opportunity_id' => '',
            'lead_id' => '',
            'company_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'status' => Quote::STATUS_DRAFT,
            'valid_until' => '',
            'notes' => '',
        ];
    }

    protected function primeForms(?Workspace $workspace): void
    {
        if (! $workspace) {
            $this->workspaceSettingsForm = $this->defaultWorkspaceSettingsForm();

            return;
        }

        $this->workspaceForm['company_id'] = $workspace->company_id;
        $this->workspaceForm['template_key'] = $workspace->templateKey();
        $this->sourceForm['workspace_id'] = $workspace->id;
        $this->workspaceSettingsForm = $this->workspaceSettingsFormFor($workspace);
    }

    protected function accessibleWorkspaces(): EloquentCollection
    {
        return auth()->user()->isAdmin()
            ? Workspace::query()->with('company')->orderBy('name')->get()
            : auth()->user()->workspaces()->with('company')->orderBy('name')->get();
    }

    protected function visibleCompanies(EloquentCollection $workspaces): EloquentCollection
    {
        if (auth()->user()->isAdmin()) {
            return Company::query()->orderBy('name')->get();
        }

        $companyIds = $workspaces->pluck('company_id')->unique()->values();

        return Company::query()->whereIn('id', $companyIds)->orderBy('name')->get();
    }

    protected function currentWorkspace(): ?Workspace
    {
        return $this->resolveCurrentWorkspace($this->accessibleWorkspaces());
    }

    protected function currentWorkspaceOrFail(): Workspace
    {
        $workspace = $this->currentWorkspace();

        abort_if(! $workspace, 404);

        return $workspace;
    }

    protected function resolveCurrentWorkspace(EloquentCollection $workspaces): ?Workspace
    {
        if ($workspaces->isEmpty()) {
            return null;
        }

        $workspace = $workspaces->firstWhere('id', (int) $this->workspaceId)
            ?? $workspaces->firstWhere('id', (int) auth()->user()->default_workspace_id)
            ?? $workspaces->first();

        return $workspace;
    }

    protected function ensureWorkspaceVisible(int $workspaceId): void
    {
        abort_unless($this->accessibleWorkspaces()->pluck('id')->contains($workspaceId), 403);
    }

    protected function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    protected function ensureWorkspaceOwner(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(auth()->user()->ownsWorkspace($workspace->id), 403);
    }

    protected function ensureWorkspaceManager(): void
    {
        abort_unless(auth()->user()->hasRole(['admin', 'manager']), 403);
    }

    protected function canBootstrapWorkspace(): bool
    {
        return $this->accessibleWorkspaces()->isEmpty();
    }

    protected function bootstrapWorkspaceOwner(Workspace $workspace): void
    {
        $user = auth()->user();

        $managerRole = Role::query()->firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Workspace manager', 'level' => 6],
        );

        if (! $user->hasRole('manager') && ! $user->hasRole('admin')) {
            $user->attachRole($managerRole);
        }

        $user->forceFill([
            'company_id' => $workspace->company_id,
            'default_workspace_id' => $workspace->id,
            'is_active' => $user->is_active ?? true,
        ])->save();

        $workspace->users()->syncWithoutDetaching([
            $user->id => [
                'job_title' => $user->job_title ?: 'Workspace owner',
                'is_owner' => true,
            ],
        ]);
    }

    protected function canManageWorkspaceAccess(?Workspace $workspace = null): bool
    {
        $workspace ??= $this->currentWorkspace();

        if (! $workspace) {
            return false;
        }

        return auth()->user()->ownsWorkspace($workspace->id);
    }

    protected function buildLeadQuery(Workspace $workspace)
    {
        $query = Lead::query()
            ->with(['assignedUser'])
            ->withCount('opportunities')
            ->where('workspace_id', $workspace->id);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('lead_id', 'like', "%{$search}%");
            });
        }

        if ($this->leadStatusFilter !== '') {
            $query->where('status', $this->leadStatusFilter);
        }

        if ($this->leadSourceFilter !== '') {
            $query->where('lead_source', $this->leadSourceFilter);
        }

        return $query;
    }

    protected function buildOpportunityQuery(Workspace $workspace)
    {
        $query = Opportunity::query()
            ->with(['assignedUser', 'lead'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('external_key', 'like', "%{$search}%");
            });
        }

        if ($this->opportunityStageFilter !== '') {
            $query->where('sales_stage', $this->opportunityStageFilter);
        }

        return $query;
    }

    protected function buildQuoteQuery(Workspace $workspace)
    {
        $query = Quote::query()
            ->with(['assignedUser', 'lead', 'opportunity'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->quoteSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        if ($this->quoteStatusFilter !== '') {
            $query->where('status', $this->quoteStatusFilter);
        }

        return $query;
    }

    protected function buildContactsQuery(Workspace $workspace)
    {
        $query = Lead::query()
            ->with(['assignedUser'])
            ->withCount('opportunities')
            ->where('workspace_id', $workspace->id)
            ->whereDoesntHave('opportunities')
            ->where(function ($builder) {
                $builder->whereNotNull('contact_name')
                    ->orWhereNotNull('company_name')
                    ->orWhereNotNull('email')
                    ->orWhereNotNull('phone');
            });

        $contactSearch = trim($this->contactSearch);

        if ($contactSearch !== '') {
            $query->where(function ($builder) use ($contactSearch) {
                $builder->where('contact_name', 'like', "%{$contactSearch}%")
                    ->orWhere('company_name', 'like', "%{$contactSearch}%")
                    ->orWhere('email', 'like', "%{$contactSearch}%")
                    ->orWhere('phone', 'like', "%{$contactSearch}%")
                    ->orWhere('lead_id', 'like', "%{$contactSearch}%");
            });
        }

        return $query;
    }

    protected function buildCustomersQuery(Workspace $workspace)
    {
        $query = Opportunity::query()
            ->with(['lead', 'assignedUser'])
            ->where('workspace_id', $workspace->id);

        $customerSearch = trim($this->customerSearch);

        if ($customerSearch !== '') {
            $query->where(function ($builder) use ($customerSearch) {
                $builder->where('company_name', 'like', "%{$customerSearch}%")
                    ->orWhere('contact_email', 'like', "%{$customerSearch}%")
                    ->orWhere('required_service', 'like', "%{$customerSearch}%")
                    ->orWhere('external_key', 'like', "%{$customerSearch}%");
            });
        }

        return $query;
    }

    protected function workspaceExportFilename(Workspace $workspace, string $type): string
    {
        return Str::slug($workspace->name).'-'.$type.'-'.now()->format('Ymd-His').'.csv';
    }

    protected function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function defaultWorkspaceSettingsForm(): array
    {
        return [
            'template_key' => Workspace::defaultTemplateKey(),
            'lead_status_labels' => Workspace::defaultLeadStatusLabels(Workspace::defaultTemplateKey()),
            'opportunity_stage_labels' => Workspace::defaultOpportunityStageLabels(Workspace::defaultTemplateKey()),
            'disqualification_reasons' => Workspace::defaultDisqualificationReasons(Workspace::defaultTemplateKey()),
            'lead_sources' => Workspace::defaultLeadSources(Workspace::defaultTemplateKey()),
            'lead_services' => Workspace::defaultLeadServices(Workspace::defaultTemplateKey()),
        ];
    }

    protected function workspaceSettingsFormFor(Workspace $workspace): array
    {
        return [
            'template_key' => $workspace->templateKey(),
            'lead_status_labels' => $workspace->leadStatusLabels(),
            'opportunity_stage_labels' => $workspace->opportunityStageLabels(),
            'disqualification_reasons' => $workspace->disqualificationReasons(),
            'lead_sources' => $workspace->leadSourcesCatalog(),
            'lead_services' => $workspace->leadServicesCatalog(),
        ];
    }

    protected function sanitizeList(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function sanitizeLabelMap(array $values, array $allowedKeys): array
    {
        return collect($values)
            ->only($allowedKeys)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->all();
    }

    public function leadStatusOptions(?Workspace $workspace = null): array
    {
        return ($workspace ?? $this->currentWorkspace())?->leadStatusLabels()
            ?? Workspace::defaultLeadStatusLabels(Workspace::defaultTemplateKey());
    }

    public function opportunityStageOptions(?Workspace $workspace = null): array
    {
        return ($workspace ?? $this->currentWorkspace())?->opportunityStageLabels()
            ?? Workspace::defaultOpportunityStageLabels(Workspace::defaultTemplateKey());
    }

    public function disqualificationReasonOptions(?Workspace $workspace = null): array
    {
        return ($workspace ?? $this->currentWorkspace())?->disqualificationReasons()
            ?? Workspace::defaultDisqualificationReasons(Workspace::defaultTemplateKey());
    }

    public function leadSourceOptions(?Workspace $workspace = null): array
    {
        $workspace ??= $this->currentWorkspace();

        $catalog = $workspace?->leadSourcesCatalog() ?? Workspace::defaultLeadSources(Workspace::defaultTemplateKey());
        $existing = $workspace
            ? Lead::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('lead_source')
                ->pluck('lead_source')
                ->all()
            : [];

        return collect($catalog)
            ->merge($existing)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function leadServiceOptions(?Workspace $workspace = null): array
    {
        $workspace ??= $this->currentWorkspace();

        $catalog = $workspace?->leadServicesCatalog() ?? Workspace::defaultLeadServices(Workspace::defaultTemplateKey());
        $existingLeads = $workspace
            ? Lead::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('service')
                ->pluck('service')
                ->all()
            : [];
        $existingOpportunities = $workspace
            ? Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('required_service')
                ->pluck('required_service')
                ->all()
            : [];

        return collect($catalog)
            ->merge($existingLeads)
            ->merge($existingOpportunities)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function leadStatusLabel(string $status, ?Workspace $workspace = null): string
    {
        return $this->leadStatusOptions($workspace)[$status] ?? $status;
    }

    public function opportunityStageLabel(string $stage, ?Workspace $workspace = null): string
    {
        return $this->opportunityStageOptions($workspace)[$stage] ?? $stage;
    }

    protected function uniqueSlug(string $modelClass, string $value, array $extraWhere = []): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $iteration = 2;

        while ($modelClass::query()->where($extraWhere)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$iteration}";
            $iteration++;
        }

        return $slug;
    }

    protected function applyLeadSorting($query)
    {
        return match ($this->leadSort) {
            'oldest' => $query->orderBy('submission_date')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('submission_date'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('submission_date'),
            'value_desc' => $query->orderByDesc('lead_value')->orderByDesc('submission_date'),
            'value_asc' => $query->orderBy('lead_value')->orderByDesc('submission_date'),
            default => $query->orderByDesc('submission_date')->orderByDesc('created_at'),
        };
    }

    protected function applyOpportunitySorting($query)
    {
        return match ($this->opportunitySort) {
            'oldest' => $query->orderBy('submission_date')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('submission_date'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('submission_date'),
            'revenue_desc' => $query->orderByDesc('revenue_potential')->orderByDesc('submission_date'),
            'revenue_asc' => $query->orderBy('revenue_potential')->orderByDesc('submission_date'),
            default => $query->orderByDesc('submission_date')->orderByDesc('created_at'),
        };
    }

    protected function applyContactSorting($query)
    {
        return match ($this->contactSort) {
            'oldest' => $query->orderBy('submission_date')->orderBy('created_at'),
            'name_asc' => $query->orderBy('contact_name')->orderByDesc('submission_date'),
            'name_desc' => $query->orderByDesc('contact_name')->orderByDesc('submission_date'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('submission_date'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('submission_date'),
            default => $query->orderByDesc('submission_date')->orderByDesc('created_at'),
        };
    }

    protected function applyCustomerSorting($query)
    {
        return match ($this->customerSort) {
            'oldest' => $query->orderBy('submission_date')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('submission_date'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('submission_date'),
            'value_desc' => $query->orderByDesc('revenue_potential')->orderByDesc('submission_date'),
            'value_asc' => $query->orderBy('revenue_potential')->orderByDesc('submission_date'),
            default => $query->orderByDesc('submission_date')->orderByDesc('created_at'),
        };
    }

    protected function applyQuoteSorting($query)
    {
        return match ($this->quoteSort) {
            'oldest' => $query->orderBy('quoted_at')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('quoted_at'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('quoted_at'),
            'sell_desc' => $query->orderByDesc('sell_amount')->orderByDesc('quoted_at'),
            'sell_asc' => $query->orderBy('sell_amount')->orderByDesc('quoted_at'),
            default => $query->orderByDesc('quoted_at')->orderByDesc('created_at'),
        };
    }

    protected function applyAnalyticsRange($query, string $column)
    {
        [$start, $end] = $this->analyticsBounds();

        if (! $start || ! $end) {
            return $query;
        }

        return $query
            ->whereDate($column, '>=', $start->toDateString())
            ->whereDate($column, '<=', $end->toDateString());
    }

    protected function analyticsRangeLabel(): string
    {
        return match ($this->analyticsRange) {
            'last_month' => 'Last month',
            '30' => 'Last 30 days',
            '60' => 'Last 60 days',
            '90' => 'Last 90 days',
            'month' => $this->analyticsMonthLabel(),
            default => 'All time',
        };
    }

    protected function analyticsAvailableMonths(Workspace $workspace)
    {
        $reportMonths = $workspace->monthlyReports()
            ->whereNotNull('month_start')
            ->orderByDesc('month_start')
            ->pluck('month_start')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        $leadMonths = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('submission_date')
            ->pluck('submission_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        $opportunityMonths = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('submission_date')
            ->pluck('submission_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        return $reportMonths
            ->merge($leadMonths)
            ->merge($opportunityMonths)
            ->unique(fn ($date) => $date->format('Y-m'))
            ->sortByDesc(fn ($date) => $date->format('Y-m'))
            ->mapWithKeys(fn ($date) => [$date->format('Y-m') => $date->format('F Y')]);
    }

    protected function analyticsBounds(): array
    {
        return match ($this->analyticsRange) {
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            '30' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            '60' => [now()->subDays(60)->startOfDay(), now()->endOfDay()],
            '90' => [now()->subDays(90)->startOfDay(), now()->endOfDay()],
            'month' => $this->monthBounds($this->analyticsMonth),
            default => [null, null],
        };
    }

    protected function monthBounds(string $month): array
    {
        if ($month === '') {
            return [null, null];
        }

        $parsed = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [$parsed->copy()->startOfMonth(), $parsed->copy()->endOfMonth()];
    }

    protected function analyticsMonthLabel(): string
    {
        if ($this->analyticsMonth === '') {
            return 'Specific month';
        }

        return Carbon::createFromFormat('Y-m', $this->analyticsMonth)->format('F Y');
    }

    protected function defaultAnalyticsMonth(): string
    {
        return now()->subMonthNoOverflow()->format('Y-m');
    }

    protected function romiBand(?float $romi): array
    {
        if ($romi === null) {
            return [
                'label' => 'No spend data',
                'classes' => 'bg-zinc-100 text-zinc-600',
            ];
        }

        return match (true) {
            $romi < 100 => [
                'label' => '0% - 100%: Low ROMI',
                'classes' => 'bg-rose-100 text-rose-700',
            ],
            $romi < 300 => [
                'label' => '100% - 300%: Moderate ROMI',
                'classes' => 'bg-amber-100 text-amber-700',
            ],
            $romi < 500 => [
                'label' => '300% - 500%: High ROMI',
                'classes' => 'bg-emerald-100 text-emerald-700',
            ],
            default => [
                'label' => '500% and above: Excellent ROMI',
                'classes' => 'bg-sky-100 text-sky-700',
            ],
        };
    }

    protected function friendlySyncError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return match (true) {
            str_contains($message, 'Google OAuth client ID and secret must be saved first.') => 'This Google Sheets source cannot sync yet. An admin must first save the Google OAuth client ID and secret in Admin > Data Sources.',
            str_contains($message, 'Google is not connected for this company.') => 'This Google Sheets source cannot sync yet. An admin must connect the company Google account in Admin > Data Sources first.',
            str_contains($message, 'Google access expired and no refresh token is available.') => 'Google access has expired for this company. An admin needs to reconnect Google in Admin > Data Sources.',
            default => Str::limit($message, 220),
        };
    }

    public function leadStatusClasses(string $status): string
    {
        return match ($status) {
            Lead::STATUS_SALES_QUALIFIED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Lead::STATUS_DISQUALIFIED => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function displayedLeadStatus(Lead $lead): string
    {
        if ($this->pendingDisqualificationLeadId === $lead->id) {
            return Lead::STATUS_DISQUALIFIED;
        }

        return $lead->status;
    }

    public function showsDisqualificationReasonSelector(Lead $lead): bool
    {
        return $this->pendingDisqualificationLeadId === $lead->id
            || $lead->status === Lead::STATUS_DISQUALIFIED;
    }

    public function leadScore(Lead $lead): array
    {
        $cacheKey = (string) ($lead->id ?: $lead->external_key ?: spl_object_id($lead));

        return $this->leadScoreCache[$cacheKey] ??= app(LeadScoringService::class)->score($lead);
    }

    public function leadScoreClasses(int $score): string
    {
        return match (true) {
            $score >= 80 => 'bg-emerald-100 text-emerald-800',
            $score >= 60 => 'bg-sky-100 text-sky-800',
            $score >= 40 => 'bg-amber-100 text-amber-800',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function opportunityStageClasses(string $stage): string
    {
        return match ($stage) {
            Opportunity::STAGE_CLOSED_WON => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Opportunity::STAGE_CLOSED_LOST, Opportunity::STAGE_NO_RESPONSE => 'border-rose-200 bg-rose-50 text-rose-700',
            Opportunity::STAGE_PROPOSAL_SENT, Opportunity::STAGE_DRAWINGS_SUBMITTED, Opportunity::STAGE_DECISION_MAKING => 'border-sky-200 bg-sky-50 text-sky-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function quoteStatusClasses(string $status): string
    {
        return match ($status) {
            Quote::STATUS_ACCEPTED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Quote::STATUS_DECLINED, Quote::STATUS_EXPIRED => 'border-rose-200 bg-rose-50 text-rose-700',
            Quote::STATUS_SENT => 'border-sky-200 bg-sky-50 text-sky-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function sourceStatusClasses(?string $status): string
    {
        return match ($status) {
            'synced' => 'bg-emerald-100 text-emerald-800',
            'failed' => 'bg-rose-100 text-rose-700',
            'syncing' => 'bg-amber-100 text-amber-700',
            default => 'bg-zinc-100 text-zinc-600',
        };
    }

    protected function nextQuoteNumber(Workspace $workspace): string
    {
        $nextId = ((int) Quote::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'QT-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function quoteMarginFromPayload(array $payload): ?float
    {
        $buy = data_get($payload, 'buy_amount');
        $sell = data_get($payload, 'sell_amount');

        if ($buy === null || $buy === '' || $sell === null || $sell === '') {
            return null;
        }

        return (float) $sell - (float) $buy;
    }

    protected function flash(string $message): void
    {
        session()->flash('status', $message);
    }

    protected function coreTabDefinitions(): array
    {
        return [
            'leads' => 'Leads',
            'opportunities' => 'Opportunities',
            'contacts' => 'Contacts',
            'customers' => 'Customers',
        ];
    }

    protected function templateModuleMeta(): array
    {
        return [
            'quotes' => [
                'label' => 'Quotes',
                'description' => 'Build freight quotes, compare buy and sell rates, and keep revisions in one place.',
            ],
            'shipments' => [
                'label' => 'Shipments',
                'description' => 'Track booked shipments, milestones, ETD and ETA handoff from the sales pipeline.',
            ],
            'carriers' => [
                'label' => 'Carriers',
                'description' => 'Manage preferred carriers, service lanes, and rate relationships for forwarding teams.',
            ],
            'projects' => [
                'label' => 'Projects',
                'description' => 'Track conversion projects from scope through delivery and installation.',
            ],
            'drawings' => [
                'label' => 'Drawings',
                'description' => 'Collect technical drawings, revisions, and engineering review stages.',
            ],
            'delivery_tracking' => [
                'label' => 'Delivery Tracking',
                'description' => 'Monitor fabrication and final delivery milestones for container projects.',
            ],
            'vessel_calls' => [
                'label' => 'Vessel Calls',
                'description' => 'Manage vessel ETA, ETD, port calls, and requisition-linked customer demand.',
            ],
            'supply_orders' => [
                'label' => 'Supply Orders',
                'description' => 'Handle chandling order capture, requisitions, and delivered supply lists.',
            ],
            'delivery_tasks' => [
                'label' => 'Delivery Tasks',
                'description' => 'Coordinate urgent port deliveries, boarding tasks, and delivery completion.',
            ],
            'bookings' => [
                'label' => 'Bookings',
                'description' => 'Track liner booking requests, confirmations, and customer shipping allocations.',
            ],
            'sailings' => [
                'label' => 'Sailings',
                'description' => 'Expose sailings, schedules, and vessel coverage linked to the commercial workflow.',
            ],
            'customer_accounts' => [
                'label' => 'Customer Accounts',
                'description' => 'Manage liner account structures, contract rates, and booking activity.',
            ],
            'fleet' => [
                'label' => 'Fleet',
                'description' => 'Organize managed vessels, owners, and fleet-level service relationships.',
            ],
            'technical_management' => [
                'label' => 'Technical Management',
                'description' => 'Track technical management proposals, reviews, and vessel handover work.',
            ],
            'crewing' => [
                'label' => 'Crewing',
                'description' => 'Manage crewing opportunities, owner needs, and manning-related workflows.',
            ],
            'inventory' => [
                'label' => 'Inventory',
                'description' => 'Track container stock, availability, grades, and unit allocation.',
            ],
            'leasing' => [
                'label' => 'Leasing',
                'description' => 'Handle lease enquiries, term discussions, and contract progression.',
            ],
            'depots' => [
                'label' => 'Depots',
                'description' => 'Monitor depot partners, location coverage, and depot-linked container flows.',
            ],
        ];
    }

    protected function availableTabsForWorkspace(Workspace $workspace, bool $canManageAccess, bool $canViewWorkspaceTools): array
    {
        $tabs = $this->coreTabDefinitions();

        foreach ($workspace->templateModules() as $module) {
            if (array_key_exists($module, $tabs)) {
                continue;
            }

            if (in_array($module, ['sources', 'access', 'settings', 'exports'], true)) {
                continue;
            }

            $tabs[$module] = data_get(
                $this->templateModuleMeta(),
                $module.'.label',
                Str::of($module)->replace('_', ' ')->title()->toString(),
            );
        }

        $tabs['analytics'] = 'Analytics';

        if ($canViewWorkspaceTools) {
            $tabs['settings'] = 'Settings';
        }

        return $tabs;
    }
}
