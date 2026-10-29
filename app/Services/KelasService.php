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
        $tahunAjaranNama = trim((string) ($data['tahun_ajaran'] ?? $data['tahunajaran_idtahunajaran'] ?? ''));
        $tingkat = trim((string) ($data['tingkat'] ?? ''));
        $jenis = trim((string) ($data['jenis'] ?? 'mapel_pilihan'));
        $guruIdguru = (int) ($data['guru_idguru'] ?? 0);
        $jurusanId = trim((string) ($data['jurusan_id'] ?? ''));
        $rombelDiminta = (int) ($data['rombel'] ?? 0);
        $idkelasDikecualikan = $data['idkelas'] ?? null;

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

        if ($result['errors'] !== []) {
            return $result;
        }

        // Validate jurusan: WAJIB untuk induk, tidak boleh untuk mapel_pilihan
        if ($jenis === 'induk') {
            if ($jurusanId === '' || (int) $jurusanId === 0) {
                $result['errors']['jurusan_id'] = 'Jurusan wajib diisi untuk kelas Induk';
            } elseif (! Jurusan::where('idjurusan', $jurusanId)->where('aktif', true)->exists()) {
                $result['errors']['jurusan_id'] = 'Jurusan tidak ditemukan atau tidak aktif';
            }
        } else {
            if ($jurusanId !== '' && (int) $jurusanId !== 0) {
                $result['errors']['jurusan_id'] = 'Jurusan hanya untuk kelas Induk';
            }
        }

        DB::transaction(function () use ($tahunAjaran, $tingkat, $jenis, $jurusanId, $guruIdguru, $rombelDiminta, $idkelasDikecualikan, &$result) {
            $tahunAjaran = Tahunajaran::lockForUpdate()->find($tahunAjaran->idtahunajaran);

            $guru = null;
            if ($guruIdguru > 0 && $jenis !== 'induk') {
                $result['errors']['guru_idguru'] = 'Wali hanya boleh diisi untuk kelas Induk';
            } elseif ($guruIdguru > 0) {
                $guru = Guru::where('idguru', $guruIdguru)->first();
                if ($guru && $guru->status !== 'aktif') {
                    $result['errors']['guru_idguru'] = 'Guru tidak ditemukan atau tidak aktif';
                    $guru = null;
                }
                if (! $guru) {
                    $result['errors']['guru_idguru'] = 'Guru tidak ditemukan atau tidak aktif';
                } elseif ($tahunAjaran && $jenis === 'induk' && Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                    ->where('jenis', 'induk')
                    ->where('guru_idguru', $guru->idguru)
                    ->where('idkelas', '!=', $idkelasDikecualikan)
                    ->exists()) {
                    $result['errors']['guru_idguru'] = 'Guru sudah menjadi Wali kelas Induk pada Tahun Ajaran ini';
                }
            }

            if ($result['errors'] !== []) {
                return;
            }

            $query = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->where('tingkat', $tingkat)
                ->where('jenis', $jenis)
                ->whereNotNull('rombel');

            if ($jurusanId !== '' && (int) $jurusanId !== 0) {
                $query->where('jurusan_id', (int) $jurusanId);
            } else {
                $query->whereNull('jurusan_id');
            }

            $takenRombels = array_map('intval', $query->pluck('rombel')->toArray());

            if ($rombelDiminta > 0) {
                if (in_array($rombelDiminta, $takenRombels, true)) {
                    $result['errors']['rombel'] = 'Nomor rombel '.$rombelDiminta.' sudah dipakai pada tahun ajaran, tingkat, dan jurusan ini';

                    return;
                }
                $rombel = $rombelDiminta;
            } else {
                $rombel = 1;
                while (in_array($rombel, $takenRombels, true)) {
                    $rombel++;
                }
            }

            $generatedName = self::generateClassName($tingkat, $jenis, $jurusanId, $rombel, $tahunAjaran->idtahunajaran);

            // Check duplikat nama
            $kelasExists = Kelas::where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                ->where('tingkat', $tingkat)
                ->where('jenis', $jenis)
                ->where('rombel', $rombel);

            if ($jurusanId !== '' && (int) $jurusanId !== 0) {
                $kelasExists->where('jurusan_id', (int) $jurusanId);
            } else {
                $kelasExists->whereNull('jurusan_id');
            }

            if ($kelasExists->exists()) {
                $result['errors']['nama'] = "Kelas $generatedName sudah ada pada tahun ajaran ini";

                return;
            }

            $kelas = new Kelas;
            $kelas->tahunajaran_idtahunajaran = $tahunAjaran->idtahunajaran;
            $kelas->nama = $generatedName;
            $kelas->tingkat = $tingkat;
            $kelas->jenis = $jenis;
            $kelas->rombel = $rombel;
            $kelas->guru_idguru = $guru ? $guru->idguru : null;
            if ($jurusanId !== '' && (int) $jurusanId !== 0) {
                $kelas->jurusan_id = (int) $jurusanId;
            }
            $kelas->save();

            $result['kelas'] = $kelas;
            $result['success'] = true;
        });

        return $result;
    }

    public static function bentukNama(string $tingkat, $jurusan, int $rombel): string
    {
        $tingkatLabel = match ($tingkat) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            default => $tingkat,
        };

        $jurusanName = '';
        if ($jurusan) {
            $jurusanName = ' ' . strtoupper($jurusan->nama ?? $jurusan->kode ?? '');
        }

        return trim($tingkatLabel . $jurusanName . ' ' . $rombel);
    }

    public static function generateClassName(string $tingkat, string $jenis, string $jurusanId, int $rombel, int $tahunAjaranId): string
    {
        $tingkatLabel = match ($tingkat) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            default => $tingkat,
        };

        $jurusanName = '';
        if ($jurusanId !== '' && (int) $jurusanId !== 0) {
            $jurusan = Jurusan::find((int) $jurusanId);
            if ($jurusan) {
                $jurusanName = ' ' . strtoupper($jurusan->nama ?? $jurusan->kode ?? '');
            }
        }

        return trim($tingkatLabel . $jurusanName . ' ' . $rombel);
    }
}
