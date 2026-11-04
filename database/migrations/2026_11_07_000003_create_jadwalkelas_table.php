<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jadwalkelas')) {
            Schema::create('jadwalkelas', function (Blueprint $table) {
                $table->bigIncrements('idjadwalkelas');
                $table->integer('semester_idsemester')->unsigned();
                $table->integer('jamhari_idjamhari')->unsigned();
                $table->integer('mapel_idmapel')->unsigned();
                $table->integer('guru_idguru')->unsigned();
                $table->timestamps();

                $table->foreign('semester_idsemester')->references('idsemester')->on('semester')->onDelete('restrict');
                $table->foreign('jamhari_idjamhari')->references('idjamhari')->on('jamhari')->onDelete('restrict');
                $table->foreign('mapel_idmapel')->references('idmapel')->on('mapel')->onDelete('restrict');
                $table->foreign('guru_idguru')->references('idguru')->on('guru')->onDelete('restrict');

                $table->unique(['semester_idsemester', 'jamhari_idjamhari'], 'jadwalkelas_semester_jamhari_unique');
                $table->index(['guru_idguru', 'semester_idsemester'], 'jadwalkelas_guru_semester_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwalkelas');
    }
};
