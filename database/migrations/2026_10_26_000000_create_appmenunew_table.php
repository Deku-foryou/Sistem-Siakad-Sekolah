<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appmenunew', function (Blueprint $table) {
            $table->id('idappmenunew');
            $table->string('appmenunew');
            $table->string('url');
            $table->string('icon');
            $table->integer('urut');
            $table->unsignedInteger('appmenucat_idappmenucat')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appmenunew');
    }
};