<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\SheetSource;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordPressLeadWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_fluent_forms_webhook_creates_a_lead(): void
    {
        [$company, $workspace] = $this->workspace();

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'WordPress Leads',
            'url' => 'https://example.com/contact',
            'source_kind' => SheetSource::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'wordpress' => [
                    'provider' => SheetSource::WORDPRESS_PROVIDER_FLUENT_FORMS,
                    'secret' => 'iqx-secret',
                ],
            ],
        ]);

        $response = $this->postJson(route('source-webhooks.ingest', $source), [
            'entry_id' => 'ff-1001',
            'full_name' => 'Sara Ahmed',
            'company_name' => 'Blue Dock Marine',
            'email' => 'sara@example.com',
            'phone' => '+971555010101',
            'service' => 'container conversion',
            'message' => 'Need a fast modular office conversion.',
        ], [
            'X-IQX-Source-Token' => 'iqx-secret',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'source_id' => $source->id,
            ]);

        $this->assertDatabaseHas('leads', [
            'workspace_id' => $workspace->id,
            'sheet_source_id' => $source->id,
            'contact_name' => 'Sara Ahmed',
            'company_name' => 'Blue Dock Marine',
            'email' => 'sara@example.com',
            'lead_source' => 'Fluent Forms',
            'service' => 'Container Conversion',
        ]);
    }

    public function test_contact_form_7_webhook_maps_common_field_names(): void
    {
        [$company, $workspace] = $this->workspace('cf7-company', 'cf7-workspace');

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'CF7 Leads',
            'url' => 'https://example.com/request-quote',
            'source_kind' => SheetSource::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'wordpress' => [
                    'provider' => SheetSource::WORDPRESS_PROVIDER_CONTACT_FORM_7,
                    'secret' => 'cf7-secret',
                ],
            ],
        ]);

        $response = $this->postJson(route('source-webhooks.ingest', $source), [
            'your-name' => 'Nadia Salem',
            'your-email' => 'nadia@example.com',
            'your-phone' => '+971522220000',
            'your-subject' => 'project cargo',
            'your-message' => 'Looking for project cargo support from Jebel Ali.',
        ], [
            'X-IQX-Source-Token' => 'cf7-secret',
        ]);

        $response->assertOk();

        $lead = Lead::query()->where('sheet_source_id', $source->id)->firstOrFail();

        $this->assertSame('Nadia Salem', $lead->contact_name);
        $this->assertSame('nadia@example.com', $lead->email);
        $this->assertSame('Contact Form 7', $lead->lead_source);
        $this->assertSame('Project Cargo', $lead->service);
        $this->assertStringContainsString('project cargo support', (string) $lead->notes);
    }

    public function test_wordpress_webhook_rejects_invalid_token(): void
    {
        [$company, $workspace] = $this->workspace('token-company', 'token-workspace');

        $source = SheetSource::create([
            'company_id' => $company->id,
            'workspace_id' => $workspace->id,
            'type' => SheetSource::TYPE_LEADS,
            'name' => 'Protected WordPress Leads',
            'url' => 'https://example.com/form',
            'source_kind' => SheetSource::SOURCE_KIND_WORDPRESS_FORM_WEBHOOK,
            'is_active' => true,
            'sync_status' => 'idle',
            'mapping' => [
                'wordpress' => [
                    'provider' => SheetSource::WORDPRESS_PROVIDER_FLUENT_FORMS,
                    'secret' => 'correct-secret',
                ],
            ],
        ]);

        $this->postJson(route('source-webhooks.ingest', $source), [
            'email' => 'blocked@example.com',
        ], [
            'X-IQX-Source-Token' => 'wrong-secret',
        ])->assertForbidden();

        $this->assertDatabaseMissing('leads', [
            'workspace_id' => $workspace->id,
            'email' => 'blocked@example.com',
        ]);
    }

    protected function workspace(string $companySlug = 'acme-wordpress', string $workspaceSlug = 'wp-workspace'): array
    {
        $company = Company::create([
            'name' => 'Acme Marine',
            'slug' => $companySlug,
            'industry' => 'Maritime',
            'timezone' => 'Asia/Dubai',
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => 'WordPress Workspace',
            'slug' => $workspaceSlug,
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, 'general_maritime'),
        ]);

        return [$company, $workspace];
    }
}
