<?php

namespace App\Support;

use App\Models\User;

class BerandaUrl
{
    public static function untuk(User $user): string
    {
        return match ($user->level) {
            'superadmin' => '/dashboard',
            'guru' => '/berandaguru',
            'siswa' => '/berandasiswa',
            default => '/setting',
        };
    }
}
