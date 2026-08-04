<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id(); // Membuat kolom 'id' otomatis (Primary Key)
        $table->string('title'); // Kolom untuk judul/keterangan transaksi
        $table->bigInteger('amount'); // Kolom untuk nominal angka uang (misal: 50000)
        $table->enum('type', ['income', 'expense']); // Kolom tipe: hanya boleh 'income' atau 'expense'
        $table->timestamps(); // Membuat kolom 'created_at' & 'updated_at' otomatis
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
