<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tahunajaran')) {
            return;
        }
        Schema::create('tahunajaran', function (Blueprint $table) {
            $table->increments('idtahunajaran');
            $table->string('nama', 20)->unique();
            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahunajaran');
    }
};
