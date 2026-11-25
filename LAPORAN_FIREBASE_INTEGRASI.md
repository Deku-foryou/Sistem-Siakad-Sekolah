# LAPORAN Integrasi FCM Laravel — SIAKAD

**Versi:** 1.0 — **Tanggal:** 24 November 2026
**Stack:** Laravel 12 + MySQL 8 — Prefix API `/api/v1`
**Scope:** FCM Backend Integration (Device Token + Notification Sender) — lihat juga
LAPORAN Integrasi SIAKAD Mobile (siakad-mobile).

---

## 1. Status Layanan (Firebase Console — Proyek `siakad-development`)

Periksa di firebase.google.com → Project → **siakad-development**:

| Layanan | Status saat ini | Catatan |
|---|---|---|
| Cloud Messaging | ✅ Dapat diaktifkan (Spark) | FCM HTTP v1 digunakan — **legacy server key dilarang/dinonaktifkan** |
| Crashlytics | ✅ Dapat diaktifkan | (Native, SDK sudah terpasang di Flutter) |
| Analytics | ✅ Dapat diaktifkan | (Native, SDK sudah terpasang di Flutter) |
| Performance Monitoring | ✅ Dapat diaktifkan | (Native, SDK sudah terpasang di Flutter) |
| Database/Realtime/Storage/Functions | ❌ Tidak dipakai | Laravel tetap sumber kebenaran |

**Billing:** Plan tetap **Spark (Free)**. Harus dikonfirmasi **TIDAK BOLEH ke Blaze** — konfigurasi default tetap Spark.

---

## 2. Arsitektur Backend FCM (before/after)

```
Sebelum — Notifikasi (canal database):
  Notifikasi::buat → NotifikasiPenerima::firstOrCreate (snapshot)
  Read via NotifikasiPenerima.

Sesudah — + FCM (baru):
  NotifikasiService::buat
    ├─ DB::transaction: Notifikasi::create + NotifikasiPenerima::firstOrCreate
    └─ SendFcmNotificationJob::dispatch($id) [queue fcm_push]   ← setelah commit
  SendFcmNotificationJob (tries=3, backoff 5s):
    ├─ Guard idempoten: Notifikasi.fcm_dispatched_at != null → skip
    ├─ Kontak = snapshot NotifikasiPenerima (selalu)
    └─ FcmSenderService::sendToUser(user, data minimal)
          └─ POST fcm.googleapis.com/v1/projects/{projectId}/messages
               Authorization: Bearer <OAuth2 access token (self-signed JWT service account)>
```

**Kebijakan pemeranan push (dependen):**
- Payload ringan: `{notification_id, notification_type}` (+ judul/isi umum) — **tidak ada data sensitif** (nilai, pembayaran, token).
- Snapshot penerima dari `NotifikasiPenerima` tetap preserved (history lengkap).
- FCM **bukan sumber kebenaran penerima** — penerima tetap dari DB + ownership diperiksa server-side.

---

## 3. Device Token Management

### 3.1 Tabel `fcm_device_tokens` (migration 2026_11_24_000001)

```sql
fcm_device_tokens (
  idfcmdevicetokens BIGINT UNSIGNED PK AUTO_INCREMENT,
  user_iduser BIGINT UNSIGNED NOT NULL,         -- FK → users.id, RESTRICT
  fcm_token VARCHAR(500) UNIQUE NOT NULL,       -- token tercatat, unik
  platform ENUM/VARCHAR(10) NOT NULL,           -- 'android' | 'ios'
  app_version VARCHAR(50) NULL,
  last_seen_at TIMESTAMP NULL,
  revoked_at TIMESTAMP NULL,                    -- token dicabut (logout/tidak valid)
  timestamps
)
```

### 3.2 Service `FcmDeviceTokenService`

