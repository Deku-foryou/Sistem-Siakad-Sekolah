# Laporan Implementasi — Fase 2: Student API (Read-only Akademik)

Tanggal: 16 November 2026
Lingkup: REST API `/api/v1/siswa/*` — read-only akademik untuk role `siswa`.
Basis: Fase 1 (`LAPORAN_IMPLEMENTASI_API_FOUNDATION_FASE_1.md`) + audit (`LAPORAN_AUDIT_KESIAPAN_MOBILE_SIAKAD.md`).
Tidak ada: pengumuman/notifikasi (Fase 3), keuangan (Fase 4), mutasi akademik apa pun.

## 1. Ringkasan implementasi

- 9 endpoint akademik siswa, semuanya GET, semua di bawah middleware Fase 1 (`auth:sanctum` + `api.role:siswa,guru,wali` lalu `api.role:siswa` + `api.akun` + `api.password`).
- Identitas siswa **selalu** dari akun login (`$request->user()->siswa()`); tidak ada parameter `siswa_id` yang dipakai untuk menentukan pemilik data. Parameter route (`semesterId`, `kelasId`) hanya memilih resource; kepemilikan divalidasi service.
- Konteks tahun ajaran/semester aktif dari `KonteksAkademikService::ambil()` (sumber tunggal).
- Logika absensi diekstrak ke service bersama (`RekapAbsensiService` + method baca baru `AbsensiPerjamService::historiSiswa`); komponen website tidak lagi menyimpan query duplicated — keduanya memakai service yang sama.
- Logika beranda diekstrak ke `BerandaSiswaService`; `BerandasiswaComponent` kini memanggil service yang sama (perilaku identik).
- Nilai & rapor: `RaporSiswaService` (sudah ada; `nilaiFinal()` ditambahkan sebagai method baca baru). Nilai dari `nilai_akhir` (snapshot finalisasi) — **tidak dihitung ulang**. Hanya rapor final yang tampil.
- Tidak ada migrasi baru. Tidak ada perubahan schema/enum. Tidak ada penambahan dependency.

## 2. Daftar endpoint final

| Method | Path | Middleware | Keterangan |
|---|---|---|---|
| GET | `/api/v1/siswa/beranda` | siswa | Ringkasan akademik (identitas, konteks TA/semester, jadwal hari ini, kelas aktif) |
| GET | `/api/v1/siswa/jadwal` | siswa | Jadwal semester aktif; `tanggal` opsional (`YYYY-MM-DD`) |
| GET | `/api/v1/siswa/jadwal/hari-ini` | siswa | Shortcut jadwal hari ini (same code path) |
| GET | `/api/v1/siswa/absensi/harian` | siswa | List + rekap semester aktif (tanpa filter konteks) |
| GET | `/api/v1/siswa/absensi/periode` | siswa | List + rekap rentang `dari`..`sampai` (wajib) |
| GET | `/api/v1/siswa/absensi/perjam` | siswa | Histori per jam (kanonis `jadwal_pertemuan`) + rekap |
| GET | `/api/v1/siswa/nilai` | siswa | Nilai akhir (snapshot) per rapor final; filter `semester_id`/`kelas_id` |
| GET | `/api/v1/siswa/rapor` | siswa | Daftar rapor final milik siswa |
| GET | `/api/v1/siswa/rapor/{semesterId}/{kelasId}` | siswa | Detail rapor final (angka + rekap kehadiran + catatan) |

Route model binding: `{semesterId}/{kelasId}` numeric (`->whereNumber`). Tidak ada endpoint POST/PUT/DELETE — siswa tidak dapat bermutasi data akademik.

## 3. Parameter request

| Endpoint | Parameter | Validasi |
|---|---|---|
| `jadwal` | `tanggal` (opsional) | `date_format:Y-m-d`; invalid → 422 |
| `absensi/harian` | `dari`, `sampai`, `status` (hadir\|sakit\|izin\|alpha), `per_page` (1–50, default 15), `page` (≥1) | `App\Http\Requests\Api\V1\AbsensiFilterRequest`; `dari > sampai` → 422 |
| `absensi/periode` | `dari` (wajib), `sampai` (wajib, `after_or_equal` dari), `status`, `per_page`, `page` | `AbsensiPeriodeRequest` |
| `absensi/perjam` | `dari`, `sampai`, `status`, `per_page`, `page` | `AbsensiFilterRequest` |
| `nilai` | `semester_id`, `kelas_id` (opsional) | `NilaiIndexRequest` (`integer`, `exists`); filter tanpa rapor final → 404 `NILAI_NOT_FOUND` |
| `rapor/{semesterId}/{kelasId}` | − | route numeric |

