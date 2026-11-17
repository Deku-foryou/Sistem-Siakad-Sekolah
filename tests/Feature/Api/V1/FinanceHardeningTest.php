<?php

namespace Tests\Feature\Api\V1;

use App\Models\Siswa;
use App\Models\User;
use App\Models\Tagihan;
use App\Models\Walimurid;
use App\Services\Payments\MidtransGateway;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class FinanceHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function getBearer(User $user): string
    {
        return $user->createToken('test', ['mobile-api'])->plainTextToken;
    }

    public function test_wali_akses_anak_verified(): void
    {
        $waliUser = User::create(['name' => 'Wali', 'username' => 'wali', 'email' => 'wali@test.com', 'password' => bcrypt('password'), 'level' => 'wali', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $userSiswa = User::create(['name' => 'Siswa', 'username' => 'siswa', 'email' => 's@test.com', 'password' => bcrypt('password'), 'level' => 'siswa', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $siswa = Siswa::create(['user_iduser' => $userSiswa->id, 'nama' => 'Anak', 'nisn' => '1', 'nis' => '1', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2010-01-01', 'status' => 'aktif']);
        
        Walimurid::create([
            'user_iduser' => $waliUser->id,
            'siswa_idsiswa' => $siswa->idsiswa,
            'status_hubungan' => 'verified',
            'tanggal_mulai' => now(),
            'pengaju_id' => $waliUser->id
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->getBearer($waliUser)])
            ->getJson('/api/v1/keuangan/ringkasan?siswa_id=' . $siswa->idsiswa)
            ->assertStatus(200);
    }

    public function test_wali_tolak_akses_anak_pending(): void
    {
        $waliUser = User::create(['name' => 'Wali', 'username' => 'wali2', 'email' => 'wali2@test.com', 'password' => bcrypt('password'), 'level' => 'wali', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $userSiswa = User::create(['name' => 'Siswa2', 'username' => 'siswa2', 'email' => 's2@test.com', 'password' => bcrypt('password'), 'level' => 'siswa', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $siswa = Siswa::create(['user_iduser' => $userSiswa->id, 'nama' => 'Anak', 'nisn' => '2', 'nis' => '2', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2010-01-01', 'status' => 'aktif']);
        
        Walimurid::create([
            'user_iduser' => $waliUser->id,
            'siswa_idsiswa' => $siswa->idsiswa,
            'status_hubungan' => 'pending',
            'tanggal_mulai' => now(),
            'pengaju_id' => $waliUser->id
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->getBearer($waliUser)])
            ->getJson('/api/v1/keuangan/ringkasan?siswa_id=' . $siswa->idsiswa)
            ->assertStatus(403);
    }

    public function test_payment_attempt_server_side_nominal(): void
    {
        $user = User::create(['name' => 'Siswa', 'username' => 'siswa3', 'email' => 's3@test.com', 'password' => bcrypt('password'), 'level' => 'siswa', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $siswa = Siswa::create(['user_iduser' => $user->id, 'nama' => 'Siswa', 'nisn' => '3', 'nis' => '3', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2010-01-01', 'status' => 'aktif']);
        
        $tagihan = Tagihan::create([
            'siswa_idsiswa' => $siswa->idsiswa,
            'nama' => 'Tagihan',
            'total_tagihan' => 100000,
            'nominal' => 100000,
            'tanggal_terbit' => now(),
            'tanggal_jatuh_tempo' => now()->addDays(7),
            'status' => 'belum_dibayar',
            'periode' => '2026/2027',
            'jenis' => 'spp'
        ]);

        $this->mock(MidtransGateway::class, function ($mock) {
            $mock->shouldReceive('createSnapTransaction')->once()->andReturn(['token' => 'token-123', 'redirect_url' => 'http://test.com']);
            $mock->shouldReceive('provider')->andReturn('midtrans');
        });

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->getBearer($user)])
            ->postJson('/api/v1/keuangan/pembayaran/attempts', [
                'tagihan_id' => $tagihan->idtagihan,
                'nominal' => 10 
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.nominal', 100000); 
    }
}
