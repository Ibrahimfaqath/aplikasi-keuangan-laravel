<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // Fungsi 1: Menampilkan semua daftar transaksi
    public function index()
    {
        $transactions = Transaction::all();
        return view('transactions.index', compact('transactions'));
    }

    // Fungsi 2: BARU - Menampilkan halaman form tambah transaksi
    public function create()
    {
        return view('transactions.create');
    }

    // Fungsi 3: Menyimpan data dari form ke database
    public function store(Request $request)
    {
        // 1. Validasi input dari user
        $request->validate([
            'title'  => 'required',
            'amount' => 'required|numeric',
            'type'   => 'required|in:income,expense',
        ]);

        // 2. Simpan ke database MySQL menggunakan Model Transaction
        Transaction::create([
            'title'  => $request->title,
            'amount' => $request->amount,
            'type'   => $request->type,
        ]);

        // 3. Kembalikan ke halaman daftar transaksi
        return redirect('/transactions');
    }

    // Fungsi 4: Menghapus data transaksi berdasarkan ID
    public function destroy($id)
    {
        // 1. Cari data transaksi berdasarkan ID, lalu hapus
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        // 2. Kembalikan ke halaman daftar transaksi
        return redirect('/transactions');
    }

    // Fungsi 5: Menampilkan halaman Form Edit beserta datanya
    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);
        return view('transactions.edit', compact('transaction'));
    }

    // Fungsi 6: Memperbarui data yang sudah diedit ke database MySQL
    public function update(Request $request, $id)
    {
        // 1. Validasi input
        $request->validate([
            'title'  => 'required',
            'amount' => 'required|numeric',
            'type'   => 'required|in:income,expense',
        ]);

        // 2. Cari data dan perbarui
        $transaction = Transaction::findOrFail($id);
        $transaction->update([
            'title'  => $request->title,
            'amount' => $request->amount,
            'type'   => $request->type,
        ]);

        // 3. Kembalikan ke halaman daftar transaksi
        return redirect('/transactions');
    }
}