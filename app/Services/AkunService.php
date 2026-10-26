<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AkunService
{
    public static function buat(string $username, string $nama, string $level, Carbon $tanggalLahir): User
    {
        $passwordAwal = $tanggalLahir->format('dmY');

        return User::create([
            'username' => $username,
            'name' => $nama,
            'email' => null,
            'password' => Hash::make($passwordAwal),
            'level' => $level,
            'wajib_ganti_password' => true,
        ]);
    }

    public static function resetPassword(User $user, Carbon $tanggalLahir): string
    {
        $passwordAwal = $tanggalLahir->format('dmY');
        $user->password = Hash::make($passwordAwal);
        $user->wajib_ganti_password = true;
        $user->save();

        return $passwordAwal;
    }
}
