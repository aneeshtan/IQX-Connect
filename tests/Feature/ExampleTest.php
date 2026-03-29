<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('IQX Connect')
            ->assertSee('Start your journey')
            ->assertSee('first 100 shipments free');

        $response->assertSee('Product Features');
        $response->assertSee('Product Benefits');
    }
}
