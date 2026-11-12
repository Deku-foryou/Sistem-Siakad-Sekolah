<?php

namespace Database\Seeders;

use App\Models\Appmenunew;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menghapus menu 'jampelajaran' (duplikat/legacy, salah label).
 *
 * Konteks:
 * - Menu dengan url 'jampelajaran' ada lebih dari satu baris (mis. satu di
 *   kategori "Jadwal Pelajaran" dan satu di kategori "Akademik") dan tidak ada
 *   komponen JampelajaranComponent, sehingga tidak bisa diakses dengan benar.
 * - Nama kanonik sistem jadwal baru adalah 'jamhari' (Jam Pelajaran per kelas).
 * - Route lama 'jampelajaran' tetap hidup sebagai pengalihan ke 'jamhari'
 *   (lihat routes/web.php) sehingga tautan lama tidak rusak.
 * - Modul legacy (tabel jadwal, jampelajaran, publikasijadwal, JadwalComponent)
 *   tidak diubah dan tetap tersedia lewat menu 'jadwal'.
 *
 * Idempoten: menghapus semua baris dengan url 'jampelajaran' beserta usermenu
 * dan role terkait; bila tidak ada, tidak melakukan apa-apa.
 */
class JampelajaranCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $menus = Appmenunew::where('url', 'jampelajaran')->get();

            foreach ($menus as $menu) {
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
