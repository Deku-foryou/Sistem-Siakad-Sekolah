<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usermenu')) {
            return;
        }
        if (Schema::hasIndex('usermenu', ['user_iduser', 'appmenunew_idappmenunew'])) {
            return;
        }
        Schema::table('usermenu', function (Blueprint $table) {
            $table->index(['user_iduser', 'appmenunew_idappmenunew']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('usermenu') && Schema::hasIndex('usermenu', ['user_iduser', 'appmenunew_idappmenunew'])) {
            Schema::table('usermenu', function (Blueprint $table) {
                $table->dropIndex(['user_iduser', 'appmenunew_idappmenunew']);
            });
        }
    }
};
