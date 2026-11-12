<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder menu untuk SISTEM JADWAL BARU: manajemen jadwal kelas.
 *
 * - Manajemen jadwal kelas -> /jadwalkelas -> JadwalKelasComponent
 * - Label menu: "Jadwal Kelas", khusus superadmin.
 *
 * Idempoten: firstOrCreate + update nilai kanonik, aman dijalankan berulang.
 */
class JadwalKelasMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $category = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Jadwal Pelajaran'],
                ['urut' => -2]
            );

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'jadwalkelas'],
                [
                    'appmenunew' => 'Jadwal Kelas',
                    'icon' => 'fa-solid fa-calendar-days',
                    'urut' => 2,
                    'appmenucat_idappmenucat' => $category->idappmenucat,
                ]
            ));

            // Selaraskan nilai kanonik pada menu yang sudah ada.
            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Jadwal Kelas',
                'icon' => 'fa-solid fa-calendar-days',
                'urut' => 2,
                'appmenucat_idappmenucat' => $category->idappmenucat,
            ]));

            $adminIds = User::where('level', 'superadmin')->pluck('id');
            foreach ($adminIds as $userId) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $userId,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        });
    }
}
