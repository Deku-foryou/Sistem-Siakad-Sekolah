<?php

namespace App\Services;

use App\Models\FinalisasiRapor;
use App\Models\KktpMapel;
use App\Models\KomponenNilai;
use App\Models\NilaiAkhir;
use App\Models\NilaiPerubahanLog;
use App\Models\NilaiSiswa;
use App\Models\Notifikasi;
use App\Models\Siswa;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi nilai rapor yang sudah difinalisasi.
 *
 * Keputusan desain (lihat LAPORAN_TAHAP_2B5_6.md):
 * - Koreksi berjalan melalui jalur khusus ini; input nilai biasa tetap
 *   menolak perubahan pada rapor final.
 * - Koreksi hanya untuk rapor berstatus `final`; rapor draf memakai input biasa.
 * - Koreksi adalah wewenang superadmin. Guru mata pelajaran tidak dapat
 *   berkoreksi.
 * - Alasan wajib diisi; nilai lama/baru, target, dan pelaku disimpan di
 *   `nilai_perubahan_log` (tepat satu target per baris, CHECK constraint DB
 *   sebagai backstop).
 * - Koreksi komponen menghitung ulang snapshot `nilai_akhir` terkait pada
 *   transaksi yang sama.
 * - Koreksi KKTP memperbarui snapshot `nilai_akhir.nilai_kktp` pada seluruh
 *   nilai akhir (semester, mapel) yang terdampak pada transaksi yang sama.
 */
