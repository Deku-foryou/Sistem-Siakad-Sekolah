<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditif: penanda pengiriman FCM per Notifikasi (untuk idempotensi job).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifikasi')) {
            return;
        }
        Schema::table('notifikasi', function (Blueprint $table) {
            if (! Schema::hasColumn('notifikasi', 'fcm_dispatched_at')) {
                $table->timestamp('fcm_dispatched_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('notifikasi')) {
            Schema::table('notifikasi', function (Blueprint $table) {
                $table->dropColumnIfExists('fcm_dispatched_at');
            });
        }
    }
};
