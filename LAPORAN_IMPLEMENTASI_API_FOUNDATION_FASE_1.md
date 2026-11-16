# Laporan Implementasi — Fase 1: API Foundation & Mobile Authentication

Tanggal: 16 November 2026
Lingkup: fondasi REST API `/api/v1` + autentikasi mobile (Sanctum). **Tidak** mencakup endpoint akademik/absensi/nilai/pembayaran/notifikasi.
Basis: hasil audit `LAPORAN_AUDIT_KESIAPAN_MOBILE_SIAKAD.md`.

## 1. Perubahan yang dilakukan

**Migrasi & konfigurasi**
- `database/migrations/2026_11_16_000001_create_personal_access_tokens_table.php` — tabel Sanctum (skema identik vendor v4.1.1: `tokenable` morph, `name`, `token` unique, `abilities`, `last_used_at`, `expires_at`). **Aditif**; `--pretend` ditinjau sebelum `php artisan migrate` dijalankan (hanya migrasi ini yang pending). Tercatat `[24] Ran` di `migrate:status`.
- `config/sanctum.php` — `'expiration' => 43200` (30 hari = 43.200 menit).
- `config/cors.php` — wildcard `allowed_origins: ['*']` **dihapus**; origin eksplisit via `CORS_ALLOWED_ORIGINS` (default kosong; native Flutter tidak butuh CORS; website session tidak terpengaruh).

**API layer (baru)**
- `routes/api.php` — prefix `v1`, name `api.v1.*`. Login publik (`throttle:api-login`); sisanya `auth:sanctum` + `api.role:siswa,guru,wali` + `api.akun` + `api.password`. Rute bawaan `GET /api/user` (yang membocorkan model penuh) **dihapus**.
- `app/Http/Controllers/Api/V1/AuthController.php` — login, logout (token perangkat ini saja, idempotent), logout-all, me (tanpa token/hash), password (transaksi + lock, cabut semua token, reset `wajib_ganti_password=false`).
- `app/Http/Resources/Api/V1/UserResource.php` — payload user aman (id, name, username, email, level, status_akun, wajib_ganti_password).
- `app/Http/Requests/Api/V1/LoginRequest.php`, `UpdatePasswordRequest.php` — validasi backend (termasuk `different:current_password`, `min:8`, `confirmed`).
- `app/Support/ApiResponse.php` — konvensi `{success, message, data|errors}` + `code` mesin.
- Middleware (BARU, tidak menyentuh middleware web): `EnsureApiRole` (`api.role:siswa,guru,wali`; admin/superadmin/walikelas/tidak dikenal → 403 `ROLE_NOT_ALLOWED`), `EnsureApiAccountActive` (`api.akun` — reuse `LoginBlockService::cekBolehLogin`; bila gagal: cabut token + 403 `ACCOUNT_INACTIVE`), `EnsureApiPasswordChanged` (`api.password` — blok endpoint operasional bila `wajib_ganti_password=true`; whitelist: `auth.me`, `auth.password`, `auth.logout`, `auth.logout-all`; 403 `PASSWORD_CHANGE_REQUIRED`). Terdaftar di `app/Http/Kernel.php` (`api.role`, `api.akun`, `api.password`) — alias web tidak diubah.
- `AppServiceProvider::boot()` — `RateLimiter::for('api-login')`: 5 percobaan/menit per `IP + username ternormalisasi` (tanpa password di log/kunci). **Tidak menyimpan password; pesan login gagal generik (anti-enumerasi).**
- `app/Exceptions/Handler.php` — renderable untuk `api/*`: `ValidationException`→422, `AuthenticationException`→401, `AccessDeniedHttpException`→403, `NotFoundHttpException|ModelNotFoundException`→404, `TooManyRequestsHttpException`→429 (dengan `Retry-After` bila ada), `Throwable`→500. Web (`render()`) tidak diubah.
- `app/Http/Kernel.php` — hanya menambah alias `api.*`; grup `web`/`api` dan alias eksisting tidak diubah.

