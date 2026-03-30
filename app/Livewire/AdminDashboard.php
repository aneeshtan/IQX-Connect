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

    public ?int $editingUserId = null;

    public ?int $pendingWorkspaceDeleteId = null;

    public string $activeTab = 'analytics';

    public string $sourceSort = 'newest';

    public string $sourceSearch = '';

    public string $sourceStatusFilter = 'all';

    public string $sourceKindFilter = 'all';

    public string $workspaceUserSort = 'newest';

    public string $workspaceSort = 'newest';

    public string $workspaceSearch = '';

    public string $companySearch = '';

    public string $userSearch = '';

    public string $userStatusFilter = 'all';

    public string $userRoleFilter = 'all';

    public string $billingSearch = '';

    public string $billingPlanFilter = 'all';

    public string $billingStatusFilter = 'all';

    public string $workspaceDeleteConfirmation = '';

    public bool $workspaceDeleteAcknowledged = false;

    public int $sourcePerPage = 10;

    public int $workspaceUserPerPage = 10;

    public int $workspacePerPage = 10;

    public array $companyForm = [];

    public array $workspaceForm = [];

    public array $editingWorkspaceForm = [];

    public array $sourceForm = [];

    public array $editingSourceForm = [];

    public array $editingUserForm = [];

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

    public function updatedSourceSearch(): void
    {
        $this->resetPage('sourcesPage');
    }

    public function updatedSourceStatusFilter(): void
    {
        $this->resetPage('sourcesPage');
    }

    public function updatedSourceKindFilter(): void
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

    public function updatedUserSearch(): void
    {
        $this->resetPage('workspaceUsersPage');
    }

    public function updatedUserStatusFilter(): void
    {
        $this->resetPage('workspaceUsersPage');
    }

    public function updatedUserRoleFilter(): void
    {
        $this->resetPage('workspaceUsersPage');
    }

    public function updatedBillingSearch(): void
    {
        $this->resetPage('billingPage');
    }

    public function updatedBillingPlanFilter(): void
    {
        $this->resetPage('billingPage');
    }

    public function updatedBillingStatusFilter(): void
    {
        $this->resetPage('billingPage');
    }

    public function updatedWorkspaceSort(): void
    {
        $this->resetPage('workspacesPage');
    }

    public function updatedWorkspaceSearch(): void
    {
        $this->resetPage('workspacesPage');
    }

    public function updatedCompanySearch(): void
    {
        // Company directory is collection-based, so no paginator reset is needed.
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

        $this->workspaceId = $workspace->id;
        $this->workspaceForm['name'] = '';
        $this->workspaceForm['description'] = '';
        $this->primeForms($workspace);
        $this->workspaceForm['template_key'] = Workspace::defaultTemplateKey();
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

    public function startEditingUser(int $userId): void
    {
        $user = User::query()
            ->with(['roles', 'workspaces'])
            ->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->editingUserForm = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'job_title' => $user->job_title ?? '',
            'role' => $user->roles->pluck('slug')->first() ?? 'sales',
            'workspace_ids' => $user->workspaces->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'default_workspace_id' => $user->default_workspace_id,
            'is_active' => (bool) $user->is_active,
        ];
    }

    public function cancelEditingUser(): void
    {
        $this->editingUserId = null;
        $this->editingUserForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'workspace_ids' => [],
            'default_workspace_id' => '',
            'is_active' => true,
        ];
    }

    public function updateUser(): void
    {
        $user = User::query()->findOrFail($this->editingUserId);

        $validated = validator($this->editingUserForm, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('slug')->all())],
            'workspace_ids' => ['required', 'array', 'min:1'],
            'workspace_ids.*' => ['integer', 'exists:workspaces,id'],
            'default_workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'is_active' => ['boolean'],
        ])->validate();

        $workspaceIds = collect($validated['workspace_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_unless($workspaceIds->contains((int) $validated['default_workspace_id']), 422, 'Default workspace must be one of the assigned workspaces.');

        $companyId = Workspace::query()
            ->whereKey((int) $validated['default_workspace_id'])
            ->value('company_id');

        $user->fill([
            'company_id' => $companyId,
            'default_workspace_id' => (int) $validated['default_workspace_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        if (filled($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->workspaces()->sync(
            $workspaceIds->mapWithKeys(fn ($id) => [$id => ['job_title' => $validated['job_title']]])->all()
        );

        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->syncRoles([$role]);

        $this->cancelEditingUser();

        $this->flash("User {$user->email} updated.");
    }

    public function toggleUserActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $user->forceFill([
            'is_active' => ! $user->is_active,
        ])->save();

        $this->flash("User {$user->email} ".($user->is_active ? 'activated' : 'paused').'.');
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

    public function requestWorkspaceDeletion(int $workspaceId): void
    {
        Workspace::query()->findOrFail($workspaceId);

        $this->pendingWorkspaceDeleteId = $workspaceId;
        $this->workspaceDeleteConfirmation = '';
        $this->workspaceDeleteAcknowledged = false;
    }

    public function cancelWorkspaceDeletion(): void
    {
        $this->pendingWorkspaceDeleteId = null;
        $this->workspaceDeleteConfirmation = '';
        $this->workspaceDeleteAcknowledged = false;
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

    public function confirmWorkspaceDeletion(): void
    {
        $workspace = Workspace::query()->findOrFail($this->pendingWorkspaceDeleteId);

        validator([
            'workspaceDeleteConfirmation' => $this->workspaceDeleteConfirmation,
            'workspaceDeleteAcknowledged' => $this->workspaceDeleteAcknowledged,
        ], [
            'workspaceDeleteConfirmation' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($workspace) {
                    if ((string) $value !== $workspace->name) {
                        $fail('Type the exact workspace name to confirm deletion.');
                    }
                },
            ],
            'workspaceDeleteAcknowledged' => [
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== true) {
                        $fail('Confirm that this will remove all linked workspace data.');
                    }
                },
            ],
        ])->validate();

        $this->deleteWorkspace($workspace);
    }

    protected function deleteWorkspace(Workspace $workspace): void
    {
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

        $this->cancelWorkspaceDeletion();

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

        $overviewWorkspaces = Workspace::query()
            ->with('company')
            ->withCount([
                'users',
                'sheetSources as active_sources_count' => fn ($query) => $query->where('is_active', true),
                'shipmentJobs',
                'projects',
                'bookings',
                'quotes',
                'opportunities as won_opportunities_count' => fn ($query) => $query->where('sales_stage', Opportunity::STAGE_CLOSED_WON),
            ])
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
        $workspaceUsers = $this->applyWorkspaceUserSorting(
            $this->applyUserFilters(
                User::query()
                    ->with(['company', 'defaultWorkspace', 'roles', 'workspaces.company'])
                    ->withCount('workspaces')
            )
        )->paginate($this->workspaceUserPerPage, ['users.*'], 'workspaceUsersPage');
        $monthlyReports = collect();
        $latestReport = null;
        $sourceBreakdown = collect();
        $userOverviewStats = [];
        $roleDistributionRows = collect();
        $userAttentionRows = collect();
        $billingOverviewStats = [];
        $billingDirectoryRows = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        $sourceOverviewStats = [];
        $sourceKindRows = collect();
        $sourceAttentionRows = collect();
        $overviewStats = [];
        $overviewSignals = [];
        $subscriptionRows = collect();
        $workspaceModeRows = collect();
        $attentionRows = collect();
        $growthSeries = collect();
        $growthMax = 1;
        $currentBillingSummary = null;
        $billingRows = collect();

        $overviewBillingRows = $overviewWorkspaces->map(function (Workspace $workspaceRow) use ($billing) {
            return [
                'workspace' => $workspaceRow,
                'summary' => $this->workspaceBillingSnapshot($workspaceRow, $billing),
            ];
        });

        $billingOverviewStats = [
            [
                'label' => 'Billable Workspaces',
                'value' => number_format($overviewBillingRows->count()),
                'detail' => 'All workspaces with a resolved subscription plan',
            ],
            [
                'label' => 'Paid Plans',
                'value' => number_format($overviewBillingRows->filter(fn (array $row) => ($row['summary']['workspace_price_monthly'] ?? 0) > 0)->count()),
                'detail' => 'Growth and Professional subscriptions',
            ],
            [
                'label' => 'Over Limit',
                'value' => number_format($overviewBillingRows->filter(fn (array $row) => $row['summary']['users_over_limit'] || $row['summary']['operational_over_limit'])->count()),
                'detail' => 'Workspaces that need a plan or limit review',
            ],
            [
                'label' => 'Estimated MRR',
                'value' => '$'.number_format((int) $overviewBillingRows->sum(fn (array $row) => $row['summary']['workspace_price_monthly'] ?? 0), 0),
                'detail' => 'Based on configured workspace plans',
            ],
        ];

        $filteredBillingRows = $this->applyBillingFilters($overviewBillingRows);
        $billingPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage('billingPage');
        $billingPerPage = 10;
        $billingDirectoryRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredBillingRows->forPage($billingPage, $billingPerPage)->values(),
            $filteredBillingRows->count(),
            $billingPerPage,
            $billingPage,
            [
                'pageName' => 'billingPage',
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $totalUsers = User::query()->count();
        $totalCompanies = $companies->count();
        $totalWorkspaces = $overviewWorkspaces->count();
        $activeSources = (int) $overviewWorkspaces->sum('active_sources_count');
        $googleConnectedCompanies = $companies->filter(fn (Company $companyRow) => $companyRow->googleAccount !== null)->count();
        $workspacesWithoutUsers = $overviewBillingRows->filter(fn (array $row) => $row['summary']['current_users'] === 0)->count();
        $workspacesOverLimit = $overviewBillingRows->filter(fn (array $row) => $row['summary']['users_over_limit'] || $row['summary']['operational_over_limit'])->count();
        $paidSubscriptions = $overviewBillingRows->filter(fn (array $row) => $row['summary']['workspace_price_monthly'] !== null && $row['summary']['workspace_price_monthly'] > 0)->count();
        $estimatedMrr = (int) $overviewBillingRows->sum(fn (array $row) => $row['summary']['workspace_price_monthly'] ?? 0);
        $newUsersLast30Days = User::query()->where('created_at', '>=', now()->subDays(30))->count();
        $newWorkspacesLast30Days = Workspace::query()->where('created_at', '>=', now()->subDays(30))->count();
        $newCompaniesLast30Days = Company::query()->where('created_at', '>=', now()->subDays(30))->count();

        $overviewStats = [
            [
                'label' => 'Total Users',
                'value' => number_format($totalUsers),
                'detail' => $newUsersLast30Days.' added in the last 30 days',
            ],
            [
                'label' => 'Total Workspaces',
                'value' => number_format($totalWorkspaces),
                'detail' => $newWorkspacesLast30Days.' new workspaces launched this month',
            ],
            [
                'label' => 'Total Companies',
                'value' => number_format($totalCompanies),
                'detail' => $newCompaniesLast30Days.' companies onboarded in the last 30 days',
            ],
            [
                'label' => 'Estimated MRR',
                'value' => '$'.number_format($estimatedMrr, 0),
                'detail' => $paidSubscriptions.' paid workspace subscriptions',
            ],
        ];

        $overviewSignals = [
            [
                'label' => 'Subscriptions Over Limit',
                'value' => number_format($workspacesOverLimit),
                'detail' => 'Workspaces exceeding seats or operational usage',
            ],
            [
                'label' => 'Workspaces Without Users',
                'value' => number_format($workspacesWithoutUsers),
                'detail' => 'Created but not staffed yet',
            ],
            [
                'label' => 'Connected Google Accounts',
                'value' => number_format($googleConnectedCompanies),
                'detail' => 'Companies with Google auth configured',
            ],
            [
                'label' => 'Active Data Sources',
                'value' => number_format($activeSources),
                'detail' => 'Live sync sources across the platform',
            ],
        ];

        $subscriptionRows = collect($billing->planCatalog())
            ->map(function (array $plan, string $planKey) use ($overviewBillingRows, $totalWorkspaces) {
                $count = $overviewBillingRows->filter(fn (array $row) => $row['summary']['plan_key'] === $planKey)->count();
                $priceMonthly = $plan['workspace_price_monthly'] ?? null;

                return [
                    'key' => $planKey,
                    'name' => $plan['name'] ?? ucfirst($planKey),
                    'price_label' => $plan['price_label'] ?? 'Custom',
                    'count' => $count,
                    'share' => $totalWorkspaces > 0 ? (int) round(($count / $totalWorkspaces) * 100) : 0,
                    'estimated_mrr' => $priceMonthly !== null ? $count * (int) $priceMonthly : null,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $workspaceModeRows = $overviewWorkspaces
            ->groupBy(fn (Workspace $workspaceRow) => $workspaceRow->templateKey())
            ->map(function ($group, string $templateKey) use ($totalWorkspaces) {
                $firstWorkspace = $group->first();
                $count = $group->count();

                return [
                    'key' => $templateKey,
                    'name' => $firstWorkspace?->templateName() ?? Str::headline($templateKey),
                    'description' => $firstWorkspace?->templateDescription() ?? '',
                    'count' => $count,
                    'share' => $totalWorkspaces > 0 ? (int) round(($count / $totalWorkspaces) * 100) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $attentionRows = $overviewBillingRows
            ->map(function (array $row) {
                /** @var Workspace $workspaceRow */
                $workspaceRow = $row['workspace'];
                $summary = $row['summary'];
                $reasons = [];

                if ($summary['users_over_limit']) {
                    $reasons[] = 'User seats over plan';
                }

                if ($summary['operational_over_limit']) {
                    $reasons[] = $summary['usage_metric_label'].' over plan';
                }

                if ($summary['current_users'] === 0) {
                    $reasons[] = 'No assigned users';
                }

                if ((int) ($workspaceRow->active_sources_count ?? 0) === 0) {
                    $reasons[] = 'No active data sources';
                }

                if ($reasons === []) {
                    return null;
                }

                return [
                    'workspace_name' => $workspaceRow->name,
                    'company_name' => $workspaceRow->company?->name ?? 'No company',
                    'reasons' => $reasons,
                    'score' => count($reasons),
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take(6)
            ->values();

        $growthSeries = collect(range(5, 0))
            ->map(function (int $monthsAgo) {
                $start = now()->startOfMonth()->subMonths($monthsAgo);
                $end = (clone $start)->endOfMonth();

                return [
                    'label' => $start->format('M'),
                    'users' => User::query()->whereBetween('created_at', [$start, $end])->count(),
                    'workspaces' => Workspace::query()->whereBetween('created_at', [$start, $end])->count(),
                    'companies' => Company::query()->whereBetween('created_at', [$start, $end])->count(),
                ];
            })
            ->values();

        $growthMax = max(
            1,
            (int) $growthSeries
                ->flatMap(fn (array $point) => [$point['users'], $point['workspaces'], $point['companies']])
                ->max()
        );

        $allSheetSources = SheetSource::query()
            ->with(['company', 'workspace'])
            ->get();

        $sheetSources = $this->applySourceSorting(
            $this->applySourceFilters(
                SheetSource::query()->with(['company', 'workspace'])
            )
        )->paginate($this->sourcePerPage, ['*'], 'sourcesPage');

        $sourceOverviewStats = [
            [
                'label' => 'Total Sources',
                'value' => number_format($allSheetSources->count()),
                'detail' => 'All configured sync and import connections',
            ],
            [
                'label' => 'Active Sources',
                'value' => number_format($allSheetSources->where('is_active', true)->count()),
                'detail' => 'Currently enabled for sync or upload',
            ],
            [
                'label' => 'Failed Syncs',
                'value' => number_format($allSheetSources->where('sync_status', 'failed')->count()),
                'detail' => 'Sources that need support or troubleshooting',
            ],
            [
                'label' => 'Companies Covered',
                'value' => number_format($allSheetSources->pluck('company_id')->filter()->unique()->count()),
                'detail' => 'Companies with at least one configured source',
            ],
        ];

        $sourceKindRows = collect(SheetSource::SOURCE_KINDS)
            ->map(function (string $kind) use ($allSheetSources) {
                $count = $allSheetSources->where('source_kind', $kind)->count();
                $total = max(1, $allSheetSources->count());

                return [
                    'key' => $kind,
                    'label' => SheetSource::sourceKindLabel($kind),
                    'count' => $count,
                    'share' => (int) round(($count / $total) * 100),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $sourceAttentionRows = $allSheetSources
            ->map(function (SheetSource $source) {
                $reasons = [];

                if (! $source->is_active) {
                    $reasons[] = 'Paused';
                }

                if ($source->sync_status === 'failed') {
                    $reasons[] = 'Latest sync failed';
                }

                if ($source->last_synced_at === null) {
                    $reasons[] = 'Never synced';
                }

                if (filled($source->last_error)) {
                    $reasons[] = 'Error recorded';
                }

                if ($source->workspace === null) {
                    $reasons[] = 'No workspace assigned';
                }

                if ($reasons === []) {
                    return null;
                }

                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'company_name' => $source->company?->name ?? 'No company',
                    'workspace_name' => $source->workspace?->name ?? 'No workspace',
                    'reasons' => $reasons,
                    'score' => count($reasons),
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take(6)
            ->values();

        $allUsers = User::query()
            ->with(['company', 'defaultWorkspace', 'roles', 'workspaces.company'])
            ->withCount('workspaces')
            ->get();

        $userOverviewStats = [
            [
                'label' => 'Total Users',
                'value' => number_format($allUsers->count()),
                'detail' => 'Every user across all companies and workspaces',
            ],
            [
                'label' => 'Active Users',
                'value' => number_format($allUsers->where('is_active', true)->count()),
                'detail' => 'Currently active accounts',
            ],
            [
                'label' => 'Admin Users',
                'value' => number_format($allUsers->filter(fn (User $userRow) => $userRow->roles->pluck('slug')->contains('admin'))->count()),
                'detail' => 'Users with platform admin access',
            ],
            [
                'label' => 'Multi-Workspace Users',
                'value' => number_format($allUsers->filter(fn (User $userRow) => (int) $userRow->workspaces_count > 1)->count()),
                'detail' => 'Users assigned to more than one workspace',
            ],
        ];

        $roleDistributionRows = $roles
            ->map(function ($role) use ($allUsers) {
                $count = $allUsers->filter(fn (User $userRow) => $userRow->roles->pluck('slug')->contains($role->slug))->count();
                $total = max(1, $allUsers->count());

                return [
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'count' => $count,
                    'share' => (int) round(($count / $total) * 100),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $userAttentionRows = $allUsers
            ->map(function (User $userRow) {
                $reasons = [];

                if (! $userRow->is_active) {
                    $reasons[] = 'Inactive account';
                }

                if ($userRow->workspaces->isEmpty()) {
                    $reasons[] = 'No workspace access';
                }

                if (! $userRow->email_verified_at) {
                    $reasons[] = 'Email not verified';
                }

                if (! $userRow->default_workspace_id) {
                    $reasons[] = 'No default workspace';
                }

                if ($reasons === []) {
                    return null;
                }

                return [
                    'id' => $userRow->id,
                    'name' => $userRow->name,
                    'email' => $userRow->email,
                    'reasons' => $reasons,
                    'score' => count($reasons),
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take(6)
            ->values();

        if ($workspace) {
            $monthlyReports = $workspace->monthlyReports()->orderByDesc('month_start')->limit(6)->get();
            $latestReport = $monthlyReports->first();

            $leadBase = Lead::query()->where('workspace_id', $workspace->id);
            $opportunityBase = Opportunity::query()->where('workspace_id', $workspace->id);

            $sourceBreakdown = Lead::query()
                ->select('lead_source', DB::raw('count(*) as total'))
                ->where('workspace_id', $workspace->id)
                ->groupBy('lead_source')
                ->orderByDesc('total')
                ->get();

            $currentBillingSummary = $billing->summary($workspace);
        }

        $workspaceRows = $this->applyWorkspaceSorting(
            $this->applyWorkspaceSearch(
                Workspace::query()
                    ->with('company')
                    ->withCount(['users', 'leads', 'opportunities'])
            )
        )->paginate($this->workspacePerPage, ['*'], 'workspacesPage');

        $companyDirectoryRows = $this->applyCompanySearch($companies);

        $billingRows = $workspaceRows->getCollection()->map(function (Workspace $workspaceRow) use ($billing) {
            return [
                'workspace' => $workspaceRow,
                'summary' => $billing->summary($workspaceRow),
            ];
        });

        return view('livewire.admin-dashboard', [
            'billingDirectoryRows' => $billingDirectoryRows,
            'billingOverviewStats' => $billingOverviewStats,
            'billingPlans' => $billing->planCatalog(),
            'billingRows' => $billingRows,
            'companies' => $companies,
            'companyDirectoryRows' => $companyDirectoryRows,
            'currentWorkspace' => $workspace,
            'currentBillingSummary' => $currentBillingSummary,
            'currentCompany' => $company,
            'googleAccount' => $company?->googleAccount,
            'googleHasClientConfig' => $googleOAuth->hasClientConfig($company),
            'growthMax' => $growthMax,
            'growthSeries' => $growthSeries,
            'latestReport' => $latestReport,
            'monthlyReports' => $monthlyReports,
            'overviewSignals' => $overviewSignals,
            'overviewStats' => $overviewStats,
            'roles' => $roles,
            'sheetSources' => $sheetSources,
            'sourceAttentionRows' => $sourceAttentionRows,
            'sourceKindRows' => $sourceKindRows,
            'sourceOverviewStats' => $sourceOverviewStats,
            'sourceBreakdown' => $sourceBreakdown,
            'subscriptionRows' => $subscriptionRows,
            'attentionRows' => $attentionRows,
            'userAttentionRows' => $userAttentionRows,
            'userOverviewStats' => $userOverviewStats,
            'roleDistributionRows' => $roleDistributionRows,
            'workspaceTemplates' => Workspace::workspaceTemplates(),
            'workspaceModeRows' => $workspaceModeRows,
            'workspaceRows' => $workspaceRows,
            'workspaceUsers' => $workspaceUsers,
            'workspaces' => $workspaces,
        ]);
    }

    protected function workspaceBillingSnapshot(Workspace $workspace, WorkspaceBillingService $billing): array
    {
        $planKey = $billing->resolvePlanKey($workspace);
        $plan = $billing->planDefinition($planKey);
        $metric = $billing->usageMetricDefinition($workspace);
        $currentUsers = (int) ($workspace->users_count ?? 0);
        $includedUsers = $billing->includedUsers($workspace);
        $includedOperationalRecords = $billing->includedOperationalRecords($workspace);
        $currentOperationalRecords = $this->workspaceOperationalCountFromCounts($workspace, $metric['key'] ?? null);

        return [
            'plan_key' => $planKey,
            'plan_name' => $plan['name'] ?? ucfirst($planKey),
            'price_label' => $plan['price_label'] ?? 'Custom',
            'workspace_price_monthly' => $plan['workspace_price_monthly'] ?? null,
            'included_users' => $includedUsers,
            'included_operational_records' => $includedOperationalRecords,
            'current_users' => $currentUsers,
            'current_operational_records' => $currentOperationalRecords,
            'usage_metric_label' => $metric['label'] ?? 'Operational records',
            'users_over_limit' => $includedUsers !== null && $currentUsers > $includedUsers,
            'operational_over_limit' => $includedOperationalRecords !== null && $currentOperationalRecords > $includedOperationalRecords,
        ];
    }

    protected function workspaceOperationalCountFromCounts(Workspace $workspace, ?string $metricKey): int
    {
        return match ($metricKey) {
            'shipment_jobs' => (int) ($workspace->shipment_jobs_count ?? 0),
            'projects' => (int) ($workspace->projects_count ?? 0),
            'bookings' => (int) ($workspace->bookings_count ?? 0),
            'quotes' => (int) ($workspace->quotes_count ?? 0),
            'won_opportunities' => (int) ($workspace->won_opportunities_count ?? 0),
            default => (int) ($workspace->won_opportunities_count ?? 0),
        };
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

        $this->editingUserForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'workspace_ids' => [],
            'default_workspace_id' => '',
            'is_active' => true,
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
            'company_asc' => $query->join('companies', 'companies.id', '=', 'sheet_sources.company_id')
                ->select('sheet_sources.*')
                ->orderBy('companies.name')
                ->orderBy('sheet_sources.name'),
            'status_desc' => $query->orderByRaw("case when sync_status = 'failed' then 0 when sync_status = 'syncing' then 1 when sync_status = 'idle' then 2 else 3 end")
                ->orderByDesc('created_at'),
            'synced_desc' => $query->orderByDesc('last_synced_at')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    protected function applySourceFilters($query)
    {
        $search = trim($this->sourceSearch);

        if ($this->sourceStatusFilter !== 'all') {
            $query = match ($this->sourceStatusFilter) {
                'active' => $query->where('is_active', true),
                'paused' => $query->where('is_active', false),
                'failed' => $query->where('sync_status', 'failed'),
                'synced' => $query->where('sync_status', 'synced'),
                'never_synced' => $query->whereNull('last_synced_at'),
                'attention' => $query->where(function ($statusQuery) {
                    $statusQuery
                        ->where('sync_status', 'failed')
                        ->orWhereNotNull('last_error')
                        ->orWhere('is_active', false)
                        ->orWhereNull('last_synced_at');
                }),
                default => $query,
            };
        }

        if ($this->sourceKindFilter !== 'all') {
            $query->where('source_kind', $this->sourceKindFilter);
        }

        if ($search === '') {
            return $query;
        }

        $matchingTypes = collect(SheetSource::availableTypes())
            ->filter(fn (string $type) => Str::contains(Str::lower($type.' '.SheetSource::typeLabel($type)), Str::lower($search)))
            ->values();

        $matchingKinds = collect(SheetSource::SOURCE_KINDS)
            ->filter(fn (string $kind) => Str::contains(Str::lower($kind.' '.SheetSource::sourceKindLabel($kind)), Str::lower($search)))
            ->values();

        return $query->where(function ($sourceQuery) use ($search, $matchingTypes, $matchingKinds) {
            $sourceQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('url', 'like', "%{$search}%")
                ->orWhere('sync_status', 'like', "%{$search}%")
                ->orWhereHas('company', function ($companyQuery) use ($search) {
                    $companyQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('industry', 'like', "%{$search}%");
                })
                ->orWhereHas('workspace', function ($workspaceQuery) use ($search) {
                    $workspaceQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });

            if ($matchingTypes->isNotEmpty()) {
                $sourceQuery->orWhereIn('type', $matchingTypes->all());
            }

            if ($matchingKinds->isNotEmpty()) {
                $sourceQuery->orWhereIn('source_kind', $matchingKinds->all());
            }
        });
    }

    protected function applyWorkspaceUserSorting($query)
    {
        return match ($this->workspaceUserSort) {
            'oldest' => $query->orderBy('users.created_at'),
            'name_asc' => $query->orderBy('users.name')->orderByDesc('users.created_at'),
            'name_desc' => $query->orderByDesc('users.name')->orderByDesc('users.created_at'),
            'company_asc' => $query->leftJoin('companies', 'companies.id', '=', 'users.company_id')
                ->select('users.*')
                ->orderBy('companies.name')
                ->orderBy('users.name'),
            default => $query->orderByDesc('users.created_at'),
        };
    }

    protected function applyUserFilters($query)
    {
        $search = trim($this->userSearch);

        if ($this->userStatusFilter !== 'all') {
            $query = match ($this->userStatusFilter) {
                'active' => $query->where('users.is_active', true),
                'inactive' => $query->where('users.is_active', false),
                'admins' => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', 'admin')),
                'unverified' => $query->whereNull('users.email_verified_at'),
                'multi_workspace' => $query->has('workspaces', '>', 1),
                default => $query,
            };
        }

        if ($this->userRoleFilter !== 'all') {
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', $this->userRoleFilter));
        }

        if ($search === '') {
            return $query;
        }

        return $query->where(function ($userQuery) use ($search) {
            $userQuery
                ->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%")
                ->orWhere('users.job_title', 'like', "%{$search}%")
                ->orWhereHas('company', function ($companyQuery) use ($search) {
                    $companyQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('defaultWorkspace', function ($workspaceQuery) use ($search) {
                    $workspaceQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('roles', function ($roleQuery) use ($search) {
                    $roleQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })
                ->orWhereHas('workspaces', function ($workspaceQuery) use ($search) {
                    $workspaceQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    protected function applyBillingFilters(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        $search = Str::lower(trim($this->billingSearch));

        return $rows
            ->filter(function (array $row) use ($search) {
                /** @var Workspace $workspaceRow */
                $workspaceRow = $row['workspace'];
                $summary = $row['summary'];

                if ($this->billingPlanFilter !== 'all' && $summary['plan_key'] !== $this->billingPlanFilter) {
                    return false;
                }

                if ($this->billingStatusFilter !== 'all') {
                    $matchesStatus = match ($this->billingStatusFilter) {
                        'paid' => ($summary['workspace_price_monthly'] ?? 0) > 0,
                        'free' => ($summary['workspace_price_monthly'] ?? 0) === 0,
                        'over_limit' => $summary['users_over_limit'] || $summary['operational_over_limit'],
                        'custom' => $summary['workspace_price_monthly'] === null,
                        default => true,
                    };

                    if (! $matchesStatus) {
                        return false;
                    }
                }

                if ($search === '') {
                    return true;
                }

                return Str::contains(
                    Str::lower(implode(' ', array_filter([
                        $workspaceRow->name,
                        $workspaceRow->company?->name,
                        $summary['plan_name'] ?? '',
                        $summary['price_label'] ?? '',
                        $summary['usage_metric_label'] ?? '',
                        $workspaceRow->templateName(),
                    ]))),
                    $search
                );
            })
            ->sortBy([
                [fn (array $row) => $row['summary']['users_over_limit'] || $row['summary']['operational_over_limit'] ? 0 : 1, 'asc'],
                [fn (array $row) => Str::lower($row['workspace']->company?->name ?? ''), 'asc'],
                [fn (array $row) => Str::lower($row['workspace']->name), 'asc'],
            ])
            ->values();
    }

    protected function applyCompanySearch(\Illuminate\Support\Collection $companies): \Illuminate\Support\Collection
    {
        $search = Str::lower(trim($this->companySearch));

        if ($search === '') {
            return $companies->values();
        }

        return $companies
            ->filter(function (Company $company) use ($search) {
                return Str::contains(
                    Str::lower(implode(' ', array_filter([
                        $company->name,
                        $company->industry,
                        $company->timezone,
                        $company->contact_email,
                        $company->contact_phone,
                    ]))),
                    $search
                );
            })
            ->values();
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

    protected function applyWorkspaceSearch($query)
    {
        $search = trim($this->workspaceSearch);

        if ($search === '') {
            return $query;
        }

        $matchingTemplateKeys = collect(Workspace::workspaceTemplates())
            ->filter(function (array $template, string $key) use ($search) {
                return Str::contains(
                    Str::lower($key.' '.($template['name'] ?? '').' '.($template['description'] ?? '')),
                    Str::lower($search)
                );
            })
            ->keys()
            ->values();

        return $query->where(function ($workspaceQuery) use ($search, $matchingTemplateKeys) {
            $workspaceQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhereHas('company', function ($companyQuery) use ($search) {
                    $companyQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('industry', 'like', "%{$search}%");
                });

            foreach ($matchingTemplateKeys as $templateKey) {
                $workspaceQuery->orWhere('settings', 'like', '%'.$templateKey.'%');
            }
        });
    }

    protected function flash(string $message): void
    {
        session()->flash('status', $message);
    }
}
