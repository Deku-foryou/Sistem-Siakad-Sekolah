<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\NilaiSiswa;
use App\Models\PenugasanMengajar;
use App\Models\Semester;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis penugasan mengajar.
 *
 * Penugasan mengajar adalah sumber otorisasi guru untuk menginput nilai.
 * Tidak membaca atau menulis data legacy `pengampu` maupun `jadwalkelas`.
 */
class PenugasanMengajarService
{
    public static function simpan(?int $id, int $semesterId, int $kelasId, int $mapelId, int $guruId): PenugasanMengajar
    {
        self::pastikanSuperadmin();

        return DB::transaction(function () use ($id, $semesterId, $kelasId, $mapelId, $guruId) {
            if (min($semesterId, $kelasId, $mapelId, $guruId) < 1) {
                throw new DomainException('Semester, kelas, mata pelajaran, dan guru wajib dipilih.');
            }

            $semester = Semester::query()->lockForUpdate()->find($semesterId);
            $kelas = Kelas::find($kelasId);
            $mapel = Mapel::find($mapelId);
            $guru = Guru::find($guruId);

            if (! $semester || ! $kelas || ! $mapel || ! $guru) {
                throw new DomainException('Semester, kelas, mata pelajaran, atau guru tidak ditemukan.');
            }

            if ((int) $kelas->tahunajaran_idtahunajaran !== (int) $semester->tahunajaran_idtahunajaran) {
                throw new DomainException('Kelas harus berasal dari tahun ajaran semester yang dipilih.');
            }

            if ($guru->status !== 'aktif') {
                throw new DomainException('Guru yang ditugaskan harus berstatus aktif.');
            }

            $row = $id !== null
                ? PenugasanMengajar::query()->lockForUpdate()->find($id)
                : new PenugasanMengajar;

            if ($id !== null && ! $row) {
                throw new DomainException('Penugasan mengajar tidak ditemukan.');
            }

            if ($row->exists
                && self::adaNilai($row->semester_idsemester, $row->kelas_idkelas, $row->mapel_idmapel)
                && ((int) $row->semester_idsemester !== $semesterId
                    || (int) $row->kelas_idkelas !== $kelasId
                    || (int) $row->mapel_idmapel !== $mapelId)) {
                throw new DomainException(
                    'Semester, kelas, atau mata pelajaran tidak dapat diubah karena penugasan ini sudah memiliki nilai.'
                );
            }

            $duplicate = PenugasanMengajar::query()
                ->where('semester_idsemester', $semesterId)
                ->where('kelas_idkelas', $kelasId)
                ->where('mapel_idmapel', $mapelId)
                ->when($id !== null, fn ($query) => $query->where('idpenugasan_mengajar', '!=', $id))
                ->exists();

            if ($duplicate) {
                throw new DomainException(
                    'Penugasan untuk semester, kelas, dan mata pelajaran tersebut sudah ada.'
                );
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
        self::pastikanSuperadmin();

        DB::transaction(function () use ($id) {
            $row = $id > 0
                ? PenugasanMengajar::query()->lockForUpdate()->find($id)
                : null;

            if (! $row) {
                throw new DomainException('Penugasan mengajar tidak ditemukan.');
            }

            if (self::adaNilai($row->semester_idsemester, $row->kelas_idkelas, $row->mapel_idmapel)) {
                throw new DomainException(
                    'Penugasan mengajar tidak dapat dihapus karena sudah memiliki nilai.'
                );
            }

            $row->delete();
        });
    }

    /**
     * Apakah kombinasi semester + kelas + mata pelajaran sudah memiliki nilai.
     */
    public static function adaNilai(int $semesterId, int $kelasId, int $mapelId): bool
    {
        return NilaiSiswa::query()
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->whereHas('komponenNilai', fn ($query) => $query->where('mapel_idmapel', $mapelId))
            ->exists();
    }

    private static function pastikanSuperadmin(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }
}
