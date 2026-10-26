<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->wajib_ganti_password) {
            $allowed = [
                'setting',
                'profil.edit',
                'logout',
            ];

            $routeName = $request->route()?->getName();

            // allow refresh-captcha by path
            if ($request->is('refresh-captcha')) {
                return $next($request);
            }

            if (! in_array($routeName, $allowed, true) && ! $request->is('setting') && ! $request->is('logout')) {
                return redirect('/setting')->with('warning', 'Anda wajib mengganti password terlebih dahulu.');
            }
        }

        return $next($request);
    }
}
