<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\DemoMode;
use App\Support\CategoryStyle;
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
            'color' => ['nullable', Rule::in(CategoryStyle::colorKeys())],
            'icon' => ['nullable', Rule::in(CategoryStyle::iconKeys())],
        ]);

        $name = trim($validated['name']);

        if ($this->nameTaken($name, $validated['type'], null)) {
            return back()->withErrors([
                'name' => 'Kategori "'.$name.'" sudah tersedia untuk jenis transaksi ini.',
            ])->withInput();
        }

        $appearance = CategoryStyle::sanitize(
            $validated['color'] ?? null,
            $validated['icon'] ?? null,
            $name,
        );

        Category::create([
            'user_id' => Auth::id(),
            'name' => $name,
            'type' => $validated['type'],
            'color' => $appearance['color'],
            'icon' => $appearance['icon'],
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori "'.$name.'" berhasil ditambahkan.');
    }

    /**
     * Ubah nama, jenis, warna, dan/atau ikon sebuah kategori custom.
     *
     * Hanya kategori milik user yang boleh diubah. Kategori bawaan
     * (user_id NULL) dipakai bersama semua user, jadi mengeditnya di sini
     * akan mengubah milik orang lain — itu tugas seeding, bukan tugas user.
     */
    public function update(Request $request, string $id)
    {
        if (DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        $category = Category::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'type' => ['required', Rule::in(['income', 'expense'])],
            'color' => ['nullable', Rule::in(CategoryStyle::colorKeys())],
            'icon' => ['nullable', Rule::in(CategoryStyle::iconKeys())],
        ]);

        $name = trim($validated['name']);
        $newType = $validated['type'];
        $typeChanged = $newType !== $category->type;

        if ($this->nameTaken($name, $newType, $category->id)) {
            return back()->withErrors([
                'name' => 'Kategori "'.$name.'" sudah tersedia untuk jenis transaksi ini.',
            ])->withInput();
        }

        // Anggaran hanya berlaku untuk pengeluaran (lihat BudgetController).
        // Kalau kategori punya anggaran lalu dipindah ke pemasukan, baris
        // anggarannya akan menggantung tanpa bisa di_edit. Tolak, dan
        // suruh user membersihkannya dulu — diam-diam menghapus anggaran
        // orang jauh lebih buruk daripada asking dia lewat.
        if ($typeChanged) {
            $budgetCount = Budget::where('user_id', Auth::id())
                ->where('category', $category->name)
                ->count();

            if ($budgetCount > 0) {
                return back()->withErrors([
                    'type' => 'Kategori "'.$category->name.'" punya '.$budgetCount.' anggaran aktif, jadi jenisnya belum bisa diubah. Hapus anggarannya dulu di halaman Anggaran.',
                ])->withInput();
            }
        }

        $appearance = CategoryStyle::sanitize(
            $validated['color'] ?? null,
            $validated['icon'] ?? null,
            $name,
        );

        $affected = $category->renameAndRetype($name, $newType);

        $category->forceFill($appearance)->save();

        $message = 'Kategori "'.$category->name.'" diperbarui.';

        if ($affected > 0) {
            $message .= ' '.$affected.' transaksi ikut mengikuti nama baru.';
        }

        return redirect()->route('categories.index')->with('success', $message);
    }

    /**
     * Apakah nama ini sudah dipakai user pada jenis tertentu?
     *
     * Sumbernya sengaja Category::availableFor() — sama persis dengan yang
     * dirender di halaman /categories dan yang jadi whitelist di form
     * transaksi. Kalau cek langsung ke tabel, nama bawaan akan lolos
     * saat tabel `categories` masih kosong (mis. database tanpa seeder),
     * padahal user tetap melihat nama itu di form.
     *
     * Perbandingan case-insensitive (mb_strtolower) supaya "gaji" dan
     * "Gaji" dianggap sama — sama seperti yang dicek Alpine di form, dan
     * sama seperti collation MySQL (utf8mb4_*_ci) yang mengabaikan case.
     * Tanpa ini, "gaji" lolos validasi PHP lalu ditolak database dengan
     * pesan yang jauh lebih membingungkan.
     *
     * $ignoreId = kategori yang sedang diedit, supaya sekadar menyimpan
     * tanpa mengubah nama tidak dianggap bentrok dengan dirinya sendiri.
     */
    protected function nameTaken(string $name, string $type, ?int $ignoreId): bool
    {
        $needle = mb_strtolower(trim($name));

        foreach (Category::availableFor(Auth::id(), $type) as $item) {
            if ($ignoreId !== null && $item['id'] === $ignoreId) {
                continue;
            }

            if (mb_strtolower($item['name']) === $needle) {
                return true;
            }
        }

        return false;
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
