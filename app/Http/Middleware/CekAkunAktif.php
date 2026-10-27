<?php

namespace App\Http\Middleware;

use App\Services\LoginBlockService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! LoginBlockService::cekBolehLogin($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun tidak dapat digunakan. Hubungi admin sekolah.');
        }

        return $next($request);
    }
}
