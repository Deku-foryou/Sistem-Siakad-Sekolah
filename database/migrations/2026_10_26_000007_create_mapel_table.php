<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mapel')) {
            return;
        }
        Schema::create('mapel', function (Blueprint $table) {
            $table->increments('idmapel');
            $table->string('kode')->unique();
            $table->string('nama');
            $table->enum('kelompok', ['wajib', 'pilihan']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapel');
    }
};
