<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Komponen penilaian per semester dan mata pelajaran.
     *
     * Total bobot per (semester, mapel) harus 100; aturan tersebut divalidasi
     * pada service, bukan pada migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('komponen_nilai')) {
            Schema::create('komponen_nilai', function (Blueprint $table) {
                $table->increments('idkomponen_nilai');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('mapel_idmapel');
                $table->string('nama', 100);
                $table->decimal('bobot', 5, 2);
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->boolean('aktif')->default(true);
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'mapel_idmapel', 'nama'],
                    'komponen_nilai_semester_mapel_nama_unique'
                );
                $table->index(
                    ['semester_idsemester', 'mapel_idmapel', 'urutan'],
                    'komponen_nilai_semester_mapel_urutan_index'
                );

                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')
                    ->onDelete('restrict');
                $table->foreign('mapel_idmapel')
                    ->references('idmapel')->on('mapel')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('komponen_nilai');
    }
};
