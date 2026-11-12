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
 * Menu kanonis "Antrean Verifikasi" (/wali-antrian-verifikasi).
 *
 * - Paket default: Admin (superadmin mendapat usermenu secara idempoten).
 * - Idempoten & self-deduplicating: karena `appmenunew.url` tidak UNIQUE,
 *   seeder ini menghapus semua baris duplikat ber-url sama sebelum menormalkan
 *   baris kanonis, sehingga aman dijalankan berulang dan pada DB yang masih
 *   memiliki sisa menu uji (52/53/54).
 */
class WaliAntrianMenuSeeder extends Seeder
{
    public const URL = 'wali-antrian-verifikasi';

    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Wali'],
                ['urut' => 52]
            );

            // 1) Ambil SEMUA baris dengan url ini (bisa >1 jika ada drift)
            $menus = Appmenunew::where('url', self::URL)
                ->orderBy('idappmenunew')
                ->get();

            // 2) Dedupe: pertahankan yang pertama, hapus sisanya + relasinya
            $kanon = $menus->shift();
            foreach ($menus as $dup) {
                DB::table('usermenu')->where('appmenunew_idappmenunew', $dup->idappmenunew)->delete();
                DB::table('role')->where('appmenunew_idappmenunew', $dup->idappmenunew)->delete();
                $dup->delete();
            }

            // 3) Buat jika belum ada
            if (! $kanon) {
                $kanon = Appmenunew::unguarded(fn () => Appmenunew::create([
                    'appmenunew' => 'Antrean Verifikasi',
                    'url' => self::URL,
                    'icon' => 'fa-solid fa-user-check',
                    'urut' => 51,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]));
            }

            // 4) Normalisasi atribut kanonis (idempoten)
            Appmenunew::unguarded(fn () => $kanon->update([
                'appmenunew' => 'Antrean Verifikasi',
                'icon' => 'fa-solid fa-user-check',
                'urut' => 51,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            // 5) Paket Admin
            $paketAdmin = Rolecat::firstOrCreate(['rolecat' => 'Admin']);
            Role::firstOrCreate([
                'rolecat_idrolecat' => $paketAdmin->idrolecat,
                'appmenunew_idappmenunew' => $kanon->idappmenunew,
            ]);

            // 6) Sinkronkan ke semua akun admin yang ada
            PaketMenuService::sinkronkanSemua('admin');

            // 7) Superadmin (idempoten)
            User::where('level', 'superadmin')->chunk(100, function ($users) use ($kanon) {
                foreach ($users as $user) {
                    Usermenu::firstOrCreate([
                        'user_iduser' => $user->id,
                        'appmenunew_idappmenunew' => $kanon->idappmenunew,
                    ]);
                }
            });
        });
    }
}
