<?php

namespace Tests\Feature\Api\V1;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\FinalisasiRapor;
use App\Models\Guru;
use App\Models\JadwalKelas;
use App\Models\JadwalPertemuan;
use App\Models\Jamhari;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\KomponenNilai;
use App\Models\Mapel;
use App\Models\NilaiSiswa;
use App\Models\Notifikasi;
use App\Models\PenugasanMengajar;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 5 — Teacher API.
 * Berjalan dalam satu transaksi per test (rollback) — data fixture tidak tersisa.
 */
class GuruApiTest extends TestCase
{
    private Tahunajaran $ta;

    private Semester $semAktif;

    private Semester $semGenap;

    private User $guruA;

    private Guru $profilA;

    private User $guruB;

    private Guru $profilB;

    private Kelas $kelasA;

    private Kelas $kelasB;

    private Kelas $kelasWaliA;

    private Kelas $kelasKunci;

    private Mapel $mtk;

    private Mapel $bin;

    private Mapel $ipa;

    private User $siswaA1;

    private User $siswaA2;

    private User $siswaA3;

    private User $siswaB1;

    private int $idSiswaA1;

    private int $idSiswaA2;

    private int $idSiswaA3;

    private int $idSiswaB1;

    private int $idPertemuanHariIni;

    private int $idPertemuanKemarin;

    private int $idPertemuanLampau;

    private int $idPertemuanBesok;

    private int $idPertemuanB;

    private int $idAbsensiLampau;

    private int $idAbsensiPerjamLampau;

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

