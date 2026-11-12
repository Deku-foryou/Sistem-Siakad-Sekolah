<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan refund pembayaran (reversal entry, bukan edit histori).
 *
 * Status:
 * - pending    : menunggu keputusan superadmin
 * - disetujui  : superadmin menyetujui; menunggu proses eksekusi (manual oleh sekolah / API)
 * - ditolak    : superadmin menolak
 * - terproses  : refund berhasil (reversal alokasi dibuat; histori asli tetap utuh)
 * - gagal      : pemrosesan gagal; dapat dicoba ulang; alokasi/saldo tidak berubah
 *
 * Semua nominal integer rupiah. Tidak ada penghapusan/pembatalan langsung terhadap
 * transaksi yang sudah diposting; efek finansial hanya melalui alokasi reversal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refund_pengajuan')) {
            Schema::create('refund_pengajuan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pembayaran_idpembayaran');
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedBigInteger('tagihan_idtagihan')->nullable();
                $table->bigInteger('nominal_refund')->unsigned();
                $table->text('alasan');
                $table->enum('metode', ['provider_api', 'transfer_manual', 'tunai', 'lainnya'])->default('transfer_manual');
                $table->string('referensi', 150)->nullable();
                $table->enum('status', ['pending', 'disetujui', 'ditolak', 'terproses', 'gagal'])->default('pending');
                $table->string('nomor_dokumen', 50)->unique()->nullable(); // nomor dokumen refund
                $table->string('ref_provider', 150)->nullable();             // referensi balik dari provider/sekolah
                $table->unsignedBigInteger('diajukan_oleh')->nullable();
                $table->unsignedBigInteger('disetujui_oleh')->nullable();
                $table->timestamp('disetujui_pada')->nullable();
                $table->unsignedBigInteger('diproses_oleh')->nullable();
                $table->timestamp('diproses_pada')->nullable();
                $table->text('alasan_ditolak')->nullable();
                $table->text('catatan_proses')->nullable();
                $table->timestamps();

                $table->index(['status'], 'refund_status_idx');
                $table->index(['pembayaran_idpembayaran', 'status'], 'refund_pembayaran_status_idx');
                $table->index(['siswa_idsiswa', 'status'], 'refund_siswa_status_idx');

                $table->foreign('pembayaran_idpembayaran')->references('idpembayaran')->on('pembayaran')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('diajukan_oleh')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('disetujui_oleh')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('diproses_oleh')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_pengajuan');
    }
};
