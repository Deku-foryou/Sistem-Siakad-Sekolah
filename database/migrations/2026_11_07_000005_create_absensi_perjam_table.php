<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Satu baris = catatan kehadiran satu siswa pada satu pertemuan.
     * Unik per (pertemuan, siswa). Pembatalan/perubahan status tidak
     * menghapus baris; audit perubahan direkam di diinput_oleh/diubah_oleh.
     */
    public function up(): void
    {
        Schema::create('absensi_perjam', function (Blueprint $table) {
            $table->bigIncrements('idabsensi_perjam');

            $table->unsignedBigInteger('jadwal_pertemuan_idjadwal_pertemuan');
            $table->unsignedInteger('siswa_idsiswa');

            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpha'])->default('hadir');
            $table->text('keterangan')->nullable();

            // Audit pencatatan (pola absensi_harian): pencatat wajib, pengubah nullable
            $table->unsignedBigInteger('diinput_oleh');
            $table->unsignedBigInteger('diubah_oleh')->nullable();

            $table->timestamps();

            $table->foreign('jadwal_pertemuan_idjadwal_pertemuan')
                ->references('idjadwal_pertemuan')
                ->on('jadwal_pertemuan')
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

            $table->unique(
                ['jadwal_pertemuan_idjadwal_pertemuan', 'siswa_idsiswa'],
                'absensi_perjam_pertemuan_siswa_unique'
            );

            $table->engine('InnoDB');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_perjam');
    }
};
