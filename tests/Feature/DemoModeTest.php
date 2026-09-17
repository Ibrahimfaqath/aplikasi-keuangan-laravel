<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private function createDemoUser(): User
    {
        return User::factory()->create([
            'name' => config('demo.name') ?: 'Demo User',
            'email' => config('demo.email'),
        ]);
    }

    public function test_demo_login_redirects_and_authenticates(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->post(route('demo.login'));

        $this->assertAuthenticatedAs($demo);
        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');
    }

    public function test_demo_login_shows_friendly_error_when_demo_account_is_missing(): void
    {
        $response = $this->post(route('demo.login'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_demo_login_requires_guest(): void
    {
        $demo = $this->createDemoUser();
        $this->actingAs($demo);

        // Sudah login + masih mencoba route demo: dikembalikan ke dashboard tanpa error.
        $response = $this->post(route('demo.login'));

        $response->assertRedirect(route('transactions.index'));
    }

    public function test_demo_data_seeder_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        $demo = User::where('email', config('demo.email'))->first();
        $this->assertNotNull($demo);

        $before = Transaction::where('user_id', $demo->id)->count();

        $this->seed(DemoDataSeeder::class);

        $after = Transaction::where('user_id', $demo->id)->count();

        $this->assertSame($before, $after);
        $this->assertGreaterThan(0, $after);

        // Anggaran bulan berjalan disimpan (progress terlihat di dashboard).
        $now = Carbon::now();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $demo->id,
            'month' => $now->month,
            'year' => $now->year,
        ]);
    }

    public function test_demo_user_cannot_create_transaction(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->actingAs($demo)->post(route('transactions.store'), [
            'title' => 'Transaksi Demo Nakal',
            'amount' => 50000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $demo->id,
            'title' => 'Transaksi Demo Nakal',
        ]);
    }

    public function test_demo_user_cannot_update_transaction(): void
    {
        $demo = $this->createDemoUser();
        $transaction = Transaction::create([
            'user_id' => $demo->id,
            'title' => 'Asli',
            'type' => 'expense',
            'category' => 'Belanja',
            'amount' => 100000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($demo)->put(route('transactions.update', $transaction->id), [
            'title' => 'Diubah',
            'amount' => 99999,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $demo->id,
            'title' => 'Asli',
        ]);
    }

    public function test_demo_user_cannot_delete_transaction(): void
    {
        $demo = $this->createDemoUser();
        $transaction = Transaction::create([
            'user_id' => $demo->id,
            'title' => 'Untuk Dihapus',
            'type' => 'expense',
            'category' => 'Lainnya',
            'amount' => 10000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($demo)->delete(route('transactions.destroy', $transaction->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $demo->id,
        ]);
    }

    public function test_demo_user_cannot_set_budget(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->actingAs($demo)->post(route('budgets.store'), ['amount' => 5000000]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(0, Budget::where('user_id', $demo->id)->count());
    }

    public function test_demo_user_cannot_confirm_ai_transaction(): void
    {
        $demo = $this->createDemoUser();

        Session::put('pending_transaction', [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($demo)->postJson(route('ai.confirm'), [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertForbidden();
        $this->assertEquals(0, Transaction::where('user_id', $demo->id)->count());
    }

    public function test_demo_user_cannot_store_ai_transactions_bulk(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->actingAs($demo)->postJson(route('ai.transactions'), [
            'items' => [[
                'title' => 'Via AI',
                'amount' => 20000,
                'type' => 'expense',
                'category' => 'Transportasi',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]],
        ]);

        $response->assertForbidden();
        $this->assertEquals(0, Transaction::where('user_id', $demo->id)->count());
    }

    public function test_demo_user_cannot_update_password(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->actingAs($demo)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'diubah-123',
            'password_confirmation' => 'diubah-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('status'));

        $this->assertFalse(
            Hash::check('diubah-123', $demo->fresh()->password)
        );
    }

    public function test_demo_user_cannot_update_profile(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->actingAs($demo)->patch(route('profile.update'), [
            'name' => 'Demon Alias',
            'email' => config('demo.email'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertNotSame('Demon Alias', $demo->fresh()->name);
    }

    public function test_demo_user_cannot_delete_account(): void
    {
        $demo = $this->createDemoUser();

        $response = $this->actingAs($demo)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertNotNull(User::where('email', config('demo.email'))->first());
    }

    public function test_demo_dashboard_only_shows_demo_data(): void
    {
        $demo = $this->createDemoUser();
        Transaction::create([
            'user_id' => $demo->id,
            'title' => 'Beli Demo Sendiri',
            'type' => 'expense',
            'category' => 'Belanja',
            'amount' => 5000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $other = User::factory()->create();
        Transaction::create([
            'user_id' => $other->id,
            'title' => 'Rahasia User Lain',
            'type' => 'expense',
            'category' => 'Lainnya',
            'amount' => 5000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($demo)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Beli Demo Sendiri');
        $response->assertDontSee('Rahasia User Lain');
    }

    public function test_normal_user_is_unaffected_by_demo_guards(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'title' => 'Transaksi Normal',
                'amount' => 25000,
                'type' => 'expense',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Transaksi Normal',
        ]);
    }
}
