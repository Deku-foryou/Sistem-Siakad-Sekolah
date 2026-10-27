<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Tahunajaran;
use Illuminate\Support\Facades\DB;

class KelasService
{
    public static function buat(array $data): array
    {
        $result = ['success' => false, 'kelas' => null, 'errors' => []];
        $nama = trim((string) ($data['nama'] ?? ''));
        $tahunAjaranNama = trim((string) ($data['tahun_ajaran'] ?? ''));
        $tingkat = trim((string) ($data['tingkat'] ?? ''));
        $jenis = trim((string) ($data['jenis'] ?? 'mapel_pilihan'));
        $nipWali = trim((string) ($data['nip_wali'] ?? ''));

        if ($nama === '') {
            $result['errors']['nama'] = 'Nama Kelas wajib diisi';
        }
        if (! in_array($tingkat, ['10', '11', '12'], true)) {
            $result['errors']['tingkat'] = 'Tingkat harus 10, 11, atau 12';
        }
        if (! in_array($jenis, ['induk', 'mapel_pilihan'], true)) {
            $result['errors']['jenis'] = 'Jenis Kelas harus Induk atau Mapel pilihan';
        }

        $tahunAjaran = $tahunAjaranNama === ''
            ? null
            : (ctype_digit($tahunAjaranNama)
                ? Tahunajaran::find((int) $tahunAjaranNama)
                : Tahunajaran::where('nama', $tahunAjaranNama)->first());
        if (! $tahunAjaran) {
            $result['errors']['tahun_ajaran'] = 'Tahun ajaran tidak ditemukan';
        } elseif (Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama, 'UTF-8')])
            ->exists()) {
            $result['errors']['nama'] = 'Kelas sudah terdaftar pada tahun ajaran ini';
        }

        $guru = null;
        if ($nipWali !== '' && $jenis !== 'induk') {
            $result['errors']['nip_wali'] = 'Wali hanya boleh diisi untuk kelas Induk';
        } elseif ($nipWali !== '') {
            $guru = Guru::where('nip', $nipWali)->first();
            if (! $guru && ctype_digit($nipWali)) {
                $guru = Guru::find((int) $nipWali);
            }
            if ($guru && $guru->status !== 'aktif') {
                $guru = null;
            }
            if (! $guru) {
                $result['errors']['nip_wali'] = 'Wali tidak ditemukan atau tidak aktif';
            } elseif ($tahunAjaran && Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->where('jenis', 'induk')
                ->where('guru_idguru', $guru->idguru)
                ->exists()) {
                $result['errors']['nip_wali'] = 'Guru sudah menjadi Wali kelas Induk pada Tahun Ajaran ini';
            }
        }

        if ($result['errors'] !== []) {
            return $result;
        }

        DB::transaction(function () use ($nama, $tahunAjaran, $tingkat, $jenis, $guru, &$result) {
            $kelas = new Kelas;
            $kelas->nama = $nama;
            $kelas->tingkat = $tingkat;
            $kelas->jenis = $jenis;
            $kelas->tahunajaran_idtahunajaran = $tahunAjaran->idtahunajaran;
            $kelas->guru_idguru = $guru?->idguru;
            $kelas->save();

            $result['kelas'] = $kelas;
            $result['success'] = true;
        });

        return $result;
    }
}
