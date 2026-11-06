<?php

namespace App\Exceptions;

use DomainException;

/**
 * Pesan agregat saat finalisasi ditolak.
 *
 * `daftarKesalahan` berisi seluruh hal yang harus diperbaiki sebelum rapor
 * dapat difinalisasi; Livewire menampilkannya sebagai daftar kepada pengguna.
 */
class FinalisasiException extends DomainException
{
    /** @var array<int, string> */
    public readonly array $daftarKesalahan;

    /** @param array<int, string> $daftarKesalahan */
    public function __construct(array $daftarKesalahan)
    {
        $daftarKesalahan = array_values(array_unique(array_map('strval', $daftarKesalahan)));
        $pesan = $daftarKesalahan === []
            ? 'Finalisasi dibatalkan.'
            : "Finalisasi dibatalkan. Perbaiki hal berikut:\n- ".implode("\n- ", $daftarKesalahan);

        parent::__construct($pesan);
        $this->daftarKesalahan = $daftarKesalahan;
    }
}
