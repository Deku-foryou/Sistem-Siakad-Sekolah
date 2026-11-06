<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai siswa per komponen penilaian.
     *
     * Kelas disimpan eksplisit agar siswa dengan lebih dari satu penempatan
     * pada semester yang sama (mis. kelas induk dan kelas mapel pilihan)
     * tidak saling menimpa. Mata pelajaran diturunkan dari komponen_nilai;
     * service wajib memastikan semester nilai sama dengan semester komponen.
     */
    public function up(): void
    {
        if (! Schema::hasTable('nilai_siswa')) {
            Schema::create('nilai_siswa', function (Blueprint $table) {
                $table->bigIncrements('idnilai_siswa');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('kelas_idkelas');
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedInteger('komponen_nilai_idkomponen_nilai');
                $table->decimal('nilai', 5, 2);
                $table->unsignedInteger('guru_idguru');
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'kelas_idkelas', 'siswa_idsiswa', 'komponen_nilai_idkomponen_nilai'],
                    'nilai_siswa_semester_kelas_siswa_komponen_unique'
                );
                $table->index(['kelas_idkelas', 'semester_idsemester'], 'nilai_siswa_kelas_semester_index');
                $table->index(['siswa_idsiswa', 'semester_idsemester'], 'nilai_siswa_siswa_semester_index');

                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')
                    ->onDelete('restrict');
                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')
                    ->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')
                    ->onDelete('restrict');
                $table->foreign('komponen_nilai_idkomponen_nilai')
                    ->references('idkomponen_nilai')->on('komponen_nilai')
                    ->onDelete('restrict');
                $table->foreign('guru_idguru')
                    ->references('idguru')->on('guru')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_siswa');
    }
};
