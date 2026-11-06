<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mendaftarkan menu "Input Nilai" (halaman /inputnilai) untuk guru.
 *
 * Halaman ini khusus guru dan otorisasinya ditegakkan per penugasan mengajar
 * (bukan paket menu), sehingga menu tidak diberikan ke superadmin.
 *
 * Idempoten: firstOrCreate untuk kategori, menu, dan role.
 */
class InputNilaiMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Penilaian'],
                ['urut' => 55]
            );

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'inputnilai'],
                [
                    'appmenunew' => 'Input Nilai',
                    'icon' => 'fa-solid fa-pen-to-square',
                    'urut' => 4,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            // Selaraskan nilai kanonik pada menu yang sudah ada.
            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Input Nilai',
                'icon' => 'fa-solid fa-pen-to-square',
                'urut' => 4,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            $paket = Rolecat::firstOrCreate(['rolecat' => 'Guru']);

            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $menu->idappmenunew,
            ]);
        });

        // Sinkronkan paket Guru ke seluruh user guru (menambah usermenu bila belum ada).
        PaketMenuService::sinkronkanSemua('guru');
    }
}
