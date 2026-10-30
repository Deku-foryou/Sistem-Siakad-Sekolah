<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use App\Models\Appmenucat;
use App\Models\Usermenu;
use App\Models\User;
use Illuminate\Database\Seeder;

class JamhariMenuSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Appmenucat::firstOrCreate(['appmenucat' => 'Jadwal Pelajaran'], ['urut' => 10]);
        
        $menuJam = Appmenunew::firstOrCreate(
            ['url' => 'jamhari'],
            ['appmenunew' => 'Jam Pelajaran', 'icon' => 'fa-solid fa-clock', 'urut' => 1, 'appmenucat_idappmenucat' => $cat->idappmenucat]
        );

        $menuLama = Appmenunew::where('url', 'jampelajaran')->first();
        if ($menuLama) {
            $menuLama->update(['appmenunew' => 'Jam Pelajaran (lama)']);
        }

        $superAdmins = User::where('level', 'superadmin')->get();
        foreach ($superAdmins as $user) {
            Usermenu::firstOrCreate([
                'user_iduser' => $user->id,
                'appmenunew_idappmenunew' => $menuJam->idappmenunew
            ]);
        }
    }
}
