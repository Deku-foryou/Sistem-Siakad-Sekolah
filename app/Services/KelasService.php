<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Tahunajaran;
use Illuminate\Support\Facades\DB;

class KelasService
{
    public static function buat(array $data): array
    {
        $result = ['success' => false, 'kelas' => null, 'errors' => []];
        $tahunAjaranNama = trim((string) ($data['tahun_ajaran'] ?? ''));
        $tingkat = trim((string) ($data['tingkat'] ?? ''));
        $jenis = trim((string) ($data['jenis'] ?? 'mapel_pilihan'));
        $nipWali = trim((string) ($data['nip_wali'] ?? ''));
        $jurusanId = trim((string) ($data['jurusan_id'] ?? ''));

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
        }

        // Validate jurusan if required based on jenis
        if ($jenis === 'induk' && $jurusanId !== '') {
            if (! Jurusan::where('idjurusan', $jurusanId)->where('aktif', true)->exists()) {
                $result['errors']['jurusan_id'] = 'Jurusan tidak ditemukan atau tidak aktif';
            }
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
            } elseif ($tahunAjaran && $jenis === 'induk' && Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->where('jenis', 'induk')
                ->where('guru_idguru', $guru->idguru)
                ->where('idkelas', '!=', $data['idkelas'] ?? null)
                ->exists()) {
                $result['errors']['nip_wali'] = 'Guru sudah menjadi Wali kelas Induk pada Tahun Ajaran ini';
            }
        }

        if ($result['errors'] !== []) {
            return $result;
        }

        DB::transaction(function () use ($tahunAjaran, $tingkat, $jenis, $jurusanId, &$result) {
            // Pessimistic locking: lock the tahun_ajaran record inside the transaction
            // to prevent race condition on rombel selection
            $tahunAjaran->lockForUpdate();

            // Double-check available rombel inside locked transaction
            // Filter berdasarkan jurusan: jika jurusan_id diberikan, ambil hanya kelas dengan jurusan yang sama
            // jika jurusan_id NULL, ambil kelas tanpa jurusan
            $query = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->where('tingkat', $tingkat)
                ->where('jenis', $jenis)
                ->whereNotNull('rombel');

            if ($jurusanId !== '' && (int) $jurusanId !== 0) {
                $query->where('jurusan_id', (int) $jurusanId);
            } else {
                $query->whereNull('jurusan_id');
            }

            $takenRombels = $query->pluck('rombel')->toArray();

            // Find the smallest positive integer not in takenRombels
            $rombel = 1;
            while (in_array($rombel, $takenRombels)) {
                $rombel++;
            }

            $generatedName = self::generateClassName($tingkat, $jenis, $jurusanId, $rombel, $tahunAjaran->idtahunajaran);

            $kelas = new Kelas;
            $kelas->nama = $generatedName;
            $kelas->tingkat = $tingkat;
            $kelas->jenis = $jenis;
            $kelas->tahunajaran_idtahunajaran = $tahunAjaran->idtahunajaran;
            $kelas->jurusan_id = $jurusanId !== '' ? (int) $jurusanId : null;
            $kelas->rombel = $rombel;
            $kelas->save();

            $result['kelas'] = $kelas;
            $result['success'] = true;
        });

        return $result;
    }

    /**
     * Generate class name format: Tingkat + Jurusan + Rombel
     * Example: X IPS 1, XI MIPA 1, XII TKJ 3
     */
    private static function generateClassName(string $tingkat, string $jenis, $jurusanId, int $rombel, int $tahunAjaranId): string
    {
        // Map tingkat to label
        $tingkatLabels = [
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
        ];
        $tingkatLabel = $tingkatLabels[$tingkat] ?? 'X';

        // Get jurusan name if jurusan provided
        $jurusanName = '';
        if ($jurusanId !== null) {
            $jurusan = \App\Models\Jurusan::find($jurusanId);
            if ($jurusan) {
                $jurusanName = ' '.$jurusan->nama;
            }
        }

        return $tingkatLabel.$jurusanName.' Rombel '.$rombel;
    }
}
