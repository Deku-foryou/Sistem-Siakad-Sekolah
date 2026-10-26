<?php

namespace App\Http\Controllers;

use App\Services\LoginBlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
            'captcha' => 'required|captcha',
        ], [
            'captcha.captcha' => 'Kode keamanan tidak valid, silakan coba lagi.',
        ]);

        $key = 'login:'.strtolower($validated['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $credentials = [
            'username' => $validated['username'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (! LoginBlockService::cekBolehLogin($user)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                RateLimiter::clear($key);

                return redirect()->back()->with('error', 'Akun tidak dapat digunakan. Hubungi admin sekolah.');
            }

            RateLimiter::clear($key);
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        RateLimiter::hit($key, 60);

        return redirect()->back()->with('error', 'Username atau password salah');

    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('login');
    }
}
