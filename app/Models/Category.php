<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * @return list<array{id: int|null, name: string, is_global: bool}>
     */
    public static function availableFor(?int $userId, string $type): array
    {
        $rows = static::query()
            ->where('type', $type)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userId))
            ->orderBy('id')
            ->get(['id', 'name', 'user_id']);

        $globals = $rows->whereNull('user_id');

        $items = [];

        if ($globals->isEmpty()) {
            foreach (Transaction::categoriesFor($type) as $name) {
                $items[$name] = ['id' => null, 'name' => $name, 'is_global' => true];
            }
        } else {
            foreach ($globals as $row) {
                $items[$row->name] = ['id' => $row->id, 'name' => $row->name, 'is_global' => true];
            }
        }

        // Custom user: nama unik, urut alfabetis. Menimpa kunci nama yang
        // sudah ada (mis. sama persis dengan bawaan) supaya tidak dobel.
        foreach ($rows->whereNotNull('user_id')->sortBy('name') as $row) {
            $items[$row->name] = ['id' => $row->id, 'name' => $row->name, 'is_global' => false];
        }

        return array_values($items);
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
