<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rolecat', function (Blueprint $table) {
            $table->unsignedInteger('idrolecat')->primary()->autoIncrement();
            $table->string('rolecat')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rolecat');
    }
};