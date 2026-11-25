<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FcmDeviceTokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Manajemen token perangkat FCM (malas). Sanctum + otorisasi user saat ini
 * (identitas user TIDAK diterima dari client).
 */
class FcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
            'platform' => ['required', Rule::in(FcmDeviceTokenService::PLATFORMS)],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $token = FcmDeviceTokenService::register(
                $request->user(),
                $data['fcm_token'],
                $data['platform'],
                isset($data['app_version']) ? (string) $data['app_version'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::fail($e->getMessage(), 422, [], 'VALIDATION_ERROR');
        }

        Log::info('fcm token registered', [
            'user_id' => $request->user()->id,
            'platform' => $token->platform,
        ]);

        // Token di-ringkas — jangan kembalikan ke response.
        return ApiResponse::ok([
            'id' => $token->idfcmdevicetokens,
            'platform' => $token->platform,
            'app_version' => $token->app_version,
            'last_seen_at' => $token->last_seen_at?->toIso8601String(),
            'revoked_at' => $token->revoked_at?->toIso8601String(),
        ], 'Token perangkat disimpan.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $revoked = FcmDeviceTokenService::unregister($request->user(), $data['fcm_token']);

        return ApiResponse::ok([
            'revoked' => (int) $revoked,
        ], 'Token perangkat dicabut.');
    }
}