## 4. Contoh response JSON (kontrak aktual; diuji lewat assertions `assertJsonPath` di test)

**`GET /api/v1/siswa/jadwal/hari-ini`**
```json
{
  "success": true,
  "message": "Berhasil",
  "data": {
    "tanggal": "2026-11-16",
    "hari": 1,
    "nama_hari": "Senin",
    "konteks": {
      "valid": true,
      "tahun_ajaran": { "id": 12, "nama": "2026/2027" },
      "semester": { "id": 24, "nama": "Ganjil" }
    },
    "jadwal": [
      {
        "hari": 1,
        "nama_hari": "Senin",
        "jam_ke": 1,
        "mulai": "07:00",
        "selesai": "07:45",
        "mapel": { "id": 3, "kode": "MTK", "nama": "Matematika" },
        "guru": { "id": 8, "nama": "Budi Santoso" },
        "kelas_id": 21
      }
    ]
  }
}
```

**`GET /api/v1/siswa/absensi/harian`** (field utama)
```json
{
  "success": true, "message": "Berhasil",
  "data": {
    "konteks": { "valid": true, "tahun_ajaran": {"id": 12, "nama": "2026/2027"}, "semester": {"id": 24, "nama": "Ganjil"} },
    "rekap_semester": { "total": 5, "hadir": 3, "sakit": 1, "izin": 1, "alpha": 0, "persen_hadir": "60,0%" },
    "ringkasan": { "total": 5, "hadir": 3, "sakit": 1, "izin": 1, "alpha": 0, "persen_hadir": "60,0%" },
    "filter": { "dari": null, "sampai": null, "status": null },
    "items": [ { "tanggal": "2026-11-15", "status": "hadir", "catatan": null, "kelas": { "id": 21, "nama": "10A" } } ]
  },
  "meta": { "current_page": 1, "per_page": 15, "last_page": 1, "total": 5, "from": 1, "to": 5 }
}
```

**Error** (contoh wajib ganti password): `403 { "success": false, "message": "Anda wajib mengganti password terlebih dahulu.", "code": "PASSWORD_CHANGE_REQUIRED" }` · role lain: `403 ROLE_NOT_ALLOWED` · akun nonaktif: `403 ACCOUNT_INACTIVE` · raporn belum final/bukan milik: `404 RAPOR_NOT_FOUND` · validasi: `422` dengan `errors`.

## 5. Service yang digunakan

| Service | Dipakai untuk |
|---|---|
| `KonteksAkademikService::ambil()` | TA/semester aktif (sumber tunggal) |
| `BerandaSiswaService` (BARU) | Beranda (dulu inline di `BerandasiswaComponent`; kini dipakai web + API) |
| `JadwalSiswaService::kelasAktif / jadwal / urutkan` | Jadwal (penempatan aktif + semester aktif + jamhari) |
| `RekapAbsensiService` (BARU) | Query harian/per jam + rekap + persen — dipakai website (`HistoriAbsensiSiswaComponent`) & API |
| `AbsensiPerjamService::historiSiswa()` (BARU, read-only) | Kanonis histori per jam (join `jadwal_pertemuan` + kelas + mapel + guru) |
| `RaporSiswaService` (`pastikanSiswa`, `daftarRapor`, `detail`, `nilaiFinal` BARU) | Identitas siswa, daftar/detail rapor final, kelompok nilai akhir |
| `RaporWaliKelasService::keterangan()` | Keterangan KKTP (konsisten dengan rapor web) |

## 6. Service baru / ekstraksi

