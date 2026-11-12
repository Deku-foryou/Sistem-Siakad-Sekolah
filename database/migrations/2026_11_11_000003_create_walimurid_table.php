<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FONDASI PORTAL WALI MURID — Tahap 2B.7.3B
 *
 * Desain final §5.2: tabel `walimurid` (relasi wali–siswa).
 * - `siswa_idsiswa` INT UNSIGNED (PK `siswa.idsiswa`), seluruh FK user BIGINT UNSIGNED.
 * - Unique generated column (VIRTUAL) → satu relasi aktif (`pending|verified`) per pasangan.
 * - Seluruh FK ON DELETE RESTRICT ON UPDATE RESTRICT. Tidak ada CASCADE/SET NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('walimurid')) {
            return;
        }

        Schema::create('walimurid', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->bigIncrements('idwalimurid');
            $table->unsignedBigInteger('user_iduser');              // PEMILIK relasi (akun wali)
            $table->unsignedInteger('siswa_idsiswa');                // HARUS INT UNSIGNED (PK siswa.idsiswa)
            $table->enum('hubungan', ['ayah', 'ibu', 'wali', 'lainnya']);
            $table->enum('status_hubungan', ['pending', 'verified', 'rejected', 'terminated'])->default('pending');
            $table->tinyInteger('status_aktif_flag')
                ->virtualAs("CASE WHEN `status_hubungan` IN ('pending','verified') THEN 1 ELSE NULL END")
                ->nullable();
            $table->date('tanggal_mulai');                          // BATAS AWAL AKSES AKADEMIK
            $table->date('tanggal_berhenti')->nullable()->default(null); // BATAS AKHIR AKSES AKADEMIK
            $table->timestamp('tanggal_verifikasi')->nullable()->default(null); // WAKTU PENGESAHAN (berbeda dari tanggal_mulai)
            $table->unsignedBigInteger('pengaju_id');                // AKTOR yang mengajukan
            $table->unsignedBigInteger('verifikator_id')->nullable()->default(null); // AKTOR yang memutuskan
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->nullable()->default(null);
            $table->timestamp('updated_at')->nullable()->default(null);

            $table->unique(['user_iduser', 'siswa_idsiswa', 'status_aktif_flag'], 'uk_walimurid_user_siswa_aktif');
            $table->index(['siswa_idsiswa', 'status_hubungan'], 'idx_walimurid_siswa_status');
            $table->index(['user_iduser', 'status_hubungan'], 'idx_walimurid_user_status');
            $table->index(['status_hubungan', 'created_at'], 'idx_walimurid_antrian');

            $table->foreign('user_iduser', 'walimurid_user_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('siswa_idsiswa', 'walimurid_siswa_fk')
                ->references('idsiswa')->on('siswa')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('pengaju_id', 'walimurid_pengaju_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('verifikator_id', 'walimurid_verifikator_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walimurid');
    }
};
