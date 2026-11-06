<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan wali kelas per siswa dan semester.
     */
    public function up(): void
    {
        if (! Schema::hasTable('catatan_rapor')) {
            Schema::create('catatan_rapor', function (Blueprint $table) {
                $table->increments('idcatatan_rapor');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('siswa_idsiswa');
                $table->text('catatan');
                $table->unsignedInteger('guru_idguru');
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'siswa_idsiswa'],
                    'catatan_rapor_semester_siswa_unique'
                );

                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')
                    ->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')
                    ->onDelete('restrict');
                $table->foreign('guru_idguru')
                    ->references('idguru')->on('guru')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_rapor');
    }
};
