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
 * Menu "Relasi Wali" (/wali-relasi) — portal self-service wali.
 * Masuk paket `Wali` (rolecat). Idempoten.
 */
class WaliRelasiMenuSeeder extends Seeder
{
    public const URL = 'wali-relasi';

    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Wali'],
                ['urut' => 52]
            );

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => self::URL],
                [
                    'appmenunew' => 'Relasi Saya',
                    'icon' => 'fa-solid fa-link',
                    'urut' => 20,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Relasi Saya',
                'icon' => 'fa-solid fa-link',
                'urut' => 20,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            $paket = Rolecat::firstOrCreate(['rolecat' => 'Wali']);
            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $menu->idappmenunew,
            ]);

            PaketMenuService::sinkronkanSemua('wali');
        });
    }
}
