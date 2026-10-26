<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Penempatan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class PenempatanService
{
    public static function tambah(Kelas $kelas, Siswa $siswa): array
    {
        $result = ['success' => false, 'penempatan' => null, 'errors' => []];

        // Validasi: siswa harus status 'aktif'
        if ($siswa->status !== 'aktif') {
            $result['errors']['siswa'] = 'Siswa tidak aktif, tidak dapat penempatan';

            return $result;
        }

        // Validasi: tahun_ajaran kelas harus valid
        $ta = TahunAjaran::find($kelas->tahun_ajaran_id);
        if (! $ta) {
            $result['errors']['tahun_ajaran'] = 'Tahun ajaran tidak valid';

            return $result;
        }

        // Aturan: satu kelas induk per tahun ajaran per siswa
        $existingInduk = Penempatan::where('siswa_id', $siswa->id)
            ->where('kelas_id', $kelas->id)
            ->where('jenis', 'induk')
            ->exists();

        if ($existingInduk) {
            $result['errors']['siswa'] = 'Siswa sudah memiliki penempatan induk di kelas ini';

            return $result;
        }

        // Aturan: untuk kelas induk, harus ada guru wali
        if ($kelas->jenis === 'induk') {
            $guruWali = $kelas->guru_wali;
            if (! $guruWali) {
                $result['errors']['kelas'] = 'Kelas induk harus memiliki guru wali';

                return $result;
            }
        }

        DB::transaction(function () use ($siswa, $kelas, &$result) {
            $penempatan = new Penempatan;
            $penempatan->siswa_id = $siswa->id;
            $penempatan->kelas_id = $kelas->id;
            $penempatan->jenis = $kelas->jenis;
            $penempatan->save();

            $result['penempatan'] = $penempatan;
            $result['success'] = true;
        });

        return $result;
    }
}
