<?php

namespace App\Services;

use App\Models\FinalisasiRapor;
use App\Models\Guru;
use App\Models\Kelassiswa;
use App\Models\KomponenNilai;
use App\Models\NilaiAkhir;
use App\Models\NilaiSiswa;
use App\Models\PenugasanMengajar;
use App\Models\Siswa;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Input dan rekap nilai guru.
 *
 * Sumber otorisasi tunggal: `penugasan_mengajar`. Modul ini tidak membaca
 * atau menulis `pengampu` maupun `jadwalkelas`.
 *
 * Nilai hanya dapat diinput pada semester aktif (KonteksAkademikService).
 * Nilai akhir dan finalisasi tidak dibuat di sini; rekap bersifat draft.
 */
class NilaiSiswaService
{
    /**
     * Daftar penugasan mengajar milik guru yang login pada semester aktif.
     *
     * @return Collection<int, PenugasanMengajar>
     */
    public static function daftarPenugasan(User $user): Collection
    {
        $guru = self::pastikanGuru($user);
        $semesterId = self::semesterAktifId();

        return PenugasanMengajar::query()
            ->with(['semester.tahunajaran', 'kelas', 'mapel'])
            ->where('guru_idguru', $guru->idguru)
            ->where('semester_idsemester', $semesterId)
            ->orderBy('kelas_idkelas')
            ->orderBy('mapel_idmapel')
            ->get();
    }

    /**
     * Ambil penugasan yang benar-benar milik guru pada semester aktif.
     */
    public static function penugasan(User $user, int $penugasanId): PenugasanMengajar
    {
        $guru = self::pastikanGuru($user);
        $semesterId = self::semesterAktifId();

        $penugasan = $penugasanId > 0
            ? PenugasanMengajar::with(['semester.tahunajaran', 'kelas', 'mapel'])->find($penugasanId)
            : null;

        if (! $penugasan) {
            throw new DomainException('Penugasan mengajar tidak ditemukan.');
        }

        if ((int) $penugasan->guru_idguru !== (int) $guru->idguru) {
            throw new DomainException('Penugasan mengajar tersebut bukan milik Anda.');
        }

        if ((int) $penugasan->semester_idsemester !== $semesterId) {
            throw new DomainException('Penugasan mengajar tersebut bukan pada semester aktif.');
        }

        return $penugasan;
    }

    /**
     * Siswa aktif yang terdaftar pada kelas penugasan.
     *
     * @return Collection<int, Siswa>
     */
    public static function siswaPenugasan(PenugasanMengajar $penugasan): Collection
    {
        return Siswa::query()
            ->where('status', 'aktif')
            ->whereHas('kelassiswa', function ($query) use ($penugasan) {
                $query->where('kelas_idkelas', $penugasan->kelas_idkelas)
                    ->where('status', 'aktif');
            })
            ->orderBy('nama')
            ->get();
    }

    /**
     * Komponen penilaian aktif milik semester + mapel penugasan.
     *
     * @return Collection<int, KomponenNilai>
     */
    public static function komponenAktif(PenugasanMengajar $penugasan): Collection
    {
        return KomponenNilai::query()
            ->where('semester_idsemester', $penugasan->semester_idsemester)
            ->where('mapel_idmapel', $penugasan->mapel_idmapel)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('idkomponen_nilai')
            ->get();
    }

    public static function konfigurasiValid(PenugasanMengajar $penugasan): bool
    {
        $komponen = self::komponenAktif($penugasan);

        if ($komponen->isEmpty()) {
            return false;
        }

        $total = (int) $komponen->sum(fn (KomponenNilai $item) => (int) round((float) $item->bobot * 100));

        return $total === 10000;
    }

    /**
     * Nilai tersimpan untuk komponen aktif, dikunci dengan "siswaId.komponenId".
     *
     * @param  Collection<int, KomponenNilai>|null  $komponen
     * @return Collection<string, NilaiSiswa>
     */
    public static function nilaiTersimpan(PenugasanMengajar $penugasan, ?Collection $komponen = null): Collection
    {
        $komponen ??= self::komponenAktif($penugasan);
        $komponenIds = $komponen->pluck('idkomponen_nilai')->all();

        if ($komponenIds === []) {
            return collect();
        }

        return NilaiSiswa::query()
            ->where('semester_idsemester', $penugasan->semester_idsemester)
            ->where('kelas_idkelas', $penugasan->kelas_idkelas)
            ->whereIn('komponen_nilai_idkomponen_nilai', $komponenIds)
            ->get()
            ->keyBy(fn (NilaiSiswa $nilai) => $nilai->siswa_idsiswa.'.'.$nilai->komponen_nilai_idkomponen_nilai);
    }

