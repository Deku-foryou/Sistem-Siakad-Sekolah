<?php

namespace App\Services;

use App\Models\FcmDeviceToken;
use App\Models\Notifikasi;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Service pengiriman FCM HTTP v1 — OAuth2 Service Account (self-signed JWT).
 *
 * - Gunakan FCM v1 (server API), jangan legacy FCM server key (dinonaktifkan
 *   oleh Google).
 * - Access token OAuth2 dari service account dicache sekitar 50 menit.
 * - Payload dikirim ringkas (notification_id, notification_type + judul/isi
 *   umum) — jangan sampai data sensitif (nilai, pembayaran, token) keluar.
 * - Hasil pengiriman dikembalikan per token agar partial delivery terdeteksi.
 */
class FcmSenderService
{
    public const STATUS_SENT = 'sent';

    public const STATUS_INVALID_TOKEN = 'invalid_token';

    public const STATUS_FAILED = 'failed';

    public const OAUTH_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const OAUTH_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const FCM_ENDPOINT = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    private const TOKEN_CACHE_TTL_SECONDS = 3000;

    private const JWT_EXPIRY_SECONDS = 300;

    private const HTTP_TIMEOUT_SECONDS = 15;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 5;

    public function __construct(
        private ?string $accessToken = null,
        private ?string $projectId = null,
        private readonly ?Client $httpClient = null,
    ) {
        $this->accessToken ??= Cache::get(self::tokenCacheKey());
        $this->projectId ??= config('fcm.project_id', 'siakad-development');
    }

    /**
     * Ambil access token OAuth2 (self-signed JWT), dicache hingga ~50 menit.
     */
    public function accessToken(): string
    {
        if ($this->accessToken !== null && $this->isTokenValid()) {
            return $this->accessToken;
        }

        // Token eksplisit (tes) dipakai saat cache belum diisi.
        if ($this->accessToken !== null && Cache::get(self::tokenCacheKey()) === null) {
            Cache::put(self::tokenCacheKey(), $this->accessToken, self::TOKEN_CACHE_TTL_SECONDS);

            return $this->accessToken;
        }

        $credentials = $this->loadCredentials();
        $response = $this->httpClient()->post(self::OAUTH_TOKEN_ENDPOINT, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->createSignedJwt($credentials),
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];
        $token = trim((string) ($body['access_token'] ?? ''));
        if ($token === '') {
            throw new RuntimeException('OAuth2 token tidak diterima dari endpoint token.');
        }

        $this->accessToken = $token;
        Cache::put(self::tokenCacheKey(), $token, self::TOKEN_CACHE_TTL_SECONDS);

        return $this->accessToken;
    }

