<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Models\User;
use App\Models\Usermenu;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Mendaftarkan menu Pengumuman (superadmin) dan Pengumuman (siswa).
 *
 * Idempoten: memakai firstOrCreate untuk kategori, menu, role, dan usermenu.
 */
class PengumumanMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Jadwal & Absensi'],
            ['urut' => 50]
        );

        $menuSuperadmin = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'pengumuman'],
            [
                'appmenunew' => 'Pengumuman',
                'icon' => 'fa-solid fa-bullhorn',
                'urut' => 13,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        User::where('level', 'superadmin')->get()->each(function (User $user) use ($menuSuperadmin) {
            Usermenu::firstOrCreate([
                'user_iduser' => $user->id,
                'appmenunew_idappmenunew' => $menuSuperadmin->idappmenunew,
            ]);
        });

        $menuSiswa = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'pengumuman-siswa'],
            [
                'appmenunew' => 'Pengumuman',
                'icon' => 'fa-solid fa-bullhorn',
                'urut' => 13,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        $paket = Rolecat::firstOrCreate(['rolecat' => 'Siswa']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $paket->idrolecat,
            'appmenunew_idappmenunew' => $menuSiswa->idappmenunew,
        ]);

        PaketMenuService::sinkronkanSemua('siswa');
    }
}