**Perubahan nyata pada kode lama (diminimalkan):** `Handler.php` (blok renderable baru, tanpa sentuh `render()`), `AppServiceProvider.php` (limiter baru), `Kernel.php` (alias baru), `routes/api.php` (tulis ulang; isinya hanya contoh bawaan), `config/sanctum.php`, `config/cors.php`. **Nol perubahan pada** modul akademik, absensi, penilaian, keuangan, notifikasi, menu/usermenu, dan role/enum `users.level`.

## 2. Daftar endpoint

| Method | Path | Auth | Throttle | Keterangan |
|---|---|---|---|---|
| POST | `/api/v1/auth/login` | publik | `api-login` 5/mnt | Login mobile; respons memuat token sekali saja, `token_type=Bearer`, `expires_at`, `wajib_ganti_password`, user aman |
| POST | `/api/v1/auth/logout` | sanctum | `api` 60/mnt | Hapus token perangkat ini; perangkat lain tetap berlaku; idempotent |
| POST | `/api/v1/auth/logout-all` | sanctum | `api` 60/mnt | Revoke semua token user |
| GET | `/api/v1/auth/me` | sanctum | `api` 60/mnt | Profil aman + info token perangkat (name, last_used_at, expires_at) |
| POST | `/api/v1/auth/password` | sanctum | `api` 60/mnt | Ganti password; transaksi + lock; cabut semua token; `wajib_ganti_password=false` |

Login API **tidak** memakai captcha web (diganti throttle ketat + rate limit per IP+username + log percobaan gagal tanpa password).

## 3. Migration & konfigurasi

- Migration: `2026_11_16_000001_create_personal_access_tokens_table` (create-only; `down()` drop tabel ini saja).
- `config/sanctum.php`: `expiration=43200` (30 hari). `SANCTUM_STATEFUL_DOMAINS`/guard tidak diubah.
- `config/cors.php`: `allowed_origins` dari env `CORS_ALLOWED_ORIGINS` (default `[]`); `supports_credentials=false` dipertahankan.

## 4. Token policy

- Laravel Sanctum Personal Access Token; **30 hari** (43.200 menit; divalidasi Sanctum pada setiap request); `createToken($deviceName, ['mobile-api'], now()->addMinutes(43200))`.
- Multi-perangkat: tidak dibatasi jumlah perangkat; satu token per perangkat (nama perangkat dicatat di `tokenable`/`name`).
- Token plaintext hanya diberikan sekali saat login; tidak pernah dikembalikan oleh `/auth/me` (test membuktikan token tidak muncul di respons).
- `logout` → `currentAccessToken()->delete()`; `logout-all` → `$user->tokens()->delete()`; logout-all tidak menyentuh token user lain.
- Ganti password sukses → **seluruh token user dicabut** (termasuk yang sedang dipakai) → pengguna login ulang.
- Akun dinonaktifkan → `api.akun` mencabut token pada pelanggaran pertama; request berikut 401.
- Token kedaluwarsa → ditolak (`401`); revoke langsung berlaku pada request berikut (request nyata selalu fresh; lihat catatan uji di §7).
- Tidak ada refresh-token OAuth; setelah 30 hari, pengguna login ulang.
- Flutter wajib menyimpan token di secure storage; transport HTTPS (deployment).

## 5. Middleware yang dibuat

| Alias | Class | Perilaku |
|---|---|---|
| `api.role:siswa,guru,wali` | `EnsureApiRole` | Level di luar daftar → 403 JSON `ROLE_NOT_ALLOWED` (tanpa `abort(404)` web) |
| `api.akun` | `EnsureApiAccountActive` | `LoginBlockService::cekBolehLogin()` false → cabut token + 403 JSON `ACCOUNT_INACTIVE` |
| `api.password` | `EnsureApiPasswordChanged` | `wajib_ganti_password=true` dan route bukan whitelist → 403 JSON `PASSWORD_CHANGE_REQUIRED` |

`menu.access` sengaja **tidak** dipakai untuk API.

