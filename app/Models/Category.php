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
     * Nama-nama kategori yang tersedia untuk seorang user pada sebuah jenis
     * transaksi: kategori bawaan global + custom milik user. Urutkan stabil
     * (id) agar tampilan chip/dropdown tidak melompat-lompat.
     *
     * Bila belum ada data tersimpan (misal database test tanpa seeder),
     * jatuh ke daftar bawaan aplikasi agar perilaku lama tetap sama.
     *
     * @return list<string>
     */
    public static function namesFor(?int $userId, string $type): array
    {
        $rows = static::query()
            ->where('type', $type)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userId))
            ->orderBy('id')
            ->pluck('name');

        if ($rows->isEmpty()) {
            return Transaction::categoriesFor($type);
        }

        return array_values(array_unique($rows->all()));
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
