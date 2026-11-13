<?php

namespace Database\Seeders;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\Guru;
use App\Models\Gurumapel;
use App\Models\JadwalKelas;
use App\Models\JadwalPertemuan;
use App\Models\Jamhari;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\PenugasanMengajar;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data induk + akademik demo (sintetis, prefix DEMO-).
 * Idempoten: seluruh insert lewat firstOrCreate; tidak ada UPDATE/DELETE data existing.
 */
class DemoAkademikSeeder extends Seeder
{
    // 60 nama siswa Indonesia yang wajar & sintetis: [nama, jenis_kelamin, agama]
    private const SISWA_PER_KELAS = [
        'X IPA 1' => [
            ['Andre Santoso', 'L', 'Islam'], ['Ayu Lestari', 'P', 'Islam'], ['Bayu Nugroho', 'L', 'Islam'],
            ['Citra Anggraini', 'P', 'Kristen'], ['Dimas Pratama', 'L', 'Islam'], ['Erika Putri', 'P', 'Islam'],
            ['Farhan Maulana', 'L', 'Islam'], ['Gita Permata', 'P', 'Islam'], ['Rizky Ramadhan', 'L', 'Islam'], ['Sinta Dewi', 'P', 'Islam'],
        ],
        'X IPS 1' => [
            ['Dika Saputra', 'L', 'Islam'], ['Yoga Saputra', 'L', 'Islam'], ['Nabila Azzahra', 'P', 'Islam'],
            ['Rian Firmansyah', 'L', 'Islam'], ['Tania Maharani', 'P', 'Kristen'], ['Fajar Nugroho', 'L', 'Islam'],
            ['Lestari Wulandari', 'P', 'Islam'], ['Rangga Wijaya', 'L', 'Islam'], ['Dinda Amelia', 'P', 'Islam'], ['Arif Ramadhan', 'L', 'Islam'],
        ],
        'XI IPA 1' => [
            ['Putri Rahma', 'P', 'Islam'], ['Ilham Firdaus', 'L', 'Islam'], ['Nia Kurniasih', 'P', 'Islam'],
            ['Galih Saputra', 'L', 'Hindu'], ['Ratih Safitri', 'P', 'Islam'], ['Faqih Ramadhan', 'L', 'Islam'],
            ['Anisa Kirana', 'P', 'Islam'], ['Fajar Laksana', 'L', 'Islam'], ['Melati Puspita', 'P', 'Buddha'], ['Yusuf Maulana', 'L', 'Islam'],
        ],
        'XI IPS 1' => [
            ['Bima Wijaya', 'L', 'Islam'], ['Larasati Dewi', 'P', 'Islam'], ['Iqbal Ramadhan', 'L', 'Islam'],
            ['Salsabila Putri', 'P', 'Islam'], ['Rayhan Firdaus', 'L', 'Islam'], ['Dhea Ananda', 'P', 'Kristen'],
            ['Zidan Hafiz', 'L', 'Islam'], ['Farah Azizah', 'P', 'Islam'], ['Rafi Syahputra', 'L', 'Islam'], ['Kirana Dewi', 'P', 'Islam'],
        ],
        'XII IPA 1' => [
            ['Raka Ramadhan', 'L', 'Islam'], ['Galih Prasetyo', 'L', 'Islam'], ['Bela Safitri', 'P', 'Islam'],
            ['Naufal Rizki', 'L', 'Islam'], ['Salma Azzahra', 'P', 'Islam'], ['Fikri Maulana', 'L', 'Islam'],
            ['Intan Permata', 'P', 'Islam'], ['Aldo Pratama', 'L', 'Katolik'], ['Nabila Kirana', 'P', 'Islam'], ['Zaki Mubarak', 'L', 'Islam'],
        ],
        'XII IPS 1' => [
            ['Salsa Marlina', 'P', 'Islam'], ['Akbar Firmansyah', 'L', 'Islam'], ['Tata Wulandari', 'P', 'Islam'],
            ['Rifqi Ramadhan', 'L', 'Islam'], ['Alya Maharani', 'P', 'Islam'], ['Danu Saputra', 'L', 'Islam'],
            ['Karin Wijaya', 'P', 'Kristen'], ['Bayu Firmansyah', 'L', 'Islam'], ['Sekar Arum', 'P', 'Islam'], ['Ridho Pratama', 'L', 'Islam'],
        ],
    ];

