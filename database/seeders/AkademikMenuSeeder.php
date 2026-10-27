<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;

class AkademikMenuSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Akademik'],
            ['urut' => 50]
        );

        $menus = [
            ['appmenunew' => 'Tahun Ajaran', 'url' => 'tahunajaran', 'icon' => 'fa-solid fa-calendar-check', 'urut' => 1],
            ['appmenunew' => 'Semester', 'url' => 'semester', 'icon' => 'fa-solid fa-layer-group', 'urut' => 2],
        ];

        foreach ($menus as $m) {
            $menu = Appmenunew::firstOrCreate(
                ['url' => $m['url']],
                [
                    'appmenunew' => $m['appmenunew'],
                    'icon' => $m['icon'],
                    'urut' => $m['urut'],
                    'appmenucat_idappmenucat' => $cat->idappmenucat,
                ]
            );

            $superadmins = User::where('level', 'superadmin')->get();
            foreach ($superadmins as $user) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $user->id,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        }
    }
}
