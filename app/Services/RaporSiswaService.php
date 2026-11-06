<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\CatatanRapor;
use App\Models\FinalisasiRapor;
use App\Models\Kelas;
use App\Models\NilaiAkhir;
use App\Models\Siswa;
use App\Models\User;
use App\Support\KonteksAkademik;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Portal rapor siswa: daftar rapor final, detail, dan data PDF.
 *
 * Keputusan desain (lihat LAPORAN_TAHAP_2B5_8.md):
 * - Identitas siswa selalu dari `User::siswa()` (akun login). Tidak ada
 *   parameter siswa dari frontend; manipulasi ID tidak relevan.
 * - Daftar: semester+kelas yang memiliki `nilai_akhir` untuk siswa ini
 *   DAN `finalisasi_rapor.status = 'final'`. Nilai draft tidak pernah
 *   ditampilkan; rapor belum final tidak ada di daftar.
 * - Nilai akademik selalu dari `nilai_akhir` (snapshot 2B.5.6). Keterangan
 *   dari snapshot KKTP. Tidak ada perhitungan ulang di seluruh service ini.
 * - Rekap kehadiran memakai aturan gabungan 2B.5.7 (harian utama, perjam
 *   pengisi, prioritas alpha > izin > sakit > hadir) untuk semester+kelas
 *   rapor yang dipilih (termasuk semester lama yang sudah final).
 * - PDF memakai template `pdf/rapor` yang sama dengan wali kelas; data
 *   dibangun di sini (bukan RaporWaliKelasService yang memakai semester aktif).
 */
class RaporSiswaService
{
    public const PRIORITAS_STATUS = ['alpha', 'izin', 'sakit', 'hadir'];

    /**
     * Siswa dari akun login. 403 bila bukan level siswa; DomainException
     * bila akun siswa belum terhubung ke data siswa.
     */
    public static function pastikanSiswa(User $user): Siswa
    {
        abort_unless($user->level === 'siswa', 403);

        $siswa = $user->siswa()->first();

        if (! $siswa) {
            throw new DomainException('Akun ini belum terhubung ke data siswa. Hubungi administrator.');
        }

        return $siswa;
    }

    /**
     * Daftar rapor final milik siswa login.
     *
     * @return Collection<int, array{semester: \App\Models\Semester, kelas: Kelas, finalisasi: FinalisasiRapor}>
     */
    public static function daftarRapor(User $user): Collection
    {
        $siswa = self::pastikanSiswa($user);

        $kunci = NilaiAkhir::query()
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->select('semester_idsemester', 'kelas_idkelas')
            ->distinct()
            ->get();

        $hasil = collect();

        foreach ($kunci as $baris) {
            $final = FinalisasiRapor::query()
                ->with(['waliKelas', 'difinalisasioleh', 'semester.tahunajaran', 'kelas.wali'])
                ->where('semester_idsemester', $baris->semester_idsemester)
                ->where('kelas_idkelas', $baris->kelas_idkelas)
                ->where('status', FinalisasiRapor::STATUS_FINAL)
                ->first();

            if (! $final) {
                continue;
            }

            $hasil->push([
                'semester' => $final->semester,
                'kelas' => $final->kelas,
                'finalisasi' => $final,
            ]);
        }

        return $hasil->sortBy([fn ($a, $b) => ($a['semester']->tahunajaran_idtahunajaran <=> $b['semester']->tahunajaran_idtahunajaran) ?: ($a['semester']->idsemester <=> $b['semester']->idsemester)])
            ->values();
    }

