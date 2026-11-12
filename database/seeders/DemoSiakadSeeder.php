<?php

namespace Database\Seeders;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\Appmenucat;
use App\Models\CatatanRapor;
use App\Models\FinalisasiRapor;
use App\Models\Guru;
use App\Models\Jamhari;
use App\Models\JadwalKelas;
use App\Models\JadwalPertemuan;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\KomponenNilai;
use App\Models\KktpMapel;
use App\Models\Mapel;
use App\Models\NilaiAkhir;
use App\Models\NilaiSiswa;
use App\Models\Notifikasi;
use App\Models\NotifikasiPenerima;
use App\Models\Pengampu;
use App\Models\Pengumuman;
use App\Models\PenugasanMengajar;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Models\Usermenu;
use App\Models\WaliakunHistory;
use App\Models\Walimurid;
use App\Models\WalimuridHistory;
use App\Models\WalimuridPermintaanPutus;
use App\Services\PaketMenuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data dummy DEMO untuk SIAKAD.
 * Prefix konsisten: DEMO_SIAKAD_.
 * Idempoten: menggunakan firstOrCreate + prefix unik.
 */
class DemoSiakadSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'DEMO_SISWA_1')->exists()) {
            return; // idempoten: sudah di-seed
        }

        DB::transaction(function () {
            // ---- Tahun ajaran & semester ----
            $ta = Tahunajaran::firstOrCreate(['nama' => '2026/2027'], ['aktif' => true]);
            $sem = Semester::firstOrCreate(
                ['tahunajaran_idtahunajaran' => $ta->idtahunajaran, 'nama' => 'Ganjil'],
                ['aktif' => true]
            );

            // ---- Jurusan ----
            $jurusan = [];
            foreach (['IPA', 'IPS', 'Bahasa'] as $kode) {
                $jurusan[$kode] = Jurusan::firstOrCreate(['kode' => $kode], ['nama' => $kode, 'aktif' => true]);
            }

            // ---- Mapel ----
            $mapelData = [
                ['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'wajib'],
                ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'wajib'],
                ['kode' => 'BIG', 'nama' => 'Bahasa Inggris', 'kelompok' => 'wajib'],
                ['kode' => 'FIS', 'nama' => 'Fisika', 'kelompok' => 'pilihan'],
                ['kode' => 'BIO', 'nama' => 'Biologi', 'kelompok' => 'pilihan'],
            ];
            $mapels = [];
            foreach ($mapelData as $md) {
                $mapels[$md['kode']] = Mapel::firstOrCreate(['kode' => $md['kode']], $md);
            }

            // ---- Kelas ----
            $kelasList = [];
            foreach (['10-A', '10-B', '11-A'] as $i => $nama) {
                $kelasList[] = Kelas::firstOrCreate(
                    ['nama' => 'DEMO_'.$nama, 'tahunajaran_idtahunajaran' => $ta->idtahunajaran],
                    ['tingkat' => $i < 2 ? '10' : '11', 'jenis' => 'induk', 'jurusan_id' => $jurusan['IPA']->idjurusan, 'rombel' => $i + 1]
                );
            }

            // ---- Guru (3) ----
            $guruList = [];
            for ($i = 1; $i <= 3; $i++) {
                $u = User::firstOrCreate(
                    ['username' => 'DEMO_GURU_'.$i],
                    ['name' => "Guru Demo $i", 'level' => 'guru', 'status_akun' => 'aktif', 'password' => Hash::make('demo123'), 'wajib_ganti_password' => false]
                );
                $g = Guru::firstOrCreate(
                    ['user_iduser' => $u->id],
                    ['nip' => '1980010'.$i, 'nama' => "Guru Demo $i", 'jenis_kelamin' => $i % 2 ? 'L' : 'P', 'tanggal_lahir' => '1980-01-01', 'status' => 'aktif']
                );
                $guruList[] = $g;
            }

            // ---- Siswa (10) + User + Kelassiswa ----
            $siswaList = [];
            for ($i = 1; $i <= 10; $i++) {
                $u = User::firstOrCreate(
                    ['username' => 'DEMO_SISWA_'.$i],
                    ['name' => "Siswa Demo $i", 'level' => 'siswa', 'status_akun' => 'aktif', 'password' => Hash::make('demo123'), 'wajib_ganti_password' => false]
                );
                $s = Siswa::firstOrCreate(
                    ['user_iduser' => $u->id],
                    [
                        'nisn' => '10000000'.$i,
                        'nis' => 'NIS'.$i,
                        'nama' => "Siswa Demo $i",
                        'jenis_kelamin' => $i % 2 ? 'L' : 'P',
                        'tanggal_lahir' => '2008-01-'.str_pad($i, 2, '0', STR_PAD_LEFT),
                        'status' => 'aktif',
                    ]
                );
                $siswaList[] = $s;

                // place into class (round-robin)
                $kelas = $kelasList[($i - 1) % count($kelasList)];
                Kelassiswa::firstOrCreate(
                    ['kelas_idkelas' => $kelas->idkelas, 'siswa_idsiswa' => $s->idsiswa],
                    ['status' => 'aktif']
                );
            }

            // ---- Pengampu (5) ----
            $pengampuList = [];
            foreach (array_slice($mapels, 0, 5, true) as $kode => $m) {
                $g = $guruList[0];
                $k = $kelasList[0];
                $pengampuList[] = Pengampu::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $k->idkelas, 'mapel_idmapel' => $m->idmapel, 'guru_idguru' => $g->idguru],
                    []
                );
            }

            // ---- Penugasan mengajar ----
            foreach (array_slice($mapels, 0, 3, true) as $kode => $m) {
                PenugasanMengajar::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelasList[0]->idkelas, 'mapel_idmapel' => $m->idmapel, 'guru_idguru' => $guruList[0]->idguru],
                    []
                );
            }

            // ---- Guru mapel ----
            foreach (array_slice($mapels, 0, 3, true) as $kode => $m) {
                \App\Models\Gurumapel::firstOrCreate(['guru_idguru' => $guruList[0]->idguru, 'mapel_idmapel' => $m->idmapel], []);
            }

            // ---- Jamhari (per kelas, 6 jam) ----
            foreach ($kelasList as $k) {
                for ($j = 1; $j <= 6; $j++) {
                    $mulaiMenit = ($j - 1) * 50;
                    $selesaiMenit = $mulaiMenit + 50;
                    $mulaiJam = 7 + intdiv($mulaiMenit, 60);
                    $mulaiMen = $mulaiMenit % 60;
                    $selesaiJam = 7 + intdiv($selesaiMenit, 60);
                    $selesaiMen = $selesaiMenit % 60;
                    Jamhari::firstOrCreate(
                        ['hari' => 1, 'kelas_id' => $k->idkelas, 'jam_ke' => $j],
                        ['jenis' => 'pelajaran', 'mulai' => sprintf('%02d:%02d:00', $mulaiJam, $mulaiMen), 'selesai' => sprintf('%02d:%02d:00', $selesaiJam, $selesaiMen)]
                    );
                }
            }

            // ---- Jadwalkelas (some) ----
            $jamhariFirst = Jamhari::where('kelas_id', $kelasList[0]->idkelas)->where('jam_ke', 1)->first();
            if ($jamhariFirst) {
                JadwalKelas::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'jamhari_idjamhari' => $jamhariFirst->idjamhari, 'mapel_idmapel' => $mapels['MTK']->idmapel, 'guru_idguru' => $guruList[0]->idguru],
                    []
                );
            }

            // ---- Jadwal pertemuan (for absensi per jam) ----
            $jp = JadwalPertemuan::firstOrCreate(
                ['jadwalkelas_idjadwalkelas' => JadwalKelas::first()->idjadwalkelas ?? 1, 'tanggal' => now()->toDateString()],
                [
                    'semester_idsemester' => $sem->idsemester,
                    'kelas_idkelas' => $kelasList[0]->idkelas,
                    'guru_idguru' => $guruList[0]->idguru,
                    'mapel_idmapel' => $mapels['MTK']->idmapel,
                    'hari' => 1,
                    'waktu_mulai' => '07:00:00',
                    'waktu_selesai' => '07:50:00',
                    'status' => 'terjadwal',
                ]
            );

            // ---- Absensi harian (5 records) ----
            for ($i = 0; $i < 5; $i++) {
                $s = $siswaList[$i];
                AbsensiHarian::firstOrCreate(
                    ['tahunajaran_idtahunajaran' => $ta->idtahunajaran, 'semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelasList[0]->idkelas, 'tanggal' => now()->subDays($i)->toDateString(), 'siswa_idsiswa' => $s->idsiswa],
                    ['status' => $i === 0 ? 'hadir' : ($i === 1 ? 'sakit' : 'hadir'), 'diinput_oleh' => $guruList[0]->user_iduser]
                );
            }

            // ---- Absensi per jam (5 records) ----
            for ($i = 0; $i < 5; $i++) {
                $s = $siswaList[$i];
                AbsensiPerjam::firstOrCreate(
                    ['jadwal_pertemuan_idjadwal_pertemuan' => $jp->idjadwal_pertemuan, 'siswa_idsiswa' => $s->idsiswa],
                    ['status' => 'hadir', 'diinput_oleh' => $guruList[0]->user_iduser]
                );
            }

            // ---- Komponen nilai + nilai siswa + nilai akhir ----
            $komponen = KomponenNilai::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'mapel_idmapel' => $mapels['MTK']->idmapel, 'nama' => 'UH1'],
                ['bobot' => 30, 'urutan' => 1, 'aktif' => true]
            );
            foreach (array_slice($siswaList, 0, 5) as $s) {
                NilaiSiswa::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelasList[0]->idkelas, 'siswa_idsiswa' => $s->idsiswa, 'komponen_nilai_idkomponen_nilai' => $komponen->idkomponen_nilai],
                    ['nilai' => 80 + ($s->idsiswa % 10), 'guru_idguru' => $guruList[0]->idguru]
                );
                NilaiAkhir::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelasList[0]->idkelas, 'mapel_idmapel' => $mapels['MTK']->idmapel, 'siswa_idsiswa' => $s->idsiswa],
                    ['nilai_akhir' => 82.5, 'nilai_kktp' => 75, 'dihitung_pada' => now()]
                );
            }

            // ---- KKTP mapel ----
            KktpMapel::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'mapel_idmapel' => $mapels['MTK']->idmapel],
                ['nilai_minimal' => 75]
            );

            // ---- Finalisasi rapor ----
            $final = FinalisasiRapor::firstOrCreate(
                ['semester_idsemester' => $sem->idsemester, 'kelas_idkelas' => $kelasList[0]->idkelas],
                ['status' => 'final', 'wali_kelas_idguru' => $guruList[0]->idguru, 'difinalisasi_oleh' => 1, 'difinalisasi_pada' => now()]
            );

            // ---- Catatan rapor ----
            foreach (array_slice($siswaList, 0, 3) as $s) {
                CatatanRapor::firstOrCreate(
                    ['semester_idsemester' => $sem->idsemester, 'siswa_idsiswa' => $s->idsiswa],
                    ['catatan' => 'Catatan demo untuk '.$s->nama, 'guru_idguru' => $guruList[0]->idguru]
                );
            }

            // ---- Pengumuman ----
            $peng = Pengumuman::firstOrCreate(
                ['judul' => 'DEMO_PENGUMUMAN_1'],
                ['isi' => 'Pengumuman demo SIAKAD.', 'kategori' => 'akademik', 'prioritas' => 'sedang', 'status' => 'terbit', 'target' => 'semua', 'dibuat_oleh' => 1, 'waktu_terbit' => now(), 'berlaku_dari' => now()->toDateString()]
            );

            // ---- Notifikasi ----
            $notif = Notifikasi::firstOrCreate(
                ['judul' => 'DEMO_NOTIFIKASI_1'],
                ['isi' => 'Notifikasi demo.', 'jenis' => 'info', 'prioritas' => 'sedang', 'dibuat_oleh' => 1, 'waktu_dibuat' => now()]
            );
            foreach (array_slice($siswaList, 0, 3) as $s) {
                $u = User::find($s->user_iduser);
                if ($u) {
                    NotifikasiPenerima::firstOrCreate(['notifikasi_idnotifikasi' => $notif->idnotifikasi, 'user_iduser' => $u->id], []);
                }
            }

            // ---- Portal Wali Murid ----
            // Akun wali (3) — wali1, wali2, wali3
            $waliUsers = [];
            for ($i = 1; $i <= 3; $i++) {
                $wu = User::firstOrCreate(
                    ['username' => 'DEMO_WALI_'.$i],
                    ['name' => "Wali Demo $i", 'level' => 'wali', 'status_akun' => 'aktif', 'password' => Hash::make('demo123'), 'wajib_ganti_password' => false]
                );
                $waliUsers[] = $wu;
                WaliakunHistory::firstOrCreate(
                    ['user_iduser' => $wu->id, 'event' => 'akun_dibuat'],
                    ['aktor_id' => 1, 'aktor_peran' => 'superadmin', 'status_sesudah' => 'aktif', 'alasan' => 'demo', 'created_at' => now()]
                );
            }

            // Relasi wali-siswa verified (3)
            $relasiList = [];
            for ($i = 0; $i < 3; $i++) {
                $w = $waliUsers[$i];
                $s = $siswaList[$i];
                $r = Walimurid::firstOrCreate(
                    ['user_iduser' => $w->id, 'siswa_idsiswa' => $s->idsiswa],
                    [
                        'hubungan' => 'wali',
                        'status_hubungan' => 'verified',
                        'tanggal_mulai' => now()->toDateString(),
                        'tanggal_verifikasi' => now(),
                        'pengaju_id' => $w->id,
                        'verifikator_id' => 1,
                    ]
                );
                $relasiList[] = $r;
                WalimuridHistory::firstOrCreate(
                    ['walimurid_id' => $r->idwalimurid, 'event' => 'pengajuan'],
                    ['user_iduser' => $w->id, 'siswa_idsiswa' => $s->idsiswa, 'aktor_id' => $w->id, 'aktor_peran' => 'wali', 'status_sesudah' => 'pending', 'created_at' => now()]
                );
                WalimuridHistory::firstOrCreate(
                    ['walimurid_id' => $r->idwalimurid, 'event' => 'verifikasi'],
                    ['user_iduser' => $w->id, 'siswa_idsiswa' => $s->idsiswa, 'aktor_id' => 1, 'aktor_peran' => 'superadmin', 'status_sebelum' => 'pending', 'status_sesudah' => 'verified', 'created_at' => now()]
                );
            }

            // Permintaan putus (1) + histori
            $perm = WalimuridPermintaanPutus::firstOrCreate(
                ['walimurid_id' => $relasiList[0]->idwalimurid, 'status_permintaan' => 'menunggu'],
                ['diminta_oleh' => $waliUsers[0]->id, 'alasan' => 'Demo permintaan putus', 'created_at' => now()]
            );
            WalimuridHistory::firstOrCreate(
                ['walimurid_id' => $relasiList[0]->idwalimurid, 'event' => 'permintaan_putus'],
                ['user_iduser' => $waliUsers[0]->id, 'siswa_idsiswa' => $siswaList[0]->idsiswa, 'aktor_id' => $waliUsers[0]->id, 'aktor_peran' => 'wali', 'status_sebelum' => 'verified', 'status_sesudah' => 'verified', 'alasan' => 'Demo permintaan putus', 'created_at' => now()]
            );

            // Relasi pending (1) untuk antrean
            Walimurid::firstOrCreate(
                ['user_iduser' => $waliUsers[1]->id, 'siswa_idsiswa' => $siswaList[3]->idsiswa],
                [
                    'hubungan' => 'ibu',
                    'status_hubungan' => 'pending',
                    'tanggal_mulai' => now()->toDateString(),
                    'pengaju_id' => $waliUsers[1]->id,
                ]
            );
        });
    }
}
