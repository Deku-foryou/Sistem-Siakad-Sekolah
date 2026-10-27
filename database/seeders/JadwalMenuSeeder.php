<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JadwalMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $category = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Jadwal Pelajaran'],
                ['urut' => -2]
            );
            $menus = [
                ['label' => 'Pola Jam', 'url' => 'polajam', 'icon' => 'fa-regular fa-clock'],
                ['label' => 'Jam Pelajaran', 'url' => 'jampelajaran', 'icon' => 'fa-solid fa-clock'],
                ['label' => 'Pengampu', 'url' => 'pengampu', 'icon' => 'fa-solid fa-chalkboard-user'],
                ['label' => 'Jadwal', 'url' => 'jadwal', 'icon' => 'fa-regular fa-calendar-days'],
            ];
            $menuIds = [];

            foreach ($menus as $index => $menu) {
                $item = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                    ['url' => $menu['url']],
                    [
                        'appmenunew' => $menu['label'],
                        'icon' => $menu['icon'],
                        'urut' => $index + 1,
                        'appmenucat_idappmenucat' => $category->idappmenucat,
                    ]
                ));
                $menuIds[] = $item->idappmenunew;
            }

            User::where('level', 'superadmin')->each(function (User $user) use ($menuIds) {
                foreach ($menuIds as $menuId) {
                    Usermenu::firstOrCreate([
                        'user_iduser' => $user->id,
                        'appmenunew_idappmenunew' => $menuId,
                    ]);
                }
            });
        });
    }
}
