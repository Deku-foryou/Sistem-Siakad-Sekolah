<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use Illuminate\Database\Seeder;

/**
 * Menyelaraskan label menu "setting" menjadi Pengaturan Akun.
 *
 * Konteks: menu url=setting sebelumnya berlabel "Profil Saya",
 * sehingga duplikat dengan menu profil-siswa (Profil Saya).
 * /setting sebenarnya adalah Pengaturan Akun (username, email, password).
 *
 * Idempoten: hanya mengupdate label dan ikon menu yang sudah ada.
 * Tidak membuat menu baru, tidak mengubah url, urutan, kategori, atau role.
 */
class MenuLabelSettingCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Appmenunew::where('url', 'setting')->first();

        if ($menu) {
            $menu->update([
                'appmenunew' => 'Pengaturan Akun',
                'icon' => 'fa-solid fa-gear',
            ]);
        }
    }
}
