<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kelassiswa')) {
            return;
        }
        Schema::create('kelassiswa', function (Blueprint $table) {
            $table->increments('idkelassiswa');
            $table->unsignedInteger('kelas_idkelas');
            $table->unsignedInteger('siswa_idsiswa');
            $table->timestamps();

            $table->unique(['kelas_idkelas', 'siswa_idsiswa']);
            $table->foreign('kelas_idkelas')->references('idkelas')->on('kelas')->restrictOnDelete();
            $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelassiswa');
    }
};
