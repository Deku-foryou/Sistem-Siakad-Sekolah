<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Models\Guru;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\User;
use App\Support\KonteksAkademik;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Agregasi data akademik guru untuk kebutuhan API mobile (read-only).
 *
 * Sumber kanonis: jadwalkelas + jamhari (bukan jadwal legacy) dan
 * penugasan_mengajar untuk otorisasi nilai. Identitas guru selalu dari
 * user yang terautentikasi; tidak ada parameter guru dari request.
 *
 * Tidak menulis data apa pun.
 */
class GuruApiService
{
    /**
     * Guru aktif milik user; lempar DomainException bila akun tidak terhubung
     * atau guru nonaktif (middleware api.akun seharusnya sudah menolak lebih dulu).
     */
    public static function pastikanGuru(User $user): Guru
    {
        if ($user->level !== 'guru') {
            throw new DomainException('Endpoint ini khusus untuk guru.');
        }

        $guru = $user->guru()->where('status', 'aktif')->first();

        if (! $guru) {
            throw new DomainException('Akun guru ini belum terhubung ke data guru atau nonaktif. Hubungi administrator.');
        }

        return $guru;
    }

    /**
     * ID kelas yang diampu guru pada semester aktif (jadwalkelas, jam pelajaran).
     *
     * @return array<int, int>
     */
    public static function kelasIdsDiajar(Guru $guru, KonteksAkademik $konteks): array
    {
        if (! $konteks->valid() || ! $konteks->semester) {
            return [];
        }

        return JadwalKelas::query()
            ->join('jamhari as jh', 'jh.idjamhari', '=', 'jadwalkelas.jamhari_idjamhari')
            ->where('jadwalkelas.semester_idsemester', $konteks->semester->idsemester)
            ->where('jadwalkelas.guru_idguru', $guru->idguru)
            ->where('jh.jenis', 'pelajaran')
            ->whereNotNull('jh.kelas_id')
            ->distinct()
            ->pluck('jh.kelas_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * ID kelas yang diwali (kelas.guru_idguru) pada tahun ajaran aktif.
     *
     * @return array<int, int>
     */
    public static function kelasIdsWali(Guru $guru, KonteksAkademik $konteks): array
    {
        if (! $konteks->valid() || ! $konteks->tahunAjaran) {
            return [];
        }

        return Kelas::query()
            ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
            ->where('guru_idguru', $guru->idguru)
            ->pluck('idkelas')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Union kelas yang diajar dan yang diwali (untuk akses baca).
     *
     * @return array<int, int>
     */
    public static function kelasIdsAkses(Guru $guru, KonteksAkademik $konteks): array
    {
        return array_values(array_unique(array_merge(
            self::kelasIdsDiajar($guru, $konteks),
            self::kelasIdsWali($guru, $konteks)
        )));
    }

    /**
     * Apakah guru berwenang MELIHAT kelas ini (mengajar semester aktif atau wali TA aktif).
     */
    public static function bolehLihatKelas(Guru $guru, int $kelasId, KonteksAkademik $konteks): bool
    {
        return in_array($kelasId, self::kelasIdsAkses($guru, $konteks), true);
    }

    /**
     * Daftar kelas yang boleh dilihat guru: id => sumber (['mengajar'] / ['wali'] / keduanya).
     *
     * @return array<int, array<int, string>>
     */
    public static function sumberKelas(Guru $guru, KonteksAkademik $konteks): array
    {
        $sumber = [];
        foreach (self::kelasIdsDiajar($guru, $konteks) as $id) {
            $sumber[$id][] = 'mengajar';
        }
        foreach (self::kelasIdsWali($guru, $konteks) as $id) {
            $sumber[$id][] = 'wali';
        }

        return $sumber;
    }

    /**
     * Ambil kelas + relasi yang dibutuhkan API; null bila guru tidak berwenang.
     */
    public static function ambilKelasBerwenang(Guru $guru, int $kelasId, KonteksAkademik $konteks): ?Kelas
    {
        if ($kelasId < 1 || ! self::bolehLihatKelas($guru, $kelasId, $konteks)) {
            return null;
        }

        return Kelas::query()
            ->with(['tahunajaran', 'jurusan', 'wali'])
            ->withCount(['kelassiswa as jumlah_siswa' => fn ($query) => $query->where('status', 'aktif')])
            ->find($kelasId);
    }

    /**
     * Jadwal (jadwalkelas) milik guru pada semester aktif.
     *
     * @return Collection<int, JadwalKelas>
     */
    public static function jadwalGuru(Guru $guru, KonteksAkademik $konteks, ?int $hari = null)
    {
        if (! $konteks->valid() || ! $konteks->semester) {
            return collect();
        }

        return JadwalKelas::query()
            ->with(['semester', 'jamhari.kelas', 'mapel', 'guru'])
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->where('guru_idguru', $guru->idguru)
            ->whereHas('jamhari', function ($query) use ($hari) {
                $query->where('jenis', 'pelajaran');
                if ($hari !== null) {
                    $query->where('hari', $hari);
                }
            })
            ->get();
    }

    /**
     * Payload konteks akademik (identik dengan StudentController).
     */
    public static function konteksPayload(KonteksAkademik $konteks): array
    {
        return [
            'valid' => $konteks->valid(),
            'peringatan' => $konteks->pesanPeringatan(),
            'tahun_ajaran' => $konteks->tahunAjaran ? ['id' => $konteks->tahunAjaran->idtahunajaran, 'nama' => $konteks->tahunAjaran->nama] : null,
            'semester' => $konteks->semester ? ['id' => $konteks->semester->idsemester, 'nama' => $konteks->semester->nama] : null,
        ];
    }

    /**
     * Jumlah siswa aktif pada kelas.
     */
    public static function jumlahSiswaAktif(int $kelasId): int
    {
        return (int) \App\Models\Kelassiswa::query()
            ->where('kelas_idkelas', $kelasId)
            ->where('status', 'aktif')
            ->count();
    }

    /**
     * Apakah record absensi harian berada dalam kewenangan baca guru
     * (kelas yang diajar/wali pada konteks aktif).
     */
    public static function bolehLihatAbsensiKelas(Guru $guru, int $kelasId, KonteksAkademik $konteks): bool
    {
        return self::bolehLihatKelas($guru, $kelasId, $konteks);
    }

    /**
     * Daftar absensi harian pada kelas-kelas yang boleh dilihat guru.
     * Dipakai untuk riwayat; selalu ter-scope.
     */
    public static function queryAbsensiHarian(Guru $guru, KonteksAkademik $konteks)
    {
        $kelasIds = self::kelasIdsAkses($guru, $konteks);

        return AbsensiHarian::query()
            ->with(['kelas:idkelas,nama', 'siswa:idsiswa,nama'])
            ->whereIn('kelas_idkelas', $kelasIds === [] ? [0] : $kelasIds);
    }
}
