<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Jampelajaran;
use App\Models\Pengampu;
use App\Models\Polajam;
use App\Models\Polajamhari;
use App\Models\Semester;
use App\Support\Hari;
use DomainException;
use Illuminate\Support\Facades\DB;

class JadwalService
{
    public static function simpan(int $pengampuId, int $hari, int $jamKe, ?int $replaceJadwalId = null): Jadwal
    {
        return DB::transaction(function () use ($pengampuId, $hari, $jamKe, $replaceJadwalId) {
            if (! array_key_exists($hari, Hari::NAMA)) {
                throw new DomainException('Hari harus Senin sampai Minggu.');
            }
            if ($pengampuId < 1 || $jamKe < 1) {
                throw new DomainException('Pilih pengampu dan nomor jam yang valid.');
            }

            $pengampu = Pengampu::find($pengampuId);
            if (! $pengampu) {
                throw new DomainException('Pengampu tidak ditemukan.');
            }
            $semester = Semester::query()->lockForUpdate()->find($pengampu->semester_idsemester);
            if (! $semester) {
                throw new DomainException('Semester pengampu tidak ditemukan.');
            }

            $polaId = Polajamhari::where('hari', $hari)->value('polajam_idpolajam');
            if (! $polaId) {
                throw new DomainException(Hari::nama($hari).' belum dipetakan ke pola jam.');
            }
            Polajam::query()->lockForUpdate()->findOrFail($polaId);
            $polaHari = Polajamhari::where('hari', $hari)
                ->where('polajam_idpolajam', $polaId)
                ->lockForUpdate()
                ->first();
            if (! $polaHari) {
                throw new DomainException('Pemetaan hari berubah. Muat ulang jadwal.');
            }
            $jam = Jampelajaran::where('polajam_idpolajam', $polaHari->polajam_idpolajam)
                ->where('jenis', 'pelajaran')
                ->where('jam_ke', $jamKe)
                ->lockForUpdate()
                ->first();
            if (! $jam) {
                throw new DomainException('Jam ke-'.$jamKe.' tidak tersedia pada pola hari '.Hari::nama($hari).'.');
            }

            $relevan = Jadwal::query()
                ->with('pengampu')
                ->where('hari', $hari)
                ->where('jam_ke', $jamKe)
                ->whereHas('pengampu', fn ($q) => $q->where('semester_idsemester', $semester->idsemester))
                ->lockForUpdate()
                ->get();

            $kelasId = (int) $pengampu->kelas_idkelas;
            $current = $relevan->first(fn (Jadwal $row) => (int) $row->pengampu->kelas_idkelas === $kelasId);
            if ($current && (int) $current->pengampu_idpengampu !== $pengampuId
                && (int) $current->idjadwal !== (int) $replaceJadwalId) {
                $current->loadMissing('pengampu.mapel', 'pengampu.guru');
                throw new DomainException('Kelas bentrok dengan '.$current->pengampu->mapel->nama.' oleh '.$current->pengampu->guru->nama.'.');
            }
            if ($replaceJadwalId !== null && (! $current || (int) $current->idjadwal !== $replaceJadwalId)) {
                throw new DomainException('Jadwal yang akan diganti sudah berubah. Muat ulang grid.');
            }

            $teacherConflict = $relevan->first(fn (Jadwal $row) => (int) $row->pengampu->guru_idguru === (int) $pengampu->guru_idguru
                && (! $current || (int) $row->idjadwal !== (int) $current->idjadwal)
            );
            if ($teacherConflict) {
                $teacherConflict->loadMissing('pengampu.kelas', 'pengampu.mapel');
                throw new DomainException('Guru bentrok dengan kelas '.$teacherConflict->pengampu->kelas->nama.' ('.$teacherConflict->pengampu->mapel->nama.').');
            }

            $studentConflicts = self::siswaBentrok(
                (int) $semester->tahunajaran_idtahunajaran,
                (int) $semester->idsemester,
                $kelasId,
                $hari,
                $jamKe
            );
            if ($studentConflicts['count'] > 0) {
                $names = implode(', ', array_slice($studentConflicts['names'], 0, 5));
                $suffix = $studentConflicts['count'] > 5 ? ', dan lainnya' : '';
                throw new DomainException(
                    'Siswa bentrok: '.$studentConflicts['count'].' siswa ('.$names.$suffix.').'
                );
            }

            if ($current) {
                $current->pengampu_idpengampu = $pengampu->idpengampu;
                $current->save();

                return $current;
            }

            return Jadwal::create([
                'pengampu_idpengampu' => $pengampu->idpengampu,
                'hari' => $hari,
                'jam_ke' => $jamKe,
            ]);
        });
    }

