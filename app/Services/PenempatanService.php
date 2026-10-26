<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use Illuminate\Support\Facades\DB;

class PenempatanService
{
    public static function tambah(Kelas $kelas, Siswa $siswa): array
    {
        $result = ['success' => false, 'penempatan' => null, 'errors' => []];

        if ($siswa->status !== 'aktif') {
            $result['errors']['siswa'] = 'Siswa tidak aktif, tidak dapat penempatan';

            return $result;
        }

        $tahunAjaran = Tahunajaran::find($kelas->tahunajaran_idtahunajaran);
        if (! $tahunAjaran) {
            $result['errors']['tahun_ajaran'] = 'Tahun ajaran tidak valid';

            return $result;
        }

        if (Kelassiswa::where('siswa_idsiswa', $siswa->idsiswa)
            ->where('kelas_idkelas', $kelas->idkelas)
            ->exists()) {
            $result['errors']['siswa'] = 'Penempatan sudah terdaftar';

            return $result;
        }

        if ($kelas->jenis === 'induk') {
            $hasInduk = Kelassiswa::where('siswa_idsiswa', $siswa->idsiswa)
                ->whereHas('kelas', function ($query) use ($tahunAjaran) {
                    $query->where('tahunajaran_idtahunajaran', $tahunAjaran->idtahunajaran)
                        ->where('jenis', 'induk');
                })
                ->exists();
            if ($hasInduk) {
                $result['errors']['siswa'] = 'Siswa sudah memiliki kelas induk pada tahun ajaran ini';

                return $result;
            }
            if (! $kelas->guru_idguru || ! $kelas->wali()->where('status', 'aktif')->exists()) {
                $result['errors']['kelas'] = 'Kelas induk harus memiliki guru wali aktif';

                return $result;
            }
        }

        DB::transaction(function () use ($siswa, $kelas, &$result) {
            $penempatan = new Kelassiswa;
            $penempatan->siswa_idsiswa = $siswa->idsiswa;
            $penempatan->kelas_idkelas = $kelas->idkelas;
            $penempatan->save();

            $result['penempatan'] = $penempatan;
            $result['success'] = true;
        });

        return $result;
    }
}
