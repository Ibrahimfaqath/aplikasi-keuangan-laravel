<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(User $user, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Beli Kopi',
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'amount' => 15000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ], $overrides));
    }

    public function test_delete_moves_transaction_to_trash_not_hard_delete(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Baris masih ada di DB dengan deleted_at terisi (soft delete).
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame(1, Transaction::withTrashed()->where('user_id', $user->id)->count());
        $this->assertSame(0, Transaction::where('user_id', $user->id)->count());
    }

    public function test_soft_deleted_transaction_is_hidden_from_index_page(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user, ['title' => 'RAHASIA Dihapus']);

        $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertDontSee('RAHASIA Dihapus');
    }

    public function test_trashed_page_lists_deleted_transactions_only(): void
    {
        $user = User::factory()->create();
        $deleted = $this->makeTransaction($user, ['title' => 'Masuk Sampah']);
        $active = $this->makeTransaction($user, ['title' => 'Tetap Aktif']);

        $this->actingAs($user)->delete(route('transactions.destroy', $deleted));

        $response = $this->actingAs($user)->get(route('transactions.trashed'));

        $response->assertOk();
        $response->assertSee('Masuk Sampah');
        $response->assertDontSee('Tetap Aktif');
    }

    public function test_trashed_page_supports_search_filter(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user, ['title' => 'Makan Malam'])
            ->delete();
        $this->makeTransaction($user, ['title' => 'Bensin Motor'])
            ->delete();

        $response = $this->actingAs($user)->get(route('transactions.trashed', ['search' => 'Bensin']));

        $response->assertOk();
        $response->assertSee('Bensin Motor');
        $response->assertDontSee('Makan Malam');
    }

    public function test_restore_brings_transaction_back_to_active(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)
            ->post(route('transactions.restore', $transaction))
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame(1, Transaction::where('user_id', $user->id)->count());

        // Setelah dipulihkan, muncul lagi di dashboard.
        $this->actingAs($user)->get(route('transactions.index'))->assertSee('Beli Kopi');
    }

    public function test_restore_preserves_receipt_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('receipts/bukti.jpg', 'data');

        $transaction = $this->makeTransaction($user, ['image' => 'receipts/bukti.jpg']);
        $transaction->delete();

        // Gambar TIDAK dihapus saat soft delete (agar bisa dipulihkan).
        $this->assertTrue(Storage::disk('public')->exists('receipts/bukti.jpg'));
        $this->assertNotNull($transaction->fresh()->image);

        $this->actingAs($user)->post(route('transactions.restore', $transaction));

        $this->assertTrue(Storage::disk('public')->exists('receipts/bukti.jpg'));
        $this->assertSame('receipts/bukti.jpg', $transaction->fresh()->image);
    }

    public function test_force_destroy_removes_transaction_permanently(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)
            ->delete(route('transactions.force-destroy', $transaction))
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertSame(0, Transaction::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_force_destroy_removes_receipt_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('receipts/bukti.jpg', 'data');

        $transaction = $this->makeTransaction($user, ['image' => 'receipts/bukti.jpg']);
        $transaction->delete();

        $this->actingAs($user)->delete(route('transactions.force-destroy', $transaction));

        $this->assertFalse(Storage::disk('public')->exists('receipts/bukti.jpg'));
    }

    public function test_user_cannot_soft_delete_other_users_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = $this->makeTransaction($owner);

        $this->actingAs($intruder)
            ->delete(route('transactions.destroy', $transaction))
            ->assertNotFound();

        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_restore_other_users_trashed_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = $this->makeTransaction($owner);
        $transaction->delete();

        $this->actingAs($intruder)
            ->post(route('transactions.restore', $transaction))
            ->assertNotFound();

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_force_destroy_other_users_trashed_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = $this->makeTransaction($owner);
        $transaction->delete();

        $this->actingAs($intruder)
            ->delete(route('transactions.force-destroy', $transaction))
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_guest_cannot_access_trashed_routes(): void
    {
        $route = route('transactions.trashed');

        $this->get($route)->assertRedirect(route('login'));
        $this->post(route('transactions.restore', 1))->assertRedirect(route('login'));
        $this->delete(route('transactions.force-destroy', 1))->assertRedirect(route('login'));
    }

    public function test_demo_user_cannot_restore_or_force_destroy(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        $transaction = $this->makeTransaction($demo);
        $transaction->delete();

        $this->actingAs($demo)
            ->post(route('transactions.restore', $transaction))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);

        $this->actingAs($demo)
            ->delete(route('transactions.force-destroy', $transaction))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }
}
