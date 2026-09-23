<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AmountFormatter;
use App\Services\AuditLogger;
use App\Services\CsvImportService;
use App\Services\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionImportController extends Controller
{
    public function index()
    {
        return view('transactions.import');
    }

    /**
     * Contoh file CSV (semicolon = standar Excel Indonesia) untuk diunduh user.
     */
    public function template()
    {
        $csv = implode("\n", [
            'Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal',
            '2026-09-23;Gaji Bulanan;Gaji;Pemasukan;5000000',
            '2026-09-22;Makan Siang;Makanan & Minuman;Pengeluaran;25000',
            '',
        ]);

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="template_transaksi.csv"');
    }

    public function preview(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $service = new CsvImportService(app(AmountFormatter::class));
            $parsed = $service->parse($validated['file']);
            $result = $service->importRows($parsed['rows'], $parsed['map'], (int) Auth::id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $token = Str::random(40);
        $filename = $validated['file']->getClientOriginalName();

        session(['import_draft' => [
            'token' => $token,
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'filename' => $filename,
        ]]);

        return view('transactions.import-preview', [
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'token' => $token,
            'filename' => $filename,
        ]);
    }

    public function store(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $validated = $request->validate([
            'token' => 'required|string|max:64',
        ]);

        $draft = session('import_draft');

        if (! is_array($draft) || ! isset($draft['token']) || ! hash_equals($draft['token'], $validated['token'])) {
            return redirect()->route('transactions.import')->with('error', 'Sesi import telah berakhir. Silakan unggah file ulang.');
        }

        $valid = $draft['valid'] ?? [];

        if (! is_array($valid) || $valid === []) {
            session()->forget('import_draft');

            return redirect()->route('transactions.import')->with('error', 'Tidak ada transaksi valid untuk diimpor.');
        }

        // Atomik: kalau satu gagal, semua dibatalkan (tanpa data setengah jadi).
        DB::transaction(function () use ($valid) {
            foreach ($valid as $row) {
                AuditLogger::begin('import');
                Transaction::create([
                    'user_id' => Auth::id(),
                    'title' => $row['title'],
                    'category' => $row['category'],
                    'amount' => $row['amount'],
                    'type' => $row['type'],
                    'transaction_date' => $row['transaction_date'],
                    'image' => null,
                ]);
            }
        });

        session()->forget('import_draft');

        $count = count($valid);
        $message = $count === 1 ? '1 transaksi berhasil diimpor!' : $count.' transaksi berhasil diimpor!';

        return redirect()->route('transactions.index')->with('success', $message);
    }
}
