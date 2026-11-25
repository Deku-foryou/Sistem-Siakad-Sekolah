<?php

namespace App\Services;

use App\Models\FcmDeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service manajemen token perangkat FCM.
 *
 * - Register idempoten (upsert per fcm_token; satu token unik terkait satu
 *   akun; satu akun bisa punya beberapa perangkat).
 * - Revoke token spesifik / semua (logout, ganti password, security).
 * - Tidak menyimpan token di log, tidak mengekspos token ke API response.
 */
class FcmDeviceTokenService
{
    public const PLATFORM_ANDROID = 'android';

    public const PLATFORM_IOS = 'ios';

    public const PLATFORMS = [self::PLATFORM_ANDROID, self::PLATFORM_IOS];

    private const TOKEN_MAX_LENGTH = 500;

    private const APP_VERSION_MAX_LENGTH = 50;

    /**
     * Daftarkan token perangkat milik user (upsert, idempoten per fcm_token).
     */
    public static function register(User $user, string $fcmToken, string $platform, ?string $appVersion = null): FcmDeviceToken
    {
        $platform = strtolower(trim($platform));
        if (! in_array($platform, self::PLATFORMS, true)) {
            throw new InvalidArgumentException('Platform tidak didukung.');
        }
        $fcmToken = trim($fcmToken);
        if ($fcmToken === '') {
            throw new InvalidArgumentException('FCM token wajib diisi.');
        }
        if (strlen($fcmToken) > self::TOKEN_MAX_LENGTH) {
            throw new InvalidArgumentException('FCM token terlalu panjang.');
        }
        $appVersion = trim((string) $appVersion);
        if ($appVersion === '') {
            $appVersion = null;
        }
        if ($appVersion !== null && strlen($appVersion) > self::APP_VERSION_MAX_LENGTH) {
            throw new InvalidArgumentException('app_version terlalu panjang.');
        }

        return DB::transaction(function () use ($user, $fcmToken, $platform, $appVersion) {
            $token = FcmDeviceToken::query()
                ->where('fcm_token', $fcmToken)
                ->lockForUpdate()
                ->first();

            if ($token === null) {
                return FcmDeviceToken::query()->create([
                    'user_iduser' => $user->id,
                    'fcm_token' => $fcmToken,
                    'platform' => $platform,
                    'app_version' => $appVersion,
                    'last_seen_at' => now(),
                    'revoked_at' => null,
                ]);
            }

            // Token aktif tidak boleh diambil alih akun lain. Perpindahan akun
            // hanya terjadi setelah lifecycle logout/unregister mencabut token.
            if ((int) $token->user_iduser !== (int) $user->id && $token->revoked_at === null) {
                throw new InvalidArgumentException('Token FCM masih terdaftar pada akun lain.');
            }
            if ((int) $token->user_iduser !== (int) $user->id) {
                Log::info('fcm token reassigned after revoke', [
                    'from_user' => (int) $token->user_iduser,
                    'to_user' => (int) $user->id,
                ]);
            }
            $token->user_iduser = $user->id;
            $token->platform = $platform;
            if ($appVersion !== null) {
                $token->app_version = $appVersion;
            }
            $token->last_seen_at = now();
            $token->revoked_at = null;
            $token->save();

            return $token;
        });
    }

    /**
     * Revoke token spesifik (token invalid, logout perangkat).
     */
    public static function unregister(User $user, string $fcmToken): int
    {
        $fcmToken = trim($fcmToken);
        if ($fcmToken === '') {
            return 0;
        }

        return DB::transaction(function () use ($user, $fcmToken) {
            $token = FcmDeviceToken::query()
                ->where('user_iduser', $user->id)
                ->where('fcm_token', $fcmToken)
                ->lockForUpdate()
                ->first();

            if ($token === null || $token->revoked_at !== null) {
                return 0;
            }

            $token->revoked_at = now();
            $token->save();

            return 1;
        });
    }

    /**
     * Revoke SEMUA token user (logout-all, ganti password, keamanan).
     */
    public static function unregisterAll(User $user): int
    {
        return DB::transaction(function () use ($user) {
            $tokens = FcmDeviceToken::query()
                ->where('user_iduser', $user->id)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get();

            foreach ($tokens as $token) {
                $token->revoked_at = now();
                $token->save();
            }

            return $tokens->count();
        });
    }

    /**
     * Cabut token yang ditolak FCM (invalid/unregistered). Dipanggil server,
     * bukan dari request client.
     */
    public static function revokeInvalidTokenById(int $tokenId): int
    {
        return DB::transaction(function () use ($tokenId) {
            $token = FcmDeviceToken::query()
                ->where('idfcmdevicetokens', $tokenId)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($token === null) {
                return 0;
            }

            $token->revoked_at = now();
            $token->save();

            return 1;
        });
    }

    /**
     * Ambil semua token ACTIF milik user (berdasarkan last_seen terbaru).
     *
     * @return array<int, string>
     */
    public static function activeTokensForUser(User $user): array
    {
        return FcmDeviceToken::query()
            ->where('user_iduser', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('last_seen_at')
            ->pluck('fcm_token')
            ->all();
    }

    /**
     * Ringkas token untuk logging/respon — jangan kembalikan ke API response.
     */
    public static function maskToken(string $fcmToken): string
    {
        return strlen($fcmToken) <= 12
            ? '***'
            : substr($fcmToken, 0, 8).'…'.substr($fcmToken, -4);
    }
}
