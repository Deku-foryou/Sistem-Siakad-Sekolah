<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penerima notifikasi per user (guru/siswa), dibuat sebagai snapshot
     * saat notifikasi dibuat. dibaca_pada NULL = belum dibaca.
     * UNIQUE (notifikasi, user) mencegah duplikasi recipient.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifikasi_penerima')) {
            Schema::create('notifikasi_penerima', function (Blueprint $table) {
                $table->bigIncrements('idnotifikasi_penerima');
                $table->unsignedBigInteger('notifikasi_idnotifikasi');
                $table->unsignedBigInteger('user_iduser');
                $table->timestamp('dibaca_pada')->nullable();
                $table->timestamps();

                $table->unique(
                    ['notifikasi_idnotifikasi', 'user_iduser'],
                    'notifikasi_penerima_unique'
                );
                $table->index(['user_iduser', 'dibaca_pada'], 'notifikasi_penerima_user_baca_index');

                $table->foreign('notifikasi_idnotifikasi')
                    ->references('idnotifikasi')->on('notifikasi')
                    ->onDelete('restrict');
                $table->foreign('user_iduser')
                    ->references('id')->on('users')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_penerima');
    }
};