    /**
     * Rekap nilai draft per siswa.
     *
     * Aturan bobot parsial:
     * - Hanya komponen aktif yang dihitung.
     * - Nilai draft = sum(nilai x bobot) / sum(bobot komponen yang sudah terisi).
     *   Normalisasi terhadap bobot terisi mencegah nilai draft terlalu kecil
     *   saat baru sebagian komponen dinilai.
     * - Jika belum semua komponen aktif terisi, status "belum lengkap" dan
     *   nilai draft bersifat sementara (bukan nilai final).
     * - Jika tidak ada komponen yang terisi, nilai draft null.
     * - Jika konfigurasi komponen tidak valid (total bobot aktif bukan 100%),
     *   nilai draft null agar tidak menyesatkan; input juga diblokir.
     *
     * @return Collection<int, array{
     *     siswa: Siswa,
     *     nilai: array<int, float|null>,
     *     nilai_draft: float|null,
     *     lengkap: bool,
     *     jumlah_terisi: int,
     *     jumlah_komponen: int,
     *     bobot_terisi: float,
     *     bobot_total: float,
     *     konfigurasi_valid: bool
     * }>
     */
    public static function rekap(PenugasanMengajar $penugasan): Collection
    {
        $komponen = self::komponenAktif($penugasan);
        $siswa = self::siswaPenugasan($penugasan);
        $tersimpan = self::nilaiTersimpan($penugasan, $komponen);
        $konfigurasiValid = self::konfigurasiValid($penugasan);

        $bobotTotal = (float) $komponen->sum(fn (KomponenNilai $item) => (float) $item->bobot);
        $hasil = collect();

        foreach ($siswa as $murid) {
            $nilaiPerKomponen = [];
            $bobotTerisi = 0.0;
            $akumulasi = 0.0;
            $jumlahTerisi = 0;

            foreach ($komponen as $item) {
                $kunci = $murid->idsiswa.'.'.$item->idkomponen_nilai;
                $baris = $tersimpan->get($kunci);

                if (! $baris) {
                    $nilaiPerKomponen[(int) $item->idkomponen_nilai] = null;

                    continue;
                }

                $nilai = (float) $baris->nilai;
                $bobot = (float) $item->bobot;

                $nilaiPerKomponen[(int) $item->idkomponen_nilai] = $nilai;
                $bobotTerisi += $bobot;
                $akumulasi += $nilai * $bobot;
                $jumlahTerisi++;
            }

            $nilaiDraft = $konfigurasiValid && $bobotTerisi > 0
                ? round($akumulasi / $bobotTerisi, 2)
                : null;

            $hasil->put((int) $murid->idsiswa, [
                'siswa' => $murid,
                'nilai' => $nilaiPerKomponen,
                'nilai_draft' => $nilaiDraft,
                'lengkap' => $konfigurasiValid
                    && $komponen->isNotEmpty()
                    && $jumlahTerisi === $komponen->count(),
                'jumlah_terisi' => $jumlahTerisi,
                'jumlah_komponen' => $komponen->count(),
                'bobot_terisi' => round($bobotTerisi, 2),
                'bobot_total' => round($bobotTotal, 2),
                'konfigurasi_valid' => $konfigurasiValid,
            ]);
        }

        return $hasil;
    }

    /**
     * Simpan atau perbarui satu nilai siswa.
     *
     * Identitas guru, semester, dan kelas diambil dari penugasan dan user yang
     * login; tidak ada parameter guru/kelas/semester dari frontend.
     */
    public static function simpan(
        User $user,
        int $penugasanId,
        int $siswaId,
        int $komponenId,
        mixed $nilai
    ): NilaiSiswa {
        $guru = self::pastikanGuru($user);

        return DB::transaction(function () use ($user, $guru, $penugasanId, $siswaId, $komponenId, $nilai) {
            $penugasan = self::penugasan($user, $penugasanId);

            self::pastikanKonfigurasiValid($penugasan);

            if (self::terkunci($penugasan)) {
                throw new DomainException(
                    'Nilai tidak dapat diubah karena rapor kelas ini sudah difinalisasi atau snapshot nilai akhir sudah ada.'
                );
            }

            if ($siswaId < 1 || ! self::siswaAdaDiKelas($penugasan, $siswaId)) {
                throw new DomainException(
                    'Siswa tidak terdaftar pada kelas penugasan ini pada semester aktif.'
                );
            }

            $komponen = KomponenNilai::query()
                ->where('idkomponen_nilai', $komponenId)
                ->where('semester_idsemester', $penugasan->semester_idsemester)
                ->where('mapel_idmapel', $penugasan->mapel_idmapel)
                ->where('aktif', true)
                ->first();

            if (! $komponen) {
                throw new DomainException(
                    'Komponen penilaian tidak valid: harus milik semester dan mata pelajaran penugasan serta berstatus aktif.'
                );
            }

            $nilaiBersih = self::validasiNilai($nilai);

            $kunci = [
                'semester_idsemester' => $penugasan->semester_idsemester,
                'kelas_idkelas' => $penugasan->kelas_idkelas,
                'siswa_idsiswa' => $siswaId,
                'komponen_nilai_idkomponen_nilai' => $komponenId,
            ];

            $baris = NilaiSiswa::query()->where($kunci)->lockForUpdate()->first();

            if ($baris) {
                $baris->nilai = $nilaiBersih;
                $baris->guru_idguru = $guru->idguru;
                $baris->save();

                return $baris;
            }

            try {
                return NilaiSiswa::create($kunci + [
                    'nilai' => $nilaiBersih,
                    'guru_idguru' => $guru->idguru,
                ]);
            } catch (QueryException $exception) {
                // Request bersamaan dapat menyisipkan baris yang sama.
                // Unique constraint menjadi penjaga terakhir; ulangi sebagai update.
                if ((string) $exception->getCode() !== '23000') {
                    throw $exception;
                }

                $baris = NilaiSiswa::query()->where($kunci)->lockForUpdate()->first();

                if (! $baris) {
                    throw new DomainException(
                        'Nilai gagal disimpan karena terjadi perubahan bersamaan. Muat ulang halaman lalu coba lagi.'
                    );
                }

                $baris->nilai = $nilaiBersih;
                $baris->guru_idguru = $guru->idguru;
                $baris->save();

                return $baris;
            }
        });
    }

