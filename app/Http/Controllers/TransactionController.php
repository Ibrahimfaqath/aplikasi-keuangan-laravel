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
     *
     * Bedanya dari /transactions: filter periode di sini menjawab "kapan
     * dibuang ke Sampah", bukan "kapan transaksinya terjadi" — jadi kolom
     * yang disaring adalah `deleted_at`. Transaksi yang masih aktif tidak
     * mungkin muncul di sini karena query-nya dibatasi `onlyTrashed()`.
     */
    public function trashed(Request $request)
    {
        $reportingService = new ReportingService;

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters, Auth::id(), true, 'deleted_at');
        $stats = $reportingService->getStatistics($query);

        $transactions = $query->orderBy('deleted_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Dua kondisi yang harus dibedakan: Sampah memang tidak ada isinya,
        // atau isinya tidak cocok dengan filter. Kalau keduanya memakai pesan
        // yang sama, user mengira datanya hilang padahal masih ada.
        $hasActiveFilters = $this->hasActiveFilters($filters);

        $data = array_merge([
            'transactions' => $transactions,
            'filters' => $filters,
            'hasActiveFilters' => $hasActiveFilters,
            // Demo read-only: seluruh kontrol tulis disembunyikan, bukan
            // dikunci satu per satu. Server tetap menolak aksinya apa pun yang
            // lolos (DemoMode::warn), jadi ini hanya lapisan antarmuka.
            'trashLocked' => DemoMode::isEnabled() && DemoMode::isDemoUser(Auth::user()),
        ], $stats);

        // Filter tanpa reload, pola yang sama dengan /transactions: server
        // tetap merender halaman penuh sebagai sumber kebenaran, `?partial=1`
        // hanya mengembalikan potongan yang berubah.
        if ($request->boolean('partial')) {
            return response()->json([
                'total' => $transactions->total(),
                'tableHtml' => view('transactions.partials.trash-table', [
                    'transactions' => $transactions,
                    'hasActiveFilters' => $hasActiveFilters,
                ])->render(),
            ]);
        }

        return view('transactions.trashed', $data);
    }

    /**
     * Filter aktif = ada nilai yang bukan pilihan netral.
     *
     * 'all' adalah nilai default Periode, jadi bukan filter aktif. Tanpa
     * pengecualian ini, `period=all` — yang memang selalu ikut terkirim
     * bersama filter lain — akan membuat tombol reset muncul sia-sia.
     */
    private function hasActiveFilters(array $filters): bool
    {
        return ($filters['search'] ?? '') !== ''
            || ($filters['type'] ?? '') !== ''
            || ($filters['category'] ?? '') !== ''
            || (($filters['period'] ?? '') !== '' && ($filters['period'] ?? '') !== 'all');
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

        return redirect()->route('transactions.trashed')
            ->with('success', 'Transaksi berhasil dipulihkan.')
            // Satu item = satu transaksi; kalau 40 item dipulihkan sekaligus,
            // "Urungkan" jadi tidak bermakna (user tidak ingat mana yang salah).
            ->with('undo_restore', [
                'id' => $transaction->id,
                'title' => $transaction->title,
            ]);
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

        $this->deleteReceipt($transaction->image);
        $transaction->forceDelete();

        return redirect()->route('transactions.trashed')->with('success', 'Transaksi dihapus permanen.');
    }

    /**
     * Pulihkan beberapa transaksi sekaligus.
     *
     * Dipisah dari `restore()` tunggal karena bulk-or-nothing di sini berarti
     * satu id nakal tidak boleh membatalkan 39 pemulihan yang berhasil — dan
     * keamanannya tetap sama: id selalu difilter `user_id` + `onlyTrashed`.
     */
    public function bulkRestore(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $ids = $this->selectedTrashedIds($request);

        if ($ids === []) {
            return redirect()->route('transactions.trashed')
                ->with('error', 'Tidak ada transaksi yang dipilih.');
        }

        $restored = 0;
        foreach ($this->trashedQuery($ids)->get() as $transaction) {
            $transaction->restore();
            $restored++;
        }

        return redirect()->route('transactions.trashed')
            ->with('success', $restored.' transaksi berhasil dipulihkan.');
    }

    /**
     * Hapus permanen beberapa transaksi terpilih.
     */
    public function bulkDestroy(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $ids = $this->selectedTrashedIds($request);

        if ($ids === []) {
            return redirect()->route('transactions.trashed')
                ->with('error', 'Tidak ada transaksi yang dipilih.');
        }

        $destroyed = 0;
        foreach ($this->trashedQuery($ids)->get() as $transaction) {
            $this->deleteReceipt($transaction->image);
            $transaction->forceDelete();
            $destroyed++;
        }

        return redirect()->route('transactions.trashed')
            ->with('success', $destroyed.' transaksi dihapus permanen.');
    }

    /**
     * Kosongkan Sampah: hapus permanen SELURUH isi Sampah milik user ini.
     *
     * Karena jangkauan tidak ditentukan user, aksi paling berbahaya di halaman
     * ini tidak cukup dengan satu klik. Ia menuntut konfirmasi yang diketik
     * sendiri, supaya tidak bisa terjadi karena jari meleset.
     */
    public function emptyTrash(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        if ($request->input('confirm') !== self::EMPTY_TRASH_CONFIRMATION) {
            return redirect()->route('transactions.trashed')
                ->with('error', 'Kosongkan Sampah dibatalkan — konfirmasi tidak cocok.');
        }

        $destroyed = 0;
        foreach ($this->trashedQuery()->cursor() as $transaction) {
            $this->deleteReceipt($transaction->image);
            $transaction->forceDelete();
            $destroyed++;
        }

        return redirect()->route('transactions.trashed')
            ->with('success', $destroyed.' transaksi dihapus permanen. Sampah sekarang kosong.');
    }

    /**
     * Kata yang harus diketik ulang untuk mengonfirmasi Kosongkan Sampah.
     */
    public const EMPTY_TRASH_CONFIRMATION = 'HAPUS';

    /**
     * Batas jumlah id dalam satu permintaan bulk.
     *
     * Tidak ada halaman yang menampilkan lebih dari ini, jadi angka ini hanya
     * menahan form yang dibuat-buat. `array_slice` diam-diam memotong sisanya
     * supaya user tidak mendapat error 500.
     */
    private const MAX_BULK_IDS = 500;

    /**
     * Id terpilih dari form, sudah dibatasi jumlah dan dibuang duplikatnya.
     *
     * Bentuknya bisa berupa array (`ids[]=1&ids[]=2`) maupun satu string CSV
     * (`ids=1,2`). Yang kedua dipakai toolbar bulk: ia menuliskan pilihan dari
     * state Alpine lewat satu hidden input, bukan menyalin ulang seluruh blok
     * checkbox ke setiap form.
     *
     * Duplikat itu hal yang nyata, bukan rekaan: checkbox desktop dan mobile
     * memakai `ids[]` yang sama, jadi satu transaksi bisa terkirim dua kali.
     *
     * @return array<int, int>
     */
    private function selectedTrashedIds(Request $request): array
    {
        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (! is_array($ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0,
        )));

        return array_slice($ids, 0, self::MAX_BULK_IDS);
    }

    /**
     * Query transaksi di Sampah milik user ini saja.
     *
     * Dua klausa di bawah ini adalah batas keamanan, bukan sekadar filter: id
     * dari form tidak boleh bisa menyentuh baris orang lain, dan tidak boleh
     * menyentuh transaksi yang masih aktif.
     */
    private function trashedQuery(?array $ids = null)
    {
        $query = Transaction::onlyTrashed()->where('user_id', Auth::id());

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        return $query->orderByDesc('deleted_at')->orderByDesc('id');
    }

    /**
     * Hapus bukti foto dari storage. Bukti sengaja TIDAK dihapus saat soft
     * delete justru supaya restore tidak kehilangan lampiran.
     */
    private function deleteReceipt(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
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
