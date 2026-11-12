<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penerima beasiswa untuk siswa dengan periode berlaku dan alasan.
     *
     * Unique (beasiswa, siswa) mencegah pemberian beasiswa yang sama ganda.
     * Status history tetap dipertahankan (tidak dihapus).
     */
    public function up(): void
    {
        if (! Schema::hasTable('beasiswa_siswa')) {
            Schema::create('beasiswa_siswa', function (Blueprint $table) {
                $table->bigIncrements('idbeasiswa_siswa');
                $table->unsignedInteger('beasiswa_idbeasiswa');
                $table->unsignedInteger('siswa_idsiswa');
                $table->date('berlaku_dari');
                $table->date('berlaku_sampai')->nullable();
                $table->text('alasan')->nullable();
                $table->enum('status', ['aktif', 'berhenti'])->default('aktif');
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->timestamps();

                $table->unique(['beasiswa_idbeasiswa', 'siswa_idsiswa'], 'beasiswa_siswa_unique');
                $table->index(['siswa_idsiswa', 'status'], 'beasiswa_siswa_siswa_status_idx');
                $table->index('berlaku_dari');

                $table->foreign('beasiswa_idbeasiswa')
                    ->references('idbeasiswa')->on('beasiswa')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('dibuat_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa_siswa');
    }
};
