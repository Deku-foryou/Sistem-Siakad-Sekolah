<?php

namespace App\Services\Import;

use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Services\PenempatanService;
use InvalidArgumentException;

class ImportPenempatan extends ImportBase
{
    public function jenis(): string
    {
        return 'penempatan';
    }

    public function kolom(): array
    {
        return ['tahun_ajaran', 'nama_kelas', 'nisn'];
    }

    public function kunciDuplikat(array $values): ?string
    {
        if (trim((string) ($values['tahun_ajaran'] ?? '')) === '' || trim((string) ($values['nama_kelas'] ?? '')) === '' || trim((string) ($values['nisn'] ?? '')) === '') {
            return null;
        }

        $nisn = trim((string) $values['nisn']);
        if (ctype_digit($nisn) && strlen($nisn) < 10) {
            $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
        }

        return $this->key([
            'tahun_ajaran' => $values['tahun_ajaran'],
            'nama_kelas' => $values['nama_kelas'],
            'nisn' => $nisn,
        ], ['tahun_ajaran', 'nama_kelas', 'nisn']);
    }

    protected function normalisasi(array $values): array
    {
        $tahunAjaran = $this->wajibTeks($values, 'tahun_ajaran');
        $tahun = Tahunajaran::where('nama', $tahunAjaran)->first();
        if (! $tahun) {
            throw new InvalidArgumentException('Tahun ajaran tidak ditemukan.');
        }

        $namaKelas = $this->wajibTeks($values, 'nama_kelas');
        $kelas = Kelas::where('tahunajaran_idtahunajaran', $tahun->idtahunajaran)
            ->where('nama', $namaKelas)
            ->first();
        if (! $kelas) {
            throw new InvalidArgumentException('Kelas tidak ditemukan pada tahun ajaran tersebut.');
        }

        $nisn = $this->normalisasiNisn($values['nisn'] ?? null);
        $siswa = Siswa::where('nisn', $nisn)->where('status', 'aktif')->first();
        if (! $siswa) {
            throw new InvalidArgumentException('Siswa tidak ditemukan atau tidak aktif.');
        }
        if (Kelassiswa::where('kelas_idkelas', $kelas->idkelas)->where('siswa_idsiswa', $siswa->idsiswa)->exists()) {
            throw new InvalidArgumentException('Penempatan sudah terdaftar.');
        }

        return ['kelas_id' => $kelas->idkelas, 'siswa_id' => $siswa->idsiswa];
    }

    protected function simpan(array $values): array
    {
        $kelas = Kelas::findOrFail($values['kelas_id']);
        $siswa = Siswa::findOrFail($values['siswa_id']);

        return PenempatanService::tambah($kelas, $siswa);
    }
}
