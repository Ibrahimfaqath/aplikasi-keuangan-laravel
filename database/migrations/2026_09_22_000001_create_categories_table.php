<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori transaksi terkelola per user.
     *
     * - user_id NULL  = kategori bawaan (global), dibagikan ke semua user.
     * - user_id terisi = kategori custom pribadi user tersebut.
     * - Transaksi tetap menyimpan kategori sebagai string di transactions.category
     *   (tanpa FK) sehingga riwayat tidak pernah rusak saat kategori dihapus.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->timestamps();

            // Boleh menambal kategori bawaan; user boleh punya kategori custom
            // dengan nama sama di kedua jenis (income & expense) tapi unik per user+type.
            $table->unique(['user_id', 'name', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
