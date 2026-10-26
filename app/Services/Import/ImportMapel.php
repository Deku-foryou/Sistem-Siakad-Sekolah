<?php

namespace App\Services\Import;

use App\Models\Mapel;
use App\Services\MapelService;
use InvalidArgumentException;

class ImportMapel extends ImportBase
{
    public function jenis(): string
    {
        return 'mapel';
    }

    public function kolom(): array
    {
        return ['kode', 'nama', 'kelompok'];
    }

    public function kunciDuplikat(array $values): ?string
    {
        $kode = trim((string) ($values['kode'] ?? ''));

        return $kode === '' ? null : mb_strtolower($kode, 'UTF-8');
    }

    protected function normalisasi(array $values): array
    {
        $kode = $this->wajibTeks($values, 'kode');
        if (Mapel::whereRaw('LOWER(kode) = ?', [mb_strtolower($kode, 'UTF-8')])->exists()) {
            throw new InvalidArgumentException('Kode mapel sudah terdaftar.');
        }
        $kelompok = mb_strtolower($this->wajibTeks($values, 'kelompok'), 'UTF-8');
        if (! in_array($kelompok, ['wajib', 'pilihan'], true)) {
            throw new InvalidArgumentException('Kelompok mapel harus wajib atau pilihan.');
        }

        return [
            'kode' => $kode,
            'nama' => $this->wajibTeks($values, 'nama'),
            'kelompok' => $kelompok,
        ];
    }

    protected function simpan(array $values): array
    {
        return MapelService::buat($values);
    }
}
