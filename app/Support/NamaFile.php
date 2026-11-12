<?php

namespace App\Support;

/**
 * Sanitasi nama file lintas platform (K2, 2B.13):
 * - whitelist [A-Za-z0-9._-]; selain itu diganti '-'
 * - path traversal (slash, backslash, titik di awal) dinetralkan
 * - tidak kosong (fallback), panjang dibatasi
 */
class NamaFile
{
    /**
     * Sanitasi satu segmen nama file.
     */
    public static function aman(?string $nilai, string $default = 'dokumen', int $maks = 48): string
    {
        $bersih = preg_replace('/[^A-Za-z0-9._-]/', '-', trim((string) $nilai) ?? '') ?? '';
        $bersih = trim($bersih, '.-');

        if ($bersih === '') {
            $bersih = $default;
        }

        return substr($bersih, 0, max(1, $maks));
    }
}
