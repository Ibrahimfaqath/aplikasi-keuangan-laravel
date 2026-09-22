<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('user_id', Auth::id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', [
            'categories' => $categories,
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
