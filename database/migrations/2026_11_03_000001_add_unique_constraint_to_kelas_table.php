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
            // Add unique constraint on (tahunajaran_idtahunajaran, tingkat, jenis, rombel)
            // For jurusan_id NULL handling, we use a unique index name that includes COALESCE logic
            // MySQL unique index: treats NULL as distinct, so we need to handle this
            // We'll add a unique index and handle application-level logic for NULL jurusan_id
            
            // First, let's add a new column as a computed/derived value for the index
            // Actually, simpler approach: add the unique constraint without COALESCE
            // and handle the NULL case in application logic
            
            $table->unique([
                'tahunajaran_idtahunajaran',
                'tingkat',
                'jenis',
                'rombel',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique(['tahunajaran_idtahunajaran', 'tingkat', 'jenis', 'rombel']);
        });
    }
};