<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil pemeriksaan rekonsiliasi keuangan.
 *
 * Tabel ini menyimpan HASIL PEMERIKSAAN saja — bukan ledger keuangan.
 * Satu baris = satu kali pemeriksaan. Rekonsiliasi bersifat read-only:
 * tidak dapat dipakai untuk memutuskan perubahan transaksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rekonsiliasi_hasil')) {
            Schema::create('rekonsiliasi_hasil', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('periode', 20)->nullable(); // mis. 2028-03, atau kosong = semua periode
                $table->unsignedBigInteger('dijalankan_oleh')->nullable();
                $table->timestamp('dijalankan_pada');
                $table->unsignedInteger('jumlah_pemeriksaan')->default(0);
                $table->unsignedInteger('jumlah_temuan')->default(0);
                $table->unsignedInteger('jumlah_kritis')->default(0);
                $table->json('ringkasan')->nullable();
                $table->timestamps();

                $table->index('dijalankan_pada');
                $table->foreign('dijalankan_oleh')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_hasil');
    }
};
