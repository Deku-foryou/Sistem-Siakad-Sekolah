<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jamhari')) {
            Schema::create('jamhari', function (Blueprint $table) {
                $table->increments('idjamhari');
                $table->tinyInteger('hari')->unsigned();
                $table->smallInteger('jam_ke')->unsigned()->nullable();
                $table->string('jenis', 10); // 'pelajaran' | 'istirahat'
                $table->time('mulai');
                $table->time('selesai');
                $table->string('keterangan', 100)->nullable();
                $table->timestamps();

                $table->unique(['hari', 'jam_ke'], 'jamhari_hari_jamke_unique');
                $table->index(['hari', 'mulai']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jamhari');
    }
};
