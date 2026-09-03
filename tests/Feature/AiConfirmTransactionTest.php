<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AiConfirmTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_is_created_on_confirmation(): void
    {
        $user = User::factory()->create();

        // Set up pending transaction from AI flow (simulating AI detection)
        Session::put('pending_transaction', [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

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

        // Set up pending transaction from AI flow
        Session::put('pending_transaction', [
            'title' => 'Beli Apa',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

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

        // Set up pending transaction from AI flow
        Session::put('pending_transaction', [
            'title' => 'Beli Apa',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

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

        // Set up pending transaction from AI flow
        Session::put('pending_transaction', [
            'title' => 'Beli Apa',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

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

        // Set up pending transaction for user A from AI flow
        Session::put('pending_transaction', [
            'title' => 'Gaji',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

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

        // User B cannot confirm user A's pending transaction
        // First, clear the pending session and set up user B's own pending
        Session::forget('pending_transaction');
        Session::put('pending_transaction', [
            'title' => 'Belanja',
            'amount' => 50000,
            'type' => 'expense',
            'category' => 'Belanja',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $responseB = $this->actingAs($userB)
            ->postJson('/ai/confirm', [
                'title' => 'Belanja',
                'amount' => 50000,
                'type' => 'expense',
                'category' => 'Belanja',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $responseB->assertOk();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userB->id,
            'title' => 'Belanja',
        ]);

        // Verify user A's transaction still only belongs to user A
        $this->assertDatabaseHas('transactions', [
            'user_id' => $userA->id,
            'title' => 'Gaji',
        ]);

        // User B should NOT have user A's transaction
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $userB->id,
            'title' => 'Gaji',
        ]);
    }
}