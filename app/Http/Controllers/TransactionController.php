<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\BudgetSummaryService;
use App\Services\DemoMode;
use App\Services\ReportingService;
use App\Services\TransactionParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function trend(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|in:week,month,year',
        ]);

        $period = $validated['period'] ?? 'week';
        $reportingService = new ReportingService;

        return response()->json($reportingService->getTrendSeries($period, Auth::id()));
    }

    public function index(Request $request)
    {
        $reportingService = new ReportingService;

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters, Auth::id());
        $stats = $reportingService->getStatistics($query);
        $categoryExpenses = $reportingService->getCategoryBreakdown($query);

        // Perf: hanya hitung tren 'week' saat load awal (1 query).
        // 'month' & 'year' dimuat lazy via /transactions/trend saat user klik tab.
        // Struktur tetap sama agar Blade/JS lama tidak rusak.
        $emptySeries = ['labels' => [], 'income' => [], 'expense' => [], 'ranges' => []];
        $trendData = [
            'week' => $reportingService->getTrendSeries('week', Auth::id()),
            'month' => $emptySeries,
            'year' => $emptySeries,
        ];

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $now = Carbon::now();

        // Kartu anggaran di dashboard sengaja ringkas: batas keseluruhan, jumlah
        // anggaran per kategori, dan pengeluaran bulan ini. Perhitungan per
        // kategori + daftar kategori hidup di /budgets (BudgetController::index).
        $budgetService = new BudgetSummaryService;
        $budgets = $budgetService->split($budgetService->budgetsForMonth(Auth::id(), $now));

        $showAnalytics = $this->shouldShowAnalytics($request, $transactions);

        $data = array_merge([
            'transactions' => $transactions,
            'filters' => $filters,
            'budget' => $budgets['overall'],
            'categoryBudgets' => $budgets['categories'],
            'monthlyExpense' => $budgetService->monthlyExpense(Auth::id(), $now),
            'categoryExpenses' => $categoryExpenses,
            'trendData' => $trendData,
            'showAnalytics' => $showAnalytics,
        ], $stats);

        // Filter tanpa reload: halaman penuh tetap dirender server sebagai sumber
        // kebenaran (jalan tanpa JS, shareable URL,first paint). `?partial=1`
        // hanya mengembalikan potongan yang benar-benar berubah, supaya fetch
        // bisa menimpanya tanpa bikin layout melompat.
        //
        // Penting: filter memengaruhi bukan hanya tabel, tapi juga angka
        // Ringkasan dan grafik donat karena keduanya dihitung dari query yang
        // sama. Kalau hanya tabel yang dikirim, kartu Ringkasan akan
        // menampilkan angka filter sebelumnya — lebih membingungkan daripada
        // reload biasa.
        if ($request->boolean('partial')) {
            return response()->json([
                'stats' => [
                    'totalBalance' => $data['totalBalance'] ?? 0,
                    'totalIncome' => $data['totalIncome'] ?? 0,
                    'totalExpense' => $data['totalExpense'] ?? 0,
                ],
                'categoryExpenses' => $categoryExpenses,
                'showAnalytics' => $showAnalytics,
                'total' => $transactions->total(),
                'tableHtml' => view('transactions.partials.table', [
                    'transactions' => $transactions,
                ])->render(),
            ]);
        }

        return view('transactions.index', $data);
    }

    /**
     * Kartu anggaran + analisis disembunyikan saat data masih sedikit, supaya
     * dashboard tidak dipenuhi angka nol. Begitu ada filter aktif, isinya
     * ditampilkan walaupun hasil filter cuma sedikit.
     *
     * 'all' adalah nilai default Periode, jadi bukan filter aktif. Tanpa
     * pengecualian ini, request apa pun yang menyertakan `period=all` (yang
     * memang selalu dikirim mesin filter) akan memaksa blok ini tampil.
     */
    private function shouldShowAnalytics(Request $request, $transactions): bool
    {
        return $transactions->total() >= 5
            || $request->filled('search')
            || $request->filled('type')
            || $request->filled('category')
            || ($request->filled('period') && $request->input('period') !== 'all');
    }

    public function parseVoice(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        $parsed = TransactionParser::fromText($request->text);

        return response()->json([
            'data' => $parsed,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $reportingService = new ReportingService;

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters, Auth::id());
        $stats = $reportingService->getStatistics($query);

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        $pdf = Pdf::loadView('transactions.pdf', array_merge([
            'transactions' => $transactions,
            'filters' => $filters,
            'printedAt' => Carbon::now()->isoFormat('D MMMM YYYY, HH:mm').' WIB',
            'user' => Auth::user(),
        ], $stats))->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Keuangan_'.Carbon::now()->format('Ymd_His').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        return Excel::download(new TransactionsExport($filters, Auth::id()), 'Laporan_Keuangan_'.Carbon::now()->format('Ymd_His').'.xlsx');
    }

    public function create()
    {
        return view('transactions.create');
    }

    public function store(StoreTransactionRequest $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeAndOptimizeImage($request->file('image'));
        }

        Transaction::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'transaction_date' => $validated['transaction_date'],
            'image' => $imagePath,
        ]);

        return redirect('/transactions')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function edit(string $id)
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);

        return view('transactions.edit', compact('transaction'));
    }

    public function update(UpdateTransactionRequest $request, string $id)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $validated = $request->validated();

        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        $imagePath = $transaction->image;

        if ($request->hasFile('image')) {
            if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
                Storage::disk('public')->delete($transaction->image);
            }
            $imagePath = $this->storeAndOptimizeImage($request->file('image'));
        }

        $transaction->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'transaction_date' => $validated['transaction_date'],
            'image' => $imagePath,
        ]);

        return redirect('/transactions')->with('success', 'Transaksi berhasil diperbarui!');
    }

    public function destroy(string $id)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);

        // Soft delete: baris disembunyikan dari laporan/dashboard tapi tetap
        // ada di DB — bisa dipulihkan dari halaman Sampah. Bukti gambar sengaja
        // dipertahankan agar restore tidak kehilangan lampiran.
        $transaction->delete();

        return redirect('/transactions')->with('success', 'Transaksi dipindahkan ke Sampah. Bisa dipulihkan kapan saja di menu Sampah.');
    }

    /**
     * Halaman Sampah: daftar transaksi yang di-soft-delete, berikut statistiknya.
     */
    public function trashed(Request $request)
    {
        $reportingService = new ReportingService;

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters, Auth::id(), true);
        $stats = $reportingService->getStatistics($query);

        $transactions = $query->orderBy('deleted_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('transactions.trashed', array_merge([
            'transactions' => $transactions,
            'filters' => $filters,
        ], $stats));
    }

    /**
     * Pulihkan transaksi yang masih di Sampah kembali ke daftar aktif.
     */
    public function restore(string $id)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $transaction = Transaction::onlyTrashed()->where('user_id', Auth::id())->findOrFail($id);
        $transaction->restore();

        return redirect()->route('transactions.trashed')->with('success', 'Transaksi berhasil dipulihkan.');
    }

    /**
     * Hapus permanen: baris dihapus dari DB dan bukti gambar ikut dihapus.
     */
    public function forceDestroy(string $id)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $transaction = Transaction::onlyTrashed()->where('user_id', Auth::id())->findOrFail($id);

        if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
            Storage::disk('public')->delete($transaction->image);
        }

        $transaction->forceDelete();

        return redirect()->route('transactions.trashed')->with('success', 'Transaksi dihapus permanen.');
    }

    private function storeAndOptimizeImage(UploadedFile $file): string
    {
        $path = $file->store('receipts', 'public');

        try {
            $optimizedPath = $this->optimizeImage(Storage::disk('public')->path($path));
            if ($optimizedPath !== null && $optimizedPath !== $path) {
                Storage::disk('public')->delete($path);

                return $optimizedPath;
            }
        } catch (\Throwable $e) {
            // Error ditoleransi
        }

        return $path;
    }

    private function optimizeImage(string $fullPath): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($fullPath);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null;
        }

        [$width, $height] = $info;
        $source = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($fullPath) : @imagecreatefrompng($fullPath);
        if (! $source) {
            return null;
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($fullPath);
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? null;
            if ($angle !== null) {
                $source = imagerotate($source, $angle, 0);
                $width = imagesx($source);
                $height = imagesy($source);
            }
        }

        $maxDim = 1280;
        $scale = min(1, $maxDim / max($width, $height));
        if ($scale < 1) {
            $canvas = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, (int) round($width * $scale), (int) round($height * $scale), $width, $height);
            imagedestroy($source);
            $source = $canvas;
        }

        $dir = dirname($fullPath);
        $newName = pathinfo($fullPath, PATHINFO_FILENAME).'-'.time().'.jpg';
        $newFull = $dir.'/'.$newName;
        imagejpeg($source, $newFull, 80);
        imagedestroy($source);

        if (! file_exists($newFull) || filesize($newFull) >= filesize($fullPath)) {
            @unlink($newFull);

            return null;
        }

        return 'receipts/'.$newName;
    }
}
