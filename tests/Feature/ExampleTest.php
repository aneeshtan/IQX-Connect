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
            ->assertSee('Try For Free');

        $response->assertSee('Product Features');
        $response->assertSee('Product Benefits');
    }
}
