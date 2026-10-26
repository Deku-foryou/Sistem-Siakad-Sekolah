<?php

namespace App\Services;

use App\Models\User;

class LoginBlockService
{
    public static function cekBolehLogin(User $user): bool
    {
        if ($user->level === 'superadmin') {
            return true;
        }

        if ($user->level === 'siswa') {
            return $user->siswa()->where('status', 'aktif')->exists();
        }

        if ($user->level === 'guru') {
            return $user->guru()->where('status', 'aktif')->exists();
        }

        return true;
    }
}