    public static function simpanDenganMapel(
        int $semesterId,
        int $kelasId,
        int $mapelId,
        int $guruId,
        int $hari,
        int $jamKe,
        ?int $idjadwalPengganti = null
    ): Jadwal {
        return DB::transaction(function () use (
            $semesterId,
            $kelasId,
            $mapelId,
            $guruId,
            $hari,
            $jamKe,
            $idjadwalPengganti
        ) {
            $pengampu = Pengampu::with('guru')
                ->where('semester_idsemester', $semesterId)
                ->where('kelas_idkelas', $kelasId)
                ->where('mapel_idmapel', $mapelId)
                ->first();

            if ($pengampu && (int) $pengampu->guru_idguru !== $guruId) {
                throw new DomainException(
                    'Mapel ini di kelas ini sudah diampu oleh '.$pengampu->guru->nama
                    .'. Ubah di menu Pengampu atau pilih guru yang sama.'
                );
            }

            if (! $pengampu) {
                $pengampu = PengampuService::simpan(
                    null,
                    $semesterId,
                    $kelasId,
                    $mapelId,
                    $guruId
                );
            }

            return self::simpan(
                (int) $pengampu->idpengampu,
                $hari,
                $jamKe,
                $idjadwalPengganti
            );
        });
    }

    public static function hapus(int $jadwalId): void
    {
        DB::transaction(function () use ($jadwalId) {
            if ($jadwalId < 1) {
                throw new DomainException('Jadwal tidak ditemukan.');
            }
            $current = Jadwal::find($jadwalId);
            if (! $current) {
                throw new DomainException('Jadwal tidak ditemukan.');
            }
            $pengampu = $current->pengampu;
            Semester::query()->lockForUpdate()->findOrFail($pengampu->semester_idsemester);
            $jadwal = Jadwal::query()->lockForUpdate()->find($jadwalId);
            if (! $jadwal) {
                throw new DomainException('Jadwal tidak ditemukan.');
            }
            $jadwal->delete();
        });
    }

    private static function siswaBentrok(int $tahunajaranId, int $semesterId, int $kelasId, int $hari, int $jamKe): array
    {
        $siswa = DB::table('kelassiswa as anggota_aktif')
            ->join('kelassiswa as anggota_lain', 'anggota_lain.siswa_idsiswa', '=', 'anggota_aktif.siswa_idsiswa')
            ->join('kelas as kelas_lain', 'kelas_lain.idkelas', '=', 'anggota_lain.kelas_idkelas')
            ->join('pengampu as pengampu_lain', 'pengampu_lain.kelas_idkelas', '=', 'kelas_lain.idkelas')
            ->join('jadwal as jadwal_lain', 'jadwal_lain.pengampu_idpengampu', '=', 'pengampu_lain.idpengampu')
            ->join('siswa', 'siswa.idsiswa', '=', 'anggota_aktif.siswa_idsiswa')
            ->where('anggota_aktif.kelas_idkelas', $kelasId)
            ->where('kelas_lain.tahunajaran_idtahunajaran', $tahunajaranId)
            ->where('kelas_lain.idkelas', '!=', $kelasId)
            ->where('pengampu_lain.semester_idsemester', $semesterId)
            ->where('jadwal_lain.hari', $hari)
            ->where('jadwal_lain.jam_ke', $jamKe)
            ->select('siswa.idsiswa', 'siswa.nama')
            ->distinct()
            ->orderBy('siswa.nama')
            ->get()
            ->pluck('nama');

        return [
            'count' => $siswa->count(),
            'names' => $siswa->all(),
        ];
    }
}
