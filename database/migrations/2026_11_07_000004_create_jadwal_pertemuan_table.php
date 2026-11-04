<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Satu baris = satu pertemuan jadwal pada tanggal tertentu.
     * Snapshot kelas/guru/mapel/jam_ke/hari/waktu disimpan sebagai kolom
     * mandiri (bukan hanya FK) agar histori tetap valid meskipun
     * jadwal sumber (jadwalkelas) atau jamhari-nya berubah di kemudian hari.
     * Pembatalan tidak menghapus baris, hanya mengubah status menjadi 'batal'.
     */
    public function up(): void
    {
        Schema::create('jadwal_pertemuan', function (Blueprint $table) {
            $table->bigIncrements('idjadwal_pertemuan');

            // FK jadwal sumber
            $table->unsignedBigInteger('jadwalkelas_idjadwalkelas');
            $table->unsignedInteger('semester_idsemester');

            // Snapshot jadwal sumber pada tanggal pertemuan
            $table->unsignedInteger('kelas_idkelas');
            $table->unsignedInteger('guru_idguru');
            $table->unsignedInteger('mapel_idmapel');
            $table->tinyInteger('hari')->unsigned();
            $table->smallInteger('jam_ke')->unsigned()->nullable();
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');

            $table->date('tanggal');
            $table->enum('status', ['terjadwal', 'batal', 'selesai'])->default('terjadwal');

            // Pembatalan (diisi hanya bila status = 'batal')
            $table->text('keterangan_pembatalan')->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->unsignedBigInteger('dibatalkan_oleh')->nullable();

            $table->timestamps();

            $table->foreign('jadwalkelas_idjadwalkelas')
                ->references('idjadwalkelas')
                ->on('jadwalkelas')
                ->onDelete('restrict');
            $table->foreign('semester_idsemester')
                ->references('idsemester')
                ->on('semester')
                ->onDelete('restrict');
            $table->foreign('kelas_idkelas')
                ->references('idkelas')
                ->on('kelas')
                ->onDelete('restrict');
            $table->foreign('guru_idguru')
                ->references('idguru')
                ->on('guru')
                ->onDelete('restrict');
            $table->foreign('mapel_idmapel')
                ->references('idmapel')
                ->on('mapel')
                ->onDelete('restrict');
            $table->foreign('dibatalkan_oleh')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->unique(['jadwalkelas_idjadwalkelas', 'tanggal'], 'jadwal_pertemuan_jadwal_tanggal_unique');
            $table->index(['tanggal', 'status'], 'jadwal_pertemuan_tanggal_status_index');
            $table->index(['semester_idsemester', 'tanggal'], 'jadwal_pertemuan_semester_tanggal_index');
            $table->index(['kelas_idkelas', 'tanggal'], 'jadwal_pertemuan_kelas_tanggal_index');
            $table->index(['guru_idguru', 'tanggal'], 'jadwal_pertemuan_guru_tanggal_index');

            $table->engine('InnoDB');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_pertemuan');
    }
};
