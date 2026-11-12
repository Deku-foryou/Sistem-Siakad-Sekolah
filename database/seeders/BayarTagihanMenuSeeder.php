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
 * Menu Pembayaran Online (Sprint 2).
 *
 * - "Bayar Tagihan" (/bayartagihan)  -> paket Siswa & Wali (pembayaran tagihan online).
 * - "Transaksi Pembayaran" (/transaksipembayaran) -> paket Superadmin (operasional read-only).
 *
 * Kedua menu berada pada kategori 'Keuangan' (urut 60) agar selaras dengan modul keuangan.
 *
 * Idempoten: firstOrCreate + penyelarasan nilai kanonik + paket sinkron ulang aman.
 */
class BayarTagihanMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(['appmenucat' => 'Keuangan'], ['urut' => 60]);

        $daftar = [
            ['url' => 'bayartagihan', 'label' => 'Bayar Tagihan', 'icon' => 'fa-solid fa-credit-card', 'urut' => 15, 'paket' => ['Siswa', 'Wali']],
            ['url' => 'transaksipembayaran', 'label' => 'Transaksi Pembayaran', 'icon' => 'fa-solid fa-receipt', 'urut' => 5, 'paket' => ['Superadmin']],
        ];

        foreach ($daftar as $item) {
            $menu = Appmenunew::unguarded(fn () => Appmenunew::firstOrCreate(
                ['url' => $item['url']],
                [
                    'appmenunew' => $item['label'],
                    'icon' => $item['icon'],
                    'urut' => $item['urut'],
                    'appmenucat_idappmenucat' => $kategori->idappmenucat,
                ]
            ));

            Appmenunew::unguarded(fn () => $menu->update([
                'appmenunew' => $item['label'],
                'icon' => $item['icon'],
                'urut' => $item['urut'],
                'appmenucat_idappmenucat' => $kategori->idappmenucat,
            ]));

            foreach ($item['paket'] as $namaPaket) {
                $paket = Rolecat::firstOrCreate(['rolecat' => $namaPaket]);
                Role::firstOrCreate([
                    'rolecat_idrolecat' => $paket->idrolecat,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        }

        // Superadmin tidak punya paket_default config — assign langsung seperti PembayaranMenuSeeder.
        $menuTransaksi = Appmenunew::where('url', 'transaksipembayaran')->first();
        if ($menuTransaksi) {
            $superadminIds = User::where('level', 'superadmin')->pluck('id');
            foreach ($superadminIds as $userId) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $userId,
                    'appmenunew_idappmenunew' => $menuTransaksi->idappmenunew,
                ]);
            }
        }

        // Sinkronkan paket agar user siswa / wali yang sudah ada mendapat menu.
        foreach (['siswa', 'wali'] as $level) {
            PaketMenuService::sinkronkanSemua($level);
        }
    }
}
