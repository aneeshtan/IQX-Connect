<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatusLog;
use App\Models\MonthlyReport;
use App\Models\Opportunity;
use App\Models\SheetSource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use jeremykenedy\LaravelRoles\Models\Role;

class CrmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Platform administrator', 'level' => 9],
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Workspace manager', 'level' => 6],
        );

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales operator', 'level' => 3],
        );

        $analystRole = Role::firstOrCreate(
            ['slug' => 'analyst'],
            ['name' => 'Analyst', 'description' => 'Reporting-only user', 'level' => 2],
        );

        $company = Company::query()
            ->whereIn('slug', ['greenbox', 'blueharbor-marine', 'iqx-connect-demo'])
            ->orWhereIn('name', ['GreenBox', 'BlueHarbor Marine', 'IQX Connect Demo'])
            ->first();

        if ($company) {
            $company->forceFill([
                'slug' => 'greenbox',
                'name' => 'GreenBox',
                'industry' => 'Maritime',
                'contact_email' => 'ops@greenbox.test',
                'contact_phone' => '+971 50 123 4567',
                'timezone' => 'Asia/Dubai',
                'is_active' => true,
            ])->save();
        } else {
            $company = Company::create([
                'slug' => 'greenbox',
                'name' => 'GreenBox',
                'industry' => 'Maritime',
                'contact_email' => 'ops@greenbox.test',
                'contact_phone' => '+971 50 123 4567',
                'timezone' => 'Asia/Dubai',
                'is_active' => true,
            ]);
        }

        $workspace = Workspace::firstOrCreate(
            ['company_id' => $company->id, 'slug' => 'main-operations'],
            [
                'name' => 'Main Operations',
                'description' => 'Primary maritime sales workspace',
                'is_default' => true,
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@iqxconnect.test'],
            [
                'company_id' => $company->id,
                'default_workspace_id' => $workspace->id,
                'name' => 'Platform Admin',
                'job_title' => 'CRM Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@iqxconnect.test'],
            [
                'company_id' => $company->id,
                'default_workspace_id' => $workspace->id,
                'name' => 'Marine Sales Manager',
                'job_title' => 'Sales Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $sales = User::firstOrCreate(
            ['email' => 'sales@iqxconnect.test'],
            [
                'company_id' => $company->id,
                'default_workspace_id' => $workspace->id,
                'name' => 'Lead Operator',
                'job_title' => 'Sales Executive',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $admin->syncRoles([$adminRole]);
        $manager->syncRoles([$managerRole]);
        $sales->syncRoles([$salesRole]);

        $workspace->users()->syncWithoutDetaching([
            $admin->id => ['job_title' => 'CRM Administrator'],
            $manager->id => ['job_title' => 'Sales Manager'],
            $sales->id => ['job_title' => 'Sales Executive'],
        ]);

        $leadSheet = SheetSource::firstOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'type' => SheetSource::TYPE_LEADS, 'name' => 'Leads Sheet'],
            [
                'url' => 'https://docs.google.com/spreadsheets/d/YOUR_LEADS_SHEET_ID/edit#gid=0',
                'description' => 'Published CSV or Google Sheets link for lead ingestion',
                'is_active' => true,
            ],
        );

        $opportunitySheet = SheetSource::firstOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'type' => SheetSource::TYPE_OPPORTUNITIES, 'name' => 'Opportunities Sheet'],
            [
                'url' => 'https://docs.google.com/spreadsheets/d/YOUR_OPPORTUNITIES_SHEET_ID/edit#gid=0',
                'description' => 'Published CSV or Google Sheets link for opportunities',
                'is_active' => true,
            ],
        );

        SheetSource::firstOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'type' => SheetSource::TYPE_REPORTS, 'name' => 'Monthly Reports Sheet'],
            [
                'url' => 'https://docs.google.com/spreadsheets/d/YOUR_REPORTS_SHEET_ID/edit#gid=0',
                'description' => 'Monthly KPI reporting source',
                'is_active' => true,
            ],
        );

        SheetSource::firstOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'type' => SheetSource::TYPE_GOOGLE_ADS, 'name' => 'Google Ads Connector'],
            [
                'url' => 'https://ads.google.com/',
                'description' => 'Placeholder for the later direct Google Ads integration',
                'is_active' => false,
            ],
        );

        $leadOne = Lead::firstOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'demo-lead-1'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSheet->id,
                'assigned_user_id' => $sales->id,
                'lead_id' => 'LD-2001',
                'contact_name' => 'Ahmed Rashid',
                'company_name' => 'Gulf Tide Shipping',
                'email' => 'ahmed@gulftide.example',
                'phone' => '971501112233',
                'service' => 'Container Conversion',
                'submission_date' => now()->subDays(7),
                'lead_source' => 'Google Ads',
                'status' => Lead::STATUS_IN_PROGRESS,
                'lead_value' => 75000,
                'manual_entry' => false,
            ],
        );

        $leadTwo = Lead::firstOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'demo-lead-2'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSheet->id,
                'assigned_user_id' => $manager->id,
                'lead_id' => 'LD-2002',
                'contact_name' => 'Sara Al Nuaimi',
                'company_name' => 'DeepWater Fabrication',
                'email' => 'sara@deepwater.example',
                'phone' => '971507778899',
                'service' => 'Offshore Container Manufacturing',
                'submission_date' => now()->subDays(10),
                'lead_source' => 'Organic',
                'status' => Lead::STATUS_SALES_QUALIFIED,
                'lead_value' => 180000,
                'manual_entry' => false,
            ],
        );

        $leadThree = Lead::firstOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'demo-lead-3'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSheet->id,
                'lead_id' => 'LD-2003',
                'contact_name' => 'Nadia Karim',
                'company_name' => 'Harbor Logistics',
                'email' => 'nadia@harborlogistics.example',
                'phone' => '971509991122',
                'service' => 'Container Trading',
                'submission_date' => now()->subDays(15),
                'lead_source' => 'Email',
                'status' => Lead::STATUS_DISQUALIFIED,
                'disqualification_reason' => 'Mismatch of Needs',
                'manual_entry' => false,
            ],
        );

        Opportunity::firstOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'demo-opportunity-1'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $opportunitySheet->id,
                'lead_id' => $leadTwo->id,
                'assigned_user_id' => $manager->id,
                'rfid' => 'RFID2001',
                'lead_reference' => $leadTwo->lead_id,
                'company_name' => $leadTwo->company_name,
                'contact_email' => $leadTwo->email,
                'lead_source' => $leadTwo->lead_source,
                'required_service' => $leadTwo->service,
                'revenue_potential' => 215000,
                'project_timeline_days' => 30,
                'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
                'submission_date' => now()->subDays(5),
                'year_month' => now()->format('M-y'),
            ],
        );

        Opportunity::firstOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'demo-opportunity-2'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $opportunitySheet->id,
                'assigned_user_id' => $sales->id,
                'rfid' => 'RFID2002',
                'company_name' => 'Ocean Crest Engineering',
                'contact_email' => 'procurement@oceancrest.example',
                'lead_source' => 'Email',
                'required_service' => 'Container Inspection',
                'revenue_potential' => 42000,
                'project_timeline_days' => 12,
                'sales_stage' => Opportunity::STAGE_CLOSED_WON,
                'manual_entry' => true,
                'submission_date' => now()->subDays(2),
                'year_month' => now()->format('M-y'),
            ],
        );

        MonthlyReport::updateOrCreate(
            [
                'company_id' => $company->id,
                'workspace_id' => $workspace->id,
                'year_month' => now()->format('M-y'),
            ],
            [
                'month_start' => now()->startOfMonth(),
                'linkedin_ads_leads' => 6,
                'organic_leads' => 11,
                'email_leads' => 4,
                'google_ads_leads' => 13,
                'total_leads' => 34,
                'linkedin_ads_cost' => 3800,
                'google_ads_cost' => 7200,
                'total_ads_cost' => 11000,
                'cost_per_conversion' => 323.53,
                'total_revenue_potential' => 332000,
                'won_revenue_potential' => 42000,
                'closed_won_count' => 1,
                'closed_lost_count' => 2,
                'proposal_sent_count' => 1,
                'initial_contact_count' => 5,
                'organic_opportunities_count' => 4,
                'total_opportunities_count' => 8,
                'mql_to_sql_rate' => 26.47,
                'sql_conversion_rate' => 12.5,
                'total_revenue_potential_2025' => 332000,
                'won_revenue_potential_2025' => 42000,
                'total_deals_2026' => 0,
                'total_deals_2025' => 1,
                'total_deals_2024' => 0,
                'romi_2025' => 281.82,
            ],
        );

        LeadStatusLog::firstOrCreate(
            [
                'lead_id' => $leadThree->id,
                'to_status' => Lead::STATUS_DISQUALIFIED,
            ],
            [
                'user_id' => $manager->id,
                'from_status' => Lead::STATUS_IN_PROGRESS,
                'change_context' => 'seed',
                'note' => 'Demo disqualification seeded for dashboard history.',
            ],
        );

        Model::reguard();
    }
}
