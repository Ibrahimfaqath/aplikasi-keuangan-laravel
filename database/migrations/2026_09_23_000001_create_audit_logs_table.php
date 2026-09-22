<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak aktivitas pengguna (audit log) — catat tiap perubahan data penting:
     * transaksi, anggaran, kategori, akun, dan peristiwa autentikasi.
     *
     * - old_values/new_values menyimpan kolom yang berubah (JSON), tabel imutabel
     *   (hanya created_at) sehingga riwayat tidak pernah bisa diubah oleh aksi
     *   pengguna.
     * - user_id null ketika user sudah dihapus permanen; baris log tetap ada.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->string('model_type', 120)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('label', 255)->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('source', 12)->default('web'); // web | ai | demo | system
            $table->timestamp('created_at')->nullable();

            $table->index(['action']);
            $table->index(['model_type', 'model_id']);
            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
