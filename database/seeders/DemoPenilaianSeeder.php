<?php

namespace Database\Seeders;

use App\Models\CatatanRapor;
use App\Models\FinalisasiRapor;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\KktpMapel;
use App\Models\KomponenNilai;
use App\Models\Mapel;
use App\Models\NilaiAkhir;
use App\Models\NilaiPerubahanLog;
use App\Models\NilaiSiswa;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data penilaian demo (sintetis) untuk kelas X IPA 1 & XI IPA 1 — matematika & BIN.
 * Idempoten via firstOrCreate. Nilai akhir dihitung dengan bobot komponen yang sama
 * dengan data (UH1 30% + UH2 30% + UTS 40%).
 */
class DemoPenilaianSeeder extends Seeder
{
    public function run(): void
    {
        $superadminId = User::where('level', 'superadmin')->value('id');
        if (! $superadminId) {
            throw new \RuntimeException('DemoPenilaianSeeder: superadmin belum ada.');
        }

        $ta = Tahunajaran::where('nama', '2026/2027')->first();
        $sem = Semester::where('tahunajaran_idtahunajaran', $ta?->idtahunajaran)->where('nama', 'Ganjil')->first();
        $kelas = Kelas::where('nama', 'X IPA 1')->where('tahunajaran_idtahunajaran', $ta?->idtahunajaran)->first();
        $kelasXi = Kelas::where('nama', 'XI IPA 1')->where('tahunajaran_idtahunajaran', $ta?->idtahunajaran)->first();
        $mtk = Mapel::where('kode', 'MTK')->first();
        $bin = Mapel::where('kode', 'BIN')->first();
        $sari = Guru::where('kode_guru', 'G01')->first();
        $agus = Guru::where('kode_guru', 'G02')->first();
        if (! $ta || ! $sem || ! $kelas || ! $kelasXi || ! $mtk || ! $bin || ! $sari || ! $agus) {
            throw new \RuntimeException('DemoPenilaianSeeder: data akademik belum lengkap. Jalankan DemoAkademikSeeder lebih dulu.');
        }

        // Siswa X IPA 1 & XI IPA 1 (lewat kelassiswa)
        $siswaX = Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelas->idkelas)->where('status', 'aktif'))->get();
        $siswaXi = Siswa::whereHas('kelassiswa', fn ($q) => $q->where('kelas_idkelas', $kelasXi->idkelas)->where('status', 'aktif'))->get();

        // ---------- KKTP ----------
        foreach ([['MTK', 75], ['BIN', 75]] as [$kode, $nilai]) {
            KktpMapel::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'mapel_idmapel' => Mapel::where('kode', $kode)->value('idmapel')],
                ['nilai_minimal' => $nilai]
            );
        }

        // ---------- Komponen nilai (MTK: 30/30/40; BIN: UTS tunggal 100) ----------
        $komponen = [
            'MTK' => [
                ['UH1', 30.00, 1], ['UH2', 30.00, 2], ['UTS', 40.00, 3],
            ],
            'BIN' => [
                ['UTS', 100.00, 1],
            ],
        ];

        $komponenIds = [];
        foreach ($komponen as $kode => $defs) {
            $mapelId = Mapel::where('kode', $kode)->value('idmapel');
            foreach ($defs as [$nama, $bobot, $urutan]) {
                $komponenIds[$kode][$nama] = KomponenNilai::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'mapel_idmapel' => $mapelId, 'nama' => $nama],
                    ['bobot' => $bobot, 'urutan' => $urutan, 'aktif' => true]
                )->idkomponen_nilai;
            }
        }

        // ---------- Nilai siswa (untuk kelas yang diberikan) ----------
        // Nilai deterministik: 70-95 dari hash (siswa|mapel|komponen).
        $nilaiDeterministik = fn (int $siswaId, string $mapelKode, string $komponenNama, string $suffix = '') => 70 + (abs(crc32($siswaId.'|'.$mapelKode.'|'.$komponenNama.$suffix)) % 26);
        $seedNilaiKelas = function ($siswaCollection, $kelasObj, string $mapelKode) use ($nilaiDeterministik, $komponenIds, $sem, $mtk, $bin, $sari, $agus) {
            $mapelId = $mapelKode === 'MTK' ? $mtk->idmapel : $bin->idmapel;
            $guruId = $mapelKode === 'MTK' ? $sari->idguru : $agus->idguru;
            $rows = [];
            foreach ($siswaCollection as $s) {
                foreach ($komponenIds[$mapelKode] as $nama => $komponenId) {
                    $rows[$s->idsiswa][$nama] = NilaiSiswa::firstOrCreate(
                        [
                            'semester_idsemester' => $sem->idsemester,
                            'kelas_idkelas' => $kelasObj->idkelas,
                            'siswa_idsiswa' => $s->idsiswa,
                            'komponen_nilai_idkomponen_nilai' => $komponenId,
                        ],
                        ['nilai' => $nilaiDeterministik($s->idsiswa, $mapelKode, $nama, '|'.$kelasObj->idkelas), 'guru_idguru' => $guruId]
                    );
                }
            }

            return $rows;
        };

        $rowsX = $seedNilaiKelas($siswaX, $kelas, 'MTK');
        $rowsXi = $seedNilaiKelas($siswaXi, $kelasXi, 'MTK');

        // BIN hanya di kelas X IPA 1
        foreach ($siswaX as $s) {
            $binUts = NilaiSiswa::firstOrCreate(
                [
                    'semester_idsemester' => $sem->idsemester,
                    'kelas_idkelas' => $kelas->idkelas,
                    'siswa_idsiswa' => $s->idsiswa,
                    'komponen_nilai_idkomponen_nilai' => $komponenIds['BIN']['UTS'],
                ],
                ['nilai' => $nilaiDeterministik($s->idsiswa, 'BIN', 'UTS', '|'.$kelas->idkelas), 'guru_idguru' => $agus->idguru]
            );
            $rowsX[$s->idsiswa]['BIN-UTS'] = $binUts;

            NilaiAkhir::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelas->idkelas, 'mapel_idmapel' => $mtk->idmapel, 'siswa_idsiswa' => $s->idsiswa],
                [
                    'nilai_akhir' => round(($rowsX[$s->idsiswa]['UH1']->nilai * 0.3) + ($rowsX[$s->idsiswa]['UH2']->nilai * 0.3) + ($rowsX[$s->idsiswa]['UTS']->nilai * 0.4), 2),
                    'nilai_kktp' => 75,
                    'dihitung_pada' => now(),
                ]
            );
            NilaiAkhir::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelas->idkelas, 'mapel_idmapel' => $bin->idmapel, 'siswa_idsiswa' => $s->idsiswa],
                ['nilai_akhir' => $binUts->nilai, 'nilai_kktp' => 75, 'dihitung_pada' => now()]
            );
        }

        foreach ($siswaXi as $s) {
            NilaiAkhir::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelasXi->idkelas, 'mapel_idmapel' => $mtk->idmapel, 'siswa_idsiswa' => $s->idsiswa],
                [
                    'nilai_akhir' => round(($rowsXi[$s->idsiswa]['UH1']->nilai * 0.3) + ($rowsXi[$s->idsiswa]['UH2']->nilai * 0.3) + ($rowsXi[$s->idsiswa]['UTS']->nilai * 0.4), 2),
                    'nilai_kktp' => 75,
                    'dihitung_pada' => now(),
                ]
            );
        }

        // ---------- Nilai perubahan log (satu contoh koreksi demo) ----------
        $nilaiPertama = NilaiSiswa::where('kelas_idkelas', $kelas->idkelas)->whereHas('siswa', fn ($q) => $q->orderBy('idsiswa'))->orderBy('idnilai_siswa')->first();
        if ($nilaiPertama) {
            NilaiPerubahanLog::firstOrCreate(
                ['nilai_siswa_idnilai_siswa' => $nilaiPertama->idnilai_siswa, 'diubah_oleh' => $superadminId, 'alasan' => 'Perbaikan input nilai (contoh demo)'],
                ['nilai_lama' => $nilaiPertama->nilai, 'nilai_baru' => min(100, $nilaiPertama->nilai + 5)]
            );
        }

        // ---------- Finalisasi rapor X IPA 1 ----------
        FinalisasiRapor::firstOrCreate(
            ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelas->idkelas],
            ['status' => 'final', 'wali_kelas_idguru' => $sari->idguru, 'difinalisasi_oleh' => $superadminId, 'difinalisasi_pada' => now()]
        );

        // ---------- Catatan rapor ----------
        $catatan = [
            'Ikut lomba cerdas cermat tingkat sekolah.',
            'Aktif berdiskusi saat pembelajaran matematika.',
            'Perlu meningkatkan ketelitian dalam berhitung.',
            'Siswa teladan semester ini.',
        ];
        foreach (array_slice($siswaX->all(), 0, 4) as $i => $s) {
            CatatanRapor::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'siswa_idsiswa' => $s->idsiswa, 'guru_idguru' => $sari->idguru],
                ['catatan' => $catatan[$i]]
            );
        }

        $this->command?->info('DemoPenilaianSeeder: komponen, nilai, nilai akhir, finalisasi & catatan rapor selesai.');
    }
}
