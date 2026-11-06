<?php

namespace App\Services;

use App\Models\KktpMapel;
use App\Models\Mapel;
use App\Models\NilaiAkhir;
use App\Models\Semester;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis KKTP (Kriteria Ketercapaian Tujuan Pembelajaran) per mapel.
 */
class KktpMapelService
{
    public static function simpan(
        ?int $id,
        int $semesterId,
        int $mapelId,
        mixed $nilaiMinimal,
        ?string $deskripsi
    ): KktpMapel {
        self::pastikanSuperadmin();

        return DB::transaction(function () use ($id, $semesterId, $mapelId, $nilaiMinimal, $deskripsi) {
            if ($semesterId < 1 || $mapelId < 1) {
                throw new DomainException('Semester dan mata pelajaran wajib dipilih.');
            }

            $semester = Semester::query()->lockForUpdate()->find($semesterId);
            if (! $semester) {
                throw new DomainException('Semester tidak ditemukan.');
            }

            if (! Mapel::find($mapelId)) {
                throw new DomainException('Mata pelajaran tidak ditemukan.');
            }

            if (! is_numeric($nilaiMinimal)) {
                throw new DomainException('Nilai minimal KKTP wajib berupa angka.');
            }

            $nilai = (float) $nilaiMinimal;
            if ($nilai < 0 || $nilai > 100) {
                throw new DomainException('Nilai minimal KKTP harus berada pada rentang 0 sampai 100.');
            }

            if (abs($nilai - round($nilai, 2)) > 0.00001) {
                throw new DomainException('Nilai minimal KKTP maksimal dua angka desimal.');
            }

            $nilai = round($nilai, 2);

            $row = $id !== null
                ? KktpMapel::query()->lockForUpdate()->find($id)
                : new KktpMapel;

            if ($id !== null && ! $row) {
                throw new DomainException('KKTP tidak ditemukan.');
            }

            // KKTP yang sudah menjadi snapshot nilai akhir tidak boleh diubah
            // melalui formulir biasa; koreksi berjalan lewat Koreksi Nilai
            // agar nilai lama/baru, alasan, dan pelaku tercatat di log.
            if ($id !== null && abs((float) $row->nilai_minimal - $nilai) > 0.00001
                && NilaiAkhir::query()
                    ->where('semester_idsemester', $row->semester_idsemester)
                    ->where('mapel_idmapel', $row->mapel_idmapel)
                    ->exists()) {
                throw new DomainException('KKTP ini sudah difinalisasi. Gunakan menu Koreksi Nilai untuk mengubahnya agar tercatat di log perubahan.');
            }

            $duplicate = KktpMapel::query()
                ->where('semester_idsemester', $semesterId)
                ->where('mapel_idmapel', $mapelId)
                ->when($id !== null, fn ($query) => $query->where('idkktp_mapel', '!=', $id))
                ->exists();

            if ($duplicate) {
                throw new DomainException('KKTP untuk semester dan mata pelajaran tersebut sudah ada.');
            }

            $deskripsi = $deskripsi !== null ? trim($deskripsi) : null;

            $row->semester_idsemester = $semesterId;
            $row->mapel_idmapel = $mapelId;
            $row->nilai_minimal = $nilai;
            $row->deskripsi = $deskripsi === '' ? null : $deskripsi;
            $row->save();

            return $row;
        });
    }

    public static function hapus(int $id): void
    {
        self::pastikanSuperadmin();

        DB::transaction(function () use ($id) {
            $row = $id > 0
                ? KktpMapel::query()->lockForUpdate()->find($id)
                : null;

            if (! $row) {
                throw new DomainException('KKTP tidak ditemukan.');
            }

            if (NilaiAkhir::query()
                ->where('semester_idsemester', $row->semester_idsemester)
                ->where('mapel_idmapel', $row->mapel_idmapel)
                ->exists()) {
                throw new DomainException('KKTP ini sudah difinalisasi dan tidak dapat dihapus. Gunakan menu Koreksi Nilai bila perlu perubahan.');
            }

            $row->delete();
        });
    }

    private static function pastikanSuperadmin(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }
}
