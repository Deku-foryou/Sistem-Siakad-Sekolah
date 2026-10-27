<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengampu', function (Blueprint $table) {
            $table->increments('idpengampu');
            $table->unsignedInteger('semester_idsemester');
            $table->unsignedInteger('kelas_idkelas');
            $table->unsignedInteger('mapel_idmapel');
            $table->unsignedInteger('guru_idguru');
            $table->timestamps();

            $table->unique(
                ['semester_idsemester', 'kelas_idkelas', 'mapel_idmapel'],
                'pengampu_semester_kelas_mapel_unique'
            );
            $table->index('kelas_idkelas');
            $table->index('mapel_idmapel');
            $table->index('guru_idguru');
            $table->foreign('semester_idsemester')
                ->references('idsemester')->on('semester')
                ->restrictOnDelete();
            $table->foreign('kelas_idkelas')
                ->references('idkelas')->on('kelas')
                ->restrictOnDelete();
            $table->foreign('mapel_idmapel')
                ->references('idmapel')->on('mapel')
                ->restrictOnDelete();
            $table->foreign('guru_idguru')
                ->references('idguru')->on('guru')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengampu');
    }
};