- **`app/Services/BerandaSiswaService.php`** (baru) — menggabungkan logika beranda yang tadinya tersebar di komponen. Komponen website (`BerandasiswaComponent`) kini memanggilnya; satu implementasi untuk web & API.
- **`app/Services/RekapAbsensiService.php`** (baru) — `queryHarian` (scope `absensi_harian` semester/TA aktif), `queryPerJam` (delegasi ke `AbsensiPerjamService::historiSiswa`), `ringkasan` (group-by counting, algoritme identik dengan portal), `persen`. `HistoriAbsensiSiswaComponent` refactored untuk memakainya — query tidak lagi diduplikasi.
- **`AbsensiPerjamService::historiSiswa()`** (ditambahkan) — histori per jam read-only; tidak menyentuh aturan input guru (`simpan`/`edit` tidak berubah).
- **`RaporSiswaService::nilaiFinal()`** (ditambahkan) — daftar nilai akhir per rapor final dengan filter semester/kelas yang hanya menerima kombinasi milik siswa.
- Tidak ada service lain yang diubah. Tidak ada file framework/middleware web yang diubah di fase ini (selain `ApiResponse::ok()` yang kini menerima parameter opsional `meta`).

## 7. Authorization & data scoping

- Seluruh endpoint berada di grup `auth:sanctum` + `api.role:siswa,guru,wali` (Fase 1) + inner `api.role:siswa` + `api.akun` + `api.password`. Guru/wali/admin/superadmin/walikelas → `403 ROLE_NOT_ALLOWED`.
- Identitas siswa = `$request->user()->siswa()` (relasi 1:1). Akun siswa tanpa relasi data siswa → `403 SISWA_NOT_LINKED` (tidak ada query bebas).
- Query keuangan/nilai/rapor selalu `where('siswa_idsiswa', $siswa->idsiswa)` (atau dipakai sebagai pembanding kepemilikan); parameter URL hanya memilih resource.
- Butir penting: detail rapor — `RaporSiswaService::detail()` menolak rapor non-final dan rapor tanpa snapshot siswa login; `nilaiFinal()` hanya memakai kombinasi dari `daftarRapor()` milik siswa sendiri.
- Tidak ada serialisasi model mentah — semua output lewat `App\Http\Resources\Api\V1\*` (bidang dipilih eksplisit).
- IDOR dites eksplisit (lihat §9 & §13).

## 8. Kebijakan visibilitas nilai & rapor

- Nilai hanya tampil untuk rapor **final** (`finalisasi_rapor.status = 'final'` + `nilai_akhir` ada). Draft (`nilai_siswa`) **tidak pernah** tampil di `/siswa/nilai` maupun detail rapor.
- Nilai filter `semester_id`/`kelas_id` yang tidak punya rapor final milik siswa → `404 NILAI_NOT_FOUND`; kombinasi kelas siswa lain → `404 NILAI_NOT_FOUND` juga.
- Detail rapor non-final → `404 RAPOR_NOT_FOUND`; kelas tanpa snapshot milik siswa → `404 RAPOR_NOT_FOUND`.
- Detail rapor memuat: nilai per mapel (nilai_akhir, KKTP, keterangan), rekap kehadiran gabungan (aturan 2B.5.7, sama dengan rapor web/PDF), catatan rapor. PDF tetap di website (tidak diekspos API).

## 9. Hasil automated tests (output aktual)

`php artisan test --filter=StudentApiTest` → **24 passed (93 assertions)**.
`php artisan test --filter=AuthApiTest` → **28 passed (63 assertions)**.
`php artisan test` penuh → **54 passed (158 assertions)**.

