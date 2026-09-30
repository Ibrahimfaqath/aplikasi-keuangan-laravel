<?php

use App\Support\CategoryStyle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan tampilan kategori (warna + ikon) sebagai data, bukan sebagai
     * peta hardcode di dalam Blade.
     *
     * Kolomnya nullable & diisi key ringkas ("green", "cart"), dipetakan
     * ke kelas Tailwind / path SVG di App\Support\CategoryStyle. Baris
     * yang belum punya nilai (baris lama, atau database tanpa seeder)
     * tetap aman: Category_style::colorClasses() / iconPath() jatuh ke
     * netral.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('color', 20)->nullable()->after('type');
            $table->string('icon', 20)->nullable()->after('color');
        });

        // Tambal kategori bawaan yang sudah ada supaya tampilan default
        // langsung sama seperti sebelumnya (hijau/amber/... per kategori).
        // Idempoten: overwrite dengan nilai baku, aman dijalankan ulang.
        foreach (CategoryStyle::DEFAULTS as $name => $style) {
            DB::table('categories')
                ->where('name', $name)
                ->whereNull('user_id')
                ->update([
                    'color' => $style['color'],
                    'icon' => $style['icon'],
                ]);
        }

        $this->dedupeGlobals();
    }

    /**
     * Bersihkan kategori bawaan yang tergandakan.
     *
     * CategorySeeder lama memakai `insertOrIgnore` untuk kategori global
     * (user_id NULL). Index unik (user_id, name, type) tidak berlaku untuk
     * NULL — SQL menganggap NULL tidak sama dengan NULL — sehingga setiap
     * kali seeder dijalankan (cPanel Git Deploy memanggilnya tiap deploy)
     * 15 baris global terduplikasi. UI tidak pernah menunjukkan gejalanya
     * karena Category::availableFor() menimpa berdasarkan nama, jadi
     * tabelnya diam-diam membengkak.
     *
     * Baris yang dihapus benar-benar salinan: user_id NULL, name & type
     * sama, dan tidak ada tabel lain yang menyimpan id kategori (transaksi
     * & anggaran menyimpan NAMA sebagai string). Yang dipertahankan adalah
     * id terkecil.
     */
    protected function dedupeGlobals(): void
    {
        $seen = [];

        // Key dibangun di PHP, bukan dengan CONCAT() di SQL, supaya
        // migration ini jalan sama baiknya di MySQL (produksi) dan
        // SQLite (test suite).
        $rows = DB::table('categories')
            ->whereNull('user_id')
            ->orderBy('id')
            ->get(['id', 'name', 'type']);

        $remove = [];

        foreach ($rows as $row) {
            $key = $row->type.'|'.$row->name;

            if (isset($seen[$key])) {
                $remove[] = $row->id;
            } else {
                $seen[$key] = true;
            }
        }

        if ($remove !== []) {
            DB::table('categories')->whereIn('id', $remove)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon']);
        });
    }
};
