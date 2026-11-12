<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pengiriman pengingat tagihan (idempotensi + audit).
 *
 * Tahap dihitung dari selisih hari(tanggal_kirim - tanggal_jatuh_tempo):
 *   -7 → 'H-7', -3 → 'H-3', 0 → 'H', 1 → 'H+1', 8/15/22... → 'H+{n}' (setiap 7 hari).
 * Unik per (tagihan, tahap, penerima) — pengingat tidak pernah dikirim ganda
 * untuk kombinasi yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihan_pengingat')) {
            Schema::create('tagihan_pengingat', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tagihan_idtagihan');
                $table->string('tahap', 30);
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedBigInteger('penerima_user_iduser');
                $table->unsignedBigInteger('notifikasi_idnotifikasi')->nullable();
                $table->timestamp('dikirim_pada')->useCurrent();
                $table->timestamps();

                $table->unique(['tagihan_idtagihan', 'tahap', 'penerima_user_iduser'], 'pengingat_tagihan_penerima_tahap_unique');
                $table->index(['tagihan_idtagihan', 'dikirim_pada'], 'pengingat_tagihan_tagihan_idx');
                $table->index(['siswa_idsiswa'], 'pengingat_tagihan_siswa_idx');

                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('penerima_user_iduser')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('notifikasi_idnotifikasi')->references('idnotifikasi')->on('notifikasi')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_pengingat');
    }
};
