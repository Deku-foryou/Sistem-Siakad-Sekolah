<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan koreksi keuangan — tidak mengubah transaksi asli.
 *
 * Koreksi diterapkan melalui reversal alokasi + pengganti alokasi baru;
 * histori pembayaran asli dan alokasi lamanya tetap utuh.
 *
 * Jenis:
 * - nominal            : nominal pembayaran salah dicatat
 * - alokasi            : alokasi salah (jumlah antar tagihan/saldo salah)
 * - pencatatan_manual  : pencatatan manual salah (metode/tanggal/pembayar)
 * - hubungan_tagihan   : pembayaran tertaut ke tagihan yang salah
 *
 * Status: pending -> disetujui|ditolak -> terproses|gagal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('koreksi_pengajuan')) {
            Schema::create('koreksi_pengajuan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pembayaran_idpembayaran');
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedBigInteger('tagihan_idtagihan')->nullable();
                $table->unsignedBigInteger('tagihan_pengganti_idtagihan')->nullable(); // untuk jenis hubungan_tagihan
                $table->bigInteger('nominal_usulan')->unsigned()->nullable();
                $table->string('metode_usulan', 30)->nullable();
                $table->string('nama_pembayar_usulan', 150)->nullable();
                $table->date('tanggal_pembayaran_usulan')->nullable();
                $table->enum('jenis', ['nominal', 'alokasi', 'pencatatan_manual', 'hubungan_tagihan']);
                $table->text('alasan');
                $table->json('snapshot_sebelum')->nullable(); // snapshot pembayaran + alokasi sebelum koreksi
                $table->enum('status', ['pending', 'disetujui', 'ditolak', 'terproses', 'gagal'])->default('pending');
                $table->unsignedBigInteger('diajukan_oleh')->nullable();
                $table->unsignedBigInteger('disetujui_oleh')->nullable();
                $table->timestamp('disetujui_pada')->nullable();
                $table->unsignedBigInteger('diproses_oleh')->nullable();
                $table->timestamp('diproses_pada')->nullable();
                $table->text('alasan_ditolak')->nullable();
                $table->text('catatan_proses')->nullable();
                $table->timestamps();

                $table->index(['status'], 'koreksi_status_idx');
                $table->index(['pembayaran_idpembayaran', 'status'], 'koreksi_pembayaran_status_idx');
                $table->index(['siswa_idsiswa', 'status'], 'koreksi_siswa_status_idx');

                $table->foreign('pembayaran_idpembayaran')->references('idpembayaran')->on('pembayaran')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('tagihan_pengganti_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('diajukan_oleh')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('disetujui_oleh')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('diproses_oleh')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_pengajuan');
    }
};
