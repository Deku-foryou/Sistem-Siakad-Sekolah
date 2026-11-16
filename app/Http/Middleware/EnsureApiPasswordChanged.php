<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bila wajib_ganti_password = true, endpoint operasional diblokir.
 * Yang tetap boleh diakses: /auth/me, /auth/password, /auth/logout, /auth/logout-all.
 */
class EnsureApiPasswordChanged
{
    private const ALLOWED_ROUTES = ['api.v1.auth.me', 'api.v1.auth.password', 'api.v1.auth.logout', 'api.v1.auth.logout-all'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (bool) $user->wajib_ganti_password && ! $request->routeIs(self::ALLOWED_ROUTES)) {
            return ApiResponse::fail('Anda wajib mengganti password terlebih dahulu.', 403, [], 'PASSWORD_CHANGE_REQUIRED');
        }

        return $next($request);
    }
}
