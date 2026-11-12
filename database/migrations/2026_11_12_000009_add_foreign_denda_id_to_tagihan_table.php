<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tagihan', 'denda_id')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->foreign('denda_id')
                    ->references('iddenda')->on('denda')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropForeign(['denda_id']);
        });
    }
};
