<?php

namespace Tests\Feature\Api\V1;

use App\Models\Siswa;
use App\Models\User;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class FinanceApiTest extends TestCase
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

    public function test_siswa_bisa_akses_keuangan(): void
    {
        $user = User::create([
            'name' => 'Uji Siswa',
            'username' => 'uji_siswa',
            'email' => 'uji@siswa.test',
            'password' => bcrypt('password'),
            'level' => 'siswa',
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
        $siswa = Siswa::create([
            'user_iduser' => $user->id,
            'nama' => 'Uji Siswa',
            'nisn' => '1234567890',
            'nis' => '123',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'status' => 'aktif',
        ]);
        
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->getBearer($user)])
            ->getJson('/api/v1/keuangan/ringkasan')
            ->assertStatus(200);
    }

    public function test_siswa_tidak_bisa_akses_tagihan_orang_lain(): void
    {
        $userA = User::create(['name' => 'Siswa A', 'username' => 'siswa_a', 'email' => 'a@test.com', 'password' => bcrypt('password'), 'level' => 'siswa', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $siswaA = Siswa::create(['user_iduser' => $userA->id, 'nama' => 'Siswa A', 'nisn' => '1', 'nis' => '1', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2010-01-01', 'status' => 'aktif']);
        
        $userB = User::create(['name' => 'Siswa B', 'username' => 'siswa_b', 'email' => 'b@test.com', 'password' => bcrypt('password'), 'level' => 'siswa', 'status_akun' => 'aktif', 'wajib_ganti_password' => false]);
        $siswaB = Siswa::create(['user_iduser' => $userB->id, 'nama' => 'Siswa B', 'nisn' => '2', 'nis' => '2', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2010-01-01', 'status' => 'aktif']);
        
        $tagihanB = Tagihan::create([
            'siswa_idsiswa' => $siswaB->idsiswa,
            'nama' => 'Tagihan B',
            'total_tagihan' => 100000,
            'nominal' => 100000,
            'tanggal_terbit' => now(),
            'tanggal_jatuh_tempo' => now()->addDays(7),
            'status' => 'belum_dibayar',
            'periode' => '2026/2027',
            'jenis' => 'spp'
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->getBearer($userA)])
            ->getJson('/api/v1/keuangan/tagihan/' . $tagihanB->idtagihan)
            ->assertStatus(404);
    }
}
