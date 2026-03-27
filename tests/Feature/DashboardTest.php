<?php

namespace Tests\Feature;

use App\Livewire\CrmDashboard;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SheetSource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use jeremykenedy\LaravelRoles\Models\Permission;
use jeremykenedy\LaravelRoles\Models\Role;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_workspace_users_can_see_the_sources_tab(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Main Workspace',
            'slug' => 'main-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Sources');

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('activeTab', 'sources')
            ->assertSee('Integrations and sources');
    }

    public function test_workspace_users_can_open_contact_and_customer_ai_briefs(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Main Workspace',
            'slug' => 'main-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-1',
            'lead_id' => 'LD-1',
            'contact_name' => 'Sara Ahmed',
            'company_name' => 'Blue Tide Logistics',
            'email' => 'sara@example.com',
            'phone' => '971500000001',
            'service' => 'Container Conversion',
            'submission_date' => now()->subDays(5),
            'lead_source' => 'Google Ads',
            'status' => Lead::STATUS_SALES_QUALIFIED,
            'lead_value' => 125000,
        ]);

        $customer = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'external_key' => 'opp-1',
            'company_name' => 'Blue Tide Logistics',
            'contact_email' => 'sara@example.com',
            'lead_source' => 'Google Ads',
            'required_service' => 'Container Conversion',
            'revenue_potential' => 125000,
            'sales_stage' => Opportunity::STAGE_CLOSED_WON,
            'submission_date' => now()->subDays(1),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'contacts')
            ->call('selectContact', $lead->id)
            ->assertSee('AI Contact Brief')
            ->assertSee('Sara Ahmed');

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'customers')
            ->call('selectCustomer', $customer->id)
            ->assertSee('AI Customer Brief')
            ->assertSee('Blue Tide Logistics');
    }

    public function test_opportunity_moves_a_contact_into_customers_even_before_closed_won(): void
    {
        $company = Company::create([
            'name' => 'Customer Pipeline',
            'slug' => 'customer-pipeline',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Customer Pipeline Workspace',
            'slug' => 'customer-pipeline-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-customer-move',
            'lead_id' => 'LD-CUST-1',
            'contact_name' => 'Nadia Stone',
            'company_name' => 'Jetty Lines',
            'email' => 'nadia@example.com',
            'submission_date' => now()->subDays(3),
            'status' => Lead::STATUS_SALES_QUALIFIED,
        ]);

        Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'external_key' => 'opp-customer-move',
            'company_name' => 'Jetty Lines',
            'contact_email' => 'nadia@example.com',
            'required_service' => 'Freight Services',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'contacts')
            ->assertDontSee('Nadia Stone');

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'customers')
            ->assertSee('Jetty Lines')
            ->assertSee('Opportunity Date');
    }

    public function test_workspace_users_can_open_and_edit_opportunities_from_popup(): void
    {
        $company = Company::create([
            'name' => 'Opportunity Marine',
            'slug' => 'opportunity-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Opportunity Workspace',
            'slug' => 'opportunity-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $opportunity = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'opp-edit-1',
            'company_name' => 'Dockside Projects',
            'contact_email' => 'ops@dockside.test',
            'lead_source' => 'Google Ads',
            'required_service' => 'Air Freight',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'opportunities')
            ->assertDontSee('Opportunity Details')
            ->call('selectOpportunity', $opportunity->id)
            ->assertSee('Opportunity Details')
            ->set('opportunityEditForm.company_name', 'Dockside Projects LLC')
            ->set('opportunityEditForm.revenue_potential', '145000')
            ->set('opportunityEditForm.sales_stage', Opportunity::STAGE_PROPOSAL_SENT)
            ->call('saveOpportunityDetails')
            ->assertSee('Dockside Projects LLC');

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'company_name' => 'Dockside Projects LLC',
            'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
        ]);
    }

    public function test_contact_and_customer_popups_are_hidden_until_clicked_and_can_close(): void
    {
        $company = Company::create([
            'name' => 'Popup Marine',
            'slug' => 'popup-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Popup Workspace',
            'slug' => 'popup-workspace-contacts',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-popup-1',
            'lead_id' => 'LD-POP-1',
            'contact_name' => 'Ava Reed',
            'company_name' => 'North Dock Lines',
            'email' => 'ava@example.com',
            'submission_date' => now()->subDays(2),
            'status' => Lead::STATUS_IN_PROGRESS,
        ]);

        $customer = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'external_key' => 'opp-popup-1',
            'company_name' => 'North Dock Lines',
            'contact_email' => 'ava@example.com',
            'sales_stage' => Opportunity::STAGE_CLOSED_WON,
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'contacts')
            ->assertSet('selectedContactId', null)
            ->assertDontSee('AI Contact Brief')
            ->call('selectContact', $lead->id)
            ->assertSee('AI Contact Brief')
            ->call('closeContactDetails')
            ->assertSet('selectedContactId', null)
            ->assertDontSee('AI Contact Brief');

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'customers')
            ->assertSet('selectedCustomerId', null)
            ->assertDontSee('AI Customer Brief')
            ->call('selectCustomer', $customer->id)
            ->assertSee('AI Customer Brief')
            ->call('closeCustomerDetails')
            ->assertSet('selectedCustomerId', null)
            ->assertDontSee('AI Customer Brief');
    }

    public function test_workspace_users_can_open_lead_details_from_the_leads_table(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Main Workspace',
            'slug' => 'main-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-2',
            'lead_id' => 'LD-200',
            'contact_name' => 'Omar Khan',
            'company_name' => 'North Sea Cargo',
            'email' => 'omar@example.com',
            'phone' => '971500000002',
            'service' => 'Air Freight',
            'submission_date' => now()->subDays(2),
            'lead_source' => 'Website Quote Form',
            'status' => Lead::STATUS_IN_PROGRESS,
            'lead_value' => 85000,
            'notes' => 'Needs pricing revision.',
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'leads')
            ->call('selectLead', $lead->id)
            ->assertSee('Lead Details')
            ->assertSee('North Sea Cargo')
            ->assertSee('Needs pricing revision.');
    }

    public function test_lead_details_popup_is_hidden_until_a_lead_is_clicked_and_can_close(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine-hidden',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Hidden Popup Workspace',
            'slug' => 'hidden-popup-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-4',
            'lead_id' => 'LD-400',
            'company_name' => 'Harbor One Shipping',
            'submission_date' => now()->subHours(6),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'leads')
            ->assertSet('selectedLeadId', null)
            ->assertDontSee('Lead Details')
            ->call('selectLead', $lead->id)
            ->assertSee('Lead Details')
            ->call('closeLeadDetails')
            ->assertSet('selectedLeadId', null)
            ->assertDontSee('Lead Details');
    }

    public function test_sales_qualified_lead_creates_a_draft_opportunity_and_opens_the_form(): void
    {
        $company = Company::create([
            'name' => 'Qualified Marine',
            'slug' => 'qualified-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Qualified Workspace',
            'slug' => 'qualified-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $user->id,
            'external_key' => 'lead-qualified-1',
            'lead_id' => 'LD-Q-1',
            'contact_name' => 'Mina Patel',
            'company_name' => 'Harbor Export Lines',
            'email' => 'mina@example.com',
            'lead_source' => 'Google Ads',
            'service' => 'Air Freight',
            'lead_value' => 90000,
            'status' => Lead::STATUS_IN_PROGRESS,
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'leads')
            ->call('updateLeadStatus', $lead->id, Lead::STATUS_SALES_QUALIFIED)
            ->assertSet('activeTab', 'manual-opportunity')
            ->assertSet('manualOpportunityForm.lead_id', (string) $lead->id)
            ->assertSet('manualOpportunityForm.company_name', 'Harbor Export Lines')
            ->assertSet('manualOpportunityForm.contact_email', 'mina@example.com')
            ->assertSet('manualOpportunityForm.required_service', 'Air Freight');

        $this->assertDatabaseHas('opportunities', [
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'company_name' => 'Harbor Export Lines',
            'contact_email' => 'mina@example.com',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
        ]);
    }

    public function test_lead_details_popup_closes_when_switching_tabs(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine-popup',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Popup Workspace',
            'slug' => 'popup-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-3',
            'lead_id' => 'LD-300',
            'company_name' => 'Anchor Logistics',
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'leads')
            ->call('selectLead', $lead->id)
            ->assertSet('selectedLeadId', $lead->id)
            ->set('activeTab', 'opportunities')
            ->assertSet('selectedLeadId', null)
            ->assertDontSee('Lead Details');
    }

    public function test_users_without_a_workspace_see_the_start_workspace_cta(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Start a new workspace');
    }

    public function test_users_without_a_workspace_can_create_their_first_workspace(): void
    {
        $user = User::factory()->create([
            'company_id' => null,
            'default_workspace_id' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('companyForm.name', 'Acme Marine')
            ->set('companyForm.industry', 'Maritime')
            ->set('companyForm.contact_email', 'ops@acme.test')
            ->set('companyForm.timezone', 'Asia/Dubai')
            ->set('workspaceForm.name', 'Main Workspace')
            ->set('workspaceForm.description', 'Primary sales workspace')
            ->call('startWorkspace')
            ->assertHasNoErrors();

        $user->refresh();
        $workspace = Workspace::query()->where('name', 'Main Workspace')->first();

        $this->assertNotNull($workspace);
        $this->assertSame($workspace->id, $user->default_workspace_id);
        $this->assertSame($workspace->company_id, $user->company_id);
        $this->assertTrue($user->workspaces()->whereKey($workspace->id)->exists());
        $this->assertTrue($user->hasRole('manager'));
    }

    public function test_google_source_sync_shows_a_friendly_message_when_admin_setup_is_missing(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Main Workspace',
            'slug' => 'main-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Manager']);

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Workspace manager', 'level' => 6],
        );

        $user->attachRole($managerRole);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'Google Leads',
            'url' => 'https://example.com/private-google-api-source',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API,
            'description' => 'Google Sheets API source',
            'is_active' => true,
            'sync_status' => 'idle',
        ]);

        $googleSheets = $this->createMock(GoogleSheetsService::class);
        $googleSheets->method('readRows')
            ->willThrowException(new \RuntimeException('Google OAuth client ID and secret must be saved first.'));

        $this->app->instance(GoogleSheetsService::class, $googleSheets);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->call('syncSource', $source->id);

        $this->assertSame('failed', $source->fresh()->sync_status);
        $this->assertSame('Google OAuth client ID and secret must be saved first.', $source->fresh()->last_error);
    }

    public function test_workspace_owner_can_create_and_edit_workspace_access_from_the_access_tab(): void
    {
        $company = Company::create([
            'name' => 'Access Marine',
            'slug' => 'access-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Access Workspace',
            'slug' => 'access-workspace',
            'is_default' => true,
        ]);

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $user->attachRole($salesRole);

        $this->actingAs($user);

        $component = Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'access')
            ->assertSee('Access')
            ->assertSee('Add workspace user')
            ->set('permissionForm.name', 'Manage Workspace')
            ->set('permissionForm.slug', 'manage-workspace')
            ->set('permissionForm.description', 'Manage workspace access')
            ->call('createPermission')
            ->assertHasNoErrors();

        $permission = Permission::where('name', 'Manage Workspace')->first();

        $this->assertNotNull($permission);

        $component
            ->set('roleForm.name', 'Workspace Owner')
            ->set('roleForm.slug', 'workspace-owner')
            ->set('roleForm.description', 'Owner role')
            ->set('roleForm.level', 7)
            ->set('roleForm.permission_ids', [(string) $permission->id])
            ->call('createRole')
            ->assertHasNoErrors();

        $role = Role::where('name', 'Workspace Owner')->first();

        $this->assertNotNull($role);

        $component
            ->set('userForm.name', 'Workspace Teammate')
            ->set('userForm.email', 'teammate@example.com')
            ->set('userForm.password', 'password123')
            ->set('userForm.job_title', 'Coordinator')
            ->set('userForm.role', $role->slug)
            ->set('userForm.permission_ids', [(string) $permission->id])
            ->call('createUser')
            ->assertHasNoErrors();

        $createdUser = User::where('email', 'teammate@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame($workspace->id, $createdUser->default_workspace_id);
        $this->assertTrue($createdUser->workspaces->pluck('id')->contains($workspace->id));
        $this->assertTrue($createdUser->hasRole($role->slug));
        $this->assertTrue($createdUser->hasPermission($permission->slug));
        $this->assertFalse((bool) $createdUser->workspaces()->where('workspaces.id', $workspace->id)->first()->pivot->is_owner);

        $component
            ->call('startEditingWorkspaceUser', $createdUser->id)
            ->set('editingWorkspaceUserForm.job_title', 'Operations Lead')
            ->set('editingWorkspaceUserForm.role', $salesRole->slug)
            ->set('editingWorkspaceUserForm.permission_ids', [])
            ->call('updateWorkspaceUserAccess')
            ->assertHasNoErrors();

        $createdUser = User::query()->findOrFail($createdUser->id);

        $this->assertSame(
            'Operations Lead',
            $createdUser->workspaces()->where('workspaces.id', $workspace->id)->first()->pivot->job_title
        );
        $this->assertTrue($createdUser->hasRole($salesRole->slug));
        $this->assertFalse($createdUser->hasRole($role->slug));
        $this->assertFalse($createdUser->hasPermission($permission->slug));
    }

    public function test_non_owner_workspace_users_cannot_manage_workspace_access(): void
    {
        $company = Company::create([
            'name' => 'Restricted Marine',
            'slug' => 'restricted-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Restricted Workspace',
            'slug' => 'restricted-workspace',
            'is_default' => true,
        ]);

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $owner = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);
        $owner->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $owner->attachRole($salesRole);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);
        $user->workspaces()->attach($workspace->id, ['job_title' => 'Coordinator', 'is_owner' => false]);
        $user->attachRole($salesRole);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->assertDontSee('Access')
            ->assertDontSee('Add workspace user');

        Livewire::test(CrmDashboard::class)
            ->set('permissionForm.name', 'Manage Workspace')
            ->set('permissionForm.slug', 'manage-workspace')
            ->set('permissionForm.description', 'Manage workspace access')
            ->call('createPermission');

        $this->assertDatabaseMissing('permissions', [
            'name' => 'Manage Workspace',
            'slug' => 'manage-workspace',
        ]);
    }

    public function test_managers_can_edit_a_source_from_the_dashboard(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Main Workspace',
            'slug' => 'main-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Manager']);

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Workspace manager', 'level' => 6],
        );

        $user->attachRole($managerRole);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'Old Source Name',
            'url' => 'https://docs.google.com/spreadsheets/d/example/edit#gid=0',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
            'description' => 'Lead source',
            'is_active' => true,
            'sync_status' => 'idle',
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->call('startEditingSource', $source->id)
            ->set('editingSourceForm.name', 'Updated Source Name')
            ->call('updateSheetSource')
            ->assertSee('Source Updated Source Name updated.');

        $this->assertSame('Updated Source Name', $source->fresh()->name);
    }
}
