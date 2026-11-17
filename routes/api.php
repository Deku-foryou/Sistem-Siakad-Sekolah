<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\NotifikasiSiswaController;
use App\Http\Controllers\Api\V1\PengumumanSiswaController;
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

                // Fase 3: pengumuman & notifikasi (read-only + tandai dibaca)
                Route::get('pengumuman', [PengumumanSiswaController::class, 'index'])->name('pengumuman');
                Route::get('pengumuman/{id}', [PengumumanSiswaController::class, 'show'])->whereNumber('id')->name('pengumuman.show');

                Route::get('notifikasi', [NotifikasiSiswaController::class, 'index'])->name('notifikasi');
                Route::get('notifikasi/unread-count', [NotifikasiSiswaController::class, 'unreadCount'])->name('notifikasi.unread');
                Route::get('notifikasi/{id}', [NotifikasiSiswaController::class, 'show'])->whereNumber('id')->name('notifikasi.show');
                Route::patch('notifikasi/{id}/read', [NotifikasiSiswaController::class, 'read'])->whereNumber('id')->name('notifikasi.read');
                Route::patch('notifikasi/read-all', [NotifikasiSiswaController::class, 'readAll'])->name('notifikasi.read-all');
            });

            // Fase 4: Keuangan (siswa & wali)
            Route::middleware('api.role:siswa,wali')->prefix('keuangan')->name('keuangan.')->group(function () {
                Route::get('ringkasan', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'ringkasan'])->name('ringkasan');
                Route::get('tagihan', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'tagihan'])->name('tagihan');
                Route::get('tagihan/{id}', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'tagihanDetail'])->whereNumber('id')->name('tagihan.detail');
                Route::get('saldo', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'saldo'])->name('saldo');
                Route::get('pembayaran', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'pembayaran'])->name('pembayaran');
                Route::get('pembayaran/{id}', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'pembayaranDetail'])->whereNumber('id')->name('pembayaran.detail');
                Route::post('pembayaran/attempts', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'buatAttempt'])->name('pembayaran.attempts.buat');
                Route::get('pembayaran/attempts/{id}', [\App\Http\Controllers\Api\V1\KeuanganController::class, 'attemptStatus'])->whereNumber('id')->name('pembayaran.attempts.status');
            });

            // Fase 5: API guru (akademik + absensi + penilaian + notifikasi) — scope guru login.
            // admin/superadmin TIDAK memperoleh akses otomatis ke endpoint guru.
            Route::middleware('api.role:guru')->prefix('guru')->name('guru.')->group(function () {
                // A. Profil & beranda
                Route::get('profil', [\App\Http\Controllers\Api\V1\GuruController::class, 'profil'])->name('profil');
                Route::get('beranda', [\App\Http\Controllers\Api\V1\GuruController::class, 'beranda'])->name('beranda');

                // B. Kelas & penugasan
                Route::get('kelas', [\App\Http\Controllers\Api\V1\GuruKelasController::class, 'index'])->name('kelas');
                Route::get('kelas/{id}', [\App\Http\Controllers\Api\V1\GuruKelasController::class, 'show'])->whereNumber('id')->name('kelas.show');
                Route::get('kelas/{id}/siswa', [\App\Http\Controllers\Api\V1\GuruKelasController::class, 'siswa'])->whereNumber('id')->name('kelas.siswa');
                Route::get('penugasan', [\App\Http\Controllers\Api\V1\GuruKelasController::class, 'penugasan'])->name('penugasan');

                // C. Jadwal mengajar
                Route::get('jadwal', [\App\Http\Controllers\Api\V1\GuruJadwalController::class, 'index'])->name('jadwal');
                Route::get('jadwal/hari-ini', [\App\Http\Controllers\Api\V1\GuruJadwalController::class, 'hariIni'])->name('jadwal.hari-ini');
                Route::get('jadwal/{id}', [\App\Http\Controllers\Api\V1\GuruJadwalController::class, 'show'])->whereNumber('id')->name('jadwal.show');

                // D. Absensi harian (grid pengisian via /kelas/{kelasId}/absensi)
                Route::get('absensi/harian', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'harianIndex'])->name('absensi.harian');
                Route::get('absensi/harian/{id}', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'harianShow'])->whereNumber('id')->name('absensi.harian.show');
                Route::post('absensi/harian', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'harianStore'])->name('absensi.harian.store');
                Route::patch('absensi/harian/{id}', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'harianUpdate'])->whereNumber('id')->name('absensi.harian.update');
                Route::get('kelas/{kelasId}/absensi', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'harianGrid'])->whereNumber('kelasId')->name('kelas.absensi');

                // E. Absensi per jam (snapshot jadwal_pertemuan)
                Route::get('absensi/perjam', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'perjamIndex'])->name('absensi.perjam');
                Route::get('absensi/perjam/pertemuan', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'perjamPertemuanIndex'])->name('absensi.perjam.pertemuan');
                Route::get('absensi/perjam/pertemuan/{id}', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'perjamPertemuanShow'])->whereNumber('id')->name('absensi.perjam.pertemuan.show');
                Route::post('absensi/perjam', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'perjamStore'])->name('absensi.perjam.store');
                Route::get('absensi/perjam/{id}', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'perjamShow'])->whereNumber('id')->name('absensi.perjam.show');
                Route::patch('absensi/perjam/{id}', [\App\Http\Controllers\Api\V1\GuruAbsensiController::class, 'perjamUpdate'])->whereNumber('id')->name('absensi.perjam.update');

                // F. Penilaian (otorisasi via penugasan_mengajar)
                Route::get('penilaian/penugasan/{id}', [\App\Http\Controllers\Api\V1\GuruPenilaianController::class, 'penugasanDetail'])->whereNumber('id')->name('penilaian.penugasan');
                Route::get('penilaian/penugasan/{id}/siswa', [\App\Http\Controllers\Api\V1\GuruPenilaianController::class, 'penugasanSiswa'])->whereNumber('id')->name('penilaian.penugasan.siswa');
                Route::put('penilaian/penugasan/{id}/nilai', [\App\Http\Controllers\Api\V1\GuruPenilaianController::class, 'simpanBanyak'])->whereNumber('id')->name('penilaian.nilai.batch');
                Route::patch('penilaian/penugasan/{id}/nilai', [\App\Http\Controllers\Api\V1\GuruPenilaianController::class, 'simpanSatu'])->whereNumber('id')->name('penilaian.nilai.satu');

                // G. Notifikasi guru (pengumuman tidak diimplementasikan: target siswa-only)
                Route::get('notifikasi', [\App\Http\Controllers\Api\V1\GuruNotifikasiController::class, 'index'])->name('notifikasi');
                Route::get('notifikasi/unread-count', [\App\Http\Controllers\Api\V1\GuruNotifikasiController::class, 'unreadCount'])->name('notifikasi.unread');
                Route::get('notifikasi/{id}', [\App\Http\Controllers\Api\V1\GuruNotifikasiController::class, 'show'])->whereNumber('id')->name('notifikasi.show');
                Route::patch('notifikasi/{id}/read', [\App\Http\Controllers\Api\V1\GuruNotifikasiController::class, 'read'])->whereNumber('id')->name('notifikasi.read');
                Route::patch('notifikasi/read-all', [\App\Http\Controllers\Api\V1\GuruNotifikasiController::class, 'readAll'])->name('notifikasi.read-all');
            });
        });
});
