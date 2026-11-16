<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SiswaController;
use Illuminate\Support\Facades\Route;

/*
 * API SIAKAD (Fase 1) — prefix /api/v1, client Flutter.
 * Role mobile: siswa, guru, wali. admin/superadmin/walikelas ditolak.
 * Otorisasi web (menu.access, check.level) TIDAK dipakai di sini.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Login mobile — publik, throttle ketat (5/menit per IP+username ternormalisasi).
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');

    // Endpoint terautentikasi — Bearer token Sanctum + pemeriksaan role & status akun
    // + kewajiban ganti password.
    Route::middleware(['auth:sanctum', 'api.role:siswa,guru,wali', 'api.akun', 'api.password'])
        ->group(function () {
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
            Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('auth/password', [AuthController::class, 'updatePassword'])->name('auth.password');

            // Fase 2: API siswa (read-only akademik) — scope siswa login.
            Route::middleware('api.role:siswa')->prefix('siswa')->name('siswa.')->group(function () {
                Route::get('beranda', [SiswaController::class, 'beranda'])->name('beranda');
                Route::get('jadwal', [SiswaController::class, 'jadwal'])->name('jadwal');
                Route::get('jadwal/hari-ini', [SiswaController::class, 'jadwalHariIni'])->name('jadwal.hari-ini');
                Route::get('absensi/harian', [SiswaController::class, 'absensiHarian'])->name('absensi.harian');
                Route::get('absensi/periode', [SiswaController::class, 'absensiPeriode'])->name('absensi.periode');
                Route::get('absensi/perjam', [SiswaController::class, 'absensiPerjam'])->name('absensi.perjam');
                Route::get('nilai', [SiswaController::class, 'nilai'])->name('nilai');
                Route::get('rapor', [SiswaController::class, 'rapor'])->name('rapor');
                Route::get('rapor/{semesterId}/{kelasId}', [SiswaController::class, 'raporDetail'])
                    ->whereNumber(['semesterId', 'kelasId'])
                    ->name('rapor.detail');
            });
        });
});
