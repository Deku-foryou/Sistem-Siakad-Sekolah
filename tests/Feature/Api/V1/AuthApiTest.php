<?php

namespace Tests\Feature\Api\V1;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Fase 1 — API Foundation & Mobile Authentication.
 * Seluruh test berjalan dalam transaksi (rollback) — tidak ada data yang tersisa.
 */
class AuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function buatPengguna(string $level, string $suffix = '', array $override = []): User
    {
        static $n = 0;
        $n++;

        $user = User::create(array_merge([
            'name' => 'Uji '.$level.' '.$suffix.$n,
            'username' => 'uuji_api_'.$level.'_'.$n.'_'.now()->format('His'),
            'email' => 'uuji-api-'.$level.'-'.$n.'@example.test',
            'password' => bcrypt('password123'),
            'level' => $level,
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ], $override));

        if ($level === 'siswa') {
            Siswa::create([
                'user_iduser' => $user->id,
                'nisn' => '7'.str_pad((string) $user->id, 9, '0', STR_PAD_LEFT),
                'nis' => 'API'.$user->id,
                'nama' => $user->name,
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2010-01-01',
                'status' => 'aktif',
            ]);
        } elseif ($level === 'guru') {
            Guru::create([
                'user_iduser' => $user->id,
                'nip' => str_pad((string) $user->id, 15, '0', STR_PAD_LEFT),
                'nama' => $user->name,
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1985-01-01',
                'status' => 'aktif',
            ]);
        }

        return $user;
    }

    private function login(string $username, string $password = 'password123', string $device = 'HP Uji'): TestResponse
    {
        return $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
            'device_name' => $device,
        ]);
    }

    private function bearer(User $user, string $device, \DateTimeInterface|string|null $expiresAt = null): string
    {
        $token = $user->createToken($device, ['mobile-api'], $expiresAt);

        return $token->plainTextToken;
    }

    // ---------------------------------------------------------------- Authentication

    public function test_login_siswa_berhasil(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $response = $this->login($siswa->username);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.wajib_ganti_password', false)
            ->assertJsonPath('data.user.level', 'siswa')
            ->assertJsonPath('data.user.username', $siswa->username);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.expires_at'));
    }

    public function test_login_guru_berhasil(): void
    {
        $guru = $this->buatPengguna('guru');
        $this->login($guru->username)->assertStatus(200)->assertJsonPath('data.user.level', 'guru');
    }

    public function test_login_wali_berhasil(): void
    {
        $wali = $this->buatPengguna('wali');
        $this->login($wali->username)->assertStatus(200)->assertJsonPath('data.user.level', 'wali');
    }

    public function test_login_password_salah_ditolak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $this->login($siswa->username, 'salahpassword')->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_login_akun_nonaktif_ditolak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        Siswa::where('user_iduser', $siswa->id)->update(['status' => 'lulus']);

        $this->login($siswa->username)->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_INACTIVE');
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $siswa->id]);
    }

    public function test_login_role_admin_ditolak(): void
    {
        $admin = $this->buatPengguna('admin');
        $this->login($admin->username)->assertStatus(403)->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_login_role_superadmin_ditolak(): void
    {
        $sa = $this->buatPengguna('superadmin');
        $this->login($sa->username)->assertStatus(403)->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_login_role_walikelas_ditolak(): void
    {
        $wk = $this->buatPengguna('walikelas');
        $this->login($wk->username)->assertStatus(403)->assertJsonPath('code', 'ROLE_NOT_ALLOWED');
    }

    public function test_login_di_throttle_setelah_batas(): void
    {
        $siswa = $this->buatPengguna('siswa');

        for ($i = 0; $i < 5; $i++) {
            $this->login($siswa->username, 'salahpassword');
        }

        $this->login($siswa->username, 'salahpassword')->assertStatus(429)->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------- Token

    public function test_token_berlaku_sebagai_bearer(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.user.username', $siswa->username);
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP', now()->subMinute());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_logout_mencabut_hanya_token_perangkat_ini(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $t1 = $this->bearer($siswa, 'HP A');
        $t2 = $this->bearer($siswa, 'HP B');

        $this->withHeader('Authorization', 'Bearer '.$t1)->postJson('/api/v1/auth/logout')->assertStatus(200);
        \Illuminate\Support\Facades\Auth::forgetGuards(); // guard cache dalam proses test; request nyata selalu fresh

        // Token perangkat ini ditolak...
        $this->withHeader('Authorization', 'Bearer '.$t1)->getJson('/api/v1/auth/me')->assertStatus(401);
        // ...tetapi token perangkat lain tetap berlaku.
        $this->withHeader('Authorization', 'Bearer '.$t2)->getJson('/api/v1/auth/me')->assertStatus(200);
    }

    public function test_logout_all_mencabut_semua_token_user(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $t1 = $this->bearer($siswa, 'HP A');
        $t2 = $this->bearer($siswa, 'HP B');

        $this->withHeader('Authorization', 'Bearer '.$t1)->postJson('/api/v1/auth/logout-all')->assertStatus(200);
        \Illuminate\Support\Facades\Auth::forgetGuards(); // guard cache dalam proses test; request nyata selalu fresh

        $this->withHeader('Authorization', 'Bearer '.$t1)->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->withHeader('Authorization', 'Bearer '.$t2)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_logout_tidak_memengaruhi_token_user_lain(): void
    {
        $a = $this->buatPengguna('siswa', 'A');
        $b = $this->buatPengguna('siswa', 'B');
        $tA = $this->bearer($a, 'HP A');
        $tB = $this->bearer($b, 'HP B');

        $this->withHeader('Authorization', 'Bearer '.$tA)->postJson('/api/v1/auth/logout-all')->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$tB)->getJson('/api/v1/auth/me')->assertStatus(200);
    }

    public function test_login_multi_perangkat(): void
    {
        $siswa = $this->buatPengguna('siswa');

        $this->login($siswa->username, 'password123', 'HP Utama')->assertStatus(200);
        $this->login($siswa->username, 'password123', 'Tablet')->assertStatus(200);

        $this->assertSame(2, $siswa->tokens()->count());
    }

    public function test_token_tidak_muncul_di_me(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me');
        $response->assertStatus(200);
        $this->assertStringNotContainsString($token, $response->getContent());
    }

    // --------------------------------------------------------------- Password

    public function test_password_endpoint_validasi_saat_ini_salah_ditolak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/password', [
            'current_password' => 'salahbanget',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertStatus(422);
    }

    public function test_password_baru_terlalu_pendek_ditolak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/password', [
            'current_password' => 'password123',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertStatus(422);
    }

    public function test_password_baru_sama_dengan_lama_ditolak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/password', [
            'current_password' => 'password123',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_password_berhasil_diubah_dan_flag_direset(): void
    {
        $siswa = $this->buatPengguna('siswa', '', ['wajib_ganti_password' => true]);
        $token = $this->bearer($siswa, 'HP');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/password', [
            'current_password' => 'password123',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('passwordbaru123', $siswa->fresh()->password));
        $this->assertFalse((bool) $siswa->fresh()->wajib_ganti_password);
    }

    public function test_endpoint_operasional_diblokir_saat_wajib_ganti_password(): void
    {
        $siswa = $this->buatPengguna('siswa', '', ['wajib_ganti_password' => true]);
        $token = $this->bearer($siswa, 'HP');

        \Illuminate\Support\Facades\Route::get('/api/v1/uji-operasional', fn () => response()->json(['ok' => true]))
            ->middleware(['auth:sanctum', 'api.role:siswa,guru,wali', 'api.akun', 'api.password'])
            ->name('api.v1.uji-operasional');

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/uji-operasional')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    public function test_endpoint_wajib_password_boleh_diakses_saat_wajib_ganti_password(): void
    {
        $siswa = $this->buatPengguna('siswa', '', ['wajib_ganti_password' => true]);
        $token = $this->bearer($siswa, 'HP');

        foreach (['/api/v1/auth/me' => 'get', '/api/v1/auth/password' => 'password', '/api/v1/auth/logout' => 'logout', '/api/v1/auth/logout-all' => 'logout-all'] as $path => $kind) {
            if ($kind === 'get') {
                $r = $this->withHeader('Authorization', 'Bearer '.$token)->getJson($path);
            } elseif ($kind === 'logout') {
                $r = $this->withHeader('Authorization', 'Bearer '.$token)->postJson($path);
            } elseif ($kind === 'logout-all') {
                $r = $this->withHeader('Authorization', 'Bearer '.$token)->postJson($path);
            } else {
                $r = $this->withHeader('Authorization', 'Bearer '.$token)->postJson($path, [
                    'current_password' => 'password123',
                    'password' => 'passwordbaru123',
                    'password_confirmation' => 'passwordbaru123',
                ]);
            }

            $this->assertContains($r->getStatusCode(), [200, 201], "Endpoint {$path} tidak boleh diblokir saat wajib ganti password");
        }
    }

    public function test_token_lama_dicabut_setelah_password_diubah(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $token = $this->bearer($siswa, 'HP');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/password', [
            'current_password' => 'password123',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertStatus(200);
        \Illuminate\Support\Facades\Auth::forgetGuards(); // guard cache dalam proses test; request nyata selalu fresh

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    // ---------------------------------------------------------- Authorization

    public function test_admin_superadmin_ditolak_akses_api_mobile(): void
    {
        foreach (['admin', 'superadmin', 'walikelas'] as $level) {
            $user = $this->buatPengguna($level);
            $token = $this->bearer($user, 'HP'); // buat token manual: login ditolak di endpoint login
            $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me')->assertStatus(403);
        }
    }

    public function test_akun_dinonaktifkan_tidak_bisa_akses_dengan_token_lama(): void
    {
        $wali = $this->buatPengguna('wali');
        $token = $this->bearer($wali, 'HP');

        // Nonaktifkan akun di tengah jalan
        $wali->update(['status_akun' => 'nonaktif']);

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me')->assertStatus(403);
        \Illuminate\Support\Facades\Auth::forgetGuards(); // guard cache dalam proses test; request nyata selalu fresh

        // Token sudah dicabut — request berikutnya 401
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    // --------------------------------------------------------------- Regression

    public function test_website_login_page_tidak_rusak(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_website_logout_tidak_rusak(): void
    {
        $siswa = $this->buatPengguna('siswa');
        $this->actingAs($siswa)->post('/logout')->assertRedirect();
    }

    public function test_website_middleware_tidak_berubah(): void
    {
        // check.level tidak diizinkan untuk siswa pada route superadmin -> 404
        $siswa = $this->buatPengguna('siswa');
        $this->actingAs($siswa)->get('/appmenunew')->assertStatus(404);
    }
}
