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
 * Mendaftarkan menu "Jadwal Guru" (halaman /jadwalguru) sebagai menu resmi.
 *
 * Target pengguna: guru (sumber data: JadwalKelas + Jamhari, semester aktif)
 * dan superadmin (dapat melihat seluruh jadwal). Sebelumnya halaman ini hanya
 * ditautkan permanen dari sidebar; menu ini menjadikannya bagian dari paket
 * menu Guru dan daftar menu superadmin.
 *
 * Idempoten: memakai firstOrCreate untuk kategori, menu, role, dan usermenu.
 */
class JadwalGuruMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $kategori = Appmenucat::firstOrCreate(
                ['appmenucat' => 'Jadwal & Absensi'],
                ['urut' => 50]
            );

            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => 'jadwalguru'],
                [
                    'appmenunew' => 'Jadwal Guru',
                    'icon' => 'fa-solid fa-calendar-week',
                    'urut' => 11,
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            // Selaraskan nilai kanonik pada menu yang sudah ada.
            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => 'Jadwal Guru',
                'icon' => 'fa-solid fa-calendar-week',
                'urut' => 11,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            // Paket Guru: menjadi daftar hak akses + sidebar guru.
            $paket = Rolecat::firstOrCreate(['rolecat' => 'Guru']);

            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $menu->idappmenunew,
            ]);

            // Superadmin: tampil di sidebar (akses halaman diizinkan komponen).
            $superadmins = User::where('level', 'superadmin')->get();
            foreach ($superadmins as $user) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $user->id,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        });

        // Sinkronkan paket Guru ke seluruh user guru (menambah usermenu bila belum ada).
        PaketMenuService::sinkronkanSemua('guru');
    }
}
