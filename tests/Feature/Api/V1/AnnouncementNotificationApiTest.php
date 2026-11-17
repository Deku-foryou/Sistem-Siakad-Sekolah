<?php

namespace Tests\Feature\Api\V1;

use App\Models\Notifikasi;
use App\Models\NotifikasiPenerima;
use App\Models\Pengumuman;
use App\Models\PengumumanBaca;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 3 — Announcement & Notification API (siswa).
 * Fixture rollback penuh (transaksi); tidak ada perubahan data yang tersisa.
 */
class AnnouncementNotificationApiTest extends TestCase
{
    private Tahunajaran $ta;

    private Semester $sem;

    private User $siswaA;

    private Siswa $profilA;

    private User $siswaB;

    private Siswa $profilB;

    private User $superadmin;

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

        $this->ta = Tahunajaran::create(['nama' => 'UJI-TA-FASE3', 'aktif' => true]);
        $this->sem = Semester::create(['tahunajaran_idtahunajaran' => $this->ta->idtahunajaran, 'nama' => 'Ganjil', 'aktif' => true]);

        [$this->siswaA, $this->profilA] = $this->buatSiswa('A', 'Siswa Uji A');
        [$this->siswaB, $this->profilB] = $this->buatSiswa('B', 'Siswa Uji B');

