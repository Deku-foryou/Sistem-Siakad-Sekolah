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
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rapor wali kelas: daftar siswa kelas tanggung jawab, nilai final snapshot,
 * rekap kehadiran gabungan (semester aktif), catatan rapor, dan data PDF.
 *
 * Keputusan desain (lihat LAPORAN_TAHAP_2B5_7.md):
 * - Nilai akademik selalu dari `nilai_akhir` (snapshot 2B.5.6). Tidak ada
 *   perhitungan ulang nilai di service ini.
 * - Otorisasi kelas memakai `FinalisasiNilaiService::pastikanBolehAkses`
 *   (wali = `kelas.guru_idguru`; superadmin semua kelas TA aktif) — dipanggil
 *   di dalam setiap method yang mengakses data kelas (hardening 2B.13, K1).
 * - Rekap kehadiran: `absensi_harian` utama per tanggal; `absensi_perjam`
 *   hanya mengisi tanggal tanpa catatan harian; prioritas per tanggal
 *   alpha > izin > sakit > hadir; difilter semester + kelas rapor aktif.
 *   Kehadiran hanya informasi rapor, bukan komponen nilai.
 * - Siswa rapor = penempatan aktif (kelassiswa aktif + siswa aktif),
 *   sesuai populasi snapshot finalisasi 2B.5.6.
 * - Catatan disimpan per semester+siswa (unique 2B.5.3). Guru penulis
 *   selalu wali kelas kelas rapor (`kelas.guru_idguru`).
 * - PDF rapor resmi wajib `finalisasi_rapor.status = 'final'`.
 *   Detail sebelum final menampilkan peringatan dan menyembunyikan nilai.
 * - Kop PDF generik dari `config('app.name')`; nama sekolah & kepala
 *   sekolah belum tersedia di proyek (konfirmasi pengguna).
 */
class RaporWaliKelasService
{
    public const PRIORITAS_STATUS = ['alpha', 'izin', 'sakit', 'hadir'];

    /**
     * @return Collection<int, Kelas>
     */
    public static function daftarKelas(User $user): Collection
    {
        return FinalisasiNilaiService::daftarKelas($user);
    }

    public static function pastikanBolehAkses(User $user, int $kelasId): Kelas
    {
        return FinalisasiNilaiService::pastikanBolehAkses($user, $kelasId);
    }

