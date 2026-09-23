<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;

class ReminderService
{
    /** Tetapkan porsi anggaran di mana mulai memberi peringatan. */
    public const BUDGET_THRESHOLD = 0.80;

    /** Reminder "belum catat hari ini" hanya tampil mulai jam ini. */
    public const DAILY_EMPTY_HOUR = 18;

    /** Maksimal item yang dikembalikan (jaga panel tetap ringkas). */
    public const MAX_ITEMS = 8;

    public function __construct(private ?Carbon $now = null)
    {
        $this->now ??= Carbon::now();
    }

    /**
     * Reminder untuk user, dihitung langsung dari data (tanpa cron).
     *
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    public function reminders(int $userId): array
    {
        $items = [];

        foreach ($this->budgetReminders($userId) as $item) {
            $items[] = $item;
        }

        if ($this->now->hour >= self::DAILY_EMPTY_HOUR) {
            $hasToday = Transaction::query()
                ->where('user_id', $userId)
                ->whereDate('transaction_date', $this->now->toDateString())
                ->exists();

            if (! $hasToday) {
                $items[] = [
                    'type' => 'daily',
                    'title' => 'Belum ada pencatatan hari ini',
                    'message' => 'Catat pemasukan atau pengeluaranmu hari ini, lalu lihat rekapnya di dashboard.',
                    'href' => route('transactions.create'),
                ];
            }
        }

        return array_slice($items, 0, self::MAX_ITEMS);
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function budgetReminders(int $userId): array
    {
        $monthStart = $this->now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $this->now->copy()->endOfMonth()->format('Y-m-d');

        $budgets = Budget::query()
            ->where('user_id', $userId)
            ->where('month', $this->now->month)
            ->where('year', $this->now->year)
            ->orderByRaw("CASE WHEN category = '' THEN 0 ELSE 1 END, category")
            ->get();

        if ($budgets->isEmpty()) {
            return [];
        }

        // Pengeluaran bulan berjalan per kategori (sekali query untuk semua budget).
        $categorySpent = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->select('category')
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // Pengeluaran keseluruhan bulan berjalan untuk anggaran global (category='').
        $overallSpent = (float) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $items = [];
        $dashboard = route('transactions.index');

        foreach ($budgets as $budget) {
            $spent = $budget->category === ''
                ? $overallSpent
                : (float) ($categorySpent[$budget->category] ?? 0);

            $limit = (float) $budget->amount;
            $pct = $limit > 0 ? $spent / $limit : 0.0;
            $label = $budget->category === ''
                ? 'Anggaran bulanan'
                : 'Anggaran '.$budget->category;

            if ($pct >= 1.0) {
                $items[] = [
                    'type' => 'budget-over',
                    'title' => $label.' terlampaui',
                    'message' => 'Sudah terpakai '.$this->rupiah($spent).' dari '.$this->rupiah($limit).' ('.number_format($pct * 100, 0, ',', '.').'%).',
                    'href' => $dashboard,
                ];
            } elseif ($pct >= self::BUDGET_THRESHOLD) {
                $items[] = [
                    'type' => 'budget-near',
                    'title' => $label.' hampir penuh',
                    'message' => 'Sisa '.$this->rupiah(max($limit - $spent, 0)).' ('.number_format($pct * 100, 0, ',', '.').'% terpakai).',
                    'href' => $dashboard,
                ];
            }
        }

        return $items;
    }

    private function rupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
