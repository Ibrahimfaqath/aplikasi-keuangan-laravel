<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Budget;
use App\Exports\TransactionsExport;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $reportingService = new ReportingService();

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters);
        $stats = $reportingService->getStatistics($query);
        $categoryExpenses = $reportingService->getCategoryBreakdown($query);

        // Data line chart gabungan (pemasukan + pengeluaran) per periode:
        // minggu, bulan, dan tahun — real, per user
        $trendData = [
            'week'  => $reportingService->getTrendSeries('week'),
            'month' => $reportingService->getTrendSeries('month'),
            'year'  => $reportingService->getTrendSeries('year'),
        ];

        $transactions = $query->orderBy('transaction_date', 'desc')
                              ->latest()
                              ->paginate(10)
                              ->withQueryString();

        $now = Carbon::now();

        $budget = Budget::where('user_id', Auth::id())
                        ->where('month', $now->month)
                        ->where('year', $now->year)
                        ->first();

        $monthlyExpense = Transaction::where('user_id', Auth::id())
                        ->where('type', 'expense')
                        ->whereMonth('transaction_date', $now->month)
                        ->whereYear('transaction_date', $now->year)
                        ->sum('amount');

        return view('transactions.index', array_merge([
            'transactions'     => $transactions,
            'filters'          => $filters,
            'budget'           => $budget,
            'monthlyExpense'   => $monthlyExpense,
            'categoryExpenses' => $categoryExpenses,
            'trendData'        => $trendData,
        ], $stats));
    }

    public function exportPdf(Request $request)
    {
        $reportingService = new ReportingService();

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters);
        $stats = $reportingService->getStatistics($query);
        
        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        $pdf = Pdf::loadView('transactions.pdf', array_merge([
            'transactions' => $transactions,
            'filters'      => $filters,
            'printedAt'    => Carbon::now()->isoFormat('D MMMM YYYY, HH:mm') . ' WIB',
            'user'         => Auth::user()
        ], $stats))->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Keuangan_' . Carbon::now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);
        return Excel::download(new TransactionsExport($filters), 'Laporan_Keuangan_' . Carbon::now()->format('Ymd_His') . '.xlsx');
    }

    public function create()
    {
        return view('transactions.create');
    }

    public function store(Request $request)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => $this->normalizeAmount($request->input('amount'))]);
        }

        $request->validate([
            'title'            => 'required|string|max:255',
            'category'         => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'amount'           => 'required|numeric|min:1|max:999999999999.99',
            'type'             => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:20480',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeAndOptimizeImage($request->file('image'));
        }

        Transaction::create([
            'user_id'          => Auth::id(),
            'title'            => $request->title,
            'category'         => $request->category,
            'amount'           => $request->amount,
            'type'             => $request->type,
            'transaction_date' => $request->transaction_date,
            'image'            => $imagePath,
        ]);

        return redirect('/transactions')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        return view('transactions.edit', compact('transaction'));
    }

    public function update(Request $request, $id)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => $this->normalizeAmount($request->input('amount'))]);
        }

        $request->validate([
            'title'            => 'required|string|max:255',
            'category'         => ['required', 'string', 'max:50', Rule::in(Transaction::allCategories())],
            'amount'           => 'required|numeric|min:1|max:999999999999.99',
            'type'             => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:20480',
        ]);

        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        $imagePath = $transaction->image;

        if ($request->hasFile('image')) {
            if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
                Storage::disk('public')->delete($transaction->image);
            }
            $imagePath = $this->storeAndOptimizeImage($request->file('image'));
        }

        $transaction->update([
            'title'            => $request->title,
            'category'         => $request->category,
            'amount'           => $request->amount,
            'type'             => $request->type,
            'transaction_date' => $request->transaction_date,
            'image'            => $imagePath,
        ]);

        return redirect('/transactions')->with('success', 'Transaksi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);

        if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
            Storage::disk('public')->delete($transaction->image);
        }

        $transaction->delete();

        return redirect('/transactions')->with('success', 'Transaksi berhasil dihapus!');
    }

    /**
     * Menyimpan gambar bukti transaksi lalu mengompres/resize otomatis
     * agar file tidak membebani penyimpanan dan halaman.
     *
     * Foto kamera HP biasanya 3-8 MB; hasilnya dikonversi ke JPEG
     * (maks 1280px, kualitas 80) sehingga biasanya < 300 KB.
     */
    private function storeAndOptimizeImage(UploadedFile $file): string
    {
        // Simpan file asli dulu (masuk folder storage/app/public/receipts)
        $path = $file->store('receipts', 'public');

        try {
            $optimizedPath = $this->optimizeImage(Storage::disk('public')->path($path));
            if ($optimizedPath !== null && $optimizedPath !== $path) {
                Storage::disk('public')->delete($path);
                return $optimizedPath;
            }
        } catch (\Throwable $e) {
            // Abaikan error optimasi — file asli tetap dipakai
        }

        return $path;
    }

    /**
     * Memperkecil & mengompres gambar dengan GD. Mengembalikan path baru
     * (format JPEG) jika berhasil dan lebih kecil, atau null jika tidak.
     */
    private function optimizeImage(string $fullPath): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($fullPath);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null;
        }

        [$width, $height] = $info;
        $source = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($fullPath) : @imagecreatefrompng($fullPath);
        if (!$source) {
            return null;
        }

        // Koreksi orientasi foto HP (EXIF)
        if (function_exists('exif_read_data')) {
            $exif  = @exif_read_data($fullPath);
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? null;
            if ($angle !== null) {
                $source = imagerotate($source, $angle, 0);
                $width  = imagesx($source);
                $height = imagesy($source);
            }
        }

        // Resize jika lebih besar dari 1280px (tetap 1:1 untuk gambar kecil)
        $maxDim = 1280;
        $scale  = min(1, $maxDim / max($width, $height));
        if ($scale < 1) {
            $canvas = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, (int) round($width * $scale), (int) round($height * $scale), $width, $height);
            imagedestroy($source);
            $source = $canvas;
        }

        // Simpan sebagai JPEG kualitas 80
        $dir     = dirname($fullPath);
        $newName = pathinfo($fullPath, PATHINFO_FILENAME) . '-' . time() . '.jpg';
        $newFull = $dir . '/' . $newName;
        imagejpeg($source, $newFull, 80);
        imagedestroy($source);

        // Hanya pakai hasil optimasi jika benar-benar lebih kecil
        if (!file_exists($newFull) || filesize($newFull) >= filesize($fullPath)) {
            @unlink($newFull);
            return null;
        }

        return 'receipts/' . $newName;
    }

    /**
     * Normalisasi nilai nominal uang agar bersih dari simbol dan format ribuan.
     */
    private function normalizeAmount($value): float
    {
        $s = preg_replace('/[^0-9.,]/', '', (string) $value);
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace('.', '', $s);
        }

        return round((float) $s, 2);
    }
}