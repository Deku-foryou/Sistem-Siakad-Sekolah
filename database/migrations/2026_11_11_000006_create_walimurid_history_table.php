<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FONDASI PORTAL WALI MURID — Tahap 2B.7.3B
 *
 * Desain final §5.5: tabel `walimurid_history` (append-only).
 * - `aktor_peran` mencakup superadmin/admin/wali/sistem.
 * - Event `periode_diubah` + empat kolom tanggal (revisi 3/4).
 * - Seluruh FK ON DELETE RESTRICT ON UPDATE RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('walimurid_history')) {
            return;
        }

        Schema::create('walimurid_history', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->bigIncrements('id');
            $table->unsignedBigInteger('walimurid_id');
            $table->unsignedBigInteger('user_iduser');     // snapshot pemilik relasi
            $table->unsignedInteger('siswa_idsiswa');      // snapshot siswa
            $table->unsignedBigInteger('aktor_id');
            $table->enum('aktor_peran', ['superadmin', 'admin', 'wali', 'sistem']);
            $table->enum('event', ['pengajuan', 'verifikasi', 'penolakan', 'pemutusan', 'pengajuan_ulang', 'permintaan_putus', 'permintaan_putus_ditolak', 'permintaan_putus_dibatalkan', 'periode_diubah']);
            $table->enum('status_sebelum', ['pending', 'verified', 'rejected', 'terminated'])->nullable();
            $table->enum('status_sesudah', ['pending', 'verified', 'rejected', 'terminated']);
            $table->date('tanggal_mulai_sebelum')->nullable()->default(null);
            $table->date('tanggal_mulai_sesudah')->nullable()->default(null);
            $table->date('tanggal_berhenti_sebelum')->nullable()->default(null);
            $table->date('tanggal_berhenti_sesudah')->nullable()->default(null);
            $table->string('alasan', 500)->nullable();
            $table->timestamp('created_at')->nullable()->default(null);

            $table->index(['walimurid_id', 'created_at'], 'idx_walimurid_history_relasi');
            $table->index(['siswa_idsiswa', 'created_at'], 'idx_walimurid_history_siswa');

            $table->foreign('walimurid_id', 'walimurid_history_relasi_fk')
                ->references('idwalimurid')->on('walimurid')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('user_iduser', 'walimurid_history_user_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('siswa_idsiswa', 'walimurid_history_siswa_fk')
                ->references('idsiswa')->on('siswa')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('aktor_id', 'walimurid_history_aktor_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walimurid_history');
    }
};
