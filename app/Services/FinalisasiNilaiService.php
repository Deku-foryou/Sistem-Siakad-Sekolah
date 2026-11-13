<?php

namespace App\Services;

use App\Exceptions\FinalisasiException;
use App\Models\FinalisasiRapor;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\KktpMapel;
use App\Models\KomponenNilai;
use App\Models\NilaiAkhir;
use App\Models\NilaiSiswa;
use App\Models\Notifikasi;
use App\Models\PenugasanMengajar;
use App\Models\Siswa;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finalisasi rapor: pemeriksaan kesiapan, snapshot nilai akhir, dan penguncian
 * rapor per kelas pada semester aktif.
 *
 * Keputusan desain (lihat LAPORAN_TAHAP_2B5_6.md):
 * - Semester finalisasi selalu semester aktif (KonteksAkademikService).
 * - Wali kelas = guru pada `kelas.guru_idguru`; hanya wali atau superadmin
 *   yang boleh memfinalisasi. Level `walikelas` tidak dipakai.
 * - Koreksi setelah final berjalan lewat KoreksiNilaiService, bukan dengan
 *   membuka kembali status final.
 * - Snapshot `nilai_akhir` dihitung TEPAT saat finalisasi (bukan saat baca
 *   rapor) dan pembuatannya satu transaksi dengan perubahan status `final`.
 */
class FinalisasiNilaiService
{
    public const OLAH_LEVEL = ['guru', 'superadmin'];

    /**
     * Kelas pada tahun ajaran semester aktif yang boleh diproses pengguna ini.
     *
     * Superadmin: seluruh kelas. Guru: hanya kelas yang menjadi tanggung
     * jawabnya (kelas.guru_idguru == guru yang terhubung ke user).
     *
     * @return Collection<int, Kelas>
     */
    public static function daftarKelas(User $user): Collection
    {
        abort_unless(in_array($user->level, self::OLAH_LEVEL, true), 403);

        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->tahunAjaran) {
            return collect();
        }

