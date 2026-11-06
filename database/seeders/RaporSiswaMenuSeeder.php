<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;

/**
 * Menu "Rapor Saya" (/raporsiswa, kategori Penilaian urut 8):
 * paket Siswa saja. Superadmin tidak diberi usermenu (seperti inputnilai);
 * route dijaga check.level:siswa + menu.access.
 */
class RaporSiswaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Penilaian'],
            ['urut' => 55]
        );

        $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'raporsiswa'],
            [
                'appmenunew' => 'Rapor Saya',
                'icon' => 'fa-solid fa-graduation-cap',
                'urut' => 8,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        Appmenunew::unguarded(fn () => $menu->update([
            'appmenunew' => 'Rapor Saya',
            'icon' => 'fa-solid fa-graduation-cap',
            'urut' => 8,
            'appmenucat_idappmenucat' => $kategori->idappmenucat,
        ]));

        $paket = Rolecat::firstOrCreate(['rolecat' => 'Siswa']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $paket->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        PaketMenuService::sinkronkanSemua('siswa');
    }
}
