<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan pembebasan denda (dua tahap): admin mengajukan → superadmin menyetujui/menolak.
 *
 * - Admin & superadmin boleh mengajukan; superadmin tidak boleh menyetujui pengajuannya sendiri.
 * - Denda tidak pernah di-waive sebelum persetujuan superadmin (histori wajib utuh).
 * - Pengajuan pending mencegah pengajuan ganda pada denda yang sama.
 * - Efek finansial hanya saat 'terproses': denda → 'dibebaskan' + tagihan.total_tagihan
 *   dikurangi nominal denda + tagihan_adjustment jenis 'denda_dibebaskan'.
 *   Denda yang dibebaskan TIDAK dicatat sebagai pembayaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('denda_pengajuan')) {
            Schema::create('denda_pengajuan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('denda_iddenda');
                $table->unsignedBigInteger('tagihan_idtagihan');
                $table->unsignedInteger('siswa_idsiswa');
                $table->bigInteger('nominal_denda')->unsigned();
                $table->text('alasan');
                $table->enum('status', ['pending', 'ditolak', 'terproses', 'gagal'])->default('pending');
                $table->unsignedBigInteger('diajukan_oleh')->nullable();
                $table->unsignedBigInteger('diproses_oleh')->nullable();
                $table->timestamp('diproses_pada')->nullable();
                $table->text('alasan_ditolak')->nullable();
                $table->text('catatan_proses')->nullable();
                $table->timestamps();

                $table->index(['status'], 'denda_pengajuan_status_idx');
                $table->index(['tagihan_idtagihan', 'status'], 'denda_pengajuan_tagihan_status_idx');
                $table->index(['siswa_idsiswa', 'status'], 'denda_pengajuan_siswa_status_idx');

                $table->foreign('denda_iddenda')->references('iddenda')->on('denda')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('diajukan_oleh')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('diproses_oleh')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('denda_pengajuan');
    }
};
