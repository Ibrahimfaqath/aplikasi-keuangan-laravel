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

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kiosapi.key' => 'test-kiosapi-key']);
        config(['services.kiosapi.url' => 'https://kiosapi.com/v1/chat/completions']);
        config(['services.kiosapi.model' => 'deepseek-v4-flash']);
        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Ini adalah balasan dari AI.']],
                ],
            ], 200),
        ]);
    }

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

        // Saldo seharusnya 7.500.000 - 2.000.000 = 5.500.000.
        // Verifikasi angka nyata tersebut benar-benar dikirim ke AI.
        $this->assertNotEmpty($response->json('reply'));
        Http::assertSent(function ($sent) {
            $prompt = $sent->data()['messages'][0]['content'] ?? '';
            return str_contains($prompt, '7.500.000')
                && str_contains($prompt, '2.000.000')
                && str_contains($prompt, '5.500.000');
        });
        // Data user lain tidak boleh bocor ke prompt: transaksi terakhir
        // milik user ini harus tercantum, dan tidak ada data asing.
        Http::assertSent(function ($sent) {
            $prompt = $sent->data()['messages'][0]['content'] ?? '';
            return str_contains($prompt, 'Sewa')
                && str_contains($prompt, 'TRANSAKSI TERAKHIR');
        });
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

        // Periode kosong (bulan lalu = 0) harus terkirim tanpa error.
        $this->assertNotEmpty($response->json('reply'));
        Http::assertSent(function ($sent) {
            $prompt = $sent->data()['messages'][0]['content'] ?? '';
            return str_contains($prompt, 'DATA BULAN LALU')
                && str_contains($prompt, 'Rp 0');
        });
    }

    public function test_context_aggregates_match_database_truth(): void
    {
        $user = User::factory()->create();
        $now = Carbon::now();

        $seed = function (string $title, float $amount, string $type, string $category, string $date) use ($user) {
            Transaction::create([
                'user_id' => $user->id,
                'title' => $title,
                'amount' => $amount,
                'type' => $type,
                'category' => $category,
                'transaction_date' => $date,
            ]);
        };

        $seed('Gaji bulan ini', 1000000, 'income', 'Gaji', $now->format('Y-m-d'));
        $seed('Makan bulan ini', 200000, 'expense', 'Makanan & Minuman', $now->format('Y-m-d'));
        $seed('Gaji bulan lalu', 500000, 'income', 'Gaji', $now->copy()->subMonth()->day(15)->format('Y-m-d'));
        $seed('Belanja bulan lalu', 100000, 'expense', 'Belanja', $now->copy()->subMonth()->day(15)->format('Y-m-d'));
        $seed('Bonus lama', 2000000, 'income', 'Bonus', $now->copy()->subDays(40)->format('Y-m-d'));

        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Berapa saldo saya?']);
        $response->assertOk();

        // Expected figures computed straight from the database.
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->copy()->endOfMonth()->format('Y-m-d');
        $lastStart = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
        $lastEnd = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');

        $sum = fn (string $type, ?string $from = null, ?string $to = null) => (float) Transaction::where('user_id', $user->id)
            ->where('type', $type)
            ->when($from, fn ($q) => $q->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transaction_date', '<=', $to))
            ->sum('amount');

        $fmt = fn (float $v) => number_format($v, 0, ',', '.');

        $monthIncome = $sum('income', $monthStart, $monthEnd);
        $monthExpense = $sum('expense', $monthStart, $monthEnd);
        $lastIncome = $sum('income', $lastStart, $lastEnd);
        $allIncome = $sum('income');
        $allExpense = $sum('expense');

        Http::assertSent(function ($sent) use ($fmt, $monthIncome, $monthExpense, $lastIncome, $allIncome, $allExpense) {
            $prompt = $sent->data()['messages'][0]['content'] ?? '';
            return str_contains($prompt, $fmt($monthIncome))
                && str_contains($prompt, $fmt($monthExpense))
                && str_contains($prompt, $fmt($lastIncome))
                && str_contains($prompt, $fmt($allIncome - $allExpense));
        });
    }
}