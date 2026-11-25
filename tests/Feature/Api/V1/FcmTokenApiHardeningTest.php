<?php

namespace Tests\Feature\Api\V1;

use App\Models\FcmDeviceToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * F3.1 — Hardening endpoint FCM token.
 *
 * Database SQLite in-memory (tidak menyentuh DB development/production),
 * setiap test di dalam transaksi dan di-rollback di tearDown.
 */
class FcmTokenApiHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');

        $this->createSchema();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('level');
            $table->string('status_akun')->nullable();
            $table->boolean('wajib_ganti_password')->default(false);
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->bigIncrements('idsiswa');
            $table->unsignedBigInteger('user_iduser');
            $table->string('status');
        });

        Schema::create('guru', function (Blueprint $table) {
            $table->bigIncrements('idguru');
            $table->unsignedBigInteger('user_iduser');
            $table->string('status');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fcm_device_tokens', function (Blueprint $table) {
            $table->bigIncrements('idfcmdevicetokens');
            $table->unsignedBigInteger('user_iduser');
            $table->string('fcm_token')->unique();
            $table->string('platform', 10);
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    private function user(string $level): User
    {
        $suffix = uniqid('', true);
        $user = User::create([
            'name' => 'Uji '.$level,
            'username' => 'uji_'.$level.'_'.$suffix,
            'email' => 'uji'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'level' => $level,
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);

        if ($level === 'siswa') {
            DB::table('siswa')->insert(['user_iduser' => $user->id, 'status' => 'aktif']);
        }
        if ($level === 'guru') {
            DB::table('guru')->insert(['user_iduser' => $user->id, 'status' => 'aktif']);
        }

        return $user;
    }

    private function register(User $user, string $token, string $platform = 'android', string $appVersion = '1.0.0')
    {
        return $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices/fcm-token', [
            'fcm_token' => $token,
            'platform' => $platform,
            'app_version' => $appVersion,
        ]);
    }

    public function test_register_token_siswa_dan_guru_menurut_middleware(): void
    {
        foreach (['siswa', 'guru', 'wali'] as $level) {
            $user = $this->user($level);
            $this->register($user, 'token-'.$level)
                ->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.platform', 'android')
                ->assertDontSee('token-'.$level);

            $this->assertDatabaseHas('fcm_device_tokens', [
                'fcm_token' => 'token-'.$level,
                'user_iduser' => $user->id,
                'revoked_at' => null,
            ], 'sqlite');
        }
    }

    public function test_role_di_luar_mobile_ditolak(): void
    {
        $this->register($this->user('superadmin'), 'token-admin')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ROLE_NOT_ALLOWED');

        $this->assertDatabaseMissing('fcm_device_tokens', ['fcm_token' => 'token-admin'], 'sqlite');
    }

    public function test_update_token_menjaga_satu_baris_per_token(): void
    {
        $user = $this->user('siswa');
        $this->register($user, 'token-update', 'android', '1.0.0')->assertStatus(200);
        $this->register($user, 'token-update', 'android', '1.0.1')->assertStatus(200);

        $this->assertSame(1, FcmDeviceToken::query()->where('fcm_token', 'token-update')->count());
        $this->assertSame('1.0.1', FcmDeviceToken::query()->where('fcm_token', 'token-update')->value('app_version'));
    }

    public function test_revoke_token_hanya_milik_sendiri_idor(): void
    {
        $pemilik = $this->user('siswa');
        $penyerang = $this->user('siswa');
        $this->register($pemilik, 'token-pribadi')->assertStatus(200);

        $this->actingAs($penyerang, 'sanctum')
            ->deleteJson('/api/v1/devices/fcm-token', ['fcm_token' => 'token-pribadi'])
            ->assertStatus(200)
            ->assertJsonPath('data.revoked', 0);

        $this->assertDatabaseHas('fcm_device_tokens', [
            'fcm_token' => 'token-pribadi',
            'user_iduser' => $pemilik->id,
            'revoked_at' => null,
        ], 'sqlite');

        $this->actingAs($pemilik, 'sanctum')
            ->deleteJson('/api/v1/devices/fcm-token', ['fcm_token' => 'token-pribadi'])
            ->assertStatus(200)
            ->assertJsonPath('data.revoked', 1);

        $this->assertNotNull(FcmDeviceToken::query()->where('fcm_token', 'token-pribadi')->value('revoked_at'));
    }

    public function test_token_sama_tidak_bisa_diambil_alih_saat_masih_aktif(): void
    {
        $pertama = $this->user('siswa');
        $kedua = $this->user('guru');

        $this->register($pertama, 'token-bersama')->assertStatus(200);
        $this->register($kedua, 'token-bersama')
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertSame($pertama->id, (int) FcmDeviceToken::query()
            ->where('fcm_token', 'token-bersama')->value('user_iduser'));
    }

    public function test_perpindahan_akun_lewat_logout_login_menggunakan_token_sama(): void
    {
        $pertama = $this->user('siswa');
        $kedua = $this->user('guru');

        $this->register($pertama, 'token-pindah')->assertStatus(200);
        $this->actingAs($pertama, 'sanctum')
            ->deleteJson('/api/v1/devices/fcm-token', ['fcm_token' => 'token-pindah'])
            ->assertStatus(200)
            ->assertJsonPath('data.revoked', 1);
        $this->register($kedua, 'token-pindah')->assertStatus(200);

        $rows = FcmDeviceToken::query()->where('fcm_token', 'token-pindah')->get();
        $this->assertCount(1, $rows);
        $this->assertSame($kedua->id, (int) $rows->first()->user_iduser);
        $this->assertNull($rows->first()->revoked_at);
    }

    public function test_validasi_platform_dan_token(): void
    {
        $user = $this->user('siswa');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices/fcm-token', [
            'fcm_token' => 'token-platform',
            'platform' => 'web',
        ])->assertStatus(422);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices/fcm-token', [
            'fcm_token' => str_repeat('x', 501),
            'platform' => 'android',
        ])->assertStatus(422);

        $this->assertSame(0, FcmDeviceToken::query()->count());
    }

    public function test_logout_all_mencabut_semua_token_fcm(): void
    {
        $user = $this->user('siswa');
        $this->register($user, 'token-logout-1')->assertStatus(200);
        $this->register($user, 'token-logout-2')->assertStatus(200);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout-all')->assertStatus(200);

        $this->assertSame(0, FcmDeviceToken::query()
            ->where('user_iduser', $user->id)->whereNull('revoked_at')->count());
    }
}
