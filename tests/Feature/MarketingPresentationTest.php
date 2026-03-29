<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_presentation_page_is_available(): void
    {
        $response = $this->get('/presentation');

        $response
            ->assertOk()
            ->assertSee('IQX Connect')
            ->assertSee('Maritime CRM and execution')
            ->assertSee('Download PDF');
    }

    public function test_marketing_presentation_pdf_exists(): void
    {
        $this->assertFileExists(public_path('marketing/IQX-Connect-Marketing-Presentation.pdf'));
    }
}
