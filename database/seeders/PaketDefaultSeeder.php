<?php

namespace Database\Seeders;

use App\Models\Rolecat;
use Illuminate\Database\Seeder;

class PaketDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;

        // Create rolecat "Siswa" if not exists
        $siswaRolecat = Rolecat::firstOrCreate(
            ['rolecat' => 'Siswa'],
            ['rolecat' => 'Siswa']
        );
        if ($siswaRolecat->wasRecentlyCreated) {
            $created++;
        }

        // Create rolecat "Guru" if not exists
        $guruRolecat = Rolecat::firstOrCreate(
            ['rolecat' => 'Guru'],
            ['rolecat' => 'Guru']
        );
        if ($guruRolecat->wasRecentlyCreated) {
            $created++;
        }

        // Note: This seeder should be run manually once via tinker or direct command
        // It is NOT called from DatabaseSeeder to maintain idempotency control
        // LaravelSeeder::defaultPaket(): Laporkan baris yang dibuat
        info("PaketDefaultSeeder: rolecat Siswa dan Guru telah diinisialisasi. Baris baru: {$created}");
    }
}
