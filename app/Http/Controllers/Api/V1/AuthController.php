<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\LoginBlockService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends \App\Http\Controllers\Controller
{
    /** Role yang boleh masuk API mobile (selain ini -> 403; tidak membuat token). */
    private const ROLES_MOBILE = ['siswa', 'guru', 'wali'];

    /** Umur token: 30 hari (43.200 menit; selaras dengan config/sanctum.php). */
    private const TOKEN_LIFETIME_MINUTES = 43200;

    public function login(LoginRequest $request): JsonResponse
    {
        $username = $request->usernameNormalized();
        $user = User::where('username', $username)->first();

        // Password tidak pernah dicatat; pesan generik untuk anti-enumerasi.
        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            Log::warning('api login gagal', ['username' => $username, 'ip' => $request->ip()]);

            return ApiResponse::fail('Username atau password salah.', 401, [], 'INVALID_CREDENTIALS');
        }

        if (! in_array($user->level, self::ROLES_MOBILE, true)) {
            return ApiResponse::fail('Akses ditolak untuk role ini.', 403, [], 'ROLE_NOT_ALLOWED');
        }

        if (! LoginBlockService::cekBolehLogin($user)) {
            return ApiResponse::fail('Akun tidak dapat digunakan. Hubungi admin sekolah.', 403, [], 'ACCOUNT_INACTIVE');
        }

        $deviceName = trim((string) $request->input('device_name'));
        if ($deviceName === '') {
            $deviceName = 'Perangkat';
        }

        $expiresAt = now()->addMinutes(self::TOKEN_LIFETIME_MINUTES);
        $token = $user->createToken($deviceName, ['mobile-api'], $expiresAt);

        Log::info('api login', ['user_id' => $user->id, 'level' => $user->level, 'ip' => $request->ip(), 'device' => $deviceName]);

        return ApiResponse::ok([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'wajib_ganti_password' => (bool) $user->wajib_ganti_password,
            'user' => new UserResource($user),
        ], 'Login berhasil');
    }

    public function logout(Request $request): JsonResponse
    {
        // Hapus hanya token yang sedang dipakai; idempotent; token perangkat lain tetap berlaku.
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::ok(null, 'Logout berhasil');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return ApiResponse::ok(null, 'Seluruh perangkat telah logout. Silakan login kembali.');
    }

    public function me(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        return ApiResponse::ok([
            'user' => new UserResource($request->user()),
            'token' => $token ? [
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
            ] : null,
        ], 'Berhasil');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return ApiResponse::fail('Validasi gagal.', 422, ['current_password' => ['Password saat ini salah.']]);
        }

        if (Hash::check((string) $request->input('password'), $user->password)) {
            return ApiResponse::fail('Validasi gagal.', 422, ['password' => ['Password baru tidak boleh sama dengan password lama.']]);
        }

        DB::transaction(function () use ($request, $user) {
            // Re-read dengan lock agar perubahan password konsisten bila ada request paralel.
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $locked->password = Hash::make((string) $request->input('password'));
            $locked->wajib_ganti_password = false;
            $locked->save();

            // Cabut SELURUH token mobile; pengguna wajib login ulang.
            $locked->tokens()->delete();
        });

        Log::info('api ganti password', ['user_id' => $user->id]);

        // Semua token dicabut di atas, termasuk yang sedang dipakai.
        return ApiResponse::ok(null, 'Password berhasil diubah. Silakan login kembali.');
    }
}
