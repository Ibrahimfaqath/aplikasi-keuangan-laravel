<?php

namespace App\Models;

use App\Support\CategoryStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'color',
        'icon',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Warna & ikon untuk ditampilkan. Kalau barisnya belum punya nilai
     * (mis. data lama sebelum migration warna/ikon), pakai nilai baku
     * untuk kategori bawaan, atau tebakan dari nama untuk custom — supaya
     * tidak ada kartu yang kehilangan ikon.
     *
     * @return array{color: string, icon: string}
     */
    public function appearance(): array
    {
        $known = CategoryStyle::sanitize($this->color, $this->icon, (string) $this->name);

        if ($this->color || $this->icon) {
            return $known;
        }

        return CategoryStyle::defaultsFor((string) $this->name) ?? $known;
    }

    /**
     * Daftar kategori yang benar-benar tersedia untuk seorang user pada satu
     * jenis transaksi: kategori bawaan (global, user_id NULL) + custom milik
     * user, unik per nama.
     *
     * Bila tabel belum punya baris global untuk jenis itu — mis. database
     * yang belum menjalankan CategorySeeder — daftar bawaan dari konstanta
     * Transaction dipakai sebagai pengganti, supaya kategori bawaan aplikasi
     * tetap tampil (dan tetap bisa dipilih) walau tabelnya kosong.
     *
     * Urutan: bawaan dulu (urutan asli aplikasi), lalu custom (alfabetis).
     *
     * @return list<array{id: int|null, name: string, is_global: bool, color: string, icon: string}>
     */
    public static function availableFor(?int $userId, string $type): array
    {
        $rows = static::query()
            ->where('type', $type)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userId))
            ->orderBy('id')
            ->get(['id', 'name', 'user_id', 'color', 'icon']);

        $globals = $rows->whereNull('user_id');

        $items = [];

        if ($globals->isEmpty()) {
            foreach (Transaction::categoriesFor($type) as $name) {
                $items[$name] = static::shape(null, $name, true, null, null);
            }
        } else {
            foreach ($globals as $row) {
                $items[$row->name] = static::shape($row->id, $row->name, true, $row->color, $row->icon);
            }
        }

        // Custom user: nama unik, urut alfabetis. Menimpa kunci nama yang
        // sudah ada (mis. sama persis dengan bawaan) supaya tidak dobel.
        foreach ($rows->whereNotNull('user_id')->sortBy('name') as $row) {
            $items[$row->name] = static::shape($row->id, $row->name, false, $row->color, $row->icon);
        }

        return array_values($items);
    }

    /**
     * Bentuk satu baris untuk halaman /categories, dengan warna & ikon
     * yang selalu terisi.
     *
     * @return array{id: int|null, name: string, is_global: bool, color: string, icon: string}
     */
    protected static function shape(?int $id, string $name, bool $isGlobal, ?string $color, ?string $icon): array
    {
        $appearance = ($color || $icon)
            ? CategoryStyle::sanitize($color, $icon, $name)
            : (CategoryStyle::defaultsFor($name) ?? CategoryStyle::fallbackFor($name));

        return [
            'id' => $id,
            'name' => $name,
            'is_global' => $isGlobal,
            'color' => $appearance['color'],
            'icon' => $appearance['icon'],
        ];
    }


    /**
     * Ubah nama &/atau jenis sebuah kategori MILIK USER, sambil menarik
     * semua data yang mereferensikannya ikut berubah.
     *
     * Kenapa cascade itu wajib: `transactions.category` dan
     * `budgets.category` menyimpan NAMA sebagai string (bukan foreign key,
     * supaya riwayat tidak pernah rusak saat kategori dihapus). Konsekuensinya
     * kalau nama kategori diganti tanpa cascade: transaksi lama tetap
     * nempel ke nama lama yang sudah tidak ada definisinya, dan kategorinya
     * effectively jadi "yatim" — mustahil diperbaiki dari UI.
     *
     * `@return int` jumlah transaksi yang ikut di-rename.
     */
    public function renameAndRetype(string $newName, ?string $newType = null): int
    {
        $oldName = (string) $this->name;
        $oldType = (string) $this->type;
        $typeChanged = $newType !== null && $newType !== $oldType;

        return DB::transaction(function () use ($oldName, $newName, $oldType, $newType, $typeChanged) {
            $transactions = DB::table('transactions')
                ->where('user_id', $this->user_id)
                ->where('category', $oldName);

            // Kalau jenisnya berubah, transaksi ikut Dipindah ke jenis yang
            // baru — kalau tidak, akan ada transaksi bertipe "income" yang
            // kategorinya bertanda pengeluaran (atau sebaliknya) dan laporan
            // menyesatkan.
            if ($typeChanged) {
                $transactions->where('type', $oldType);
            }

            $affected = (clone $transactions)->update($typeChanged
                ? ['category' => $newName, 'type' => $newType, 'updated_at' => now()]
                : ['category' => $newName, 'updated_at' => now()]);

            // Anggaran per kategori (budgets.category default '' = anggaran
            // keseluruhan, jadi string kosong tidak ikut tersentuh).
            if ($newName !== $oldName) {
                DB::table('budgets')
                    ->where('user_id', $this->user_id)
                    ->where('category', $oldName)
                    ->update(['category' => $newName, 'updated_at' => now()]);
            }

            $changes = [];
            if ($newName !== $oldName) {
                $changes['name'] = $newName;
            }
            if ($typeChanged) {
                $changes['type'] = $newType;
            }

            $this->forceFill($changes)->save();

            return $affected;
        });
    }

    /**
     * Nama-nama kategori yang tersedia untuk seorang user pada sebuah jenis
     * transaksi. Urutan stabil (bawaan lalu custom) agar tampilan
     * chip/dropdown tidak melompat-lompat.
     *
     * Diturunkan dari availableFor() supaya whitelist validasi dan isi
     * halaman /categories tidak pernah berbeda.
     *
     * @return list<string>
     */
    public static function namesFor(?int $userId, string $type): array
    {
        return array_column(static::availableFor($userId, $type), 'name');
    }

    /**
     * Semua nama kategori yang tersedia untuk user (pemasukan + pengeluaran),
     * unik dan urut. Sumber tunggal untuk whitelist validasi.
     *
     * @return list<string>
     */
    public static function allNames(?int $userId): array
    {
        return array_values(array_unique(array_merge(
            static::namesFor($userId, 'income'),
            static::namesFor($userId, 'expense')
        )));
    }
}
