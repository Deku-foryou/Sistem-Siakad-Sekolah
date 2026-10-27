<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Semester;
use App\Support\Hari;
use DomainException;
use Illuminate\Support\Facades\DB;

class PengampuService
{
    public static function simpan(?int $id, int $semesterId, int $kelasId, int $mapelId, int $guruId): Pengampu
    {
        return DB::transaction(function () use ($id, $semesterId, $kelasId, $mapelId, $guruId) {
            if (min($semesterId, $kelasId, $mapelId, $guruId) < 1) {
                throw new DomainException('Semester, kelas, mata pelajaran, dan guru wajib dipilih.');
            }
            $semester = Semester::query()->lockForUpdate()->find($semesterId);
            $kelas = Kelas::find($kelasId);
            $guru = Guru::find($guruId);
            if (! $semester || ! $kelas || ! $guru || ! Mapel::whereKey($mapelId)->exists()) {
                throw new DomainException('Semester, kelas, mata pelajaran, atau guru tidak ditemukan.');
            }

            if ((int) $kelas->tahunajaran_idtahunajaran !== (int) $semester->tahunajaran_idtahunajaran) {
                throw new DomainException('Kelas harus berasal dari tahun ajaran semester yang dipilih.');
            }
            if ($guru->status !== 'aktif') {
                throw new DomainException('Guru pengampu harus berstatus aktif.');
            }

            $row = $id ? Pengampu::query()->lockForUpdate()->find($id) : new Pengampu;
            if ($id !== null && (! $row || $id < 1)) {
                throw new DomainException('Pengampu tidak ditemukan.');
            }
            $row ??= new Pengampu;
            if ($row->exists && $row->jadwal()->exists()
                && ((int) $row->semester_idsemester !== $semesterId
                    || (int) $row->kelas_idkelas !== $kelasId
                    || (int) $row->mapel_idmapel !== $mapelId)) {
                throw new DomainException('Semester, kelas, atau mata pelajaran tidak dapat diubah setelah memiliki jadwal.');
            }

            $duplicate = Pengampu::where('semester_idsemester', $semesterId)
                ->where('kelas_idkelas', $kelasId)
                ->where('mapel_idmapel', $mapelId)
                ->when($id, fn ($q) => $q->where('idpengampu', '!=', $id))
                ->exists();
            if ($duplicate) {
                throw new DomainException('Pengampu untuk semester, kelas, dan mata pelajaran tersebut sudah ada.');
            }

            if ($row->exists && (int) $row->guru_idguru !== $guruId) {
                $conflict = DB::table('jadwal as jadwal_milik')
                    ->join('jadwal as jadwal_lain', function ($join) {
                        $join->on('jadwal_lain.hari', '=', 'jadwal_milik.hari')
                            ->on('jadwal_lain.jam_ke', '=', 'jadwal_milik.jam_ke');
                    })
                    ->join('pengampu as pengampu_lain', 'pengampu_lain.idpengampu', '=', 'jadwal_lain.pengampu_idpengampu')
                    ->join('kelas as kelas_lain', 'kelas_lain.idkelas', '=', 'pengampu_lain.kelas_idkelas')
                    ->where('jadwal_milik.pengampu_idpengampu', $row->idpengampu)
                    ->where('jadwal_lain.pengampu_idpengampu', '!=', $row->idpengampu)
                    ->where('pengampu_lain.semester_idsemester', $semesterId)
                    ->where('pengampu_lain.guru_idguru', $guruId)
                    ->select('jadwal_lain.hari', 'jadwal_lain.jam_ke', 'kelas_lain.nama as nama_kelas')
                    ->first();
                if ($conflict) {
                    throw new DomainException(
                        'Guru sudah memiliki jadwal pada '.Hari::nama((int) $conflict->hari)
                        .' jam ke-'.$conflict->jam_ke.' untuk kelas '.$conflict->nama_kelas.'.'
                    );
                }
            }

            $row->semester_idsemester = $semesterId;
            $row->kelas_idkelas = $kelasId;
            $row->mapel_idmapel = $mapelId;
            $row->guru_idguru = $guruId;
            $row->save();

            return $row;
        });
    }

    public static function hapus(int $id): void
    {
        DB::transaction(function () use ($id) {
            $current = $id > 0 ? Pengampu::find($id) : null;
            if (! $current) {
                throw new DomainException('Pengampu tidak ditemukan.');
            }
            Semester::query()->lockForUpdate()->findOrFail($current->semester_idsemester);
            $row = Pengampu::query()->lockForUpdate()->findOrFail($id);
            if ($row->jadwal()->exists()) {
                throw new DomainException('Pengampu tidak dapat dihapus karena masih memiliki jadwal.');
            }
            $row->delete();
        });
    }
}
