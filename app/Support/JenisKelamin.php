<?php

namespace App\Support;

use InvalidArgumentException;

class JenisKelamin
{
    public static function normalisasi(?string $value): string
    {
        if ($value === null) {
            throw new InvalidArgumentException('Jenis kelamin tidak valid.');
        }

        return match (mb_strtoupper(trim($value), 'UTF-8')) {
            'L', 'LAKI-LAKI', 'LAKI LAKI' => 'L',
            'P', 'PEREMPUAN' => 'P',
            default => throw new InvalidArgumentException('Jenis kelamin tidak valid.'),
        };
    }
}
