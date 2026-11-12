<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Menu "Notifikasi" (/notifikasi) untuk paket Wali (2B.12, keputusan D7a).
 *
 * - Memakai menu yang sama dengan paket Guru & Siswa (url: notifikasi),
 *   bukan membuat menu duplikat.
 * - Superadmin tidak diberi menu pada tahap ini.
 * - Idempoten: Role::firstOrCreate + sinkronisasi paket Wali aman dijalankan berulang.
 */
class NotifikasiWaliMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Appmenunew::where('url', 'notifikasi')->first();

        if (! $menu) {
            $this->command?->warn('Menu notifikasi tidak ditemukan; jalankan NotifikasiMenuSeeder lebih dulu.');

            return;
        }

        $wali = Rolecat::firstOrCreate(['rolecat' => 'Wali']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $wali->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        // Sinkronkan paket Wali agar user wali yang sudah ada mendapat menu.
        PaketMenuService::sinkronkanSemua('wali');
    }
}
