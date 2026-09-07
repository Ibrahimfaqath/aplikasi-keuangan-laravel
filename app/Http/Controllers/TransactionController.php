<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Budget;
use App\Models\Transaction;
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
    public function index(Request $request)
    {
        $reportingService = new ReportingService;

        $filters = $request->only(['search', 'type', 'category', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters, Auth::id());
        $stats = $reportingService->getStatistics($query);
        $categoryExpenses = $reportingService->getCategoryBreakdown($query);

        $trendData = [
            'week' => $reportingService->getTrendSeries('week', Auth::id()),
            'month' => $reportingService->getTrendSeries('month', Auth::id()),
            'year' => $reportingService->getTrendSeries('year', Auth::id()),
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
            'transactions' => $transactions,
            'filters' => $filters,
            'budget' => $budget,
            'monthlyExpense' => $monthlyExpense,
            'categoryExpenses' => $categoryExpenses,
            'trendData' => $trendData,
        ], $stats));
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
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);

        if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
            Storage::disk('public')->delete($transaction->image);
        }

        $transaction->delete();

        return redirect('/transactions')->with('success', 'Transaksi berhasil dihapus!');
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
