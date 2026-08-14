<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Menampilkan ringkasan keuangan dengan data asli dari database.
     */
    public function index()
    {
        $userId = Auth::id();
        $now    = Carbon::now();

        $totalSaldo = Transaction::where('user_id', $userId)
            ->get(['type', 'amount'])
            ->sum(fn ($transaction) => $transaction->type === 'income' ? $transaction->amount : -$transaction->amount);

        $pemasukan = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('amount');

        $pengeluaran = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('amount');

        $recentTransactions = Transaction::where('user_id', $userId)
            ->orderByDesc('transaction_date')
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboard', compact('totalSaldo', 'pemasukan', 'pengeluaran', 'recentTransactions'));
    }
}
