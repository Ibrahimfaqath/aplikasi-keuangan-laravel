<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_url_redirects_to_transactions(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/transactions');
    }
}
