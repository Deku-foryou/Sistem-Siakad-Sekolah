<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IkonMenuSeeder extends Seeder
{
    public function run(): void
    {
        $icons = [
            'tahunajaran' => 'fa-solid fa-calendar-check',
            'semester' => 'fa-solid fa-layer-group',
            'guru' => 'fa-solid fa-person-chalkboard',
            'siswa' => 'fa-solid fa-user-graduate',
            'mapel' => 'fa-solid fa-book',
            'kelas' => 'fa-solid fa-school',
            'kelassiswa' => 'fa-solid fa-users',
            'polajam' => 'fa-solid fa-clock',
            'jampelajaran' => 'fa-solid fa-hourglass-half',
            'pengampu' => 'fa-solid fa-clipboard-user',
            'jadwal' => 'fa-solid fa-calendar-days',
            'berandasiswa' => 'fa-solid fa-house',
            'berandaguru' => 'fa-solid fa-house',
            'setting' => 'fa-solid fa-id-card',
        ];

        DB::transaction(function () use ($icons) {
            foreach ($icons as $url => $icon) {
                DB::table('appmenunew')
                    ->where('url', $url)
                    ->update(['icon' => $icon]);
            }
        });
    }
}
