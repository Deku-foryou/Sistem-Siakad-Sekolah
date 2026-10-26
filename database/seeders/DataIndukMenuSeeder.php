<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;

class DataIndukMenuSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Data Induk'],
            ['urut' => 60]
        );

        $menus = [
            ['appmenunew' => 'Guru', 'url' => 'guru', 'urut' => 1],
            ['appmenunew' => 'Siswa', 'url' => 'siswa', 'urut' => 2],
            ['appmenunew' => 'Mapel', 'url' => 'mapel', 'urut' => 3],
            ['appmenunew' => 'Kelas', 'url' => 'kelas', 'urut' => 4],
            ['appmenunew' => 'Penempatan Siswa', 'url' => 'kelassiswa', 'urut' => 5],
        ];

        foreach ($menus as $m) {
            $menu = Appmenunew::firstOrCreate(
                ['url' => $m['url']],
                [
                    'appmenunew' => $m['appmenunew'],
                    'icon' => null,
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
