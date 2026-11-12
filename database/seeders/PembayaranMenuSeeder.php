<?php

namespace Database\Seeders;

use App\Models\Appmenucat;
use App\Models\Appmenunew;
use App\Models\Role;
use App\Models\Rolecat;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;

/**
 * Menu Keuangan (khusus superadmin).
 *
 * - Dashboard Keuangan -> /dashboardkeuangan
 * - Data Tagihan       -> /datatagihan
 * - Tarif              -> /tarif
 * - Beasiswa           -> /beasiswa
 *
 * Idempoten: firstOrCreate + penyelarasan nilai kanonik.
 */
class PembayaranMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = Appmenucat::firstOrCreate(
            ['appmenucat' => 'Keuangan'],
            ['urut' => 60]
        );

        $daftar = [
            ['url' => 'dashboardkeuangan', 'label' => 'Dashboard Keuangan', 'icon' => 'fa-solid fa-chart-pie', 'urut' => 1],
            ['url' => 'datatagihan', 'label' => 'Data Tagihan', 'icon' => 'fa-solid fa-file-invoice', 'urut' => 2],
            ['url' => 'tarif', 'label' => 'Tarif', 'icon' => 'fa-solid fa-tags', 'urut' => 3],
            ['url' => 'beasiswa', 'label' => 'Beasiswa', 'icon' => 'fa-solid fa-hands-holding-child', 'urut' => 4],
            ['url' => 'transaksipembayaran', 'label' => 'Transaksi Pembayaran', 'icon' => 'fa-solid fa-receipt', 'urut' => 5],
            ['url' => 'pembayaranmanual', 'label' => 'Pembayaran Manual', 'icon' => 'fa-solid fa-hand-holding-dollar', 'urut' => 6],
            ['url' => 'rekonsiliasikeuangan', 'label' => 'Rekonsiliasi Keuangan', 'icon' => 'fa-solid fa-scale-balanced', 'urut' => 7],
            ['url' => 'refundkoreksi', 'label' => 'Refund & Koreksi', 'icon' => 'fa-solid fa-money-bill-transfer', 'urut' => 8],
            ['url' => 'pembebasandenda', 'label' => 'Pembebasan Denda', 'icon' => 'fa-solid fa-hand-holding-dollar', 'urut' => 9],
        ];

        $menus = [];
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

            $menus[] = $menu;
        }

        $paket = Rolecat::firstOrCreate(['rolecat' => 'Superadmin']);

        foreach ($menus as $menu) {
            Role::firstOrCreate([
                'rolecat_idrolecat' => $paket->idrolecat,
                'appmenunew_idappmenunew' => $menu->idappmenunew,
            ]);
        }

        $adminIds = User::where('level', 'superadmin')->pluck('id');
        foreach ($menus as $menu) {
            foreach ($adminIds as $userId) {
                Usermenu::firstOrCreate([
                    'user_iduser' => $userId,
                    'appmenunew_idappmenunew' => $menu->idappmenunew,
                ]);
            }
        }
    }
}
