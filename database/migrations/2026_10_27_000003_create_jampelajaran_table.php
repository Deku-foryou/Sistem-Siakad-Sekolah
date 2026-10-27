<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jampelajaran', function (Blueprint $table) {
            $table->increments('idjampelajaran');
            $table->unsignedInteger('polajam_idpolajam');
            $table->enum('jenis', ['pelajaran', 'istirahat']);
            $table->unsignedSmallInteger('jam_ke')->nullable();
            $table->time('mulai');
            $table->time('selesai');
            $table->timestamps();

            $table->unique(['polajam_idpolajam', 'jam_ke']);
            $table->index('polajam_idpolajam');
            $table->foreign('polajam_idpolajam')
                ->references('idpolajam')->on('polajam')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jampelajaran');
    }
};
