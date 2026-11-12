<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temuan (anomali/ketidakcocokan) hasil rekonsiliasi.
 *
 * Bukan ledger keuangan — berisi referensi record dan selisih nominal
 * untuk ditinjau oleh admin/superadmin. Tidak ada aksi otomatis
 * (koreksi hanya dilakukan manual dengan proses resmi di luar sprint).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rekonsiliasi_temuan')) {
            Schema::create('rekonsiliasi_temuan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('rekonsiliasi_hasil_id');
                $table->string('kategori', 60); // mis. ATTEMPT_SUKSES_TANPA_LEDGER
                $table->string('referensi_tipe', 30)->nullable(); // payment_attempt|pembayaran|kuitansi|tagihan|siswa
                $table->unsignedBigInteger('referensi_id')->nullable();
                $table->unsignedInteger('siswa_idsiswa')->nullable();
                $table->unsignedBigInteger('tagihan_idtagihan')->nullable();
                $table->bigInteger('nominal_selisih')->nullable();
                $table->text('keterangan');
                $table->enum('status', ['perlu_ditinjau', 'kritis', 'info'])->default('perlu_ditinjau');
                $table->timestamps();

                $table->index(['rekonsiliasi_hasil_id', 'kategori'], 'temuan_hasil_kategori_idx');
                $table->index(['kategori'], 'temuan_kategori_idx');
                $table->index(['siswa_idsiswa'], 'temuan_siswa_idx');
                $table->foreign('rekonsiliasi_hasil_id')->references('id')->on('rekonsiliasi_hasil')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_temuan');
    }
};
