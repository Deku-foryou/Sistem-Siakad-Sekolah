<?php

namespace App\Support;

final class Hari
{
    public const NAMA = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public static function nama(int $hari): string
    {
        return self::NAMA[$hari] ?? throw new \InvalidArgumentException('Hari harus Senin sampai Minggu.');
    }
}
