<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiConfirmTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_is_created_on_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Beli Kopi',
                'amount' => 15000,
                'type' => 'expense',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
        ]);
    }

    public function test_transaction_is_not_created_on_cancel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/cancel');

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmation_fails_with_invalid_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Transaksi Aneh',
                'amount' => 1000,
                'type' => 'expense',
                'category' => 'Kategori Tidak Valid',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmation_fails_with_invalid_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Transaksi Aneh',
                'amount' => 1000,
                'type' => 'invalid_type',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmation_fails_with_invalid_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Transaksi Aneh',
                'amount' => 0,
                'type' => 'expense',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_ownership_is_always_auth_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this->actingAs($userA)
            ->postJson('/ai/confirm', [
                'title' => 'Gaji',
                'amount' => 5000000,
                'type' => 'income',
                'category' => 'Gaji',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userA->id,
            'title' => 'Gaji',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $userB->id,
        ]);
    }
}