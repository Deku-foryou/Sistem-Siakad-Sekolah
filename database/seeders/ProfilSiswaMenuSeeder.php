<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Mendaftarkan menu "Profil Saya" milik siswa.
 *
 * Idempoten: memakai firstOrCreate untuk kategori, menu, dan role.
 * Hanya menambahkan paket menu Siswa; menu dan paket lain tidak diubah.
 */
class ProfilSiswaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Jadwal & Absensi'],
            ['urut' => 50]
        );

        $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'profil-siswa'],
            [
                'appmenunew' => 'Profil Saya',
                'icon' => 'fa-solid fa-id-card',
                'urut' => 12,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        $paket = Rolecat::firstOrCreate(['rolecat' => 'Siswa']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $paket->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        PaketMenuService::sinkronkanSemua('siswa');
    }
}
