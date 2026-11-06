<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot nilai akhir per siswa, kelas, dan mata pelajaran.
     *
     * Baris diisi saat finalisasi (bersama perubahan status finalisasi_rapor
     * dalam satu transaksi). KKTP disimpan sebagai snapshot agar cetak ulang
     * rapor tidak berubah ketika KKTP dikonfigurasi ulang di kemudian hari.
     */
    public function up(): void
    {
        if (! Schema::hasTable('nilai_akhir')) {
            Schema::create('nilai_akhir', function (Blueprint $table) {
                $table->bigIncrements('idnilai_akhir');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('kelas_idkelas');
                $table->unsignedInteger('mapel_idmapel');
                $table->unsignedInteger('siswa_idsiswa');
                $table->decimal('nilai_akhir', 5, 2);
                $table->decimal('nilai_kktp', 5, 2)->nullable();
                $table->timestamp('dihitung_pada')->nullable();
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'kelas_idkelas', 'mapel_idmapel', 'siswa_idsiswa'],
                    'nilai_akhir_semester_kelas_mapel_siswa_unique'
                );
                $table->index(['kelas_idkelas', 'semester_idsemester'], 'nilai_akhir_kelas_semester_index');
                $table->index(['siswa_idsiswa', 'semester_idsemester'], 'nilai_akhir_siswa_semester_index');

                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')
                    ->onDelete('restrict');
                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')
                    ->onDelete('restrict');
                $table->foreign('mapel_idmapel')
                    ->references('idmapel')->on('mapel')
                    ->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_akhir');
    }
};
