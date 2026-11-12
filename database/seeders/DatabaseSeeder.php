<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Appmenunew;
use App\Models\User;
use App\Models\Usermenu;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default paket/rolecat
        $this->call([
            PaketDefaultSeeder::class,
            AkademikMenuSeeder::class,
            BerandaMenuSeeder::class,
            DataIndukMenuSeeder::class,
            IkonMenuSeeder::class,
            JadwalMenuSeeder::class,
            JadwalKelasMenuSeeder::class,
            JamhariMenuSeeder::class,
            JampelajaranCleanupSeeder::class,
            SemesterMenuCleanupSeeder::class,
            PolajamCleanupSeeder::class,
            AbsensiHarianMenuSeeder::class,
            JadwalGuruMenuSeeder::class,
            JadwalSiswaMenuSeeder::class,
            ProfilSiswaMenuSeeder::class,
            MenuLabelSettingCleanupSeeder::class,
            PengumumanMenuSeeder::class,
            PenilaianMenuSeeder::class,
            InputNilaiMenuSeeder::class,
            FinalisasiKoreksiMenuSeeder::class,
            RaporWaliKelasMenuSeeder::class,
            RaporSiswaMenuSeeder::class,
            NotifikasiMenuSeeder::class,
            WaliAkunMenuSeeder::class,
            WaliBerandaMenuSeeder::class,
            WaliRelasiMenuSeeder::class,
            WaliAkademikMenuSeeder::class,
            WaliAntrianMenuSeeder::class,
            PembayaranMenuSeeder::class,
            BayarTagihanMenuSeeder::class,
        ]);

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@sekolah.id'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'level' => 'superadmin',
                'wajib_ganti_password' => false,
                'password' => bcrypt('rahasia123'),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            info('DatabaseSeeder: Superadmin user created with email: admin@sekolah.id');
        } else {
            info('DatabaseSeeder: Superadmin user already exists, skipping creation.');
        }

        // Populate usermenu for superadmin - assign all menus to superadmin,
        // kecuali menu self-service Portal Wali (hanya untuk level 'wali').
        // Superadmin should have access to all application menus
        $menus = Appmenunew::query()
            ->whereNotIn('url', ['wali-beranda', 'wali-relasi', 'wali-akademik', 'bayartagihan'])
            ->pluck('idappmenunew');
        foreach ($menus as $menuId) {
            Usermenu::firstOrCreate(
                ['user_iduser' => $admin->id, 'appmenunew_idappmenunew' => $menuId],
            );
        }

        info('DatabaseSeeder: Usermenu entries created for superadmin with '.count($menus).' menus.');
    }
}
