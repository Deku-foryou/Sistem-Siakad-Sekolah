<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FONDASI PORTAL WALI MURID — Tahap 2B.7.3B
 *
 * Desain final §7.1–7.2 / §4 B12: nilai enum `users.level` TIDAK diubah selain
 * menambahkan `wali` (additive, nilai terakhir). Enum lama dipertahankan:
 *   superadmin, admin, guru, siswa, walikelas  →  superadmin, admin, guru, siswa, walikelas, wali
 *
 * MODIFY pada enum menambahkan nilai baru tanpa menyentuh baris existing
 * (nilai lama tidak diubah/dihapus). Query tanpa UPDATE → aman untuk data.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `level` enum('superadmin','admin','guru','siswa','walikelas','wali') NOT NULL DEFAULT 'siswa'");
    }

    public function down(): void
    {
        // No-op: mengembalikan enum dengan menghapus 'wali' akan gagal bila
        // sudah ada user level='wali' (error 1265) — itu justru perilaku
        // yang diinginkan. Hapus manual dengan pengawasan bila diperlukan.
    }
};
