<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SheetSource;
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
}
