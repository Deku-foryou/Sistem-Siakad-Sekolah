<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tarif keuangan sekolah (global/kelas/individu).
     *
     * - Jenis 'global'   : tarif umum untuk seluruh siswa pada periode berlaku.
     * - Jenis 'kelas'    : tarif khusus untuk satu kelas (kelas_idkelas).
     * - Jenis 'individu' : tarif khusus untuk satu siswa (siswa_idsiswa).
     *
     * Prioritas resolusi: individu > kelas > global.
     * Nominal dalam rupiah (integer).
     */
    public function up(): void
    {
        if (! Schema::hasTable('tarif')) {
            Schema::create('tarif', function (Blueprint $table) {
                $table->increments('idtarif');
                $table->enum('jenis', ['global', 'kelas', 'individu']);
                $table->string('nama', 150);
                $table->bigInteger('nominal')->unsigned();
                $table->date('berlaku_dari');
                $table->date('berlaku_sampai')->nullable();
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
                $table->unsignedInteger('kelas_idkelas')->nullable();
                $table->unsignedInteger('siswa_idsiswa')->nullable();
                $table->text('alasan')->nullable();
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->timestamps();

                $table->index(['jenis', 'status', 'berlaku_dari'], 'tarif_jenis_status_dari_idx');
                $table->index('kelas_idkelas');
                $table->index('siswa_idsiswa');
                $table->index(['berlaku_dari', 'berlaku_sampai']);

                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('dibuat_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif');
    }
};
