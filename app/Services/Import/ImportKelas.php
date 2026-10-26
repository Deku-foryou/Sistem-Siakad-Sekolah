<?php

namespace App\Services\Import;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Tahunajaran;
use App\Services\KelasService;
use InvalidArgumentException;

class ImportKelas extends ImportBase
{
    public function jenis(): string
    {
        return 'kelas';
    }

    public function kolom(): array
    {
        return ['tahun_ajaran', 'nama', 'tingkat', 'jenis', 'nip_wali'];
    }

    public function kunciDuplikat(array $values): ?string
    {
        if (trim((string) ($values['tahun_ajaran'] ?? '')) === '' || trim((string) ($values['nama'] ?? '')) === '') {
            return null;
        }

        return $this->key($values, ['tahun_ajaran', 'nama']);
    }

    protected function normalisasi(array $values): array
    {
        $tahunAjaran = $this->wajibTeks($values, 'tahun_ajaran');
        $tahun = Tahunajaran::where('nama', $tahunAjaran)->first();
        if (! $tahun) {
            throw new InvalidArgumentException('Tahun ajaran tidak ditemukan.');
        }

        $nama = $this->wajibTeks($values, 'nama');
        if (Kelas::where('tahunajaran_idtahunajaran', $tahun->idtahunajaran)->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama, 'UTF-8')])->exists()) {
            throw new InvalidArgumentException('Kelas sudah terdaftar.');
        }

        $jenis = mb_strtolower($this->wajibTeks($values, 'jenis'), 'UTF-8');
        if (! in_array($jenis, ['induk', 'mapel_pilihan'], true)) {
            throw new InvalidArgumentException('Jenis kelas harus induk atau mapel_pilihan.');
        }

        $wali = $this->teksOpsional($values, 'nip_wali');
        if ($jenis !== 'induk' && $wali !== null) {
            throw new InvalidArgumentException('NIP wali hanya boleh diisi untuk kelas induk.');
        }
        $guru = null;
        if ($jenis === 'induk' && $wali !== null) {
            $guru = Guru::where('nip', $this->normalisasiNip($wali))->where('status', 'aktif')->first();
            if (! $guru) {
                throw new InvalidArgumentException('Guru wali tidak ditemukan atau tidak aktif.');
            }
            if (Kelas::where('tahunajaran_idtahunajaran', $tahun->idtahunajaran)
                ->where('jenis', 'induk')
                ->where('guru_idguru', $guru->idguru)
                ->exists()) {
                throw new InvalidArgumentException('Guru wali sudah menjadi wali kelas induk pada tahun ajaran ini.');
            }
        }

        $tingkat = trim((string) ($values['tingkat'] ?? ''));
        if (! in_array($tingkat, ['10', '11', '12'], true)) {
            throw new InvalidArgumentException('Tingkat harus 10, 11, atau 12.');
        }

        return [
            'tahun_ajaran' => $tahun->idtahunajaran,
            'nama' => $nama,
            'tingkat' => $tingkat,
            'jenis' => $jenis,
            'nip_wali' => $guru?->idguru,
        ];
    }

    protected function simpan(array $values): array
    {
        return KelasService::buat($values);
    }
}
