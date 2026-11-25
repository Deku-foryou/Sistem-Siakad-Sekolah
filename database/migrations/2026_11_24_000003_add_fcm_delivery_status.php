<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditif (F3.1): status pengiriman FCM per token per notifikasi.
 *
 * fcm_dispatched_at hanya dipakai sebagai penanda "seluruh target selesai",
 * bukan satu-satunya indikator keberhasilan. Status per token dipakai untuk
 * membedakan pengiriman penuh, parsial, token invalid, dan kegagalan retry.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifikasi')) {
            Schema::table('notifikasi', function (Blueprint $table) {
                if (! Schema::hasColumn('notifikasi', 'fcm_claimed_at')) {
                    $table->timestamp('fcm_claimed_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('notifikasi_fcm_delivery') && Schema::hasTable('fcm_device_tokens')) {
            Schema::create('notifikasi_fcm_delivery', function (Blueprint $table) {
                $table->bigIncrements('idnotifikasi_fcm_delivery');
                $table->unsignedBigInteger('notifikasi_idnotifikasi');
                $table->unsignedBigInteger('fcm_token_id');
                $table->string('status', 20);
                $table->string('message_id', 255)->nullable();
                $table->string('error', 255)->nullable();
                $table->timestamps();

                $table->unique(
                    ['notifikasi_idnotifikasi', 'fcm_token_id'],
                    'notifikasi_fcm_delivery_unique'
                );
                $table->index('status', 'notifikasi_fcm_delivery_status_index');

                $table->foreign('notifikasi_idnotifikasi')
                    ->references('idnotifikasi')->on('notifikasi')
                    ->onDelete('restrict');
                $table->foreign('fcm_token_id')
                    ->references('idfcmdevicetokens')->on('fcm_device_tokens')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_fcm_delivery');

        if (Schema::hasTable('notifikasi')) {
            Schema::table('notifikasi', function (Blueprint $table) {
                $table->dropColumnIfExists('fcm_claimed_at');
            });
        }
    }
};
