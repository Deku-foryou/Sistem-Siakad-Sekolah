<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kelas')) {
            return;
        }
        Schema::create('kelas', function (Blueprint $table) {
            $table->increments('idkelas');
            $table->unsignedInteger('tahunajaran_idtahunajaran');
            $table->string('nama');
            $table->enum('tingkat', ['10', '11', '12']);
            $table->enum('jenis', ['induk', 'mapel_pilihan']);
            $table->unsignedInteger('guru_idguru')->nullable();
            $table->timestamps();

            $table->unique(['tahunajaran_idtahunajaran', 'nama']);
            $table->foreign('tahunajaran_idtahunajaran')->references('idtahunajaran')->on('tahunajaran')->restrictOnDelete();
            $table->foreign('guru_idguru')->references('idguru')->on('guru')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
