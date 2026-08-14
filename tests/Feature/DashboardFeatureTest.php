<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_data_from_database(): void
    {
        $user = User::factory()->create();

        Transaction::create([
            'user_id'          => $user->id,
            'title'            => 'Gaji Bulanan',
            'amount'           => 8000000,
            'type'             => 'income',
            'transaction_date' => Carbon::now()->startOfMonth(),
        ]);

        Transaction::create([
            'user_id'          => $user->id,
            'title'            => 'Belanja Bulanan',
            'amount'           => 1500000,
            'type'             => 'expense',
            'transaction_date' => Carbon::now(),
        ]);

        // Data user lain tidak boleh tampil
        $otherUser = User::factory()->create();
        Transaction::create([
            'user_id'          => $otherUser->id,
            'title'            => 'Transaksi Rahasia',
            'amount'           => 999999,
            'type'             => 'expense',
            'transaction_date' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Gaji Bulanan');
        $response->assertSee('Belanja Bulanan');
        $response->assertSee('8.000.000', false);
        $response->assertSee('1.500.000', false);
        $response->assertDontSee('Transaksi Rahasia');
        $response->assertDontSee('999.999', false);
    }

    public function test_dashboard_monthly_cards_only_count_current_month(): void
    {
        $user = User::factory()->create();

        Transaction::create([
            'user_id'          => $user->id,
            'title'            => 'Pengeluaran Bulan Lalu',
            'amount'           => 5000000,
            'type'             => 'expense',
            'transaction_date' => Carbon::now()->subMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Total saldo mencakup seluruh riwayat
        $response->assertSee('-Rp 5.000.000', false);
        // Kartu "Pengeluaran Bulan Ini" tidak terpengaruh transaksi bulan lalu
        $response->assertSee('Rp 0', false);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
