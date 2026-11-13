<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Registry eksplisit (whitelist) untuk pencarian LOV.
 *
 * Setiap definisi menentukan model, kolom pencarian, kolom yang
 * boleh keluar dari server, scope wajib, dan batas hasil.
 * Komponen pemanggil menentukan scope tambahan melalui parameter $scope
 * di LovService::search (closure $query => void) bila perlu.
 *
 * Aturan:
 * - Tidak ada nama model / kolom dari request. Hanya kunci terdaftar di sini.
 * - Kolom yang boleh dikirim ke browser selalu minimal (tanpa password, token, dsb.).
 */
final class Lov
{
    public const KEY_SISWA = 'siswa';

    public const LIMIT_DEFAULT = 20;

    public const LIMIT_MAKSIMAL = 50;

    /**
     * Definisi registry per kunci — dipanggil tiap kali (PHP tidak mengizinkan
     * closure di dalam konstanta).
     *
     * @return array<string, array<string, mixed>>
     */
    private static function semua(): array
    {
        return [
            self::KEY_SISWA => [
                'model' => \App\Models\Siswa::class,
                'cari' => ['nama', 'nis', 'nisn'],
                'pilih' => ['idsiswa', 'nis', 'nisn', 'nama', 'status'],
                'where' => [['status', 'aktif']],
                'order_by' => 'nama',
                'label' => fn ($r) => $r->nama,
                'sublabel' => fn ($r) => 'NIS '.($r->nis ?? '-').' · NISN '.($r->nisn ?? '-'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function definisi(string $key): array
    {
        $def = self::semua()[$key] ?? null;
        if ($def === null) {
            throw new InvalidArgumentException("LOV '{$key}' tidak terdaftar.");
        }

        return $def;
    }

    public static function kunci(): array
    {
        return array_keys(self::semua());
    }
}
