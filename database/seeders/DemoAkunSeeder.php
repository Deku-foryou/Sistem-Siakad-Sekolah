<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun demo lintas level (selain superadmin yang dibuat DatabaseSeeder).
 * Password semua akun demo: demo123 (Hash::make).
 *
 * Catatan: level 'admin' tidak memiliki paket menu default (tidak ada
 * rolecat 'Admin' pada seeder aplikasi) — akun demo.admin tetap bisa login
 * dan mengakses halaman yang mengizinkan level admin (mis. /transaksipembayaran),
 * tetapi tanpa menu paket. Paket admin tidak dikarang; keputusan paket admin
 * menunggu pemilik proyek.
 */
class DemoAkunSeeder extends Seeder
{
    public function run(): void
    {
        $superadminId = User::where('level', 'superadmin')->value('id');
        if (! $superadminId) {
            throw new \RuntimeException('DemoAkunSeeder: superadmin belum ada. Jalankan DatabaseSeeder lebih dulu.');
        }

        User::firstOrCreate(
            ['username' => 'demo.admin'],
            [
                'name' => 'Admin Demo',
                'email' => 'demo.admin@demo.sekolah.test',
                'password' => Hash::make('demo123'),
                'level' => 'admin',
                'status_akun' => 'aktif',
                'wajib_ganti_password' => false,
            ]
        );

        $this->command?->info('DemoAkunSeeder: akun demo.admin tersedia (password demo123; paket menu Admin tidak tersedia by design).');
    }
}