## 6. Response convention & error

- Sukses: `{ success: true, message, data }` (200; 201 untuk created — belum dipakai di fase ini).
- Gagal: `{ success: false, message, errors?, code? }`; HTTP 400/401/403/404/422/429/500.
- 401 `UNAUTHENTICATED`, 403 `FORBIDDEN`/`ROLE_NOT_ALLOWED`/`ACCOUNT_INACTIVE`/`PASSWORD_CHANGE_REQUIRED`, 404 `NOT_FOUND`, 422 `errors` (validasi), 429 `TOO_MANY_REQUESTS`, 500 `SERVER_ERROR`. Semua error di `api/*` adalah JSON; tanpa stack trace/SQL/password/token.

## 7. Hasil automated tests (output aktual)

`php artisan test --filter=AuthApiTest` → **28 passed (63 assertions)**. `php artisan test` (penuh) → **30 passed (65 assertions)** (28 API + 2 ExampleTest baseline). Test permanen: `tests/Feature/Api/V1/AuthApiTest.php` (transaksi + rollback; tidak ada perubahan data tersisa; `CACHE_DRIVER=array` untuk isolasi limiter).

| Kelompok | Skenario | Hasil |
|---|---|---|
| Auth | login siswa/guru/wali OK; password salah → 401; akun nonaktif (siswa `lulus` / wali nonaktif) → 403 + tidak ada token dibuat; admin/superadmin/walikelas ditolak → 403 | LULUS |
| Throttle | 5 percobaan gagal → ke-6 → 429 JSON | LULUS |
| Token | Bearer dipakai OK; token kedaluwarsa (expires_at lampau) → 401; logout mencabut hanya perangkat ini (perangkat lain tetap OK); logout-all mencabut semua; user lain tidak terpengaruh; multi-perangkat (2 token aktif); token tidak muncul di `/auth/me` | LULUS |
| Password | current salah → 422; terlalu pendek → 422; sama dengan lama → 422; sukses → hash berubah + `wajib_ganti_password=false`; password lama gagal; token lama dicabut → 401 berikutnya | LULUS |
| Wajib ganti password | endpoint operasional → 403 `PASSWORD_CHANGE_REQUIRED` (route uji didaftarkan sementara di dalam test); `/auth/me`, `/auth/password`, `/auth/logout`, `/auth/logout-all` tetap bisa diakses | LULUS |
| Authorization | admin/superadmin/walikelas → 403 di API; akun dinonaktifkan di tengah sesi → 403 lalu token tercabut → 401 | LULUS |
| Regression | `/login` 200; POST `/logout` (web) redirect; route superadmin `/appmenunew` tetap 404 untuk siswa | LULUS |

**Catatan teknis jujur:** dalam proses PHPUnit yang panjang, guard Sanctum di-cache antar-request dalam satu test; test memanggil `Auth::forgetGuards()` setelah pencabutan token (request nyata per-HTTP selalu fresh — tidak diperlukan di produksi). Throttle diuji dengan `CACHE_DRIVER=array` (phpunit.xml); di produksi memakai cache default (file).

**Regression website:** login web tetap 200 (`GET /login` test), logout web redirect normal, middleware web (`check.level` 404) tidak berubah. Login web sukses **tidak** dapat diotomasi tanpa workaround captcha — bukan regresi, melainkan batas yang memang ada sejak awal.

`php artisan migrate` dijalankan (aditif, satu migrasi); `migrate:status` menunjukkan `[24] Ran`.
`php vendor/bin/pint` — 3 file diperbaiki gaya; `--test` akhir: **PASS (16 files)**. Blade tidak berubah; `view:cache` OK.

## 8. Acceptance criteria

