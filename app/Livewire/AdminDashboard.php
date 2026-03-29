<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SheetSource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\GoogleOAuthService;
use App\Services\SheetSourceSyncService;
use App\Services\WorkspaceBillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use jeremykenedy\LaravelRoles\Models\Role;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;

class AdminDashboard extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $workspaceId = null;

    public $leadCsvUpload = null;

    public array $csvUploadForm = [];

    public array $googleOauthForm = [];

    public array $googleSourceForm = [];

    public array $googleSpreadsheets = [];

    public array $googleSheetTabs = [];

    public ?int $editingWorkspaceId = null;

    public ?int $editingSourceId = null;

    public string $activeTab = 'analytics';

    public string $sourceSort = 'newest';

    public string $workspaceUserSort = 'newest';

    public string $workspaceSort = 'newest';

    public int $sourcePerPage = 10;

    public int $workspaceUserPerPage = 10;

    public int $workspacePerPage = 10;

    public array $companyForm = [];

    public array $workspaceForm = [];

    public array $editingWorkspaceForm = [];

    public array $sourceForm = [];

    public array $editingSourceForm = [];

    public array $userForm = [];

    public array $roleForm = [];

    public array $billingForm = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->resetForms();
        $this->activeTab = request()->string('tab')->toString() ?: 'analytics';

        $workspace = Workspace::query()->with('company')->orderBy('name')->first();

        $this->workspaceId = $workspace?->id;

        $this->primeForms($workspace);
        $this->primeWorkspaceEditor($workspace);
        $this->primeBillingForm($workspace);
    }

    public function updatedWorkspaceId(): void
    {
        $workspace = $this->currentWorkspace();

        $this->primeForms($workspace);
        $this->primeWorkspaceEditor($workspace);
        $this->primeBillingForm($workspace);
        $this->resetPage('sourcesPage');
        $this->resetPage('workspaceUsersPage');
        $this->resetPage('workspacesPage');
    }

    public function saveWorkspaceBilling(): void
    {
        $workspace = $this->currentWorkspaceOrFail();
        $billing = app(WorkspaceBillingService::class);

        $validated = validator($this->billingForm, [
            'plan_key' => ['required', Rule::in(array_keys($billing->planCatalog()))],
            'included_users' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'included_operational_records' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ])->validate();

        $billing->setWorkspacePlan(
            $workspace,
            $validated['plan_key'],
            $validated['included_users'] ?? null,
            $validated['included_operational_records'] ?? null,
        );

        $this->primeBillingForm($workspace->fresh('company'));

        $this->flash("Billing plan updated for {$workspace->name}.");
    }

    public function updatedSourceSort(): void
    {
        $this->resetPage('sourcesPage');
    }

    public function updatedSourcePerPage(): void
    {
        $this->resetPage('sourcesPage');
    }

    public function updatedSourceFormSourceKind(string $value): void
    {
        $this->sourceForm = [
            ...$this->sourceForm,
            ...$this->defaultSourceConnectionFields(),
            'source_kind' => $value,
            'url' => $value === SheetSource::SOURCE_KIND_CARGOWISE_API ? '' : ($this->sourceForm['url'] ?? ''),
        ];
    }

    public function updatedSourceFormCargoAuthMode(string $value): void
    {
        if ($value === 'bearer') {
            $this->sourceForm['cargo_username'] = '';
            $this->sourceForm['cargo_password'] = '';
        }

        if ($value === 'basic') {
            $this->sourceForm['cargo_token'] = '';
        }
    }

    public function updatedEditingSourceFormSourceKind(string $value): void
    {
        $this->editingSourceForm = [
            ...$this->editingSourceForm,
            ...$this->defaultSourceConnectionFields(),
            'source_kind' => $value,
            'url' => $value === SheetSource::SOURCE_KIND_CARGOWISE_API ? '' : ($this->editingSourceForm['url'] ?? ''),
        ];
    }

    public function updatedEditingSourceFormCargoAuthMode(string $value): void
    {
        if ($value === 'bearer') {
            $this->editingSourceForm['cargo_username'] = '';
            $this->editingSourceForm['cargo_password'] = '';
        }

        if ($value === 'basic') {
            $this->editingSourceForm['cargo_token'] = '';
        }
    }

    public function updatedWorkspaceUserSort(): void
    {
        $this->resetPage('workspaceUsersPage');
    }

    public function updatedWorkspaceUserPerPage(): void
    {
        $this->resetPage('workspaceUsersPage');
    }

    public function updatedWorkspaceSort(): void
    {
        $this->resetPage('workspacesPage');
    }

    public function updatedWorkspacePerPage(): void
    {
        $this->resetPage('workspacesPage');
    }

    public function saveCompany(): void
    {
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

        $this->companyForm['name'] = '';
        $this->companyForm['contact_email'] = '';
        $this->companyForm['contact_phone'] = '';
        $this->workspaceForm['company_id'] = $company->id;

        $this->flash("Company {$company->name} created.");
    }

    public function saveWorkspace(): void
    {
        $validated = validator($this->workspaceForm, [
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $workspace = Workspace::create([
            ...$validated,
            'slug' => $this->uniqueSlug(
                Workspace::class,
                $validated['name'],
                ['company_id' => $validated['company_id']],
            ),
            'is_default' => Workspace::where('company_id', $validated['company_id'])->doesntExist(),
        ]);

        $this->workspaceId = $workspace->id;
        $this->workspaceForm['name'] = '';
        $this->workspaceForm['description'] = '';
        $this->primeForms($workspace);
        $this->primeWorkspaceEditor($workspace);

        $this->flash("Workspace {$workspace->name} created.");
    }

    public function saveSheetSource(): void
    {
        $validated = validator($this->sourceForm, [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'type' => ['required', Rule::in(SheetSource::availableTypes())],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'description' => ['nullable', 'string', 'max:255'],
            'source_kind' => ['required', Rule::in(SheetSource::SOURCE_KINDS)],
            'is_active' => ['boolean'],
            'cargo_auth_mode' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseAuthModes()))],
            'cargo_username' => ['nullable', 'string', 'max:255'],
            'cargo_password' => ['nullable', 'string', 'max:2048'],
            'cargo_token' => ['nullable', 'string', 'max:4096'],
            'cargo_format' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseFormats()))],
            'cargo_data_path' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $workspace = Workspace::query()->with('company')->findOrFail($validated['workspace_id']);

        $sourceKind = SheetSource::normalizeSourceKind(
            $validated['source_kind'],
            $validated['url'],
        );

        SheetSource::create([
            ...$validated,
            'company_id' => $workspace->company_id,
            'source_kind' => $sourceKind,
            'sync_status' => 'idle',
            'mapping' => $this->sourceMappingFromForm($validated),
        ]);

        $this->sourceForm = [
            ...$this->sourceForm,
            ...$this->defaultSourceConnectionFields(),
            'name' => '',
            'url' => '',
            'description' => '',
        ];

        $this->flash('Sheet source added.');
    }

    public function saveGoogleClientConfig(): void
    {
        $company = $this->currentCompanyOrFail();

        $validated = validator($this->googleOauthForm, [
            'client_id' => ['required', 'string', 'max:2048'],
            'client_secret' => ['required', 'string', 'max:4096'],
        ])->validate();

        app(GoogleOAuthService::class)->saveClientConfig(
            $company,
            $validated['client_id'],
            $validated['client_secret'],
        );

        $this->flash("Google OAuth app saved for {$company->name}.");
    }

    public function loadGoogleSpreadsheets(): void
    {
        try {
            $company = $this->currentCompanyOrFail();

            $this->googleSpreadsheets = app(GoogleOAuthService::class)->listSpreadsheets($company);
            $this->googleSheetTabs = [];

            if ($this->googleSourceForm['spreadsheet_id'] !== '') {
                $this->loadGoogleSheetTabs();
            }

            $this->flash('Google Sheets loaded.');
        } catch (RuntimeException $exception) {
            $this->flash($exception->getMessage());
        }
    }

    public function updatedGoogleSourceFormSpreadsheetId(): void
    {
        $this->googleSourceForm['sheet_gid'] = '';
        $this->googleSourceForm['sheet_title'] = '';
        $this->googleSheetTabs = [];

        if ($this->googleSourceForm['spreadsheet_id'] !== '') {
            $this->loadGoogleSheetTabs();
        }
    }

    public function updatedGoogleSourceFormSheetGid($value): void
    {
        $sheet = collect($this->googleSheetTabs)->firstWhere('gid', (int) $value);

        $this->googleSourceForm['sheet_title'] = $sheet['title'] ?? '';
    }

    public function loadGoogleSheetTabs(): void
    {
        try {
            $company = $this->currentCompanyOrFail();
            $spreadsheetId = (string) ($this->googleSourceForm['spreadsheet_id'] ?? '');

            if ($spreadsheetId === '') {
                $this->googleSheetTabs = [];

                return;
            }

            $this->googleSheetTabs = app(GoogleOAuthService::class)->listSheets($company, $spreadsheetId);

            if ($this->googleSourceForm['sheet_gid'] !== '') {
                $sheet = collect($this->googleSheetTabs)->firstWhere('gid', (int) $this->googleSourceForm['sheet_gid']);
                $this->googleSourceForm['sheet_title'] = $sheet['title'] ?? '';
            }
        } catch (RuntimeException $exception) {
            $this->flash($exception->getMessage());
        }
    }

    public function createGoogleSheetSource(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->googleSourceForm, [
            'type' => ['required', Rule::in([
                SheetSource::TYPE_LEADS,
                SheetSource::TYPE_OPPORTUNITIES,
                SheetSource::TYPE_REPORTS,
            ])],
            'spreadsheet_id' => ['required', 'string', 'max:255'],
            'sheet_gid' => ['required', 'integer'],
            'sheet_title' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $source = SheetSource::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'type' => $validated['type'],
            'name' => $validated['name'] !== ''
                ? $validated['name']
                : $this->defaultGoogleSourceName($validated['type'], $validated['sheet_title']),
            'url' => $this->googleSheetUrl($validated['spreadsheet_id'], (int) $validated['sheet_gid']),
            'source_kind' => SheetSource::normalizeSourceKind(
                SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API,
                $this->googleSheetUrl($validated['spreadsheet_id'], (int) $validated['sheet_gid']),
                preferApi: true,
            ),
            'description' => $validated['description'] ?: "Connected Google Sheet tab: {$validated['sheet_title']}",
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'spreadsheet_id' => $validated['spreadsheet_id'],
                'sheet_gid' => (int) $validated['sheet_gid'],
                'sheet_title' => $validated['sheet_title'],
                'header_row' => 1,
                'data_start_row' => 2,
                'status_column' => $this->defaultStatusColumns($validated['type']),
            ],
        ]);

        $this->googleSourceForm['type'] = SheetSource::TYPE_LEADS;
        $this->googleSourceForm['name'] = '';
        $this->googleSourceForm['description'] = '';

        $this->flash("Google source {$source->name} added.");
    }

    public function createRole(): void
    {
        $validated = validator($this->roleForm, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:99'],
        ])->validate();

        Role::create($validated);

        $this->roleForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'level' => 3,
        ];

        $this->flash('Role created.');
    }

    public function createUser(): void
    {
        $validated = validator($this->userForm, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('slug')->all())],
            'workspace_ids' => ['required', 'array', 'min:1'],
            'workspace_ids.*' => ['integer', 'exists:workspaces,id'],
        ])->validate();

        $workspaceIds = collect($validated['workspace_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $companyId = Workspace::query()
            ->whereIn('id', $workspaceIds)
            ->value('company_id');

        $user = User::create([
            'company_id' => $companyId,
            'default_workspace_id' => $workspaceIds->first(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'job_title' => $validated['job_title'],
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->workspaces()->sync(
            $workspaceIds->mapWithKeys(fn ($id) => [$id => ['job_title' => $validated['job_title']]])->all()
        );

        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->syncRoles([$role]);

        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'workspace_ids' => $workspaceIds->all(),
        ];

        $this->flash("User {$user->email} created.");
    }

    public function syncSource(int $sourceId): void
    {
        $source = SheetSource::query()->findOrFail($sourceId);

        $rows = app(SheetSourceSyncService::class)->sync($source);

        $this->flash("Source {$source->name} synced with {$rows} imported rows.");
    }

    public function syncWorkspaceSources(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $totalRows = 0;

        foreach ($workspace->sheetSources()->where('is_active', true)->get() as $source) {
            $totalRows += app(SheetSourceSyncService::class)->sync($source);
        }

        $this->flash("Workspace synced. Imported {$totalRows} rows.");
    }

    public function uploadLeadCsv(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->csvUploadForm, [
            'type' => ['required', Rule::in([
                SheetSource::TYPE_LEADS,
                SheetSource::TYPE_OPPORTUNITIES,
            ])],
        ])->validate();

        $this->validate([
            'leadCsvUpload' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $type = $validated['type'];
        $sourceLabel = $type === SheetSource::TYPE_OPPORTUNITIES ? 'Opportunities' : 'Leads';

        $source = SheetSource::firstOrCreate(
            [
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'type' => $type,
                'name' => "Uploaded {$sourceLabel} CSV",
            ],
            [
                'url' => 'upload://uploaded-leads-csv',
                'source_kind' => SheetSource::SOURCE_KIND_UPLOADED_CSV,
                'description' => "Manual CSV uploads for {$sourceLabel} imports",
                'is_active' => true,
                'sync_status' => 'idle',
            ],
        );

        $source->forceFill([
            'url' => 'upload://'.$this->leadCsvUpload->getClientOriginalName(),
            'source_kind' => SheetSource::SOURCE_KIND_UPLOADED_CSV,
        ])->save();

        $rows = app(SheetSourceSyncService::class)->importCsvForSource(
            $source,
            file_get_contents($this->leadCsvUpload->getRealPath()),
        );

        $this->reset('leadCsvUpload');
        $this->csvUploadForm['type'] = SheetSource::TYPE_LEADS;

        $targetLabel = $type === SheetSource::TYPE_OPPORTUNITIES ? 'opportunities' : 'leads';

        $this->flash("Uploaded CSV imported with {$rows} {$targetLabel}.");
    }

    public function startEditingWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::query()->with('company')->findOrFail($workspaceId);

        $this->workspaceId = $workspace->id;
        $this->primeForms($workspace);
        $this->primeWorkspaceEditor($workspace);
    }

    public function cancelEditingWorkspace(): void
    {
        $this->primeWorkspaceEditor($this->currentWorkspace());
    }

    public function updateWorkspace(): void
    {
        $workspace = Workspace::query()->findOrFail($this->editingWorkspaceId);

        $validated = validator($this->editingWorkspaceForm, [
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'settings_json' => ['nullable', 'json'],
        ])->validate();

        $companyId = (int) $validated['company_id'];
        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            Workspace::query()
                ->where('company_id', $companyId)
                ->whereKeyNot($workspace->id)
                ->update(['is_default' => false]);
        } elseif ($workspace->is_default) {
            $fallbackWorkspace = Workspace::query()
                ->where('company_id', $workspace->company_id)
                ->whereKeyNot($workspace->id)
                ->orderBy('name')
                ->first();

            $fallbackWorkspace?->forceFill(['is_default' => true])->save();
        }

        $workspace->fill([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug(
                Workspace::class,
                $validated['name'],
                ['company_id' => $companyId],
                $workspace->id,
            ),
            'description' => $validated['description'],
            'is_default' => $isDefault,
            'settings' => $this->decodeSettings($validated['settings_json'] ?? null),
        ])->save();

        $this->workspaceId = $workspace->id;
        $this->primeForms($workspace->fresh('company'));
        $this->primeWorkspaceEditor($workspace->fresh('company'));
        $this->primeBillingForm($workspace->fresh('company'));

        $this->flash("Workspace {$workspace->name} updated.");
    }

    public function deleteWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::query()->findOrFail($workspaceId);
        $companyId = $workspace->company_id;
        $name = $workspace->name;
        $wasCurrentWorkspace = (int) $this->workspaceId === $workspace->id;
        $wasDefault = $workspace->is_default;

        $workspace->delete();

        if ($wasDefault) {
            Workspace::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->first()?->forceFill(['is_default' => true])->save();
        }

        if ($wasCurrentWorkspace) {
            $nextWorkspace = Workspace::query()->with('company')->orderBy('name')->first();
            $this->workspaceId = $nextWorkspace?->id;
            $this->primeForms($nextWorkspace);
            $this->primeWorkspaceEditor($nextWorkspace);
            $this->primeBillingForm($nextWorkspace);
        }

        $this->flash("Workspace {$name} deleted.");
    }

    public function startEditingSource(int $sourceId): void
    {
        $source = SheetSource::query()->findOrFail($sourceId);

        $this->editingSourceId = $source->id;
        $this->editingSourceForm = [
            'workspace_id' => $source->workspace_id,
            'type' => $source->type,
            'name' => $source->name,
            'url' => $source->url,
            'source_kind' => $source->source_kind,
            'description' => $source->description ?? '',
            'is_active' => $source->is_active,
            ...$this->sourceConnectionFieldsFromSource($source),
        ];
    }

    public function cancelEditingSource(): void
    {
        $this->editingSourceId = null;
        $this->editingSourceForm = [];
    }

    public function updateSheetSource(): void
    {
        $source = SheetSource::query()->findOrFail($this->editingSourceId);

        $validated = validator($this->editingSourceForm, [
            'workspace_id' => ['nullable', 'exists:workspaces,id'],
            'type' => ['required', Rule::in(SheetSource::availableTypes())],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string'],
            'source_kind' => ['required', Rule::in(SheetSource::SOURCE_KINDS)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'cargo_auth_mode' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseAuthModes()))],
            'cargo_username' => ['nullable', 'string', 'max:255'],
            'cargo_password' => ['nullable', 'string', 'max:2048'],
            'cargo_token' => ['nullable', 'string', 'max:4096'],
            'cargo_format' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseFormats()))],
            'cargo_data_path' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $workspace = isset($validated['workspace_id'])
            ? Workspace::query()->find($validated['workspace_id'])
            : null;

        $preferApi = filled(data_get($source->mapping, 'spreadsheet_id'));
        $sourceKind = SheetSource::normalizeSourceKind(
            $validated['source_kind'],
            $validated['url'],
            $preferApi,
        );

        $source->fill([
            'company_id' => $workspace?->company_id ?? $source->company_id,
            'workspace_id' => $workspace?->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'url' => $validated['url'],
            'source_kind' => $sourceKind,
            'description' => $validated['description'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'mapping' => $this->sourceMappingFromForm($validated, $source),
        ])->save();

        $this->cancelEditingSource();

        $this->flash("Source {$source->name} updated.");
    }

    public function deleteSheetSource(int $sourceId): void
    {
        $source = SheetSource::query()->findOrFail($sourceId);
        $name = $source->name;

        $source->delete();

        if ($this->editingSourceId === $sourceId) {
            $this->cancelEditingSource();
        }

        $this->flash("Source {$name} deleted.");
    }

    public function render()
    {
        $workspaces = Workspace::query()
            ->with('company.googleAccount')
            ->orderBy('name')
            ->get();

        $workspace = $this->currentWorkspace();
        $this->workspaceId = $workspace?->id;
        $company = $workspace?->company;
        $googleOAuth = app(GoogleOAuthService::class);
        $billing = app(WorkspaceBillingService::class);

        $companies = Company::query()
            ->withCount('workspaces')
            ->with('googleAccount')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderByDesc('level')->get();
        $sheetSources = SheetSource::query()->whereRaw('1 = 0')->paginate(
            $this->sourcePerPage,
            ['*'],
            'sourcesPage',
        );
        $workspaceUsers = User::query()->whereRaw('1 = 0')->paginate(
            $this->workspaceUserPerPage,
            ['*'],
            'workspaceUsersPage',
        );
        $monthlyReports = collect();
        $latestReport = null;
        $sourceBreakdown = collect();
        $kpis = [];
        $currentBillingSummary = null;
        $billingRows = collect();

        if ($workspace) {
            $sheetSources = $this->applySourceSorting(
                $workspace->sheetSources()->getQuery()
            )->paginate($this->sourcePerPage, ['*'], 'sourcesPage');

            $workspaceUsers = $this->applyWorkspaceUserSorting(
                $workspace->users()->with('roles')
            )->paginate($this->workspaceUserPerPage, ['users.*'], 'workspaceUsersPage');

            $monthlyReports = $workspace->monthlyReports()->orderByDesc('month_start')->limit(6)->get();
            $latestReport = $monthlyReports->first();

            $leadBase = Lead::query()->where('workspace_id', $workspace->id);
            $opportunityBase = Opportunity::query()->where('workspace_id', $workspace->id);

            $kpis = [
                [
                    'label' => 'Total Leads',
                    'value' => (clone $leadBase)->count(),
                    'detail' => 'All imported and manual leads',
                ],
                [
                    'label' => 'Qualified Leads',
                    'value' => (clone $leadBase)->where('status', Lead::STATUS_SALES_QUALIFIED)->count(),
                    'detail' => 'Sales-ready pipeline',
                ],
                [
                    'label' => 'Open Opportunities',
                    'value' => (clone $opportunityBase)->count(),
                    'detail' => 'Tracked in the workspace',
                ],
                [
                    'label' => 'Won Revenue',
                    'value' => 'AED '.number_format((float) (clone $opportunityBase)->where('sales_stage', Opportunity::STAGE_CLOSED_WON)->sum('revenue_potential'), 0),
                    'detail' => 'Closed-won revenue',
                ],
            ];

            $sourceBreakdown = Lead::query()
                ->select('lead_source', DB::raw('count(*) as total'))
                ->where('workspace_id', $workspace->id)
                ->groupBy('lead_source')
                ->orderByDesc('total')
                ->get();

            $currentBillingSummary = $billing->summary($workspace);
        }

        $workspaceRows = $this->applyWorkspaceSorting(
            Workspace::query()
                ->with('company')
                ->withCount(['users', 'leads', 'opportunities'])
        )->paginate($this->workspacePerPage, ['*'], 'workspacesPage');

        $billingRows = $workspaceRows->getCollection()->map(function (Workspace $workspaceRow) use ($billing) {
            return [
                'workspace' => $workspaceRow,
                'summary' => $billing->summary($workspaceRow),
            ];
        });

        return view('livewire.admin-dashboard', [
            'billingPlans' => $billing->planCatalog(),
            'billingRows' => $billingRows,
            'companies' => $companies,
            'currentWorkspace' => $workspace,
            'currentBillingSummary' => $currentBillingSummary,
            'currentCompany' => $company,
            'googleAccount' => $company?->googleAccount,
            'googleHasClientConfig' => $googleOAuth->hasClientConfig($company),
            'kpis' => $kpis,
            'latestReport' => $latestReport,
            'monthlyReports' => $monthlyReports,
            'roles' => $roles,
            'sheetSources' => $sheetSources,
            'sourceBreakdown' => $sourceBreakdown,
            'workspaceRows' => $workspaceRows,
            'workspaceUsers' => $workspaceUsers,
            'workspaces' => $workspaces,
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
        ];

        $this->editingWorkspaceForm = [
            'company_id' => '',
            'name' => '',
            'description' => '',
            'is_default' => false,
            'settings_json' => '',
        ];

        $this->sourceForm = [
            'workspace_id' => '',
            'type' => SheetSource::TYPE_LEADS,
            'name' => '',
            'url' => '',
            'description' => '',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
            'is_active' => true,
            ...$this->defaultSourceConnectionFields(),
        ];

        $this->editingSourceForm = [
            'workspace_id' => '',
            'type' => SheetSource::TYPE_LEADS,
            'name' => '',
            'url' => '',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
            'description' => '',
            'is_active' => true,
            ...$this->defaultSourceConnectionFields(),
        ];

        $this->csvUploadForm = [
            'type' => SheetSource::TYPE_LEADS,
        ];

        $this->googleOauthForm = [
            'client_id' => '',
            'client_secret' => '',
        ];

        $this->googleSourceForm = [
            'type' => SheetSource::TYPE_LEADS,
            'spreadsheet_id' => '',
            'sheet_gid' => '',
            'sheet_title' => '',
            'name' => '',
            'description' => '',
        ];

        $this->googleSpreadsheets = [];
        $this->googleSheetTabs = [];

        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'workspace_ids' => [],
        ];

        $this->roleForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'level' => 3,
        ];

        $this->billingForm = [
            'plan_key' => app(WorkspaceBillingService::class)->defaultPlanKey(),
            'included_users' => null,
            'included_operational_records' => null,
        ];
    }

    protected function defaultSourceConnectionFields(): array
    {
        return [
            'cargo_auth_mode' => 'basic',
            'cargo_username' => '',
            'cargo_password' => '',
            'cargo_token' => '',
            'cargo_format' => 'json',
            'cargo_data_path' => '',
        ];
    }

    protected function sourceConnectionFieldsFromSource(SheetSource $source): array
    {
        return [
            'cargo_auth_mode' => data_get($source->mapping, 'cargowise.auth_mode', 'basic'),
            'cargo_username' => data_get($source->mapping, 'cargowise.username', ''),
            'cargo_password' => data_get($source->mapping, 'cargowise.password', ''),
            'cargo_token' => data_get($source->mapping, 'cargowise.token', ''),
            'cargo_format' => data_get($source->mapping, 'cargowise.format', 'json'),
            'cargo_data_path' => data_get($source->mapping, 'cargowise.data_path', ''),
        ];
    }

    protected function sourceMappingFromForm(array $validated, ?SheetSource $source = null): ?array
    {
        $mapping = $source?->mapping ?? [];

        if (($validated['source_kind'] ?? null) !== SheetSource::SOURCE_KIND_CARGOWISE_API) {
            unset($mapping['cargowise']);

            return $mapping === [] ? null : $mapping;
        }

        $mapping['cargowise'] = [
            'endpoint' => $validated['url'],
            'auth_mode' => $validated['cargo_auth_mode'] ?: 'basic',
            'username' => $validated['cargo_username'] ?: '',
            'password' => $validated['cargo_password'] ?: '',
            'token' => $validated['cargo_token'] ?: '',
            'format' => $validated['cargo_format'] ?: 'json',
            'data_path' => $validated['cargo_data_path'] ?: '',
        ];

        return $mapping;
    }

    protected function primeForms(?Workspace $workspace): void
    {
        if (! $workspace) {
            $this->primeGoogleForms(null);

            return;
        }

        $this->workspaceForm['company_id'] = $workspace->company_id;
        $this->sourceForm['workspace_id'] = $workspace->id;
        $this->userForm['workspace_ids'] = [$workspace->id];
        $this->primeGoogleForms($workspace);
    }

    protected function primeWorkspaceEditor(?Workspace $workspace): void
    {
        if (! $workspace) {
            $this->editingWorkspaceId = null;
            $this->editingWorkspaceForm = [
                'company_id' => '',
                'name' => '',
                'description' => '',
                'is_default' => false,
                'settings_json' => '',
            ];

            return;
        }

        $this->editingWorkspaceId = $workspace->id;
        $this->editingWorkspaceForm = [
            'company_id' => $workspace->company_id,
            'name' => $workspace->name,
            'description' => $workspace->description ?? '',
            'is_default' => $workspace->is_default,
            'settings_json' => $workspace->settings ? json_encode($workspace->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
        ];
    }

    protected function primeBillingForm(?Workspace $workspace): void
    {
        $billing = app(WorkspaceBillingService::class);

        if (! $workspace) {
            $this->billingForm = [
                'plan_key' => $billing->defaultPlanKey(),
                'included_users' => null,
                'included_operational_records' => null,
            ];

            return;
        }

        $this->billingForm = [
            'plan_key' => $billing->resolvePlanKey($workspace),
            'included_users' => data_get($workspace->settings, WorkspaceBillingService::BILLING_KEY.'.included_users'),
            'included_operational_records' => data_get($workspace->settings, WorkspaceBillingService::BILLING_KEY.'.included_operational_records'),
        ];
    }

    protected function currentWorkspace(): ?Workspace
    {
        $workspaces = Workspace::query()->with('company.googleAccount')->orderBy('name')->get();

        if ($workspaces->isEmpty()) {
            return null;
        }

        return $workspaces->firstWhere('id', (int) $this->workspaceId)
            ?? $workspaces->first();
    }

    protected function currentWorkspaceOrFail(): Workspace
    {
        $workspace = $this->currentWorkspace();

        abort_if(! $workspace, 404);

        return $workspace;
    }

    protected function currentCompanyOrFail(): Company
    {
        $workspace = $this->currentWorkspaceOrFail();

        return $workspace->company;
    }

    protected function uniqueSlug(string $modelClass, string $value, array $extraWhere = [], ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $iteration = 2;

        while ($modelClass::query()
            ->where($extraWhere)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$iteration}";
            $iteration++;
        }

        return $slug;
    }

    protected function decodeSettings(?string $settingsJson): ?array
    {
        if (! $settingsJson) {
            return null;
        }

        return json_decode($settingsJson, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function primeGoogleForms(?Workspace $workspace): void
    {
        $company = $workspace?->company;
        $googleOAuth = app(GoogleOAuthService::class);

        if (! $company) {
            $this->googleOauthForm = [
                'client_id' => '',
                'client_secret' => '',
            ];
            $this->googleSourceForm = [
                'type' => SheetSource::TYPE_LEADS,
                'spreadsheet_id' => '',
                'sheet_gid' => '',
                'sheet_title' => '',
                'name' => '',
                'description' => '',
            ];
            $this->googleSpreadsheets = [];
            $this->googleSheetTabs = [];

            return;
        }

        $clientConfig = $googleOAuth->clientConfig($company);

        $this->googleOauthForm = [
            'client_id' => $clientConfig['client_id'],
            'client_secret' => $clientConfig['client_secret'],
        ];

        $this->googleSourceForm = [
            'type' => $this->googleSourceForm['type'] ?? SheetSource::TYPE_LEADS,
            'spreadsheet_id' => '',
            'sheet_gid' => '',
            'sheet_title' => '',
            'name' => '',
            'description' => '',
        ];

        $this->googleSpreadsheets = [];
        $this->googleSheetTabs = [];
    }

    protected function defaultGoogleSourceName(string $type, string $sheetTitle): string
    {
        return match ($type) {
            SheetSource::TYPE_OPPORTUNITIES => "{$sheetTitle} Opportunities",
            SheetSource::TYPE_REPORTS => "{$sheetTitle} Reports",
            default => "{$sheetTitle} Leads",
        };
    }

    protected function defaultStatusColumns(string $type): array
    {
        return match ($type) {
            SheetSource::TYPE_OPPORTUNITIES => ['Sales Stage'],
            SheetSource::TYPE_LEADS => ['Lead Status'],
            default => [],
        };
    }

    protected function googleSheetUrl(string $spreadsheetId, int $sheetGid): string
    {
        return "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid={$sheetGid}";
    }

    protected function applySourceSorting($query)
    {
        return match ($this->sourceSort) {
            'oldest' => $query->orderBy('created_at'),
            'name_asc' => $query->orderBy('name')->orderByDesc('created_at'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('created_at'),
            'synced_desc' => $query->orderByDesc('last_synced_at')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    protected function applyWorkspaceUserSorting($query)
    {
        return match ($this->workspaceUserSort) {
            'oldest' => $query->orderBy('users.created_at'),
            'name_asc' => $query->orderBy('users.name')->orderByDesc('users.created_at'),
            'name_desc' => $query->orderByDesc('users.name')->orderByDesc('users.created_at'),
            default => $query->orderByDesc('users.created_at'),
        };
    }

    protected function applyWorkspaceSorting($query)
    {
        return match ($this->workspaceSort) {
            'oldest' => $query->orderBy('created_at'),
            'name_asc' => $query->orderBy('name')->orderByDesc('created_at'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    protected function flash(string $message): void
    {
        session()->flash('status', $message);
    }
}
