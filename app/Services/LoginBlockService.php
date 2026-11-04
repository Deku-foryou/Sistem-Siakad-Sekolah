<?php

namespace App\Services;

use App\Models\User;

class LoginBlockService
{
    /**
     * Cek apakah user diizinkan login berdasarkan level dan status data induk.
     *
     * @return bool true jika user boleh login, false sebaliknya
     */
    public static function cekBolehLogin(User $user): bool
    {
        return match ($user->level) {
            'superadmin' => true,
            'admin' => true,
            'guru' => $user->guru()->where('status', 'aktif')->exists(),
            'siswa' => $user->siswa()->where('status', 'aktif')->exists(),
            'walikelas' => false,  // Level walikelas belum diimplementasikan
            default => false,  // Unknown level: reject akses (safe default)
        };
    }
}
