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
 * Menu "Finalisasi Rapor" (/finalisasirapor) dan "Koreksi Nilai" (/koreksinilai).
 *
 * Finalisasi Rapor: wali kelas (guru) dan superadmin.
 * Koreksi Nilai: superadmin saja (guru mata pelajaran tidak diberi akses).
 *
 * Idempoten: firstOrCreate untuk kategori, menu, role, dan usermenu.
 */
class FinalisasiKoreksiMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Penilaian'],
                ['urut' => 55]
            );

            $finalisasi = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'finalisasirapor'],
                [
                    'appmenunew' => 'Finalisasi Rapor',
                    'icon' => 'fa-solid fa-clipboard-check',
                    'urut' => 5,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            Appmenunew::unguarded(fn () => $finalisasi->update([
                'appmenunew' => 'Finalisasi Rapor',
                'icon' => 'fa-solid fa-clipboard-check',
                'urut' => 5,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            $koreksi = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'koreksinilai'],
                [
                    'appmenunew' => 'Koreksi Nilai',
                    'icon' => 'fa-solid fa-file-pen',
                    'urut' => 6,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            Appmenunew::unguarded(fn () => $koreksi->update([
                'appmenunew' => 'Koreksi Nilai',
                'icon' => 'fa-solid fa-file-pen',
                'urut' => 6,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            // Paket Guru: Finalisasi Rapor (guru/wali). Bukan Koreksi Nilai.
            $paket = Rolecat::firstOrCreate(['rolecat' => 'Guru']);

            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $finalisasi->idappmenunew,
            ]);

            // Superadmin: kedua menu tersedia di sidebar.
            $superadminIds = User::where('level', 'superadmin')->pluck('id');

            foreach ([$finalisasi, $koreksi] as $menu) {
                foreach ($superadminIds as $userId) {
                    Usermenu::firstOrCreate([
                        'user_iduser' => $userId,
                        'appmenunew_idappmenunew' => $menu->idappmenunew,
                    ]);
                }
            }
        });

        // Sinkronkan paket Guru ke seluruh user guru.
        PaketMenuService::sinkronkanSemua('guru');
    }
}
