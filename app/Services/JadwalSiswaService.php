<?php

namespace App\Services;

use App\Models\JadwalKelas;
use App\Models\Kelassiswa;
use App\Support\KonteksAkademik;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal data jadwal untuk Portal Siswa.
 *
 * Aturan:
 * - Hanya memakai jadwalkelas + jamhari (bukan jadwal legacy / publikasijadwal).
 * - Hanya penempatan kelassiswa berstatus aktif pada tahun ajaran aktif.
 * - Hanya jadwal pada semester aktif menurut KonteksAkademikService.
 *
 * Tidak menulis data apa pun.
 */
class JadwalSiswaService
{
    /**
     * Kelas aktif milik siswa pada tahun ajaran aktif.
     *
     * @return Collection<int, \App\Models\Kelas>
     */
    public function kelasAktif(int $siswaId, KonteksAkademik $konteks): Collection
    {
        if (! $konteks->valid() || ! $konteks->tahunAjaran) {
            return collect();
        }

        return Kelassiswa::query()
            ->with(['kelas.jurusan', 'kelas.wali'])
            ->where('siswa_idsiswa', $siswaId)
            ->where('status', 'aktif')
            ->whereHas('kelas', fn ($query) => $query->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran))
            ->get()
            ->pluck('kelas')
            ->filter()
            ->sortBy('nama')
            ->values();
    }

    /**
     * Jadwal kelas pada semester aktif, opsional dibatasi satu hari.
     *
     * @param  array<int, int>  $kelasIds
     * @return Collection<int, JadwalKelas>
     */
    public function jadwal(KonteksAkademik $konteks, array $kelasIds, ?int $hari = null): Collection
    {
        if (! $konteks->valid() || ! $konteks->semester || $kelasIds === []) {
            return collect();
        }

        return JadwalKelas::query()
            ->with(['semester', 'jamhari.kelas', 'mapel', 'guru'])
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->whereHas('jamhari', function ($query) use ($kelasIds, $hari) {
                $query->whereIn('kelas_id', $kelasIds);

                if ($hari !== null) {
                    $query->where('hari', $hari);
                }
            })
            ->get();
    }

    /**
     * Urutkan jadwal berdasarkan hari, jam ke-, lalu waktu mulai.
     *
     * @param  Collection<int, JadwalKelas>  $jadwal
     * @return Collection<int, JadwalKelas>
     */
    public function urutkan(Collection $jadwal): Collection
    {
        return $jadwal
            ->sortBy(fn ($item) => [
                (int) ($item->jamhari->hari ?? 0),
                (int) ($item->jamhari->jam_ke ?? 999),
                (string) ($item->jamhari->mulai ?? ''),
            ])
            ->values();
    }

    /**
     * Kelompokkan jadwal per hari (1 = Senin ... 7 = Minggu).
     *
     * @param  Collection<int, JadwalKelas>  $jadwal
     */
    public function perHari(Collection $jadwal): Collection
    {
        return $this->urutkan($jadwal)
            ->groupBy(fn ($item) => (int) $item->jamhari->hari)
            ->sortKeys();
    }
}
