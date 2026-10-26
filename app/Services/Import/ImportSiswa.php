<?php

namespace App\Services\Import;

use App\Models\Siswa;
use App\Models\User;
use App\Services\SiswaService;

class ImportSiswa extends ImportBase
{
    public function jenis(): string
    {
        return 'siswa';
    }

    public function kolom(): array
    {
        return ['nisn', 'nis', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'nama_ayah', 'nama_ibu', 'telepon_ortu', 'tahun_masuk'];
    }

    public function kunciDuplikat(array $values): ?string
    {
        $nisn = trim((string) ($values['nisn'] ?? ''));
        if ($nisn === '') {
            return null;
        }
        if (ctype_digit($nisn) && strlen($nisn) < 10) {
            $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
        }

        return $this->key(['nisn' => $nisn], ['nisn']);
    }

    protected function normalisasi(array $values): array
    {
        $nisn = $this->normalisasiNisn($values['nisn'] ?? null);
        if (Siswa::where('nisn', $nisn)->exists() || User::where('username', $nisn)->exists()) {
            throw new \InvalidArgumentException('NISN sudah terdaftar.');
        }

        $warnings = [];

        return [
            'nisn' => $nisn,
            'nis' => $this->teksOpsional($values, 'nis'),
            'nama' => $this->wajibTeks($values, 'nama'),
            'jenis_kelamin' => $this->gender($values),
            'tempat_lahir' => $this->teksOpsional($values, 'tempat_lahir'),
            'tanggal_lahir' => $this->tanggalLahir($values['tanggal_lahir'] ?? null),
            'agama' => $this->teksOpsional($values, 'agama'),
            'alamat' => $this->teksOpsional($values, 'alamat'),
            'nama_ayah' => $this->teksOpsional($values, 'nama_ayah'),
            'nama_ibu' => $this->teksOpsional($values, 'nama_ibu'),
            'telepon_ortu' => $this->telepon($values, 'telepon_ortu', $warnings),
            'tahun_masuk' => $this->tahunMasuk($values),
            '_warnings' => $warnings,
        ];
    }

    protected function simpan(array $values): array
    {
        $warnings = $values['_warnings'];
        unset($values['_warnings']);
        $result = SiswaService::buat($values);
        $result['warnings'] = array_merge($warnings, $result['warnings'] ?? []);
        foreach ($result['warnings'] as &$warning) {
            if (str_contains($warning, 'Paket menu "Siswa" belum dibuat')) {
                $warning = "Paket menu 'Siswa' belum dibuat; akun dibuat tanpa menu";
            }
        }

        return $result;
    }
}