| # | Kriteria | Status |
|---|---|---|
| 1 | Semua endpoint autentikasi berjalan sesuai kontrak | **LULUS** (5 endpoint, diuji) |
| 2 | Token berlaku 30 hari | **LULUS** (`expiration=43200`; expires_at dikembalikan; kedaluwarsa diuji → 401) |
| 3 | Login multi-perangkat tanpa batas | **LULUS** (2 token aktif / 1 user; tidak ada limit) |
| 4 | Logout perangkat & logout semua berfungsi | **LULUS** |
| 5 | Token kedaluwarsa/dicabut ditolak | **LULUS** |
| 6 | Role & status akun diperiksa server-side | **LULUS** (`api.role`, `api.akun`, `LoginBlockService`) |
| 7 | Wajib ganti password ditegakkan | **LULUS** (whitelist 4 endpoint; selain itu 403; diuji via route uji sementara) |
| 8 | Semua error API berupa JSON | **LULUS** (401/403/404/422/429/500 via Handler renderable; web tidak terpengaruh) |
| 9 | Rate limiting & keamanan dasar diuji | **LULUS** (login 5/mnt; limiter `api` 60/mnt bawaan) |
| 10 | Automated tests fase ini lulus | **LULUS** (28/28) |
| 11 | Regression test website lulus | **LULUS** (sebatas yang dapat diotomasi — lihat catatan) |
| 12 | Laporan dengan bukti aktual | **LULUS** (dokumen ini) |

## 9. Risiko & pekerjaan tersisa

- Flutter, secure storage, dan UAT perangkat: **belum ada** (Fase berikutnya; di luar lingkup Fase 1).
- Deployment HTTPS production, aktivasi scheduler di server, dan kebijakan rotasi/monitoring log: belum dilakukan (butuh keputusan deployment).
- Midtrans belum teruji end-to-end (Fase 4 bergantung pada ini).
- `SANCTUM_STATEFUL_DOMAINS` tidak diubah; web session tetap berjalan apa adanya. Bila kelak web memakai API lintas domain, konfigurasikan origin eksplisit di `CORS_ALLOWED_ORIGINS`.
- Tidak ada endpoint /api/v1 operasional selain auth; saat endpoint lain dibuat, wajib memakai `api.role` + `api.akun` + `api.password` dan pola response yang sama.
- Spam limiter `api-login` di shared-hosting (banyak user di belakang satu IP) bisa salah sasaran; jika terjadi di produksi, longgarkan dengan identitas ternormalisasi yang sama (sudah kombinasi IP+username) atau butuh keputusan.
- Tidak ada klaim production-ready; integrasi mobile & hardening adalah pekerjaan terpisah.

## 10. Status repository

```
 M app/Exceptions/Handler.php          (JSON renderable api/*; web tidak berubah)
 M app/Http/Kernel.php                 (alias api.role/api.akun/api.password)
 M app/Providers/AppServiceProvider.php(RateLimiter api-login)
 M config/sanctum.php                  (expiration 43200)
 M config/cors.php                     (allowed_origins dari env; wildcard dihapus)
 M routes/api.php                      (direwrite: /api/v1 auth saja)
?? app/Http/Controllers/Api/           (V1\AuthController)
?? app/Http/Middleware/EnsureApi*.php  (3 middleware baru)
?? app/Http/Requests/Api/V1/           (LoginRequest, UpdatePasswordRequest)
?? app/Http/Resources/Api/V1/          (UserResource)
?? app/Support/ApiResponse.php
?? database/migrations/2026_11_16_000001_create_personal_access_tokens_table.php
?? tests/Feature/Api/V1/AuthApiTest.php (28 tests, permanen — diwajibkan spesifikasi §9)
?? LAPORAN_AUDIT_KESIAPAN_MOBILE_SIAKAD.md  (dari Fase 0)
```

(Perubahan lain yang sudah ada sebelum Fase 1 — `AuthController`, `setting`, `navbar`, `LAPORAN_PENYUSUNAN_README.md` yang D — tidak disentuh oleh Fase 1.)

Tidak ada commit/push/reset/stash. **Tidak ada git write.** Migrasi `personal_access_tokens` sudah dijalankan di database pengembangan (`templatedb`) — satu-satunya perubahan schema; aditif; tabel baru.