    private const GURU = [
        // [kode, nama, jenis_kelamin, mapel_codes, wali_kelas?]
        ['G01', 'Sari Wulandari', 'P', ['MTK'], 'X IPA 1'],
        ['G02', 'Agus Prasetyo', 'L', ['BIN'], 'X IPS 1'],
        ['G03', 'Nadia Ramadhani', 'P', ['BIG'], null],
        ['G04', 'Dewi Lestari', 'P', ['PKN'], null],
        ['G05', 'Rizky Hidayat', 'L', ['SEJ', 'GEO'], 'XII IPS 1'],
        ['G06', 'Maya Pratiwi', 'P', ['FIS'], null],
        ['G07', 'Budi Santoso', 'L', ['BIO'], 'XI IPA 1'],
        ['G08', 'Sinta Dewi', 'P', ['EKO'], 'XI IPS 1'],
        ['G09', 'Andi Kurnia', 'L', ['KIM'], 'XII IPA 1'],
    ];

    private const MAPEL = [
        ['MTK', 'Matematika', 'wajib'], ['BIN', 'Bahasa Indonesia', 'wajib'], ['BIG', 'Bahasa Inggris', 'wajib'],
        ['PKN', 'Pendidikan Pancasila', 'wajib'], ['SEJ', 'Sejarah', 'wajib'],
        ['FIS', 'Fisika', 'pilihan'], ['BIO', 'Biologi', 'pilihan'], ['KIM', 'Kimia', 'pilihan'],
        ['GEO', 'Geografi', 'pilihan'], ['EKO', 'Ekonomi', 'pilihan'],
    ];

    // mapel per kelas (selain 5 wajib)
    private const PILIHAN = [
        'X IPA 1' => ['FIS', 'BIO', 'KIM'], 'XI IPA 1' => ['FIS', 'BIO', 'KIM'], 'XII IPA 1' => ['FIS', 'BIO', 'KIM'],
        'X IPS 1' => ['GEO', 'EKO'], 'XI IPS 1' => ['GEO', 'EKO'], 'XII IPS 1' => ['GEO', 'EKO'],
    ];

    // Slot jadwal per kelas (hanya 2 kelas yang punya jadwal penuh untuk contoh absensi per jam)
    // [mapel, hari, jam_ke]
    private const JADWAL_SLOTS = [
        'X IPA 1' => [['MTK', 1, 1], ['BIN', 1, 2], ['FIS', 2, 1], ['BIO', 2, 3], ['MTK', 3, 1], ['BIG', 3, 2], ['FIS', 4, 1], ['BIN', 4, 2], ['BIO', 5, 3], ['BIG', 5, 2]],
        'XI IPA 1' => [['MTK', 1, 1], ['FIS', 1, 2], ['BIN', 2, 2], ['BIO', 2, 3], ['BIG', 3, 2], ['MTK', 3, 1], ['BIN', 4, 2], ['FIS', 4, 1], ['BIG', 5, 2], ['BIO', 5, 3]],
    ];

