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

/**
 * Menu admin "Kelola Akun Wali" (desain final §7.6, §9):
 * - Route: /wali-akun (Livewire WaliAkunComponent)
 * - Paket default: Admin (paket Wali disiapkan kosong — diisi modul portal pada tahap berikutnya)
 * - Superadmin mendapat usermenu secara idempoten.
 *
 * Idempoten: aman dijalankan berulang; tidak mengubah menu/paket yang sudah ada.
 */
class WaliAkunMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Wali'],
            ['urut' => 52]
        );

        $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
            ['url' => 'wali-akun'],
            [
                'appmenunew' => 'Kelola Akun Wali',
                'icon' => 'fa-solid fa-user-plus',
                'urut' => 50,
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]
        ));

        Appmenunew::unguarded(fn () => $menu->update([
            'appmenunew' => 'Kelola Akun Wali',
            'icon' => 'fa-solid fa-user-plus',
            'urut' => 50,
            'appmenucat_idappmenucat' => $kategori->idappmenucat,
        ]));

        // Paket Admin berisi menu pengelola akun wali (antrian verifikasi menyusul pada tahap berikutnya).
        $paketAdmin = Rolecat::firstOrCreate(['rolecat' => 'Admin']);

        Role::firstOrCreate([
            'rolecat_idrolecat' => $paketAdmin->idrolecat,
            'appmenunew_idappmenunew' => $menu->idappmenunew,
        ]);

        // Paket Wali: dibiarkan tanpa menu sampai modul portal wali disetujui.
        Rolecat::firstOrCreate(['rolecat' => 'Wali']);

        // Sinkronkan paket Admin ke semua akun admin yang sudah ada (idempoten).
        PaketMenuService::sinkronkanSemua('admin');
    }
}
