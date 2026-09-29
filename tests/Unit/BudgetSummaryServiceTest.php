<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BudgetSummaryService jadi sumber tunggal angka progress anggaran, jadi rumus
 * dan query-nya diuji terpisah dari view.
 */
class BudgetSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BudgetSummaryService
    {
        return app(BudgetSummaryService::class);
    }

    private function now(): Carbon
    {
        return Carbon::create(2026, 3, 15);
    }

    public function test_split_menempatkan_anggaran_keseluruhan_terpisah(): void
    {
        $user = User::factory()->create();
        $now = $this->now();

        Budget::create(['user_id' => $user->id, 'month' => 3, 'year' => 2026, 'category' => '', 'amount' => 3000000]);
        Budget::create(['user_id' => $user->id, 'month' => 3, 'year' => 2026, 'category' => 'Transportasi', 'amount' => 400000]);
        Budget::create(['user_id' => $user->id, 'month' => 3, 'year' => 2026, 'category' => 'Makanan & Minuman', 'amount' => 800000]);

        $split = $this->service()->split(
            $this->service()->budgetsForMonth($user->id, $now)
        );

        $this->assertNotNull($split['overall']);
        $this->assertSame('3000000.00', $split['overall']->amount);
        $this->assertCount(2, $split['categories']);
        // Urut alfabetis setelah keseluruhan.
        $this->assertSame(['Makanan & Minuman', 'Transportasi'], $split['categories']->pluck('category')->all());
    }

    public function test_budgets_bulan_lain_tidak_termasuk(): void
    {
        $user = User::factory()->create();

        Budget::create(['user_id' => $user->id, 'month' => 3, 'year' => 2026, 'category' => '', 'amount' => 1000000]);
        Budget::create(['user_id' => $user->id, 'month' => 4, 'year' => 2026, 'category' => '', 'amount' => 2000000]);

        $split = $this->service()->split(
            $this->service()->budgetsForMonth($user->id, $this->now())
        );

        $this->assertSame('1000000.00', $split['overall']->amount);
        $this->assertTrue($split['categories']->isEmpty());
    }

    public function test_anggaran_user_lain_tidak_terbaca(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Budget::create(['user_id' => $other->id, 'month' => 3, 'year' => 2026, 'category' => '', 'amount' => 9999999]);

        $this->assertTrue($this->service()->budgetsForMonth($user->id, $this->now())->isEmpty());
    }

    public function test_monthly_expense_hanya_menghitung_pengeluaran_bulan_berjalan(): void
    {
        $user = User::factory()->create();

        // Pengeluaran bulan berjalan (15 Mar).
        Transaction::create([
            'user_id' => $user->id, 'title' => 'Makan', 'category' => 'Makanan & Minuman',
            'amount' => 250000, 'type' => 'expense', 'transaction_date' => '2026-03-15',
        ]);
        Transaction::create([
            'user_id' => $user->id, 'title' => 'Bensin', 'category' => 'Transportasi',
            'amount' => 100000, 'type' => 'expense', 'transaction_date' => '2026-03-01',
        ]);
        // Bulan lain, dan pemasukan bulan yang sama — keduanya tidak boleh masuk.
        Transaction::create([
            'user_id' => $user->id, 'title' => 'Februari', 'category' => 'Makanan & Minuman',
            'amount' => 500000, 'type' => 'expense', 'transaction_date' => '2026-02-28',
        ]);
        Transaction::create([
            'user_id' => $user->id, 'title' => 'Gaji', 'category' => 'Gaji',
            'amount' => 9000000, 'type' => 'income', 'transaction_date' => '2026-03-05',
        ]);

        $this->assertSame(350000.0, $this->service()->monthlyExpense($user->id, $this->now()));
    }

    public function test_category_spending_dikelompokkan_per_kategori(): void
    {
        $user = User::factory()->create();

        Transaction::create([
            'user_id' => $user->id, 'title' => 'Makan 1', 'category' => 'Makanan & Minuman',
            'amount' => 250000, 'type' => 'expense', 'transaction_date' => '2026-03-10',
        ]);
        Transaction::create([
            'user_id' => $user->id, 'title' => 'Makan 2', 'category' => 'Makanan & Minuman',
            'amount' => 150000, 'type' => 'expense', 'transaction_date' => '2026-03-11',
        ]);
        Transaction::create([
            'user_id' => $user->id, 'title' => 'Bensin', 'category' => 'Transportasi',
            'amount' => 100000, 'type' => 'expense', 'transaction_date' => '2026-03-12',
        ]);

        $spending = $this->service()->categorySpending($user->id, $this->now());

        $this->assertSame(['Makanan & Minuman' => 400000.0, 'Transportasi' => 100000.0], $spending);
    }

    public function test_progress_menghitung_sisa_dan_porsi(): void
    {
        $p = $this->service()->progress(2000000, 500000, $this->now());

        $this->assertSame(25, $p['percentage']);
        $this->assertSame(1500000.0, $p['remaining']);
        $this->assertFalse($p['isOver']);
    }

    public function test_progress_terkunci_di_100_saat_melebihi_batas(): void
    {
        $p = $this->service()->progress(1000000, 1400000, $this->now());

        $this->assertSame(100, $p['percentage'], 'Persentase tidak boleh >100.');
        $this->assertSame(-400000.0, $p['remaining']);
        $this->assertTrue($p['isOver']);
    }

    public function test_progress_hitung_jatah_harian_dengan_batas_hari_terakhir(): void
    {
        // 15 Mar di bulan 31 hari -> 17 hari tersisa (15..31).
        $p = $this->service()->progress(2000000, 500000, $this->now());

        $this->assertSame(17, $p['daysLeft']);
        $this->assertSame(88235.0, $p['daily'], 'floor(1500000 / 17)');
    }

    public function test_progress_aman_saat_batas_nol(): void
    {
        $p = $this->service()->progress(0, 500000, $this->now());

        $this->assertSame(0, $p['percentage'], 'Pembagian nol tidak boleh jadi error/NaN.');
        $this->assertTrue($p['isOver']);
    }

    public function test_progress_bulan_panjang_tidak_membagi_nol(): void
    {
        // Tanggal 31: hanya tersisa 1 hari, bukan 0.
        $p = $this->service()->progress(1000000, 400000, Carbon::create(2026, 1, 31));

        $this->assertSame(1, $p['daysLeft']);
        $this->assertSame(600000.0, $p['daily']);
    }
}
