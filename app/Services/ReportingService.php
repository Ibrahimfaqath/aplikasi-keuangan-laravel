<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Membuat query transaksi milik user yang diberikan, difilter sesuai parameter.
     *
     * @param  array  $filters  search, type, period, start_date, end_date
     * @return Builder
     */
    public function getFilteredQuery(array $filters, ?int $userId = null)
    {
        $userId = $userId ?? request()->user()?->id;

        // Tanpa user yang jelas, jangan bocorkan data: kembalikan query kosong.
        if (! $userId) {
            return Transaction::whereRaw('1 = 0');
        }

        $query = Transaction::where('user_id', $userId);

        if (! empty($filters['search'])) {
            // Escape wildcard LIKE agar "%" / "_" user tidak jadi full-scan liar.
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $filters['search']);
            $query->where('title', 'like', '%'.$search.'%');
        }

        if (! empty($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category']) && in_array($filters['category'], Transaction::allCategories(), true)) {
            $query->where('category', $filters['category']);
        }

        $allowedPeriods = ['today', 'yesterday', '7_days', '30_days', 'this_month', 'last_month', 'this_year', 'custom', 'all'];
        $period = $filters['period'] ?? 'all';
        if (! in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }
        $today = Carbon::today();

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
                // Perf: whereBetween memakai index range, sedangkan whereMonth()/
                // whereYear() membungkus kolom dengan fungsi SQL sehingga index
                // (user_id, transaction_date) tidak terpakai. Hasil identik (DATE).
                $query->whereBetween('transaction_date', [
                    $today->copy()->startOfMonth()->format('Y-m-d'),
                    $today->copy()->endOfMonth()->format('Y-m-d'),
                ]);
                break;
            case 'last_month':
                $lastMonth = $today->copy()->subMonth();
                $query->whereBetween('transaction_date', [
                    $lastMonth->copy()->startOfMonth()->format('Y-m-d'),
                    $lastMonth->copy()->endOfMonth()->format('Y-m-d'),
                ]);
                break;
            case 'this_year':
                $query->whereBetween('transaction_date', [
                    $today->copy()->startOfYear()->format('Y-m-d'),
                    $today->copy()->endOfYear()->format('Y-m-d'),
                ]);
                break;
            case 'custom':
                if (! empty($filters['start_date'])) {
                    try {
                        $start = Carbon::parse($filters['start_date'])->format('Y-m-d');
                        $query->whereDate('transaction_date', '>=', $start);
                    } catch (\Throwable $e) {
                        // Abaikan tanggal invalid, jangan 500.
                    }
                }
                if (! empty($filters['end_date'])) {
                    try {
                        $end = Carbon::parse($filters['end_date'])->format('Y-m-d');
                        $query->whereDate('transaction_date', '<=', $end);
                    } catch (\Throwable $e) {
                        // Abaikan tanggal invalid, jangan 500.
                    }
                }
                break;
        }

        return $query;
    }

    /**
     * Menghitung total saldo, pemasukan, dan pengeluaran dari sebuah query.
     *
     * @param  Builder  $query
     * @return array{totalIncome: float, totalExpense: float, totalBalance: float}
     */
    public function getStatistics($query)
    {
        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalBalance' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Rincian pengeluaran per kategori (untuk grafik donat).
     * Mengikuti filter aktif yang sama dengan tabel transaksi.
     *
     * @param  Builder  $query
     * @return array<string, float> contoh: ['Makanan & Minuman' => 150000, ...]
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
     * @return array{labels: string[], income: float[], expense: float[], ranges: string[]}
     */
    public function getTrendSeries(string $period = 'week', ?int $userId = null): array
    {
        $today = Carbon::today();
        $userId = $userId ?? request()->user()?->id;

        // Nama hari pendek sesuai dayOfWeek Carbon (0=Minggu .. 6=Sabtu)
        $shortDays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        if ($period === 'year') {
            $startDate = $today->copy()->subMonthsNoOverflow(11)->startOfMonth();
            $endDate = $today->copy()->endOfMonth();

            // 1 query: GROUP BY year, month, type
            // Gunakan YEAR()/MONTH() agar kompatibel MySQL & SQLite
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $yExpr = "strftime('%Y', transaction_date)";
                $mExpr = "strftime('%m', transaction_date)";
            } else {
                $yExpr = 'YEAR(transaction_date)';
                $mExpr = 'MONTH(transaction_date)';
            }

            $rows = Transaction::where('user_id', $userId)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("{$yExpr} as y, {$mExpr} as m, type, SUM(amount) as total")
                ->groupByRaw("{$yExpr}, {$mExpr}, type")
                ->get();

            // Bangun map: 'YYYY-MM' => ['income' => x, 'expense' => y]
            $map = [];
            foreach ($rows as $row) {
                $key = str_pad((string) $row->y, 4, '0', STR_PAD_LEFT).'-'.str_pad((string) $row->m, 2, '0', STR_PAD_LEFT);
                $map[$key][$row->type] = (float) $row->total;
            }

            $labels = $income = $expense = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = $today->copy()->subMonthsNoOverflow($i);
                $key = $date->format('Y-m');
                $labels[] = $date->locale('id')->isoFormat('MMM');
                $income[] = $map[$key]['income'] ?? 0.0;
                $expense[] = $map[$key]['expense'] ?? 0.0;
            }
        } elseif ($period === 'month') {
            // Bulan: agregasi PER MINGGU (Senin sebagai awal minggu).
            // Rentang 30 hari → dikelompokkan jadi Minggu 1..N (maks 6 bucket),
            // dengan range tanggal aktual per bucket untuk tooltip.
            $days = 30;
            $startDate = $today->copy()->subDays($days - 1);

            // 1 query: GROUP BY date, type (tetap per hari, dikelompokkan di PHP)
            $rows = Transaction::where('user_id', $userId)
                ->where('transaction_date', '>=', $startDate)
                ->selectRaw('DATE(transaction_date) as d, type, SUM(amount) as total')
                ->groupByRaw('DATE(transaction_date), type')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $map[$row->d][$row->type] = (float) $row->total;
            }

            $labels = $income = $expense = $ranges = [];
            $weekNo = 0;
            $cursor = $startDate->copy()->startOfWeek(Carbon::MONDAY);
            while ($cursor->lte($today)) {
                $bucketStart = $cursor->copy()->lt($startDate) ? $startDate->copy() : $cursor->copy();
                $weekEnd = $cursor->copy()->addDays(6);
                $bucketEnd = $weekEnd->gt($today) ? $today->copy() : $weekEnd->copy();

                $sumInc = $sumExp = 0.0;
                $d = $bucketStart->copy();
                while ($d->lte($bucketEnd)) {
                    $key = $d->format('Y-m-d');
                    $sumInc += $map[$key]['income'] ?? 0.0;
                    $sumExp += $map[$key]['expense'] ?? 0.0;
                    $d->addDay();
                }

                $weekNo++;
                $labels[] = 'Minggu '.$weekNo;
                $ranges[] = $bucketStart->locale('id')->translatedFormat('d M').' – '.$bucketEnd->locale('id')->translatedFormat('d M');
                $income[] = round($sumInc, 0);
                $expense[] = round($sumExp, 0);

                $cursor->addWeek();
            }
        } else {
            $days = 7;
            $startDate = $today->copy()->subDays($days - 1);

            // 1 query: GROUP BY date, type
            $rows = Transaction::where('user_id', $userId)
                ->where('transaction_date', '>=', $startDate)
                ->selectRaw('DATE(transaction_date) as d, type, SUM(amount) as total')
                ->groupByRaw('DATE(transaction_date), type')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $map[$row->d][$row->type] = (float) $row->total;
            }

            $labels = $income = $expense = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $shortDays[$date->dayOfWeek];
                $income[] = $map[$key]['income'] ?? 0.0;
                $expense[] = $map[$key]['expense'] ?? 0.0;
            }
        }

        return ['labels' => $labels, 'income' => $income, 'expense' => $expense, 'ranges' => $ranges ?? []];
    }
}
