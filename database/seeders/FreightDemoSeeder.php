<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JobCosting;
use App\Models\JobCostingLine;
use App\Models\Lead;
use App\Models\LeadStatusLog;
use App\Models\MonthlyReport;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\RateCard;
use App\Models\SheetSource;
use App\Models\ShipmentDocument;
use App\Models\ShipmentJob;
use App\Models\ShipmentMilestone;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use jeremykenedy\LaravelRoles\Models\Role;

class FreightDemoSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Workspace manager', 'level' => 6],
        );

        $salesRole = Role::firstOrCreate(
            ['slug' => 'sales'],
            ['name' => 'Sales', 'description' => 'Sales operator', 'level' => 3],
        );

        $company = Company::updateOrCreate(
            ['slug' => 'iqx-freight-demo'],
            [
                'name' => 'IQX Freight Demo',
                'industry' => 'Freight Forwarding',
                'contact_email' => 'ops@iqxconnect.demo',
                'contact_phone' => '+971 4 555 0101',
                'timezone' => 'Asia/Dubai',
                'is_active' => true,
            ],
        );

        $workspace = Workspace::firstOrCreate(
            ['company_id' => $company->id, 'slug' => 'freight-demo'],
            [
                'name' => 'Freight Demo',
                'description' => 'Freight forwarder demo workspace',
                'is_default' => true,
            ],
        );

        $workspace->forceFill([
            'settings' => Workspace::applyTemplateSettings($workspace->settings, 'freight_forwarding', true),
        ])->save();

        $demoOwner = User::updateOrCreate(
            ['email' => 'demo@iqxconnect.demo'],
            [
                'company_id' => $company->id,
                'default_workspace_id' => $workspace->id,
                'name' => 'IQX Demo Owner',
                'job_title' => 'Workspace Owner',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $demoSales = User::updateOrCreate(
            ['email' => 'sales.demo@iqxconnect.demo'],
            [
                'company_id' => $company->id,
                'default_workspace_id' => $workspace->id,
                'name' => 'Nadia Saleh',
                'job_title' => 'Freight Executive',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $demoOwner->syncRoles([$managerRole]);
        $demoSales->syncRoles([$salesRole]);

        $workspace->users()->syncWithoutDetaching([
            $demoOwner->id => ['job_title' => 'Workspace Owner', 'is_owner' => true],
            $demoSales->id => ['job_title' => 'Freight Executive', 'is_owner' => false],
        ]);

        $leadSource = SheetSource::updateOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'name' => 'Website Leads Feed'],
            [
                'type' => SheetSource::TYPE_LEADS,
                'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
                'url' => 'https://docs.google.com/spreadsheets/d/10e7bFezWnxiVEOtTMsAn5bOS9-2Y33utDoMFFibS-dY/edit?gid=0#gid=0',
                'description' => 'Public lead sheet used for the freight demo workspace',
                'is_active' => true,
                'sync_status' => 'synced',
            ],
        );

        $quoteSource = SheetSource::updateOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'name' => 'CargoWise Quotes API'],
            [
                'type' => SheetSource::TYPE_QUOTES,
                'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
                'url' => 'https://api.demo.iqxconnect.local/cargowise/quotes',
                'description' => 'Demo CargoWise-style quote source',
                'is_active' => true,
                'sync_status' => 'idle',
                'mapping' => [
                    'cargo_auth_mode' => 'bearer',
                    'cargo_format' => 'json',
                    'cargo_data_path' => 'data.rows',
                ],
            ],
        );

        $shipmentSource = SheetSource::updateOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'name' => 'CargoWise Shipments API'],
            [
                'type' => SheetSource::TYPE_SHIPMENTS,
                'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
                'url' => 'https://api.demo.iqxconnect.local/cargowise/shipments',
                'description' => 'Demo CargoWise-style shipment source',
                'is_active' => true,
                'sync_status' => 'idle',
                'mapping' => [
                    'cargo_auth_mode' => 'bearer',
                    'cargo_format' => 'json',
                    'cargo_data_path' => 'data.rows',
                ],
            ],
        );

        $reportSource = SheetSource::updateOrCreate(
            ['company_id' => $company->id, 'workspace_id' => $workspace->id, 'name' => 'Monthly Reports Feed'],
            [
                'type' => SheetSource::TYPE_REPORTS,
                'source_kind' => SheetSource::SOURCE_KIND_UPLOADED_CSV,
                'url' => 'storage/demo/monthly-reports.csv',
                'description' => 'Monthly KPI figures for the freight demo workspace',
                'is_active' => true,
                'sync_status' => 'synced',
            ],
        );

        $carrierOcean = Carrier::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'MSC'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'mode' => Carrier::MODE_OCEAN,
                'code' => 'MSC',
                'scac_code' => 'MSCU',
                'contact_name' => 'Karim Nasser',
                'contact_email' => 'bookings@msc-demo.com',
                'contact_phone' => '+971 4 300 0101',
                'website' => 'https://www.msc.com',
                'service_lanes' => 'Jebel Ali - Rotterdam - Antwerp',
                'notes' => 'Preferred ocean carrier for Europe exports',
                'is_active' => true,
            ],
        );

        $carrierAir = Carrier::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Lufthansa Cargo'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'mode' => Carrier::MODE_AIR,
                'code' => 'LH',
                'iata_code' => 'LH',
                'contact_name' => 'Eva Klein',
                'contact_email' => 'capacity@lhcargo-demo.com',
                'contact_phone' => '+49 69 555 0102',
                'website' => 'https://www.lufthansa-cargo.com',
                'service_lanes' => 'Dubai - Frankfurt - Amsterdam',
                'notes' => 'Used for urgent healthcare and electronics shipments',
                'is_active' => true,
            ],
        );

        $carrierRoad = Carrier::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Regional Haulage'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'mode' => Carrier::MODE_ROAD,
                'code' => 'RHL',
                'contact_name' => 'Fahad Mirza',
                'contact_email' => 'dispatch@regionalhaulage.demo',
                'contact_phone' => '+966 50 999 2221',
                'website' => 'https://regionalhaulage.demo',
                'service_lanes' => 'Jeddah - Riyadh - Dammam',
                'notes' => 'Road feeder and final-mile GCC coverage',
                'is_active' => true,
            ],
        );

        $leadOne = Lead::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-lead-001'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSource->id,
                'assigned_user_id' => $demoSales->id,
                'lead_id' => 'LD-FD-1001',
                'contact_name' => 'Layla Noor',
                'company_name' => 'Northstar Cargo',
                'email' => 'layla@northstar.test',
                'phone' => '+971 50 111 9901',
                'service' => 'Ocean Freight',
                'submission_date' => now()->subDays(18),
                'lead_source' => 'Website Quote Form',
                'status' => Lead::STATUS_SALES_QUALIFIED,
                'lead_value' => 22000,
                'notes' => 'Shipper needs weekly Europe export pricing.',
                'manual_entry' => false,
            ],
        );

        $leadTwo = Lead::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-lead-002'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSource->id,
                'assigned_user_id' => $demoSales->id,
                'lead_id' => 'LD-FD-1002',
                'contact_name' => 'Aisha Rahman',
                'company_name' => 'Medline Gulf',
                'email' => 'aisha@medlinegulf.test',
                'phone' => '+971 56 777 4402',
                'service' => 'Air Freight',
                'submission_date' => now()->subDays(12),
                'lead_source' => 'Google Ads',
                'status' => Lead::STATUS_SALES_QUALIFIED,
                'lead_value' => 18500,
                'notes' => 'Urgent healthcare movement to Germany.',
                'manual_entry' => false,
            ],
        );

        $leadThree = Lead::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-lead-003'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSource->id,
                'assigned_user_id' => $demoSales->id,
                'lead_id' => 'LD-FD-1003',
                'contact_name' => 'Omar Hadi',
                'company_name' => 'Desert Retail Supply',
                'email' => 'omar@desertretail.test',
                'phone' => '+966 55 888 3103',
                'service' => 'Road Freight',
                'submission_date' => now()->subDays(9),
                'lead_source' => 'Referral',
                'status' => Lead::STATUS_IN_PROGRESS,
                'lead_value' => 9600,
                'notes' => 'Weekly GCC distribution enquiry, still confirming lane volume.',
                'manual_entry' => false,
            ],
        );

        $leadFour = Lead::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-lead-004'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $leadSource->id,
                'assigned_user_id' => $demoSales->id,
                'lead_id' => 'LD-FD-1004',
                'contact_name' => 'Hassan Tarek',
                'company_name' => 'BluePort Trading',
                'email' => 'hassan@blueport.test',
                'phone' => '+971 58 333 2299',
                'service' => 'Customs Clearance',
                'submission_date' => now()->subDays(21),
                'lead_source' => 'Email',
                'status' => Lead::STATUS_DISQUALIFIED,
                'disqualification_reason' => 'Mismatch of Needs',
                'lead_value' => 0,
                'notes' => 'Customer needed only destination brokerage in a market not covered.',
                'manual_entry' => false,
            ],
        );

        $opportunityOne = Opportunity::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-opp-001'],
            [
                'company_id' => $company->id,
                'lead_id' => $leadOne->id,
                'sheet_source_id' => $quoteSource->id,
                'assigned_user_id' => $demoSales->id,
                'rfid' => 'FD-RFID-001',
                'lead_reference' => $leadOne->lead_id,
                'company_name' => $leadOne->company_name,
                'contact_email' => 'pricing@northstar.test',
                'lead_source' => $leadOne->lead_source,
                'required_service' => $leadOne->service,
                'revenue_potential' => 22000,
                'project_timeline_days' => 14,
                'sales_stage' => Opportunity::STAGE_CLOSED_WON,
                'notes' => 'Customer accepted weekly FCL booking program.',
                'submission_date' => now()->subDays(16),
                'year_month' => now()->subDays(16)->format('M-y'),
            ],
        );

        $opportunityTwo = Opportunity::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-opp-002'],
            [
                'company_id' => $company->id,
                'lead_id' => $leadTwo->id,
                'sheet_source_id' => $quoteSource->id,
                'assigned_user_id' => $demoSales->id,
                'rfid' => 'FD-RFID-002',
                'lead_reference' => $leadTwo->lead_id,
                'company_name' => $leadTwo->company_name,
                'contact_email' => 'ops@medlinegulf.test',
                'lead_source' => $leadTwo->lead_source,
                'required_service' => $leadTwo->service,
                'revenue_potential' => 18500,
                'project_timeline_days' => 7,
                'sales_stage' => Opportunity::STAGE_PROPOSAL_SENT,
                'notes' => 'Customer comparing two air freight routing options.',
                'submission_date' => now()->subDays(10),
                'year_month' => now()->subDays(10)->format('M-y'),
            ],
        );

        $opportunityThree = Opportunity::updateOrCreate(
            ['workspace_id' => $workspace->id, 'external_key' => 'freight-demo-opp-003'],
            [
                'company_id' => $company->id,
                'lead_id' => $leadThree->id,
                'sheet_source_id' => $quoteSource->id,
                'assigned_user_id' => $demoSales->id,
                'rfid' => 'FD-RFID-003',
                'lead_reference' => $leadThree->lead_id,
                'company_name' => $leadThree->company_name,
                'contact_email' => $leadThree->email,
                'lead_source' => $leadThree->lead_source,
                'required_service' => $leadThree->service,
                'revenue_potential' => 9600,
                'project_timeline_days' => 18,
                'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
                'notes' => 'Awaiting lane volume and pickup window confirmation.',
                'submission_date' => now()->subDays(8),
                'year_month' => now()->subDays(8)->format('M-y'),
            ],
        );

        $rateOcean = RateCard::updateOrCreate(
            ['workspace_id' => $workspace->id, 'rate_code' => 'RT-FD-001'],
            [
                'company_id' => $company->id,
                'carrier_id' => $carrierOcean->id,
                'assigned_user_id' => $demoSales->id,
                'customer_name' => 'Northstar Cargo',
                'service_mode' => 'Ocean Freight',
                'origin' => 'Jebel Ali',
                'destination' => 'Rotterdam',
                'incoterm' => 'FOB',
                'commodity' => 'Industrial Equipment',
                'equipment_type' => '40HC',
                'transit_days' => 24,
                'buy_amount' => 18000,
                'sell_amount' => 22000,
                'margin_amount' => 4000,
                'currency' => 'AED',
                'valid_from' => now()->subDays(20)->toDateString(),
                'valid_until' => now()->addDays(20)->toDateString(),
                'is_active' => true,
                'notes' => 'Weekly FCL allocation from Jebel Ali to Rotterdam.',
            ],
        );

        $rateAir = RateCard::updateOrCreate(
            ['workspace_id' => $workspace->id, 'rate_code' => 'RT-FD-002'],
            [
                'company_id' => $company->id,
                'carrier_id' => $carrierAir->id,
                'assigned_user_id' => $demoSales->id,
                'customer_name' => 'Medline Gulf',
                'service_mode' => 'Air Freight',
                'origin' => 'Dubai',
                'destination' => 'Frankfurt',
                'incoterm' => 'EXW',
                'commodity' => 'Healthcare Supplies',
                'equipment_type' => 'ULD',
                'transit_days' => 2,
                'buy_amount' => 14500,
                'sell_amount' => 18500,
                'margin_amount' => 4000,
                'currency' => 'AED',
                'valid_from' => now()->subDays(14)->toDateString(),
                'valid_until' => now()->addDays(12)->toDateString(),
                'is_active' => true,
                'notes' => 'Priority lift for temperature-sensitive cargo.',
            ],
        );

        $rateRoad = RateCard::updateOrCreate(
            ['workspace_id' => $workspace->id, 'rate_code' => 'RT-FD-003'],
            [
                'company_id' => $company->id,
                'carrier_id' => $carrierRoad->id,
                'assigned_user_id' => $demoSales->id,
                'customer_name' => 'Desert Retail Supply',
                'service_mode' => 'Road Freight',
                'origin' => 'Jeddah',
                'destination' => 'Riyadh',
                'incoterm' => 'DAP',
                'commodity' => 'Retail Fixtures',
                'equipment_type' => 'Curtainsider',
                'transit_days' => 2,
                'buy_amount' => 7600,
                'sell_amount' => 9600,
                'margin_amount' => 2000,
                'currency' => 'AED',
                'valid_from' => now()->subDays(9)->toDateString(),
                'valid_until' => now()->addDays(15)->toDateString(),
                'is_active' => true,
                'notes' => 'GCC road freight demo tariff.',
            ],
        );

        $quoteOne = Quote::updateOrCreate(
            ['workspace_id' => $workspace->id, 'quote_number' => 'QT-FD-001'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $quoteSource->id,
                'rate_card_id' => $rateOcean->id,
                'opportunity_id' => $opportunityOne->id,
                'lead_id' => $leadOne->id,
                'assigned_user_id' => $demoSales->id,
                'company_name' => 'Northstar Cargo',
                'contact_name' => 'Layla Noor',
                'contact_email' => 'pricing@northstar.test',
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
                'margin_amount' => 4000,
                'currency' => 'AED',
                'status' => Quote::STATUS_ACCEPTED,
                'valid_until' => now()->addDays(10)->toDateString(),
                'quoted_at' => now()->subDays(15),
                'notes' => 'Accepted annual account quote.',
            ],
        );

        $quoteTwo = Quote::updateOrCreate(
            ['workspace_id' => $workspace->id, 'quote_number' => 'QT-FD-002'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $quoteSource->id,
                'rate_card_id' => $rateAir->id,
                'opportunity_id' => $opportunityTwo->id,
                'lead_id' => $leadTwo->id,
                'assigned_user_id' => $demoSales->id,
                'company_name' => 'Medline Gulf',
                'contact_name' => 'Aisha Rahman',
                'contact_email' => 'ops@medlinegulf.test',
                'service_mode' => 'Air Freight',
                'origin' => 'Dubai',
                'destination' => 'Frankfurt',
                'incoterm' => 'EXW',
                'commodity' => 'Healthcare Supplies',
                'equipment_type' => 'ULD',
                'weight_kg' => 4200,
                'volume_cbm' => 16.7,
                'buy_amount' => 14500,
                'sell_amount' => 18500,
                'margin_amount' => 4000,
                'currency' => 'AED',
                'status' => Quote::STATUS_SENT,
                'valid_until' => now()->addDays(5)->toDateString(),
                'quoted_at' => now()->subDays(8),
                'notes' => 'Priority service quote sent to customer.',
            ],
        );

        $quoteThree = Quote::updateOrCreate(
            ['workspace_id' => $workspace->id, 'quote_number' => 'QT-FD-003'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $quoteSource->id,
                'rate_card_id' => $rateRoad->id,
                'opportunity_id' => $opportunityThree->id,
                'lead_id' => $leadThree->id,
                'assigned_user_id' => $demoSales->id,
                'company_name' => 'Desert Retail Supply',
                'contact_name' => 'Omar Hadi',
                'contact_email' => 'omar@desertretail.test',
                'service_mode' => 'Road Freight',
                'origin' => 'Jeddah',
                'destination' => 'Riyadh',
                'incoterm' => 'DAP',
                'commodity' => 'Retail Fixtures',
                'equipment_type' => 'Curtainsider',
                'weight_kg' => 9800,
                'volume_cbm' => 31.4,
                'buy_amount' => 7600,
                'sell_amount' => 9600,
                'margin_amount' => 2000,
                'currency' => 'AED',
                'status' => Quote::STATUS_DRAFT,
                'valid_until' => now()->addDays(7)->toDateString(),
                'quoted_at' => now()->subDays(4),
                'notes' => 'Draft quote pending final lane volume.',
            ],
        );

        $shipmentOne = ShipmentJob::updateOrCreate(
            ['workspace_id' => $workspace->id, 'job_number' => 'SJ-FD-001'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'opportunity_id' => $opportunityOne->id,
                'quote_id' => $quoteOne->id,
                'lead_id' => $leadOne->id,
                'assigned_user_id' => $demoSales->id,
                'external_reference' => 'NSC-ROT-2403',
                'company_name' => 'Northstar Cargo',
                'contact_name' => 'Layla Noor',
                'contact_email' => 'ops@northstar.test',
                'service_mode' => 'Ocean Freight',
                'origin' => 'Jebel Ali',
                'destination' => 'Rotterdam',
                'incoterm' => 'FOB',
                'commodity' => 'Industrial Equipment',
                'equipment_type' => '40HC',
                'container_count' => 2,
                'weight_kg' => 18000,
                'volume_cbm' => 55.2,
                'carrier_name' => 'MSC',
                'vessel_name' => 'MSC Horizon',
                'voyage_number' => 'HZ2403',
                'house_bill_no' => 'HBL-FD-001',
                'master_bill_no' => 'MBL-FD-001',
                'estimated_departure_at' => now()->subDays(6),
                'estimated_arrival_at' => now()->addDays(18),
                'actual_departure_at' => now()->subDays(5),
                'status' => ShipmentJob::STATUS_IN_TRANSIT,
                'buy_amount' => 18000,
                'sell_amount' => 22000,
                'margin_amount' => 4000,
                'currency' => 'AED',
                'notes' => 'Weekly export job already sailing.',
            ],
        );

        $shipmentTwo = ShipmentJob::updateOrCreate(
            ['workspace_id' => $workspace->id, 'job_number' => 'SJ-FD-002'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'opportunity_id' => $opportunityTwo->id,
                'quote_id' => $quoteTwo->id,
                'lead_id' => $leadTwo->id,
                'assigned_user_id' => $demoSales->id,
                'external_reference' => 'MDL-FRA-2403',
                'company_name' => 'Medline Gulf',
                'contact_name' => 'Aisha Rahman',
                'contact_email' => 'ops@medlinegulf.test',
                'service_mode' => 'Air Freight',
                'origin' => 'Dubai',
                'destination' => 'Frankfurt',
                'incoterm' => 'EXW',
                'commodity' => 'Healthcare Supplies',
                'equipment_type' => 'ULD',
                'container_count' => 0,
                'weight_kg' => 4200,
                'volume_cbm' => 16.7,
                'carrier_name' => 'Lufthansa Cargo',
                'vessel_name' => 'LH 631',
                'voyage_number' => 'LH631',
                'house_bill_no' => 'HAWB-FD-002',
                'master_bill_no' => 'MAWB-FD-002',
                'estimated_departure_at' => now()->addDay(),
                'estimated_arrival_at' => now()->addDays(3),
                'status' => ShipmentJob::STATUS_BOOKED,
                'buy_amount' => 14500,
                'sell_amount' => 18500,
                'margin_amount' => 4000,
                'currency' => 'AED',
                'notes' => 'Booked, waiting for departure from DXB.',
            ],
        );

        $bookingOne = Booking::updateOrCreate(
            ['workspace_id' => $workspace->id, 'booking_number' => 'BK-FD-001'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'carrier_id' => $carrierOcean->id,
                'shipment_job_id' => $shipmentOne->id,
                'quote_id' => $quoteOne->id,
                'opportunity_id' => $opportunityOne->id,
                'lead_id' => $leadOne->id,
                'assigned_user_id' => $demoSales->id,
                'external_reference' => 'MSC-BOOK-2403',
                'carrier_confirmation_ref' => 'MSC-CNF-2403',
                'customer_name' => 'Northstar Cargo',
                'contact_name' => 'Layla Noor',
                'contact_email' => 'ops@northstar.test',
                'service_mode' => 'Ocean Freight',
                'origin' => 'Jebel Ali',
                'destination' => 'Rotterdam',
                'incoterm' => 'FOB',
                'commodity' => 'Industrial Equipment',
                'equipment_type' => '40HC',
                'container_count' => 2,
                'weight_kg' => 18000,
                'volume_cbm' => 55.2,
                'requested_etd' => now()->subDays(8),
                'requested_eta' => now()->addDays(18),
                'confirmed_etd' => now()->subDays(6),
                'confirmed_eta' => now()->addDays(18),
                'status' => Booking::STATUS_IN_TRANSIT,
                'notes' => 'Carrier confirmed and cargo departed.',
            ],
        );

        $bookingTwo = Booking::updateOrCreate(
            ['workspace_id' => $workspace->id, 'booking_number' => 'BK-FD-002'],
            [
                'company_id' => $company->id,
                'sheet_source_id' => $shipmentSource->id,
                'carrier_id' => $carrierAir->id,
                'shipment_job_id' => $shipmentTwo->id,
                'quote_id' => $quoteTwo->id,
                'opportunity_id' => $opportunityTwo->id,
                'lead_id' => $leadTwo->id,
                'assigned_user_id' => $demoSales->id,
                'external_reference' => 'LH-BOOK-2403',
                'carrier_confirmation_ref' => 'LH-CNF-2403',
                'customer_name' => 'Medline Gulf',
                'contact_name' => 'Aisha Rahman',
                'contact_email' => 'ops@medlinegulf.test',
                'service_mode' => 'Air Freight',
                'origin' => 'Dubai',
                'destination' => 'Frankfurt',
                'incoterm' => 'EXW',
                'commodity' => 'Healthcare Supplies',
                'equipment_type' => 'ULD',
                'container_count' => 0,
                'weight_kg' => 4200,
                'volume_cbm' => 16.7,
                'requested_etd' => now()->subDays(2),
                'requested_eta' => now()->addDays(3),
                'confirmed_etd' => now()->addDay(),
                'confirmed_eta' => now()->addDays(3),
                'status' => Booking::STATUS_CONFIRMED,
                'notes' => 'Priority allotment confirmed.',
            ],
        );

        $costingOne = JobCosting::updateOrCreate(
            ['workspace_id' => $workspace->id, 'costing_number' => 'JC-FD-001'],
            [
                'company_id' => $company->id,
                'shipment_job_id' => $shipmentOne->id,
                'quote_id' => $quoteOne->id,
                'opportunity_id' => $opportunityOne->id,
                'lead_id' => $leadOne->id,
                'assigned_user_id' => $demoSales->id,
                'customer_name' => 'Northstar Cargo',
                'service_mode' => 'Ocean Freight',
                'currency' => 'AED',
                'total_cost_amount' => 18000,
                'total_sell_amount' => 22000,
                'margin_amount' => 4000,
                'margin_percent' => 18.18,
                'status' => JobCosting::STATUS_FINALIZED,
                'notes' => 'Ocean export job costing finalized.',
            ],
        );

        $costingTwo = JobCosting::updateOrCreate(
            ['workspace_id' => $workspace->id, 'costing_number' => 'JC-FD-002'],
            [
                'company_id' => $company->id,
                'shipment_job_id' => $shipmentTwo->id,
                'quote_id' => $quoteTwo->id,
                'opportunity_id' => $opportunityTwo->id,
                'lead_id' => $leadTwo->id,
                'assigned_user_id' => $demoSales->id,
                'customer_name' => 'Medline Gulf',
                'service_mode' => 'Air Freight',
                'currency' => 'AED',
                'total_cost_amount' => 14500,
                'total_sell_amount' => 18500,
                'margin_amount' => 4000,
                'margin_percent' => 21.62,
                'status' => JobCosting::STATUS_READY_TO_INVOICE,
                'notes' => 'Air export costing ready for AR invoice.',
            ],
        );

        $this->syncCostingLines($costingOne, [
            ['Cost', 'OCEAN_FREIGHT', 'Ocean freight buy', 'MSC', 1, 15000, true, 'Booked buy rate'],
            ['Cost', 'LOCAL_CHARGES', 'Origin local charges', 'Jebel Ali Agent', 1, 3000, true, 'THC and docs'],
            ['Revenue', 'SELL_FREIGHT', 'Sell freight to customer', 'Northstar Cargo', 1, 22000, true, 'Customer invoice line'],
        ]);

        $this->syncCostingLines($costingTwo, [
            ['Cost', 'AIR_FREIGHT', 'Air freight buy', 'Lufthansa Cargo', 1, 12500, true, 'Confirmed airline rate'],
            ['Cost', 'HANDLING', 'Airport handling', 'DXB Handling', 1, 2000, true, 'Terminal charges'],
            ['Revenue', 'SELL_AIR', 'Sell freight to customer', 'Medline Gulf', 1, 18500, true, 'Customer invoice line'],
        ]);

        $invoiceOne = Invoice::updateOrCreate(
            ['workspace_id' => $workspace->id, 'invoice_number' => 'INV-FD-AR-001'],
            [
                'company_id' => $company->id,
                'shipment_job_id' => $shipmentOne->id,
                'booking_id' => $bookingOne->id,
                'job_costing_id' => $costingOne->id,
                'quote_id' => $quoteOne->id,
                'opportunity_id' => $opportunityOne->id,
                'lead_id' => $leadOne->id,
                'assigned_user_id' => $demoSales->id,
                'posted_by_user_id' => $demoOwner->id,
                'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
                'bill_to_name' => 'Northstar Cargo',
                'contact_email' => 'finance@northstar.test',
                'issue_date' => now()->subDays(3)->toDateString(),
                'due_date' => now()->addDays(11)->toDateString(),
                'currency' => 'AED',
                'subtotal_amount' => 22000,
                'tax_amount' => 1100,
                'total_amount' => 23100,
                'paid_amount' => 10000,
                'balance_amount' => 13100,
                'posted_at' => now()->subDays(3),
                'status' => Invoice::STATUS_PARTIALLY_PAID,
                'notes' => 'Partial payment received.',
            ],
        );

        $invoiceTwo = Invoice::updateOrCreate(
            ['workspace_id' => $workspace->id, 'invoice_number' => 'INV-FD-AP-001'],
            [
                'company_id' => $company->id,
                'shipment_job_id' => $shipmentOne->id,
                'booking_id' => $bookingOne->id,
                'job_costing_id' => $costingOne->id,
                'quote_id' => $quoteOne->id,
                'opportunity_id' => $opportunityOne->id,
                'lead_id' => $leadOne->id,
                'assigned_user_id' => $demoSales->id,
                'posted_by_user_id' => $demoOwner->id,
                'invoice_type' => Invoice::TYPE_ACCOUNTS_PAYABLE,
                'bill_to_name' => 'MSC',
                'contact_email' => 'ap@msc-demo.com',
                'issue_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'currency' => 'AED',
                'subtotal_amount' => 18000,
                'tax_amount' => 0,
                'total_amount' => 18000,
                'paid_amount' => 0,
                'balance_amount' => 18000,
                'posted_at' => now()->subDays(5),
                'status' => Invoice::STATUS_SENT,
                'notes' => 'Carrier payable for ocean job.',
            ],
        );

        $invoiceThree = Invoice::updateOrCreate(
            ['workspace_id' => $workspace->id, 'invoice_number' => 'INV-FD-AR-002'],
            [
                'company_id' => $company->id,
                'shipment_job_id' => $shipmentTwo->id,
                'booking_id' => $bookingTwo->id,
                'job_costing_id' => $costingTwo->id,
                'quote_id' => $quoteTwo->id,
                'opportunity_id' => $opportunityTwo->id,
                'lead_id' => $leadTwo->id,
                'assigned_user_id' => $demoSales->id,
                'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
                'bill_to_name' => 'Medline Gulf',
                'contact_email' => 'finance@medlinegulf.test',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'currency' => 'AED',
                'subtotal_amount' => 18500,
                'tax_amount' => 925,
                'total_amount' => 19425,
                'paid_amount' => 0,
                'balance_amount' => 19425,
                'status' => Invoice::STATUS_DRAFT,
                'notes' => 'Draft invoice ready once uplift is completed.',
            ],
        );

        $this->syncInvoiceLines($invoiceOne, [
            ['SELL_FREIGHT', 'Ocean freight to Rotterdam', 1, 22000, 22000, 'Customer sell line'],
        ]);

        $this->syncInvoiceLines($invoiceTwo, [
            ['OCEAN_FREIGHT', 'Carrier ocean freight payable', 1, 15000, 15000, 'Carrier buy line'],
            ['LOCAL_CHARGES', 'Origin charges payable', 1, 3000, 3000, 'Origin charges'],
        ]);

        $this->syncInvoiceLines($invoiceThree, [
            ['SELL_AIR', 'Air freight to Frankfurt', 1, 18500, 18500, 'Customer sell line'],
        ]);

        $this->syncShipmentMilestones($shipmentOne, [
            ['booking_requested', 'Booking Requested', 1, ShipmentMilestone::STATUS_COMPLETED, now()->subDays(9), now()->subDays(8), 'Booking request sent'],
            ['booked_with_carrier', 'Booked With Carrier', 2, ShipmentMilestone::STATUS_COMPLETED, now()->subDays(8), now()->subDays(7), 'MSC booking confirmed'],
            ['departed_origin', 'Departed Origin', 3, ShipmentMilestone::STATUS_COMPLETED, now()->subDays(6), now()->subDays(5), 'Vessel sailed from Jebel Ali'],
            ['arrived_destination', 'Arrived Destination', 4, ShipmentMilestone::STATUS_PENDING, now()->addDays(18), null, 'Awaiting arrival in Rotterdam'],
            ['customs_clearance', 'Customs Clearance', 5, ShipmentMilestone::STATUS_PENDING, now()->addDays(19), null, 'Destination customs pending'],
            ['delivered', 'Delivered', 6, ShipmentMilestone::STATUS_PENDING, now()->addDays(21), null, 'Final delivery not completed'],
        ]);

        $this->syncShipmentMilestones($shipmentTwo, [
            ['booking_requested', 'Booking Requested', 1, ShipmentMilestone::STATUS_COMPLETED, now()->subDays(3), now()->subDays(2), 'Air booking request sent'],
            ['booked_with_carrier', 'Booked With Carrier', 2, ShipmentMilestone::STATUS_COMPLETED, now()->subDays(2), now()->subDay(), 'Lufthansa allotment secured'],
            ['departed_origin', 'Departed Origin', 3, ShipmentMilestone::STATUS_PENDING, now()->addDay(), null, 'Flight departure pending'],
            ['arrived_destination', 'Arrived Destination', 4, ShipmentMilestone::STATUS_PENDING, now()->addDays(3), null, 'ETA Frankfurt'],
            ['customs_clearance', 'Customs Clearance', 5, ShipmentMilestone::STATUS_PENDING, now()->addDays(3), null, 'Import clearance pending'],
            ['delivered', 'Delivered', 6, ShipmentMilestone::STATUS_PENDING, now()->addDays(4), null, 'Last-mile delivery pending'],
        ]);

        $this->syncShipmentDocuments($shipmentOne, [
            [ShipmentDocument::TYPE_BOOKING_CONFIRMATION, 'MSC Booking Confirmation', 'MSC-CNF-2403', 'https://demo.iqxconnect.com/docs/booking-confirmation', ShipmentDocument::STATUS_RECEIVED, now()->subDays(7), 'Carrier confirmation file'],
            [ShipmentDocument::TYPE_HOUSE_BILL, 'House Bill', 'HBL-FD-001', 'https://demo.iqxconnect.com/docs/hbl-001', ShipmentDocument::STATUS_APPROVED, now()->subDays(5), 'House bill approved'],
            [ShipmentDocument::TYPE_MASTER_BILL, 'Master Bill', 'MBL-FD-001', 'https://demo.iqxconnect.com/docs/mbl-001', ShipmentDocument::STATUS_RECEIVED, now()->subDays(5), 'Master bill received'],
            [ShipmentDocument::TYPE_COMMERCIAL_INVOICE, 'Commercial Invoice', 'CI-FD-001', 'https://demo.iqxconnect.com/docs/ci-001', ShipmentDocument::STATUS_SENT, now()->subDays(4), 'Commercial invoice sent to consignee'],
        ]);

        $this->syncShipmentDocuments($shipmentTwo, [
            [ShipmentDocument::TYPE_BOOKING_CONFIRMATION, 'Air Booking Confirmation', 'LH-CNF-2403', 'https://demo.iqxconnect.com/docs/air-booking', ShipmentDocument::STATUS_RECEIVED, now()->subDay(), 'Airline booking confirmation'],
            [ShipmentDocument::TYPE_MASTER_BILL, 'Master Air Waybill', 'MAWB-FD-002', 'https://demo.iqxconnect.com/docs/mawb-002', ShipmentDocument::STATUS_MISSING, null, 'Will be available after uplift'],
            [ShipmentDocument::TYPE_CUSTOMS, 'Export Customs', 'CUS-FD-002', 'https://demo.iqxconnect.com/docs/customs-002', ShipmentDocument::STATUS_RECEIVED, now()->subHours(8), 'Export customs completed'],
        ]);

        $this->syncMonthlyReports($company->id, $workspace->id, $reportSource->id);

        LeadStatusLog::updateOrCreate(
            [
                'lead_id' => $leadFour->id,
                'to_status' => Lead::STATUS_DISQUALIFIED,
                'change_context' => 'seed',
            ],
            [
                'user_id' => $demoOwner->id,
                'from_status' => Lead::STATUS_IN_PROGRESS,
                'note' => 'Demo disqualification history for the freight workspace.',
            ],
        );

        Model::reguard();
    }

    protected function syncCostingLines(JobCosting $costing, array $lines): void
    {
        $costing->lines()->delete();

        foreach ($lines as [$type, $chargeCode, $description, $vendor, $quantity, $unitAmount, $isBillable, $notes]) {
            JobCostingLine::create([
                'job_costing_id' => $costing->id,
                'line_type' => $type,
                'charge_code' => $chargeCode,
                'description' => $description,
                'vendor_name' => $vendor,
                'quantity' => $quantity,
                'unit_amount' => $unitAmount,
                'total_amount' => $quantity * $unitAmount,
                'is_billable' => $isBillable,
                'notes' => $notes,
            ]);
        }
    }

    protected function syncInvoiceLines(Invoice $invoice, array $lines): void
    {
        $invoice->lines()->delete();

        foreach ($lines as [$chargeCode, $description, $quantity, $unitAmount, $totalAmount, $notes]) {
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'charge_code' => $chargeCode,
                'description' => $description,
                'quantity' => $quantity,
                'unit_amount' => $unitAmount,
                'total_amount' => $totalAmount,
                'notes' => $notes,
            ]);
        }
    }

    protected function syncShipmentMilestones(ShipmentJob $shipment, array $milestones): void
    {
        $shipment->milestones()->delete();

        foreach ($milestones as [$eventKey, $label, $sequence, $status, $plannedAt, $completedAt, $notes]) {
            ShipmentMilestone::create([
                'company_id' => $shipment->company_id,
                'workspace_id' => $shipment->workspace_id,
                'shipment_job_id' => $shipment->id,
                'event_key' => $eventKey,
                'label' => $label,
                'sequence' => $sequence,
                'status' => $status,
                'planned_at' => $plannedAt,
                'completed_at' => $completedAt,
                'notes' => $notes,
            ]);
        }
    }

    protected function syncShipmentDocuments(ShipmentJob $shipment, array $documents): void
    {
        $shipment->documents()->delete();

        foreach ($documents as [$type, $name, $reference, $url, $status, $uploadedAt, $notes]) {
            ShipmentDocument::create([
                'company_id' => $shipment->company_id,
                'workspace_id' => $shipment->workspace_id,
                'shipment_job_id' => $shipment->id,
                'document_type' => $type,
                'document_name' => $name,
                'reference_number' => $reference,
                'external_url' => $url,
                'status' => $status,
                'uploaded_at' => $uploadedAt,
                'notes' => $notes,
            ]);
        }
    }

    protected function syncMonthlyReports(int $companyId, int $workspaceId, int $sheetSourceId): void
    {
        $rows = [
            [
                'year_month' => now()->subMonths(2)->format('M-y'),
                'month_start' => now()->subMonths(2)->startOfMonth(),
                'linkedin_ads_leads' => 3,
                'organic_leads' => 6,
                'email_leads' => 4,
                'google_ads_leads' => 9,
                'total_leads' => 22,
                'linkedin_ads_cost' => 1800,
                'google_ads_cost' => 4700,
                'total_ads_cost' => 6500,
                'cost_per_conversion' => 62.5,
                'total_revenue_potential' => 210000,
                'won_revenue_potential' => 60545,
                'closed_won_count' => 1,
                'closed_lost_count' => 1,
                'proposal_sent_count' => 2,
                'initial_contact_count' => 4,
                'organic_opportunities_count' => 3,
                'total_opportunities_count' => 6,
                'mql_to_sql_rate' => 18.18,
                'sql_conversion_rate' => 16.67,
                'total_revenue_potential_2025' => 210000,
                'won_revenue_potential_2025' => 60545,
                'total_deals_2026' => 4,
                'total_deals_2025' => 0,
                'total_deals_2024' => 0,
                'romi_2025' => 831.46,
            ],
            [
                'year_month' => now()->subMonth()->format('M-y'),
                'month_start' => now()->subMonth()->startOfMonth(),
                'linkedin_ads_leads' => 4,
                'organic_leads' => 8,
                'email_leads' => 5,
                'google_ads_leads' => 12,
                'total_leads' => 29,
                'linkedin_ads_cost' => 2100,
                'google_ads_cost' => 5200,
                'total_ads_cost' => 7300,
                'cost_per_conversion' => 63,
                'total_revenue_potential' => 370000,
                'won_revenue_potential' => 915000,
                'closed_won_count' => 4,
                'closed_lost_count' => 1,
                'proposal_sent_count' => 3,
                'initial_contact_count' => 5,
                'organic_opportunities_count' => 5,
                'total_opportunities_count' => 9,
                'mql_to_sql_rate' => 24.14,
                'sql_conversion_rate' => 44.44,
                'total_revenue_potential_2025' => 370000,
                'won_revenue_potential_2025' => 915000,
                'total_deals_2026' => 17,
                'total_deals_2025' => 0,
                'total_deals_2024' => 0,
                'romi_2025' => 12000,
            ],
            [
                'year_month' => now()->format('M-y'),
                'month_start' => now()->startOfMonth(),
                'linkedin_ads_leads' => 5,
                'organic_leads' => 7,
                'email_leads' => 6,
                'google_ads_leads' => 11,
                'total_leads' => 29,
                'linkedin_ads_cost' => 2300,
                'google_ads_cost' => 6500,
                'total_ads_cost' => 8800,
                'cost_per_conversion' => 71.2,
                'total_revenue_potential' => 415000,
                'won_revenue_potential' => 231000,
                'closed_won_count' => 2,
                'closed_lost_count' => 1,
                'proposal_sent_count' => 4,
                'initial_contact_count' => 6,
                'organic_opportunities_count' => 6,
                'total_opportunities_count' => 10,
                'mql_to_sql_rate' => 20.69,
                'sql_conversion_rate' => 20,
                'total_revenue_potential_2025' => 415000,
                'won_revenue_potential_2025' => 231000,
                'total_deals_2026' => 11,
                'total_deals_2025' => 0,
                'total_deals_2024' => 0,
                'romi_2025' => 2525,
            ],
        ];

        foreach ($rows as $row) {
            MonthlyReport::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'workspace_id' => $workspaceId,
                    'year_month' => $row['year_month'],
                ],
                [
                    ...$row,
                    'sheet_source_id' => $sheetSourceId,
                ],
            );
        }
    }
}
