<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger alokasi pembayaran ke tagihan (dan ke saldo siswa bila ada kelebihan).
 *
 * - `jenis = 'tagihan'` : mengalokasikan uang ke satu tagihan (mengurangi sisa tagihan).
 * - `jenis = 'saldo'`   : sisa/kelebihan pembayaran yang belum dialokasikan ke tagihan;
 *                         bukan uang hilang — dapat dipakai untuk tagihan berikutnya.
 * - Satu baris uang masuk (pembayaran) dapat menghasilkan > 1 baris alokasi.
 * - Tidak ada penghapusan; koreksi melalui alokasi lanjutan (bukan edit/hapus historis).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembayaran_alokasi')) {
            Schema::create('pembayaran_alokasi', function (Blueprint $table) {
                $table->bigIncrements('idpembayaran_alokasi');
                $table->unsignedBigInteger('pembayaran_idpembayaran');
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedBigInteger('tagihan_idtagihan')->nullable(); // NULL saat jenis = 'saldo'
                $table->bigInteger('nominal')->unsigned();
                $table->enum('jenis', ['tagihan', 'saldo']);
                $table->timestamps();

                $table->index(['tagihan_idtagihan', 'jenis'], 'alokasi_tagihan_jenis_idx');
                $table->index(['siswa_idsiswa', 'jenis'], 'alokasi_siswa_jenis_idx');

                $table->foreign('pembayaran_idpembayaran')->references('idpembayaran')->on('pembayaran')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_alokasi');
    }
};
