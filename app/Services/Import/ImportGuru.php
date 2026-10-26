<?php

namespace App\Services\Import;

use App\Models\Guru;
use App\Models\User;
use App\Services\GuruService;

class ImportGuru extends ImportBase
{
    public function jenis(): string
    {
        return 'guru';
    }

    public function kolom(): array
    {
        return ['nip', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'alamat'];
    }

    public function kunciDuplikat(array $values): ?string
    {
        $nip = trim((string) ($values['nip'] ?? ''));

        return $nip === '' ? null : $this->key(['nip' => $nip], ['nip']);
    }

    protected function normalisasi(array $values): array
    {
        $nip = $this->normalisasiNip($values['nip'] ?? null);
        if (Guru::where('nip', $nip)->exists() || User::where('username', $nip)->exists()) {
            throw new \InvalidArgumentException('NIP sudah terdaftar.');
        }

        $warnings = [];

        return [
            'nip' => $nip,
            'nama' => $this->wajibTeks($values, 'nama'),
            'jenis_kelamin' => $this->gender($values),
            'tempat_lahir' => $this->teksOpsional($values, 'tempat_lahir'),
            'tanggal_lahir' => $this->tanggalLahir($values['tanggal_lahir'] ?? null),
            'telepon' => $this->telepon($values, 'telepon', $warnings),
            'alamat' => $this->teksOpsional($values, 'alamat'),
            '_warnings' => $warnings,
        ];
    }

    protected function simpan(array $values): array
    {
        $warnings = $values['_warnings'];
        unset($values['_warnings']);
        $result = GuruService::buat($values);
        $result['warnings'] = array_merge($warnings, $result['warnings'] ?? []);
        foreach ($result['warnings'] as &$warning) {
            if (str_contains($warning, 'Paket menu "Guru" belum dibuat')) {
                $warning = "Paket menu 'Guru' belum dibuat; akun dibuat tanpa menu";
            }
        }

        return $result;
    }
}
