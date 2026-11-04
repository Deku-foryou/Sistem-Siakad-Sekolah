<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('absensi_harian', function (Blueprint $table) {
            $table->bigIncrements('idabsensi_harian');

            // Context: Tahun ajaran & semester
            $table->unsignedInteger('tahunajaran_idtahunajaran');
            $table->unsignedInteger('semester_idsemester');

            // Context: Kelas & tanggal
            $table->unsignedInteger('kelas_idkelas');
            $table->date('tanggal');

            // Siswa yang di-absensi
            $table->unsignedInteger('siswa_idsiswa');

            // Status kehadiran
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpha'])->default('hadir');

            // Catatan/alasan
            $table->text('catatan')->nullable();

            // Audit: siapa yang input & ubah
            $table->unsignedBigInteger('diinput_oleh');
            $table->unsignedBigInteger('diubah_oleh')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign keys
            $table->foreign('tahunajaran_idtahunajaran')
                ->references('idtahunajaran')
                ->on('tahunajaran')
                ->onDelete('restrict');

            $table->foreign('semester_idsemester')
                ->references('idsemester')
                ->on('semester')
                ->onDelete('restrict');

            $table->foreign('kelas_idkelas')
                ->references('idkelas')
                ->on('kelas')
                ->onDelete('restrict');

            $table->foreign('siswa_idsiswa')
                ->references('idsiswa')
                ->on('siswa')
                ->onDelete('restrict');

            $table->foreign('diinput_oleh')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('diubah_oleh')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Unique constraint: prevent duplicate absensi for siswa per hari per kelas
            $table->unique(
                ['tahunajaran_idtahunajaran', 'semester_idsemester', 'kelas_idkelas', 'tanggal', 'siswa_idsiswa'],
                'absensi_harian_unique'
            );

            // Indexes for common queries
            $table->index(['kelas_idkelas', 'tanggal'], 'absensi_harian_kelas_tanggal_index');
            $table->index(['siswa_idsiswa', 'tanggal'], 'absensi_harian_siswa_tanggal_index');
            $table->index(['tahunajaran_idtahunajaran', 'semester_idsemester'], 'absensi_harian_tahun_semester_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_harian');
    }
};
