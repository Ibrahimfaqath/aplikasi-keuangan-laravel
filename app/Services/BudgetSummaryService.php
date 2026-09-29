<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal untuk query & hitungan anggaran bulanan.
 *
 * Kartu ringkas di dashboard, halaman /budgets, dan ReminderService butuh data
 * yang sama; dikumpulkan di sini supaya angka progress di ketiganya tidak pernah
 * berbeda. `$now` selalu diteruskan eksplisit (bukan Carbon::now()) supaya
 * ReminderService tetap bisa diuji dengan tanggal buatan.
 */
class BudgetSummaryService
{
    /**
     * Baris anggaran satu bulan: batas keseluruhan (category='') diurutkan dulu,
     * lalu per kategori secara alfabetis.
     */
    public function budgetsForMonth(int $userId, Carbon $now): Collection
    {
        return Budget::query()
            ->where('user_id', $userId)
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->orderByRaw("CASE WHEN category = '' THEN 0 ELSE 1 END, category")
            ->get();
    }

    /**
     * Pecah hasil budgetsForMonth() jadi batas keseluruhan + daftar per kategori.
     *
     * @return array{overall: ?Budget, categories: Collection}
     */
    public function split(Collection $budgets): array
    {
        return [
            'overall' => $budgets->firstWhere('category', '') ?: null,
            'categories' => $budgets->where('category', '!==', '')->values(),
        ];
    }

    /**
     * Total pengeluaran bulan berjalan, seluruh kategori.
     */
    public function monthlyExpense(int $userId, Carbon $now): float
    {
        return (float) $this->expensesInMonth($userId, $now)->sum('amount');
    }

    /**
     * Pengeluaran bulan berjalan per kategori, sekali query untuk semua anggaran.
     *
     * @return array<string, float>
     */
    public function categorySpending(int $userId, Carbon $now): array
    {
        return $this->expensesInMonth($userId, $now)
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($total) => (float) $total)
            ->toArray();
    }

    /**
     * Angka progress satu batas anggaran terhadap pengeluaran — dipakai kartu
     * dashboard dan halaman /budgets supaya keduanya menghitung dengan rumus sama.
     *
     * @return array{percentage: int, remaining: float, isOver: bool, daysLeft: int, daily: float}
     */
    public function progress(float $limit, float $spent, Carbon $now): array
    {
        $remaining = $limit - $spent;
        $daysLeft = max(1, $now->daysInMonth - $now->day + 1);

        return [
            'percentage' => $limit > 0 ? (int) min(100, round(($spent / $limit) * 100)) : 0,
            'remaining' => $remaining,
            'isOver' => $remaining < 0,
            'daysLeft' => $daysLeft,
            'daily' => $remaining > 0 ? floor($remaining / $daysLeft) : 0,
        ];
    }

    /**
     * Builder dasar pengeluaran bulan berjalan.
     *
     * whereBetween memakai index (user_id, type, transaction_date), sedangkan
     * whereMonth()/whereYear() membungkus kolom dengan fungsi SQL sehingga index
     * tidak terpakai. Hasilnya identik untuk kolom DATE.
     */
    private function expensesInMonth(int $userId, Carbon $now): Builder
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $now->copy()->startOfMonth()->format('Y-m-d'),
                $now->copy()->endOfMonth()->format('Y-m-d'),
            ]);
    }
}