Skenario penting yang LULUS:
- Auth/role: guru ditolak 403, wali ditolak 403, admin & superadmin ditolak 403, token invalid → 401, token kedaluwarsa → 401, akun nonaktif → 403 (+ token dicabut), `wajib_ganti_password` memblokir endpoint akademik (403 `PASSWORD_CHANGE_REQUIRED`).
- IDOR: siswa A tidak melihat absensi/nilai/rapor siswa B; detail rapor kelas siswa lain (B-only) → 404; nilai filter kelas B-only → 404; query tidak menerima `siswa_id` dari request.
- Beranda: konteks aktif dari service resmi; jadwal hari ini ≥ 2 item; response **tidak** memuat `tagihan`/`saldo`/`pengumuman`/`notifikasi`.
- Jadwal: default = semua hari semester aktif (3 fixture items); filter tanggal valid (2 items hari ini, 1 besok); `tanggal` invalid → 422.
- Absensi harian: rekap fixture A = 5 total (3/1/1/0); filter `status=sakit` → 1; `status=hadir` → 3; `absensi/periode` tanpa/invalid tanggal → 422; rentang 10 hari → total 5; tidak ada baris siswa B yang ikut.
- Per jam: dari `jadwal_pertemuan` + `absensi_perjam`; fixture A = hadir 1 + alpha 1; α filter hadir; status B (`sakit`) tidak muncul di histori A.
- Nilai: hanya rapor final muncul; MTK 85,0 / BIN 70,0; nilai draft Genap 77,0 tidak muncul; filter semester draf → 404; filter kelas bukan milik siswa → 404.
- Rapor: daftar final milik siswa; detail 200 (`is_final`, 2 nilai, catatan "Rajin"); detail Genap (non-final) → 404; detail kelas B-only → 404; B melihat 2 rapor (7A+11B), A melihat 1.

**Bug test yang ditemukan & diperbaiki selama pengerjaan** (test-harness artifact, bukan bug aplikasi): Laravel menyimpan guard auth di proses test → antar-request dalam satu test method, pengguna B "lengket"; diselesaikan dengan `Auth::forgetGuards()` di helper `jsonA/jsonB` (request nyata per-HTTP selalu fresh). Verifikasi: `daftarRapor(A)` menghasilkan 1 item (fieldservice behavior benar) sebelum perbaikan guard di test.

**Regression website:** `php artisan test` penuh 54 passed; `view:cache` OK; `HistoriAbsensiSiswaComponent` dan `BerandasiswaComponent` memakai service yang sama (perilaku dan rendering tidak berubah secara fungsional); `AuthApiTest` Fase 1 28/28 tetap hijau. Regression perilaku website tidak dapat diotomasi penuh di sini (login web memakai captcha; halaman Livewire diproteksi `menu.access`) — snapshot visual tidak diklaim.

## 10. Commands yang dijalankan

```
php artisan route:list           → 9 route api/v1/siswa/* terdaftar
php artisan test --filter=StudentApiTest   → 24 passed (93 assertions)
php artisan test --filter=AuthApiTest      → 28 passed (63 assertions)
php artisan test                 → 54 passed (158 assertions)
php artisan view:cache           → Blade templates cached successfully
php artisan view:clear           → Compiled views cleared successfully
php vendor/bin/pint --test <file-file Fase 2>  → PASS (24 files; 2 files diperbaiki otomatis)
```

## 11. Performance & integrity

- Eager loading: `with(['kelas:idkelas,nama'])` pada list harian; `with(['semester','jamhari.kelas','mapel','guru'])` pada jadwal (service).
- Tidak ada query berulang untuk data yang sama dalam satu respons (rekap + list dari builder yang sama).
- Pagination: `per_page` default 15, cap 50; `meta` disertakan (current_page/last_page/total/from/to). Histori per jam & harian paginated, bukan di-dump seluruhnya.
- Sorting jadwal memakai `urutkan()` (hari, jam_ke, mulai); per jam `jp.tanggal desc, jp.jam_ke desc`.
- Tidak ada N+1 pada nilai/rapor: `nilaiFinal` eager-load `mapel`; `daftarRapor` tetap satu set query (pasangan kunci → finals).
- Tidak ada perubahan schema/migrasi; tidak ada index baru; tidak ada perubahan enum; tidak ada data uji tersisa (rollback transaksi; fixture dibuat deterministik di dalam rollback).

## 12. File baru / berubah

**Baru:** `app/Services/BerandaSiswaService.php`, `app/Services/RekapAbsensiService.php`, `app/Http/Controllers/Api/V1/SiswaController.php`, `app/Http/Requests/Api/V1/{JadwalIndexRequest,AbsensiFilterRequest,AbsensiPeriodeRequest,NilaiIndexRequest}.php`, `app/Http/Resources/Api/V1/{BerandaResource,JadwalResource,AbsensiHarianResource,AbsensiPerjamResource,RaporResource,RaporDetailResource,NilaiKelompokResource}.php`, `tests/Feature/Api/V1/StudentApiTest.php` (permanen; diwajibkan spesifikasi).

