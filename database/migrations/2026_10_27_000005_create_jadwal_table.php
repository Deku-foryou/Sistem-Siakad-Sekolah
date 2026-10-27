<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->increments('idjadwal');
            $table->unsignedInteger('pengampu_idpengampu');
            $table->unsignedTinyInteger('hari');
            $table->unsignedSmallInteger('jam_ke');
            $table->timestamps();

            $table->unique(
                ['pengampu_idpengampu', 'hari', 'jam_ke'],
                'jadwal_pengampu_hari_jam_unique'
            );
            $table->index(['hari', 'jam_ke'], 'jadwal_hari_jam_index');
            $table->foreign('pengampu_idpengampu')
                ->references('idpengampu')->on('pengampu')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};
