<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportingService
{
    /**
     * Membuat query transaksi milik user yang sedang login, difilter sesuai parameter.
     *
     * @param  array  $filters  search, type, period, start_date, end_date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getFilteredQuery(array $filters)
    {
        $query = Transaction::where('user_id', Auth::id());

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $period = $filters['period'] ?? 'all';
        $today  = Carbon::today();

        switch ($period) {
            case 'today':
                $query->whereDate('transaction_date', $today);
                break;
            case 'yesterday':
                $query->whereDate('transaction_date', Carbon::yesterday());
                break;
            case '7_days':
                $query->whereDate('transaction_date', '>=', $today->copy()->subDays(6));
                break;
            case '30_days':
                $query->whereDate('transaction_date', '>=', $today->copy()->subDays(29));
                break;
            case 'this_month':
                $query->whereMonth('transaction_date', $today->month)
                      ->whereYear('transaction_date', $today->year);
                break;
            case 'last_month':
                $lastMonth = $today->copy()->subMonth();
                $query->whereMonth('transaction_date', $lastMonth->month)
                      ->whereYear('transaction_date', $lastMonth->year);
                break;
            case 'this_year':
                $query->whereYear('transaction_date', $today->year);
                break;
            case 'custom':
                if (!empty($filters['start_date'])) {
                    $query->whereDate('transaction_date', '>=', $filters['start_date']);
                }
                if (!empty($filters['end_date'])) {
                    $query->whereDate('transaction_date', '<=', $filters['end_date']);
                }
                break;
        }

        return $query;
    }

    /**
     * Menghitung total saldo, pemasukan, dan pengeluaran dari sebuah query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array{totalIncome: float, totalExpense: float, totalBalance: float}
     */
    public function getStatistics($query)
    {
        $totalIncome  = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'totalIncome'  => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalBalance' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Rincian pengeluaran per kategori (untuk grafik donat).
     * Mengikuti filter aktif yang sama dengan tabel transaksi.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array<string, float>  contoh: ['Makanan & Minuman' => 150000, ...]
     */
    public function getCategoryBreakdown($query)
    {
        return (clone $query)
            ->where('type', 'expense')
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'category')
            ->map(fn ($total) => (float) $total)
            ->toArray();
    }

    /**
     * Data grafik garis gabungan (pemasukan + pengeluaran) per periode.
     * Dipakai di line chart dashboard dengan toggle Minggu / Bulan / Tahun.
     *
     * @param  string  $period  'week' | 'month' | 'year'
     * @return array{labels: string[], income: float[], expense: float[]}
     */
    public function getTrendSeries(string $period = 'week'): array
    {
        $today  = Carbon::today();
        $userId = Auth::id();

        // Nama hari pendek sesuai dayOfWeek Carbon (0=Minggu .. 6=Sabtu)
        $shortDays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        $labels  = [];
        $income  = [];
        $expense = [];

        if ($period === 'year') {
            for ($i = 11; $i >= 0; $i--) {
                $date = $today->copy()->subMonthsNoOverflow($i);

                $labels[]  = $date->locale('id')->isoFormat('MMM');
                $income[]  = (float) Transaction::where('user_id', $userId)
                    ->where('type', 'income')
                    ->whereYear('transaction_date', $date->year)
                    ->whereMonth('transaction_date', $date->month)
                    ->sum('amount');
                $expense[] = (float) Transaction::where('user_id', $userId)
                    ->where('type', 'expense')
                    ->whereYear('transaction_date', $date->year)
                    ->whereMonth('transaction_date', $date->month)
                    ->sum('amount');
            }
        } else {
            $days = $period === 'month' ? 30 : 7;

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);

                $labels[]  = $period === 'month' ? $date->format('d M') : $shortDays[$date->dayOfWeek];
                $income[]  = (float) Transaction::where('user_id', $userId)
                    ->where('type', 'income')
                    ->whereDate('transaction_date', $date)
                    ->sum('amount');
                $expense[] = (float) Transaction::where('user_id', $userId)
                    ->where('type', 'expense')
                    ->whereDate('transaction_date', $date)
                    ->sum('amount');
            }
        }

        return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
    }
}
