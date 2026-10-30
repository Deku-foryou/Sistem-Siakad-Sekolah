<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JampelajaranCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $menu = Appmenunew::where('url', 'jampelajaran')->first();
            
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
