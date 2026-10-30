<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            if (!Schema::hasColumn('guru', 'kode_guru')) {
                $table->string('kode_guru', 50)->nullable()->after('nip')->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropUnique(['kode_guru']);
            $table->dropColumn('kode_guru');
        });
    }
};