        $this->ta = Tahunajaran::create(['nama' => 'UJI-TA-FASE5', 'aktif' => true]);
        $this->semAktif = Semester::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'Ganjil', 'aktif' => true]);
        $this->semGenap = Semester::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'Genap', 'aktif' => false]);

        [$this->guruA, $this->profilA] = $this->buatGuru('A');
        [$this->guruB, $this->profilB] = $this->buatGuru('B');

        $this->mtk = Mapel::create(['kode' => 'UJI5-MTK', 'nama' => 'Matematika Uji', 'kelompok' => 'wajib']);
        $this->bin = Mapel::create(['kode' => 'UJI5-BIN', 'nama' => 'B. Indonesia Uji', 'kelompok' => 'wajib']);
        $this->ipa = Mapel::create(['kode' => 'UJI5-IPA', 'nama' => 'IPA Uji', 'kelompok' => 'wajib']);

        $this->kelasA = Kelas::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'UJI-5A', 'tingkat' => '10', 'jenis' => 'induk', 'rombel' => 1, 'guru_idguru' => $this->profilA->idguru]);
        $this->kelasB = Kelas::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'UJI-5B', 'tingkat' => '10', 'jenis' => 'induk', 'rombel' => 2, 'guru_idguru' => $this->profilB->idguru]);
        // Kelas yang DIWALI guru A tetapi tidak diajar — akses baca, bukan absensi.
        $this->kelasWaliA = Kelas::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'UJI-5C', 'tingkat' => '10', 'jenis' => 'induk', 'rombel' => 3, 'guru_idguru' => $this->profilA->idguru]);
        // Kelas D: untuk pengujian terkunci (finalisasi).
        $this->kelasKunci = Kelas::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'UJI-5D', 'tingkat' => '10', 'jenis' => 'induk', 'rombel' => 4, 'guru_idguru' => $this->profilA->idguru]);

        [$this->siswaA1, $profilA1] = $this->buatSiswa('A1', 'Siswa Uji A1');
        [$this->siswaA2, $profilA2] = $this->buatSiswa('A2', 'Siswa Uji A2');
        [$this->siswaA3, $profilA3] = $this->buatSiswa('A3', 'Siswa Uji A3');
        [$this->siswaB1, $profilB1] = $this->buatSiswa('B1', 'Siswa Uji B1');
        $this->idSiswaA1 = $profilA1->idsiswa;
        $this->idSiswaA2 = $profilA2->idsiswa;
        $this->idSiswaA3 = $profilA3->idsiswa;
        $this->idSiswaB1 = $profilB1->idsiswa;
        Kelassiswa::create(['kelas_idkelas' => $this->kelasA->idkelas, 'siswa_idsiswa' => $profilA1->idsiswa, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasA->idkelas, 'siswa_idsiswa' => $profilA2->idsiswa, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasKunci->idkelas, 'siswa_idsiswa' => $profilA3->idsiswa, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasB->idkelas, 'siswa_idsiswa' => $profilB1->idsiswa, 'status' => 'aktif']);

        $hariIni = now()->dayOfWeekIso;

        // Jadwal hari ini: guru A (MTK jam 1, BIN jam 2), guru B (MTK di kelas B).
        $jam1 = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $this->kelasA->idkelas, 'jam_ke' => 1, 'jenis' => 'pelajaran', 'mulai' => '07:00:00', 'selesai' => '07:45:00']);
        $jam2 = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $this->kelasA->idkelas, 'jam_ke' => 2, 'jenis' => 'pelajaran', 'mulai' => '07:45:00', 'selesai' => '08:30:00']);
        $jamB = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $this->kelasB->idkelas, 'jam_ke' => 1, 'jenis' => 'pelajaran', 'mulai' => '07:00:00', 'selesai' => '07:45:00']);

        $jkA1 = JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jam1->idjamhari, 'mapel_idmapel' => $this->mtk->idmapel, 'guru_idguru' => $this->profilA->idguru]);
        $jkA2 = JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jam2->idjamhari, 'mapel_idmapel' => $this->bin->idmapel, 'guru_idguru' => $this->profilA->idguru]);
        JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jamB->idjamhari, 'mapel_idmapel' => $this->mtk->idmapel, 'guru_idguru' => $this->profilB->idguru]);

        // Pertemuan (snapshot): hari ini, kemarin, 3 hari lalu, besok.
        $idPertemuanHariIni = $this->buatPertemuan($jkA1, $this->kelasA->idkelas, $this->profilA->idguru, $this->mtk->idmapel, now()->format('Y-m-d'), 1, 'terjadwal');
        $idPertemuanKemarin = $this->buatPertemuan($jkA1, $this->kelasA->idkelas, $this->profilA->idguru, $this->mtk->idmapel, now()->subDay()->format('Y-m-d'), 1, 'terjadwal');
        $idPertemuanLampau = $this->buatPertemuan($jkA1, $this->kelasA->idkelas, $this->profilA->idguru, $this->mtk->idmapel, now()->subDays(3)->format('Y-m-d'), 1, 'terjadwal');
        $idPertemuanBesok = $this->buatPertemuan($jkA1, $this->kelasA->idkelas, $this->profilA->idguru, $this->mtk->idmapel, now()->addDay()->format('Y-m-d'), 1, 'terjadwal');
        $jkB = JadwalKelas::query()->where('jamhari_idjamhari', $jamB->idjamhari)->first();
        $idPertemuanB = $this->buatPertemuan($jkB, $this->kelasB->idkelas, $this->profilB->idguru, $this->mtk->idmapel, now()->format('Y-m-d'), 1, 'terjadwal');

        $this->idPertemuanHariIni = $idPertemuanHariIni;
        $this->idPertemuanKemarin = $idPertemuanKemarin;
        $this->idPertemuanLampau = $idPertemuanLampau;
        $this->idPertemuanBesok = $idPertemuanBesok;
        $this->idPertemuanB = $idPertemuanB;

        // Penugasan mengajar (semester aktif).
        PenugasanMengajar::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelasA->idkelas, 'mapel_idmapel' => $this->mtk->idmapel, 'guru_idguru' => $this->profilA->idguru]);
        PenugasanMengajar::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelasA->idkelas, 'mapel_idmapel' => $this->bin->idmapel, 'guru_idguru' => $this->profilA->idguru]);
        PenugasanMengajar::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelasB->idkelas, 'mapel_idmapel' => $this->mtk->idmapel, 'guru_idguru' => $this->profilB->idguru]);
        PenugasanMengajar::create(['semester_idsemester' => $this->semAktif->idsemester, 'kelas_idkelas' => $this->kelasKunci->idkelas, 'mapel_idmapel' => $this->ipa->idmapel, 'guru_idguru' => $this->profilA->idguru]);

        // Komponen: MTK 60+40 (valid), BIN hanya 50 (tidak valid), IPA 100 (valid, tapi terkunci).
        KomponenNilai::create(['semester_idsemester' => $this->semAktif->idsemester, 'mapel_idmapel' => $this->mtk->idmapel, 'nama' => 'Tugas', 'bobot' => 60, 'urutan' => 1, 'aktif' => true]);
        KomponenNilai::create(['semester_idsemester' => $this->semAktif->idsemester, 'mapel_idmapel' => $this->mtk->idmapel, 'nama' => 'Ulangan', 'bobot' => 40, 'urutan' => 2, 'aktif' => true]);
        KomponenNilai::create(['semester_idsemester' => $this->semAktif->idsemester, 'mapel_idmapel' => $this->bin->idmapel, 'nama' => 'Tugas', 'bobot' => 50, 'urutan' => 1, 'aktif' => true]);
        KomponenNilai::create(['semester_idsemester' => $this->semAktif->idsemester, 'mapel_idmapel' => $this->ipa->idmapel, 'nama' => 'Praktik', 'bobot' => 100, 'urutan' => 1, 'aktif' => true]);

        // Finalisasi rapor kelas kunci -> nilai penugasan IPA terkunci.
        $admin = $this->buatAdmin();
        FinalisasiRapor::create([
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelasKunci->idkelas,
            'status' => FinalisasiRapor::STATUS_FINAL,
            'wali_kelas_idguru' => $this->profilA->idguru,
            'difinalisasi_oleh' => $admin->id,
            'difinalisasi_pada' => now(),
        ]);

        // Absensi harian: hari ini di kelas A (A1 hadir, A2 izin), kelas B (B1 hadir), 3 hari lalu (A1 hadir).
        $mk = fn (int $kelasId, int $siswaId, string $tanggal, string $status) => AbsensiHarian::create([
            'tahunajaran_idtahunajaran' => $this->ta->idtahunajaran,
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $kelasId,
            'tanggal' => $tanggal,
            'siswa_idsiswa' => $siswaId,
            'status' => $status,
            'catatan' => null,
            'diinput_oleh' => $this->guruA->id,
        ]);
        $idA1 = $profilA1->idsiswa;
        $mk($this->kelasA->idkelas, $idA1, now()->format('Y-m-d'), 'hadir');
        $mk($this->kelasA->idkelas, $profilA2->idsiswa, now()->format('Y-m-d'), 'izin');
        $mk($this->kelasB->idkelas, $profilB1->idsiswa, now()->format('Y-m-d'), 'hadir');
        $this->idAbsensiLampau = $mk($this->kelasA->idkelas, $idA1, now()->subDays(3)->format('Y-m-d'), 'hadir')->idabsensi_harian;

        // Absensi per jam: A1 hadir di pertemuan hari ini; kemarin sakit; 3 hari lalu izin.
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $idPertemuanHariIni, 'siswa_idsiswa' => $idA1, 'status' => 'hadir', 'keterangan' => null, 'diinput_oleh' => $this->guruA->id]);
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $idPertemuanKemarin, 'siswa_idsiswa' => $idA1, 'status' => 'sakit', 'keterangan' => null, 'diinput_oleh' => $this->guruA->id]);
        $this->idAbsensiPerjamLampau = AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $idPertemuanLampau, 'siswa_idsiswa' => $idA1, 'status' => 'izin', 'keterangan' => null, 'diinput_oleh' => $this->guruA->id])->idabsensi_perjam;
        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $idPertemuanB, 'siswa_idsiswa' => $profilB1->idsiswa, 'status' => 'hadir', 'keterangan' => null, 'diinput_oleh' => $this->guruB->id]);
    }

    // ------------------------------------------------------------------ helpers

    private function buatGuru(string $suffix): array
    {
        $suffix = random_int(1000, 9999).now()->format('His');
        $user = User::create([
            'name' => 'Guru Uji '.$suffix.' '.$suffix,
            'username' => 'uuji5_guru_'.$suffix,
            'email' => 'uuji5-guru-'.$suffix.'@example.test',
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
            'username' => 'uuji5_siswa_'.$suffix,
            'email' => 'uuji5-siswa-'.$suffix.'@example.test',
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

    private function buatAdmin(): User
    {
        $suffix = random_int(1000, 9999).now()->format('His');

        return User::create([
            'name' => 'Admin Uji '.$suffix,
            'username' => 'uuji5_admin_'.$suffix,
            'email' => 'uuji5-admin-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'superadmin',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
    }

    private function buatPertemuan(?JadwalKelas $jk, int $kelasId, int $guruId, int $mapelId, string $tanggal, int $jamKe, string $status): int
    {
        return (int) JadwalPertemuan::create([
            'jadwalkelas_idjadwalkelas' => $jk?->idjadwalkelas,
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $kelasId,
            'guru_idguru' => $guruId,
            'mapel_idmapel' => $mapelId,
            'hari' => (int) \Carbon\Carbon::parse($tanggal)->dayOfWeekIso,
            'jam_ke' => $jamKe,
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '07:45:00',
            'tanggal' => $tanggal,
            'status' => $status,
        ])->idjadwal_pertemuan;
    }

    private function bearer(User $user, string $device = 'HP Uji'): string
    {
        return $user->createToken($device, ['mobile-api'])->plainTextToken;
    }

    private function authA(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->guruA)];
    }

    private function authB(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->guruB)];
    }

    private function authSiswaA(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->siswaA1)];
    }

    private function jsonA(string $method, string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return $this->withHeaders($this->authA())->{$method}($url, $data);
    }

    private function jsonB(string $method, string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return $this->withHeaders($this->authB())->{$method}($url, $data);
    }

    // ============================================================== Auth/role

    public function test_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/v1/guru/profil')->assertStatus(401);
    }

    public function test_token_tidak_valid_ditolak(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer token-ngawur-123'])
            ->getJson('/api/v1/guru/profil')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_siswa_ditolak_dari_endpoint_guru(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authSiswaA())
            ->getJson('/api/v1/guru/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_wali_ditolak_dari_endpoint_guru(): void
    {
        $wali = User::create(['name' => 'Wali Uji', 'username' => 'uuji5_wali_'.random_int(1000, 9999), 'email' => 'uuji5-wali-'.random_int(1000, 9999).'@example.test', 'password' => bcrypt('password123'), 'level' => 'wali', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($wali)])
            ->getJson('/api/v1/guru/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_superadmin_tidak_otomatis_bisa_endpoint_guru(): void
    {
        $admin = $this->buatAdmin();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($admin)])
            ->getJson('/api/v1/guru/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_akun_guru_nonaktif_ditolak(): void
    {
        $this->profilA->update(['status' => 'nonaktif']);

        $this->jsonA('getJson', '/api/v1/guru/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }

    public function test_wajib_ganti_password_memblokir_endpoint_guru(): void
    {
        $this->guruA->update(['wajib_ganti_password' => true]);

        $this->jsonA('getJson', '/api/v1/guru/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    // ====================================================== Profil & beranda

    public function test_profil_berhasil_dan_memuat_mapel(): void
    {
        // pastikan guru A terdaftar mengampu MTK
        $this->profilA->mapel()->syncWithoutDetaching([$this->mtk->idmapel]);

        $response = $this->jsonA('getJson', '/api/v1/guru/profil');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->profilA->idguru)
            ->assertJsonPath('data.nama', $this->profilA->nama);

        $this->assertContains($this->mtk->idmapel, collect($response->json('data.mapel'))->pluck('id')->all());
    }

    public function test_beranda_memuat_konteks_jadwal_dan_ringkasan(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/beranda');

        $response->assertOk()
            ->assertJsonPath('data.konteks.valid', true)
            ->assertJsonPath('data.ringkasan.jumlah_kelas', 3) // kelasA (mengajar), kelasWaliA (wali), kelasKunci (mengajar? tidak) + kelasA... hitung fixture
            ->assertJsonStructure([
                'data' => [
                    'guru' => ['id', 'nip', 'nama'],
                    'akun' => ['username', 'wajib_ganti_password'],
                    'konteks' => ['valid', 'tahun_ajaran', 'semester'],
                    'kelas',
                    'ringkasan' => ['jumlah_kelas', 'jumlah_penugasan', 'pertemuan_hari_ini', 'absensi_perjam_hari_ini', 'absensi_harian_hari_ini'],
                    'jadwal_hari_ini',
                ],
            ]);
    }

    // ====================================================== Kelas & penugasan

    public function test_daftar_kelas_hanya_milik_guru(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/kelas');
        $ids = collect($response->json('data.kelas'))->pluck('id')->all();

        $this->assertContains($this->kelasA->idkelas, $ids);   // mengajar
        $this->assertContains($this->kelasWaliA->idkelas, $ids); // wali
        $this->assertContains($this->kelasKunci->idkelas, $ids); // mengajar? tidak; wali? guruA -> ya (wali_kelas)
        $this->assertNotContains($this->kelasB->idkelas, $ids);  // milik guru B
    }

    public function test_detail_kelas_milik_sendiri_berhasil(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasA->idkelas);

        $response->assertOk()
            ->assertJsonPath('data.id', $this->kelasA->idkelas)
            ->assertJsonPath('data.jumlah_siswa', 2);
    }

    public function test_detail_kelas_guru_lain_404(): void
    {
        $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasB->idkelas)
            ->assertStatus(404)
            ->assertJsonPath('code', 'KELAS_NOT_FOUND');
    }

    public function test_siswa_kelas_guru_lain_404(): void
    {
        $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasB->idkelas.'/siswa')
            ->assertStatus(404);
    }

    public function test_siswa_kelas_sendiri_berhasil(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasA->idkelas.'/siswa');

        $response->assertOk()
            ->assertJsonPath('data.jumlah', 2);

        $ids = collect($response->json('data.siswa'))->pluck('id')->all();
        $this->assertContains($this->idSiswaA1, $ids);
        $this->assertContains($this->idSiswaA2, $ids);
        $this->assertNotContains($this->idSiswaB1, $ids);
    }

    public function test_penugasan_hanya_milik_sendiri(): void
    {
        $responseA = $this->jsonA('getJson', '/api/v1/guru/penugasan');
        $responseB = $this->jsonB('getJson', '/api/v1/guru/penugasan');

        $kelasIdsA = collect($responseA->json('data.penugasan'))->pluck('kelas.id')->all();
        $kelasIdsB = collect($responseB->json('data.penugasan'))->pluck('kelas.id')->all();

        $this->assertContains($this->kelasA->idkelas, $kelasIdsA);
        $this->assertContains($this->kelasKunci->idkelas, $kelasIdsA);
        $this->assertNotContains($this->kelasB->idkelas, $kelasIdsA);
        $this->assertSame([$this->kelasB->idkelas], $kelasIdsB);
    }

    // ====================================================== Jadwal

    public function test_jadwal_hanya_milik_sendiri(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/jadwal');
        $kelas = collect($response->json('data.jadwal'))->pluck('kelas.id')->all();

        $this->assertContains($this->kelasA->idkelas, $kelas);
        $this->assertNotContains($this->kelasB->idkelas, $kelas);
    }

    public function test_jadwal_filter_hari_dan_tanggal(): void
    {
        $hariIni = now()->dayOfWeekIso;
        $response = $this->jsonA('getJson', '/api/v1/guru/jadwal?hari='.$hariIni);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.jadwal'));

        $response = $this->jsonA('getJson', '/api/v1/guru/jadwal?tanggal='.now()->format('Y-m-d'));
        $response->assertOk()->assertJsonPath('data.filter.hari', $hariIni);

        $response = $this->jsonA('getJson', '/api/v1/guru/jadwal?tanggal=bukan-tanggal');
        $response->assertStatus(422);
    }

    public function test_jadwal_hari_ini(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/jadwal/hari-ini');

        $response->assertOk()
            ->assertJsonPath('data.tanggal', now()->format('Y-m-d'))
            ->assertJsonPath('data.hari', now()->dayOfWeekIso);

        $this->assertNotEmpty($response->json('data.jadwal'));
    }

    public function test_jadwal_detail_milik_sendiri_berhasil(): void
    {
        $jadwalA = \App\Models\JadwalKelas::query()->where('guru_idguru', $this->profilA->idguru)->first();

        $this->jsonA('getJson', '/api/v1/guru/jadwal/'.$jadwalA->idjadwalkelas)
            ->assertOk()
            ->assertJsonPath('data.id', $jadwalA->idjadwalkelas);
    }

    public function test_jadwal_detail_guru_lain_404(): void
    {
        $jadwalB = \App\Models\JadwalKelas::query()->where('guru_idguru', $this->profilB->idguru)->first();

        $this->jsonA('getJson', '/api/v1/guru/jadwal/'.$jadwalB->idjadwalkelas)
            ->assertStatus(404)
            ->assertJsonPath('code', 'JADWAL_NOT_FOUND');
    }

    // ====================================================== Absensi harian

    public function test_grid_absensi_harian_berhasil(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasA->idkelas.'/absensi?tanggal='.now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonPath('data.kelas_id', $this->kelasA->idkelas)
            ->assertJsonCount(2, 'data.siswa');

        $siswa = collect($response->json('data.siswa'));
        $a1 = $siswa->firstWhere('id', $this->idSiswaA1);
        $this->assertSame('hadir', $a1['absensi']['status']);
    }

    public function test_grid_absensi_harian_kelas_guru_lain_404(): void
    {
        $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasB->idkelas.'/absensi')
            ->assertStatus(404);
    }

    public function test_grid_absensi_harian_tanggal_masa_depan_422(): void
    {
        $this->jsonA('getJson', '/api/v1/guru/kelas/'.$this->kelasA->idkelas.'/absensi?tanggal='.now()->addDay()->format('Y-m-d'))
            ->assertStatus(422);
    }

    public function test_simpan_absensi_harian_membuat_dan_memperbarui(): void
    {
        // 2 record baru? A1 & A2 sudah punya record hari ini -> keduanya jadi "ubah".
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->format('Y-m-d'),
            'kelas_id' => $this->kelasA->idkelas,
            'records' => [
                ['siswa_id' => $this->idSiswaA1, 'status' => 'sakit', 'catatan' => 'demam'],
                ['siswa_id' => $this->idSiswaA2, 'status' => 'hadir', 'catatan' => null],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.diubah', 2)
            ->assertJsonPath('data.dibuat', 0);

        // Idempoten: tidak ada baris ganda.
        $this->assertSame(2, AbsensiHarian::query()->where('kelas_idkelas', $this->kelasA->idkelas)->whereDate('tanggal', now()->format('Y-m-d'))->count());

        $this->assertSame('sakit', AbsensiHarian::query()->where('kelas_idkelas', $this->kelasA->idkelas)->whereDate('tanggal', now()->format('Y-m-d'))->where('siswa_idsiswa', $this->idSiswaA1)->value('status'));
    }

    public function test_simpan_absensi_harian_menolak_siswa_luar_kelas(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->format('Y-m-d'),
            'kelas_id' => $this->kelasA->idkelas,
            'records' => [
                ['siswa_id' => $this->idSiswaB1, 'status' => 'hadir', 'catatan' => null],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_simpan_absensi_harian_menolak_status_invalid(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->format('Y-m-d'),
            'kelas_id' => $this->kelasA->idkelas,
            'records' => [
                ['siswa_id' => $this->idSiswaA1, 'status' => 'bolos', 'catatan' => null],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_simpan_absensi_harian_menolak_duplikat_siswa_dalam_records(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->format('Y-m-d'),
            'kelas_id' => $this->kelasA->idkelas,
            'records' => [
                ['siswa_id' => $this->idSiswaA1, 'status' => 'hadir'],
                ['siswa_id' => $this->idSiswaA1, 'status' => 'sakit'],
            ],
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'DUPLIKAT_SISWA');
    }

    public function test_simpan_absensi_harian_kelas_guru_lain_ditolak(): void
    {
        $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->format('Y-m-d'),
            'kelas_id' => $this->kelasB->idkelas,
            'records' => [
                ['siswa_id' => $this->idSiswaB1, 'status' => 'hadir'],
            ],
        ])->assertStatus(403);
    }

    public function test_simpan_absensi_harian_menolak_tanggal_lampau_lebih_dari_kemarin(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->subDays(2)->format('Y-m-d'),
            'kelas_id' => $this->kelasA->idkelas,
            'records' => [
                ['siswa_id' => $this->idSiswaA2, 'status' => 'hadir'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_simpan_absensi_harian_mengabaikan_guru_id_dari_payload(): void
    {
        $this->jsonA('postJson', '/api/v1/guru/absensi/harian', [
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'kelas_id' => $this->kelasA->idkelas,
            'guru_id' => $this->profilB->idguru, // upaya manipulasi identitas
            'records' => [
                ['siswa_id' => $this->idSiswaA2, 'status' => 'hadir'],
            ],
        ])->assertOk();

        $record = AbsensiHarian::query()
            ->where('kelas_idkelas', $this->kelasA->idkelas)
            ->whereDate('tanggal', now()->subDay()->format('Y-m-d'))
            ->where('siswa_idsiswa', $this->idSiswaA2)
            ->first();

        $this->assertSame($this->guruA->id, $record->diinput_oleh);
    }

    public function test_riwayat_absensi_harian_hanya_kelas_berwenang(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/absensi/harian');

        $response->assertOk();
        $kelasIds = collect($response->json('data.items'))->pluck('kelas.id')->all();
        $this->assertNotEmpty($kelasIds);
        $this->assertNotContains($this->kelasB->idkelas, $kelasIds);
    }

    public function test_riwayat_absensi_harian_filter_status(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/absensi/harian?status=izin');

        $response->assertOk();
        foreach ($response->json('data.items') as $item) {
            $this->assertSame('izin', $item['status']);
        }
    }

    public function test_detail_absensi_harian_guru_lain_404(): void
    {
        // Record absensi milik kelas B (diinput oleh guru A dalam fixture, tapi kelas B milik guru B)
        $recordB = AbsensiHarian::query()->where('kelas_idkelas', $this->kelasB->idkelas)->first();

        $this->jsonA('getJson', '/api/v1/guru/absensi/harian/'.$recordB->idabsensi_harian)
            ->assertStatus(404);
    }

    public function test_edit_absensi_harian_berhasil(): void
    {
        $record = AbsensiHarian::query()
            ->where('kelas_idkelas', $this->kelasA->idkelas)
            ->whereDate('tanggal', now()->format('Y-m-d'))
            ->where('siswa_idsiswa', $this->idSiswaA2)
            ->firstOrFail();

        $this->jsonA('patchJson', '/api/v1/guru/absensi/harian/'.$record->idabsensi_harian, [
            'status' => 'sakit',
            'catatan' => 'demam',
        ])->assertOk()->assertJsonPath('data.status', 'sakit');
    }

    public function test_edit_absensi_harian_melewati_tenggat_ditolak(): void
    {
        $this->jsonA('patchJson', '/api/v1/guru/absensi/harian/'.$this->idAbsensiLampau, [
            'status' => 'alpha',
        ])->assertStatus(422);
    }

    public function test_edit_absensi_harian_kelas_guru_lain_404(): void
    {
        $recordB = AbsensiHarian::query()->where('kelas_idkelas', $this->kelasB->idkelas)->first();

        $this->jsonA('patchJson', '/api/v1/guru/absensi/harian/'.$recordB->idabsensi_harian, [
            'status' => 'alpha',
        ])->assertStatus(404);
    }

    // ====================================================== Absensi per jam

    public function test_daftar_pertemuan_hari_ini_hanya_milik_guru(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/absensi/perjam/pertemuan?tanggal='.now()->format('Y-m-d'));

        $response->assertOk();
        foreach ($response->json('data.pertemuan') as $p) {
            $this->assertNotSame($this->kelasB->idkelas, $p['kelas']['id']);
        }

        $this->assertNotEmpty($response->json('data.pertemuan'));
    }

    public function test_detail_pertemuan_milik_sendiri_berhasil(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/absensi/perjam/pertemuan/'.$this->idPertemuanHariIni);

        $response->assertOk()
            ->assertJsonPath('data.id', $this->idPertemuanHariIni)
            ->assertJsonCount(2, 'data.siswa');
    }

    public function test_detail_pertemuan_guru_lain_404(): void
    {
        $this->jsonA('getJson', '/api/v1/guru/absensi/perjam/pertemuan/'.$this->idPertemuanB)
            ->assertStatus(404);
    }

    public function test_catat_absensi_perjam_berhasil_dan_upsert(): void
    {
        // A2 belum punya catatan di pertemuan hari ini.
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/perjam', [
            'jadwal_pertemuan_id' => $this->idPertemuanHariIni,
            'records' => [
                ['siswa_id' => $this->idSiswaA2, 'status' => 'izin', 'keterangan' => 'acara keluarga'],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.dibuat', 1);

        // Kirim ulang -> menjadi update, bukan baris ganda.
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/perjam', [
            'jadwal_pertemuan_id' => $this->idPertemuanHariIni,
            'records' => [
                ['siswa_id' => $this->idSiswaA2, 'status' => 'sakit', 'keterangan' => 'demam'],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.diubah', 1);
        $this->assertSame(1, AbsensiPerjam::query()->where('jadwal_pertemuan_idjadwal_pertemuan', $this->idPertemuanHariIni)->where('siswa_idsiswa', $this->idSiswaA2)->count());
    }

    public function test_catat_absensi_perjam_menolak_siswa_luar_pertemuan(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/perjam', [
            'jadwal_pertemuan_id' => $this->idPertemuanHariIni,
            'records' => [
                ['siswa_id' => $this->idSiswaB1, 'status' => 'hadir'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_catat_absensi_perjam_pertemuan_masa_depan_ditolak(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/perjam', [
            'jadwal_pertemuan_id' => $this->idPertemuanBesok,
            'records' => [
                ['siswa_id' => $this->idSiswaA1, 'status' => 'hadir'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_catat_absensi_perjam_pertemuan_guru_lain_404(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/guru/absensi/perjam', [
            'jadwal_pertemuan_id' => $this->idPertemuanB,
            'records' => [
                ['siswa_id' => $this->idSiswaB1, 'status' => 'hadir'],
            ],
        ]);

        $response->assertStatus(404);
    }

    public function test_edit_absensi_perjam_kemarin_berhasil(): void
    {
        $record = AbsensiPerjam::query()
            ->where('jadwal_pertemuan_idjadwal_pertemuan', $this->idPertemuanKemarin)
            ->where('siswa_idsiswa', $this->idSiswaA1)
            ->firstOrFail();

        $this->jsonA('patchJson', '/api/v1/guru/absensi/perjam/'.$record->idabsensi_perjam, [
            'status' => 'hadir',
            'keterangan' => 'koreksi',
        ])->assertOk()->assertJsonPath('data.status', 'hadir');
    }

    public function test_edit_absensi_perjam_melewati_h_plus_1_ditolak(): void
    {
        $this->jsonA('patchJson', '/api/v1/guru/absensi/perjam/'.$this->idAbsensiPerjamLampau, [
            'status' => 'hadir',
        ])->assertStatus(422);
    }

    public function test_edit_absensi_perjam_milik_guru_lain_404(): void
    {
        $recordB = AbsensiPerjam::query()->where('jadwal_pertemuan_idjadwal_pertemuan', $this->idPertemuanB)->firstOrFail();

        $this->jsonA('patchJson', '/api/v1/guru/absensi/perjam/'.$recordB->idabsensi_perjam, [
            'status' => 'alpha',
        ])->assertStatus(404);
    }

    public function test_riwayat_absensi_perjam_hanya_pertemuan_sendiri(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/guru/absensi/perjam');

        $response->assertOk();
        foreach ($response->json('data.items') as $item) {
            $this->assertNotSame('UJI-5B', $item['kelas_nama']);
        }
    }

    public function test_detail_absensi_perjam_milik_guru_lain_404(): void
    {
        $recordB = AbsensiPerjam::query()->where('jadwal_pertemuan_idjadwal_pertemuan', $this->idPertemuanB)->firstOrFail();

        $this->jsonA('getJson', '/api/v1/guru/absensi/perjam/'.$recordB->idabsensi_perjam)
            ->assertStatus(404);
    }

    // ====================================================== Penilaian

    private function idPenugasan(string $mapelKode, int $kelasId): int
    {
        $mapelId = Mapel::query()->where('kode', $mapelKode)->value('idmapel');

        return (int) PenugasanMengajar::query()
            ->where('semester_idsemester', $this->semAktif->idsemester)
            ->where('kelas_idkelas', $kelasId)
            ->where('mapel_idmapel', $mapelId)
            ->where('guru_idguru', $this->profilA->idguru)
            ->value('idpenugasan_mengajar');
    }

    private function idKomponen(string $mapelKode, string $nama): int
    {
        $mapelId = Mapel::query()->where('kode', $mapelKode)->value('idmapel');

        return (int) KomponenNilai::query()
            ->where('semester_idsemester', $this->semAktif->idsemester)
            ->where('mapel_idmapel', $mapelId)
            ->where('nama', $nama)
            ->value('idkomponen_nilai');
    }

    public function test_detail_penugasan_milik_sendiri_berhasil(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);

        $response = $this->jsonA('getJson', '/api/v1/guru/penilaian/penugasan/'.$id);

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.konfigurasi_valid', true)
            ->assertJsonPath('data.terkunci', false)
            ->assertJsonCount(2, 'data.komponen');
    }

    public function test_detail_penugasan_guru_lain_404(): void
    {
        $id = (int) PenugasanMengajar::query()->where('guru_idguru', $this->profilB->idguru)->value('idpenugasan_mengajar');

        $this->jsonA('getJson', '/api/v1/guru/penilaian/penugasan/'.$id)
            ->assertStatus(404)
            ->assertJsonPath('code', 'PENUGASAN_NOT_FOUND');
    }

    public function test_rekap_siswa_penugasan_sendiri_berhasil(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);

        $response = $this->jsonA('getJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/siswa');

        $response->assertOk()->assertJsonPath('data.penugasan_id', $id);
        $this->assertCount(2, $response->json('data.siswa'));
    }

    public function test_rekap_siswa_penugasan_guru_lain_404(): void
    {
        $id = (int) PenugasanMengajar::query()->where('guru_idguru', $this->profilB->idguru)->value('idpenugasan_mengajar');

        $this->jsonA('getJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/siswa')
            ->assertStatus(404);
    }

    public function test_simpan_nilai_batch_berhasil(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);
        $tugas = $this->idKomponen('UJI5-MTK', 'Tugas');
        $ulangan = $this->idKomponen('UJI5-MTK', 'Ulangan');

        $response = $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [
                $this->idSiswaA1 => [$tugas => 80, $ulangan => 90],
                $this->idSiswaA2 => [$tugas => 70, $ulangan => 85],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.tersimpan', 4);

        $this->assertSame('80.00', (string) NilaiSiswa::query()->where('siswa_idsiswa', $this->idSiswaA1)->where('komponen_nilai_idkomponen_nilai', $tugas)->value('nilai'));
    }

    public function test_simpan_nilai_validasi_rentang_ditolak(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);
        $tugas = $this->idKomponen('UJI5-MTK', 'Tugas');

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [$this->idSiswaA1 => [$tugas => 150]],
        ])->assertStatus(422);

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [$this->idSiswaA1 => [$tugas => -5]],
        ])->assertStatus(422);

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => 'bukan-array',
        ])->assertStatus(422);
    }

    public function test_simpan_nilai_komponen_luar_mapel_penugasan_ditolak(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);
        $komponenBin = $this->idKomponen('UJI5-BIN', 'Tugas');

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [$this->idSiswaA1 => [$komponenBin => 88]],
        ])->assertStatus(422);
    }

    public function test_simpan_nilai_siswa_luar_kelas_ditolak(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);
        $tugas = $this->idKomponen('UJI5-MTK', 'Tugas');

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [$this->idSiswaB1 => [$tugas => 88]],
        ])->assertStatus(422);
    }

    public function test_simpan_nilai_penugasan_guru_lain_404(): void
    {
        $id = (int) PenugasanMengajar::query()->where('guru_idguru', $this->profilB->idguru)->value('idpenugasan_mengajar');

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => ['1' => ['1' => 88]],
        ])->assertStatus(404);
    }

    public function test_simpan_nilai_terkunci_finalisasi_ditolak(): void
    {
        $id = $this->idPenugasan('UJI5-IPA', $this->kelasKunci->idkelas);
        $praktik = $this->idKomponen('UJI5-IPA', 'Praktik');

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [$this->idSiswaA3 => [$praktik => 88]],
        ])->assertStatus(422);
    }

    public function test_simpan_nilai_konfigurasi_tidak_valid_ditolak(): void
    {
        // BIN hanya punya komponen bobot 50 -> total aktif != 100 -> input diblokir.
        $id = $this->idPenugasan('UJI5-BIN', $this->kelasA->idkelas);
        $tugas = $this->idKomponen('UJI5-BIN', 'Tugas');

        $this->jsonA('putJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'nilai' => [$this->idSiswaA1 => [$tugas => 88]],
        ])->assertStatus(422);
    }

    public function test_simpan_nilai_tunggal_berhasil(): void
    {
        $id = $this->idPenugasan('UJI5-MTK', $this->kelasA->idkelas);
        $tugas = $this->idKomponen('UJI5-MTK', 'Tugas');

        $response = $this->jsonA('patchJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'siswa_id' => $this->idSiswaA1,
            'komponen_id' => $tugas,
            'nilai' => 95,
        ]);

        $response->assertOk()->assertJsonPath('data.nilai', 95);
    }

    public function test_simpan_nilai_tunggal_guru_lain_404(): void
    {
        $id = (int) PenugasanMengajar::query()->where('guru_idguru', $this->profilB->idguru)->value('idpenugasan_mengajar');

        $this->jsonA('patchJson', '/api/v1/guru/penilaian/penugasan/'.$id.'/nilai', [
            'siswa_id' => $this->idSiswaB1,
            'komponen_id' => 1,
            'nilai' => 90,
        ])->assertStatus(404);
    }

    // ====================================================== Notifikasi

    private function buatNotifikasiUntuk(User $user): int
    {
        $pembuat = $this->buatAdmin();

        return (int) NotifikasiService::buat([$user->id], [
            'judul' => 'Uji Notifikasi',
            'isi' => 'Isi uji notifikasi',
            'jenis' => Notifikasi::JENIS_UMUM,
        ], $pembuat)->idnotifikasi;
    }

    public function test_notifikasi_list_dan_hitung_belum_dibaca(): void
    {
        $this->buatNotifikasiUntuk($this->guruA);

        $response = $this->jsonA('getJson', '/api/v1/guru/notifikasi');
        $response->assertOk()->assertJsonPath('data.belum_dibaca', 1);

        $this->jsonA('getJson', '/api/v1/guru/notifikasi/unread-count')
            ->assertOk()
            ->assertJsonPath('data.belum_dibaca', 1);
    }

    public function test_notifikasi_detail_dan_tandai_dibaca(): void
    {
        $id = $this->buatNotifikasiUntuk($this->guruA);

        $this->jsonA('getJson', '/api/v1/guru/notifikasi/'.$id)->assertOk();
        $this->jsonA('patchJson', '/api/v1/guru/notifikasi/'.$id.'/read')->assertOk()->assertJsonPath('data.dibaca', true);
        $this->jsonA('getJson', '/api/v1/guru/notifikasi/unread-count')->assertOk()->assertJsonPath('data.belum_dibaca', 0);
    }

    public function test_notifikasi_orang_lain_tidak_bisa_diakses(): void
    {
        $id = $this->buatNotifikasiUntuk($this->guruA);

        // Guru B mencoba membaca notifikasi guru A
        $this->jsonB('getJson', '/api/v1/guru/notifikasi/'.$id)->assertStatus(404);
        $this->jsonB('patchJson', '/api/v1/guru/notifikasi/'.$id.'/read')->assertStatus(404);
    }

    public function test_notifikasi_tandai_semua_dibaca(): void
    {
        $this->buatNotifikasiUntuk($this->guruA);
        $this->buatNotifikasiUntuk($this->guruA);

        $this->jsonA('patchJson', '/api/v1/guru/notifikasi/read-all')
            ->assertOk()
            ->assertJsonPath('data.belum_dibaca', 0);
    }
}
