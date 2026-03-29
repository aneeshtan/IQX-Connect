<?php

namespace Tests\Feature;

use App\Livewire\CrmDashboard;
use App\Mail\WorkspaceActivityMail;
use App\Models\Account;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\CollaborationEntry;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerSegmentDefinition;
use App\Models\Invoice;
use App\Models\JobCosting;
use App\Models\JobCostingLine;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\ProjectDeliveryMilestone;
use App\Models\ProjectDrawing;
use App\Models\Quote;
use App\Models\RateCard;
use App\Models\SheetSource;
use App\Models\ShipmentDocument;
use App\Models\ShipmentJob;
use App\Models\ShipmentMilestone;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceNotification;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        $response->assertDontSee('flux.js', false);
    }

    public function test_workspace_owners_can_open_sources_from_workspace_settings(): void
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

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $user->attachRole($salesRole);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Settings');
        $response->assertDontSee('>Sources<', false);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('activeTab', 'settings')
            ->assertSee('Workspace Settings')
            ->set('activeTab', 'sources')
            ->assertSee('Integrations and sources');
    }

    public function test_workspace_sources_can_target_active_modules(): void
    {
        $company = Company::create([
            'name' => 'Forward Source Marine',
            'slug' => 'forward-source-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Forward Source Workspace',
            'slug' => 'forward-source-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Manager role', 'level' => 4],
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $user->attachRole($managerRole);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'sources')
            ->set('sourceForm.workspace_id', $workspace->id)
            ->set('sourceForm.type', 'shipments')
            ->set('sourceForm.name', 'Shipment Source')
            ->set('sourceForm.url', 'https://docs.google.com/spreadsheets/d/IQXCONNECTDEMO1234567890/edit?gid=0#gid=0')
            ->set('sourceForm.source_kind', SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV)
            ->set('sourceForm.description', 'Freight shipment feed')
            ->call('saveSheetSource')
            ->assertHasNoErrors()
            ->assertSee('Shipment Source')
            ->assertSee('Sync');

        $this->assertDatabaseHas('sheet_sources', [
            'workspace_id' => $workspace->id,
            'type' => 'shipments',
            'name' => 'Shipment Source',
        ]);
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

    public function test_workspace_users_can_update_notification_preferences_from_settings(): void
    {
        $company = Company::create([
            'name' => 'Notification Marine',
            'slug' => 'notification-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Notification Workspace',
            'slug' => 'notification-workspace',
            'is_default' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, [
            'job_title' => 'Sales',
            'is_owner' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('activeTab', 'settings')
            ->set('settingsTab', 'notifications')
            ->set('notificationSettingsForm.channels.in_app', false)
            ->set('notificationSettingsForm.channels.email', true)
            ->set('notificationSettingsForm.events.assignment', true)
            ->set('notificationSettingsForm.events.note', false)
            ->set('notificationSettingsForm.events.message', true)
            ->call('saveNotificationSettings')
            ->assertHasNoErrors()
            ->assertSee('Notification channels');

        $membership = WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame([
            'channels' => [
                'in_app' => false,
                'email' => true,
            ],
            'events' => [
                'assignment' => true,
                'note' => false,
                'message' => true,
            ],
        ], $membership->notificationPreferences());
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

        $opportunity = Opportunity::create([
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
            ->assertSee('Customers created from opportunities');
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

    public function test_freight_forwarder_customers_receive_segment_badges_and_can_be_filtered(): void
    {
        $company = Company::create([
            'name' => 'Segment Marine',
            'slug' => 'segment-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Segment Workspace',
            'slug' => 'segment-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);

        $activeAccount = Account::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'name' => 'Fresh Forwarding',
            'slug' => 'fresh-forwarding',
            'primary_email' => 'ops@fresh.test',
            'latest_service' => 'Ocean Freight',
            'last_activity_at' => now()->subDays(5),
        ]);

        $churnedAccount = Account::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'name' => 'Dormant Logistics',
            'slug' => 'dormant-logistics',
            'primary_email' => 'ops@dormant.test',
            'latest_service' => 'Air Freight',
            'last_activity_at' => now()->subDays(220),
        ]);

        Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'account_id' => $activeAccount->id,
            'external_key' => 'opp-active-segment',
            'company_name' => 'Fresh Forwarding',
            'contact_email' => 'ops@fresh.test',
            'required_service' => 'Ocean Freight',
            'sales_stage' => Opportunity::STAGE_CLOSED_WON,
            'submission_date' => now()->subDays(10),
            'revenue_potential' => 180000,
        ]);

        Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'account_id' => $churnedAccount->id,
            'external_key' => 'opp-churn-segment',
            'company_name' => 'Dormant Logistics',
            'contact_email' => 'ops@dormant.test',
            'required_service' => 'Air Freight',
            'sales_stage' => Opportunity::STAGE_CLOSED_WON,
            'submission_date' => now()->subDays(240),
            'revenue_potential' => 250000,
        ]);

        ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'account_id' => $activeAccount->id,
            'job_number' => 'SJ-ACT-001',
            'company_name' => 'Fresh Forwarding',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Rotterdam',
            'status' => ShipmentJob::STATUS_IN_TRANSIT,
            'estimated_departure_at' => now()->subDays(5),
            'sell_amount' => 32000,
            'buy_amount' => 25000,
            'margin_amount' => 7000,
            'currency' => 'AED',
        ]);

        foreach (range(1, 6) as $index) {
            ShipmentJob::create([
                'company_id' => $company->id,
                'workspace_id' => $workspace->id,
                'account_id' => $churnedAccount->id,
                'job_number' => 'SJ-OLD-00'.$index,
                'company_name' => 'Dormant Logistics',
                'service_mode' => 'Air Freight',
                'origin' => 'DXB',
                'destination' => 'LHR',
                'status' => ShipmentJob::STATUS_DELIVERED,
                'actual_departure_at' => now()->subDays(220 + $index),
                'actual_arrival_at' => now()->subDays(218 + $index),
                'sell_amount' => 18000,
                'buy_amount' => 14000,
                'margin_amount' => 4000,
                'currency' => 'AED',
            ]);
        }

        $this->actingAs($user);

        $component = Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('activeTab', 'customers')
            ->assertSee('Fresh Forwarding')
            ->assertSee('Dormant Logistics')
            ->assertSee('Active Customer')
            ->assertSee('Late Churned');

        $activeSegmentId = CustomerSegmentDefinition::query()
            ->where('workspace_id', $workspace->id)
            ->where('slug', 'active-customer')
            ->value('id');

        $component
            ->set('customerSegmentFilter', (string) $activeSegmentId)
            ->assertSee('Fresh Forwarding')
            ->assertDontSee('Dormant Logistics');
    }

    public function test_workspace_owner_can_update_customer_segment_rules_from_settings(): void
    {
        $company = Company::create([
            'name' => 'Segment Settings Marine',
            'slug' => 'segment-settings-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Segment Settings Workspace',
            'slug' => 'segment-settings-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('activeTab', 'settings')
            ->set('settingsTab', 'segmentations')
            ->set('workspaceSettingsForm.segment_definitions.0.name', 'Strategic Account')
            ->set('workspaceSettingsForm.segment_definitions.0.description', 'High-value freight account')
            ->set('workspaceSettingsForm.segment_definitions.0.color', 'violet')
            ->set('workspaceSettingsForm.segment_definitions.0.priority', 120)
            ->set('workspaceSettingsForm.segment_definitions.0.rules.0.metric_key', 'revenue_365d')
            ->set('workspaceSettingsForm.segment_definitions.0.rules.0.operator', 'gte')
            ->set('workspaceSettingsForm.segment_definitions.0.rules.0.threshold_value', '250000')
            ->call('saveWorkspaceSettings')
            ->assertHasNoErrors()
            ->assertSee('Workspace settings updated');

        $this->assertDatabaseHas('customer_segment_definitions', [
            'workspace_id' => $workspace->id,
            'name' => 'Strategic Account',
            'slug' => 'strategic-account',
            'color' => 'violet',
            'priority' => 120,
        ]);

        $segmentId = CustomerSegmentDefinition::query()
            ->where('workspace_id', $workspace->id)
            ->where('slug', 'strategic-account')
            ->value('id');

        $this->assertDatabaseHas('customer_segment_rules', [
            'segment_definition_id' => $segmentId,
            'metric_key' => 'revenue_365d',
            'operator' => 'gte',
            'threshold_value' => 250000.00,
        ]);
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

    public function test_closed_won_opportunity_creates_a_draft_shipment_and_opens_the_form(): void
    {
        $company = Company::create([
            'name' => 'Shipment Marine',
            'slug' => 'shipment-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Shipment Workspace',
            'slug' => 'shipment-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
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
            'external_key' => 'lead-shipment-1',
            'lead_id' => 'LD-S-1',
            'contact_name' => 'Mina Patel',
            'company_name' => 'Harbor Export Lines',
            'email' => 'mina@example.com',
            'lead_source' => 'Google Ads',
            'service' => 'Ocean Freight',
            'status' => Lead::STATUS_SALES_QUALIFIED,
            'submission_date' => now()->subDays(2),
        ]);

        $opportunity = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'assigned_user_id' => $user->id,
            'external_key' => 'opp-shipment-1',
            'company_name' => 'Harbor Export Lines',
            'contact_email' => 'mina@example.com',
            'lead_source' => 'Google Ads',
            'required_service' => 'Ocean Freight',
            'revenue_potential' => 15000,
            'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
            'submission_date' => now()->subDay(),
        ]);

        Quote::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
            'assigned_user_id' => $user->id,
            'quote_number' => 'QT-00001',
            'company_name' => 'Harbor Export Lines',
            'contact_name' => 'Mina Patel',
            'contact_email' => 'mina@example.com',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Hamburg',
            'buy_amount' => 12000,
            'sell_amount' => 15000,
            'currency' => 'AED',
            'status' => Quote::STATUS_ACCEPTED,
            'quoted_at' => now()->subHours(5),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'opportunities')
            ->call('updateOpportunityStage', $opportunity->id, Opportunity::STAGE_CLOSED_WON)
            ->assertSet('activeTab', 'manual-shipment')
            ->assertSet('manualShipmentForm.opportunity_id', (string) $opportunity->id)
            ->assertSet('manualShipmentForm.company_name', 'Harbor Export Lines')
            ->assertSet('manualShipmentForm.contact_email', 'mina@example.com')
            ->assertSet('manualShipmentForm.origin', 'Jebel Ali')
            ->assertSet('manualShipmentForm.destination', 'Hamburg')
            ->assertSet('manualShipmentForm.status', ShipmentJob::STATUS_DRAFT);

        $this->assertDatabaseHas('shipment_jobs', [
            'workspace_id' => $workspace->id,
            'opportunity_id' => $opportunity->id,
            'company_name' => 'Harbor Export Lines',
            'origin' => 'Jebel Ali',
            'destination' => 'Hamburg',
            'status' => ShipmentJob::STATUS_DRAFT,
        ]);

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();

        $this->assertDatabaseHas('shipment_milestones', [
            'shipment_job_id' => $shipment->id,
            'event_key' => 'booking_requested',
            'label' => 'Booking Requested',
        ]);
    }

    public function test_selecting_an_opportunity_autofills_the_manual_shipment_form(): void
    {
        $company = Company::create([
            'name' => 'Autofill Marine',
            'slug' => 'autofill-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Autofill Workspace',
            'slug' => 'autofill-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-auto-ship-1',
            'lead_id' => 'LD-AUTO-1',
            'contact_name' => 'Layla Noor',
            'company_name' => 'Northstar Cargo',
            'email' => 'layla@northstar.test',
            'service' => 'Ocean Freight',
            'status' => Lead::STATUS_SALES_QUALIFIED,
            'submission_date' => now()->subDays(3),
        ]);

        $opportunity = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'external_key' => 'opp-auto-ship-1',
            'company_name' => 'Northstar Cargo',
            'contact_email' => 'ops@northstar.test',
            'lead_source' => 'Website Quote Form',
            'required_service' => 'Ocean Freight',
            'revenue_potential' => 22000,
            'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
            'notes' => 'Customer confirmed preferred route.',
            'submission_date' => now()->subDay(),
        ]);

        $quote = Quote::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
            'quote_number' => 'QT-AUTO-1',
            'company_name' => 'Northstar Cargo',
            'contact_name' => 'Layla Noor',
            'contact_email' => 'layla@northstar.test',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Rotterdam',
            'incoterm' => 'FOB',
            'commodity' => 'Industrial Equipment',
            'equipment_type' => '40HC',
            'weight_kg' => 18000,
            'volume_cbm' => 55.2,
            'buy_amount' => 18000,
            'sell_amount' => 22000,
            'currency' => 'AED',
            'status' => Quote::STATUS_ACCEPTED,
            'notes' => 'Approved by customer.',
            'quoted_at' => now()->subHours(4),
        ]);

        $accountId = (string) $opportunity->fresh()->account_id;

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-shipment')
            ->set('manualShipmentForm.customer_record_id', (string) $opportunity->id)
            ->assertSet('manualShipmentForm.opportunity_id', '')
            ->assertSet('manualShipmentForm.quote_id', '')
            ->assertSet('manualShipmentForm.customer_record_id', $accountId)
            ->assertSet('manualShipmentForm.company_name', 'Northstar Cargo')
            ->assertSet('manualShipmentForm.contact_name', 'Layla Noor')
            ->assertSet('manualShipmentForm.contact_email', 'layla@northstar.test')
            ->set('manualShipmentForm.opportunity_id', (string) $opportunity->id)
            ->assertSet('manualShipmentForm.customer_record_id', $accountId)
            ->assertSet('manualShipmentForm.lead_id', (string) $lead->id)
            ->assertSet('manualShipmentForm.quote_id', (string) $quote->id)
            ->assertSet('manualShipmentForm.company_name', 'Northstar Cargo')
            ->assertSet('manualShipmentForm.contact_name', 'Layla Noor')
            ->assertSet('manualShipmentForm.contact_email', 'ops@northstar.test')
            ->assertSet('manualShipmentForm.service_mode', 'Ocean Freight')
            ->assertSet('manualShipmentForm.origin', 'Jebel Ali')
            ->assertSet('manualShipmentForm.destination', 'Rotterdam')
            ->assertSet('manualShipmentForm.incoterm', 'FOB')
            ->assertSet('manualShipmentForm.commodity', 'Industrial Equipment')
            ->assertSet('manualShipmentForm.equipment_type', '40HC')
            ->assertSet('manualShipmentForm.weight_kg', '18000.00')
            ->assertSet('manualShipmentForm.volume_cbm', '55.200')
            ->assertSet('manualShipmentForm.buy_amount', '18000.00')
            ->assertSet('manualShipmentForm.sell_amount', '22000.00')
            ->assertSet('manualShipmentForm.notes', 'Approved by customer.');
    }

    public function test_container_conversion_workspace_can_create_a_project(): void
    {
        $company = Company::create([
            'name' => 'GreenBox Containers',
            'slug' => 'greenbox-containers',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'GreenBox',
            'slug' => 'greenbox',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'container_conversion'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Projects', 'is_owner' => true]);

        $account = Account::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'name' => 'Desert Retail Holdings',
            'slug' => 'desert-retail-holdings',
            'primary_email' => 'procurement@desertretail.test',
            'latest_service' => 'Container Conversion',
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-project')
            ->set('manualProjectForm.customer_record_id', (string) $account->id)
            ->assertSet('manualProjectForm.customer_name', 'Desert Retail Holdings')
            ->set('manualProjectForm.project_name', 'Site Office Conversion')
            ->set('manualProjectForm.service_type', 'Container Conversion')
            ->set('manualProjectForm.container_type', '40HC')
            ->set('manualProjectForm.unit_quantity', '2')
            ->set('manualProjectForm.scope_summary', 'Office fit-out with HVAC and power distribution.')
            ->set('manualProjectForm.site_location', 'Abu Dhabi Industrial City')
            ->set('manualProjectForm.target_delivery_date', now()->addDays(30)->toDateString())
            ->set('manualProjectForm.target_installation_date', now()->addDays(37)->toDateString())
            ->set('manualProjectForm.estimated_value', '285000')
            ->call('addManualProject')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'projects')
            ->assertSee('Project added.');

        $project = Project::query()->where('workspace_id', $workspace->id)->first();

        $this->assertNotNull($project);
        $this->assertSame('Site Office Conversion', $project->project_name);
        $this->assertSame('Desert Retail Holdings', $project->customer_name);
        $this->assertSame('40HC', $project->container_type);
        $this->assertSame(2, $project->unit_quantity);
        $this->assertDatabaseCount('project_delivery_milestones', 8);
    }

    public function test_closed_won_opportunity_in_container_conversion_workspace_creates_project_draft_not_shipment(): void
    {
        $company = Company::create([
            'name' => 'GreenBox Deals',
            'slug' => 'greenbox-deals',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'GreenBox Projects',
            'slug' => 'greenbox-projects',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'container_conversion'),
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
            'external_key' => 'gb-lead-1',
            'lead_id' => 'LD-GB-1',
            'contact_name' => 'Hadi Kareem',
            'company_name' => 'Mall Development Group',
            'email' => 'hadi@malldev.test',
            'lead_source' => 'Website Quote Form',
            'service' => 'Container Conversion',
            'status' => Lead::STATUS_SALES_QUALIFIED,
            'submission_date' => now()->subDays(2),
        ]);

        $opportunity = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'assigned_user_id' => $user->id,
            'external_key' => 'opp-gb-1',
            'company_name' => 'Mall Development Group',
            'contact_email' => 'projects@malldev.test',
            'lead_source' => 'Website Quote Form',
            'required_service' => 'Container Conversion',
            'revenue_potential' => 420000,
            'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
            'submission_date' => now()->subDay(),
            'notes' => 'Awaiting final site approval.',
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->call('updateOpportunityStage', $opportunity->id, Opportunity::STAGE_CLOSED_WON)
            ->assertSet('activeTab', 'manual-project')
            ->assertSet('manualProjectForm.opportunity_id', (string) $opportunity->id)
            ->assertSet('manualProjectForm.lead_id', (string) $lead->id)
            ->assertSet('manualProjectForm.customer_name', 'Mall Development Group')
            ->assertSee('Project draft is ready');

        $project = Project::query()
            ->where('workspace_id', $workspace->id)
            ->where('opportunity_id', $opportunity->id)
            ->first();

        $this->assertNotNull($project);
        $this->assertSame(Project::STATUS_DRAFT, $project->status);
        $this->assertDatabaseMissing('shipment_jobs', [
            'workspace_id' => $workspace->id,
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_container_conversion_workspace_can_create_drawings_and_delivery_milestones(): void
    {
        $company = Company::create([
            'name' => 'GreenBox Delivery',
            'slug' => 'greenbox-delivery',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'GreenBox Delivery Workspace',
            'slug' => 'greenbox-delivery-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'container_conversion'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Engineering']);

        $project = Project::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $user->id,
            'project_number' => 'PRJ-00001',
            'project_name' => 'Coffee Kiosk Rollout',
            'customer_name' => 'Metro Hospitality',
            'service_type' => 'Container Conversion',
            'container_type' => '20DC',
            'unit_quantity' => 1,
            'status' => Project::STATUS_DESIGN_REVIEW,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-drawing')
            ->set('manualDrawingForm.project_id', (string) $project->id)
            ->set('manualDrawingForm.revision_number', 'REV-2')
            ->set('manualDrawingForm.drawing_title', 'Coffee kiosk elevation')
            ->set('manualDrawingForm.status', ProjectDrawing::STATUS_SUBMITTED)
            ->set('manualDrawingForm.submitted_at', now()->format('Y-m-d\TH:i'))
            ->call('addManualDrawing')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'drawings')
            ->assertSee('Drawing added.')
            ->set('activeTab', 'manual-delivery')
            ->set('manualDeliveryForm.project_id', (string) $project->id)
            ->set('manualDeliveryForm.milestone_label', 'Site delivery confirmed')
            ->set('manualDeliveryForm.sequence', '90')
            ->set('manualDeliveryForm.planned_date', now()->addDays(21)->toDateString())
            ->set('manualDeliveryForm.status', ProjectDeliveryMilestone::STATUS_SCHEDULED)
            ->set('manualDeliveryForm.site_location', 'Dubai Design District')
            ->set('manualDeliveryForm.requires_crane', true)
            ->set('manualDeliveryForm.installation_required', true)
            ->call('addManualDelivery')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'delivery_tracking')
            ->assertSee('Delivery milestone added.');

        $this->assertDatabaseHas('project_drawings', [
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'revision_number' => 'REV-2',
            'drawing_title' => 'Coffee kiosk elevation',
            'status' => ProjectDrawing::STATUS_SUBMITTED,
        ]);

        $this->assertDatabaseHas('project_delivery_milestones', [
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'milestone_label' => 'Site delivery confirmed',
            'status' => ProjectDeliveryMilestone::STATUS_SCHEDULED,
            'site_location' => 'Dubai Design District',
            'requires_crane' => true,
            'installation_required' => true,
        ]);
    }

    public function test_shipment_popup_supports_milestones_documents_and_timeline(): void
    {
        $company = Company::create([
            'name' => 'Execution Marine',
            'slug' => 'execution-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Execution Workspace',
            'slug' => 'execution-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Operations']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-EXEC-1',
            'company_name' => 'Execution Harbor Co',
            'service_mode' => 'Ocean Freight',
            'status' => ShipmentJob::STATUS_BOOKING_REQUESTED,
            'currency' => 'AED',
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'shipments')
            ->call('selectShipment', $shipment->id)
            ->assertSee('Operational timeline')
            ->assertSee('Booking Requested')
            ->assertSee('Booking Confirmation')
            ->set('shipmentMilestoneForm.label', 'Cargo Received At Origin')
            ->set('shipmentMilestoneForm.status', ShipmentMilestone::STATUS_IN_PROGRESS)
            ->call('addShipmentMilestone')
            ->assertSee('Cargo Received At Origin')
            ->set('shipmentDocumentForm.document_type', ShipmentDocument::TYPE_CUSTOMS)
            ->set('shipmentDocumentForm.document_name', 'Import Customs Entry')
            ->set('shipmentDocumentForm.reference_number', 'CUS-7781')
            ->set('shipmentDocumentForm.status', ShipmentDocument::STATUS_RECEIVED)
            ->call('addShipmentDocument')
            ->assertSee('Import Customs Entry');

        $this->assertDatabaseHas('shipment_milestones', [
            'shipment_job_id' => $shipment->id,
            'label' => 'Cargo Received At Origin',
            'status' => ShipmentMilestone::STATUS_IN_PROGRESS,
        ]);

        $this->assertDatabaseHas('shipment_documents', [
            'shipment_job_id' => $shipment->id,
            'document_name' => 'Import Customs Entry',
            'reference_number' => 'CUS-7781',
            'status' => ShipmentDocument::STATUS_RECEIVED,
        ]);
    }

    public function test_freight_forwarder_workspace_can_create_job_costing_and_update_the_shipment_margin(): void
    {
        $company = Company::create([
            'name' => 'Costing Marine',
            'slug' => 'costing-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Costing Workspace',
            'slug' => 'costing-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-COST-1',
            'company_name' => 'Harbor Costing Co',
            'service_mode' => 'Ocean Freight',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_DRAFT,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-costing')
            ->set('manualCostingForm.shipment_job_id', (string) $shipment->id)
            ->assertSet('manualCostingForm.customer_name', 'Harbor Costing Co')
            ->assertSet('manualCostingForm.service_mode', 'Ocean Freight')
            ->set('manualCostingForm.lines', [
                [
                    'line_type' => 'Cost',
                    'charge_code' => 'FRT-BUY',
                    'description' => 'Carrier buy rate',
                    'vendor_name' => 'Ocean Carrier',
                    'quantity' => '1',
                    'unit_amount' => '1200',
                    'is_billable' => true,
                    'notes' => '',
                ],
                [
                    'line_type' => 'Revenue',
                    'charge_code' => 'FRT-SELL',
                    'description' => 'Customer sell rate',
                    'vendor_name' => '',
                    'quantity' => '1',
                    'unit_amount' => '1800',
                    'is_billable' => true,
                    'notes' => '',
                ],
            ])
            ->call('addManualCosting')
            ->assertSet('activeTab', 'costings')
            ->assertSee('Job costing added.');

        $this->assertDatabaseHas('job_costings', [
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'customer_name' => 'Harbor Costing Co',
            'total_cost_amount' => 1200,
            'total_sell_amount' => 1800,
            'margin_amount' => 600,
        ]);

        $this->assertDatabaseHas('shipment_jobs', [
            'id' => $shipment->id,
            'buy_amount' => 1200,
            'sell_amount' => 1800,
            'margin_amount' => 600,
        ]);
    }

    public function test_selecting_a_shipment_autofills_the_manual_invoice_form_from_the_latest_costing(): void
    {
        $company = Company::create([
            'name' => 'Invoice Marine',
            'slug' => 'invoice-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Invoice Workspace',
            'slug' => 'invoice-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-INV-1',
            'company_name' => 'Invoice Harbor Co',
            'contact_email' => 'billing@invoice.test',
            'currency' => 'AED',
            'sell_amount' => 2500,
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);

        $costing = JobCosting::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'costing_number' => 'JC-00001',
            'customer_name' => 'Invoice Harbor Co',
            'currency' => 'AED',
            'total_cost_amount' => 1800,
            'total_sell_amount' => 2500,
            'margin_amount' => 700,
            'margin_percent' => 28,
            'status' => JobCosting::STATUS_READY_TO_INVOICE,
        ]);

        JobCostingLine::create([
            'job_costing_id' => $costing->id,
            'line_type' => JobCostingLine::TYPE_REVENUE,
            'charge_code' => 'FRT-SELL',
            'description' => 'Ocean freight sell rate',
            'quantity' => 1,
            'unit_amount' => 2500,
            'total_amount' => 2500,
            'is_billable' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-invoice')
            ->set('manualInvoiceForm.shipment_job_id', (string) $shipment->id)
            ->assertSet('manualInvoiceForm.job_costing_id', (string) $costing->id)
            ->assertSet('manualInvoiceForm.bill_to_name', 'Invoice Harbor Co')
            ->assertSet('manualInvoiceForm.contact_email', 'billing@invoice.test')
            ->assertSet('manualInvoiceForm.subtotal_amount', '2500')
            ->assertSet('manualInvoiceForm.lines.0.description', 'Ocean freight sell rate')
            ->set('manualInvoiceForm.invoice_type', Invoice::TYPE_ACCOUNTS_RECEIVABLE)
            ->set('manualInvoiceForm.issue_date', now()->toDateString())
            ->set('manualInvoiceForm.due_date', now()->addDays(14)->toDateString())
            ->set('manualInvoiceForm.tax_amount', '125')
            ->call('addManualInvoice')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'invoices')
            ->assertSee('Invoice added.');

        $this->assertDatabaseHas('invoices', [
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'job_costing_id' => $costing->id,
            'bill_to_name' => 'Invoice Harbor Co',
            'subtotal_amount' => 2500,
            'tax_amount' => 125,
            'total_amount' => 2625,
            'balance_amount' => 2625,
        ]);
    }

    public function test_selecting_a_booking_autofills_the_manual_invoice_form_and_links_the_invoice(): void
    {
        $company = Company::create([
            'name' => 'Booking Invoice Marine',
            'slug' => 'booking-invoice-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Booking Invoice Workspace',
            'slug' => 'booking-invoice-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Finance']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-BKG-INV-1',
            'company_name' => 'Harbor Billing Co',
            'contact_email' => 'accounts@harbor.test',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);

        $booking = Booking::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'booking_number' => 'BK-INV-1',
            'customer_name' => 'Harbor Billing Co',
            'contact_email' => 'booking@harbor.test',
            'service_mode' => 'Ocean Freight',
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $costing = JobCosting::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'costing_number' => 'JC-BKG-1',
            'customer_name' => 'Harbor Billing Co',
            'currency' => 'AED',
            'total_cost_amount' => 1200,
            'total_sell_amount' => 1600,
            'margin_amount' => 400,
            'status' => JobCosting::STATUS_READY_TO_INVOICE,
        ]);

        JobCostingLine::create([
            'job_costing_id' => $costing->id,
            'line_type' => JobCostingLine::TYPE_REVENUE,
            'charge_code' => 'FRT',
            'description' => 'Freight charge',
            'quantity' => 1,
            'unit_amount' => 1600,
            'total_amount' => 1600,
            'is_billable' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-invoice')
            ->set('manualInvoiceForm.booking_id', (string) $booking->id)
            ->assertSet('manualInvoiceForm.shipment_job_id', (string) $shipment->id)
            ->assertSet('manualInvoiceForm.job_costing_id', (string) $costing->id)
            ->assertSet('manualInvoiceForm.bill_to_name', 'Harbor Billing Co')
            ->assertSet('manualInvoiceForm.contact_email', 'booking@harbor.test')
            ->assertSet('manualInvoiceForm.lines.0.description', 'Freight charge')
            ->set('manualInvoiceForm.invoice_type', Invoice::TYPE_ACCOUNTS_RECEIVABLE)
            ->set('manualInvoiceForm.issue_date', now()->toDateString())
            ->set('manualInvoiceForm.due_date', now()->addDays(14)->toDateString())
            ->call('addManualInvoice')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'workspace_id' => $workspace->id,
            'booking_id' => $booking->id,
            'shipment_job_id' => $shipment->id,
            'job_costing_id' => $costing->id,
            'bill_to_name' => 'Harbor Billing Co',
            'contact_email' => 'booking@harbor.test',
        ]);
    }

    public function test_invoice_list_can_be_filtered_by_booking(): void
    {
        $company = Company::create([
            'name' => 'Booking Filter Marine',
            'slug' => 'booking-filter-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Booking Filter Workspace',
            'slug' => 'booking-filter-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Finance']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-FILTER-1',
            'company_name' => 'Filter Shipping',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);

        $bookingA = Booking::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'booking_number' => 'BK-FLT-A',
            'customer_name' => 'Filter Shipping',
            'service_mode' => 'Ocean Freight',
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $bookingB = Booking::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'booking_number' => 'BK-FLT-B',
            'customer_name' => 'Other Shipping',
            'service_mode' => 'Ocean Freight',
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $invoiceForShipment = Invoice::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'booking_id' => $bookingA->id,
            'invoice_number' => 'AR-FLT-1',
            'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => 'Filter Shipping',
            'currency' => 'AED',
            'subtotal_amount' => 1000,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'booking_id' => $bookingB->id,
            'invoice_number' => 'AR-FLT-2',
            'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => 'Other Shipping',
            'currency' => 'AED',
            'subtotal_amount' => 500,
            'tax_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 0,
            'balance_amount' => 500,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'invoices')
            ->set('invoiceBookingFilter', (string) $bookingA->id)
            ->assertSee('AR-FLT-1')
            ->assertDontSee('AR-FLT-2')
            ->call('selectInvoice', $invoiceForShipment->id)
            ->assertSee('BK-FLT-A');
    }

    public function test_booking_popup_shows_related_invoices(): void
    {
        $company = Company::create([
            'name' => 'Booking Popup Marine',
            'slug' => 'booking-popup-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Booking Popup Workspace',
            'slug' => 'booking-popup-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Operations']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-POPUP-1',
            'company_name' => 'Popup Shipping',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);

        $booking = Booking::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'booking_number' => 'BK-POP-1',
            'customer_name' => 'Popup Shipping',
            'service_mode' => 'Ocean Freight',
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'booking_id' => $booking->id,
            'invoice_number' => 'AR-POP-1',
            'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => 'Popup Shipping',
            'currency' => 'AED',
            'subtotal_amount' => 900,
            'tax_amount' => 0,
            'total_amount' => 900,
            'paid_amount' => 0,
            'balance_amount' => 900,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'bookings')
            ->call('selectBooking', $booking->id)
            ->assertSee('Related invoices')
            ->assertSee('AR-POP-1');
    }

    public function test_posting_an_invoice_marks_it_as_posted_and_finalizes_the_linked_costing(): void
    {
        $company = Company::create([
            'name' => 'Posting Marine',
            'slug' => 'posting-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Posting Workspace',
            'slug' => 'posting-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Finance']);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-POST-1',
            'company_name' => 'Posting Harbor Co',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);

        $costing = JobCosting::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'costing_number' => 'JC-POST-1',
            'customer_name' => 'Posting Harbor Co',
            'currency' => 'AED',
            'status' => JobCosting::STATUS_READY_TO_INVOICE,
        ]);

        $costingLine = JobCostingLine::create([
            'job_costing_id' => $costing->id,
            'line_type' => JobCostingLine::TYPE_REVENUE,
            'charge_code' => 'DOC',
            'description' => 'Documentation fee',
            'quantity' => 1,
            'unit_amount' => 850,
            'total_amount' => 850,
            'is_billable' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'job_costing_id' => $costing->id,
            'invoice_number' => 'AR-POST-1',
            'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => 'Posting Harbor Co',
            'currency' => 'AED',
            'subtotal_amount' => 850,
            'tax_amount' => 0,
            'total_amount' => 850,
            'paid_amount' => 0,
            'balance_amount' => 850,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $invoice->lines()->create([
            'job_costing_line_id' => $costingLine->id,
            'charge_code' => 'DOC',
            'description' => 'Documentation fee',
            'quantity' => 1,
            'unit_amount' => 850,
            'total_amount' => 850,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'invoices')
            ->call('postInvoice', $invoice->id)
            ->assertSee('posted');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_SENT,
            'posted_by_user_id' => $user->id,
        ]);

        $this->assertNotNull($invoice->fresh()->posted_at);

        $this->assertDatabaseHas('job_costings', [
            'id' => $costing->id,
            'status' => JobCosting::STATUS_FINALIZED,
        ]);
    }

    public function test_disqualified_lead_requires_a_reason_before_status_is_saved(): void
    {
        $company = Company::create([
            'name' => 'Disqualify Marine',
            'slug' => 'disqualify-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Disqualify Workspace',
            'slug' => 'disqualify-workspace',
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
            'external_key' => 'lead-disq-1',
            'lead_id' => 'LD-D-1',
            'contact_name' => 'Nadia Salem',
            'company_name' => 'Anchor Gulf Shipping',
            'email' => 'nadia@example.com',
            'lead_source' => 'Website Quote Form',
            'service' => 'Freight Services',
            'status' => Lead::STATUS_IN_PROGRESS,
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'leads')
            ->call('updateLeadStatus', $lead->id, Lead::STATUS_DISQUALIFIED)
            ->assertSet('pendingDisqualificationLeadId', $lead->id)
            ->call('saveDisqualificationReason', $lead->id, '')
            ->assertHasErrors(['reason'])
            ->call('saveDisqualificationReason', $lead->id, Lead::DISQUALIFICATION_REASON_MISMATCH)
            ->assertSet('pendingDisqualificationLeadId', null);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => Lead::STATUS_DISQUALIFIED,
            'disqualification_reason' => Lead::DISQUALIFICATION_REASON_MISMATCH,
        ]);

        $this->assertDatabaseHas('lead_status_logs', [
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'from_status' => Lead::STATUS_IN_PROGRESS,
            'to_status' => Lead::STATUS_DISQUALIFIED,
            'note' => Lead::DISQUALIFICATION_REASON_MISMATCH,
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
            ->set('activeTab', 'settings')
            ->assertSee('User Access')
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

    public function test_workspace_owner_can_customize_workspace_vocabulary_from_settings_tab(): void
    {
        $company = Company::create([
            'name' => 'Config Marine',
            'slug' => 'config-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Config Workspace',
            'slug' => 'config-workspace',
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

        Livewire::test(CrmDashboard::class)
            ->assertSee('Settings')
            ->set('activeTab', 'settings')
            ->set('settingsTab', 'general')
            ->set('workspaceSettingsForm.lead_status_labels.'.Lead::STATUS_IN_PROGRESS, 'Working')
            ->set('workspaceSettingsForm.opportunity_stage_labels.'.Opportunity::STAGE_INITIAL_CONTACT, 'New Deal')
            ->set('workspaceSettingsForm.disqualification_reasons', ['Geo Limits', 'Duplicate Inquiry'])
            ->set('workspaceSettingsForm.lead_sources', ['Email', 'Meta Ads', 'Partner Portal'])
            ->set('workspaceSettingsForm.lead_services', ['Marine Freight', 'Port Services'])
            ->call('saveWorkspaceSettings')
            ->assertHasNoErrors();

        $workspace = $workspace->fresh();

        $this->assertSame(
            'Working',
            data_get($workspace->settings, 'crm_vocabulary.lead_status_labels.'.Lead::STATUS_IN_PROGRESS)
        );
        $this->assertSame(
            'New Deal',
            data_get($workspace->settings, 'crm_vocabulary.opportunity_stage_labels.'.Opportunity::STAGE_INITIAL_CONTACT)
        );
        $this->assertSame(
            ['Geo Limits', 'Duplicate Inquiry'],
            data_get($workspace->settings, 'crm_vocabulary.disqualification_reasons')
        );
        $this->assertSame(
            ['Email', 'Meta Ads', 'Partner Portal'],
            data_get($workspace->settings, 'crm_vocabulary.lead_sources')
        );
        $this->assertSame(
            ['Marine Freight', 'Port Services'],
            data_get($workspace->settings, 'crm_vocabulary.lead_services')
        );

        Livewire::test(CrmDashboard::class)
            ->assertSee('Working')
            ->assertSee('Meta Ads')
            ->assertSee('Marine Freight');
    }

    public function test_workspace_owner_can_switch_workspace_mode(): void
    {
        $company = Company::create([
            'name' => 'Template Marine',
            'slug' => 'template-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Template Workspace',
            'slug' => 'template-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'general_maritime'),
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

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'settings')
            ->set('settingsTab', 'general')
            ->set('workspaceSettingsForm.template_key', 'ship_chandling')
            ->call('saveWorkspaceSettings')
            ->assertHasNoErrors()
            ->assertSee('General Maritime')
            ->assertSee('Workspace mode is locked after creation');

        $workspace = $workspace->fresh();

        $this->assertSame('general_maritime', $workspace->templateKey());
        $this->assertNotContains('vessel_calls', $workspace->templateModules());
        $this->assertSame('Initial Contact', $workspace->opportunityStageLabels()[Opportunity::STAGE_INITIAL_CONTACT]);
    }

    public function test_freight_forwarding_mode_shows_activated_module_tabs(): void
    {
        $company = Company::create([
            'name' => 'Forwarding Marine',
            'slug' => 'forwarding-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Forwarding Workspace',
            'slug' => 'forwarding-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
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

        Livewire::test(CrmDashboard::class)
            ->assertSee('Forwarding Workspace Dashboard')
            ->assertSee('Rates')
            ->assertSee('Quotes')
            ->assertSee('Shipments')
            ->assertSee('Carriers')
            ->assertSee('Reports')
            ->call('openTemplateModule', 'quotes')
            ->assertSet('activeTab', 'quotes')
            ->assertSee('Quote List')
            ->assertSee('New Quote')
            ->assertSee('No quotes created yet for this workspace.');
    }

    public function test_freight_forwarder_workspace_can_create_a_quote(): void
    {
        $company = Company::create([
            'name' => 'Forwarding Marine',
            'slug' => 'forwarding-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Forwarding Workspace',
            'slug' => 'forwarding-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
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

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-quote')
            ->set('manualQuoteForm.company_name', 'Oceanic Traders')
            ->set('manualQuoteForm.contact_name', 'Lina Noor')
            ->set('manualQuoteForm.contact_email', 'lina@example.com')
            ->set('manualQuoteForm.service_mode', 'Ocean Freight')
            ->set('manualQuoteForm.origin', 'Jebel Ali')
            ->set('manualQuoteForm.destination', 'Hamburg')
            ->set('manualQuoteForm.buy_amount', '8200')
            ->set('manualQuoteForm.sell_amount', '9500')
            ->call('addManualQuote')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'quotes')
            ->assertSee('QT-00001')
            ->assertSee('Oceanic Traders');

        $quote = Quote::query()->where('workspace_id', $workspace->id)->first();

        $this->assertNotNull($quote);
        $this->assertSame('QT-00001', $quote->quote_number);
        $this->assertSame('Oceanic Traders', $quote->company_name);
        $this->assertSame('Ocean Freight', $quote->service_mode);
        $this->assertSame('1300.00', $quote->margin_amount);
    }

    public function test_freight_forwarder_workspace_can_create_a_rate_card(): void
    {
        $company = Company::create([
            'name' => 'Rates Marine',
            'slug' => 'rates-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Rates Workspace',
            'slug' => 'rates-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
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

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-rate')
            ->set('manualRateForm.customer_name', 'Oceanic Traders')
            ->set('manualRateForm.service_mode', RateCard::MODE_OCEAN)
            ->set('manualRateForm.origin', 'Jebel Ali')
            ->set('manualRateForm.destination', 'Hamburg')
            ->set('manualRateForm.transit_days', '18')
            ->set('manualRateForm.buy_amount', '8200')
            ->set('manualRateForm.sell_amount', '9500')
            ->set('manualRateForm.currency', 'AED')
            ->set('manualRateForm.valid_until', now()->addDays(14)->format('Y-m-d'))
            ->set('manualRateForm.is_active', true)
            ->call('addManualRate')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'rates')
            ->assertSee('RT-00001')
            ->assertSee('Oceanic Traders');

        $this->assertDatabaseHas('rate_cards', [
            'workspace_id' => $workspace->id,
            'rate_code' => 'RT-00001',
            'customer_name' => 'Oceanic Traders',
            'service_mode' => RateCard::MODE_OCEAN,
            'origin' => 'Jebel Ali',
            'destination' => 'Hamburg',
            'currency' => 'AED',
            'is_active' => true,
        ]);
    }

    public function test_selecting_customer_and_opportunity_autofills_the_manual_quote_form(): void
    {
        $company = Company::create([
            'name' => 'Quote Autofill Marine',
            'slug' => 'quote-autofill-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Quote Autofill Workspace',
            'slug' => 'quote-autofill-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'external_key' => 'lead-auto-quote-1',
            'lead_id' => 'LD-AUTO-Q-1',
            'contact_name' => 'Lina Noor',
            'company_name' => 'Oceanic Traders',
            'email' => 'lina@example.com',
            'service' => 'Ocean Freight',
            'status' => Lead::STATUS_SALES_QUALIFIED,
            'submission_date' => now()->subDays(3),
        ]);

        $opportunity = Opportunity::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'lead_id' => $lead->id,
            'external_key' => 'opp-auto-quote-1',
            'company_name' => 'Oceanic Traders',
            'contact_email' => 'pricing@oceanic.test',
            'lead_source' => 'Website Quote Form',
            'required_service' => 'Ocean Freight',
            'revenue_potential' => 9500,
            'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
            'notes' => 'Customer asked for fastest routing option.',
            'submission_date' => now()->subDay(),
        ]);

        $accountId = (string) $opportunity->fresh()->account_id;

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-quote')
            ->set('manualQuoteForm.customer_record_id', (string) $opportunity->id)
            ->assertSet('manualQuoteForm.opportunity_id', '')
            ->assertSet('manualQuoteForm.customer_record_id', $accountId)
            ->assertSet('manualQuoteForm.lead_id', '')
            ->assertSet('manualQuoteForm.company_name', 'Oceanic Traders')
            ->assertSet('manualQuoteForm.contact_name', 'Lina Noor')
            ->assertSet('manualQuoteForm.contact_email', 'lina@example.com')
            ->assertSet('manualQuoteForm.service_mode', 'Ocean Freight')
            ->set('manualQuoteForm.opportunity_id', (string) $opportunity->id)
            ->assertSet('manualQuoteForm.customer_record_id', $accountId)
            ->assertSet('manualQuoteForm.lead_id', (string) $lead->id)
            ->assertSet('manualQuoteForm.sell_amount', '9500.00')
            ->assertSet('manualQuoteForm.contact_email', 'pricing@oceanic.test')
            ->assertSet('manualQuoteForm.notes', 'Customer asked for fastest routing option.');
    }

    public function test_selecting_a_rate_card_autofills_the_manual_quote_form(): void
    {
        $company = Company::create([
            'name' => 'Quote Rate Marine',
            'slug' => 'quote-rate-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Quote Rate Workspace',
            'slug' => 'quote-rate-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Sales']);

        $rateCard = RateCard::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'rate_code' => 'RT-00001',
            'customer_name' => 'Oceanic Traders',
            'service_mode' => RateCard::MODE_OCEAN,
            'origin' => 'Jebel Ali',
            'destination' => 'Rotterdam',
            'incoterm' => 'FOB',
            'commodity' => 'General Cargo',
            'equipment_type' => '40HC',
            'transit_days' => 21,
            'buy_amount' => 7800,
            'sell_amount' => 9300,
            'currency' => 'AED',
            'valid_until' => now()->addDays(20),
            'is_active' => true,
            'assigned_user_id' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-quote')
            ->set('manualQuoteForm.rate_card_id', (string) $rateCard->id)
            ->assertSet('manualQuoteForm.service_mode', RateCard::MODE_OCEAN)
            ->assertSet('manualQuoteForm.origin', 'Jebel Ali')
            ->assertSet('manualQuoteForm.destination', 'Rotterdam')
            ->assertSet('manualQuoteForm.incoterm', 'FOB')
            ->assertSet('manualQuoteForm.commodity', 'General Cargo')
            ->assertSet('manualQuoteForm.equipment_type', '40HC')
            ->assertSet('manualQuoteForm.buy_amount', '7800.00')
            ->assertSet('manualQuoteForm.sell_amount', '9300.00')
            ->assertSet('manualQuoteForm.currency', 'AED')
            ->assertSee('Optional linked rate card');
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
            ->assertDontSee('Add workspace user')
            ->assertDontSee('Create permission')
            ->set('activeTab', 'access')
            ->assertSet('activeTab', 'leads')
            ->set('activeTab', 'settings')
            ->assertSet('activeTab', 'settings')
            ->assertSee('Notification channels')
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

    public function test_workspace_owner_can_export_leads_csv(): void
    {
        $company = Company::create([
            'name' => 'Export Marine',
            'slug' => 'export-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Export Workspace',
            'slug' => 'export-workspace',
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

        Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $user->id,
            'external_key' => 'lead-export-1',
            'lead_id' => 'LD-EXP-1',
            'contact_name' => 'Amina Noor',
            'company_name' => 'Tide Cargo',
            'email' => 'amina@example.com',
            'phone' => '971500000111',
            'service' => 'Freight Services',
            'lead_source' => 'Email',
            'status' => Lead::STATUS_IN_PROGRESS,
            'lead_value' => 100000,
            'submission_date' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->assertSee('Export CSV')
            ->call('exportLeadsCsv')
            ->assertFileDownloaded();
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

    public function test_freight_forwarder_workspace_can_create_a_carrier(): void
    {
        $company = Company::create([
            'name' => 'Carrier Marine',
            'slug' => 'carrier-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Carrier Workspace',
            'slug' => 'carrier-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Manager role', 'level' => 4],
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $user->attachRole($managerRole);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-carrier')
            ->set('manualCarrierForm.name', 'Maersk')
            ->set('manualCarrierForm.mode', Carrier::MODE_OCEAN)
            ->set('manualCarrierForm.code', 'MSK')
            ->set('manualCarrierForm.scac_code', 'MAEU')
            ->set('manualCarrierForm.contact_name', 'Lina Noor')
            ->set('manualCarrierForm.contact_email', 'lina@maersk.test')
            ->set('manualCarrierForm.service_lanes', 'Jebel Ali -> Rotterdam')
            ->call('addManualCarrier')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'carriers');

        $this->assertDatabaseHas('carriers', [
            'workspace_id' => $workspace->id,
            'name' => 'Maersk',
            'mode' => Carrier::MODE_OCEAN,
            'scac_code' => 'MAEU',
        ]);
    }

    public function test_selecting_a_shipment_autofills_the_manual_booking_form_and_booking_updates_the_shipment(): void
    {
        $company = Company::create([
            'name' => 'Booking Marine',
            'slug' => 'booking-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Booking Workspace',
            'slug' => 'booking-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Manager role', 'level' => 4],
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $user->attachRole($managerRole);

        $carrier = Carrier::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'name' => 'MSC',
            'mode' => Carrier::MODE_OCEAN,
            'is_active' => true,
        ]);

        $shipment = ShipmentJob::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'job_number' => 'SJ-BOOK-001',
            'company_name' => 'Oceanic Traders',
            'contact_name' => 'Lina Noor',
            'contact_email' => 'lina@example.com',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Hamburg',
            'estimated_departure_at' => now()->addDays(4),
            'estimated_arrival_at' => now()->addDays(19),
            'status' => ShipmentJob::STATUS_DRAFT,
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('activeTab', 'manual-booking')
            ->assertSet('manualBookingForm.customer_name', '')
            ->set('manualBookingForm.shipment_job_id', (string) $shipment->id)
            ->assertSet('manualBookingForm.customer_name', 'Oceanic Traders')
            ->assertSet('manualBookingForm.origin', 'Jebel Ali')
            ->assertSet('manualBookingForm.destination', 'Hamburg')
            ->set('manualBookingForm.carrier_id', (string) $carrier->id)
            ->set('manualBookingForm.status', Booking::STATUS_CONFIRMED)
            ->set('manualBookingForm.confirmed_etd', now()->addDays(5)->format('Y-m-d\\TH:i'))
            ->set('manualBookingForm.confirmed_eta', now()->addDays(20)->format('Y-m-d\\TH:i'))
            ->call('addManualBooking')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'bookings');

        $this->assertDatabaseHas('bookings', [
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'carrier_id' => $carrier->id,
            'customer_name' => 'Oceanic Traders',
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $this->assertDatabaseHas('shipment_jobs', [
            'id' => $shipment->id,
            'carrier_name' => 'MSC',
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);
    }

    public function test_workspace_users_can_add_collaboration_notes_and_messages_on_a_lead(): void
    {
        $company = Company::create([
            'name' => 'Collab Marine',
            'slug' => 'collab-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Collab Workspace',
            'slug' => 'collab-workspace',
            'is_default' => true,
        ]);

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $owner = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Owner User',
        ]);
        $owner->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $owner->attachRole($salesRole);

        $teammate = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Nadia Saleh',
        ]);
        $teammate->workspaces()->attach($workspace->id, ['job_title' => 'Sales', 'is_owner' => false]);
        $teammate->attachRole($salesRole);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $teammate->id,
            'external_key' => 'lead-collab-001',
            'lead_id' => 'LD-COLLAB-001',
            'contact_name' => 'Ali Noor',
            'company_name' => 'Ocean Star',
            'email' => 'ops@oceanstar.test',
            'service' => 'Ocean Freight',
            'lead_source' => 'Email',
            'status' => Lead::STATUS_IN_PROGRESS,
            'submission_date' => now(),
        ]);

        $this->actingAs($owner);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->call('selectLead', $lead->id)
            ->set('collaborationForms.lead.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.lead.body', 'Customer wants an update before noon.')
            ->call('addCollaborationEntry', 'lead', $lead->id)
            ->set('collaborationForms.lead.type', CollaborationEntry::TYPE_MESSAGE)
            ->set('collaborationForms.lead.recipient_user_id', (string) $teammate->id)
            ->set('collaborationForms.lead.body', 'Please call this lead and confirm cargo dimensions.')
            ->call('addCollaborationEntry', 'lead', $lead->id)
            ->assertHasNoErrors()
            ->assertSee('Team collaboration');

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => Lead::class,
            'notable_id' => $lead->id,
            'entry_type' => CollaborationEntry::TYPE_NOTE,
            'body' => 'Customer wants an update before noon.',
        ]);

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => Lead::class,
            'notable_id' => $lead->id,
            'entry_type' => CollaborationEntry::TYPE_MESSAGE,
            'recipient_user_id' => $teammate->id,
        ]);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_MESSAGE,
            'notable_type' => Lead::class,
            'notable_id' => $lead->id,
        ]);
    }

    public function test_workspace_users_can_reassign_a_quote_and_generate_assignment_notifications(): void
    {
        $company = Company::create([
            'name' => 'Assignment Marine',
            'slug' => 'assignment-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Assignment Workspace',
            'slug' => 'assignment-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $owner = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Owner User',
        ]);
        $owner->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $owner->attachRole($salesRole);

        $salesUser = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Mira Hassan',
        ]);
        $salesUser->workspaces()->attach($workspace->id, ['job_title' => 'Sales', 'is_owner' => false]);
        $salesUser->attachRole($salesRole);

        $quote = Quote::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'quote_number' => 'QT-ASN-001',
            'company_name' => 'Blue Sea Traders',
            'contact_name' => 'Amir Khan',
            'contact_email' => 'amir@bluesea.test',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Singapore',
            'currency' => 'AED',
            'status' => Quote::STATUS_DRAFT,
            'quoted_at' => now(),
        ]);

        $this->actingAs($owner);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->call('selectQuote', $quote->id)
            ->call('updateRecordAssignment', 'quote', $quote->id, (string) $salesUser->id)
            ->assertHasNoErrors()
            ->assertSee('Team collaboration');

        $this->assertSame($salesUser->id, $quote->fresh()->assigned_user_id);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $salesUser->id,
            'notification_type' => WorkspaceNotification::TYPE_ASSIGNMENT,
            'notable_type' => Quote::class,
            'notable_id' => $quote->id,
        ]);
    }

    public function test_notification_preferences_control_in_app_and_email_delivery(): void
    {
        Mail::fake();

        $company = Company::create([
            'name' => 'Delivery Preference Marine',
            'slug' => 'delivery-preference-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Delivery Preference Workspace',
            'slug' => 'delivery-preference-workspace',
            'is_default' => true,
        ]);

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $owner = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Owner User',
        ]);
        $owner->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $owner->attachRole($salesRole);

        $teammate = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Teammate User',
            'email' => 'teammate@example.test',
        ]);
        $teammate->workspaces()->attach($workspace->id, [
            'job_title' => 'Sales',
            'is_owner' => false,
            'notification_preferences' => [
                'channels' => [
                    'in_app' => false,
                    'email' => true,
                ],
                'events' => [
                    'assignment' => true,
                    'note' => false,
                    'message' => false,
                ],
            ],
        ]);
        $teammate->attachRole($salesRole);

        $lead = Lead::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $teammate->id,
            'external_key' => 'lead-delivery-001',
            'lead_id' => 'LD-DEL-001',
            'contact_name' => 'Nour Salem',
            'company_name' => 'Harbor Link',
            'email' => 'nour@harborlink.test',
            'service' => 'Ocean Freight',
            'lead_source' => 'Email',
            'status' => Lead::STATUS_IN_PROGRESS,
            'submission_date' => now(),
        ]);

        $quote = Quote::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'quote_number' => 'QT-DEL-001',
            'company_name' => 'Harbor Link',
            'contact_name' => 'Nour Salem',
            'contact_email' => 'nour@harborlink.test',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Hamburg',
            'currency' => 'AED',
            'status' => Quote::STATUS_DRAFT,
            'quoted_at' => now(),
        ]);

        $this->actingAs($owner);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('collaborationForms.lead.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.lead.body', 'Internal note should stay muted for teammate.')
            ->call('addCollaborationEntry', 'lead', $lead->id)
            ->call('updateRecordAssignment', 'quote', $quote->id, (string) $teammate->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_NOTE,
            'notable_type' => Lead::class,
            'notable_id' => $lead->id,
        ]);

        $this->assertDatabaseMissing('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_ASSIGNMENT,
            'notable_type' => Quote::class,
            'notable_id' => $quote->id,
        ]);

        Mail::assertSent(WorkspaceActivityMail::class, function (WorkspaceActivityMail $mail) use ($teammate): bool {
            return $mail->hasTo($teammate->email) && str_contains($mail->title, 'Assigned to quote');
        });
    }

    public function test_workspace_users_can_collaborate_on_bookings_costings_invoices_contacts_and_customers(): void
    {
        $company = Company::create([
            'name' => 'Extended Collab Marine',
            'slug' => 'extended-collab-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Extended Collab Workspace',
            'slug' => 'extended-collab-workspace',
            'is_default' => true,
        ]);

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales role', 'level' => 3],
        );

        $owner = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Owner User',
        ]);
        $owner->workspaces()->attach($workspace->id, ['job_title' => 'Owner', 'is_owner' => true]);
        $owner->attachRole($salesRole);

        $teammate = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
            'name' => 'Ops Partner',
        ]);
        $teammate->workspaces()->attach($workspace->id, ['job_title' => 'Ops', 'is_owner' => false]);
        $teammate->attachRole($salesRole);

        $customer = Account::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $teammate->id,
            'name' => 'Blue Harbor Freight',
            'slug' => 'blue-harbor-freight',
            'primary_email' => 'ops@blueharbor.test',
            'primary_phone' => '971500000100',
            'latest_service' => 'Ocean Freight',
            'last_activity_at' => now()->subDays(2),
        ]);

        $contact = Contact::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'account_id' => $customer->id,
            'assigned_user_id' => $teammate->id,
            'full_name' => 'Hana Malik',
            'email' => 'hana@blueharbor.test',
            'phone' => '971500000101',
            'job_title' => 'Procurement Lead',
            'last_activity_at' => now()->subDay(),
        ]);

        $booking = Booking::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'account_id' => $customer->id,
            'contact_id' => $contact->id,
            'assigned_user_id' => $teammate->id,
            'booking_number' => 'BK-EXT-001',
            'customer_name' => $customer->name,
            'contact_name' => $contact->full_name,
            'contact_email' => $contact->email,
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Rotterdam',
            'status' => Booking::STATUS_CONFIRMED,
            'requested_etd' => now()->addDays(5),
        ]);

        $costing = JobCosting::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => null,
            'assigned_user_id' => $teammate->id,
            'costing_number' => 'JC-EXT-001',
            'customer_name' => $customer->name,
            'service_mode' => 'Ocean Freight',
            'currency' => 'AED',
            'status' => JobCosting::STATUS_IN_PROGRESS,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'account_id' => $customer->id,
            'contact_id' => $contact->id,
            'booking_id' => $booking->id,
            'job_costing_id' => $costing->id,
            'assigned_user_id' => $teammate->id,
            'invoice_number' => 'INV-EXT-001',
            'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => $customer->name,
            'contact_email' => $contact->email,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'AED',
            'status' => Invoice::STATUS_SENT,
        ]);

        $this->actingAs($owner);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('collaborationForms.contact.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.contact.body', 'Contact wants a revised sailing update.')
            ->call('addCollaborationEntry', 'contact', $contact->id)
            ->set('collaborationForms.customer.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.customer.body', 'Customer asked for a rate review.')
            ->call('addCollaborationEntry', 'customer', $customer->id)
            ->set('collaborationForms.booking.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.booking.body', 'Booking confirmed with carrier.')
            ->call('addCollaborationEntry', 'booking', $booking->id)
            ->set('collaborationForms.costing.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.costing.body', 'Costing finalized for margin review.')
            ->call('addCollaborationEntry', 'costing', $costing->id)
            ->set('collaborationForms.invoice.type', CollaborationEntry::TYPE_NOTE)
            ->set('collaborationForms.invoice.body', 'Invoice has been shared with the customer.')
            ->call('addCollaborationEntry', 'invoice', $invoice->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => Contact::class,
            'notable_id' => $contact->id,
            'entry_type' => CollaborationEntry::TYPE_NOTE,
            'body' => 'Contact wants a revised sailing update.',
        ]);

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => Account::class,
            'notable_id' => $customer->id,
            'entry_type' => CollaborationEntry::TYPE_NOTE,
            'body' => 'Customer asked for a rate review.',
        ]);

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => Booking::class,
            'notable_id' => $booking->id,
            'entry_type' => CollaborationEntry::TYPE_NOTE,
            'body' => 'Booking confirmed with carrier.',
        ]);

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => JobCosting::class,
            'notable_id' => $costing->id,
            'entry_type' => CollaborationEntry::TYPE_NOTE,
            'body' => 'Costing finalized for margin review.',
        ]);

        $this->assertDatabaseHas('collaboration_entries', [
            'workspace_id' => $workspace->id,
            'notable_type' => Invoice::class,
            'notable_id' => $invoice->id,
            'entry_type' => CollaborationEntry::TYPE_NOTE,
            'body' => 'Invoice has been shared with the customer.',
        ]);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_NOTE,
            'notable_type' => Contact::class,
            'notable_id' => $contact->id,
        ]);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_NOTE,
            'notable_type' => Account::class,
            'notable_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_NOTE,
            'notable_type' => Booking::class,
            'notable_id' => $booking->id,
        ]);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_NOTE,
            'notable_type' => JobCosting::class,
            'notable_id' => $costing->id,
        ]);

        $this->assertDatabaseHas('workspace_notifications', [
            'workspace_id' => $workspace->id,
            'user_id' => $teammate->id,
            'notification_type' => WorkspaceNotification::TYPE_NOTE,
            'notable_type' => Invoice::class,
            'notable_id' => $invoice->id,
        ]);
    }
}
