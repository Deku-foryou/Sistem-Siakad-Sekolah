<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder menu untuk SISTEM JADWAL BARU: pengaturan jam per kelas.
 *
 * - Manajemen jam per kelas -> /jamhari -> JamhariComponent
 * - Label menu: "Jam Pelajaran" (satu-satunya menu "Jam Pelajaran" yang aktif)
 * - Khusus superadmin.
 *
 * Menu 'jampelajaran' (legacy) dihapus oleh JampelajaranCleanupSeeder;
 * route lama 'jampelajaran' dialihkan ke 'jamhari' oleh routes/web.php.
 *
 * Idempoten: firstOrCreate + update nilai kanonik, aman dijalankan berulang.
 */
class JamhariMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $category = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Jadwal Pelajaran'],
                ['urut' => -2]
            );

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'jamhari'],
                [
                    'appmenunew' => 'Jam Pelajaran',
                    'icon' => 'fa-solid fa-clock',
                    'urut' => 1,
                    'appmenucat_idappmenucat' => $category->idappmenucat,
                ]
            ));

            // Selaraskan nilai kanonik pada menu yang sudah ada.
            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Jam Pelajaran',
                'icon' => 'fa-solid fa-clock',
                'urut' => 1,
                'appmenucat_idappmenucat' => $category->idappmenucat,
            ]));

            $superadmins = User::where('level', 'superadmin')->get();
            foreach ($superadmins as $user) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $user->id,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        });
    }
}
