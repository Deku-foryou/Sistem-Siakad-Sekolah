<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appmenucat', function (Blueprint $table) {
            $table->increments('idappmenucat');
            $table->string('appmenucat')->unique();
            $table->integer('urut');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appmenucat');
    }
};