        $suffix = random_int(1000, 9999).now()->format('His');
        $this->superadmin = User::create([
            'name' => 'Superadmin Uji '.$suffix,
            'username' => 'uuji_sa_'.$suffix,
            'email' => 'uuji-sa-'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'superadmin',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
    }

    private function buatSiswa(string $suffix, string $nama): array
    {
        $suffix .= '_'.random_int(1000, 9999).now()->format('His');
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
            'nisn' => str_pad((string) ($user->id * 3 % 10000000000), 10, '0', STR_PAD_LEFT),
            'nis' => 'NIS'.$user->id,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }

    /** Buat pengumuman dengan snapshot untuk siswa tertentu (langsung, tanpa alur terbit). */
    private function buatPengumuman(string $judul, string $status, ?string $berlakuDari, ?string $berlakuSampai, array $penerimaSiswaIds): Pengumuman
    {
        $pengumuman = Pengumuman::create([
            'judul' => $judul,
            'isi' => 'Isi '.$judul,
            'kategori' => 'akademik',
            'prioritas' => 'sedang',
            'status' => $status,
            'waktu_terbit' => $status === 'draf' ? null : now(),
            'berlaku_dari' => $berlakuDari,
            'berlaku_sampai' => $berlakuSampai,
            'target' => 'semua',
            'dibuat_oleh' => $this->superadmin->id,
            'diperbarui_oleh' => $this->superadmin->id,
        ]);

        foreach ($penerimaSiswaIds as $siswaId) {
            PengumumanBaca::create([
                'pengumuman_idpengumuman' => $pengumuman->idpengumuman,
                'siswa_idsiswa' => $siswaId,
                'dibaca_pada' => null,
            ]);
        }

        return $pengumuman;
    }

    private function buatNotifikasi(array $penerimaUserIds, string $jenis = 'umum', bool $baca = false): Notifikasi
    {
        $notifikasi = NotifikasiService::buat($penerimaUserIds, [
            'judul' => 'Info uji '.$jenis,
            'isi' => 'Isi notifikasi uji '.$jenis,
            'jenis' => $jenis,
            'prioritas' => 'sedang',
            'sumber_tabel' => 'pengumuman',
            'sumber_id' => 123,
            'link' => '/pengumuman-siswa/123',
        ], $this->superadmin);

        if ($baca) {
            foreach ($penerimaUserIds as $userId) {
                NotifikasiPenerima::query()
                    ->where('notifikasi_idnotifikasi', $notifikasi->idnotifikasi)
                    ->where('user_iduser', $userId)
                    ->update(['dibaca_pada' => now()->subDay()]);
            }
        }

        return $notifikasi->fresh('penerima');
    }

    private function bearer(User $user, string $device = 'HP Uji'): string
    {
        return $user->createToken($device, ['mobile-api'])->plainTextToken;
    }

    private function jsonA(string $method, string $url, array $data = [])
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($this->siswaA)])->{$method}($url, $data);
    }

    private function jsonB(string $method, string $url, array $data = [])
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($this->siswaB)])->{$method}($url, $data);
    }

    // ============================================================== Auth/role

    public function test_guru_wali_admin_superadmin_ditolak(): void
    {
        foreach (['guru', 'wali', 'admin', 'superadmin'] as $level) {
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

            // Guru harus punya record guru agar dianggap aktif (LoginBlockService)
            if ($level === 'guru') {
                \App\Models\Guru::create([
                    'user_iduser' => $user->id,
                    'nip' => 'NIP'.$user->id,
                    'nama' => $user->name,
                    'jenis_kelamin' => 'L',
                    'tanggal_lahir' => '1980-01-01',
                    'status' => 'aktif',
                ]);
            }

            \Illuminate\Support\Facades\Auth::forgetGuards();
            $this->withHeaders(['Authorization' => 'Bearer '.$this->bearer($user)])
                ->getJson('/api/v1/siswa/pengumuman')
                ->assertStatus(403)
                ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
        }
    }

    public function test_token_invalid_ditolak(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer token-salah'])
            ->getJson('/api/v1/siswa/pengumuman')
            ->assertStatus(401);
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $token = $this->bearer($this->siswaA);
        $this->profilA->update(['status' => 'lulus']);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/siswa/pengumuman')
            ->assertStatus(403);
    }

    public function test_wajib_ganti_password_memblokir_endpoint(): void
    {
        $this->siswaA->update(['wajib_ganti_password' => true]);
        $token = $this->bearer($this->siswaA);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/siswa/pengumuman')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    // ============================================================ Pengumuman

    public function test_pengumuman_aktif_hanya_milik_target(): void
    {
        $semuaA = $this->buatPengumuman('Umum Semua', 'terbit', null, null, [$this->profilA->idsiswa, $this->profilB->idsiswa]);
        $hanyaB = $this->buatPengumuman('Hanya B', 'terbit', null, null, [$this->profilB->idsiswa]);
        $draft = $this->buatPengumuman('Masih Draft', 'draf', null, null, [$this->profilA->idsiswa, $this->profilB->idsiswa]);
        $belumMulai = $this->buatPengumuman('Belum Berlaku', 'terbit', now()->addDay()->format('Y-m-d'), null, [$this->profilA->idsiswa]);
        $arsip = $this->buatPengumuman('Sudah Arsip', 'arsip', null, null, [$this->profilA->idsiswa]);
        $cerai = $this->buatPengumuman('Baru Kedaluwarsa', 'terbit', null, now()->subDay()->format('Y-m-d'), [$this->profilA->idsiswa]);

        $response = $this->jsonA('get', '/api/v1/siswa/pengumuman');
        $response->assertStatus(200);
        $judul = collect($response->json('data.items'))->pluck('judul')->values()->all();

        $this->assertContains('Umum Semua', $judul);
        $this->assertNotContains('Hanya B', $judul);       // bukan target A
        $this->assertNotContains('Masih Draft', $judul);   // draf
        $this->assertNotContains('Belum Berlaku', $judul); // belum berlaku_dari
        $this->assertNotContains('Sudah Arsip', $judul);   // arsip tidak di tab aktif
        $this->assertNotContains('Baru Kedaluwarsa', $judul);   // kedaluwarsa tidak di tab aktif
    }

    public function test_pengumuman_riwayat_menampilkan_kedaluwarsa_dan_arsip(): void
    {
        $this->buatPengumuman('Baru Kedaluwarsa', 'terbit', null, now()->subDay()->format('Y-m-d'), [$this->profilA->idsiswa]);
        $this->buatPengumuman('Sudah Arsip', 'arsip', null, null, [$this->profilA->idsiswa]);
        $this->buatPengumuman('Umum Semua', 'terbit', null, null, [$this->profilA->idsiswa]);

        $response = $this->jsonA('get', '/api/v1/siswa/pengumuman?tab=riwayat');
        $response->assertStatus(200);
        $judul = collect($response->json('data.items'))->pluck('judul')->values()->all();

        $this->assertContains('Baru Kedaluwarsa', $judul);
        $this->assertContains('Sudah Arsip', $judul);
        $this->assertNotContains('Umum Semua', $judul);
    }

    public function test_pengumuman_detail_dan_penandaan_dibaca(): void
    {
        $pub = $this->buatPengumuman('Detail Saya', 'terbit', null, null, [$this->profilA->idsiswa]);

        $detail = $this->jsonA('get', '/api/v1/siswa/pengumuman/'.$pub->idpengumuman);
        $detail->assertStatus(200)->assertJsonPath('data.judul', 'Detail Saya')->assertJsonPath('data.dibaca', true);

        // Idempoten: dibaca_pada tidak berubah pada pembukaan kedua
        $lagi = $this->jsonA('get', '/api/v1/siswa/pengumuman/'.$pub->idpengumuman);
        $this->assertSame($detail->json('data.dibaca_pada'), $lagi->json('data.dibaca_pada'));
    }

    public function test_pengumuman_detail_bukan_milik_target_ditolak(): void
    {
        $hanyaB = $this->buatPengumuman('Rahasia B', 'terbit', null, null, [$this->profilB->idsiswa]);

        $this->jsonA('get', '/api/v1/siswa/pengumuman/'.$hanyaB->idpengumuman)
            ->assertStatus(404)
            ->assertJsonPath('code', 'PENGUMUMAN_NOT_FOUND');
    }

    public function test_pengumuman_draf_dan_belum_berlaku_tidak_bisa_diakses_langsung(): void
    {
        $draft = $this->buatPengumuman('Draft', 'draf', null, null, [$this->profilA->idsiswa]);
        $belumMulai = $this->buatPengumuman('Belum Berlaku', 'terbit', now()->addDay()->format('Y-m-d'), null, [$this->profilA->idsiswa]);

        $this->jsonA('get', '/api/v1/siswa/pengumuman/'.$draft->idpengumuman)->assertStatus(404);
        $this->jsonA('get', '/api/v1/siswa/pengumuman/'.$belumMulai->idpengumuman)->assertStatus(404);
    }

    public function test_pengumuman_tab_tidak_valid_ditolak(): void
    {
        $this->jsonA('get', '/api/v1/siswa/pengumuman?tab=semua')->assertStatus(422);
    }

    public function test_pengumuman_per_page_dan_halaman(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->buatPengumuman('Pengumuman '.$i, 'terbit', null, null, [$this->profilA->idsiswa]);
        }

        $page1 = $this->jsonA('get', '/api/v1/siswa/pengumuman?per_page=15&page=1');
        $page1->assertStatus(200);
        $this->assertSame(15, count($page1->json('data.items')));
        $this->assertSame(2, $page1->json('meta.last_page'));

        $page2 = $this->jsonA('get', '/api/v1/siswa/pengumuman?per_page=15&page=2');
        $this->assertSame(5, count($page2->json('data.items')));
    }

    // ============================================================= Notifikasi

    public function test_notifikasi_hanya_milik_user(): void
    {
        $this->buatNotifikasi([$this->siswaA->id], 'umum');
        $this->buatNotifikasi([$this->siswaB->id], 'umum');

        $response = $this->jsonA('get', '/api/v1/siswa/notifikasi');
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame(1, $response->json('data.belum_dibaca'));

        $this->jsonB('get', '/api/v1/siswa/notifikasi')->assertJsonPath('meta.total', 1);
    }

    public function test_unread_count_akurat(): void
    {
        $this->buatNotifikasi([$this->siswaA->id], 'umum');
        $this->buatNotifikasi([$this->siswaA->id], 'rapor');
        $this->buatNotifikasi([$this->siswaA->id], 'umum', true); // sudah dibaca
        $this->buatNotifikasi([$this->siswaB->id], 'umum');      // milik B, tidak dihitung

        $this->jsonA('get', '/api/v1/siswa/notifikasi/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.belum_dibaca', 2);
    }

    public function test_filter_status_dan_jenis(): void
    {
        $this->buatNotifikasi([$this->siswaA->id], 'umum');
        $this->buatNotifikasi([$this->siswaA->id], 'rapor');
        $this->buatNotifikasi([$this->siswaA->id], 'rapor', true);

        $unread = $this->jsonA('get', '/api/v1/siswa/notifikasi?status=unread');
        $this->assertSame(2, $unread->json('meta.total'));

        $read = $this->jsonA('get', '/api/v1/siswa/notifikasi?status=read');
        $this->assertSame(1, $read->json('meta.total'));

        $jenis = $this->jsonA('get', '/api/v1/siswa/notifikasi?jenis=rapor');
        $this->assertSame(2, $jenis->json('meta.total'));

        $this->jsonA('get', '/api/v1/siswa/notifikasi?status=kosong')->assertStatus(422);
    }

    public function test_detail_notifikasi_milik_user_lain_ditolak(): void
    {
        $nB = $this->buatNotifikasi([$this->siswaB->id], 'umum');

        $this->jsonA('get', '/api/v1/siswa/notifikasi/'.$nB->idnotifikasi)
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOTIFIKASI_NOT_FOUND');
    }

    public function test_tandai_satu_dibaca_idempoten(): void
    {
        $nA = $this->buatNotifikasi([$this->siswaA->id], 'umum');

        $pertama = $this->jsonA('patch', '/api/v1/siswa/notifikasi/'.$nA->idnotifikasi.'/read');
        $pertama->assertStatus(200)
            ->assertJsonPath('data.dibaca', true);

        $dibacaPadaPertama = $pertama->json('data.dibaca_pada');
        $this->assertNotNull($dibacaPadaPertama);

        $kedua = $this->jsonA('patch', '/api/v1/siswa/notifikasi/'.$nA->idnotifikasi.'/read');
        $kedua->assertStatus(200);
        $this->assertSame($dibacaPadaPertama, $kedua->json('data.dibaca_pada'));

        $penerima = NotifikasiPenerima::where('notifikasi_idnotifikasi', $nA->idnotifikasi)->where('user_iduser', $this->siswaA->id)->first();
        $this->assertNotNull($penerima->dibaca_pada);
    }

    public function test_tandai_dibaca_notifikasi_user_lain_ditolak(): void
    {
        $nB = $this->buatNotifikasi([$this->siswaB->id], 'umum');

        $this->jsonA('patch', '/api/v1/siswa/notifikasi/'.$nB->idnotifikasi.'/read')
            ->assertStatus(404);

        $penerima = NotifikasiPenerima::where('notifikasi_idnotifikasi', $nB->idnotifikasi)->where('user_iduser', $this->siswaB->id)->first();
        $this->assertNull($penerima->dibaca_pada, 'Penerima user lain tidak boleh ditandai');
    }

    public function test_read_all_hanya_mengubah_milik_user(): void
    {
        $this->buatNotifikasi([$this->siswaA->id], 'umum');
        $this->buatNotifikasi([$this->siswaA->id], 'rapor');
        $this->buatNotifikasi([$this->siswaA->id], 'umum', true);
        $nB = $this->buatNotifikasi([$this->siswaB->id], 'umum');

        $response = $this->jsonA('patch', '/api/v1/siswa/notifikasi/read-all');
        $response->assertStatus(200)
            ->assertJsonPath('data.ditandai', 2)
            ->assertJsonPath('data.belum_dibaca', 0);

        $this->jsonA('get', '/api/v1/siswa/notifikasi/unread-count')->assertJsonPath('data.belum_dibaca', 0);

        $penerimaB = NotifikasiPenerima::where('notifikasi_idnotifikasi', $nB->idnotifikasi)->where('user_iduser', $this->siswaB->id)->first();
        $this->assertNull($penerimaB->dibaca_pada, 'Notifikasi milik B tidak boleh ikut ditandai');
    }

    public function test_read_all_idempoten(): void
    {
        $this->buatNotifikasi([$this->siswaA->id], 'umum');
        $this->buatNotifikasi([$this->siswaA->id], 'rapor');

        $this->jsonA('patch', '/api/v1/siswa/notifikasi/read-all')->assertJsonPath('data.ditandai', 2);
        $this->jsonA('patch', '/api/v1/siswa/notifikasi/read-all')->assertJsonPath('data.ditandai', 0);
    }

    public function test_multi_perangkat_sama_status_baca(): void
    {
        $nA = $this->buatNotifikasi([$this->siswaA->id], 'umum');

        $tokenHp1 = $this->bearer($this->siswaA, 'HP Satu');
        $tokenHp2 = $this->bearer($this->siswaA, 'HP Dua');

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenHp1])
            ->patchJson('/api/v1/siswa/notifikasi/'.$nA->idnotifikasi.'/read')
            ->assertStatus(200);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenHp2])
            ->getJson('/api/v1/siswa/notifikasi')
            ->assertJsonPath('data.items.0.dibaca', true);
    }

    // =================================================================== IDOR

    public function test_pengumuman_dan_notifikasi_idor(): void
    {
        // Pengumuman target B tidak dapat diakses A lewat penggantian ID
        $rahasiaB = $this->buatPengumuman('Rahasia B', 'terbit', null, null, [$this->profilB->idsiswa]);
        $this->jsonA('get', '/api/v1/siswa/pengumuman/'.$rahasiaB->idpengumuman)->assertStatus(404);

        // Notifikasi B tidak dapat diakses/ditandai A lewat penggantian ID
        $nB = $this->buatNotifikasi([$this->siswaB->id], 'umum');
        $this->jsonA('get', '/api/v1/siswa/notifikasi/'.$nB->idnotifikasi)->assertStatus(404);
        $this->jsonA('patch', '/api/v1/siswa/notifikasi/'.$nB->idnotifikasi.'/read')->assertStatus(404);

        // Parameter identitas penerima diabaikan (tidak ada yang mengubah scope)
        $daftar = $this->jsonA('get', '/api/v1/siswa/notifikasi?user_id='.$this->siswaB->id);
        $daftar->assertJsonPath('meta.total', 2 === 2 ? $daftar->json('meta.total') : null); // same as without param
        $this->assertSame(
            $this->jsonA('get', '/api/v1/siswa/notifikasi')->json('meta.total'),
            $daftar->json('meta.total')
        );
    }

    public function test_tidak_ada_kebocoran_relasi_penerima(): void
    {
        $this->buatNotifikasi([$this->siswaA->id, $this->siswaB->id], 'umum');

        $response = $this->jsonA('get', '/api/v1/siswa/notifikasi');
        foreach ($response->json('data.items') as $item) {
            $this->assertArrayNotHasKey('penerima', $item);
            $this->assertArrayNotHasKey('penerima_lain', $item);
            // hanya kolom yang dipilih resource
            $this->assertArrayHasKey('dibaca', $item);
            $this->assertArrayHasKey('dibaca_pada', $item);
        }
    }
}