class KoreksiNilaiService
{
    /**
     * Koreksi satu nilai komponen pada rapor final.
     */
    public static function koreksiKomponen(User $aktor, int $idNilaiSiswa, mixed $nilaiBaru, ?string $alasan): NilaiSiswa
    {
        abort_unless($aktor->level === 'superadmin', 403);

        $alasan = self::validasiAlasan($alasan);
        $nilaiBaru = NilaiSiswaService::validasiNilai($nilaiBaru);

        return DB::transaction(function () use ($aktor, $idNilaiSiswa, $nilaiBaru, $alasan) {
            $nilai = NilaiSiswa::query()
                ->with(['komponenNilai' => fn ($q) => $q->with('mapel')])
                ->lockForUpdate()
                ->find($idNilaiSiswa);

            if (! $nilai) {
                throw new DomainException('Nilai siswa tidak ditemukan.');
            }

            $komponen = $nilai->komponenNilai;

            if (! $komponen) {
                throw new DomainException('Komponen penilaian untuk nilai ini tidak ditemukan.');
            }

            if (! $komponen->aktif) {
                throw new DomainException('Komponen penilaian ini sudah nonaktif; koreksi tidak dapat dilakukan.');
            }

            $raporFinal = FinalisasiRapor::query()
                ->where('semester_idsemester', $nilai->semester_idsemester)
                ->where('kelas_idkelas', $nilai->kelas_idkelas)
                ->where('status', FinalisasiRapor::STATUS_FINAL)
                ->lockForUpdate()
                ->first();

            if (! $raporFinal) {
                throw new DomainException('Rapor kelas ini belum difinalisasi. Gunakan input nilai biasa.');
            }

            // Hitung ulang snapshot nilai akhir dari seluruh komponen aktif.
            $komponenAktif = KomponenNilai::query()
                ->where('semester_idsemester', $nilai->semester_idsemester)
                ->where('mapel_idmapel', $komponen->mapel_idmapel)
                ->where('aktif', true)
                ->orderBy('urutan')
                ->orderBy('idkomponen_nilai')
                ->get();

            $totalBobot = round(
                (float) $komponenAktif->sum(fn (KomponenNilai $item) => (float) $item->bobot),
                2
            );

            if (abs($totalBobot - 100.0) >= 0.005) {
                throw new DomainException('Total bobot komponen aktif mata pelajaran ini berubah dan tidak lagi 100%; koreksi dibatalkan agar snapshot tetap konsisten.');
            }

            $nilaiKomponen = NilaiSiswa::query()
                ->where('semester_idsemester', $nilai->semester_idsemester)
                ->where('kelas_idkelas', $nilai->kelas_idkelas)
                ->where('siswa_idsiswa', $nilai->siswa_idsiswa)
                ->whereIn('komponen_nilai_idkomponen_nilai', $komponenAktif->pluck('idkomponen_nilai')->all())
                ->get()
                ->keyBy('komponen_nilai_idkomponen_nilai');

            if ($nilaiKomponen->count() !== $komponenAktif->count()) {
                throw new DomainException('Nilai komponen tidak lengkap; snapshot nilai akhir tidak dapat dihitung ulang.');
            }

            $akumulasi = 0.0;

            foreach ($komponenAktif as $item) {
                $nilaiItem = $item->idkomponen_nilai === $nilai->komponen_nilai_idkomponen_nilai
                    ? $nilaiBaru
                    : (float) $nilaiKomponen->get($item->idkomponen_nilai)->nilai;

                $akumulasi += $nilaiItem * (float) $item->bobot;
            }

            $nilaiAkhirBaru = round($akumulasi / $totalBobot, 2);

            $nilaiLama = (float) $nilai->nilai;
            $nilai->nilai = $nilaiBaru;
            $nilai->save();

            $nilaiAkhir = NilaiAkhir::query()
                ->where('semester_idsemester', $nilai->semester_idsemester)
                ->where('kelas_idkelas', $nilai->kelas_idkelas)
                ->where('mapel_idmapel', $komponen->mapel_idmapel)
                ->where('siswa_idsiswa', $nilai->siswa_idsiswa)
                ->lockForUpdate()
                ->first();

            $nilaiAkhirLama = $nilaiAkhir ? (float) $nilaiAkhir->nilai_akhir : null;

            $nilaiAkhir ??= new NilaiAkhir([
                'semester_idsemester' => $nilai->semester_idsemester,
                'kelas_idkelas' => $nilai->kelas_idkelas,
                'mapel_idmapel' => $komponen->mapel_idmapel,
                'siswa_idsiswa' => $nilai->siswa_idsiswa,
            ]);
            $nilaiAkhir->nilai_akhir = $nilaiAkhirBaru;
            $nilaiAkhir->dihitung_pada = now();

            if ($nilaiAkhir->nilai_kktp === null) {
                $kktp = KktpMapel::query()
                    ->where('semester_idsemester', $nilai->semester_idsemester)
                    ->where('mapel_idmapel', $komponen->mapel_idmapel)
                    ->value('nilai_minimal');

                $nilaiAkhir->nilai_kktp = $kktp;
            }

            $nilaiAkhir->save();

            self::catatLog((int) $nilai->idnilai_siswa, null, $nilaiLama, $nilaiBaru, $alasan, $aktor);
            self::catatLog(null, (int) $nilaiAkhir->idnilai_akhir, $nilaiAkhirLama, $nilaiAkhirBaru, $alasan, $aktor);

            // Notifikasi 2B.6.3: siswa pemilik nilai yang dikoreksi (atomic dalam transaksi).
            $siswaUserId = Siswa::query()
                ->where('idsiswa', $nilai->siswa_idsiswa)
                ->value('user_iduser');

            if ($siswaUserId) {
                NotifikasiService::buat(
                    [(int) $siswaUserId],
                    [
                        'judul' => 'Nilai dikoreksi: '.$komponen->mapel->nama,
                        'isi' => 'Nilai Anda pada mata pelajaran '.$komponen->mapel->nama.' telah diperbarui melalui koreksi resmi.',
                        'jenis' => Notifikasi::JENIS_KOREKSI,
                        'prioritas' => 'tinggi',
                        'sumber_tabel' => 'nilai_siswa',
                        'sumber_id' => $nilai->idnilai_siswa,
                        'link' => '/raporsiswa',
                    ],
                    $aktor
                );
            }

            return $nilai->fresh(['komponenNilai', 'siswa']);
        });
    }

