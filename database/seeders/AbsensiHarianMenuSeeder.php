<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;

class AbsensiHarianMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari atau buat kategori "Jadwal & Absensi"
        $category = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Jadwal & Absensi'],
            ['urut' => 50]
        );

        // Cari atau buat menu "Absensi Harian"
        $menu = Appmenunew::firstOrCreate(
            ['url' => 'absensi-harian'],
            [
                'appmenucat_idappmenucat' => $category->idappmenucat,
                'appmenunew' => 'Absensi Harian',
                'urut' => 10,
                'icon' => 'fa-solid fa-clipboard-list',
            ]
        );

        // Selaraskan nilai kanonik pada menu yang sudah ada.
        \App\Models\Appmenunew::unguarded(fn () => $menu->update([
            'appmenucat_idappmenucat' => $category->idappmenucat,
            'appmenunew' => 'Absensi Harian',
            'urut' => 10,
            'icon' => 'fa-solid fa-clipboard-list',
        ]));

        // Assign menu ke guru melalui Role (permission-based access control)
        // ini digunakan oleh middleware check.level untuk validasi kewenangan
        $guruRolecat = Rolecat::where('rolecat', 'Guru')->first();
        if ($guruRolecat) {
            Role::firstOrCreate(
                [
                    'rolecat_idrolecat' => $guruRolecat->idrolecat,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]
            );
        }

        // Assign menu ke guru melalui Usermenu (menampilkan di sidebar)
        // Setiap guru mendapatkan entri usermenu unik dengan idusermenu terhitung
        $guruUsers = User::where('level', 'guru')->get();
        $baseId = 100; // base value untuk menghindari konflik dengan usermenu lain

        foreach ($guruUsers as $index => $user) {
            $idusermenu = $baseId + $index;
            Usermenu::firstOrCreate(
                [
                    'user_iduser' => $user->id,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ],
                [
                    'idusermenu' => $idusermenu,
                ]
            );
        }

        // Untuk superadmin: ambil semua user dengan level superadmin dan assign menu
        // Superadmin mendapatkan semua menu, usermenu IDs mulai dari 1
        $superadminUsers = User::where('level', 'superadmin')->get();
        foreach ($superadminUsers as $user) {
            Usermenu::firstOrCreate(
                [
                    'user_iduser' => $user->id,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]
            );
        }
    }
}