**Diubah:** `routes/api.php` (grup siswa), `app/Support/ApiResponse.php` (parameter opsional `meta` pada `ok()`), `app/Services/AbsensiPerjamService.php` (method baca baru `historiSiswa`), `app/Services/RaporSiswaService.php` (static `nilaiFinal`), `app/Livewire/BerandasiswaComponent.php` (delegasi ke service), `app/Livewire/HistoriAbsensiSiswaComponent.php` (hapus query duplikat; pakai `RekapAbsensiService`).

**Tidak diubah:** semua file Fase 1 (kecuali `ApiResponse::ok` kompatibel mundur — Fase 1 tetap 28/28 hijau), semua middleware web/API, semua migrasi, semua konfigurasi akademik, website routing (`routes/web.php`), query/aturan modul lain.

## 13. Acceptance criteria

| # | Kriteria | Status |
|---|---|---|
| 1 | Seluruh endpoint siswa dalam scope tersedia | **LULUS** (9 endpoint) |
| 2 | Semua endpoint hanya mengakses data siswa login | **LULUS** (scoping `siswa_idsiswa` + `pastikanSiswa`) |
| 3 | Tidak ada IDOR dalam test | **LULUS** (8 assertion IDOR eksplisit + role matrix 403) |
| 4 | Konteks akademik dari service resmi | **LULUS** (`KonteksAkademikService::ambil()`) |
| 5 | Jadwal memakai service kanonis | **LULUS** (`JadwalSiswaService`) |
| 6 | Rekap absensi tidak menduplikasi logika website | **LULUS** (`RekapAbsensiService` dipakai keduanya) |
| 7 | Histori per jam dari tabel kanonis | **LULUS** (`jadwal_pertemuan` + `absensi_perjam`) |
| 8 | Nilai & rapor mengikuti kebijakan finalisasi | **LULUS** (finalisasi gate di `daftarRapor`/`detail`/`nilaiFinal`) |
| 9 | Tidak ada endpoint mutasi akademik | **LULUS** (semua GET) |
| 10 | Response mengikuti konvensi Fase 1 | **LULUS** (`ApiResponse`, error 401/403/404/422/429/500 JSON) |
| 11 | Tidak ada perubahan schema/migrasi | **LULUS** (nol migrasi) |
| 12 | Test Fase 2 lulus | **LULUS** (24/24) |
| 13 | AuthApiTest tetap lulus | **LULUS** (28/28) |
| 14 | Tidak ada regresi website | **LULUS (terbatas)** — tidak ada perubahan perilaku kode website; snapshot visual tidak diklaim (lihat §9) |
| 15 | Laporan dengan bukti aktual | **LULUS** (dokumen ini) |

## 14. Risiko & pekerjaan tersisa

- PDF rapor tetap web-only; tidak diekspos mobile.
- **Kebijakan rapor belum final:** nilai draf guru tidak bocor — dijamin oleh query snapshot; bila kelak aturan membuka partial rapor sebelum finalisasi, endpoint ini mengikutinya secara otomatis (via `daftarRapor`/`detail`).
- **Guard cache dalam proses test**: dokumentasikan pola `Auth::forgetGuards()` untuk test multi-user dalam satu proses; bukan isu produksi.
- Belum ada UAT perangkat/Flutter; belum ada pengukuran performa pada data produksi besar (paginasi sudah dibatasi; query ter-index mengikuti index eksisting — tidak ada index baru tanpa persetujuan).
- `/siswa/absensi/periode` sengaja mencakup semester aktif (konsisten portal) — untuk histori luar semester aktif gunakan `/siswa/absensi/perjam`.
- Notifikasi/pengumuman = Fase 3; tagihan/saldo/bayar = Fase 4; endpoint guru/wali = fase berikutnya; upload foto profil mobile = belum masuk scope.
- Data uji fixture memakai prefix `UJI-*` dan rollback penuh — **tidak tersisa** di database.