    /**
     * Koreksi KKTP sebuah (semester, mapel) beserta snapshot nilai akhir
     * yang sudah terlanjur difinalisasi.
     */
    public static function koreksiKKTP(User $aktor, int $idKktpMapel, mixed $nilaiBaru, ?string $alasan): int
    {
        abort_unless($aktor->level === 'superadmin', 403);

        $alasan = self::validasiAlasan($alasan);
        $nilaiBaru = NilaiSiswaService::validasiNilai($nilaiBaru);

        return DB::transaction(function () use ($aktor, $idKktpMapel, $nilaiBaru, $alasan) {
            $kktp = KktpMapel::query()->lockForUpdate()->find($idKktpMapel);

            if (! $kktp) {
                throw new DomainException('Konfigurasi KKTP tidak ditemukan.');
            }

            $snapshot = NilaiAkhir::query()
                ->where('semester_idsemester', $kktp->semester_idsemester)
                ->where('mapel_idmapel', $kktp->mapel_idmapel)
                ->lockForUpdate()
                ->get();

            if ($snapshot->isEmpty()) {
                throw new DomainException('Koreksi KKTP melalui jalur ini hanya untuk rapor yang sudah difinalisasi. Gunakan halaman konfigurasi KKTP untuk semester berjalan.');
            }

            $nilaiLama = (float) $kktp->nilai_minimal;
            $kktp->nilai_minimal = $nilaiBaru;
            $kktp->save();

            foreach ($snapshot as $baris) {
                $lama = $baris->nilai_kktp === null ? null : (float) $baris->nilai_kktp;
                $baris->nilai_kktp = $nilaiBaru;
                $baris->save();

                self::catatLog(null, (int) $baris->idnilai_akhir, $lama, $nilaiBaru, $alasan, $aktor);
            }

            // Notifikasi 2B.6.3: seluruh siswa terdampak koreksi KKTP (atomic).
            $siswaTerdampak = $snapshot->pluck('siswa_idsiswa')->unique()->values()->all();
            $siswaUserIds = Siswa::query()
                ->whereIn('idsiswa', $siswaTerdampak)
                ->pluck('user_iduser')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($siswaUserIds !== []) {
                NotifikasiService::buat(
                    $siswaUserIds,
                    [
                        'judul' => 'KKTP dikoreksi: '.$kktp->mapel->nama,
                        'isi' => 'KKTP mata pelajaran '.$kktp->mapel->nama.' telah diperbarui; nilai akhir Anda ikut terdampak.',
                        'jenis' => Notifikasi::JENIS_KOREKSI,
                        'prioritas' => 'tinggi',
                        'sumber_tabel' => 'kktp_mapel',
                        'sumber_id' => $kktp->idkktp_mapel,
                        'link' => '/raporsiswa',
                    ],
                    $aktor
                );
            }

            return $snapshot->count();
        });
    }

    /**
     * Nilai lama/baru untuk ditampilkan pada log; keduanya dapat null pada
     * komponen tertentu (mis. awal koreksi nilai akhir yang baru dibuat).
     */
    private static function catatLog(?int $nilaiSiswaId, ?int $nilaiAkhirId, ?float $nilaiLama, ?float $nilaiBaru, string $alasan, User $aktor): void
    {
        if (($nilaiSiswaId === null) === ($nilaiAkhirId === null)) {
            throw new DomainException('Log perubahan nilai harus menunjuk tepat satu target.');
        }

        NilaiPerubahanLog::create([
            'nilai_siswa_idnilai_siswa' => $nilaiSiswaId,
            'nilai_akhir_idnilai_akhir' => $nilaiAkhirId,
            'nilai_lama' => $nilaiLama,
            'nilai_baru' => $nilaiBaru,
            'alasan' => $alasan,
            'diubah_oleh' => $aktor->id,
        ]);
    }

    private static function validasiAlasan(?string $alasan): string
    {
        $alasan = trim((string) $alasan);

        if ($alasan === '') {
            throw new DomainException('Alasan koreksi wajib diisi.');
        }

        if (mb_strlen($alasan) > 1000) {
            throw new DomainException('Alasan koreksi maksimal 1000 karakter.');
        }

        return $alasan;
    }
}
