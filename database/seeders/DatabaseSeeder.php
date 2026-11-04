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
            SemesterMenuCleanupSeeder::class,
            PolajamCleanupSeeder::class,
            AbsensiHarianMenuSeeder::class,
            JadwalSiswaMenuSeeder::class,
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

        // Populate usermenu for superadmin - assign all menus to superadmin
        // Superadmin should have access to all application menus
        $menus = Appmenunew::query()->pluck('idappmenunew');
        foreach ($menus as $menuId) {
            Usermenu::firstOrCreate(
                ['user_iduser' => $admin->id, 'appmenunew_idappmenunew' => $menuId],
            );
        }

        info('DatabaseSeeder: Usermenu entries created for superadmin with '.count($menus).' menus.');
    }
}
