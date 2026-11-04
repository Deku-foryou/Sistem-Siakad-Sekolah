<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jamhari', function (Blueprint $table) {
            // Drop the old global unique constraint on (hari, jam_ke)
            $table->dropUnique('jamhari_hari_jamke_unique');

            // Add new class-based unique index.
            // In MySQL/InnoDB, NULL values in unique indexes are not considered duplicates.
            // Since kelas_id is nullable, we are safe to use this simple index for class-based uniqueness.
            $table->unique(['kelas_id', 'hari', 'jam_ke'], 'jamhari_kelas_hari_jamke_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check for global duplication before re-adding global unique constraint
        // This prevents rollback from failing or corrupting data
        $duplicateGlobals = DB::table('jamhari')
            ->whereNotNull('kelas_id')
            ->select('hari', 'jam_ke')
            ->groupBy('hari', 'jam_ke')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateGlobals) {
            throw new \Exception('Tidak dapat rollback: Terdapat kelas yang menggunakan jam yang sama pada hari yang sama. Hapus duplikasi terlebih dahulu sebelum rollback.');
        }

        Schema::table('jamhari', function (Blueprint $table) {
            $table->dropUnique('jamhari_kelas_hari_jamke_unique');
            $table->unique(['hari', 'jam_ke'], 'jamhari_hari_jamke_unique');
        });
    }
};