    /**
     * Kirim ke semua token ACTIF milik user (dipakai caller lama).
     *
     * @param  array<string, mixed>  $data
     * @return int jumlah token yang berhasil dikirim
     */
    public function sendToUser(User $user, array $data): int
    {
        $sent = 0;
        $tokens = FcmDeviceToken::query()
            ->where('user_iduser', $user->id)
            ->whereNull('revoked_at')
            ->get();

        foreach ($tokens as $token) {
            $result = $this->sendToTokenDetailed((string) $token->fcm_token, $data);
            if ($result['status'] === self::STATUS_SENT) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Kirim satu notifikasi ke satu fcm_token (wrapper kompatibilitas).
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToToken(string $fcmToken, array $data): bool
    {
        return $this->sendToTokenDetailed($fcmToken, $data)['status'] === self::STATUS_SENT;
    }

    /**
     * Data payload FCM: hanya ID dan jenis notifikasi yang diteruskan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function safeData(array $data): array
    {
        $safe = [];
        foreach (['notification_id', 'notification_type'] as $key) {
            if (array_key_exists($key, $data)) {
                $safe[$key] = (string) $data[$key];
            }
        }

        return $safe;
    }

    /**
     * Salinan notification block FCM: hanya label jenis dari allowlist.
     *
     * @param  array<string, mixed>  $data
     * @return array{title: string, body: string}
     */
    private function notificationCopy(array $data): array
    {
        $labels = [
            Notifikasi::JENIS_PENGUMUMAN => 'Pengumuman baru',
            Notifikasi::JENIS_RAPOR => 'Rapor tersedia',
            Notifikasi::JENIS_KOREKSI => 'Ada koreksi nilai',
            Notifikasi::JENIS_UMUM => 'Notifikasi baru',
            Notifikasi::JENIS_KEUANGAN => 'Info keuangan',
            Notifikasi::JENIS_PENGINGAT_TAGIHAN => 'Pengingat tagihan',
        ];

        $type = $data['notification_type'] ?? null;
        $label = is_string($type) && isset($labels[$type]) ? $labels[$type] : 'Notifikasi baru';

        return ['title' => 'Notifikasi SIAKAD', 'body' => $label.'.'];
    }

    /**
     * Kirim satu notifikasi ke satu fcm_token dengan status terperinci.
     *
     * @param  array<string, mixed>  $data
     * @return array{status: string, message_id: ?string, error: ?string}
     */
    public function sendToTokenDetailed(string $fcmToken, array $data): array
    {
        $fcmToken = trim($fcmToken);
        if ($fcmToken === '') {
            return $this->result(self::STATUS_INVALID_TOKEN, null, 'empty_token');
        }

        $endpoint = sprintf(self::FCM_ENDPOINT, $this->projectId());
        try {
            $response = $this->httpClient()->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->accessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $fcmToken,
                        'notification' => $this->notificationCopy($data),
                        'data' => $this->safeData($data),
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Respons FCM tidak sesuai (bukan 200).');
            }

            $body = json_decode((string) $response->getBody(), true) ?: [];

            return $this->result(self::STATUS_SENT, isset($body['name']) ? (string) $body['name'] : null, null);
        } catch (GuzzleException $e) {
            $error = $this->fcmErrorInfo($e);
            $status = $error['http_status'];
            $code = $error['code'];

            if ($this->isInvalidTokenError($error['reason_codes'])) {
                Log::warning('FCM token invalid', ['status' => $status, 'error' => $code]);

                return $this->result(self::STATUS_INVALID_TOKEN, null, $code);
            }

            if ($status === 400) {
                Log::error('FCM payload invalid', ['status' => $status, 'error' => $code]);

                throw new RuntimeException('FCM payload tidak valid ('.$code.').', 0, $e);
            }

            if ($status === 404) {
                Log::error('FCM resource not found', ['status' => $status, 'error' => $code]);

                throw new RuntimeException('FCM resource tidak ditemukan ('.$code.').', 0, $e);
            }

            if ($status === 401 || $status === 403) {
                Log::error('FCM OAuth/authorization failed', ['status' => $status, 'error' => $code]);

                throw new RuntimeException('FCM menolak kredensial service account ('.$code.').', 0, $e);
            }

            if ($status === 429) {
                Log::warning('FCM rate limited', ['status' => $status, 'error' => $code]);

                throw new RuntimeException('FCM terkena rate limit ('.$code.').', 0, $e);
            }

            if ($status !== null && $status >= 500) {
                Log::warning('FCM server error', ['status' => $status, 'error' => $code]);

                throw new RuntimeException('Server FCM mengalami gangguan ('.$code.').', 0, $e);
            }

            Log::warning('FCM network failed', ['status' => $status ?? 0, 'error' => $code]);

            throw new RuntimeException('Gagal mengirim FCM (jaringan: '.$code.').', 0, $e);
        }
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * @return array{status: string, message_id: ?string, error: ?string}
     */
    private function result(string $status, ?string $messageId, ?string $error): array
    {
        return [
            'status' => $status,
            'message_id' => $messageId,
            'error' => $error === null ? null : substr($error, 0, 255),
        ];
    }

    /**
     * @return array{http_status: ?int, code: string, reason_codes: array<int, string>}
     */
    private function fcmErrorInfo(GuzzleException $e): array
    {
        if (! $e instanceof RequestException || $e->getResponse() === null) {
            return [
                'http_status' => null,
                'code' => 'network_error',
                'reason_codes' => [],
            ];
        }

        $response = $e->getResponse();
        $status = (int) $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?: [];
        $error = isset($body['error']) && is_array($body['error']) ? $body['error'] : [];

        $reasons = [];
        foreach ($error['details'] ?? [] as $detail) {
            $reason = is_array($detail) ? (string) ($detail['errorCode'] ?? '') : '';
            if ($this->isSafeCode($reason)) {
                $reasons[] = $reason;
            }
        }

        $statusCode = (string) ($error['status'] ?? '');
        $code = $reasons[0] ?? ($this->isSafeCode($statusCode) ? $statusCode : 'http_'.$status);

        return [
            'http_status' => $status,
            'code' => substr($code, 0, 64),
            'reason_codes' => $reasons,
        ];
    }

    /**
     * @param  array<int, string>  $reasonCodes
     */
    private function isInvalidTokenError(array $reasonCodes): bool
    {
        foreach ($reasonCodes as $code) {
            if (in_array($code, ['UNREGISTERED', 'TOKEN_NOT_FOUND'], true)) {
                return true;
            }
        }

        return false;
    }

    private function isSafeCode(string $code): bool
    {
        return $code !== '' && preg_match('/^[A-Z][A-Z0-9_]{1,63}$/', $code) === 1;
    }

    private function httpClient(): Client
    {
        return $this->httpClient ?? new Client([
            'timeout' => self::HTTP_TIMEOUT_SECONDS,
            'connect_timeout' => self::HTTP_CONNECT_TIMEOUT_SECONDS,
        ]);
    }

    private function projectId(): string
    {
        return $this->projectId;
    }

    private function isTokenValid(): bool
    {
        // Cached token berlaku hingga sekitar 50 menit — beri jeda buffer aman.
        $cached = Cache::get(self::tokenCacheKey());

        return is_string($cached) && $cached !== '' && $cached === $this->accessToken;
    }

    private function tokenCacheKey(): string
    {
        return 'fcm_oauth_access_token:'.(config('fcm.project_id', 'siakad-development'));
    }

    /**
     * Muat kredensial service account dari file (path dari config/fcm.php).
     */
    private function loadCredentials(): array
    {
        $path = config('fcm.service_account_path');
        if ($path === null || $path === '' || ! is_file($path)) {
            throw new RuntimeException('Service account FCM tidak ditemukan (FCM belum dikonfigurasi).');
        }

        $json = file_get_contents($path);
        $credentials = json_decode((string) $json, true);
        if (! is_array($credentials)
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])) {
            throw new RuntimeException('File service account FCM tidak valid (client_email/private_key kosong).');
        }

        return $credentials;
    }

    /**
     * Buat self-signed JWT (RS256) untuk flow OAuth2 FCM v1.
     */
    private function createSignedJwt(array $credentials): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = now()->getTimestamp();
        $claims = [
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => self::OAUTH_TOKEN_ENDPOINT,
            'scope' => self::OAUTH_SCOPE,
            'iat' => $now,
            'exp' => $now + self::JWT_EXPIRY_SECONDS,
        ];

        $header64 = $this->base64UrlEncode((string) json_encode($header));
        $claims64 = $this->base64UrlEncode((string) json_encode($claims));
        $signingInput = $header64.'.'.$claims64;

        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('Kunci privat service account tidak valid.');
        }
        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Gagal menandatangani JWT service account.');
        }

        return $signingInput.'.'.$this->base64UrlEncode((string) $signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
