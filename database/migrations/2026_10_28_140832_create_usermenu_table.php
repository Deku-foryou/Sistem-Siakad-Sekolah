<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usermenu', function (Blueprint $table) {
            $table->integer('idusermenu')->primary();
            $table->unsignedBigInteger('appmenunew_idappmenunew');
            $table->unsignedBigInteger('user_iduser');
            $table->timestamps();

            $table->foreign('appmenunew_idappmenunew')
                ->references('idappmenunew')->on('appmenunew')
                ->onDelete('cascade');
            $table->foreign('user_iduser')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usermenu');
    }
};