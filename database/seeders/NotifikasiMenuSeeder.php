<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Menu "Notifikasi" (/notifikasi): guru & siswa (paket Guru & Siswa).
 * Superadmin tidak diberi menu pada tahap ini (rute 404 lewat check.level).
 * Idempoten: firstOrCreate untuk kategori, menu, role; sinkronkan paket.
 */
class NotifikasiMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Penilaian'],
            ['urut' => 55]
        );

        $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'notifikasi'],
            [
                'appmenunew' => 'Notifikasi',
                'icon' => 'fa-solid fa-bell',
                'urut' => 9,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        Appmenunew::unguarded(fn () => $menu->update([
            'appmenunew' => 'Notifikasi',
            'icon' => 'fa-solid fa-bell',
            'urut' => 9,
            'appmenucat_idappmenucat' => $kategori->idappmenucat,
        ]));

        $guru = Rolecat::firstOrCreate(['rolecat' => 'Guru']);
        $siswa = Rolecat::firstOrCreate(['rolecat' => 'Siswa']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $guru->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $siswa->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        // Sinkronkan paket Guru & Siswa agar user guru/siswa yang sudah ada mendapat menu.
        PaketMenuService::sinkronkanSemua('guru');
        PaketMenuService::sinkronkanSemua('siswa');
    }
}
