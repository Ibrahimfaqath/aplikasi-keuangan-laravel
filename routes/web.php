<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// 1. Redirect halaman depan ke transaksi
Route::get('/', function () {
    return redirect('/transactions');
});

// 2. Route Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 3. Route terproteksi Auth
Route::middleware('auth')->group(function () {
    // Export Laporan Keuangan
    Route::get('/transactions/export-pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
    Route::get('/transactions/export-excel', [TransactionController::class, 'exportExcel'])->name('transactions.export-excel');

    // CRUD Utama Transaksi
    Route::resource('transactions', TransactionController::class);

    // Anggaran Bulanan
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';