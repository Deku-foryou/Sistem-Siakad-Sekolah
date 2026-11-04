<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use App\Models\Usermenu;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JadwalKelasMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Update menu lama
            $menuJadwal = Appmenunew::where('url', 'jadwal')->first();
            if ($menuJadwal) {
                $menuJadwal->update(['appmenunew' => 'Jadwal (lama)']);
            }

            // Tambah menu baru
            $menuBaru = Appmenunew::firstOrCreate(
                ['url' => 'jadwalkelas'],
                [
                    'appmenunew' => 'Jadwal Kelas',
                    'icon' => 'fa-solid fa-calendar-days',
                    'appmenucat_idappmenucat' => DB::table('appmenucat')->where('appmenucat', 'Jadwal Pelajaran')->value('idappmenucat') ?? 1,
                    'urut' => DB::table('appmenunew')->max('urut') + 1,
                ]
            );

            // Tambah ke superadmin
            $adminIds = User::where('level', 'superadmin')->pluck('id');
            foreach ($adminIds as $userId) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $userId,
                    'appmenunew_idappmenunew' => $menuBaru->idappmenunew,
                ], [
                    'idusermenu' => DB::table('usermenu')->max('idusermenu') + 1,
                ]);
            }
        });
    }
}
