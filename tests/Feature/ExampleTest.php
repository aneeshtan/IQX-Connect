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
            ->assertSee('first 100 operational records');

        $response->assertSee('Trusted by modern maritime businesses');
        $response->assertSee('Workspace Modes');
        $response->assertSee('Easy Listing Views');
        $response->assertSee('Full Migrations and Integrations');
        $response->assertSee('Frequently asked questions');
    }
}
