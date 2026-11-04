<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PolajamCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $menu = Appmenunew::where('url', 'polajam')->first();

            if ($menu) {
                // Hapus dari usermenu
                DB::table('usermenu')->where('appmenunew_idappmenunew', $menu->idappmenunew)->delete();

                // Hapus dari role
                DB::table('role')->where('appmenunew_idappmenunew', $menu->idappmenunew)->delete();

                // Hapus menu itu sendiri
                $menu->delete();
            }
        });
    }
}
