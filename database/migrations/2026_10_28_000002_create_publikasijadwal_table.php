<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publikasijadwal', function (Blueprint $table) {
            $table->id('idpublikasijadwal');
            $table->string('nomor')->unique();
            $table->unsignedInteger('semester_idsemester');
            $table->unsignedInteger('kelas_idkelas');
            $table->unsignedInteger('versi')->default(1);
            $table->string('status')->default('published');
            $table->timestamp('published_at');
            $table->unsignedBigInteger('published_by')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->foreign('semester_idsemester')->references('idsemester')->on('semester')->cascadeOnDelete();
            $table->foreign('kelas_idkelas')->references('idkelas')->on('kelas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publikasijadwal');
    }
};
