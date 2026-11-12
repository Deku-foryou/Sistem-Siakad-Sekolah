<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen resmi (PDF) untuk refund yang BERHASIL diproses.
 *
 * - Baris dibuat hanya setelah refund terproses dan reversal ledger tersimpan.
 * - Nomor dokumen mengikuti refund_pengajuan.nomor_dokumen (RF/YYYY/MM/NNNNNN, unik).
 * - File PDF disimpan di private storage (tidak dapat diakses publik tanpa otorisasi).
 * - Checksum SHA256 + ukuran file disimpan untuk verifikasi integritas.
 * - Snapshot identitas sekolah & dokumen disimpan saat terbit; dokumen lama
 *   tidak berubah meski konfigurasi sekolah/data terkait berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refund_dokumen')) {
            Schema::create('refund_dokumen', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('refund_pengajuan_id')->unique();
                $table->string('nomor_dokumen', 50)->unique();
                $table->string('file_path', 255);
                $table->string('checksum_sha256', 64);
                $table->unsignedBigInteger('ukuran_file')->nullable();
                $table->json('snapshot_sekolah')->nullable();
                $table->json('snapshot_dokumen')->nullable();
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->timestamps();

                $table->index(['dibuat_oleh'], 'refund_dokumen_dibuat_idx');

                $table->foreign('refund_pengajuan_id')->references('id')->on('refund_pengajuan')->onDelete('restrict');
                $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_dokumen');
    }
};
