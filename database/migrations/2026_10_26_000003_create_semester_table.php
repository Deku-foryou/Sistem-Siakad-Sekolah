<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('semester')) {
            return;
        }
        Schema::create('semester', function (Blueprint $table) {
            $table->increments('idsemester');
            $table->unsignedInteger('tahunajaran_idtahunajaran');
            $table->enum('nama', ['Ganjil', 'Genap']);
            $table->boolean('aktif')->default(false);
            $table->timestamps();

            $table->unique(['tahunajaran_idtahunajaran', 'nama']);
            $table->foreign('tahunajaran_idtahunajaran')
                ->references('idtahunajaran')->on('tahunajaran')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester');
    }
};