    /**
     * Detail rapor final milik siswa login untuk satu semester+kelas.
     * Wajib final; kelas/semester harus ada di daftar rapor siswa ini.
     * Tidak ada parameter siswa — siswa selalu dari akun login.
     */
    public static function detail(User $user, int $semesterId, int $kelasId): array
    {
        $siswa = self::pastikanSiswa($user);

        if ($semesterId < 1 || $kelasId < 1) {
            throw new DomainException('Semester dan kelas rapor tidak valid.');
        }

        $final = FinalisasiRapor::query()
            ->with(['waliKelas', 'difinalisasioleh', 'semester.tahunajaran', 'kelas.wali'])
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->where('status', FinalisasiRapor::STATUS_FINAL)
            ->first();

        if (! $final) {
            throw new DomainException('Rapor ini belum difinalisasi atau bukan rapor Anda.');
        }

        $adaSnapshot = NilaiAkhir::query()
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->exists();

        if (! $adaSnapshot) {
            throw new DomainException('Rapor ini belum tersedia untuk Anda.');
        }

        $semester = $final->semester;
        $kelas = $final->kelas;

        $nilaiFinal = NilaiAkhir::query()
            ->with(['mapel', 'kelas', 'semester.tahunajaran', 'siswa'])
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->orderBy('mapel_idmapel')
            ->get()
            ->map(function (NilaiAkhir $item) {
                $item->setAttribute('keterangan', RaporWaliKelasService::keterangan(
                    (float) $item->nilai_akhir,
                    $item->nilai_kktp !== null ? (float) $item->nilai_kktp : null
                ));

                return $item;
            });

        $catatan = CatatanRapor::query()
            ->with(['siswa', 'guru'])
            ->where('semester_idsemester', $semesterId)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->first();

        return [
            'kelas' => $kelas,
            'siswa' => $siswa,
            'konteks' => new KonteksAkademik($semester->tahunajaran, $semester),
            'finalisasi' => $final,
            'is_final' => true,
            'nilai_final' => $nilaiFinal,
            'rekap_kehadiran' => self::rekapKehadiran($kelasId, $siswa->idsiswa, $semesterId),
            'catatan' => $catatan,
            'nama_sekolah' => (string) config('app.name', 'SIAKAD'),
        ];
    }

    /**
     * Rekap kehadiran gabungan (2B.5.7) untuk semester+kelas rapor tertentu.
     *
     * @return array{hadir: int, sakit: int, izin: int, alpha: int, total: int, per_tanggal: Collection<string, string>}
     */
    public static function rekapKehadiran(int $kelasId, int $siswaId, int $semesterId): array
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $kosong = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0, 'per_tanggal' => collect()];
        $semester = \App\Models\Semester::query()->with('tahunajaran')->find($semesterId);

        if (! $semester) {
            return $kosong;
        }

        $perTanggal = AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $semester->tahunajaran_idtahunajaran)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->where('siswa_idsiswa', $siswaId)
            ->get()
            ->keyBy(fn (AbsensiHarian $item) => $item->tanggal->format('Y-m-d'))
            ->map(fn (AbsensiHarian $item) => (string) $item->status);

        $perJam = AbsensiPerjam::query()
            ->join('jadwal_pertemuan as jp', 'jp.idjadwal_pertemuan', '=', 'absensi_perjam.jadwal_pertemuan_idjadwal_pertemuan')
            ->where('jp.semester_idsemester', $semesterId)
            ->where('jp.kelas_idkelas', $kelasId)
            ->where('absensi_perjam.siswa_idsiswa', $siswaId)
            ->select('absensi_perjam.status', 'jp.tanggal')
            ->get()
            ->groupBy(fn ($item) => substr((string) $item->tanggal, 0, 10));

        foreach ($perJam as $tanggal => $baris) {
            if ($perTanggal->has($tanggal)) {
                continue;
            }

            $statuses = $baris->pluck('status')->map(fn ($s) => (string) $s)->all();
            $terpilih = null;

            foreach (self::PRIORITAS_STATUS as $prioritas) {
                if (in_array($prioritas, $statuses, true)) {
                    $terpilih = $prioritas;
                    break;
                }
            }

            $perTanggal->put($tanggal, $terpilih ?? 'hadir');
        }

        $rekap = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0, 'per_tanggal' => $perTanggal->sortKeys()];

        foreach ($perTanggal as $status) {
            if (array_key_exists($status, $rekap)) {
                $rekap[$status]++;
                $rekap['total']++;
            }
        }

        return $rekap;
    }
}
