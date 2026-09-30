<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Support\CategoryStyle;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Benih kategori bawaan (global, milik semua user) yang diambil dari
     * konstanta Transaction, lengkap dengan warna & ikonnya.
     *
     * Idempotent dan aman dijalankan ulang tiap deploy. Kalau sindir:
     * `insertOrIgnore` TIDAK bisa dipakai di sini — index unik
     * (user_id, name, type) tidak berlaku untuk baris dengan user_id NULL,
     * karena SQL menganggap NULL tidak sama dengan NULL. Akibatnya setiap
     * deploy menambah 15 baris duplikat. Jadi ketersediaan dicek eksplisit.
     */
    public function run(): void
    {
        $now = now();

        foreach (['income' => Transaction::INCOME_CATEGORIES, 'expense' => Transaction::EXPENSE_CATEGORIES] as $type => $names) {
            $existing = Category::whereNull('user_id')
                ->where('type', $type)
                ->pluck('name')
                ->all();

            $missing = array_values(array_diff($names, $existing));

            if ($missing === []) {
                continue;
            }

            // Nama yang punya warna/ikon baku (mis. "Gaji") memakai nilai itu.
            // Sisanya (mis. "Lainnya") dapat warna tebakan dari namanya —
            // BUKAN DEFAULT_COLOR, karena netral dipakai untuk "belum punya
            // identitas" dan kategori bawaan tidak boleh terlihat seperti itu.
            $rows = array_map(function (string $name) use ($type, $now) {
                $appearance = CategoryStyle::defaultsFor($name) ?? CategoryStyle::fallbackFor($name);

                return [
                    'user_id' => null,
                    'name' => $name,
                    'type' => $type,
                    'color' => $appearance['color'],
                    'icon' => $appearance['icon'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $missing);

            Category::query()->insert($rows);
        }
    }
}
