<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialContextBuilder
{
    /**
     * Bangun seluruh konteks keuangan untuk system prompt dalam 3 query:
     * satu agregat kondisional semua periode, satu rincian kategori bulan
     * berjalan, dan 20 transaksi terakhir.
     *
     * Batas periode selaras dengan ReportingService::getFilteredQuery sehingga
     * angka yang dihasilkan identik dengan implementasi sebelumnya.
     *
     * @return array{allTime: array, thisMonth: array, monthCategories: array, lastMonth: array, thisWeek: array, thisYear: array, recent: Collection, lastMonthDate: Carbon}
     */
    public function build(int $userId, Carbon $now): array
    {
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->copy()->endOfMonth()->format('Y-m-d');
        $lastMonthDate = $now->copy()->subMonth();
        $lastStart = $lastMonthDate->copy()->startOfMonth()->format('Y-m-d');
        $lastEnd = $lastMonthDate->copy()->endOfMonth()->format('Y-m-d');
        $weekStart = $now->copy()->subDays(6)->format('Y-m-d');
        $yearStart = $now->copy()->startOfYear()->format('Y-m-d');
        $yearEnd = $now->copy()->endOfYear()->format('Y-m-d');

        $row = Transaction::where('user_id', $userId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS all_income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS all_expense")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS month_income", [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS month_expense", [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS last_income", [$lastStart, $lastEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS last_expense", [$lastStart, $lastEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date >= ? THEN amount ELSE 0 END), 0) AS week_income", [$weekStart])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date >= ? THEN amount ELSE 0 END), 0) AS week_expense", [$weekStart])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS year_income", [$yearStart, $yearEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END), 0) AS year_expense", [$yearStart, $yearEnd])
            ->first();

        $stats = static function (float $income, float $expense): array {
            return [
                'totalIncome' => $income,
                'totalExpense' => $expense,
                'totalBalance' => $income - $expense,
            ];
        };

        $reportingService = new ReportingService;
        $monthCategories = $reportingService->getCategoryBreakdown(
            $reportingService->getFilteredQuery(['period' => 'this_month'], $userId)
        );

        $recent = Transaction::where('user_id', $userId)
            ->orderBy('transaction_date', 'desc')
            ->limit(20)
            ->get(['title', 'amount', 'type', 'category', 'transaction_date']);

        return [
            'allTime' => $stats((float) $row->all_income, (float) $row->all_expense),
            'thisMonth' => $stats((float) $row->month_income, (float) $row->month_expense),
            'monthCategories' => $monthCategories,
            'lastMonth' => $stats((float) $row->last_income, (float) $row->last_expense),
            'thisWeek' => $stats((float) $row->week_income, (float) $row->week_expense),
            'thisYear' => $stats((float) $row->year_income, (float) $row->year_expense),
            'recent' => $recent,
            'lastMonthDate' => $lastMonthDate,
        ];
    }

    public function formatCategoryBreakdown(array $categories): string
    {
        if (empty($categories)) {
            return '  (Belum ada data pengeluaran bulan ini)';
        }

        $result = [];
        foreach ($categories as $category => $amount) {
            $result[] = "  - {$category}: Rp ".number_format($amount, 0, ',', '.');
        }

        return implode("\n", $result);
    }
}
