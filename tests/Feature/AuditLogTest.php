<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Waktu deterministik supaya filter periode & format tanggal stabil.
        $this->travelTo(now()->setDate(2026, 9, 15));
    }

    public function test_login_fires_auth_login_log(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/transactions');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
            'source' => 'web',
        ]);
    }

    public function test_logout_fires_auth_logout_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
    }

    public function test_transaction_lifecycle_is_logged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/transactions', [
            'title' => 'Gaji Bulanan',
            'category' => 'Gaji',
            'amount' => 'Rp 5.000.000',
            'type' => 'income',
            'transaction_date' => '2026-09-01',
        ])->assertRedirect('/transactions');

        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_type' => Transaction::class,
            'model_id' => $transaction->id,
            'action' => 'created',
            'source' => 'web',
        ]);

        $this->put("/transactions/{$transaction->id}", [
            'title' => 'Gaji Bulanan Diperbarui',
            'category' => 'Gaji',
            'amount' => '5.500.000',
            'type' => 'income',
            'transaction_date' => '2026-09-01',
        ])->assertRedirect('/transactions');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_id' => $transaction->id,
            'action' => 'updated',
            'description' => 'Ubah Transaksi: Gaji Bulanan Diperbarui — Rp 5.500.000 (Pemasukan, kategori: Gaji) (perubahan: title, amount)',
        ]);

        $this->delete("/transactions/{$transaction->id}")->assertRedirect('/transactions');
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_id' => $transaction->id,
            'action' => 'deleted',
        ]);

        $this->post("/transactions/{$transaction->id}/restore");
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_id' => $transaction->id,
            'action' => 'restored',
        ]);

        // Transaksi sudah kembali aktif; masukkan ke Sampah dulu sebelum hapus permanen.
        $this->delete("/transactions/{$transaction->id}")->assertRedirect('/transactions');
        $this->delete("/transactions/{$transaction->id}/force-destroy");
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_id' => $transaction->id,
            'action' => 'force_deleted',
        ]);
    }

    public function test_budget_create_and_update_are_logged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from('/transactions')->post('/budgets', ['amount' => '1.500.000']);
        $budget = Budget::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_type' => Budget::class,
            'model_id' => $budget->id,
            'action' => 'created',
        ]);

        // updateOrCreate pada baris yang sama → event updated.
        $this->from('/transactions')->post('/budgets', ['amount' => '2.000.000']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_id' => $budget->id,
            'action' => 'updated',
        ]);
    }

    public function test_category_create_and_delete_are_logged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/categories', ['name' => 'Jualan Online', 'type' => 'income'])
            ->assertRedirect(route('categories.index'));

        $category = Category::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_type' => Category::class,
            'model_id' => $category->id,
            'action' => 'created',
        ]);

        $this->delete(route('categories.destroy', $category->id));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'model_id' => $category->id,
            'action' => 'deleted',
        ]);
    }

    public function test_ai_store_transactions_logged_with_source_ai(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/ai/transactions', [
            'items' => [
                ['title' => 'Gaji Bulan Ini', 'category' => 'Gaji', 'amount' => 5000000, 'type' => 'income', 'transaction_date' => '2026-09-01'],
                ['title' => 'Kopi', 'category' => 'Makanan & Minuman', 'amount' => 25000, 'type' => 'expense', 'transaction_date' => '2026-09-02'],
            ],
        ])->assertJsonPath('success', true);

        $this->assertSame(2, AuditLog::where('user_id', $user->id)->where('action', 'created')->where('source', 'ai')->count());
    }

    public function test_audit_page_lists_only_current_users_logs(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->actingAs($userA);

        Transaction::create([
            'user_id' => $userA->id,
            'title' => 'Transaksi Rahasia A',
            'category' => 'Gaji',
            'amount' => 100000,
            'type' => 'income',
            'transaction_date' => '2026-09-01',
        ]);
        Transaction::create([
            'user_id' => $userB->id,
            'title' => 'Transaksi Rahasia B',
            'category' => 'Gaji',
            'amount' => 200000,
            'type' => 'income',
            'transaction_date' => '2026-09-01',
        ]);

        $this->get('/audit')
            ->assertOk()
            ->assertSee('Transaksi Rahasia A')
            ->assertDontSee('Transaksi Rahasia B');
    }

    public function test_audit_page_filters_by_action_and_model(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/transactions', [
            'title' => 'Filter Saya',
            'category' => 'Gaji',
            'amount' => '1.000.000',
            'type' => 'income',
            'transaction_date' => '2026-09-01',
        ]);

        $this->get('/audit?model=transaction&action=created')
            ->assertOk()
            ->assertSee('Filter Saya');

        $this->get('/audit?action=deleted')
            ->assertOk()
            ->assertDontSee('Filter Saya');
    }

    public function test_audit_log_never_stores_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->update(['name' => 'Nama Baru']);

        $log = AuditLog::where('user_id', $user->id)->where('action', 'updated')->firstOrFail();
        $newValues = (array) $log->new_values;

        $this->assertArrayHasKey('name', $newValues);
        $this->assertArrayNotHasKey('password', $newValues);
        $this->assertStringNotContainsString(bcrypt('secret'), json_encode($newValues));
    }

    public function test_deleting_user_hard_removes_logs_user_reference_without_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Sebelum Akun Dihapus',
            'category' => 'Makanan & Minuman',
            'amount' => 50000,
            'type' => 'expense',
            'transaction_date' => '2026-09-01',
        ]);

        // Hapus user permanen: FK nullOnDelete harus men-null-kan user_id semua
        // baris log miliknya tanpa error.
        $user->delete();

        $this->assertSame(0, AuditLog::where('model_type', Transaction::class)->where('user_id', $user->id)->count());
        $this->assertSame(1, AuditLog::where('model_type', Transaction::class)->whereNull('user_id')->count());
    }
}
