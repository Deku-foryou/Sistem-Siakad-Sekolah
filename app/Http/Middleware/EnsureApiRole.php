<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Otorisasi API per role (Fase 1). Role mobile yang diizinkan: siswa, guru, wali.
 * admin/superadmin/walikelas dan level tak dikenal ditolak (403).
 * Usage: middleware('api.role:siswa,guru,wali')
 */
class EnsureApiRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $level = $request->user()?->level;

        if (! is_string($level) || ! in_array($level, $roles, true)) {
            return ApiResponse::fail('Akses ditolak untuk role ini.', 403, [], 'ROLE_NOT_ALLOWED');
        }

        return $next($request);
    }
}
