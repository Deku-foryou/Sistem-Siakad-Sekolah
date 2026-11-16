<?php

namespace App\Http\Middleware;

use App\Services\LoginBlockService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Status akun untuk API: akun yang tidak layak (nonaktif / data induk
 * bermasalah) ditolak, dan token yang sedang dipakai dicabut.
 * Kebijakan mengikuti LoginBlockService::cekBolehLogin() — sama dengan web.
 */
class EnsureApiAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! LoginBlockService::cekBolehLogin($user)) {
            // Cabut token agar akses langsung berhenti, tidak menunggu kedaluwarsa.
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return ApiResponse::fail('Akun tidak dapat digunakan. Hubungi admin sekolah.', 403, [], 'ACCOUNT_INACTIVE');
        }

        return $next($request);
    }
}
