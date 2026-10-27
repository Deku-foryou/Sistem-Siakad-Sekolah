<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polajamhari', function (Blueprint $table) {
            $table->increments('idpolajamhari');
            $table->unsignedInteger('polajam_idpolajam');
            $table->unsignedTinyInteger('hari');
            $table->timestamps();

            $table->unique('hari');
            $table->index('polajam_idpolajam');
            $table->foreign('polajam_idpolajam')
                ->references('idpolajam')->on('polajam')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polajamhari');
    }
};
