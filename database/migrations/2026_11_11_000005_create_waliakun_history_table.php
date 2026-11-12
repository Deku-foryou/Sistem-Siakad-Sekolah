<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FONDASI PORTAL WALI MURID — Tahap 2B.7.3B
 *
 * Desain final §5.4: tabel `waliakun_history` (append-only).
 * - Tanpa kolom sandi; `aktor_peran` menyimpan snapshot tingkat aktor (T1c).
 * - Seluruh FK ON DELETE RESTRICT ON UPDATE RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('waliakun_history')) {
            return;
        }

        Schema::create('waliakun_history', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->bigIncrements('idwaliakun_history');
            $table->unsignedBigInteger('user_iduser');
            $table->unsignedBigInteger('aktor_id');
            $table->enum('aktor_peran', ['superadmin', 'admin', 'sistem']);
            $table->enum('event', ['akun_dibuat', 'akun_diaktifkan', 'akun_dinonaktifkan', 'password_direset_paksa']);
            $table->enum('status_sebelum', ['aktif', 'nonaktif'])->nullable();
            $table->enum('status_sesudah', ['aktif', 'nonaktif'])->nullable();
            $table->string('alasan', 500)->nullable();
            $table->timestamp('created_at')->nullable()->default(null);

            $table->index(['user_iduser', 'created_at'], 'idx_waliakun_history_user');

            $table->foreign('user_iduser', 'waliakun_history_user_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('aktor_id', 'waliakun_history_aktor_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waliakun_history');
    }
};