    /**
     * Simpan banyak nilai dalam satu transaksi.
     *
     * Nilai kosong (null/'') dilewati, bukan menghapus nilai yang sudah ada.
     *
     * @param  array<int|string, array<int|string, mixed>>  $matriks  [siswaId => [komponenId => nilai]]
     */
    public static function simpanBanyak(User $user, int $penugasanId, array $matriks): int
    {
        $penugasan = self::penugasan($user, $penugasanId);
        self::pastikanKonfigurasiValid($penugasan);

        $jumlah = 0;

        DB::transaction(function () use ($user, $penugasanId, $matriks, &$jumlah) {
            foreach ($matriks as $siswaId => $nilaiKomponen) {
                if (! is_array($nilaiKomponen)) {
                    throw new DomainException('Format nilai yang dikirim tidak valid.');
                }

                foreach ($nilaiKomponen as $komponenId => $nilai) {
                    if ($nilai === null || $nilai === '') {
                        continue;
                    }

                    self::simpan($user, $penugasanId, (int) $siswaId, (int) $komponenId, $nilai);
                    $jumlah++;
                }
            }
        });

        return $jumlah;
    }

    /**
     * Apakah nilai penugasan ini terkunci oleh finalisasi/snapshot.
     *
     * Memakai lockForUpdate() agar input nilai yang berjalan bersamaan dengan
     * transaksi finalisasi menunggu, lalu melihat status final yang baru
     * commit, bukan memakai snapshot lama.
     */
    public static function terkunci(PenugasanMengajar $penugasan): bool
    {
        $final = FinalisasiRapor::query()
            ->where('semester_idsemester', $penugasan->semester_idsemester)
            ->where('kelas_idkelas', $penugasan->kelas_idkelas)
            ->where('status', FinalisasiRapor::STATUS_FINAL)
            ->lockForUpdate()
            ->first() !== null;

        if ($final) {
            return true;
        }

        return NilaiAkhir::query()
            ->where('semester_idsemester', $penugasan->semester_idsemester)
            ->where('kelas_idkelas', $penugasan->kelas_idkelas)
            ->where('mapel_idmapel', $penugasan->mapel_idmapel)
            ->lockForUpdate()
            ->first() !== null;
    }

    private static function pastikanGuru(User $user): Guru
    {
        abort_unless($user->level === 'guru', 403);

        $guru = $user->guru()->first();

        if (! $guru) {
            throw new DomainException('Akun guru ini belum terhubung ke data guru. Hubungi administrator.');
        }

        return $guru;
    }

    private static function semesterAktifId(): int
    {
        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->semester) {
            throw new DomainException(
                'Konteks akademik belum valid. Aktifkan tahun ajaran dan semester terlebih dahulu.'
            );
        }

        return (int) $konteks->semester->idsemester;
    }

    private static function pastikanKonfigurasiValid(PenugasanMengajar $penugasan): void
    {
        if (! self::konfigurasiValid($penugasan)) {
            throw new DomainException(
                'Konfigurasi komponen belum valid. Total bobot komponen aktif harus tepat 100% sebelum nilai dapat diinput.'
            );
        }
    }

    private static function siswaAdaDiKelas(PenugasanMengajar $penugasan, int $siswaId): bool
    {
        return Kelassiswa::query()
            ->where('kelas_idkelas', $penugasan->kelas_idkelas)
            ->where('siswa_idsiswa', $siswaId)
            ->where('status', 'aktif')
            ->whereHas('siswa', fn ($query) => $query->where('status', 'aktif'))
            ->exists();
    }

    /**
     * Direvisi menjadi public agar FinalisasiNilaiService/KoreksiNilaiService
     * dapat memakai aturan nilai yang sama tanpa menduplikasi validasi.
     */
    public static function validasiNilai(mixed $nilai): float
    {
        if (! is_numeric($nilai)) {
            throw new DomainException('Nilai wajib berupa angka.');
        }

        $angka = (float) $nilai;

        if ($angka < 0 || $angka > 100) {
            throw new DomainException('Nilai harus berada pada rentang 0 sampai 100.');
        }

        if (abs($angka - round($angka, 2)) > 0.00001) {
            throw new DomainException('Nilai maksimal dua angka desimal.');
        }

        return round($angka, 2);
    }
}
