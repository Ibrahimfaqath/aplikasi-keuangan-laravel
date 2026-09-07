<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu (belum login) melihat landing page, bukan redirect.
     * Sesuai routes/web.php: Auth::check() ? redirect : view('landing').
     */
    public function test_root_url_shows_landing_for_guest(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('landing');
    }

    public function test_root_url_redirects_authenticated_user_to_transactions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/transactions');
    }
}
