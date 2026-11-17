<?php

namespace Tests\Feature\Api\V1;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\CatatanRapor;
use App\Models\FinalisasiRapor;
use App\Models\Guru;
use App\Models\JadwalKelas;
use App\Models\JadwalPertemuan;
use App\Models\Jamhari;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\KomponenNilai;
use App\Models\Mapel;
use App\Models\NilaiAkhir;
use App\Models\NilaiSiswa;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 2 — Student API (read-only akademik).
 * Berjalan dalam satu transaksi per test (rollback) — data fixture tidak tersisa.
 */
class StudentApiTest extends TestCase
{
    private Tahunajaran $ta;

    private Semester $semAktif;

    private Semester $semGenap;

    private Guru $waliKelas;

    private Kelas $kelas;

    private Kelas $kelasB;

    private Mapel $mtk;

    private Mapel $bin;

    private User $siswaA;

    private Siswa $profilA;

    private User $siswaB;

    private Siswa $profilB;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        DB::beginTransaction();
        $this->bangunFixture();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function bangunFixture(): void
    {
        // Nonaktifkan konteks eksisting agar fixture yang jadi aktif (rollback di akhir).
        Tahunajaran::query()->where('aktif', true)->update(['aktif' => false]);
        Semester::query()->where('aktif', true)->update(['aktif' => false]);

        $this->ta = Tahunajaran::create(['nama' => 'UJI-TA-FASE2', 'aktif' => true]);
        $this->semAktif = Semester::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'Ganjil', 'aktif' => true]);
        $this->semGenap = Semester::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'Genap', 'aktif' => false]);

        // Guru wali kelas + mapel
        [$guruUser, $this->waliKelas] = $this->buatGuru('wali');

        $this->mtk = Mapel::create(['kode' => 'UJI-MTK', 'nama' => 'Matematika Uji', 'kelompok' => 'wajib']);
        $this->bin = Mapel::create(['kode' => 'UJI-BIN', 'nama' => 'B. Indonesia Uji', 'kelompok' => 'wajib']);

        $this->kelas = Kelas::create([
            'tahunajaran_idtahunajaran' => $this->ta->idtahunajaran,
            'nama' => 'UJI-10A',
            'tingkat' => '10',
            'jenis' => 'induk',
            'rombel' => 1,
            'guru_idguru' => $this->waliKelas->idguru,
        ]);
        $this->kelasB = Kelas::create([
            'tahunajaran_idtahunajaran' => $this->ta->idtahunajaran,
            'nama' => 'UJI-11B',
            'tingkat' => '11',
            'jenis' => 'induk',
            'rombel' => 2,
            'guru_idguru' => $this->waliKelas->idguru,
        ]);

        [$this->siswaA, $this->profilA] = $this->buatSiswa('A', 'Siswa Uji A');
        [$this->siswaB, $this->profilB] = $this->buatSiswa('B', 'Siswa Uji B');

        Kelassiswa::create(['kelas_idkelas' => $this->kelas->idkelas, 'siswa_idsiswa' => $this->profilA->idsiswa, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelas->idkelas, 'siswa_idsiswa' => $this->profilB->idsiswa, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasB->idkelas, 'siswa_idsiswa' => $this->profilB->idsiswa, 'status' => 'aktif']);

        // Jadwal: hari ini (MTK & BIN), besok (MTK)
        $hariIni = now()->dayOfWeekIso;
        $besok = now()->addDay()->dayOfWeekIso;

        $jam1 = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $this->kelas->idkelas, 'jam_ke' => 1, 'jenis' => 'kbm', 'mulai' => '07:00:00', 'selesai' => '07:45:00']);
        $jam2 = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $this->kelas->idkelas, 'jam_ke' => 2, 'jenis' => 'kbm', 'mulai' => '07:45:00', 'selesai' => '08:30:00']);
        $jamBesok = Jamhari::create(['hari' => $besok, 'kelas_id' => $this->kelas->idkelas, 'jam_ke' => 1, 'jenis' => 'kbm', 'mulai' => '07:00:00', 'selesai' => '07:45:00']);

        JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jam1->idjamhari, 'mapel_idmapel' => $this->mtk->idmapel, 'guru_idguru' => $this->waliKelas->idguru]);
        JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jam2->idjamhari, 'mapel_idmapel' => $this->bin->idmapel, 'guru_idguru' => $this->waliKelas->idguru]);
        JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jamBesok->idjamhari, 'mapel_idmapel' => $this->mtk->idmapel, 'guru_idguru' => $this->waliKelas->idguru]);

        // Pertemuan hari ini (kanonis untuk absensi per jam)
        $jpMtk = JadwalPertemuan::create([
            'jadwalkelas_idjadwalkelas' => JadwalKelas::query()->where('jamhari_idjamhari', $jam1->idjamhari)->value('idjadwalkelas'),
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelas->idkelas,
            'guru_idguru' => $this->waliKelas->idguru,
            'mapel_idmapel' => $this->mtk->idmapel,
            'hari' => $hariIni,
            'jam_ke' => 1,
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '07:45:00',
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'terjadwal',
        ]);
        $jpBin = JadwalPertemuan::create([
            'jadwalkelas_idjadwalkelas' => JadwalKelas::query()->where('jamhari_idjamhari', $jam2->idjamhari)->value('idjadwalkelas'),
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelas->idkelas,
            'guru_idguru' => $this->waliKelas->idguru,
            'mapel_idmapel' => $this->bin->idmapel,
            'hari' => $hariIni,
            'jam_ke' => 2,
            'waktu_mulai' => '07:45:00',
            'waktu_selesai' => '08:30:00',
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'terjadwal',
        ]);

        // Absensi harian: A 5 baris (3 hadir, 1 sakit, 1 izin), B 2 baris hadir
        $mk = fn (int $d, int $offset, string $status) => AbsensiHarian::create([
            'tahunajaran_idtahunajaran' => $this->ta->idtahunajaran,
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelas->idkelas,
            'tanggal' => now()->subDays($offset)->format('Y-m-d'),
            'siswa_idsiswa' => $d,
            'status' => $status,
            'catatan' => null,
            'diinput_oleh' => $guruUser->id,
        ]);
        $idA = $this->profilA->idsiswa;
        $idB = $this->profilB->idsiswa;
        $mk($idA, 5, 'hadir');
        $mk($idA, 4, 'hadir');
        $mk($idA, 3, 'izin');
        $mk($idA, 2, 'hadir');
        $mk($idA, 1, 'sakit');
        $mk($idB, 5, 'hadir');
        $mk($idB, 4, 'sakit');

        // Absensi per jam: A hadir+alpha, B hadir+hadir
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $jpMtk->idjadwal_pertemuan, 'siswa_idsiswa' => $idA, 'status' => 'hadir', 'keterangan' => null, 'diinput_oleh' => $guruUser->id]);
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $jpBin->idjadwal_pertemuan, 'siswa_idsiswa' => $idA, 'status' => 'alpha', 'keterangan' => null, 'diinput_oleh' => $guruUser->id]);
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $jpMtk->idjadwal_pertemuan, 'siswa_idsiswa' => $idB, 'status' => 'hadir', 'keterangan' => null, 'diinput_oleh' => $guruUser->id]);
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $jpBin->idjadwal_pertemuan, 'siswa_idsiswa' => $idB, 'status' => 'sakit', 'keterangan' => null, 'diinput_oleh' => $guruUser->id]);

        // Nilai draft (semester Genap — belum final; TIDAK boleh bocor)
        $komp = KomponenNilai::create(['semester_idsemester' => $this->semGenap->idsemester, 'mapel_idmapel' => $this->mtk->idmapel, 'nama' => 'Tugas Uji', 'bobot' => 100, 'urutan' => 1, 'aktif' => true]);
        NilaiSiswa::create(['semester_idsemester' => $this->semGenap->idsemester, 'kelas_idkelas' => $this->kelas->idkelas, 'siswa_idsiswa' => $idA, 'komponen_nilai_idkomponen_nilai' => $komp->idkomponen_nilai, 'nilai' => 77, 'guru_idguru' => $this->waliKelas->idguru]);

        // Nilai akhir (snapshot) semester aktif: A MTK 85 (KKTP 75), A BIN 70, B MTK 90
        NilaiAkhir::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelas->idkelas, 'mapel_idmapel' => $this->mtk->idmapel, 'siswa_idsiswa' => $idA, 'nilai_akhir' => 85, 'nilai_kktp' => 75, 'dihitung_pada' => now()]);
        NilaiAkhir::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelas->idkelas, 'mapel_idmapel' => $this->bin->idmapel, 'siswa_idsiswa' => $idA, 'nilai_akhir' => 70, 'nilai_kktp' => null, 'dihitung_pada' => now()]);
        NilaiAkhir::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelas->idkelas, 'mapel_idmapel' => $this->mtk->idmapel, 'siswa_idsiswa' => $idB, 'nilai_akhir' => 90, 'nilai_kktp' => 80, 'dihitung_pada' => now()]);

        // Nilai akhir hanya kelas B (milik B) — tidak boleh terbaca A
        NilaiAkhir::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelasB->idkelas, 'mapel_idmapel' => $this->mtk->idmapel, 'siswa_idsiswa' => $idB, 'nilai_akhir' => 60, 'nilai_kktp' => 75, 'dihitung_pada' => now()]);

        // Finalisasi rapor semester aktif untuk kelas 7A (A & B) dan kelas 8B (B saja)
        $admin = $this->buatAdmin('finalisasi');
        FinalisasiRapor::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelas->idkelas, 'status' => FinalisasiRapor::STATUS_FINAL, 'wali_kelas_idguru' => $this->waliKelas->idguru, 'difinalisasi_oleh' => $admin->id, 'difinalisasi_pada' => now()]);
        FinalisasiRapor::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelasB->idkelas, 'status' => FinalisasiRapor::STATUS_FINAL, 'wali_kelas_idguru' => $this->waliKelas->idguru, 'difinalisasi_oleh' => $admin->id, 'difinalisasi_pada' => now()]);

        CatatanRapor::create(['semester_idsemester' => $this->semAktif->idsemester, 'siswa_idsiswa' => $idA, 'catatan' => 'Rajin', 'guru_idguru' => $this->waliKelas->idguru]);
    }

    private function buatGuru(string $suffix): array
    {
        $suffix = random_int(1000, 9999).now()->format('His');
        $user = User::create([
            'name' => 'Guru Uji '.$suffix,
            'username' => 'uuji_guru_'.$suffix,
            'email' => 'uuji-guru-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'guru',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $guru = Guru::create([
            'user_iduser' => $user->id,
            'nip' => str_pad((string) $user->id, 15, '0', STR_PAD_LEFT),
            'nama' => $user->name,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1980-01-01',
            'status' => 'aktif',
        ]);

        return [$user, $guru];
    }

    private function buatSiswa(string $suffix, string $nama): array
    {
        $suffix = random_int(1000, 9999).now()->format('His');
        $user = User::create([
            'name' => $nama,
            'username' => 'uuji_siswa_'.$suffix,
            'email' => 'uuji-siswa-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'siswa',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $siswa = Siswa::create([
            'user_iduser' => $user->id,
            'nisn' => str_pad((string) ($user->id * 7 % 10000000000), 10, '0', STR_PAD_LEFT),
            'nis' => 'NIS'.$user->id,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }

    private function buatAdmin(string $suffix): User
    {
        $suffix = random_int(1000, 9999).now()->format('His');

        return User::create([
            'name' => 'Admin Uji '.$suffix,
            'username' => 'uuji_admin_'.$suffix,
            'email' => 'uuji-admin-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'superadmin',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
    }

    private function bearer(User $user, string $device = 'HP Uji', mixed $expiresAt = null): string
    {
        return $user->createToken($device, ['mobile-api'], $expiresAt)->plainTextToken;
    }

    private function authA(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->siswaA)];
    }

    private function authB(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->siswaB)];
    }

    private function jsonA(string $method, string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        \Illuminate\Support\Facades\Auth::forgetGuards(); // request nyata selalu fresh; guard di-cache dlm proses test

        return $this->withHeaders($this->authA())->{$method}($url, $data);
    }

    private function jsonB(string $method, string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return $this->withHeaders($this->authB())->{$method}($url, $data);
    }

    // ============================================================== Auth/role

    public function test_guru_ditolak(): void
    {
        [$guruUser] = $this->buatGuru('tolak');

        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($guruUser)])
            ->getJson('/api/v1/siswa/beranda')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_wali_ditolak(): void
    {
        $suffix = '_'.random_int(1000, 9999).now()->format('His');
        $wali = User::create([
            'name' => 'Wali Uji'.$suffix,
            'username' => 'uuji_wali_'.$suffix,
            'email' => 'uuji-wali-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'wali',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($wali)])
            ->getJson('/api/v1/siswa/beranda')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_admin_superadmin_ditolak(): void
    {
        foreach (['admin', 'superadmin'] as $level) {
            $suffix = '_'.random_int(1000, 9999).now()->format('His');
            $user = User::create([
                'name' => 'Uji '.$level.$suffix,
                'username' => 'uuji_'.$level.'_'.$suffix,
                'email' => 'uuji-'.$level.'-'.$suffix.'@example.test',
                'password' => bcrypt('password123'),
                'level' => $level,
                'status_akun' => 'aktif',
                'wajib_ganti_password' => false,
            ]);

            $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($user)])
                ->getJson('/api/v1/siswa/beranda')
                ->assertStatus(403)
                ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
        }
    }

    public function test_token_invalid_dan_kedaluwarsa_ditolak(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer token-salah'])->getJson('/api/v1/siswa/beranda')->assertStatus(401);

        $token = $this->bearer($this->siswaA, 'HP', now()->subMinute());
        $this->withHeaders(['Authorization' => 'Bearer '.$token])->getJson('/api/v1/siswa/beranda')->assertStatus(401);
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $token = $this->bearer($this->siswaA);
        $this->profilA->update(['status' => 'lulus']);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])->getJson('/api/v1/siswa/beranda')->assertStatus(403);
    }

    public function test_wajib_ganti_password_memblokir_endpoint_akademik(): void
    {
        $this->siswaA->update(['wajib_ganti_password' => true]);
        $token = $this->bearer($this->siswaA);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])->getJson('/api/v1/siswa/beranda')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    // ================================================================ Beranda

    public function test_profil_memuat_identitas_dan_kelas(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/siswa/profil');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nisn', $this->profilA->nisn)
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonStructure(['data' => ['id', 'nisn', 'nis', 'nama', 'jenis_kelamin', 'tahun_masuk', 'status', 'kelas', 'akun']]);
    }

    public function test_beranda_memuat_konteks_jadwal_dan_tidak_memuat_modul_lain(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/beranda');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.konteks.valid', true)
            ->assertJsonPath('data.konteks.tahun_ajaran.nama', 'UJI-TA-FASE2')
            ->assertJsonPath('data.konteks.semester.nama', 'Ganjil')
            ->assertJsonPath('data.siswa.nisn', $this->profilA->nisn);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data['jadwal_hari_ini']));
        $this->assertArrayNotHasKey('tagihan', $data);
        $this->assertArrayNotHasKey('pengumuman', $data);
        $this->assertArrayNotHasKey('notifikasi', $data);
        $this->assertArrayNotHasKey('saldo', $data);
    }

    public function test_beranda_siswa_lain_tidak_bocor(): void
    {
        $responseA = $this->jsonA('get', '/api/v1/siswa/beranda');
        $this->assertSame($this->profilA->nisn, $responseA->json('data.siswa.nisn'));
        $this->assertNotSame($this->profilB->nisn, $responseA->json('data.siswa.nisn'));
    }

    // ================================================================ Jadwal

    public function test_jadwal_default_dan_filter_tanggal(): void
    {
        $default = $this->jsonA('get', '/api/v1/siswa/jadwal');
        $default->assertStatus(200);
        $this->assertCount(3, $default->json('data.jadwal')); // hari ini (2) + besok (1)

        $hariIni = now()->format('Y-m-d');
        $terfilter = $this->jsonA('get', '/api/v1/siswa/jadwal?tanggal='.$hariIni);
        $terfilter->assertStatus(200)->assertJsonPath('data.tanggal', $hariIni);
        $this->assertCount(2, $terfilter->json('data.jadwal'));

        $besok = now()->addDay()->format('Y-m-d');
        $besokResponse = $this->jsonA('get', '/api/v1/siswa/jadwal?tanggal='.$besok);
        $this->assertCount(1, $besokResponse->json('data.jadwal'));
    }

    public function test_jadwal_tanggal_invalid_ditolak(): void
    {
        $this->jsonA('get', '/api/v1/siswa/jadwal?tanggal=bukan-tanggal')->assertStatus(422);
        $this->jsonA('get', '/api/v1/siswa/jadwal?tanggal=2026-13-40')->assertStatus(422);
    }

    public function test_jadwal_tidak_memuat_miliki_siswa_lain(): void
    {
        // Buat kelas/jadwal khusus B (UJI-8B tidak dipakai A untuk jadwal, tapi pastikan
        // struktur response hanya memuat kelas tempat A terdaftar).
        $response = $this->jsonA('get', '/api/v1/siswa/jadwal');
        $kelasIds = collect($response->json('data.jadwal'))->pluck('kelas_id')->unique()->values();
        $this->assertContains($this->kelas->idkelas, $kelasIds);
        $this->assertNotContains($this->kelasB->idkelas, $kelasIds);
    }

    // ================================================================ Absensi

    public function test_rekap_harian_dan_daftar(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/absensi/harian');
        $response->assertStatus(200);

        $rekap = $response->json('data.rekap_semester');
        $this->assertSame(5, $rekap['total']);
        $this->assertSame(3, $rekap['hadir']);
        $this->assertSame(1, $rekap['izin']);
        $this->assertSame(1, $rekap['sakit']);
        $this->assertSame(0, $rekap['alpha']);

        $this->assertSame(5, $response->json('meta.total'));
    }

    public function test_filter_status_harian(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/absensi/harian?status=sakit');
        $this->assertCount(1, $response->json('data.items'));

        $response = $this->jsonA('get', '/api/v1/siswa/absensi/harian?status=hadir');
        $this->assertCount(3, $response->json('data.items'));
    }

    public function test_periode_wajib_dan_validasi_tanggal(): void
    {
        $this->jsonA('get', '/api/v1/siswa/absensi/periode')->assertStatus(422);
        $this->jsonA('get', '/api/v1/siswa/absensi/periode?dari=2026-11-10&sampai=2026-11-01')->assertStatus(422);
        $this->jsonA('get', '/api/v1/siswa/absensi/periode?dari=kemarin&sampai=besok')->assertStatus(422);

        $response = $this->jsonA('get', '/api/v1/siswa/absensi/periode?dari='.now()->subDays(10)->format('Y-m-d').'&sampai='.now()->format('Y-m-d'));
        $response->assertStatus(200);
        $this->assertSame(5, $response->json('data.ringkasan.total'));
    }

    public function test_absensi_harian_tidak_memuat_data_siswa_lain(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/absensi/harian?per_page=50');
        $tanggal = collect($response->json('data.items'))->pluck('tanggal')->all();
        // Siswa A: 5 baris (semua diuji). Siswa B: 2 baris pada tanggal yang sama, tidak boleh ikut.
        $this->assertCount(5, $tanggal);
    }

    public function test_histori_perjam_dari_tabel_kanonis(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/absensi/perjam');
        $response->assertStatus(200);

        $this->assertSame(2, $response->json('data.rekap.total'));
        $this->assertSame(1, $response->json('data.rekap.hadir'));
        $this->assertSame(1, $response->json('data.rekap.alpha'));

        $items = $response->json('data.items');
        $this->assertSame(2, count($items));
        foreach ($items as $item) {
            $this->assertNotEmpty($item['tanggal']);
            $this->assertNotNull($item['jam_ke']);
            $this->assertNotEmpty($item['mapel_nama']);
        }

        $alpha = $this->jsonA('get', '/api/v1/siswa/absensi/perjam?status=alpha');
        $this->assertCount(1, $alpha->json('data.items'));
    }

    public function test_perjam_tidak_bocor_data_siswa_lain(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/absensi/perjam');
        $statuses = collect($response->json('data.items'))->pluck('status')->sort()->values();
        // A: hadir + alpha. B: hadir + sakit — sakit tidak boleh muncul.
        $this->assertContains('hadir', $statuses);
        $this->assertContains('alpha', $statuses);
        $this->assertNotContains('sakit', $statuses);
    }

    // ============================================================ Nilai & Rapor

    public function test_nilai_akhir_muncul_hanya_rapor_final(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/nilai');
        $response->assertStatus(200);

        $rapor = $response->json('data.rapor');
        $this->assertCount(1, $rapor);
        $this->assertSame('Ganjil', $rapor[0]['semester']['nama']);
        $nilai = collect($rapor[0]['nilai'])->keyBy('mapel_kode');
        $this->assertSame(85.0, (float) $nilai['UJI-MTK']['nilai_akhir']);
        $this->assertSame(70.0, (float) $nilai['UJI-BIN']['nilai_akhir']);
        $this->assertNull($nilai['UJI-BIN']['nilai_kktp']);
        // Nilai draft Genap (77) tidak pernah muncul
        $this->assertNotContains(77, collect($rapor[0]['nilai'])->pluck('nilai_akhir')->map(fn ($v) => (float) $v)->all());
    }

    public function test_nilai_filter_semester_draf_ditolak(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/nilai?semester_id='.$this->semGenap->idsemester);
        $response->assertStatus(404)->assertJsonPath('code', 'NILAI_NOT_FOUND');
    }

    public function test_nilai_filter_kelas_bukan_milik_siswa_ditolak(): void
    {
        $response = $this->jsonA('get', '/api/v1/siswa/nilai?kelas_id='.$this->kelasB->idkelas);
        $response->assertStatus(404)->assertJsonPath('code', 'NILAI_NOT_FOUND');
    }

    public function test_rapor_daftar_dan_detail(): void
    {
        $daftar = $this->jsonA('get', '/api/v1/siswa/rapor');
        $daftar->assertStatus(200);
        $this->assertCount(1, $daftar->json('data.rapor'));
        $this->assertSame('UJI-10A', $daftar->json('data.rapor.0.kelas.nama'));

        $detail = $this->jsonA('get', '/api/v1/siswa/rapor/'.$this->semAktif->idsemester.'/'.$this->kelas->idkelas);
        $detail->assertStatus(200);
        $this->assertTrue($detail->json('data.is_final'));
        $this->assertCount(2, $detail->json('data.nilai'));
        $this->assertSame('Rajin', $detail->json('data.catatan'));
        $this->assertGreaterThanOrEqual(5, $detail->json('data.rekap_kehadiran.total'));
    }

    public function test_rapor_beli_final_tidak_bisa_diakses(): void
    {
        $detail = $this->jsonA('get', '/api/v1/siswa/rapor/'.$this->semGenap->idsemester.'/'.$this->kelas->idkelas);
        $detail->assertStatus(404)->assertJsonPath('code', 'RAPOR_NOT_FOUND');
    }

    public function test_rapor_kelas_siswa_lain_tidak_bisa_diakses(): void
    {
        $detail = $this->jsonA('get', '/api/v1/siswa/rapor/'.$this->semAktif->idsemester.'/'.$this->kelasB->idkelas);
        $detail->assertStatus(404)->assertJsonPath('code', 'RAPOR_NOT_FOUND');
    }

    public function test_rapor_siswa_lain_tidak_muncul(): void
    {
        $respB = $this->jsonB('get', '/api/v1/siswa/rapor');
        // B punya rapor final (7A & 8B): 2 entri
        $this->assertCount(2, $respB->json('data.rapor'));

        $respA = $this->jsonA('get', '/api/v1/siswa/rapor');
        $this->assertCount(1, $respA->json('data.rapor'));
    }

    public function test_jadwal_filter_hari(): void
    {
        $hariIni = now()->dayOfWeekIso;
        $resp = $this->jsonA('get', '/api/v1/siswa/jadwal?hari='.$hariIni);

        $resp->assertOk()
            ->assertJsonPath('data.hari', $hariIni)
            ->assertJsonPath('data.nama_hari', \App\Support\Hari::nama($hariIni));

        $this->assertNotEmpty($resp->json('data.jadwal'));
    }
}
