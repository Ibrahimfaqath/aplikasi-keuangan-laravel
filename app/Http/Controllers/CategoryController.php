<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Halaman Kelola Kategori: kategori bawaan aplikasi + kategori custom
     * milik user, dikelompokkan per jenis transaksi, lengkap dengan berapa
     * kali tiap kategori dipakai di transaksi.
     */
    public function index()
    {
        $userId = Auth::id();

        $income = Category::availableFor($userId, 'income');
        $expense = Category::availableFor($userId, 'expense');

        // Berapa kali tiap kategori dipakai. Satu query GROUP BY, memakai
        // index (user_id, category) yang sudah ada di tabel transactions.
        $usage = Transaction::where('user_id', $userId)
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($total) => (int) $total)
            ->all();

        $all = array_merge($income, $expense);
        $names = array_column($all, 'name');

        // Hanya kategori yang masih terdaftar ikut dihitung, supaya angka
        // ringkasan konsisten dengan yang tampil di layar.
        $listed = array_intersect_key($usage, array_flip($names));

        return view('categories.index', [
            'income' => $income,
            'expense' => $expense,
            'usage' => $usage,
            'stats' => [
                'total' => count($all),
                'custom' => count(array_filter($all, fn ($item) => ! $item['is_global'])),
                'used' => count($listed),
                'unused' => count($all) - count($listed),
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'type' => ['required', Rule::in(['income', 'expense'])],
        ]);

        $name = trim($validated['name']);

        // Hindari duplikat: nama yang sama di daftar legal user (bawaan global
        // maupun custom) ditolak. Nama "Lainnya" sudah ada di dua jenis, jadi
        // cek per jenis transaksi.
        if (in_array($name, Category::namesFor(Auth::id(), $validated['type']), true)) {
            return back()->withErrors([
                'name' => 'Kategori "'.$name.'" sudah tersedia untuk jenis transaksi ini.',
            ])->withInput();
        }

        Category::create([
            'user_id' => Auth::id(),
            'name' => $name,
            'type' => $validated['type'],
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori "'.$name.'" berhasil ditambahkan.');
    }

    /**
     * Hapus sebuah kategori custom milik user.
     *
     * Transaksi lama tetap menyimpan kategori sebagai string (transactions.category),
     * jadi menghapus definisi kategori TIDAK merusak riwayat; nama tinggal tampil
     * sebagai kategori biasa tanpa manajemen.
     */
    public function destroy(string $id)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $category = Category::where('user_id', Auth::id())->findOrFail($id);

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori "'.$category->name.'" dihapus. Riwayat transaksi lama tetap utuh.');
    }
}
