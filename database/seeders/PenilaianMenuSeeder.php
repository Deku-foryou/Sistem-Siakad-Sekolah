<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menu konfigurasi penilaian (khusus superadmin).
 *
 * - Penugasan Mengajar -> /penugasanmengajar
 * - Komponen Nilai     -> /komponennilai
 * - KKTP               -> /kktpmapel
 *
 * Hanya berisi konfigurasi. Input nilai oleh guru, finalisasi, dan rapor
 * berada pada tahap berikutnya dan tidak didaftarkan di sini.
 *
 * Idempoten: firstOrCreate + penyelarasan nilai kanonik.
 */
class PenilaianMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Penilaian'],
                ['urut' => 55]
            );

            $daftar = [
                [
                    'url' => 'penugasanmengajar',
                    'label' => 'Penugasan Mengajar',
                    'icon' => 'fa-solid fa-chalkboard-user',
                    'urut' => 1,
                ],
                [
                    'url' => 'komponennilai',
                    'label' => 'Komponen Nilai',
                    'icon' => 'fa-solid fa-list-check',
                    'urut' => 2,
                ],
                [
                    'url' => 'kktpmapel',
                    'label' => 'KKTP',
                    'icon' => 'fa-solid fa-bullseye',
                    'urut' => 3,
                ],
            ];

            $adminIds = User::where('level', 'superadmin')->pluck('id');

            foreach ($daftar as $item) {
                $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                    ['url' => $item['url']],
                    [
                        'appmenunew' => $item['label'],
                        'icon' => $item['icon'],
                        'urut' => $item['urut'],
                        'appmenucat_idappmenucat' => $kategori->idappmenucat,
                    ]
                ));

                // Selaraskan nilai kanonik pada menu yang sudah ada.
                Appmenunew::unguarded(fn () => $menu->update([
                    'appmenunew' => $item['label'],
                    'icon' => $item['icon'],
                    'urut' => $item['urut'],
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]));

                foreach ($adminIds as $userId) {
                    Usermenu::firstOrCreate([
                        'user_iduser' => $userId,
                        'appmenunew_idappmenunew' => $menu->idappmenunew,
                    ]);
                }
            }
        });
    }
}
