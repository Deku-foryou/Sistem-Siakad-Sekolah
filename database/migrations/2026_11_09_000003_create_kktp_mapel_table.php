<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KKTP per semester dan mata pelajaran.
     */
    public function up(): void
    {
        if (! Schema::hasTable('kktp_mapel')) {
            Schema::create('kktp_mapel', function (Blueprint $table) {
                $table->increments('idkktp_mapel');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('mapel_idmapel');
                $table->decimal('nilai_minimal', 5, 2);
                $table->text('deskripsi')->nullable();
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'mapel_idmapel'],
                    'kktp_mapel_semester_mapel_unique'
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
        Schema::dropIfExists('kktp_mapel');
    }
};
