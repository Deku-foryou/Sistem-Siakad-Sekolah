<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WaliAkademikMenuSeeder extends Seeder
{
    public const URL = 'wali-akademik';

    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(['appmenucat' => 'Wali'], ['urut' => 52]);

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => self::URL],
                [
                    'appmenunew' => 'Akademik',
                    'icon' => 'fa-solid fa-graduation-cap',
                    'urut' => 30,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Akademik',
                'icon' => 'fa-solid fa-graduation-cap',
                'urut' => 30,
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
