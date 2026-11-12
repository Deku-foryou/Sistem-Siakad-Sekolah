<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FONDASI PORTAL WALI MURID — Tahap 2B.7.3B
 *
 * Desain final §5.1: tambah kolom status akun pada `users`.
 * Sifat: ADITIF. Aditif tanpa backfill — seluruh baris lama berstatus 'aktif'.
 *
 * Keputusan yang diikuti (jangan diubah tanpa keputusan bisnis baru):
 * - FK/AKUN bukan status relasi; `status_akun` hanya mengatur boleh/tidak login.
 * - Khusus dibaca pada cabang `level = 'wali'` oleh LoginBlockService (desain §7.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'status_akun')) {
                $table->enum('status_akun', ['aktif', 'nonaktif'])
                    ->default('aktif')
                    ->after('level');
            }

            if (! Schema::hasColumn('users', 'dinonaktifkan_pada')) {
                $table->timestamp('dinonaktifkan_pada')
                    ->nullable()
                    ->default(null)
                    ->after('status_akun');
            }
        });
    }

    public function down(): void
    {
        // Sengaja no-op: menghapus kolom `status_akun`/`dinonaktifkan_pada`
        // akan menghilangkan data status akun wali secara diam-diam.
        // Bila benar-benar diperlukan, hapus manual dengan pengawasan.
    }
};
