<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTransactionTest extends TestCase  // ← Nama class HARUS AiTransactionTest
{
    use RefreshDatabase;

    public function test_ai_transaction_can_be_stored_single_item(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/ai/transactions', [
            'items' => [
                [
                    'title'            => 'Nasi Goreng',
                    'amount'           => 25000,
                    'type'             => 'expense',
                    'category'         => 'Makanan & Minuman',
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true, 'count' => 1]);

        $this->assertDatabaseHas('transactions', [
            'user_id'  => $user->id,
            'title'    => 'Nasi Goreng',
            'amount'   => 25000,
            'type'     => 'expense',
            'category' => 'Makanan & Minuman',
        ]);
    }

    public function test_ai_transaction_can_be_stored_multiple_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/ai/transactions', [
            'items' => [
                [
                    'title'            => 'Indomie',
                    'amount'           => 3500,
                    'type'             => 'expense',
                    'category'         => 'Makanan & Minuman',
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                ],
                [
                    'title'            => 'Telur 1kg',
                    'amount'           => 28000,
                    'type'             => 'expense',
                    'category'         => 'Belanja',
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true, 'count' => 2]);

        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'title' => 'Indomie']);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'title' => 'Telur 1kg']);
    }

    public function test_ai_transaction_validates_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/ai/transactions', [
            'items' => [
                [
                    'title'            => 'Transaksi Aneh',
                    'amount'           => 1000,
                    'type'             => 'expense',
                    'category'         => 'Kategori Tidak Ada',
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('transactions', ['user_id' => $user->id]);
    }

    public function test_ai_transaction_requires_auth(): void
    {
        $response = $this->postJson('/ai/transactions', [
            'items' => [
                [
                    'title'            => 'Nasi Goreng',
                    'amount'           => 25000,
                    'type'             => 'expense',
                    'category'         => 'Makanan & Minuman',
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertStatus(401);
    }
}