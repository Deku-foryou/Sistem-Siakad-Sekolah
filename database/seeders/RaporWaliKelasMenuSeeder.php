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
use Illuminate\Support\Facades\DB;

/**
 * Menu "Wali Kelas & Rapor" (/raporwalikelas, kategori Penilaian urut 7):
 * wali kelas (guru) dan superadmin. Idempoten.
 */
class RaporWaliKelasMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Penilaian'],
                ['urut' => 55]
            );

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'raporwalikelas'],
                [
                    'appmenunew' => 'Wali Kelas & Rapor',
                    'icon' => 'fa-solid fa-file-lines',
                    'urut' => 7,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Wali Kelas & Rapor',
                'icon' => 'fa-solid fa-file-lines',
                'urut' => 7,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            $paket = Rolecat::firstOrCreate(['rolecat' => 'Guru']);

            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $menu->idappmenunew,
            ]);

            foreach (User::where('level', 'superadmin')->pluck('id') as $userId) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $userId,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        });

        PaketMenuService::sinkronkanSemua('guru');
    }
}
