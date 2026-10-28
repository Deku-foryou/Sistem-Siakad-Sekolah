<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Usermenu;

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
            info("DatabaseSeeder: Superadmin user created with email: admin@sekolah.id");
        } else {
            info("DatabaseSeeder: Superadmin user already exists, skipping creation.");
        }

        // Populate usermenu for superadmin - assign all menus to superadmin
        // Superadmin should have access to all application menus
        $menus = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]; // All appmenunew IDs
        foreach ($menus as $key => $menuId) {
            Usermenu::firstOrCreate(
                ['user_iduser' => $admin->id, 'appmenunew_idappmenunew' => $menuId],
                ['idusermenu' => $key + 1] // Unique idusermenu for each entry
            );
        }

        info("DatabaseSeeder: Usermenu entries created for superadmin with " . count($menus) . " menus.");
    }
}