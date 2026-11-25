# DOKUMENTASI REST API SIAKAD (Mobile)

**Versi:** 1.0 · **Tanggal:** 17 November 2026 · **Status:** Siap UAT Flutter (lihat §15)
**Stack:** Laravel 12 + Livewire 3 · MySQL 8 · Sanctum · Prefix `/api/v1`

Dokumen ini adalah acuan tunggal integrasi Flutter (Android/iOS) terhadap backend SIAKAD.
Sumber kebenaran bisnis tetap berada di backend; Flutter hanya menampilkan data.

---

## 1. Arsitektur API

```
Flutter (Dio) ── Bearer token ──► /api/v1/*  ──► Middleware:
                                    auth:sanctum  (token Sanctum, abilities ['mobile-api'])
                                    api.role:<roles>  (EnsureApiRole)
                                    api.akun          (EnsureApiAccountActive + LoginBlockService)
                                    api.password      (EnsureApiPasswordChanged)
                                              │
                                              ▼
                                    Controller (tipis) ──► Service (business logic)
                                              │
                                              ▼
                                    Form Request (validasi) · ApiResponse (envelope)
                                    API Resources (transform) · KonteksAkademikService
```

Aturan arsitektur:
- Identitas user **selalu** dari token (`auth()->user()`), tidak pernah dari payload.
- Otorisasi di backend penuh (ownership dicek per request); Flutter tidak menjadi dasar trust.
- Business logic di Service layer; controller tidak mengandung query bisnis.
- Prefix konsisten `/api/v1`; response envelope konsisten `{success, message, data?, errors?, meta?}`.

## 2. Authentication & token

### 2.1 Login
`POST /api/v1/auth/login` — publik, throttle `api-login` (5/menit per IP+username ternormalisasi).

