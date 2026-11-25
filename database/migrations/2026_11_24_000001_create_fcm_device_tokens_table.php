<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel token perangkat FCM (malas, tidak disimpan di log, bisa direvoke).
 *
 * Konvensi proyek: tabel lowercase tanpa plural, PK id{tabel}, FK {tabel}_id{pk},
 * ON DELETE RESTRICT. Satu token hanya terkait satu akun (unik); satu akun bisa
 * punya beberapa perangkat (tidak ada unique(user_iduser)).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fcm_device_tokens')) {
            return;
        }

        Schema::create('fcm_device_tokens', function (Blueprint $table) {
            $table->id('idfcmdevicetokens');
            $table->unsignedBigInteger('user_iduser');
            $table->string('fcm_token')->unique();
            $table->string('platform', 10); // android | ios
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('user_iduser', 'fcm_device_tokens_user_index');

            $table->foreign('user_iduser')
                ->references('id')->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_device_tokens');
    }
};
