<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_page_is_available(): void
    {
        $response = $this->get('/product');

        $response
            ->assertOk()
            ->assertSee('Product Guide')
            ->assertSee('IQX Connect')
            ->assertSee('Freight Forwarder')
            ->assertSee('Growth')
            ->assertSee('Professional')
            ->assertSee('Enterprise')
            ->assertSee('Frequently asked questions');
    }

    public function test_authenticated_product_page_is_available(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/product');

        $response
            ->assertOk()
            ->assertSee('Product Documentation')
            ->assertSee('Workspace Guide')
            ->assertSee('Lead To Job');
    }

    public function test_legacy_documentation_url_redirects_to_product(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/documentation');

        $response->assertRedirect('/product');
    }
}
