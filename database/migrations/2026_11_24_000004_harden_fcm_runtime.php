<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaikan runtime FCM:
     * - fcm_token mengikuti batas validator 500 karakter.
     * - fcm_claim_token dipakai sebagai fencing token saat klaim job.
     */
    public function up(): void
    {
        if (Schema::hasColumn('notifikasi', 'fcm_claim_token') === false) {
            Schema::table('notifikasi', function (Blueprint $table) {
                $table->string('fcm_claim_token', 64)->nullable()->after('fcm_claimed_at');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('fcm_device_tokens', function (Blueprint $table) {
            $table->dropUnique(['fcm_token']);
        });

        Schema::table('fcm_device_tokens', function (Blueprint $table) {
            $table->string('fcm_token', 500)->unique()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifikasi', 'fcm_claim_token')) {
            Schema::table('notifikasi', function (Blueprint $table) {
                $table->dropColumn('fcm_claim_token');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('fcm_device_tokens', function (Blueprint $table) {
            $table->dropUnique(['fcm_token']);
        });

        Schema::table('fcm_device_tokens', function (Blueprint $table) {
            $table->string('fcm_token')->unique()->change();
        });

        DB::statement('UPDATE fcm_device_tokens SET fcm_token = LEFT(fcm_token, 255) WHERE CHAR_LENGTH(fcm_token) > 255');
    }
};