        $query = Kelas::query()
            ->with(['tahunajaran', 'wali'])
            ->withCount(['kelassiswa as jumlah_siswa_aktif' => fn ($q) => $q->where('status', 'aktif')])
            ->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)
            ->orderBy('tingkat')
            ->orderBy('nama');

        if ($user->level === 'guru') {
            $guru = $user->guru()->first();

            if (! $guru) {
                return collect();
            }

            $query->where('guru_idguru', $guru->idguru);
        }

        return $query->get();
    }

    /**
     * Status finalisasi rapor kelas pada semester aktif.
     */
    public static function status(int $kelasId): ?FinalisasiRapor
    {
        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->semester) {
            return null;
        }

        return FinalisasiRapor::query()
            ->with(['waliKelas', 'difinalisasiOleh'])
            ->where('semester_idsemester', $konteks->semester->idsemester)
            ->where('kelas_idkelas', $kelasId)
            ->first();
    }

    /**
     * Pemeriksaan kesiapan + ringkasan. Dipakai oleh UI (mode baca) dan oleh
     * finalisasi() (ditulis ulang di dalam transaksi agar tetap konsisten).
     *
     * @return array{
     *     kelas: Kelas,
     *     semester_id: ?int,
     *     errors: array<int, string>,
     *     ringkasan: array{jumlah_siswa: int, jumlah_mapel: int, jumlah_nilai: int}
     * }
     */
    public static function periksa(User $user, int $kelasId): array
    {
        $kelas = self::pastikanBolehAkses($user, $kelasId);

        return self::periksaInternal($kelas);
    }

    /**
     * Finalisasi rapor kelas pada semester aktif.
     *
     * @throws FinalisasiException Bila ada hal yang belum siap.
     */
    public static function finalisasi(User $user, int $kelasId): FinalisasiRapor
    {
        self::pastikanBolehAkses($user, $kelasId);

        return DB::transaction(function () use ($user, $kelasId) {
            // Kunci kelas agar dua finalisasi bersamaan tidak lolos bersama.
            $kelas = Kelas::query()->lockForUpdate()->findOrFail($kelasId);

            $data = self::periksaInternal($kelas, true);

            if ($data['errors'] !== []) {
                throw new FinalisasiException($data['errors']);
            }

            $konteks = KonteksAkademikService::ambil();
            $semesterId = (int) $konteks->semester->idsemester;

            $row = FinalisasiRapor::query()
                ->where('semester_idsemester', $semesterId)
                ->where('kelas_idkelas', $kelas->idkelas)
                ->lockForUpdate()
                ->first();

            if ($row && $row->status === FinalisasiRapor::STATUS_FINAL) {
                throw new DomainException('Rapor kelas ini pada semester aktif sudah difinalisasi.');
            }

            $siswaList = $data['siswa'];
            $now = now();
            $jumlahSnapshot = 0;

            foreach ($data['mapel'] as $mapelId => $paket) {
                foreach ($siswaList as $siswa) {
                    $nilaiPerKomponen = $paket['nilai'][$siswa->idsiswa] ?? [];
                    $akumulasi = 0.0;

                    foreach ($paket['komponen'] as $komponen) {
                        $akumulasi += ((float) ($nilaiPerKomponen[$komponen->idkomponen_nilai] ?? 0))
                            * (float) $komponen->bobot;
                    }

                    $nilaiAkhir = round($akumulasi / $paket['total_bobot'], 2);

                    NilaiAkhir::query()->updateOrCreate(
                        [
                            'semester_idsemester' => $semesterId,
                            'kelas_idkelas' => $kelas->idkelas,
                            'mapel_idmapel' => $mapelId,
                            'siswa_idsiswa' => $siswa->idsiswa,
                        ],
                        [
                            'nilai_akhir' => $nilaiAkhir,
                            'nilai_kktp' => $paket['kktp']->nilai_minimal,
                            'dihitung_pada' => $now,
                        ]
                    );

                    $jumlahSnapshot++;
                }
            }

            $row ??= new FinalisasiRapor([
                'semester_idsemester' => $semesterId,
                'kelas_idkelas' => $kelas->idkelas,
            ]);

            $row->status = FinalisasiRapor::STATUS_FINAL;
            $row->wali_kelas_idguru = $kelas->guru_idguru;
            $row->difinalisasi_oleh = $user->id;
            $row->difinalisasi_pada = $now;

            try {
                $row->save();
            } catch (QueryException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    throw new DomainException('Rapor kelas ini baru saja difinalisasi oleh proses lain.');
                }

                throw $exception;
            }

            // Notifikasi pemicu 2B.6.3: siswa roster aktif + wali kelas (atomic dalam transaksi finalisasi).
            $siswaUserIds = Siswa::query()
                ->whereIn('idsiswa', $siswaList->pluck('idsiswa')->all())
                ->pluck('user_iduser')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($siswaUserIds !== []) {
                NotifikasiService::buat(
                    $siswaUserIds,
                    [
                        'judul' => 'Rapor difinalisasi: '.$kelas->nama,
                        'isi' => 'Rapor Anda untuk kelas '.$kelas->nama.' semester aktif telah difinalisasi.',
                        'jenis' => Notifikasi::JENIS_RAPOR,
                        'prioritas' => 'tinggi',
                        'sumber_tabel' => 'finalisasi_rapor',
                        'sumber_id' => $row->idfinalisasi_rapor,
                        'link' => '/raporsiswa',
                    ],
                    $user
                );
            }

            if ($kelas->guru_idguru) {
                $waliUserId = Guru::query()
                    ->where('idguru', $kelas->guru_idguru)
                    ->value('user_iduser');
                $waliUserIds = $waliUserId ? [(int) $waliUserId] : [];

                if ($waliUserIds !== []) {
                    NotifikasiService::buat(
                        $waliUserIds,
                        [
                            'judul' => 'Rapor kelas difinalisasi: '.$kelas->nama,
                            'isi' => 'Rapor kelas '.$kelas->nama.' yang menjadi tanggung jawab Anda telah difinalisasi.',
                            'jenis' => Notifikasi::JENIS_RAPOR,
                            'prioritas' => 'tinggi',
                            'sumber_tabel' => 'finalisasi_rapor',
                            'sumber_id' => $row->idfinalisasi_rapor,
                            'link' => '/raporwalikelas',
                        ],
                        $user
                    );
                }
            }

            // Notifikasi wali murid (2B.12, D1/D3/D10): satu notifikasi per anak per wali.
            // Hanya relasi verified + periode berlaku + akun wali aktif; tanpa nilai/data sensitif.
            $relasiWali = WalimuridService::relasiWaliAktifSiswa($siswaList->pluck('idsiswa')->all())
                ->groupBy('siswa_idsiswa');

            foreach ($siswaList as $siswa) {
                $waliIds = ($relasiWali->get($siswa->idsiswa) ?? collect())->pluck('user_iduser')->map(fn ($id) => (int) $id)->unique()->all();

                foreach ($waliIds as $waliId) {
                    NotifikasiService::buat(
                        [$waliId],
                        [
                            'judul' => 'Rapor difinalisasi: '.$kelas->nama,
                            'isi' => 'Rapor '.$siswa->nama.' pada kelas '.$kelas->nama.' untuk semester aktif telah difinalisasi. Lihat detail di portal Wali.',
                            'jenis' => Notifikasi::JENIS_RAPOR,
                            'prioritas' => 'tinggi',
                            'sumber_tabel' => 'finalisasi_rapor',
                            'sumber_id' => $row->idfinalisasi_rapor,
                            'link' => '/wali-akademik',
                        ],
                        $user
                    );
                }
            }

            return $row->fresh(['waliKelas', 'difinalisasiOleh']);
        });
    }

    /**
     * Pastikan pengguna boleh memproses kelas ini.
     *
     * Superadmin: boleh untuk semua kelas. Guru: hanya kelas yang menjadi
     * tanggung jawabnya. Sisanya (siswa, guru tanpa kelas wali) ditolak.
     */
    public static function pastikanBolehAkses(User $user, int $kelasId): Kelas
    {
        abort_unless(in_array($user->level, self::OLAH_LEVEL, true), 403);

        $konteks = KonteksAkademikService::ambil();

        if (! $konteks->valid() || ! $konteks->tahunAjaran) {
            throw new DomainException('Konteks akademik belum valid. Aktifkan tahun ajaran dan semester terlebih dahulu.');
        }

        $kelas = Kelas::with('tahunajaran')->find($kelasId);

        if (! $kelas) {
            throw new DomainException('Kelas tidak ditemukan.');
        }

        if ((int) $kelas->tahunajaran_idtahunajaran !== (int) $konteks->tahunAjaran->idtahunajaran) {
            throw new DomainException('Kelas tersebut tidak berada pada tahun ajaran aktif.');
        }

        if ($user->level === 'superadmin') {
            return $kelas;
        }

        $guru = $user->guru()->first();

        if (! $guru) {
            throw new DomainException('Akun guru ini belum terhubung ke data guru.');
        }

        if ((int) $kelas->guru_idguru !== (int) $guru->idguru) {
            abort(403, 'Anda bukan wali kelas untuk kelas ini.');
        }

        return $kelas;
    }

    /**
     * Kumpulan data + kesalahan. Dipanggil di dalam atau di luar transaksi.
     *
     * @return array{
     *     kelas: Kelas,
     *     semester_id: ?int,
     *     errors: array<int, string>,
     *     ringkasan: array{jumlah_siswa: int, jumlah_mapel: int, jumlah_nilai: int},
     *     siswa: Collection<int, Siswa>,
     *     mapel: array<int, array{komponen: Collection<int, KomponenNilai>, nilai: array<int, array<int, float>>, total_bobot: float, kktp: KktpMapel}>
     * }
     */
    private static function periksaInternal(Kelas $kelas, bool $lockRows = false): array
    {
        $errors = [];
        $konteks = KonteksAkademikService::ambil();
        $semesterId = $konteks->semester?->idsemester;
        $kosong = [
            'kelas' => $kelas,
            'semester_id' => $semesterId,
            'errors' => [],
            'ringkasan' => ['jumlah_siswa' => 0, 'jumlah_mapel' => 0, 'jumlah_nilai' => 0],
            'siswa' => collect(),
            'mapel' => [],
        ];

        if (! $konteks->valid() || ! $konteks->semester) {
            $kosong['errors'] = ['Konteks akademik belum valid. Finalisasi tidak dapat dilakukan.'];

            return $kosong;
        }

        $semester = $konteks->semester;

        // Siswa aktif pada kelas.
        $siswaList = Siswa::query()
            ->where('status', 'aktif')
            ->whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelas->idkelas)->where('status', 'aktif'))
            ->orderBy('nama')
            ->get();

        if ($siswaList->isEmpty()) {
            $errors[] = 'Tidak ada siswa aktif pada kelas ini.';
        }

        $penugasan = PenugasanMengajar::query()
            ->with('mapel')
            ->where('semester_idsemester', $semester->idsemester)
            ->where('kelas_idkelas', $kelas->idkelas)
            ->orderBy('mapel_idmapel')
            ->get();

        if ($penugasan->isEmpty()) {
            $errors[] = 'Tidak ada penugasan mengajar untuk kelas ini pada semester aktif.';
        }

        // Petakan penugasan per mapel (yang tidak konsisten jumlahnya ganti salah satunya).
        $mapelPenugasan = $penugasan->keyBy('mapel_idmapel');

        // Seluruh nilai untuk kelas + semester pada komponen yang relevan.
        $seluruhNilai = self::nilaiQuery($lockRows)
            ->where('semester_idsemester', $semester->idsemester)
            ->where('kelas_idkelas', $kelas->idkelas)
            ->get();

        $jumlahNilai = $seluruhNilai->count();

        // Komponen yang ditunjuk nilai untuk pemeriksaan konsistensi.
        $komponenIdsNilai = $seluruhNilai->pluck('komponen_nilai_idkomponen_nilai')->unique()->all();

        $komponenSemua = $komponenIdsNilai === []
            ? collect()
            : KomponenNilai::query()->with('mapel')->whereIn('idkomponen_nilai', $komponenIdsNilai)->get()->keyBy('idkomponen_nilai');

        // Konsistensi: mapel pada nilai harus memiliki penugasan pada kelas + semester.
        foreach ($komponenSemua->groupBy('mapel_idmapel') as $mapelId => $items) {
            if (! $mapelPenugasan->has((int) $mapelId)) {
                $namaMapel = $items->first()->mapel?->nama ?? ('mapel #'.$mapelId);
                $errors[] = 'Terdapat nilai untuk mata pelajaran "'.$namaMapel.'" yang tidak memiliki penugasan mengajar pada kelas ini.';
            }
        }

        // Konsistensi semester: komponen pada nilai harus milik semester aktif.
        foreach ($komponenSemua as $komponenNilai) {
            if ((int) $komponenNilai->semester_idsemester !== (int) $semester->idsemester) {
                $errors[] = 'Terdapat nilai yang merujuk komponen penilaian dari semester lain (komponen #'.$komponenNilai->idkomponen_nilai.').';
            }
        }

        // Nilai di luar rentang 0-100.
        foreach ($seluruhNilai as $nilai) {
            $angka = (float) $nilai->nilai;

            if ($angka < 0 || $angka > 100) {
                $errors[] = 'Terdapat nilai di luar rentang 0-100 (siswa #'.$nilai->siswa_idsiswa.').';
            }
        }

        $siswaIds = $siswaList->pluck('idsiswa')->map(fn ($id) => (int) $id)->all();

        // Konsistensi siswa: nilai harus milik siswa aktif yang terdaftar pada kelas.
        $siswaTidakTerdaftar = $seluruhNilai
            ->filter(fn ($nilai) => ! in_array((int) $nilai->siswa_idsiswa, $siswaIds, true))
            ->pluck('siswa_idsiswa')
            ->unique()
            ->values();

        foreach ($siswaTidakTerdaftar as $siswaId) {
            $errors[] = 'Terdapat nilai untuk siswa #'.$siswaId.' yang tidak aktif atau tidak terdaftar pada kelas ini semester aktif.';
        }
        $paketMapel = [];

        foreach ($mapelPenugasan as $mapelId => $penugasanItem) {
            $mapelId = (int) $mapelId;
            $namaMapel = $penugasanItem->mapel?->nama ?? ('mapel #'.$mapelId);
            $komponen = NilaiSiswaService::komponenAktif($penugasanItem);

            if ($komponen->isEmpty()) {
                $errors[] = 'Mata pelajaran "'.$namaMapel.'" tidak memiliki komponen penilaian aktif.';

                continue;
            }

            $totalBobot = round((float) $komponen->sum(fn (KomponenNilai $item) => (float) $item->bobot), 2);

            if (abs($totalBobot - 100.0) >= 0.005) {
                $errors[] = 'Total bobot komponen aktif mata pelajaran "'.$namaMapel.'" adalah '.number_format((float) $totalBobot, 2, ',', '.').'%, harus tepat 100%.';
            }

            // Pada finalisasi ($lockRows=true): kunci baris KKTP agar transaksi
            // koreksi KKTP (yang mengunci baris yang sama lebih dulu) dihormati,
            // dan koreksi yang tiba setelah bacaan ini menunggu finalisasi selesai.
            $kktpQuery = KktpMapel::query()
                ->where('semester_idsemester', $semester->idsemester)
                ->where('mapel_idmapel', $mapelId);

            if ($lockRows) {
                $kktpQuery->lockForUpdate();
            }

            $kktp = $kktpQuery->first();

            if (! $kktp) {
                $errors[] = 'KKTP untuk mata pelajaran "'.$namaMapel.'" belum dikonfigurasi.';
            } elseif ((float) $kktp->nilai_minimal < 0 || (float) $kktp->nilai_minimal > 100) {
                $errors[] = 'Nilai KKTP mata pelajaran "'.$namaMapel.'" tidak berada pada rentang 0-100.';
            }

            $komponenIds = $komponen->pluck('idkomponen_nilai')->all();

            $nilaiMapel = self::nilaiQuery($lockRows)
                ->where('semester_idsemester', $semester->idsemester)
                ->where('kelas_idkelas', $kelas->idkelas)
                ->whereIn('komponen_nilai_idkomponen_nilai', $komponenIds)
                ->get()
                ->groupBy('siswa_idsiswa');

            // Nilai untuk komponen nonaktif pada mapel ini dihitung sebagai data tidak konsisten.
            $nilaiNonaktif = NilaiSiswa::query()
                ->where('semester_idsemester', $semester->idsemester)
                ->where('kelas_idkelas', $kelas->idkelas)
                ->whereIn('komponen_nilai_idkomponen_nilai', KomponenNilai::query()
                    ->where('semester_idsemester', $semester->idsemester)
                    ->where('mapel_idmapel', $mapelId)
                    ->where('aktif', false)
                    ->pluck('idkomponen_nilai'))
                ->count();

            if ($nilaiNonaktif > 0) {
                $errors[] = 'Terdapat '.$nilaiNonaktif.' nilai untuk komponen tidak aktif pada mata pelajaran "'.$namaMapel.'".';
            }

            $nilaiPerSiswa = [];

            foreach ($siswaList as $siswa) {
                $baris = $nilaiMapel->get($siswa->idsiswa, collect());
                $isi = [];

                foreach ($baris as $barisNilai) {
                    $isi[(int) $barisNilai->komponen_nilai_idkomponen_nilai] = (float) $barisNilai->nilai;
                }

                if (count($isi) < $komponen->count()) {
                    $errors[] = 'Siswa '.$siswa->nama.' (NIS '.($siswa->nis ?? '-').') belum lengkap pada mata pelajaran "'.$namaMapel.'" ('.count($isi).'/'.$komponen->count().' komponen terisi).';
                }

                $nilaiPerSiswa[(int) $siswa->idsiswa] = $isi;
            }

            $paketMapel[$mapelId] = [
                'komponen' => $komponen,
                'nilai' => $nilaiPerSiswa,
                'total_bobot' => $totalBobot,
                'kktp' => $kktp,
            ];
        }

        return [
            'kelas' => $kelas,
            'semester_id' => (int) $semester->idsemester,
            'errors' => array_values(array_unique($errors)),
            'ringkasan' => [
                'jumlah_siswa' => $siswaList->count(),
                'jumlah_mapel' => count($paketMapel),
                'jumlah_nilai' => $jumlahNilai,
            ],
            'siswa' => $siswaList,
            'mapel' => $paketMapel,
        ];
    }

    /**
     * Builder nilai dengan opsi penguncian baris (dipakai saat finalisasi
     * berjalan dalam transaksi agar bacaan konsisten terhadap input nilai).
     */
    private static function nilaiQuery(bool $lockRows): \Illuminate\Database\Eloquent\Builder
    {
        $query = NilaiSiswa::query();

        if ($lockRows) {
            $query->lockForUpdate();
        }

        return $query;
    }
}
