<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Menu "Beranda Wali" (/wali-beranda) — halaman status akun, bukan akademik.
 * Masuk paket `Wali` (rolecat), bukan paket `Admin`.
 * Idempoten: aman dijalankan berulang.
 */
class WaliBerandaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Wali'],
            ['urut' => 52]
        );

        $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'wali-beranda'],
            [
                'appmenunew' => 'Beranda',
                'icon' => 'fa-solid fa-house',
                'urut' => 10,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        Appmenunew::unguarded(fn () => $menu->update([
            'appmenunew' => 'Beranda',
            'icon' => 'fa-solid fa-house',
            'urut' => 10,
            'appmenucat_idappmenucat' => $kategori->idappmenucat,
        ]));

        $paket = Rolecat::firstOrCreate(['rolecat' => 'Wali']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $paket->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        // Pastikan user wali yang sudah ada juga mendapat menu Beranda Wali.
        PaketMenuService::sinkronkanSemua('wali');
    }
}
