<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\AmountFormatter;
use App\Services\DemoMode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function __construct(private AmountFormatter $amountFormatter) {}

    /**
     * Halaman kelola anggaran: ringkasan bulan berjalan + anggaran per kategori.
     * Dashboard hanya menampilkan versi ringkas — semua aksi (atur/ubah/hapus)
     * dilakukan di sini.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();

        // Semua baris anggaran bulan berjalan: keseluruhan (category='') + per kategori.
        $budgets = Budget::where('user_id', Auth::id())
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->orderByRaw("CASE WHEN category = '' THEN 0 ELSE 1 END, category")
            ->get();

        $budget = $budgets->firstWhere('category', '') ?: null;
        $categoryBudgets = $budgets->where('category', '!==', '')->values();

        // whereBetween memakai index (user_id, type, transaction_date).
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->copy()->endOfMonth()->format('Y-m-d');
        $monthlyExpense = Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        // Pengeluaran bulan berjalan per kategori, untuk progress anggaran kategori.
        $categorySpent = Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->whereNotNull('category')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($total) => (float) $total)
            ->toArray();

        return view('budgets.index', [
            'budget' => $budget,
            'categoryBudgets' => $categoryBudgets,
            'categorySpent' => $categorySpent,
            'monthlyExpense' => $monthlyExpense,
            'expenseCategories' => Category::namesFor(Auth::id(), 'expense'),
        ]);
    }

    /**
     * Menyimpan (membuat atau memperbarui) anggaran pengeluaran.
     *
     * Mendukung:
     * - kategori kosong ('') = anggaran keseluruhan, atau nama kategori pengeluaran
     *   untuk anggaran per kategori;
     * - months > 1 = terapkan nominal yang sama ke beberapa bulan berurutan
     *   (default 1 = hanya bulan berjalan).
     */
    public function store(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        // Terima format Rupiah ramah pengguna ("Rp 1.500.000", "1.500.000", "1500000")
        // normalize() return null kalau kosong/tanpa digit -> validasi required gagal (tidak jadi 0).
        $request->merge(['amount' => $this->amountFormatter->normalize($request->input('amount'))]);

        $expenseCategories = Category::namesFor(Auth::id(), 'expense');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:999999999999.99',
            'category' => ['nullable', 'string', 'max:50', Rule::in(['', ...$expenseCategories])],
            'months' => 'nullable|integer|min:1|max:12',
        ]);

        $amount = $validated['amount'];
        $category = trim((string) ($validated['category'] ?? ''));
        $months = (int) ($validated['months'] ?? 1);

        $start = Carbon::now();
        for ($i = 0; $i < $months; $i++) {
            $monthOf = $start->copy()->addMonthsNoOverflow($i);

            Budget::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'month' => $monthOf->month,
                    'year' => $monthOf->year,
                    'category' => $category,
                ],
                ['amount' => $amount]
            );
        }

        $label = $category === '' ? 'Anggaran bulanan' : 'Anggaran '.$category;
        $message = $months > 1
            ? $label.' berhasil disimpan untuk '.$months.' bulan!'
            : $label.' berhasil disimpan!';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Menghapus satu baris anggaran milik user yang sedang login.
     */
    public function destroy(Request $request, Budget $budget)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $budget = Budget::where('user_id', Auth::id())->findOrFail($budget->id);
        $budget->delete();

        return redirect()->back()->with('success', 'Anggaran berhasil dihapus!');
    }
}
