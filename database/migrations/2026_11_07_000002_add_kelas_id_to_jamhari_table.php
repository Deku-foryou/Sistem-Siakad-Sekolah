<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jamhari', function (Blueprint $table) {
            $table->unsignedBigInteger('kelas_id')->nullable()->after('hari');
        });
    }

    public function down(): void
    {
        Schema::table('jamhari', function (Blueprint $table) {
            $table->dropColumn('kelas_id');
        });
    }
};
