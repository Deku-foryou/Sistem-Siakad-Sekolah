<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gurumapel')) {
            Schema::create('gurumapel', function (Blueprint $table) {
                $table->increments('idgurumapel');
                $table->unsignedInteger('guru_idguru');
                $table->unsignedInteger('mapel_idmapel');
                $table->timestamps();

                $table->unique(['guru_idguru', 'mapel_idmapel'], 'gurumapel_guru_mapel_unique');
                $table->index('mapel_idmapel');

                $table->foreign('guru_idguru')->references('idguru')->on('guru')->onDelete('restrict');
                $table->foreign('mapel_idmapel')->references('idmapel')->on('mapel')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gurumapel');
    }
};
