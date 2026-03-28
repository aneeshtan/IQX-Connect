<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Company;
use App\Models\SheetSource;
use App\Models\ShipmentJob;
use App\Models\Workspace;
use App\Services\GoogleSheetsService;
use App\Services\SheetSourceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SheetSourceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_google_sheet_can_fallback_to_csv_when_oauth_is_missing(): void
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

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'Public Google Sheet',
            'url' => 'https://docs.google.com/spreadsheets/d/10e7bFezWnxiVEOtTMsAn5bOS9-2Y33utDoMFFibS-dY/edit?gid=0#gid=0',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API,
            'is_active' => true,
            'sync_status' => 'idle',
        ]);

        Http::fake([
            'https://docs.google.com/spreadsheets/d/*/export?format=csv&gid=0' => Http::response(
                "Lead ID,Company name,Column 1,Email,Lead Source,Service,Submission Create Date\n".
                "LD-100,Acme Marine,Sara Ahmed,sara@example.com,Google Ads,Container Conversion,2026-03-01\n",
                200
            ),
        ]);

        $googleSheets = $this->createMock(GoogleSheetsService::class);
        $googleSheets->method('readRows')
            ->willThrowException(new RuntimeException('Google OAuth client ID and secret must be saved first.'));

        $this->app->instance(GoogleSheetsService::class, $googleSheets);

        $rows = app(SheetSourceSyncService::class)->sync($source);

        $this->assertSame(1, $rows);
        $this->assertSame(SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV, $source->fresh()->source_kind);
        $this->assertDatabaseHas('leads', [
            'workspace_id' => $workspace->id,
            'lead_id' => 'LD-100',
            'company_name' => 'Acme Marine',
            'email' => 'sara@example.com',
        ]);
    }

    public function test_lead_sync_supports_name_and_company_headers_from_google_sheet(): void
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => 'acme-marine-alt-headers',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Alt Header Workspace',
            'slug' => 'alt-header-workspace',
            'is_default' => true,
        ]);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'Lead CSV',
            'url' => 'https://example.com/leads.csv',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
            'is_active' => true,
            'sync_status' => 'idle',
        ]);

        app(SheetSourceSyncService::class)->importCsvForSource(
            $source,
            "Lead ID,Name,Company,Email,Lead Source,Service,Submission Create Date\n".
            "LD-2670,Matteo Carria,Matteo Carria,info@karpov.it,Website Quote Form,Freight services,2026-03-27 09:50:43\n"
        );

        $this->assertDatabaseHas('leads', [
            'workspace_id' => $workspace->id,
            'lead_id' => 'LD-2670',
            'contact_name' => 'Matteo Carria',
            'company_name' => 'Matteo Carria',
            'email' => 'info@karpov.it',
        ]);
    }

    public function test_cargowise_source_can_sync_quotes_from_json_endpoint(): void
    {
        $company = Company::create([
            'name' => 'CargoWise Marine',
            'slug' => 'cargowise-marine',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'CargoWise Workspace',
            'slug' => 'cargowise-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_QUOTES,
            'name' => 'CargoWise Quotes',
            'url' => 'https://cw.example.com/api/quotes',
            'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'cargowise' => [
                    'endpoint' => 'https://cw.example.com/api/quotes',
                    'auth_mode' => 'bearer',
                    'token' => 'secret-token',
                    'format' => 'json',
                    'data_path' => 'data.rows',
                ],
            ],
        ]);

        Http::fake([
            'https://cw.example.com/api/quotes' => Http::response([
                'data' => [
                    'rows' => [
                        [
                            'quote_number' => 'CW-001',
                            'company_name' => 'Oceanic Traders',
                            'contact_name' => 'Lina Noor',
                            'contact_email' => 'lina@example.com',
                            'service_mode' => 'Ocean Freight',
                            'origin' => 'Jebel Ali',
                            'destination' => 'Hamburg',
                            'buy_amount' => 8200,
                            'sell_amount' => 9500,
                            'currency' => 'AED',
                            'status' => 'Sent',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $rows = app(SheetSourceSyncService::class)->sync($source);

        $this->assertSame(1, $rows);
        $this->assertDatabaseHas('quotes', [
            'workspace_id' => $workspace->id,
            'sheet_source_id' => $source->id,
            'quote_number' => 'CW-001',
            'company_name' => 'Oceanic Traders',
            'contact_email' => 'lina@example.com',
            'service_mode' => 'Ocean Freight',
            'status' => 'Sent',
        ]);
    }

    public function test_cargowise_source_can_sync_shipments_from_json_endpoint(): void
    {
        $company = Company::create([
            'name' => 'CargoWise Shipments',
            'slug' => 'cargowise-shipments',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'CargoWise Shipment Workspace',
            'slug' => 'cargowise-shipment-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_SHIPMENTS,
            'name' => 'CargoWise Shipments',
            'url' => 'https://cw.example.com/api/shipments',
            'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'cargowise' => [
                    'endpoint' => 'https://cw.example.com/api/shipments',
                    'auth_mode' => 'bearer',
                    'token' => 'shipment-token',
                    'format' => 'json',
                    'data_path' => 'data.rows',
                ],
            ],
        ]);

        Http::fake([
            'https://cw.example.com/api/shipments' => Http::response([
                'data' => [
                    'rows' => [
                        [
                            'job_number' => 'SHP-001',
                            'company_name' => 'Oceanic Traders',
                            'contact_name' => 'Lina Noor',
                            'contact_email' => 'lina@example.com',
                            'service_mode' => 'Ocean Freight',
                            'origin' => 'Jebel Ali',
                            'destination' => 'Hamburg',
                            'carrier_name' => 'Maersk',
                            'vessel_name' => 'Maersk Atlantis',
                            'voyage_number' => 'MA-445',
                            'house_bill_no' => 'HBL-778',
                            'master_bill_no' => 'MBL-882',
                            'estimated_departure_at' => '2026-04-01 10:00:00',
                            'estimated_arrival_at' => '2026-04-18 15:00:00',
                            'buy_amount' => 8200,
                            'sell_amount' => 9500,
                            'currency' => 'AED',
                            'status' => 'Booked',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $rows = app(SheetSourceSyncService::class)->sync($source);

        $this->assertSame(1, $rows);
        $this->assertDatabaseHas('shipment_jobs', [
            'workspace_id' => $workspace->id,
            'sheet_source_id' => $source->id,
            'job_number' => 'SHP-001',
            'company_name' => 'Oceanic Traders',
            'contact_email' => 'lina@example.com',
            'carrier_name' => 'Maersk',
            'status' => 'Booked',
        ]);
    }

    public function test_cargowise_source_can_sync_carriers_from_json_endpoint(): void
    {
        $company = Company::create([
            'name' => 'CargoWise Carriers',
            'slug' => 'cargowise-carriers',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'CargoWise Carrier Workspace',
            'slug' => 'cargowise-carrier-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_CARRIERS,
            'name' => 'CargoWise Carriers',
            'url' => 'https://cw.example.com/api/carriers',
            'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'cargowise' => [
                    'endpoint' => 'https://cw.example.com/api/carriers',
                    'auth_mode' => 'bearer',
                    'token' => 'carrier-token',
                    'format' => 'json',
                    'data_path' => 'data.rows',
                ],
            ],
        ]);

        Http::fake([
            'https://cw.example.com/api/carriers' => Http::response([
                'data' => [
                    'rows' => [
                        [
                            'carrier_name' => 'Maersk',
                            'mode' => 'ocean',
                            'carrier_code' => 'MSK',
                            'scac' => 'MAEU',
                            'contact_name' => 'Lina Noor',
                            'contact_email' => 'lina@maersk.test',
                            'service_lanes' => 'Jebel Ali -> Rotterdam',
                            'active' => true,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $rows = app(SheetSourceSyncService::class)->sync($source);

        $this->assertSame(1, $rows);
        $this->assertDatabaseHas('carriers', [
            'workspace_id' => $workspace->id,
            'sheet_source_id' => $source->id,
            'name' => 'Maersk',
            'mode' => Carrier::MODE_OCEAN,
            'scac_code' => 'MAEU',
        ]);
    }

    public function test_cargowise_source_can_sync_bookings_from_json_endpoint_and_update_the_shipment(): void
    {
        $company = Company::create([
            'name' => 'CargoWise Bookings',
            'slug' => 'cargowise-bookings',
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'CargoWise Booking Workspace',
            'slug' => 'cargowise-booking-workspace',
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'freight_forwarding'),
        ]);

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
            'job_number' => 'SJ-CW-001',
            'company_name' => 'Oceanic Traders',
            'contact_email' => 'lina@example.com',
            'service_mode' => 'Ocean Freight',
            'origin' => 'Jebel Ali',
            'destination' => 'Hamburg',
            'status' => ShipmentJob::STATUS_DRAFT,
        ]);

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_BOOKINGS,
            'name' => 'CargoWise Bookings',
            'url' => 'https://cw.example.com/api/bookings',
            'source_kind' => SheetSource::SOURCE_KIND_CARGOWISE_API,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'cargowise' => [
                    'endpoint' => 'https://cw.example.com/api/bookings',
                    'auth_mode' => 'bearer',
                    'token' => 'booking-token',
                    'format' => 'json',
                    'data_path' => 'data.rows',
                ],
            ],
        ]);

        Http::fake([
            'https://cw.example.com/api/bookings' => Http::response([
                'data' => [
                    'rows' => [
                        [
                            'booking_number' => 'BK-CW-001',
                            'shipment_job' => 'SJ-CW-001',
                            'carrier_name' => 'MSC',
                            'customer_name' => 'Oceanic Traders',
                            'contact_email' => 'lina@example.com',
                            'service_mode' => 'Ocean Freight',
                            'origin' => 'Jebel Ali',
                            'destination' => 'Hamburg',
                            'requested_etd' => '2026-04-10 09:00:00',
                            'requested_eta' => '2026-04-26 18:00:00',
                            'confirmed_etd' => '2026-04-11 09:00:00',
                            'confirmed_eta' => '2026-04-27 18:00:00',
                            'status' => 'Confirmed',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $rows = app(SheetSourceSyncService::class)->sync($source);

        $this->assertSame(1, $rows);
        $this->assertDatabaseHas('bookings', [
            'workspace_id' => $workspace->id,
            'sheet_source_id' => $source->id,
            'booking_number' => 'BK-CW-001',
            'carrier_id' => $carrier->id,
            'shipment_job_id' => $shipment->id,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $this->assertDatabaseHas('shipment_jobs', [
            'id' => $shipment->id,
            'carrier_name' => 'MSC',
            'status' => ShipmentJob::STATUS_BOOKED,
        ]);
    }
}
