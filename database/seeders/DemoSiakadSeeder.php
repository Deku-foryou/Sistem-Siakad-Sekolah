<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator seeder data DEMO sintetis untuk SIAKAD (komprehensif).
 *
 * PRINSIP:
 * - Aditif & idempoten: hanya INSERT/firstOrCreate; tidak pernah UPDATE/DELETE data existing,
 *   kecuali kolom baru (tidak ada) — re-run tidak menggandakan baris.
 * - Seluruh data sintetis: prefix global `DEMO-` (order_id, NIS, NISN, dll.).
 * - Password akun demo: Hash::make('demo123') — hanya untuk data demo.
 * - Tidak membuat transaksi provider/Midtrans palsu: payment_attempt hanya
 *   created/pending/failed/expired (TIDAK ADA attempt success sintetis).
 * - Tabel history wali bersifat immutable (trigger) — insert-only.
 * - users.level: superadmin ada dari DatabaseSeeder; seeder ini menambah
 *   akun demo 'admin' (paket menu Admin tidak tersedia by design — lihat dokumentasi).
 *
 * Urutan dependency (wajib): menu (dari DatabaseSeeder) -> akun demo -> akademik ->
 * penilaian -> pengumuman/notifikasi -> wali -> keuangan -> docs.
 */
class DemoSiakadSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoAkunSeeder::class,
            DemoAkademikSeeder::class,
            DemoPenilaianSeeder::class,
            DemoPengumumanSeeder::class,
            DemoWaliSeeder::class,
            DemoKeuanganSeeder::class,
            DemoDocsSeeder::class,
        ]);

        // Paket menu default untuk akun demo siswa/guru/wali (idempoten).
        foreach (['siswa', 'guru', 'wali'] as $level) {
            $hasil = \App\Services\PaketMenuService::sinkronkanSemua($level);
            $this->command?->info("PaketMenuService[{$level}]: processed={$hasil['processed']} added={$hasil['added']} paket_tidak_ada=".($hasil['paket_tidak_ada'] ? 'ya' : 'tidak'));
        }

        Log::info('DemoSiakadSeeder: seeding demo sintetis selesai (idempoten).');
    }
}
