<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            // Drop the unique constraint that doesn't properly handle jurusan_id NULL
            // The constraint (tahunajaran_idtahunajaran, tingkat, jenis, rombel)
            // would incorrectly prevent different jurusans from having same rombel number
            $table->dropUnique(['tahunajaran_idtahunajaran', 'tingkat', 'jenis', 'rombel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            // Re-add the unique constraint if needed in the future
            // $table->unique(['tahunajaran_idtahunajaran', 'tingkat', 'jenis', 'rombel']);
        });
    }
};