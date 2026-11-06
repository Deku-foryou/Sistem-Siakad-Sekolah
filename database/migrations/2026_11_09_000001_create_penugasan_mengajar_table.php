<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penugasan mengajar: sumber otorisasi guru untuk penilaian.
     *
     * Terpisah dari jadwal (jadwalkelas) dan dari data legacy (pengampu).
     * Satu kombinasi semester + kelas + mapel hanya boleh memiliki satu guru
     * penanggung jawab pada versi awal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('penugasan_mengajar')) {
            Schema::create('penugasan_mengajar', function (Blueprint $table) {
                $table->increments('idpenugasan_mengajar');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('kelas_idkelas');
                $table->unsignedInteger('mapel_idmapel');
                $table->unsignedInteger('guru_idguru');
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'kelas_idkelas', 'mapel_idmapel'],
                    'penugasan_mengajar_semester_kelas_mapel_unique'
                );
                $table->index(['guru_idguru', 'semester_idsemester'], 'penugasan_mengajar_guru_semester_index');
                $table->index('kelas_idkelas', 'penugasan_mengajar_kelas_index');
                $table->index('mapel_idmapel', 'penugasan_mengajar_mapel_index');

                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')
                    ->onDelete('restrict');
                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')
                    ->onDelete('restrict');
                $table->foreign('mapel_idmapel')
                    ->references('idmapel')->on('mapel')
                    ->onDelete('restrict');
                $table->foreign('guru_idguru')
                    ->references('idguru')->on('guru')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_mengajar');
    }
};
