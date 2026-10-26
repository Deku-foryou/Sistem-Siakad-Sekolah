<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class MenuAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil nama route yang sedang diakses
        $routeName = $request->route()->getName();

        // Periksa apakah pengguna memiliki izin akses ke route ini
        if (!$this->userHasMenuAccess($routeName)) {
            // Jika tidak memiliki akses, kembalikan response error
            return abort(404);
        }

        return $next($request);
    }

    private function userHasMenuAccess($routeName)
    {
        // Ambil user saat ini
        $user = Auth::user();

        // Periksa apakah user memiliki akses ke menu dengan nama route tertentu
        if ($user) {
            // Query ke database untuk memeriksa apakah ada entri usermenu yang sesuai
            return $user->usermenu()->whereHas('appmenunew', function ($query) use ($routeName) {
                $query->where('url', $routeName);
            })->exists();
        }

        return false;
    }
}