- `register($user, $token, $platform, $appVersion)` — upsert idempoten per fcm_token; ownership di-set dari `$user`; `last_seen_at` diperbarui.
- `unregister($user, $token)` — revoke token spesifik (scoped ke user).
- `unregisterAll($user)` — revoke semua token user.
- `activeTokensForUser($user)` — token aktif (non-revoked).
- Validasi: platform wajib + di whitelist, token wajib + maks 500 char, app_version maks 50 char.
- Token **tidak pernah ditampilkan** ke API response.

---

## 4. API — `POST/DELETE /api/v1/devices/fcm-token`

Lihat DOKUMENTASI_REST_API_SIAKAD.md §devices/fcm-token (diperbarui menyusul).

Ringkasan kontrak (ApiResponse standar):
- POST: `{success, message: "Token perangkat disimpan.", data: {id, platform, app_version, last_seen_at, revoked_at}}`.
- DELETE: `{success, message: "Token perangkat dicabut.", data: {revoked: 0|1}}`.

Auth: Sanctum + role siswa/guru/wali + account active + wajib ganti password (default group).

Ownership: identitas user selalu dari Sanctum token login.

---

## 5. Notification Sender — `FcmSenderService`

- **Teknik autentikasi:** FCM HTTP v1 via OAuth2 Service Account (self-signed JWT — tanpa legacy server key).
- **Kredensial:** `config/fcm.php` → `service_account_path` (default `storage/app/fcm_service_account.json`). File ini dipasang **MANUAL** oleh tim operasional — **tidak terisi otomatis, tidak diekspor**.
- **Project ID:** default `siakad-development` (dari task Firebase Project).
- **Cache token OAuth2:** ~50 menit.
- **Retry:** job 3x dengan backoff jala (5s default).
- **Payload:** ringan — `{notification_id, notification_type}` saja.
- **Jangan masuk token ke log** (log hanya id + tipe, kalau ada).

## 6. Queue & Scheduler (operasional)

- **Queue:** `fcm_push` (buat di config/queue.php connections bila diperlukan; default tetap `sync` saat ini).
- **Retry/idempotency:** job mengecek `fcm_dispatched_at` pada Notifikasi — aman di-dispatch berulang.
- **Cron:** pasang `php artisan schedule:run` di crontab (produk); lihat DOKUMENTASI_REST_API_SIAKAD.md §Queue & Scheduler.

## 7. Testing & Verifikasi

### Laravel (PHPUnit)
```
php artisan test                 # 201 passed (567 assertions) — tanpa regresi
php artisan test --filter Fcm    # 19 passed — FCM feature tests
```
- FCM: register idempoten, IDOR, validasi platform, getInitialMessage fallback, job idempotensi, FcmSenderService (mock Guzzle), logout/logout-all revoke.
- Tests di-transaksi rollback (tidak ada data tersisa).
- **Jangan buat akun uji permanen di tempat ini** (sambil CI: gunakan factory dalam transaksi).

### Konfigurasi eksternal (dipisahkan)
- [ ] Pasang `storage/app/fcm_service_account.json` (firebase console → service account).
- [ ] Verifikasi `config('fcm.project_id')` = `siakad-development`.
- [ ] Aktifkan notifikasi di console (Cloud Messaging).
- [ ] Build & install ke device, e2e: kirim notif dari Laravel, cek FCM di device.

## 8. Keterbatasan & Known Issue

1. Kredensial service account **diluar repo** — sedang kosong. Harus dipasang manual: `storage/app/fcm_service_account.json`.
2. Legacy server key tidak dipakai (tidak aktif di Google).
3. E2E push device blok (credential) — dokumentasikan; lihat bagian 9.

## 9. Timeline & Next Steps

1. Pasang service account + konfigurasi console (Blaze: **TIDAK**).
2. Onboard queue worker (di tempat yang sudah ada).
3. Deploy + e2e: kirim notif → verifikasi di device.
4. Apabila ada issue: dokumentasikan.

---
*Dokumen ini menggunakan bahasa Indonesia sesuai konvensi proyek.*
