<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Mendaftarkan halaman siswa (jadwal & histori absensi) sebagai menu resmi.
 *
 * Idempoten: memakai firstOrCreate untuk kategori, menu, dan role.
 * Hanya menambahkan paket menu Siswa; menu dan paket lain tidak diubah.
 */
class JadwalSiswaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Jadwal & Absensi'],
            ['urut' => 50]
        );

        $menus = [];
        foreach ([
            ['label' => 'Jadwal Siswa', 'url' => 'jadwalsiswa', 'icon' => 'fa-solid fa-calendar-week', 'urut' => 12],
            ['label' => 'Histori Absensi', 'url' => 'absensi-siswa', 'icon' => 'fa-solid fa-clipboard-list', 'urut' => 13],
        ] as $definition) {
            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => $definition['url']],
                [
                    'appmenunew' => $definition['label'],
                    'icon' => $definition['icon'],
                    'urut' => $definition['urut'],
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            // Selaraskan nilai kanonik pada menu yang sudah ada.
            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => $definition['label'],
                'icon' => $definition['icon'],
                'urut' => $definition['urut'],
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            $menus[$definition['url']] = $menu;
        }

        $paket = Rolecat::firstOrCreate(['rolecat' => 'Siswa']);

        foreach ($menus as $menu) {
            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $menu->idappmenunew,
            ]);
        }

        PaketMenuService::sinkronkanSemua('siswa');
    }
}
