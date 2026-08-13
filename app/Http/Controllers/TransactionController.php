<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Budget;
use App\Exports\TransactionsExport;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $reportingService = new ReportingService();

        $filters = $request->only(['search', 'type', 'period', 'start_date', 'end_date']);

        $query = $reportingService->getFilteredQuery($filters);
        $stats = $reportingService->getStatistics($query);

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
            'transactions'   => $transactions,
            'filters'        => $filters,
            'budget'         => $budget,
            'monthlyExpense' => $monthlyExpense,
        ], $stats));
    }

    public function exportPdf(Request $request)
    {
        $reportingService = new ReportingService();

        $filters = $request->only(['search', 'type', 'period', 'start_date', 'end_date']);

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
        $filters = $request->only(['search', 'type', 'period', 'start_date', 'end_date']);
        return Excel::download(new TransactionsExport($filters), 'Laporan_Keuangan_' . Carbon::now()->format('Ymd_His') . '.xlsx');
    }

    public function create()
    {
        return view('transactions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'amount'           => 'required|numeric|min:1',
            'type'             => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('receipts', 'public');
        }

        Transaction::create([
            'user_id'          => Auth::id(),
            'title'            => $request->title,
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
        $request->validate([
            'title'            => 'required|string|max:255',
            'amount'           => 'required|numeric|min:1',
            'type'             => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        $imagePath = $transaction->image;

        if ($request->hasFile('image')) {
            if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
                Storage::disk('public')->delete($transaction->image);
            }
            $imagePath = $request->file('image')->store('receipts', 'public');
        }

        $transaction->update([
            'title'            => $request->title,
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
}