<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Kolom tanggal yang boleh dipakai sebagai dasar filter periode.
     *
     * Dipakai sebagai whitelist, bukan sekadar default: nama kolom masuk ke
     * SQL, jadi tidak boleh datang dari request.
     */
    private const DATE_COLUMNS = ['transaction_date', 'deleted_at'];

    /**
     * Arti setiap kode periode, dalam satu tempat.
     *
     * Dipakai dua kolom sekaligus: `transaction_date` (kapan transaksinya)
     * dan `deleted_at` (kapan dibuang ke Sampah). Keduanya menjawab pertanyaan
     * berbeda, jadi keduanya membaca definisi yang sama di sini.
     *
     * `$openEnded` menandai periode yang sengaja TIDAK dibatasi atas (dulu
     * `whereDate('>=', ...)` tanpa batas), sehingga transaksi bertanggal
     * ke depan tetap ikut terhitung seperti sebelumnya.
     *
     * @return array{start: Carbon, end: Carbon, openEnded: bool}|null null = tanpa batas
     */
    private function periodRange(string $period): ?array
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => ['start' => $today, 'end' => $today, 'openEnded' => false],
            'yesterday' => ['start' => $today->copy()->subDay(), 'end' => $today->copy()->subDay(), 'openEnded' => false],
            '7_days' => ['start' => $today->copy()->subDays(6), 'end' => $today, 'openEnded' => true],
            '30_days' => ['start' => $today->copy()->subDays(29), 'end' => $today, 'openEnded' => true],
            'this_month' => [
                'start' => $today->copy()->startOfMonth(),
                'end' => $today->copy()->endOfMonth(),
                'openEnded' => false,
            ],
            'last_month' => [
                'start' => $today->copy()->subMonthNoOverflow()->startOfMonth(),
                'end' => $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'openEnded' => false,
            ],
            'this_year' => [
                'start' => $today->copy()->startOfYear(),
                'end' => $today->copy()->endOfYear(),
                'openEnded' => false,
            ],
            default => null,
        };
    }

    /**
     * Membuat query transaksi milik user yang diberikan, difilter sesuai parameter.
     *
     * @param  array  $filters  search, type, period, start_date, end_date
     * @param  bool  $onlyTrashed  true = hanya transaksi di Sampah (soft-deleted)
     * @param  string  $dateColumn  kolom yang dipakai filter periode:
     *                              'transaction_date' (default) atau 'deleted_at'
     *                              untuk pertanyaan "kapan dibuang ke Sampah".
     * @return Builder
     */
    public function getFilteredQuery(
        array $filters,
        ?int $userId = null,
        bool $onlyTrashed = false,
        string $dateColumn = 'transaction_date',
    ) {
        $userId = $userId ?? request()->user()?->id;

        // Tanpa user yang jelas, jangan bocorkan data: kembalikan query kosong.
        if (! $userId) {
            return ($onlyTrashed ? Transaction::onlyTrashed() : Transaction::query())->whereRaw('1 = 0');
        }

        // Nama kolom masuk ke SQL — hanya terima yang sudah dikenal.
        if (! in_array($dateColumn, self::DATE_COLUMNS, true)) {
            $dateColumn = 'transaction_date';
        }

        // Kolom DATE disimpan sebagai 'Y-m-d'; kolom DATETIME butuh jam-menit
        // agar batas akhirnya tidak memotong baris di hari yang sama.
        $format = $dateColumn === 'deleted_at' ? 'Y-m-d H:i:s' : 'Y-m-d';

        $query = $onlyTrashed
            ? Transaction::onlyTrashed()->where('user_id', $userId)
            : Transaction::where('user_id', $userId);

        if (! empty($filters['search'])) {
            // Escape wildcard LIKE agar "%" / "_" user tidak jadi full-scan liar.
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $filters['search']);
            $query->where('title', 'like', '%'.$search.'%');
        }

        if (! empty($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category']) && in_array($filters['category'], Category::allNames($userId), true)) {
            $query->where('category', $filters['category']);
        }

        $period = $filters['period'] ?? 'all';
        $range = $this->periodRange(is_string($period) ? $period : 'all');

        if ($range) {
            // Perf: rentang membuat index (user_id, transaction_date) terpakai,
            // sedangkan whereMonth()/whereYear() membungkus kolom dengan fungsi
            // SQL sehingga index tidak bisa dipakai. Hasilnya identik.
            if ($range['openEnded']) {
                $query->whereDate($dateColumn, '>=', $range['start']->format($format));
            } else {
                $query->whereBetween($dateColumn, [
                    $range['start']->copy()->startOfDay()->format($format),
                    $range['end']->copy()->endOfDay()->format($format),
                ]);
            }
        } elseif ($period === 'custom') {
            if (! empty($filters['start_date'])) {
                try {
                    $start = Carbon::parse($filters['start_date'])->startOfDay()->format($format);
                    $query->whereDate($dateColumn, '>=', $start);
                } catch (\Throwable $e) {
                    // Abaikan tanggal invalid, jangan 500.
                }
            }
            if (! empty($filters['end_date'])) {
                try {
                    $end = Carbon::parse($filters['end_date'])->endOfDay()->format($format);
                    $query->whereDate($dateColumn, '<=', $end);
                } catch (\Throwable $e) {
                    // Abaikan tanggal invalid, jangan 500.
                }
            }
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
