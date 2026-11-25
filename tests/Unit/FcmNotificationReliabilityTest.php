<?php

namespace Tests\Unit;

use App\Jobs\SendFcmNotificationJob;
use App\Models\FcmDeviceToken;
use App\Models\Notifikasi;
use App\Models\User;
use App\Services\FcmSenderService;
use App\Services\NotifikasiService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * F3.1 — Hardening idempotensi/reliability pengiriman FCM.
 *
 * SQLite in-memory, mutasi di dalam transaksi yang di-rollback di tearDown.
 */
class FcmNotificationReliabilityTest extends TestCase
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
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Mockery::close();
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

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->bigIncrements('idnotifikasi');
            $table->string('judul');
            $table->text('isi');
            $table->string('jenis');
            $table->string('prioritas');
            $table->string('sumber_tabel')->nullable();
            $table->unsignedBigInteger('sumber_id')->nullable();
            $table->string('link')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamp('waktu_dibuat')->nullable();
            $table->timestamp('fcm_dispatched_at')->nullable();
            $table->timestamp('fcm_claimed_at')->nullable();
            $table->string('fcm_claim_token', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasi_penerima', function (Blueprint $table) {
            $table->bigIncrements('idnotifikasi_penerima');
            $table->unsignedBigInteger('notifikasi_idnotifikasi');
            $table->unsignedBigInteger('user_iduser');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();
            $table->unique(['notifikasi_idnotifikasi', 'user_iduser']);
        });

        Schema::create('notifikasi_fcm_delivery', function (Blueprint $table) {
            $table->bigIncrements('idnotifikasi_fcm_delivery');
            $table->unsignedBigInteger('notifikasi_idnotifikasi');
            $table->unsignedBigInteger('fcm_token_id');
            $table->string('status', 20);
            $table->string('message_id', 255)->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamps();
            $table->unique(['notifikasi_idnotifikasi', 'fcm_token_id']);
        });
    }

    private function user(string $level): User
    {
        return User::create([
            'name' => 'Uji '.$level,
            'username' => 'uji_'.uniqid('', true),
            'email' => uniqid('', true).'@example.test',
            'password' => bcrypt('password123'),
            'level' => $level,
            'status_akun' => 'aktif',
            'wajib_ganti_password' => false,
        ]);
    }

    private function token(User $user, string $value): FcmDeviceToken
    {
        return FcmDeviceToken::query()->create([
            'user_iduser' => $user->id,
            'fcm_token' => $value,
            'platform' => 'android',
            'last_seen_at' => now(),
            'revoked_at' => null,
        ]);
    }

    private function notificationWithRecipient(User $recipient): Notifikasi
    {
        $notifikasi = Notifikasi::query()->create([
            'judul' => 'Judul umum',
            'isi' => 'Isi privat tidak boleh masuk payload',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
            'prioritas' => Notifikasi::PRIORITAS_SEDANG,
            'waktu_dibuat' => now(),
        ]);
        DB::table('notifikasi_penerima')->insert([
            'notifikasi_idnotifikasi' => $notifikasi->idnotifikasi,
            'user_iduser' => $recipient->id,
        ]);

        return $notifikasi;
    }

    private function sendResult(string $status, ?string $error = null): array
    {
        return [
            'status' => $status,
            'message_id' => $status === FcmSenderService::STATUS_SENT ? 'projects/siakad/messages/1' : null,
            'error' => $error,
        ];
    }

    public function test_job_dispatch_hanya_setelah_commit(): void
    {
        Queue::fake();
        $pembuat = $this->user('guru');
        $penerima = $this->user('siswa');
        $data = [
            'judul' => 'Pengumuman',
            'isi' => 'Isi rahasia',
            'jenis' => Notifikasi::JENIS_PENGUMUMAN,
        ];

        NotifikasiService::buat([$penerima->id], $data, $pembuat);
        Queue::assertNothingPushed();
        DB::rollBack();
        Queue::assertNothingPushed();

        // Tanpa transaksi aktif, afterCommit langsung dijalankan.
        $pembuat = $this->user('guru');
        $penerima = $this->user('siswa');
        NotifikasiService::buat([$penerima->id], $data, $pembuat);
        Queue::assertPushed(SendFcmNotificationJob::class, 1);
    }

    public function test_dua_worker_tidak_mengirim_ulang_notifikasi(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-idempoten');
        $notifikasi = $this->notificationWithRecipient($penerima);

        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->once()->andReturn($this->sendResult(FcmSenderService::STATUS_SENT));

        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);
        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $this->assertNotNull($notifikasi->fresh()->fcm_dispatched_at);
        $this->assertSame(FcmSenderService::STATUS_SENT,
            $notifikasi->fresh()->fcmDeliveries()->first()->status);
    }

    public function test_partial_delivery_tidak_menandai_selesai_dan_retry_aman(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-ok');
        $this->token($penerima, 'token-gagal');
        $notifikasi = $this->notificationWithRecipient($penerima);

        $calls = [];
        $attempts = [];
        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->andReturnUsing(
            function (string $token, array $data) use (&$calls, &$attempts) {
                $calls[] = $token;
                $attempts[$token] = ($attempts[$token] ?? 0) + 1;

                return $token === 'token-ok' || $attempts[$token] > 1
                    ? $this->sendResult(FcmSenderService::STATUS_SENT)
                    : $this->sendResult(FcmSenderService::STATUS_FAILED, 'http_503');
            }
        );

        try {
            (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);
            $this->fail('Job seharusnya melempar exception saat ada pengiriman gagal.');
        } catch (RuntimeException) {
            // Expected — retry harus terjadi.
        }

        $this->assertNull($notifikasi->fresh()->fcm_dispatched_at);
        $this->assertNull($notifikasi->fresh()->fcm_claimed_at);
        $this->assertSame(FcmSenderService::STATUS_SENT,
            $notifikasi->fresh()->fcmDeliveries()->whereHas('deviceToken', fn ($q) => $q->where('fcm_token', 'token-ok'))->first()->status);
        $this->assertSame(FcmSenderService::STATUS_FAILED,
            $notifikasi->fresh()->fcmDeliveries()->whereHas('deviceToken', fn ($q) => $q->where('fcm_token', 'token-gagal'))->first()->status);

        // Retry: token-ok tidak dikirim ulang, token-gagal berhasil.
        $calls = [];
        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $this->assertSame(['token-gagal'], $calls);
        $this->assertNotNull($notifikasi->fresh()->fcm_dispatched_at);
        $this->assertSame(2, $notifikasi->fresh()->fcmDeliveries()
            ->where('status', FcmSenderService::STATUS_SENT)->count());
    }

    public function test_invalid_token_dicabut_dan_tidak_dikirim_ulang(): void
    {
        $penerima = $this->user('siswa');
        $deviceToken = $this->token($penerima, 'token-invalid');
        $notifikasi = $this->notificationWithRecipient($penerima);

        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->once()
            ->andReturn($this->sendResult(FcmSenderService::STATUS_INVALID_TOKEN, 'UNREGISTERED'));

        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $this->assertNotNull($deviceToken->fresh()->revoked_at);
        $this->assertSame(FcmSenderService::STATUS_INVALID_TOKEN,
            $notifikasi->fresh()->fcmDeliveries()->first()->status);
        $this->assertNotNull($notifikasi->fresh()->fcm_dispatched_at);
    }

    public function test_payload_hanya_berisi_id_dan_jenis_notifikasi(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-privasi');
        $notifikasi = $this->notificationWithRecipient($penerima);

        $payload = null;
        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->once()
            ->andReturnUsing(function (string $token, array $data) use (&$payload) {
                $payload = $data;

                return $this->sendResult(FcmSenderService::STATUS_SENT);
            });

        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $this->assertSame(['notification_id', 'notification_type'], array_keys($payload));
        $this->assertSame($notifikasi->idnotifikasi, $payload['notification_id']);
        $this->assertStringNotContainsString('privat', json_encode($payload));
    }

    public function test_klaim_kedaluwarsa_dapat_diambil_ulang_setelah_retry(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-klaim-expired');
        $notifikasi = $this->notificationWithRecipient($penerima);
        $notifikasi->forceFill([
            'fcm_claimed_at' => now()->subSeconds(61),
            'fcm_claim_token' => 'claim-expired',
        ])->save();

        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->once()->andReturn($this->sendResult(FcmSenderService::STATUS_SENT));

        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $fresh = $notifikasi->fresh();
        $this->assertNotNull($fresh->fcm_dispatched_at);
        $this->assertNull($fresh->fcm_claim_token);
    }

    public function test_worker_yang_kehilangan_klaim_tidak_mengirim_token_berikutnya(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-pertama');
        $this->token($penerima, 'token-kedua');
        $notifikasi = $this->notificationWithRecipient($penerima);

        $calls = 0;
        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->andReturnUsing(
            function () use (&$calls) {
                $calls++;
                DB::table('notifikasi')->update(['fcm_claim_token' => 'worker-pengganti']);

                return $this->sendResult(FcmSenderService::STATUS_SENT);
            }
        );

        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $this->assertSame(1, $calls);
        $this->assertNull($notifikasi->fresh()->fcm_dispatched_at);
        $this->assertSame('worker-pengganti', $notifikasi->fresh()->fcm_claim_token);
    }

    public function test_klaim_aktif_milik_worker_lain_menunda_job(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-klaim-aktif');
        $notifikasi = $this->notificationWithRecipient($penerima);
        $notifikasi->forceFill([
            'fcm_claimed_at' => now(),
            'fcm_claim_token' => 'worker-lain',
        ])->save();

        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldNotReceive('sendToTokenDetailed');

        try {
            (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);
            $this->fail('Job aktif yang masih diklaim worker lain seharusnya menunda.');
        } catch (RuntimeException) {
            $this->assertNotNull($notifikasi->fresh()->fcm_claim_token);
        }
    }

    public function test_backoff_dan_tries_sesuai_reliability(): void
    {
        $job = new SendFcmNotificationJob(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame([5, 30, 120], $job->backoff());
    }

    public function test_penerima_snapshot_tetap_jadi_sumber_pengiriman(): void
    {
        $penerima = $this->user('siswa');
        $this->token($penerima, 'token-snapshot');
        $notifikasi = $this->notificationWithRecipient($penerima);

        // Role diubah setelah notifikasi dibuat — snapshot penerima tetap dipakai.
        $penerima->level = 'admin';
        $penerima->save();

        $sender = Mockery::mock(FcmSenderService::class);
        $sender->shouldReceive('sendToTokenDetailed')->once()->andReturn($this->sendResult(FcmSenderService::STATUS_SENT));

        (new SendFcmNotificationJob($notifikasi->idnotifikasi))->handle($sender);

        $this->assertSame(1, $notifikasi->fresh()->fcmDeliveries()->count());
    }
}