    public function run(): void
    {
        $superadminId = User::where('level', 'superadmin')->value('id');
        if (! $superadminId) {
            throw new \RuntimeException('DemoAkademikSeeder: superadmin belum ada. Jalankan DatabaseSeeder lebih dulu.');
        }

        // ---------- TA & semester ----------
        $ta = Tahunajaran::firstOrCreate(['nama' => '2026/2027'], ['aktif' => true]);
        $sem = Semester::firstOrCreate(
            ['tahunajaran_idtahunajaran' => $ta->idtahunajaran, 'nama' => 'Ganjil'],
            ['aktif' => true]
        );

        // ---------- Jurusan ----------
        $jurusan = [];
        foreach ([['IPA', 'Ilmu Pengetahuan Alam'], ['IPS', 'Ilmu Pengetahuan Sosial'], ['BAHASA', 'Bahasa']] as [$kode, $nama]) {
            $jurusan[$kode] = Jurusan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'aktif' => true]);
        }

        // ---------- Mapel ----------
        $mapels = [];
        foreach (self::MAPEL as [$kode, $nama, $kelompok]) {
            $mapels[$kode] = Mapel::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kelompok' => $kelompok]);
        }

        // ---------- Kelas ----------
        $kelasDef = [
            ['X IPA 1', '10', 'IPA', 1, 2026], ['X IPS 1', '10', 'IPS', 1, 2026],
            ['XI IPA 1', '11', 'IPA', 1, 2025], ['XI IPS 1', '11', 'IPS', 1, 2025],
            ['XII IPA 1', '12', 'IPA', 1, 2024], ['XII IPS 1', '12', 'IPS', 1, 2024],
        ];
        $kelas = [];
        foreach ($kelasDef as [$nama, $tingkat, $jurKode, $rombel, $thMasuk]) {
            $kelas[$nama] = Kelas::firstOrCreate(
                ['nama' => $nama, 'tahunajaran_idtahunajaran' => $ta->idtahunajaran],
                ['tingkat' => $tingkat, 'jenis' => 'induk', 'jurusan_id' => $jurusan[$jurKode]->idjurusan, 'rombel' => $rombel]
            );
        }

        // ---------- Guru + user ----------
        $gurus = [];
        foreach (self::GURU as $i => $def) {
            [$kode, $nama, $jk, $mapelCodes, $waliKelas] = $def;
            $slug = $this->slug($nama);
            $u = User::firstOrCreate(
                ['username' => $slug],
                ['name' => $nama, 'email' => $slug.'@demo.sekolah.test', 'password' => Hash::make('demo123'), 'level' => 'guru', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]
            );
            $g = Guru::firstOrCreate(
                ['user_iduser' => $u->id],
                [
                    'nip' => '19850'.str_pad((string) ($i + 11), 2, '0', STR_PAD_LEFT).'2010121'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'kode_guru' => $kode,
                    'nama' => $nama,
                    'jenis_kelamin' => $jk,
                    'tempat_lahir' => 'Kota Demo',
                    'tanggal_lahir' => '1985-0'.($i + 1).'-12',
                    'telepon' => '0812'.str_pad((string) (770000000 + $i), 10, '0', STR_PAD_LEFT),
                    'alamat' => 'Jl. Kenanga No. '.($i + 3).' RT 02/RW 03, Kota Demo 40123',
                    'status' => 'aktif',
                ]
            );
            $gurus[$kode] = $g;
        }

        // wali kelas — hanya isi bila kelas belum punya wali (jangan ubah data existing)
        foreach (self::GURU as [$kode, $nama, $jk, $mapelCodes, $waliKelas]) {
            if ($waliKelas && isset($kelas[$waliKelas]) && is_null($kelas[$waliKelas]->guru_idguru)) {
                $kelas[$waliKelas]->forceFill(['guru_idguru' => $gurus[$kode]->idguru])->save();
            }
        }

        // ---------- Siswa + user + kelassiswa ----------
        $seq = 0;
        $students = [];
        foreach (self::SISWA_PER_KELAS as $kelasNama => $rows) {
            $kelasId = $kelas[$kelasNama]->idkelas;
            $thMasuk = $kelasDef[array_search($kelasNama, array_column($kelasDef, 0))][4];
            foreach ($rows as [$nama, $jk, $agama]) {
                $seq++;
                $slug = $this->slug($nama);
                $username = $slug.'.'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $u = User::firstOrCreate(
                    ['username' => $username],
                    ['name' => $nama, 'email' => $username.'@demo.sekolah.test', 'password' => Hash::make('demo123'), 'level' => 'siswa', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]
                );
                $s = Siswa::firstOrCreate(
                    ['user_iduser' => $u->id],
                    [
                        'nisn' => '006'.str_pad((string) $seq, 7, '0', STR_PAD_LEFT),
                        'nis' => '2026'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                        'nik' => '320100'.str_pad((string) $seq, 10, '0', STR_PAD_LEFT),
                        'nama' => $nama,
                        'jenis_kelamin' => $jk,
                        'tempat_lahir' => 'Kota Demo',
                        'tanggal_lahir' => ($thMasuk === 2026 ? '2010' : ($thMasuk === 2025 ? '2009' : '2008')).'-0'.(($seq % 9) + 1).'-15',
                        'agama' => $agama,
                        'alamat' => 'Jl. Melati No. '.($seq + 10).' RT 0'.(($seq % 8) + 1).'/RW 0'.(($seq % 4) + 1).', Kota Demo 40123',
                        'telepon_siswa' => '0813'.str_pad((string) (880000000 + $seq), 10, '0', STR_PAD_LEFT),
                        'email' => $username.'@demo.sekolah.test',
                        'telepon_ortu' => '0812'.str_pad((string) (990000000 + $seq), 10, '0', STR_PAD_LEFT),
                        'tahun_masuk' => $thMasuk,
                        'asal_sekolah' => 'SMP Demo '.($seq % 5 + 1),
                        'nama_ayah' => 'Ayah '.$nama,
                        'nama_ibu' => 'Ibu '.$nama,
                        'status' => 'aktif',
                    ]
                );
                $students[] = $s;

                Kelassiswa::firstOrCreate(
                    ['kelas_idkelas' => $kelasId, 'siswa_idsiswa' => $s->idsiswa],
                    ['status' => 'aktif']
                );
            }
        }

        // ---------- Gurumapel ----------
        foreach (self::GURU as [$kode, , , $mapelCodes]) {
            foreach ($mapelCodes as $mk) {
                Gurumapel::firstOrCreate(['guru_idguru' => $gurus[$kode]->idguru, 'mapel_idmapel' => $mapels[$mk]->idmapel]);
            }
        }

        // ---------- Penugasan mengajar + Pengampu (untuk 2 kelas jadwal penuh) ----------
        $mapelCodesPerJurusan = self::PILIHAN;
        $guruForMapel = ['MTK' => 'G01', 'BIN' => 'G02', 'BIG' => 'G03', 'PKN' => 'G04', 'SEJ' => 'G05', 'FIS' => 'G06', 'BIO' => 'G07', 'KIM' => 'G09', 'GEO' => 'G05', 'EKO' => 'G08'];
        foreach (array_keys(self::SISWA_PER_KELAS) as $kelasNama) {
            $PILIHAN = $mapelCodesPerJurusan[$kelasNama] ?? [];
            $all = ['MTK', 'BIN', 'BIG', 'PKN', 'SEJ', ...$PILIHAN];
            foreach ($all as $mk) {
                PenugasanMengajar::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelas[$kelasNama]->idkelas, 'mapel_idmapel' => $mapels[$mk]->idmapel, 'guru_idguru' => $gurus[$guruForMapel[$mk]]->idguru]
                );
            }
        }

        // ---------- Jamhari (X IPA 1 & XI IPA 1; hari 1-5, 6 jam + 1 istirahat) ----------
        $jamDef = [
            [1, '07:00:00', '07:40:00'], [2, '07:50:00', '08:30:00'], [3, '08:40:00', '09:20:00'],
            [4, '09:45:00', '10:25:00'], [5, '10:35:00', '11:15:00'], [6, '11:25:00', '12:05:00'],
        ];
        $jamhari = [];
        foreach (['X IPA 1', 'XI IPA 1'] as $kelasNama) {
            $kelasId = $kelas[$kelasNama]->idkelas;
            for ($hari = 1; $hari <= 5; $hari++) {
                foreach ($jamDef as [$jamKe, $mulai, $selesai]) {
                    $jamhari[$kelasNama][$hari][$jamKe] = Jamhari::firstOrCreate(
                        ['hari' => $hari, 'kelas_id' => $kelasId, 'jam_ke' => $jamKe],
                        ['jenis' => 'pelajaran', 'mulai' => $mulai, 'selesai' => $selesai]
                    );
                }
                Jamhari::firstOrCreate(
                    ['hari' => $hari, 'kelas_id' => $kelasId, 'jam_ke' => null],
                    ['jenis' => 'istirahat', 'mulai' => '09:20:00', 'selesai' => '09:45:00', 'keterangan' => 'Istirahat']
                );
            }
        }

        // ---------- Jadwalkelas + Pengampu (per semester, unik per jamhari) ----------
        $tanggalHari = [
            1 => ['2026-10-26', '2026-11-02', '2026-11-16'],
            2 => ['2026-10-27', '2026-11-10', '2026-11-17'],
            3 => ['2026-10-28', '2026-11-11', '2026-11-18'],
            4 => ['2026-10-29', '2026-11-12', '2026-11-19'],
            5 => ['2026-10-30', '2026-11-13', '2026-11-20'],
        ];
        foreach (self::JADWAL_SLOTS as $kelasNama => $slots) {
            foreach ($slots as [$mk, $hari, $jamKe]) {
                $mkId = $mapels[$mk]->idmapel;
                $gId = $gurus[$guruForMapel[$mk]]->idguru;
                $jk = JadwalKelas::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'jamhari_idjamhari' => $jamhari[$kelasNama][$hari][$jamKe]->idjamhari],
                    ['mapel_idmapel' => $mkId, 'guru_idguru' => $gId]
                );

                Pengampu::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelas[$kelasNama]->idkelas, 'mapel_idmapel' => $mkId],
                    ['guru_idguru' => $gId]
                );

                // Pertemuan: 2 selesai (masa lalu) + 1 terjadwal (masa depan)
                foreach ([0, 1] as $idx) {
                    $tgl = $tanggalHari[$hari][$idx];
                    $jp = JadwalPertemuan::firstOrCreate(
                        ['jadwalkelas_idjadwalkelas' => $jk->idjadwalkelas, 'tanggal' => $tgl],
                        [
                            'semester_idsemester' => $sem->idsemester,
                            'kelas_idkelas' => $kelas[$kelasNama]->idkelas,
                            'guru_idguru' => $gId,
                            'mapel_idmapel' => $mkId,
                            'hari' => $hari,
                            'jam_ke' => $jamKe,
                            'waktu_mulai' => $jamhari[$kelasNama][$hari][$jamKe]->mulai,
                            'waktu_selesai' => $jamhari[$kelasNama][$hari][$jamKe]->selesai,
                            'status' => 'selesai',
                        ]
                    );
                    // Absensi per jam untuk pertemuan selesai: siswa kelas hadir, 1-2 alpha/sakit
                    $studentsInClass = array_values(array_filter($students, fn ($s) => Kelassiswa::where('kelas_idkelas', $kelas[$kelasNama]->idkelas)->where('siswa_idsiswa', $s->idsiswa)->exists()));
                    foreach ($studentsInClass as $i => $siswa) {
                        $status = ($i === 3 && $tanggalHari[$hari][$idx] === '2026-10-27') ? 'alpha' : (($i === 7 && $tanggalHari[$hari][$idx] === '2026-10-27') ? 'sakit' : 'hadir');
                        AbsensiPerjam::firstOrCreate(
                            ['jadwal_pertemuan_idjadwal_pertemuan' => $jp->idjadwal_pertemuan, 'siswa_idsiswa' => $siswa->idsiswa],
                            ['status' => $status, 'diinput_oleh' => $superadminId]
                        );
                    }
                }
                // pertemuan mendatang
                $tglUpcoming = $tanggalHari[$hari][2];
                JadwalPertemuan::firstOrCreate(
                    ['jadwalkelas_idjadwalkelas' => $jk->idjadwalkelas, 'tanggal' => $tglUpcoming],
                    [
                        'semester_idsemester' => $sem->idsemester,
                        'kelas_idkelas' => $kelas[$kelasNama]->idkelas,
                        'guru_idguru' => $gId,
                        'mapel_idmapel' => $mkId,
                        'hari' => $hari,
                        'jam_ke' => $jamKe,
                        'waktu_mulai' => $jamhari[$kelasNama][$hari][$jamKe]->mulai,
                        'waktu_selesai' => $jamhari[$kelasNama][$hari][$jamKe]->selesai,
                        'status' => 'terjadwal',
                    ]
                );
            }
        }

        // ---------- Absensi harian (X IPA 1, 10 hari terakhir hari sekolah) ----------
        $hariSekolah = ['2026-10-19', '2026-10-20', '2026-10-21', '2026-10-22', '2026-10-23', '2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29', '2026-10-30'];
        $studentsX = array_values(array_filter($students, fn ($s) => Kelassiswa::where('kelas_idkelas', $kelas['X IPA 1']->idkelas)->where('siswa_idsiswa', $s->idsiswa)->exists()));
        foreach ($hariSekolah as $dIdx => $tanggal) {
            foreach ($studentsX as $i => $siswa) {
                $status = 'hadir';
                $catatan = null;
                if ($i === 2 && $dIdx === 2) {
                    $status = 'sakit';
                    $catatan = 'Demam';
                } elseif ($i === 6 && $dIdx === 3) {
                    $status = 'sakit';
                    $catatan = 'Flu';
                } elseif ($i === 5 && $dIdx === 5) {
                    $status = 'izin';
                    $catatan = 'Acara keluarga';
                } elseif ($i === 7 && $dIdx === 7) {
                    $status = 'alpha';
                    $catatan = null;
                }
                AbsensiHarian::firstOrCreate(
                    ['tahunajaran_idtahunajaran' => $ta->idtahunajaran, 'semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelas['X IPA 1']->idkelas, 'tanggal' => $tanggal, 'siswa_idsiswa' => $siswa->idsiswa],
                    ['status' => $status, 'catatan' => $catatan, 'diinput_oleh' => $superadminId]
                );
            }
        }

        $this->command?->info('DemoAkademikSeeder: TA 2026/2027, 6 kelas, 9 guru, 60 siswa, absensi & jadwal selesai.');
    }

    private function slug(string $nama): string
    {
        $base = strtolower(str_replace(' ', '.', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nama)));

        return preg_replace('/[^a-z0-9.]+/', '', $base);
    }
}
