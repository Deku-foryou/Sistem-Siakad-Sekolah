<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemesterMenuCleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = Appmenunew::where('url', 'semester')->first();
        if ($menu) {
            DB::table('usermenu')->where('appmenunew_idappmenunew', $menu->idappmenunew)->delete();
            DB::table('role')->where('appmenunew_idappmenunew', $menu->idappmenunew)->delete();
            $menu->delete();
        }
    }
}