Request:
```json
{ "username": "siswa01", "password": "rahasia", "device_name": "Pixel 8" }
```
Response `200`:
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|r4nd0mPlainTextToken",
    "token_type": "Bearer",
    "expires_at": "2026-12-17T08:00:00+07:00",
    "wajib_ganti_password": false,
    "user": { "id": 1280, "name": "...", "username": "siswa01", "level": "siswa", "...": "..." }
  }
}
```
- **Token plaintext hanya dikembalikan saat login.** `GET /auth/me` hanya mengembalikan metadata (`name`, `last_used_at`, `expires_at`), tidak pernah token mentah.
- Token berlaku **30 hari** (43.200 menit; `config/sanctum.php` + `expires_at` per-token).
- Multi-device tanpa batas: setiap login membuat token baru; perangkat lain tidak tercabut.
- Role di luar siswa/guru/wali → `403 ROLE_NOT_ALLOWED` (tidak membuat token).

### 2.2 Endpoint auth lain (Bearer)
| Method | Path | Fungsi |
|---|---|---|
| POST | `/auth/logout` | Hapus token yang sedang dipakai (idempoten) |
| POST | `/auth/logout-all` | Hapus SEMUA token user (logout semua perangkat) |
| GET | `/auth/me` | Profil akun + metadata token |
| POST | `/auth/password` | Ganti password → **semua token dicabut**, wajib login ulang |

### 2.3 Status akun & wajib ganti password
- Akun tidak layak (nonaktif / data induk bermasalah) → `403 ACCOUNT_INACTIVE` + token yang sedang dipakai dicabut.
- `wajib_ganti_password = true` → endpoint operasional `403 PASSWORD_CHANGE_REQUIRED`; yang tetap boleh: `/auth/me`, `/auth/password`, `/auth/logout`, `/auth/logout-all`.

## 3. Role & authorization matrix

| Grup | Role | Middleware | Keterangan |
|---|---|---|---|
| `/auth/*` | publik (login) + semua role mobile | token / throttle | — |
| `/siswa/*` | siswa | `api.role:siswa` | data milik sendiri |
| `/guru/*` | guru | `api.role:guru` | kelas/penugasan/pertemuan miliknya |
| `/wali/*` | wali | `api.role:wali` | relasi anak miliknya |
| `/keuangan/*` | siswa & wali | `api.role:siswa,wali` | siswa: data sendiri; wali: `?siswa_id=` wajib + relasi verified |

Aturan umum:
- admin/superadmin/walikelas/role lain → `403 ROLE_NOT_ALLOWED` di semua endpoint mobile.
- Akun nonaktif → `403 ACCOUNT_INACTIVE`. Wajib ganti password → `403 PASSWORD_CHANGE_REQUIRED`.
- Wali tanpa relasi `verified` (atau `tanggal_berhenti` sudah lewat / siswa nonaktif) → **tidak** bisa akses akademik maupun keuangan anak; tetap bisa login dan melihat status relasi.
- Pengumuman: penerima hanya siswa (modul tidak mendukung guru/wali sebagai penerima). Notifikasi: guru, siswa, wali.

## 4. Daftar seluruh endpoint (82)

### 4.1 Auth (5)
| # | Method | Path | Deskripsi |
|---|---|---|---|
| A1 | POST | `/auth/login` | Login (throttle 5/mnt) |
| A2 | POST | `/auth/logout` | Logout perangkat ini |
| A3 | POST | `/auth/logout-all` | Logout semua perangkat |
| A4 | GET | `/auth/me` | Profil akun + metadata token |
| A5 | POST | `/auth/password` | Ganti password (mencabut semua token) |

### 4.2 Siswa (17) — role `siswa`
| # | Method | Path | Deskripsi |
|---|---|---|---|
| S1 | GET | `/siswa/profil` | Identitas siswa + kelas aktif + akun |
| S2 | GET | `/siswa/beranda` | Beranda (identitas, konteks, kelas, jadwal hari ini) |
| S3 | GET | `/siswa/jadwal` | Jadwal (`?tanggal=YYYY-MM-DD` menang; `?hari=1..7`) |
| S4 | GET | `/siswa/jadwal/hari-ini` | Jadwal hari ini |
| S5 | GET | `/siswa/absensi/harian` | Riwayat absensi harian (`?dari,sampai,status,per_page,page`) |
| S6 | GET | `/siswa/absensi/periode` | Rekap/list per periode (`dari`,`sampai` wajib) |
| S7 | GET | `/siswa/absensi/perjam` | Histori absensi per jam |
| S8 | GET | `/siswa/nilai` | Nilai akhir (`?semester_id,kelas_id`) |
| S9 | GET | `/siswa/rapor` | Daftar rapor final |
| S10 | GET | `/siswa/rapor/{semesterId}/{kelasId}` | Detail rapor |
| S11 | GET | `/siswa/pengumuman` | Daftar pengumuman (`?tab=aktif|riwayat`) |
| S12 | GET | `/siswa/pengumuman/{id}` | Detail (auto-tandai dibaca) |
| S13 | GET | `/siswa/notifikasi` | Daftar notifikasi (`?status,jenis,per_page,page`) |
| S14 | GET | `/siswa/notifikasi/unread-count` | Jumlah belum dibaca |
| S15 | GET | `/siswa/notifikasi/{id}` | Detail notifikasi |
| S16 | PATCH | `/siswa/notifikasi/{id}/read` | Tandai dibaca |
| S17 | PATCH | `/siswa/notifikasi/read-all` | Tandai semua dibaca |

### 4.3 Keuangan (11) — role `siswa`, `wali`
| # | Method | Path | Deskripsi |
|---|---|---|---|
| K1 | GET | `/keuangan/ringkasan` | Total kewajiban, terbayar, sisa, saldo |
| K2 | GET | `/keuangan/tagihan` | Daftar tagihan (`?status`,`per_page`) |
| K3 | GET | `/keuangan/tagihan/{id}` | Detail tagihan |
| K4 | GET | `/keuangan/saldo` | Saldo tersedia |
| K5 | GET | `/keuangan/pembayaran` | Riwayat pembayaran (paginated) |
| K6 | GET | `/keuangan/pembayaran/{id}` | Detail pembayaran |
| K7 | POST | `/keuangan/pembayaran/attempts` | Buat payment attempt (Midtrans Snap) |
| K8 | GET | `/keuangan/pembayaran/attempts/{id}` | Status payment attempt |
| K9 | GET | `/keuangan/pembayaran/{id}/bukti` | Unduh bukti bayar (privat) |
| K10 | GET | `/keuangan/pembayaran/{id}/kuitansi` | Unduh kuitansi (privat) |
| K11 | GET | `/keuangan/refund/dokumen/{id}` | Unduh dokumen refund (privat, checksum diverifikasi) |

Wali: tambahkan `?siswa_id=<idAnak>` pada K1–K6 (contoh `/keuangan/ringkasan?siswa_id=5698`); K7–K11 memilih anak yang sama dari `tagihan_id`/`id` — otorisasi selalu dari relasi verified. Siswa: parameter diabaikan (identitas dari token).

### 4.4 Guru (30) — role `guru`
| # | Method | Path | Deskripsi |
|---|---|---|---|
| G1 | GET | `/guru/profil` | Identitas + mapel + akun |
| G2 | GET | `/guru/beranda` | Identitas, konteks, kelas (mengajar+wali), ringkasan, jadwal hari ini |
| G3 | GET | `/guru/kelas` | Kelas diajar ∪ diwali + sumber + jumlah siswa + mapel |
| G4 | GET | `/guru/kelas/{id}` | Detail kelas |
| G5 | GET | `/guru/kelas/{id}/siswa` | Siswa aktif kelas |
| G6 | GET | `/guru/penugasan` | Penugasan mengajar (semester aktif) |
| G7 | GET | `/guru/jadwal` | Jadwal (`?tanggal` menang; `?hari=1..7`) |
| G8 | GET | `/guru/jadwal/hari-ini` | Jadwal hari ini |
| G9 | GET | `/guru/jadwal/{id}` | Detail jadwal (`idjadwalkelas`) |
| G10 | GET | `/guru/absensi/rekap-kelas` | Rekap kehadiran per siswa (`kelas_id` wajib; `dari`,`sampai`) |
| G11 | GET | `/guru/absensi/harian` | Riwayat absensi harian (`dari,sampai,status,kelas_id,siswa_id,per_page,page`) |
| G12 | GET | `/guru/absensi/harian/{id}` | Detail record |
| G13 | POST | `/guru/absensi/harian` | Simpan massal (upsert per siswa) |
| G14 | PATCH | `/guru/absensi/harian/{id}` | Ubah status/catatan (≤ H+1) |
| G15 | GET | `/guru/kelas/{kelasId}/absensi` | Grid pengisian (siswa + absensi) `?tanggal` |
| G16 | GET | `/guru/absensi/perjam` | Riwayat per jam (paginated) |
| G17 | GET | `/guru/absensi/perjam/pertemuan` | Daftar pertemuan tanggal (default hari ini; generate snapshot idempoten) |
| G18 | GET | `/guru/absensi/perjam/pertemuan/{id}` | Grid pertemuan (siswa + absensi) |
| G19 | POST | `/guru/absensi/perjam` | Catat massal per pertemuan (upsert) |
| G20 | GET | `/guru/absensi/perjam/{id}` | Detail record |
| G21 | PATCH | `/guru/absensi/perjam/{id}` | Ubah status/keterangan (≤ H+1) |
| G22 | GET | `/guru/penilaian/penugasan/{id}` | Penugasan + komponen/bobot + `konfigurasi_valid` + `terkunci` |
| G23 | GET | `/guru/penilaian/penugasan/{id}/siswa` | Rekap siswa (nilai per komponen, nilai draft) |
| G24 | PUT | `/guru/penilaian/penugasan/{id}/nilai` | Batch upsert nilai |
| G25 | PATCH | `/guru/penilaian/penugasan/{id}/nilai` | Satu nilai |
| G26 | GET | `/guru/notifikasi` | Daftar notifikasi |
| G27 | GET | `/guru/notifikasi/unread-count` | Jumlah belum dibaca |
| G28 | GET | `/guru/notifikasi/{id}` | Detail |
| G29 | PATCH | `/guru/notifikasi/{id}/read` | Tandai dibaca |
| G30 | PATCH | `/guru/notifikasi/read-all` | Tandai semua dibaca |

### 4.5 Wali murid (19) — role `wali`
| # | Method | Path | Deskripsi |
|---|---|---|---|
| W1 | GET | `/wali/profil` | Identitas + ringkasan relasi (verified/pending/rejected/terminated) |
| W2 | GET | `/wali/beranda` | Identitas, konteks, daftar anak verified, ringkasan |
| W3 | GET | `/wali/anak` | Semua relasi milik wali (semua status) |
| W4 | GET | `/wali/anak/{siswaId}` | Detail relasi + histori (append-only) |
| W5 | POST | `/wali/anak/{siswaId}/permintaan-putus` | Pengajuan penghentian hubungan (`alasan` wajib 5–500) |
| W6 | GET | `/wali/anak/{siswaId}/beranda` | Beranda anak (identitas, konteks, kelas, jadwal) |
| W7 | GET | `/wali/anak/{siswaId}/jadwal` | Jadwal anak (`?hari`/`?tanggal`) |
| W8 | GET | `/wali/anak/{siswaId}/jadwal/hari-ini` | Jadwal hari ini |
| W9 | GET | `/wali/anak/{siswaId}/absensi/harian` | Histori absensi harian |
| W10 | GET | `/wali/anak/{siswaId}/absensi/periode` | Absensi periode (`dari`,`sampai` wajib) |
| W11 | GET | `/wali/anak/{siswaId}/absensi/perjam` | Histori per jam |
| W12 | GET | `/wali/anak/{siswaId}/nilai` | Nilai akhir (rapor final saja) |
| W13 | GET | `/wali/anak/{siswaId}/rapor` | Daftar rapor final anak |
| W14 | GET | `/wali/anak/{siswaId}/rapor/{semesterId}/{kelasId}` | Detail rapor final |
| W15 | GET | `/wali/notifikasi` | Daftar notifikasi |
| W16 | GET | `/wali/notifikasi/unread-count` | Jumlah belum dibaca |
| W17 | GET | `/wali/notifikasi/{id}` | Detail |
| W18 | PATCH | `/wali/notifikasi/{id}/read` | Tandai dibaca |
| W19 | PATCH | `/wali/notifikasi/read-all` | Tandai semua dibaca |

W6–W14 hanya untuk relasi `verified` tanpa `tanggal_berhenti` → selain itu `403 AKSES_DITOLAK`.
Pengumuman wali: **tidak tersedia** (modul pengumuman siswa-scoped; bukan keputusan API).

### 4.6 Devices & FCM Token (2 endpoints)

Lihat LAPORAN_FIREBASE_INTEGRASI.md untuk arsitektur keseluruhan. Ringkasan endpoint FCM v1 (Autentikasi Sanctum + role siswa/guru/wali):

| Method | Path | Fungsi | Response |
|---|---|---|---|
| POST | `/api/v1/devices/fcm-token` | Daftarkan/perbarui token FCM perangkat (payload: `fcm_token` (wajib, maks 500), `platform` (android/ios, wajib), `app_version` (opsional)) | `{success, message, data: {id, platform, app_version, last_seen_at, revoked_at}}` |
| DELETE | `/api/v1/devices/fcm-token` | Cabut token FCM (payload: `fcm_token` wajib) — hanya token user saat ini yang tercantum (IDOR melindungi token user lain) | `{success, message, data: {revoked: 0|1}}` |

Aturan:
- Identitas user dari Sanctum token login — **tidak menerima user_iduser dari client**.
- Token hanya terkait dengan satu akun pada satu waktu (unik). Satu akun bisa punya beberapa perangkat.
- Validasi 422 (platform invalid / token kosong / app_version terlalu panjang). Token tidak pernah ditampilkan di response (di-ringkas).
- Rate limiting default bisa diterapkan oleh Laravel 12 + Sanctum default group.
- Dokumen di `LAPORAN_FIREBASE_INTEGRASI.md` & `storage/app/fcm_service_account.json` (manual) — jangan masuk credential ke repo.

---

## 5. Konvensi request & response

### 5.1 Envelope sukses
```json
{ "success": true, "message": "Berhasil", "data": { }, "meta": { "current_page": 1, "...": "..." } }
```
`meta` hanya pada endpoint paginated: `current_page, per_page, last_page, total, from, to`.

### 5.2 Envelope error
```json
{ "success": false, "message": "...", "code": "KELAS_NOT_FOUND", "errors": { "field": ["..."] } }
```
`errors` hanya untuk 422 validasi; `code` selalu ada pada error terstruktur.

### 5.3 HTTP status
`200` ok · `201` created · `401` UNAUTHENTICATED · `403` ROLE_NOT_ALLOWED / ACCOUNT_INACTIVE / PASSWORD_CHANGE_REQUIRED / FORBIDDEN / AKSES_DITOLAK / WALI_NOT_ACTIVE · `404` NOT_FOUND / *_NOT_FOUND · `405` METHOD_NOT_ALLOWED · `422` VALIDATION_ERROR · `429` TOO_MANY_REQUESTS · `500` SERVER_ERROR · `503` gateway tidak tersedia.

### 5.4 Format data
- Tanggal: `YYYY-MM-DD` · Datetime: ISO-8601 (`2026-11-17T09:20:20+07:00`) · Hari: 1=Senin..7=Minggu (+`nama_hari`).
- Status absensi: `hadir|sakit|izin|alpha`. Status relasi wali: `pending|verified|rejected|terminated`.
- Uang: integer rupiah (mis. `100000`), tanpa desimal. Nilai akhir: desimal 2 digit.
- `persen_hadir`: string format lokal (`"87,5%"`).

### 5.5 Pagination & filter
Semua endpoint list: `?page=1&per_page=15` (maks 50). Filter umum absensi: `dari, sampai, status` (Y-m-d).

## 6. Contoh response nyata (Fase 6 wali)

### 6.1 `GET /api/v1/wali/beranda` (contoh aktual 17-11-2026)
```json
{
  "success": true, "message": "Berhasil",
  "data": {
    "user": { "id": 10633, "name": "Wali Uji A 7913092020", "username": "wali_uji_a_7913092020" },
    "akun": { "username": "wali_uji_a_7913092020", "wajib_ganti_password": false },
    "konteks": {
      "valid": true, "peringatan": [],
      "tahun_ajaran": { "id": 1779, "nama": "UJI-TA-MASTER" },
      "semester": { "id": 3815, "nama": "Ganjil" }
    },
    "anak": [
      { "id": 5698, "nisn": "0000074445", "nis": "NIS10635", "nama": "Anak Uji A1", "kelas": "UJI-M1A" },
      { "id": 5701, "nisn": "0000074466", "nis": "NIS10638", "nama": "Anak Uji A4", "kelas": "UJI-M1A" }
    ],
    "ringkasan": { "jumlah_anak": 2, "jumlah_menunggu": 1, "jumlah_ditolak": 0, "jumlah_berakhir": 1, "notifikasi_belum_dibaca": 1 },
    "nama_hari": "Selasa"
  }
}
```

### 6.2 `GET /api/v1/wali/anak` (contoh aktual)
```json
{
  "success": true, "message": "Berhasil",
  "data": {
    "konteks": { "...": "..." },
    "anak": [
      { "relasi_id": 1401, "hubungan": "ayah", "status_hubungan": "verified",
        "tanggal_mulai": "2026-11-16", "tanggal_berhenti": null,
        "tanggal_verifikasi": "2026-11-17T09:20:20+07:00",
        "bisa_akses_akademik": true, "kelas": "UJI-M1A",
        "siswa": { "id": 5698, "nisn": "0000074445", "nis": "NIS10635", "nama": "Anak Uji A1", "jenis_kelamin": "L", "tahun_masuk": null, "status": "aktif" } },
      { "relasi_id": 1402, "hubungan": "ayah", "status_hubungan": "pending",
        "tanggal_mulai": "2026-11-16", "tanggal_berhenti": null,
        "tanggal_verifikasi": null,
        "bisa_akses_akademik": false, "kelas": null,
        "siswa": { "id": 5699, "nisn": "0000074452", "nis": "NIS10636", "nama": "Anak Uji A2", "jenis_kelamin": "L", "tahun_masuk": null, "status": "aktif" } }
    ]
  }
}
```

### 6.3 `GET /api/v1/wali/anak/{siswaId}/nilai` (contoh aktual)
```json
{
  "success": true, "message": "Berhasil",
  "data": {
    "rapor": [
      {
        "semester": { "id": 3815, "nama": "Ganjil", "tahunajaran": { "id": 1779, "nama": "UJI-TA-MASTER" } },
        "kelas": { "id": 4062, "nama": "UJI-M1A", "tingkat": "10" },
        "nilai": [
          { "mapel_id": 3357, "mapel_kode": "UJI-MAS", "mapel_nama": "Mapel Uji Master",
            "nilai_akhir": 85, "nilai_kktp": 75, "keterangan": "Tuntas" }
        ]
      }
    ]
  }
}
```

### 6.4 `POST /api/v1/wali/anak/{siswaId}/permintaan-putus` (contoh aktual)
Request `{ "alasan": "Contoh pengajuan penghentian" }` → `201`:
```json
{ "success": true, "message": "Permintaan penghentian hubungan berhasil diajukan dan menunggu keputusan admin.",
  "data": { "id": 47, "status_permintaan": "menunggu", "alasan": "Contoh pengajuan penghentian" } }
```

### 6.5 Error contoh
```json
{ "success": false, "message": "Akses ditolak untuk role ini.", "code": "ROLE_NOT_ALLOWED" }
{ "success": false, "message": "Akses akademik tidak tersedia untuk siswa ini.", "code": "AKSES_DITOLAK" }
{ "success": false, "message": "Kelas tidak ditemukan.", "code": "KELAS_NOT_FOUND" }
{ "success": false, "message": "Validasi gagal.", "code": "VALIDATION_ERROR", "errors": { "alasan": ["..."] } }
```

## 7. Alur pembayaran (ringkas)

1. `GET /keuangan/tagihan` → pilih `idtagihan`.
2. `POST /keuangan/pembayaran/attempts` `{ "tagihan_id": N }` → `PaymentAttempt` (Midtrans Snap) — **nominal selalu dari server** (`tagihan.total_tagihan`), bukan dari payload.
3. Aplikasi menampilkan Snap (token/redirect URL dari attempt).
4. Server menerima webhook (`POST /webhook/midtrans`, signature SHA512, idempotent) — hanya settlement terkonfirmasi yang masuk ledger.
5. Polling `GET /keuangan/pembayaran/attempts/{id}` sampai `success`/`expired`/`failed`.
6. `GET /keuangan/pembayaran/{id}` → bukti/receipt; unduh `/bukti` / `/kuitansi` bila ada.

Aturan: attempt **bukan** bukti bayar; wali membayar untuk anaknya (relasi verified); siswa tidak boleh submit nominal.

## 8. Alur akses wali ↔ anak

1. Admin membuat akun wali → wali login → melihat `/wali/anak` (semua status; `pending`/`rejected`/`terminated` tidak memberi akses data).
2. Setelah relasi **verified** & `tanggal_berhenti` null → akademik & keuangan terbuka (otomatis; token wali tidak perlu diganti).
3. Wali melihat anak via `/wali/anak/{id}/*`; backend memverifikasi relasi pada **setiap** request.
4. Penghentian: wali mengajukan `permintaan-putus` → admin/superadmin memutuskan (di luar API mobile). Wali tidak bisa menyetujui sendiri (W4).
5. Setelah `terminated`/`tanggal_berhenti` → akses langsung hilang pada request berikutnya.

## 9. Upload/download dokumen

Tidak ada endpoint upload untuk mobile. File privat (bukti bayar, kuitansi, dokumen refund) hanya dapat diunduh oleh:
- siswa pemilik transaksi → `/keuangan/pembayaran/{id}/bukti`, `/kuitansi`, `/keuangan/refund/dokumen/{id}`
- wali dengan relasi verified untuk anak pemilik transaksi

Response sukses: file binary (`Content-Disposition: attachment`). Error selalu JSON (`403`/`404`).
Path traversal ditolak (realpath dibandingkan dengan root `storage/app/private`). Dokumen refund diverifikasi checksum SHA-256 sebelum dikirim.

## 10. Contoh integrasi Flutter (Dio)

```dart
final dio = Dio(BaseOptions(baseUrl: 'https://siakad.example.id/api/v1'));

// Login
final res = await dio.post('/auth/login', data: {'username': u, 'password': p, 'device_name': 'Flutter Android'});
final token = res.data['data']['token'] as String;

// Header untuk request selanjutnya
dio.options.headers['Authorization'] = 'Bearer $token';

// Jadwal anak wali
final jadwal = await dio.get('/wali/anak/5698/jadwal/hari-ini');
final items  = (jadwal.data['data']['jadwal'] as List);

// Penanganan error standar
try {
  await dio.get('/guru/kelas');
} on DioException catch (e) {
  final body = e.response?.data;
  if (body is Map && body['code'] != null) {
    switch (body['code']) {
      case 'ACCOUNT_INACTIVE': goToLogin(); break;
      case 'PASSWORD_CHANGE_REQUIRED': goToGantiPassword(); break;
      case 'ROLE_NOT_ALLOWED': showDialog('Role tidak diizinkan'); break;
      default: showDialog(body['message'] ?? 'Terjadi kesalahan');
    }
  }
}
```
Simpan token di `flutter_secure_storage`; hapus saat `401`. Jangan cache data akademik melebihi kebutuhan tampilan; backend adalah satu-satunya sumber kebenaran.

## 11. Konvensi tanggal & middleware per role

Semua endpoint operasional memerlukan `Bearer <token>`. Urutan middleware: `auth:sanctum` → `api.role:<group>` → `api.akun` → `api.password`.

## 12. Keterbatasan & yang belum tersedia
1. **Pengumuman untuk guru/wali tidak tersedia** (modul pengumuman penerima-siswa).
2. **Push notification FCM/APNs** belum disepakati — notifikasi hanya kanal database (polling).
3. **Payment gateway Sandbox E2E belum diuji** (kredensial Midtrans belum tersedia); endpoint attempt ada, pembayaran nyata belum diverifikasi.
4. **Upload foto profil mobile** belum masuk scope.
5. **Concurrency test** (multi-klien) belum dijalankan — lock transaksi mengikuti web (`lockForUpdate`).
6. **Export CSV/PDF** rekap/rapor via API mobile belum tersedia (web punya; ekspor wali CSV tetap di web).
7. **Webhook Midtrans** adalah jalur server-to-server (bukan untuk Flutter).
8. Tidak ada API administratif untuk admin/superadmin (by design).
9. Riwayat absensi harian (guru/siswa) dibatasi konteks TA/semester aktif; riwayat lintas semester hanya lewat endpoint rapor/histori per jam yang memang bebas filter semester.
10. `GET /guru/absensi/perjam/pertemuan` memicu `generateUntukTanggal()` (idempoten; menulis snapshot pertemuan yang belum ada).

## 13. Kesiapan

| Aspek | Status |
|---|---|
| Autentikasi & otorisasi backend | **PASS** (test 182/534 hijau) |
| API siswa (Fase 2) | **PASS** (teruji; dokumen ini) |
| API guru (Fase 5) | **PASS** |
| API wali (Fase 6/Master) | **PASS** |
| API keuangan siswa/wali (Fase 4) | **PASS WITH LIMITATIONS** (Midtrans E2E ditunda pemilik) |
| Pengumuman & notifikasi | Siswa **PASS** · Guru/Wali (notifikasi) **PASS** · Pengumuman guru/wali **TIDAK TERSEDIA** |
| Dokumen privat (bukti/kuitansi/refund) | **PASS** (ownership + checksum; test khusus) |
| UAT Flutter / perangkat | **Belum diuji** |
| Production readiness | **NOT READY** — perlu deployment rehearsal, HTTPS, konfigurasi gateway, backup/drill, monitoring |
