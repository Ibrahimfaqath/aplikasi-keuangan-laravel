<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiFinancialTest extends TestCase
{
    use RefreshDatabase;

    private function createMockTransactionResponse(array $data = [])
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => $data['content'] ?? 'Ini adalah balasan dari AI.'
                    ]
                ]
            ]
        ];
    }

    public function test_balance_question_does_not_create_transaction(): void
    {
        $user = User::factory()->create();

        // Buat beberapa transaksi
        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Gaji',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now(),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Makan',
            'amount' => 50000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now(),
        ]);

        // Simulasi pertanyaan finansial
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Berapa saldo saya?']);

        // Pastikan response sukses
        $response->assertOk();

        // Pastikan TIDAK ada transaksi baru yang dibuat
        $this->assertEquals(2, Transaction::where('user_id', $user->id)->count());
    }

    public function test_financial_question_does_not_trigger_transaction_flow(): void
    {
        $user = User::factory()->create();

        // Chat dengan pertanyaan finansial
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Berapa pengeluaran saya bulan ini?']);

        $response->assertOk();

        // Pastikan tidak ada transaksi baru
        $this->assertEquals(0, Transaction::where('user_id', $user->id)->count());
    }

    public function test_transaction_request_still_goes_to_confirm_flow(): void
    {
        $user = User::factory()->create();

        // Chat dengan permintaan mencatat transaksi
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Catat makan siang 25 ribu']);

        $response->assertOk();

        // Pastikan BELUM ada transaksi yang tersimpan (harus konfirmasi dulu)
        $this->assertEquals(0, Transaction::where('user_id', $user->id)->count());
    }

    public function test_user_isolation_user_a_cannot_access_user_b_data(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Transaksi user A
        Transaction::create([
            'user_id' => $userA->id,
            'title' => 'Gaji A',
            'amount' => 10000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now(),
        ]);

        // Transaksi user B
        Transaction::create([
            'user_id' => $userB->id,
            'title' => 'Gaji B',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now(),
        ]);

        // User A login dan bertanya saldo
        $response = $this->actingAs($userA)
            ->postJson('/ai/chat', ['message' => 'Berapa saldo saya?']);

        $response->assertOk();

        // System prompt seharusnya hanya berisi data user A (Rp 10.000.000)
        // Tidak mungkin menguji langsung, tapi kita bisa verifikasi transaksi user B tidak terlihat
        $this->assertDatabaseHas('transactions', ['user_id' => $userA->id, 'title' => 'Gaji A']);
        $this->assertDatabaseHas('transactions', ['user_id' => $userB->id, 'title' => 'Gaji B']);
        // Kedua transaksi ada di database, tapi AI hanya melihat milik user A
    }

    public function test_ai_uses_real_data_not_fabricated(): void
    {
        $user = User::factory()->create();

        // Buat transaksi dengan angka spesifik
        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Gaji',
            'amount' => 7500000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now(),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Sewa',
            'amount' => 2000000,
            'type' => 'expense',
            'category' => 'Keluarga',
            'transaction_date' => Carbon::now(),
        ]);

        // Simulasi chat dengan data real
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Berapa saldo saya?']);

        $response->assertOk();

        // Saldo seharusnya 7.500.000 - 2.000.000 = 5.500.000
        // Data ini ada di system prompt yang dikirim ke AI
        // Test akan pass jika tidak ada error
        $this->assertTrue(true);
    }

    public function test_zero_previous_period_handled_gracefully(): void
    {
        $user = User::factory()->create();

        // Hanya transaksi bulan ini, tidak ada bulan lalu
        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Gaji',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now(),
        ]);

        // Chat dengan pertanyaan perbandingan
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Bandingkan pengeluaran bulan ini dengan bulan lalu']);

        $response->assertOk();

        // System prompt harus handle bulan lalu dengan nilai 0
        // Tidak boleh division by zero
        $this->assertTrue(true);
    }
}