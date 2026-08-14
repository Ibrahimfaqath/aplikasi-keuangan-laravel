<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_can_be_created_with_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', [
            'title'            => 'Beli Kopi',
            'category'         => 'Makanan & Minuman',
            'amount'           => 25000,
            'type'             => 'expense',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'user_id'  => $user->id,
            'title'    => 'Beli Kopi',
            'category' => 'Makanan & Minuman',
        ]);
    }

    public function test_category_must_be_from_allowed_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', [
            'title'            => 'Transaksi Aneh',
            'category'         => 'Kategori Tidak Ada',
            'amount'           => 1000,
            'type'             => 'expense',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_index_page_shows_category_filter_and_breakdown(): void
    {
        $user = User::factory()->create();

        Transaction::create([
            'user_id'          => $user->id,
            'title'            => 'Makan Siang',
            'category'         => 'Makanan & Minuman',
            'amount'           => 50000,
            'type'             => 'expense',
            'transaction_date' => Carbon::now(),
        ]);

        Transaction::create([
            'user_id'          => $user->id,
            'title'            => 'Bensin',
            'category'         => 'Transportasi',
            'amount'           => 100000,
            'type'             => 'expense',
            'transaction_date' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertStatus(200);
        $response->assertSee('Transportasi');
        $response->assertViewHas('categoryExpenses', function (array $breakdown) {
            return isset($breakdown['Transportasi']) && $breakdown['Transportasi'] == 100000;
        });
    }
}
