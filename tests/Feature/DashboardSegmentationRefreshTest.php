<?php

namespace Tests\Feature;

use App\Jobs\RefreshWorkspaceSegmentation;
use App\Livewire\CrmDashboard;
use App\Models\Account;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardSegmentationRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_tab_queues_segmentation_refresh_without_blocking_render(): void
    {
        Bus::fake();

        [$user, $workspace] = $this->workspaceOwner();

        $account = Account::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => $user->id,
            'name' => 'Atlas Shipping',
            'slug' => 'atlas-shipping',
            'primary_email' => 'atlas@example.test',
        ]);

        Opportunity::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'account_id' => $account->id,
            'assigned_user_id' => $user->id,
            'external_key' => 'opp-atlas-1',
            'company_name' => 'Atlas Shipping',
            'contact_email' => 'atlas@example.test',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'submission_date' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->set('activeTab', 'customers')
            ->assertSee('Customer segments are refreshing in the background');

        Bus::assertDispatched(RefreshWorkspaceSegmentation::class, fn (RefreshWorkspaceSegmentation $job) => $job->workspaceId === $workspace->id);
        $this->assertDatabaseCount('account_metric_snapshots', 0);
    }

    public function test_saving_workspace_settings_queues_background_segmentation_refresh(): void
    {
        Bus::fake();

        [$user, $workspace] = $this->workspaceOwner();

        $this->actingAs($user);

        Livewire::test(CrmDashboard::class)
            ->set('workspaceId', $workspace->id)
            ->call('saveWorkspaceSettings')
            ->assertSee("Workspace settings updated for {$workspace->name}. Customer segments are refreshing in the background.");

        Bus::assertDispatched(RefreshWorkspaceSegmentation::class, fn (RefreshWorkspaceSegmentation $job) => $job->workspaceId === $workspace->id);
        $this->assertDatabaseCount('account_metric_snapshots', 0);
    }

    protected function workspaceOwner(): array
    {
        $company = Company::create([
            'name' => 'Segmentation Test Co',
            'slug' => 'segmentation-test-co',
            'industry' => 'Logistics',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Segmentation Workspace',
            'slug' => 'segmentation-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'default_workspace_id' => $workspace->id,
        ]);

        $user->workspaces()->attach($workspace->id, [
            'job_title' => 'Owner',
            'is_owner' => true,
        ]);

        return [$user, $workspace];
    }
}
