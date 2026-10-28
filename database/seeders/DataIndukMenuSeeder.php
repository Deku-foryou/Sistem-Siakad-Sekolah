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
            ['appmenunew' => 'Guru', 'url' => 'guru', 'icon' => 'fa-solid fa-person-chalkboard', 'urut' => 1],
            ['appmenunew' => 'Siswa', 'url' => 'siswa', 'icon' => 'fa-solid fa-user-graduate', 'urut' => 2],
            ['appmenunew' => 'Mapel', 'url' => 'mapel', 'icon' => 'fa-solid fa-book', 'urut' => 3],
            ['appmenunew' => 'Kelas', 'url' => 'kelas', 'icon' => 'fa-solid fa-school', 'urut' => 4],
            ['appmenunew' => 'Penempatan Siswa', 'url' => 'kelassiswa', 'icon' => 'fa-solid fa-users', 'urut' => 5],
            ['appmenunew' => 'Jurusan', 'url' => 'jurusan', 'icon' => 'fa-solid fa-school', 'urut' => 6],
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
