<?php

namespace Tests\Feature\Api\V1;

use App\Models\Kuitansi;
use App\Models\Pembayaran;
use App\Models\RefundDokumen;
use App\Models\RefundPengajuan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Tahunajaran;
use App\Models\User;
use App\Models\Walimurid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Master Phase — API unduh dokumen keuangan privat (siswa & wali).
 * File uji disimpan sementara di storage/app/private; dihapus di tearDown.
 */
class KeuanganFileApiTest extends TestCase
{
    private User $siswaA;

    private Siswa $profilA;

    private User $waliA;

    private User $waliLain;

    private Tagihan $tagihan;

    private Pembayaran $pembayaran;

    private int $idSiswaA;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        DB::beginTransaction();
        $this->bangunFixture();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        DB::rollBack();
        parent::tearDown();
    }

    private function bangunFixture(): void
    {
        $suf = random_int(1000, 9999).now()->format('His');

        Tahunajaran::create(['nama' => 'UJI-TA-FILE', 'aktif' => false]);

        $this->siswaA = User::create([
            'name' => 'Siswa File '.$suf,
            'username' => 'ujim_file_siswa_'.$suf,
            'email' => 'ujim-file-siswa-'.$suf.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'siswa',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->profilA = Siswa::create([
            'user_iduser' => $this->siswaA->id,
            'nisn' => str_pad((string) ($this->siswaA->id * 7 % 10000000000), 10, '0', STR_PAD_LEFT),
            'nis' => 'NIS'.$this->siswaA->id,
            'nama' => 'Siswa File '.$suf,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-01-01',
            'status' => 'aktif',
        ]);
        $this->idSiswaA = $this->profilA->idsiswa;

        $this->waliA = User::create([
            'name' => 'Wali File '.$suf,
            'username' => 'ujim_file_wali_'.$suf,
            'email' => 'ujim-file-wali-'.$suf.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'wali',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $this->waliLain = User::create([
            'name' => 'Wali Lain '.$suf,
            'username' => 'ujim_file_walilain_'.$suf,
            'email' => 'ujim-file-walilain-'.$suf.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'wali',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);

        Walimurid::create([
            'user_iduser' => $this->waliA->id,
            'siswa_idsiswa' => $this->idSiswaA,
            'hubungan' => 'ayah',
            'status_hubungan' => 'verified',
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
            'tanggal_verifikasi' => now(),
            'pengaju_id' => $this->waliA->id,
        ]);

        $this->tagihan = Tagihan::create([
            'siswa_idsiswa' => $this->idSiswaA,
            'jenis' => 'spp',
            'nama' => 'SPP Uji File',
            'nominal' => 100000,
            'total_tagihan' => 100000,
            'tanggal_terbit' => now(),
            'tanggal_jatuh_tempo' => now()->addDays(7),
            'status' => 'belum_dibayar',
            'periode' => '2026/2027',
        ]);

        // Bukti pembayaran (file sungguhan di private storage).
        $buktiRel = 'tmp-uji-masterapi/bukti-'.$suf.'.pdf';
        $this->tulisFileUji($buktiRel, 'BUKTI-'.$suf);

        $this->pembayaran = Pembayaran::create([
            'tagihan_idtagihan' => $this->tagihan->idtagihan,
            'siswa_idsiswa' => $this->idSiswaA,
            'nominal_dibayar' => 100000,
            'metode' => 'transfer',
            'provider' => 'manual',
            'status' => 'success',
            'dibayar_pada' => now(),
            'bukti_path' => $buktiRel,
            'bukti_nama_asli' => 'bukti-pembayaran.pdf',
            'bukti_mime' => 'application/pdf',
            'bukti_ukuran' => 12,
            'payer_user_id' => $this->waliA->id,
            'payer_jenis' => 'wali',
            'payer_nama' => 'Wali File',
        ]);

        // Kuitansi (file + baris DB).
        $kuitansiRel = 'tmp-uji-masterapi/kuitansi-'.$suf.'.pdf';
        $this->tulisFileUji($kuitansiRel, 'KUITANSI-'.$suf);
        Kuitansi::create([
            'pembayaran_idpembayaran' => $this->pembayaran->idpembayaran,
            'siswa_idsiswa' => $this->idSiswaA,
            'tagihan_idtagihan' => $this->tagihan->idtagihan,
            'nomor' => 'KT/2026/11/'.$suf,
            'tahun' => 2026,
            'bulan' => 11,
            'nomor_urut' => 1,
            'snapshot_sekolah' => ['nama' => 'Sekolah Uji'],
            'snapshot_kuitansi' => ['nomor' => 'KT/2026/11/'.$suf],
            'file_path' => $kuitansiRel,
            'checksum_sha256' => hash_file('sha256', storage_path('app/private/'.$kuitansiRel)),
            'ukuran_file' => 12,
            'dibuat_oleh' => $this->waliA->id,
        ]);

        // Dokumen refund (file + checksum valid).
        $refundRel = 'tmp-uji-masterapi/refund-'.$suf.'.pdf';
        $this->tulisFileUji($refundRel, 'REFUND-'.$suf);
        $admin = User::create([
            'name' => 'Admin File '.$suf,
            'username' => 'ujim_file_admin_'.$suf,
            'email' => 'ujim-file-admin-'.$suf.'@example.test',
            'password' => bcrypt('password123'),
            'level' => 'superadmin',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);

        $refund = RefundPengajuan::create([
            'pembayaran_idpembayaran' => $this->pembayaran->idpembayaran,
            'siswa_idsiswa' => $this->idSiswaA,
            'tagihan_idtagihan' => $this->tagihan->idtagihan,
            'nominal_refund' => 100000,
            'alasan' => 'Uji refund file',
            'metode' => 'transfer_manual',
            'status' => 'terproses',
            'diajukan_oleh' => $admin->id,
        ]);

        RefundDokumen::create([
            'refund_pengajuan_id' => $refund->id,
            'nomor_dokumen' => 'RF/2026/11/'.$suf,
            'file_path' => $refundRel,
            'checksum_sha256' => hash_file('sha256', storage_path('app/private/'.$refundRel)),
            'ukuran_file' => 12,
            'snapshot_sekolah' => ['nama' => 'Sekolah Uji'],
            'snapshot_dokumen' => ['nomor' => 'RF/2026/11/'.$suf],
            'dibuat_oleh' => $admin->id,
        ]);
    }

    private function tulisFileUji(string $relPath, string $konten): void
    {
        $fullPath = storage_path('app/private/'.$relPath);
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, $konten);
        $this->tempFiles[] = $fullPath;
    }

    private function bearer(User $user): string
    {
        return $user->createToken('HP Uji', ['mobile-api'])->plainTextToken;
    }

    private function authSiswa(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->siswaA)];
    }

    private function authWali(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->waliA)];
    }

    private function authWaliLain(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer($this->waliLain)];
    }

    private function authAdmin(): array
    {
        return ['Authorization' => 'Bearer '.$this->bearer(User::query()->where('level', 'superadmin')->first())];
    }

    // =============================================================== Bukti

    public function test_bukti_siswa_pemilik_bisa_mengunduh(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $response = $this->withHeaders($this->authSiswa())->get('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/bukti');
        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('bukti-pembayaran.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_bukti_wali_anak_bisa_mengunduh(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authWali())->get('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/bukti')
            ->assertOk();
    }

    public function test_bukti_wali_lain_ditolak_403(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authWaliLain())->getJson('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/bukti')
            ->assertStatus(403);
    }

    public function test_bukti_superadmin_tidak_bisa_via_api_mobile(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authAdmin())->getJson('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/bukti')
            ->assertStatus(403);
    }

    // =============================================================== Kuitansi

    public function test_kuitansi_siswa_dan_wali_bisa_mengunduh(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authSiswa())->get('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/kuitansi')
            ->assertOk();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authWali())->get('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/kuitansi')
            ->assertOk();
    }

    public function test_kuitansi_wali_lain_ditolak(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authWaliLain())->getJson('/api/v1/keuangan/pembayaran/'.$this->pembayaran->idpembayaran.'/kuitansi')
            ->assertStatus(403);
    }

    // =============================================================== Dokumen refund

    public function test_dokumen_refund_wali_bisa_mengunduh(): void
    {
        $dokumen = \App\Models\RefundDokumen::query()->first();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authWali())->get('/api/v1/keuangan/refund/dokumen/'.$dokumen->id)
            ->assertOk();
    }

    public function test_dokumen_refund_siswa_bisa_mengunduh(): void
    {
        $dokumen = \App\Models\RefundDokumen::query()->first();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authSiswa())->get('/api/v1/keuangan/refund/dokumen/'.$dokumen->id)
            ->assertOk();
    }

    public function test_dokumen_refund_wali_lain_ditolak(): void
    {
        $dokumen = \App\Models\RefundDokumen::query()->first();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withHeaders($this->authWaliLain())->getJson('/api/v1/keuangan/refund/dokumen/'.$dokumen->id)
            ->assertStatus(403);
    }
}
