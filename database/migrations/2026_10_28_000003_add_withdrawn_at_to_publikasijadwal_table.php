<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publikasijadwal', function (Blueprint $table) {
            $table->timestamp('withdrawn_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('publikasijadwal', function (Blueprint $table) {
            $table->dropColumn('withdrawn_at');
        });
    }
};
