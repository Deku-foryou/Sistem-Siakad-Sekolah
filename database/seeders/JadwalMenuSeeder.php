<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder menu untuk SISTEM JADWAL LEGACY (dibekukan).
 *
 * Sistem baru memakai Jamhari + JadwalKelas (lihat JamhariMenuSeeder dan
 * JadwalKelasMenuSeeder). Seeder ini hanya mengelola identitas legacy:
 * - Pengampu           -> menu 'pengampu' (dipakai PengampuComponent, legacy)
 * - Jadwal (Lama)      -> menu 'jadwal'   (dipakai JadwalComponent, legacy)
 *
 * Seeder ini TIDAK membuat menu 'jampelajaran'. Menu tersebut sudah dihapus
 * oleh JampelajaranCleanupSeeder; route lama 'jampelajaran' dialihkan ke
 * 'jamhari' oleh routes/web.php.
 *
 * Idempoten: firstOrCreate + update nilai kanonik, aman dijalankan berulang.
 */
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
                ['label' => 'Pengampu', 'url' => 'pengampu', 'icon' => 'fa-solid fa-clipboard-user', 'urut' => 3],
                ['label' => 'Jadwal (Lama)', 'url' => 'jadwal', 'icon' => 'fa-solid fa-calendar-days', 'urut' => 4],
            ];
            $menuIds = [];

            foreach ($menus as $menu) {
                $item = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                    ['url' => $menu['url']],
                    [
                        'appmenunew' => $menu['label'],
                        'icon' => $menu['icon'],
                        'urut' => $menu['urut'],
                        'appmenucat_idappmenucat' => $category->idappmenucat,
                    ]
                ));

                // Selaraskan label/ikon/urutan/kategori menu yang sudah ada.
                Appmenunew::unguarded(fn () => $item->update([
                    'appmenunew' => $menu['label'],
                    'icon' => $menu['icon'],
                    'urut' => $menu['urut'],
                    'appmenucat_idappmenucat' => $category->idappmenucat,
                ]));

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
