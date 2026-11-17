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
use App\Models\NilaiAkhir;
use App\Models\Notifikasi;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Models\Walimurid;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Master Phase — Guardian API.
 * Berjalan dalam satu transaksi per test (rollback) — data fixture tidak tersisa.
 */
class WaliApiTest extends TestCase
{
    private Tahunajaran $ta;

    private Semester $semAktif;

    private User $waliA;

    private User $waliB;

    private User $guruPengampu;

    private Guru $profilGuru;

    private Kelas $kelasA;

    private User $siswaA1;

    private Siswa $profilA1;

    private User $siswaA2;

    private Siswa $profilA2;

    private User $siswaA3;

    private Siswa $profilA3;

    private User $siswaA4;

    private Siswa $profilA4;

    private User $siswaB1;

    private Siswa $profilB1;

    private int $idSiswaA1;

    private int $idSiswaA2;

    private int $idSiswaA3;

    private int $idSiswaA4;

    private int $idSiswaB1;

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
        Tahunajaran::query()->where('aktif', true)->update(['aktif' => false]);
        Semester::query()->where('aktif', true)->update(['aktif' => false]);

        $this->ta = Tahunajaran::create(['nama' => 'UJI-TA-MASTER', 'aktif' => true]);
        $this->semAktif = Semester::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'Ganjil', 'aktif' => true]);

        [$this->guruPengampu, $this->profilGuru] = $this->buatGuru('P');
        $this->waliA = $this->buatWali('A');
        $this->waliB = $this->buatWali('B');

        $this->kelasA = Kelas::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'UJI-M1A', 'tingkat' => '10', 'jenis' => 'induk', 'rombel' => 1, 'guru_idguru' => $this->profilGuru->idguru]);
        $kelasB = Kelas::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'UJI-M1B', 'tingkat' => '10', 'jenis' => 'induk', 'rombel' => 2, 'guru_idguru' => $this->profilGuru->idguru]);

        [$this->siswaA1, $this->profilA1] = $this->buatSiswa('A1', 'Anak Uji A1');
        [$this->siswaA2, $this->profilA2] = $this->buatSiswa('A2', 'Anak Uji A2');
        [$this->siswaA3, $this->profilA3] = $this->buatSiswa('A3', 'Anak Uji A3');
        [$this->siswaA4, $this->profilA4] = $this->buatSiswa('A4', 'Anak Uji A4');
        [$this->siswaB1, $this->profilB1] = $this->buatSiswa('B1', 'Anak Uji B1');
        $this->idSiswaA1 = $this->profilA1->idsiswa;
        $this->idSiswaA2 = $this->profilA2->idsiswa;
        $this->idSiswaA3 = $this->profilA3->idsiswa;
        $this->idSiswaA4 = $this->profilA4->idsiswa;
        $this->idSiswaB1 = $this->profilB1->idsiswa;

        Kelassiswa::create(['kelas_idkelas' => $this->kelasA->idkelas, 'siswa_idsiswa' => $this->idSiswaA1, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasA->idkelas, 'siswa_idsiswa' => $this->idSiswaA2, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasA->idkelas, 'siswa_idsiswa' => $this->idSiswaA3, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $this->kelasA->idkelas, 'siswa_idsiswa' => $this->idSiswaA4, 'status' => 'aktif']);
        Kelassiswa::create(['kelas_idkelas' => $kelasB->idkelas, 'siswa_idsiswa' => $this->idSiswaB1, 'status' => 'aktif']);

        // Relasi: A verified kemarin, A2 pending, A3 terminated, A4 verified + tanggal_berhenti kemarin, B1 milik waliB.
        $mk = fn (User $wali, int $siswaId, string $status, ?string $mulai = null, ?string $berhenti = null) => Walimurid::create([
            'user_iduser' => $wali->id,
            'siswa_idsiswa' => $siswaId,
            'hubungan' => 'ayah',
            'status_hubungan' => $status,
            'tanggal_mulai' => $mulai,
            'tanggal_berhenti' => $berhenti,
            'tanggal_verifikasi' => $status === 'pending' ? null : now(),
            'pengaju_id' => $wali->id,
            'verifikator_id' => null,
            'catatan' => null,
        ]);
        $mk($this->waliA, $this->idSiswaA1, 'verified', now()->subDay()->format('Y-m-d'));
        $mk($this->waliA, $this->idSiswaA2, 'pending', now()->subDay()->format('Y-m-d'));
        $mk($this->waliA, $this->idSiswaA3, 'terminated', now()->subDays(10)->format('Y-m-d'), now()->subDays(2)->format('Y-m-d'));
        $mk($this->waliA, $this->idSiswaA4, 'verified', now()->subDays(10)->format('Y-m-d'), now()->subDay()->format('Y-m-d'));
        $mk($this->waliB, $this->idSiswaB1, 'verified', now()->subDay()->format('Y-m-d'));

        // Jadwal (hari ini) untuk kelasA & B1's kelasB.
        $mapel = Mapel::create(['kode' => 'UJI-MAS', 'nama' => 'Mapel Uji Master', 'kelompok' => 'wajib']);
        $hariIni = now()->dayOfWeekIso;
        $jamA = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $this->kelasA->idkelas, 'jam_ke' => 1, 'jenis' => 'pelajaran', 'mulai' => '07:00:00', 'selesai' => '07:45:00']);
        $jamB = Jamhari::create(['hari' => $hariIni, 'kelas_id' => $kelasB->idkelas, 'jam_ke' => 1, 'jenis' => 'pelajaran', 'mulai' => '07:00:00', 'selesai' => '07:45:00']);
        $jkA = JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jamA->idjamhari, 'mapel_idmapel' => $mapel->idmapel, 'guru_idguru' => $this->profilGuru->idguru]);
        JadwalKelas::create(['semester_idsemester' => $this->semAktif->idsemester, 'jamhari_idjamhari' => $jamB->idjamhari, 'mapel_idmapel' => $mapel->idmapel, 'guru_idguru' => $this->profilGuru->idguru]);

        $pertemuan = JadwalPertemuan::create([
            'jadwalkelas_idjadwalkelas' => $jkA->idjadwalkelas,
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelasA->idkelas,
            'guru_idguru' => $this->profilGuru->idguru,
            'mapel_idmapel' => $mapel->idmapel,
            'hari' => $hariIni,
            'jam_ke' => 1,
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '07:45:00',
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'terjadwal',
        ]);

        // Absensi: A1 & A2 (kelasA), B1 (kelasB) — untuk uji bahwa A2/B1 tidak bocor ke waliA.
        $mkAbs = fn (int $kelasId, int $siswaId, string $status) => AbsensiHarian::create([
            'tahunajaran_idtahunajaran' => $this->ta->idtahunajaran,
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $kelasId,
            'tanggal' => now()->format('Y-m-d'),
            'siswa_idsiswa' => $siswaId,
            'status' => $status,
            'catatan' => null,
            'diinput_oleh' => $this->guruPengampu->id,
        ]);
        $mkAbs($this->kelasA->idkelas, $this->idSiswaA1, 'hadir');
        $mkAbs($this->kelasA->idkelas, $this->idSiswaA2, 'hadir');
        $mkAbs($kelasB->idkelas, $this->idSiswaB1, 'hadir');

        AbsensiPerjam::create(['jadwal_pertemuan_idjadwal_pertemuan' => $pertemuan->idjadwal_pertemuan, 'siswa_idsiswa' => $this->idSiswaA1, 'status' => 'hadir', 'keterangan' => null, 'diinput_oleh' => $this->guruPengampu->id]);

        // Nilai akhir + finalisasi rapor (A1 & kelasA) — untuk uji nilai/rapor.
        KomponenNilai::create(['semester_idsemester' => $this->semAktif->idsemester, 'mapel_idmapel' => $mapel->idmapel, 'nama' => 'Tugas', 'bobot' => 100, 'urutan' => 1, 'aktif' => true]);
        $admin = $this->buatAdmin();
        FinalisasiRapor::create([
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelasA->idkelas,
            'status' => FinalisasiRapor::STATUS_FINAL,
            'wali_kelas_idguru' => $this->profilGuru->idguru,
            'difinalisasi_oleh' => $admin->id,
            'difinalisasi_pada' => now(),
        ]);
        NilaiAkhir::create([
            'semester_idsemester' => $this->semAktif->idsemester,
            'kelas_idkelas' => $this->kelasA->idkelas,
            'mapel_idmapel' => $mapel->idmapel,
            'siswa_idsiswa' => $this->idSiswaA1,
            'nilai_akhir' => 85,
            'nilai_kktp' => 75,
            'dihitung_pada' => now(),
        ]);

        // Notifikasi untuk wali A (dan B untuk uji isolasi).
        NotifikasiService::buat([$this->waliA->id], [
            'judul' => 'Uji Notifikasi Wali A',
            'isi' => 'Isi notifikasi wali A',
            'jenis' => Notifikasi::JENIS_UMUM,
        ], $admin);
        NotifikasiService::buat([$this->waliB->id], [
            'judul' => 'Uji Notifikasi Wali B',
            'isi' => 'Isi notifikasi wali B',
            'jenis' => Notifikasi::JENIS_UMUM,
        ], $admin);
    }

    // ------------------------------------------------------------------ helpers

    private function buatGuru(string $suffix): array
    {
        $user = $this->buatUser('Guru Uji '.$suffix, 'guru');
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

    private function buatWali(string $suffix): User
    {
        return $this->buatUser('Wali Uji '.$suffix, 'wali');
    }

    private function buatSiswa(string $suffix, string $nama): array
    {
        $user = $this->buatUser($nama, 'siswa');
        $siswa = Siswa::create([
            'user_iduser' => $user->id,
            'nisn' => str_pad((string) ($user->id * 7 % 10000000000), 10, '0', STR_PAD_LEFT),
            'nis' => 'NIS'.$user->id,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-01-01',
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }

    private function buatAdmin(): User
    {
        return $this->buatUser('Admin Uji Master', 'superadmin');
    }

    private function buatUser(string $nama, string $level): User
    {
        $suffix = random_int(1000, 9999).now()->format('His');

        return User::create([
            'name' => $nama.' '.$suffix,
            'username' => strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $nama)).'_'.$suffix,
            'email' => 'ujim-'.$level.'-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => $level,
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
    }

    private function bearer(User $user): string
    {
        return $user->createToken('HP Uji', ['mobile-api'])->plainTextToken;
    }

    private function authA(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->waliA)];
    }

    private function authB(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->waliB)];
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

    // ============================================================== Auth & role

    public function test_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/v1/wali/profil')->assertStatus(401);
    }

    public function test_siswa_dan_guru_ditolak(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($this->siswaA1)])
            ->getJson('/api/v1/wali/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($this->guruPengampu)])
            ->getJson('/api/v1/wali/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_admin_dan_superadmin_ditolak(): void
    {
        $admin = $this->buatAdmin();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($admin)])
            ->getJson('/api/v1/wali/profil')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    // ====================================================== Profil & beranda

    public function test_profil_memuat_ringkasan_relasi(): void
    {
        $this->jsonA('getJson', '/api/v1/wali/profil')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.relasi.verified', 2)   // A1 (verified) + A4 (verified tapi berakhir — tetap dihitung sebagai verified)
            ->assertJsonPath('data.relasi.pending', 1)
            ->assertJsonPath('data.relasi.terminated', 1);
    }

    public function test_beranda_memuat_anak_verified_saja(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/wali/beranda');

        $response->assertOk()
            ->assertJsonPath('data.ringkasan.jumlah_anak', 2)      // A1 + A4
            ->assertJsonPath('data.ringkasan.jumlah_menunggu', 1)  // A2
            ->assertJsonPath('data.ringkasan.notifikasi_belum_dibaca', 1);

        $ids = collect($response->json('data.anak'))->pluck('id')->all();
        $this->assertContains($this->idSiswaA1, $ids);
        $this->assertContains($this->idSiswaA4, $ids);
        $this->assertNotContains($this->idSiswaA2, $ids);
        $this->assertNotContains($this->idSiswaB1, $ids);
    }

    // ====================================================== Relasi anak

    public function test_daftar_anak_semua_status(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/wali/anak');

        $response->assertOk();
        $ids = collect($response->json('data.anak'))->pluck('siswa.id')->all();
        $this->assertContains($this->idSiswaA1, $ids);
        $this->assertContains($this->idSiswaA2, $ids);
        $this->assertContains($this->idSiswaA3, $ids);
        $this->assertContains($this->idSiswaA4, $ids);
        $this->assertNotContains($this->idSiswaB1, $ids);

        $a1 = collect($response->json('data.anak'))->firstWhere('siswa.id', $this->idSiswaA1);
        $this->assertTrue($a1['bisa_akses_akademik']);
        $a2 = collect($response->json('data.anak'))->firstWhere('siswa.id', $this->idSiswaA2);
        $this->assertFalse($a2['bisa_akses_akademik']);
        $this->assertSame('pending', $a2['status_hubungan']);
    }

    public function test_detail_anak_memuat_histori(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1);

        $response->assertOk()
            ->assertJsonPath('data.status_hubungan', 'verified')
            ->assertJsonStructure(['data' => ['histori']]);
    }

    public function test_detail_anak_milik_wali_lain_404(): void
    {
        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaB1)
            ->assertStatus(404)
            ->assertJsonPath('code', 'ANAK_NOT_FOUND');
    }

    public function test_permintaan_putus_pada_relasi_verified_berhasil(): void
    {
        $response = $this->jsonA('postJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/permintaan-putus', [
            'alasan' => 'Anak sudah mandiri dan pindah tempat tinggal',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status_permintaan', 'menunggu');

        // Permintaan kedua untuk relasi yang sama ditolak (sudah menunggu).
        $this->jsonA('postJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/permintaan-putus', [
            'alasan' => 'Masih mau mengajukan lagi dengan alasan lain',
        ])->assertStatus(422);
    }

    public function test_permintaan_putus_pada_relasi_pending_ditolak(): void
    {
        $this->jsonA('postJson', '/api/v1/wali/anak/'.$this->idSiswaA2.'/permintaan-putus', [
            'alasan' => 'Mau menghentikan relasi pending',
        ])->assertStatus(422);
    }

    public function test_permintaan_putus_anak_wali_lain_404(): void
    {
        $this->jsonA('postJson', '/api/v1/wali/anak/'.$this->idSiswaB1.'/permintaan-putus', [
            'alasan' => 'Mencoba mengajukan relasi orang lain',
        ])->assertStatus(404);
    }

    public function test_permintaan_putus_validasi_alasan(): void
    {
        $this->jsonA('postJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/permintaan-putus', [
            'alasan' => 'abc',
        ])->assertStatus(422);
    }

    // ====================================================== Akademik anak

    public function test_akademik_anak_verified_berhasil(): void
    {
        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/beranda')
            ->assertOk()
            ->assertJsonPath('data.siswa.id', $this->idSiswaA1);

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/jadwal/hari-ini')
            ->assertOk()
            ->assertJsonPath('data.tanggal', now()->format('Y-m-d'));

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/jadwal')
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/absensi/harian')
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/absensi/periode?dari='.now()->subDay()->format('Y-m-d').'&sampai='.now()->format('Y-m-d'))
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/absensi/perjam')
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/nilai')
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/rapor')
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/rapor/'.$this->semAktif->idsemester.'/'.$this->kelasA->idkelas)
            ->assertOk();
    }

    public function test_akademik_anak_pending_ditolak(): void
    {
        foreach (['beranda', 'jadwal', 'jadwal/hari-ini', 'absensi/harian', 'absensi/perjam', 'nilai', 'rapor'] as $endpoint) {
            $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA2.'/'.$endpoint)
                ->assertStatus(403)
                ->assertJsonPath('code', 'AKSES_DITOLAK');
        }
    }

    public function test_akademik_anak_terminated_ditolak(): void
    {
        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA3.'/beranda')
            ->assertStatus(403)
            ->assertJsonPath('code', 'AKSES_DITOLAK');
    }

    public function test_akademik_anak_berakhir_ditolak(): void
    {
        // Relasi verified tetapi tanggal_berhenti sudah lewat → akses hilang.
        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA4.'/beranda')
            ->assertStatus(403)
            ->assertJsonPath('code', 'AKSES_DITOLAK');
    }

    public function test_akademik_anak_wali_lain_ditolak(): void
    {
        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaB1.'/beranda')
            ->assertStatus(403)
            ->assertJsonPath('code', 'AKSES_DITOLAK');

        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaB1.'/rapor/'.$this->semAktif->idsemester.'/9999')
            ->assertStatus(403);
    }

    public function test_absensi_periode_wajib_dari_sampai(): void
    {
        $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/absensi/periode')
            ->assertStatus(422);
    }

    public function test_rapor_belum_final_tidak_ada(): void
    {
        // A1 punya rapor final; A2 tidak (dan A2 memang pending → 403).
        $response = $this->jsonA('getJson', '/api/v1/wali/anak/'.$this->idSiswaA1.'/rapor');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.rapor'));
    }

    // ====================================================== Notifikasi

    public function test_notifikasi_hanya_milik_sendiri(): void
    {
        $response = $this->jsonA('getJson', '/api/v1/wali/notifikasi');
        $response->assertOk()->assertJsonPath('data.belum_dibaca', 1);

        $this->jsonA('getJson', '/api/v1/wali/notifikasi/unread-count')
            ->assertOk()
            ->assertJsonPath('data.belum_dibaca', 1);

        // Notifikasi wali B: wali A tidak bisa membaca/menandai.
        $idMilikB = (int) Notifikasi::query()->whereHas('penerima', fn ($q) => $q->where('user_iduser', $this->waliB->id))->value('idnotifikasi');
        $this->jsonA('getJson', '/api/v1/wali/notifikasi/'.$idMilikB)->assertStatus(404);
        $this->jsonA('patchJson', '/api/v1/wali/notifikasi/'.$idMilikB.'/read')->assertStatus(404);

        // Tandai milik sendiri → 200
        $idMilikA = (int) Notifikasi::query()->whereHas('penerima', fn ($q) => $q->where('user_iduser', $this->waliA->id))->value('idnotifikasi');
        $this->jsonA('patchJson', '/api/v1/wali/notifikasi/'.$idMilikA.'/read')->assertOk();
        $this->jsonA('getJson', '/api/v1/wali/notifikasi/unread-count')->assertOk()->assertJsonPath('data.belum_dibaca', 0);
    }

    // ====================================================== Keuangan (via grup /keuangan)

    public function test_keuangan_anak_verified_boleh_dan_pending_ditolak(): void
    {
        $this->jsonA('getJson', '/api/v1/keuangan/ringkasan?siswa_id='.$this->idSiswaA1)
            ->assertOk();

        $this->jsonA('getJson', '/api/v1/keuangan/ringkasan?siswa_id='.$this->idSiswaA2)
            ->assertStatus(403);

        $this->jsonA('getJson', '/api/v1/keuangan/ringkasan?siswa_id='.$this->idSiswaB1)
            ->assertStatus(403);
    }
}
