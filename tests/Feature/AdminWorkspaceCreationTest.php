<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Models\Company;
use App\Models\SheetSource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use jeremykenedy\LaravelRoles\Models\Role;
use Livewire\Livewire;
use Tests\TestCase;

class AdminWorkspaceCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_workspace_with_a_selected_mode(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('workspaceForm.company_id', $company->id)
            ->set('workspaceForm.name', 'Chandling Desk')
            ->set('workspaceForm.description', 'Port supply workspace')
            ->set('workspaceForm.template_key', 'ship_chandling')
            ->call('saveWorkspace')
            ->assertHasNoErrors();

        $workspace = $company->workspaces()->where('name', 'Chandling Desk')->first();

        $this->assertNotNull($workspace);
        $this->assertSame('ship_chandling', $workspace->templateKey());
        $this->assertContains('vessel_calls', $workspace->templateModules());
    }

    public function test_admin_can_find_the_correct_workspace_by_searching_mode_or_company(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $acme = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $blue = Company::create([
            'name' => 'Blue Dock',
            'slug' => 'blue-dock',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        Workspace::create([
            'company_id' => $acme->id,
            'name' => 'Forwarding Desk',
            'slug' => 'forwarding-desk',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        Workspace::create([
            'company_id' => $blue->id,
            'name' => 'Port Supply',
            'slug' => 'port-supply',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'ship_chandling'),
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('activeTab', 'structure')
            ->set('workspaceSearch', 'chandling')
            ->assertSee('Port Supply')
            ->assertSee('1 workspace found')
            ->set('workspaceSearch', 'Acme')
            ->assertSee('Forwarding Desk')
            ->assertSee('1 workspace found');
    }

    public function test_admin_must_type_workspace_name_before_deleting_it(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Danger Zone',
            'slug' => 'danger-zone',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('requestWorkspaceDeletion', $workspace->id)
            ->set('workspaceDeleteConfirmation', 'Wrong Name')
            ->set('workspaceDeleteAcknowledged', true)
            ->call('confirmWorkspaceDeletion')
            ->assertHasErrors(['workspaceDeleteConfirmation'])
            ->set('workspaceDeleteConfirmation', 'Danger Zone')
            ->set('workspaceDeleteAcknowledged', false)
            ->call('confirmWorkspaceDeletion')
            ->assertHasErrors(['workspaceDeleteAcknowledged'])
            ->set('workspaceDeleteConfirmation', 'Danger Zone')
            ->set('workspaceDeleteAcknowledged', true)
            ->call('confirmWorkspaceDeletion');

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }

    public function test_super_admin_overview_shows_platform_metrics_and_subscription_mix(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $companyA = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $companyB = Company::create([
            'name' => 'Blue Dock',
            'slug' => 'blue-dock',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $growthSettings = Workspace::applyTemplateSettings(null, 'freight_forwarding');
        data_set($growthSettings, 'billing.plan_key', 'growth');
        data_set($growthSettings, 'subscription_plan', 'growth');

        $professionalSettings = Workspace::applyTemplateSettings(null, 'ship_chandling');
        data_set($professionalSettings, 'billing.plan_key', 'professional');
        data_set($professionalSettings, 'subscription_plan', 'professional');

        $growthWorkspace = Workspace::create([
            'company_id' => $companyA->id,
            'name' => 'Forwarding Desk',
            'slug' => 'forwarding-desk',
            'is_default' => true,
            'settings' => $growthSettings,
        ]);

        $professionalWorkspace = Workspace::create([
            'company_id' => $companyB->id,
            'name' => 'Port Supply',
            'slug' => 'port-supply',
            'is_default' => true,
            'settings' => $professionalSettings,
        ]);

        $userOne = User::factory()->create(['company_id' => $companyA->id, 'default_workspace_id' => $growthWorkspace->id]);
        $userTwo = User::factory()->create(['company_id' => $companyA->id, 'default_workspace_id' => $growthWorkspace->id]);
        $userThree = User::factory()->create(['company_id' => $companyB->id, 'default_workspace_id' => $professionalWorkspace->id]);

        $growthWorkspace->users()->sync([
            $userOne->id => ['job_title' => 'Coordinator'],
            $userTwo->id => ['job_title' => 'Sales'],
        ]);

        $professionalWorkspace->users()->sync([
            $userThree->id => ['job_title' => 'Operations'],
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSee('Growth over time')
            ->assertSee('Subscription mix')
            ->assertViewHas('overviewStats', function (array $stats) {
                return collect($stats)->contains(fn (array $stat) => $stat['label'] === 'Total Users' && $stat['value'] === '4')
                    && collect($stats)->contains(fn (array $stat) => $stat['label'] === 'Total Workspaces' && $stat['value'] === '2')
                    && collect($stats)->contains(fn (array $stat) => $stat['label'] === 'Estimated MRR' && $stat['value'] === '$548');
            })
            ->assertViewHas('subscriptionRows', function ($rows) {
                return $rows->firstWhere('key', 'growth')['count'] === 1
                    && $rows->firstWhere('key', 'professional')['count'] === 1;
            });
    }

    public function test_super_admin_can_search_and_filter_all_sources_across_the_platform(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $acme = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $blue = Company::create([
            'name' => 'Blue Dock',
            'slug' => 'blue-dock',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $acmeWorkspace = Workspace::create([
            'company_id' => $acme->id,
            'name' => 'Forwarding Desk',
            'slug' => 'forwarding-desk',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $blueWorkspace = Workspace::create([
            'company_id' => $blue->id,
            'name' => 'Port Supply',
            'slug' => 'port-supply',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'ship_chandling'),
        ]);

        SheetSource::create([
            'company_id' => $acme->id,
            'workspace_id' => $acmeWorkspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'CargoWise Ops Feed',
            'url' => 'https://api.acme.test/cargowise/leads',
            'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
            'is_active' => true,
            'sync_status' => 'failed',
            'last_error' => 'Authentication failed',
        ]);

        SheetSource::create([
            'company_id' => $blue->id,
            'workspace_id' => $blueWorkspace->id,
            'type' => SheetSource::TYPE_REPORTS,
            'name' => 'Google Reports',
            'url' => 'https://docs.google.com/spreadsheets/d/example/edit#gid=0',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('activeTab', 'sources')
            ->assertSee('All data sources')
            ->assertSee('Source support view')
            ->set('sourceSearch', 'Blue Dock')
            ->assertViewHas('sheetSources', function ($sources) {
                return $sources->total() === 1
                    && $sources->getCollection()->first()?->name === 'Google Reports';
            })
            ->set('sourceSearch', '')
            ->set('sourceStatusFilter', 'failed')
            ->assertViewHas('sheetSources', function ($sources) {
                return $sources->total() === 1
                    && $sources->getCollection()->first()?->name === 'CargoWise Ops Feed';
            })
            ->set('sourceStatusFilter', 'all')
            ->set('sourceKindFilter', SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API)
            ->assertViewHas('sheetSources', function ($sources) {
                return $sources->total() === 1
                    && $sources->getCollection()->first()?->name === 'Google Reports';
            });
    }

    public function test_super_admin_can_search_and_update_users_from_the_master_directory(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales user', 'level' => 3],
        );

        $opsRole = Role::firstOrCreate(
            ['slug' => 'operations'],
            ['name' => 'Operations', 'description' => 'Operations user', 'level' => 3],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $acme = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $blue = Company::create([
            'name' => 'Blue Dock',
            'slug' => 'blue-dock',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $acmeWorkspace = Workspace::create([
            'company_id' => $acme->id,
            'name' => 'Forwarding Desk',
            'slug' => 'forwarding-desk',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $blueWorkspace = Workspace::create([
            'company_id' => $blue->id,
            'name' => 'Port Supply',
            'slug' => 'port-supply',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'ship_chandling'),
        ]);

        $targetUser = User::factory()->create([
            'company_id' => $acme->id,
            'default_workspace_id' => $acmeWorkspace->id,
            'name' => 'Jane Harbor',
            'email' => 'jane@example.com',
            'is_active' => true,
        ]);
        $targetUser->attachRole($salesRole);
        $targetUser->workspaces()->sync([$acmeWorkspace->id => ['job_title' => 'Coordinator']]);

        $otherUser = User::factory()->create([
            'company_id' => $blue->id,
            'default_workspace_id' => $blueWorkspace->id,
            'name' => 'Mark Dock',
            'email' => 'mark@example.com',
            'is_active' => false,
        ]);
        $otherUser->attachRole($opsRole);
        $otherUser->workspaces()->sync([$blueWorkspace->id => ['job_title' => 'Ops']]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('activeTab', 'access')
            ->assertSee('All users')
            ->assertSee('User support view')
            ->set('userSearch', 'Jane Harbor')
            ->assertViewHas('workspaceUsers', function ($users) {
                return $users->total() === 1
                    && $users->getCollection()->first()?->email === 'jane@example.com';
            })
            ->set('userSearch', '')
            ->set('userStatusFilter', 'inactive')
            ->assertViewHas('workspaceUsers', function ($users) {
                return $users->total() === 1
                    && $users->getCollection()->first()?->email === 'mark@example.com';
            })
            ->set('userStatusFilter', 'all')
            ->call('startEditingUser', $targetUser->id)
            ->set('editingUserForm.role', 'operations')
            ->set('editingUserForm.workspace_ids', [$acmeWorkspace->id, $blueWorkspace->id])
            ->set('editingUserForm.default_workspace_id', $blueWorkspace->id)
            ->set('editingUserForm.is_active', false)
            ->call('updateUser')
            ->assertHasNoErrors();

        $targetUser->refresh();

        $this->assertFalse($targetUser->is_active);
        $this->assertSame($blueWorkspace->id, $targetUser->default_workspace_id);
        $this->assertTrue($targetUser->hasRole('operations'));
        $assignedWorkspaceIds = $targetUser->workspaces()->pluck('workspaces.id')->sort()->values()->all();

        $this->assertSame([$acmeWorkspace->id, $blueWorkspace->id], $assignedWorkspaceIds);
    }

    public function test_super_admin_can_search_and_filter_the_billing_directory(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        $acme = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $blue = Company::create([
            'name' => 'Blue Dock',
            'slug' => 'blue-dock',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $growthSettings = Workspace::applyTemplateSettings(null, 'freight_forwarding');
        data_set($growthSettings, 'billing.plan_key', 'growth');
        data_set($growthSettings, 'subscription_plan', 'growth');
        data_set($growthSettings, 'billing.included_users', 1);

        $freeSettings = Workspace::applyTemplateSettings(null, 'ship_chandling');
        data_set($freeSettings, 'billing.plan_key', 'free');
        data_set($freeSettings, 'subscription_plan', 'free');

        $growthWorkspace = Workspace::create([
            'company_id' => $acme->id,
            'name' => 'Forwarding Desk',
            'slug' => 'forwarding-desk',
            'is_default' => true,
            'settings' => $growthSettings,
        ]);

        $freeWorkspace = Workspace::create([
            'company_id' => $blue->id,
            'name' => 'Port Supply',
            'slug' => 'port-supply',
            'is_default' => true,
            'settings' => $freeSettings,
        ]);

        $userOne = User::factory()->create(['company_id' => $acme->id, 'default_workspace_id' => $growthWorkspace->id]);
        $userTwo = User::factory()->create(['company_id' => $acme->id, 'default_workspace_id' => $growthWorkspace->id]);
        $growthWorkspace->users()->sync([
            $userOne->id => ['job_title' => 'Sales'],
            $userTwo->id => ['job_title' => 'Ops'],
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('activeTab', 'billing')
            ->assertSee('Billing directory')
            ->set('billingSearch', 'Blue Dock')
            ->assertViewHas('billingDirectoryRows', function ($rows) {
                return $rows->total() === 1
                    && $rows->items()[0]['workspace']->name === 'Port Supply';
            })
            ->set('billingSearch', '')
            ->set('billingPlanFilter', 'growth')
            ->assertViewHas('billingDirectoryRows', function ($rows) {
                return $rows->total() === 1
                    && $rows->items()[0]['workspace']->name === 'Forwarding Desk';
            })
            ->set('billingPlanFilter', 'all')
            ->set('billingStatusFilter', 'over_limit')
            ->assertViewHas('billingDirectoryRows', function ($rows) {
                return $rows->total() === 1
                    && $rows->items()[0]['workspace']->name === 'Forwarding Desk';
            });
    }

    public function test_super_admin_can_search_the_company_directory_in_structure_tab(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $admin = User::factory()->create();
        $admin->attachRole($adminRole);

        Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'contact_email' => 'ops@acme.test',
            'is_active' => true,
        ]);

        Company::create([
            'name' => 'Blue Dock',
            'slug' => 'blue-dock',
            'industry' => 'Port Services',
            'timezone' => 'Europe/London',
            'contact_email' => 'hello@blue.test',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('activeTab', 'structure')
            ->set('companySearch', 'london')
            ->assertViewHas('companyDirectoryRows', function ($companies) {
                return $companies->count() === 1
                    && $companies->first()?->name === 'Blue Dock';
            })
            ->set('companySearch', 'Acme')
            ->assertViewHas('companyDirectoryRows', function ($companies) {
                return $companies->count() === 1
                    && $companies->first()?->name === 'Acme Marine';
            });
    }
}