    /**
     * Siswa sesuai histori kelas + semester rapor aktif: penempatan aktif
     * pada kelas dan siswa aktif. Siswa pindah/keluar dikecualikan.
     *
     * @return Collection<int, Siswa>
     */
    public static function daftarSiswa(User $user, int $kelasId): Collection
    {
        $kelas = self::pastikanBolehAkses($user, $kelasId);
        $konteks = KonteksAkademikService::ambil();

        return Siswa::query()
            ->where('status', 'aktif')
            ->whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelas->idkelas)->where('status', 'aktif'))
            ->with([
                'kelassiswa' => fn ($q) => $q->where('kelas_idkelas', $kelas->idkelas),
                'nilaiAkhir' => fn ($q) => $q->where('semester_idsemester', $konteks->semester?->idsemester)->where('kelas_idkelas', $kelas->idkelas),
                'catatanRapor' => fn ($q) => $q->where('semester_idsemester', $konteks->semester?->idsemester),
            ])
            ->orderBy('nama')
            ->get();
    }

    /**
     * Guard kepemilikan kelas untuk pemanggil berbasis user (2B.13, K1).
     * Guru hanya boleh kelas yang menjadi tanggung jawabnya; superadmin semua kelas TA aktif.
     */
    private static function pastikanAksesKelas(User $user, int $kelasId): Kelas
    {
        return self::pastikanBolehAkses($user, $kelasId);
    }

    public static function statusFinalisasi(User $user, int $kelasId): ?FinalisasiRapor
    {
        self::pastikanAksesKelas($user, $kelasId);

        return FinalisasiNilaiService::status($kelasId);
    }

    public static function pastikanFinal(User $user, int $kelasId): FinalisasiRapor
    {
        self::pastikanAksesKelas($user, $kelasId);
        $konteks = KonteksAkademikService::ambil();
        $semesterId = $konteks->semester?->idsemester;

        $row = $semesterId
            ? FinalisasiRapor::query()->with(['waliKelas', 'difinalisasioleh', 'semester.tahunajaran', 'kelas.wali'])
                ->where('semester_idsemester', $semesterId)->where('kelas_idkelas', $kelasId)->first()
            : null;

        if (! $row || $row->status !== FinalisasiRapor::STATUS_FINAL) {
            throw new DomainException('Rapor kelas ini belum difinalisasi. PDF rapor resmi tidak dapat dibuat sampai status menjadi final.');
        }

        return $row;
    }

    /**
     * Nilai final snapshot per mapel untuk satu siswa pada kelas+semester aktif.
     * Kurang/tidak dikecualikan — hanya baris `nilai_akhir` yang dibaca.
     */
    public static function nilaiFinal(User $user, int $kelasId, int $siswaId): Collection
    {
        self::pastikanAksesKelas($user, $kelasId);

        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->semester) {
            return collect();
        }

        return NilaiAkhir::query()
            ->with(['mapel', 'kelas', 'semester.tahunajaran', 'siswa'])
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->where('kelas_idkelas', $kelasId)
            ->where('siswa_idsiswa', $siswaId)
            ->orderBy('mapel_idmapel')
            ->get()
            ->map(function (NilaiAkhir $item) {
                $item->setAttribute('keterangan', self::keterangan((float) $item->nilai_akhir, $item->nilai_kktp !== null ? (float) $item->nilai_kktp : null));

                return $item;
            });
    }

    public static function keterangan(float $nilaiAkhir, ?float $nilaiKktp): ?string
    {
        if ($nilaiKktp === null) {
            return null;
        }

        return $nilaiAkhir >= $nilaiKktp ? 'Tuntas' : 'Belum Tuntas';
    }

    /**
     * Rekap kehadiran gabungan satu siswa pada semester + kelas rapor.
     *
     * @return array{hadir: int, sakit: int, izin: int, alpha: int, total: int, per_tanggal: Collection<string, string>}
     */
    public static function rekapKehadiran(User $user, int $kelasId, int $siswaId): array
    {
        self::pastikanAksesKelas($user, $kelasId);

        $konteks = KonteksAkademikService::ambil();
        $kosong = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0, 'per_tanggal' => collect()];

        if (! $konteks->valid() || ! $konteks->semester) {
            return $kosong;
        }

        $semesterId = $konteks->semester->idsemester;

        // Utama: absensi_harian per tanggal.
        $perTanggal = AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->where('siswa_idsiswa', $siswaId)
            ->get()
            ->keyBy(fn (AbsensiHarian $item) => $item->tanggal->format('Y-m-d'))
            ->map(fn (AbsensiHarian $item) => (string) $item->status);

        // Pelengkap: absensi_perjam per tanggal (hanya tanggal tanpa harian).
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

    // ---- Catatan rapor ----

    public static function catatan(User $user, int $kelasId, int $siswaId): ?CatatanRapor
    {
        self::pastikanAksesKelas($user, $kelasId);
        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->semester) {
            return null;
        }

        return CatatanRapor::query()
            ->with(['siswa', 'guru'])
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->where('siswa_idsiswa', $siswaId)
            ->first();
    }

    /**
     * Simpan/perbarui catatan wali kelas. Teks kosong menghapus baris
     * (rapi, tidak menyimpan catatan kosong).
     */
    public static function simpanCatatan(User $user, int $kelasId, int $siswaId, ?string $catatan): ?CatatanRapor
    {
        $kelas = self::pastikanBolehAkses($user, $kelasId);

        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->semester) {
            throw new DomainException('Konteks akademik belum valid.');
        }

        // Siswa harus sesuai histori kelas + semester rapor: roster aktif.
        $daftar = self::daftarSiswa($user, $kelasId);
        $siswa = $daftar->firstWhere('idsiswa', $siswaId);

        if (! $siswa) {
            throw new DomainException('Siswa tidak terdaftar aktif pada kelas ini pada semester aktif.');
        }

        $teks = trim((string) $catatan);

        if (mb_strlen($teks) > 1000) {
            throw new DomainException('Catatan maksimal 1000 karakter.');
        }

        if (! $kelas->guru_idguru) {
            throw new DomainException('Kelas ini belum memiliki wali kelas, sehingga catatan tidak dapat disimpan.');
        }

        return DB::transaction(function () use ($konteks, $siswaId, $teks, $kelas) {
            $row = CatatanRapor::query()
                ->where('semester_idsemester', $konteks->semester->idsemester)
                ->where('siswa_idsiswa', $siswaId)
                ->lockForUpdate()
                ->first();

            if ($teks === '') {
                $row?->delete();

                return null;
            }

            if ($row) {
                $row->update(['catatan' => $teks, 'guru_idguru' => $kelas->guru_idguru]);

                return $row->fresh(['siswa', 'guru']);
            }

            return CatatanRapor::create([
                'semester_idsemester' => $konteks->semester->idsemester,
                'siswa_idsiswa' => $siswaId,
                'catatan' => $teks,
                'guru_idguru' => $kelas->guru_idguru,
            ])->load(['siswa', 'guru']);
        });
    }

    // ---- Detail rapor / PDF ----

    /**
     * Data rapor per siswa. Nilai hanya disertakan bila final.
     * $wajibFinal = true untuk PDF resmi.
     */
    public static function raporDetail(User $user, int $kelasId, int $siswaId, bool $wajibFinal = false): array
    {
        $kelas = self::pastikanBolehAkses($user, $kelasId);

        $daftar = self::daftarSiswa($user, $kelasId);
        $siswa = $daftar->firstWhere('idsiswa', $siswaId);

        if (! $siswa) {
            throw new DomainException('Siswa tidak terdaftar aktif pada kelas ini pada semester aktif.');
        }

        $konteks = KonteksAkademikService::ambil();
        $final = self::statusFinalisasi($user, $kelasId);
        $isFinal = $final && $final->status === FinalisasiRapor::STATUS_FINAL;

        if ($wajibFinal && ! $isFinal) {
            throw new DomainException('Rapor kelas ini belum difinalisasi. PDF rapor resmi tidak dapat dibuat sampai status menjadi final.');
        }

        return [
            'kelas' => $kelas->load(['wali', 'tahunajaran']),
            'siswa' => $siswa,
            'konteks' => $konteks,
            'finalisasi' => $final,
            'is_final' => (bool) $isFinal,
            'nilai_final' => $isFinal ? self::nilaiFinal($user, $kelasId, $siswaId) : collect(),
            'rekap_kehadiran' => self::rekapKehadiran($user, $kelasId, $siswaId),
            'catatan' => self::catatan($user, $kelasId, $siswaId),
            'nama_sekolah' => (string) config('app.name', 'SIAKAD'),
        ];
    }
}
