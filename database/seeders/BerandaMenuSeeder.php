<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

class BerandaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Beranda'],
            ['urut' => -1]
        );

        $menus = [];
        foreach ([
            ['label' => 'Beranda Siswa', 'url' => 'berandasiswa', 'icon' => 'fa-solid fa-house', 'urut' => 1],
            ['label' => 'Beranda Guru', 'url' => 'berandaguru', 'icon' => 'fa-solid fa-house', 'urut' => 2],
            ['label' => 'Pengaturan Akun', 'url' => 'setting', 'icon' => 'fa-solid fa-gear', 'urut' => 3],
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

            $menus[$definition['url']] = $menu;
        }

        foreach ([
            'Siswa' => ['berandasiswa', 'setting'],
            'Guru' => ['berandaguru', 'setting'],
        ] as $namaPaket => $urls) {
            $paket = Rolecat::firstOrCreate(['rolecat' => $namaPaket]);

            foreach ($urls as $url) {
                Role::firstOrCreate([
                    'rolecat_idrolecat' => $paket->idrolecat,
                    'appmenunew_idappmenunew' => $menus[$url]->idappmenunew,
                ]);
            }
        }

        PaketMenuService::sinkronkanSemua('siswa');
        PaketMenuService::sinkronkanSemua('guru');
    }
}
