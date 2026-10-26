<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class KelasService
{
    public static function buat(array $data): array
    {
        $result = ['success' => false, 'kelas' => null, 'errors' => []];

        // Validate required fields
        if (empty($data['nama'])) {
            $result['errors']['nama'] = 'Nama kelas wajib diisi';
        }
        if (empty($data['tingkat'])) {
            $result['errors']['tingkat'] = 'Tingkat wajib diisi';
        }

        if (! empty($result['errors'])) {
            return $result;
        }

        // Validate tahun_ajaran exists
        if (! empty($data['tahun_ajaran'])) {
            $ta = TahunAjaran::where('nama', $data['tahun_ajaran'])->first();
            if (! $ta) {
                $result['errors']['tahun_ajaran'] = 'Tahun ajaran tidak ditemukan';

                return $result;
            }
        }

        // Check for duplicate nama kelas + tahun_ajaran combination
        $nama = trim($data['nama']);
        $taId = $data['tahun_ajaran'] ?? null;

        $query = Mapel::where('nama', $nama)->whereNotNull('tahun_ajaran_id');
        if ($taId) {
            $query->whereHas('tahunAjaran', function ($q) use ($taId) {
                $q->where('id', $taId);
            });
        }

        if ($query->exists()) {
            $result['errors']['nama'] = 'Kelas dengan nama ini sudah terdaftar pada tahun ajaran yang sama';

            return $result;
        }

        // Check wali kelas: guru harus ada dan aktif, hanya satu guru wali satu kelas induk per tahun ajaran
        if (! empty($data['nip_wali']) && ! empty($data['jenis']) && $data['jenis'] === 'induk') {
            $guru = \App\Models\Guru::where('nip', $data['nip_wali'])->first();
            if (! $guru) {
                $result['errors']['nip_wali'] = 'Guru wali tidak ditemukan';
            } elseif (! $guru->status_aktif) {
                $result['errors']['nip_wali'] = 'Guru wali harus status aktif';
            }
        }

        DB::transaction(function () use ($nama, $taId, &$result) {
            $kelas = new Kelas;
            $kelas->nama = $nama;
            $kelas->tingkat = $data['tingkat'] ?? 'X';
            $kelas->jenis = $data['jenis'] ?? 'mapel_pilihan';
            $kelas->tahun_ajaran_id = $taId ?? null;

            if (! empty($data['nip_wali']) && ! empty($result['errors'] ?? [])) {
                // Only set wali if valid
                $guru = \App\Models\Guru::where('nip', $data['nip_wali'])->first();
                if ($guru && $guru->status_aktif) {
                    $kelas->guru_wali_id = $guru->id;
                }
            }

            $kelas->save();

            $result['kelas'] = $kelas;
            $result['success'] = true;
        });

        return $result;
    }
}
