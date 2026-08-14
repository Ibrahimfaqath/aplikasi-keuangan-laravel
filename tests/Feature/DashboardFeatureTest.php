<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_url_redirects_to_transactions_page(): void
    {
        $user = User::factory()->create();

        // URL /dashboard lama diarahkan ke halaman utama (transaksi)
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('transactions.index'));
    }

    public function test_guest_is_redirected_to_login_from_dashboard_url(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
