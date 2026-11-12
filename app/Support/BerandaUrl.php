<?php

namespace App\Support;

use App\Models\User;

class BerandaUrl
{
    /**
     * Tentukan URL dashboard berdasarkan user level.
     *
     * @return string URL dashboard yang sesuai
     *
     * @throws \Exception Jika level user tidak dikenali
     */
    public static function untuk(User $user): string
    {
        return match ($user->level) {
            'superadmin' => '/dashboard',
            // Level 'admin' masih ada di enum users.level, tetapi tidak memiliki
            // dashboard sendiri dan /dashboard hanya untuk superadmin. Arahkan ke
            // halaman profil agar tidak terjadi redirect loop ke /dashboard.
            'admin' => '/setting',
            'guru' => '/berandaguru',
            'siswa' => '/berandasiswa',
            'walikelas' => '/setting',  // Level walikelas: redirect ke profil (not implemented yet)
            // Beranda wali (wali-beranda) tersedia sebagai route terdaftar dengan menu.access.
            'wali' => '/wali-beranda',
            default => abort(403, 'Unknown user level: '.$user->level),
        };
    }
}
