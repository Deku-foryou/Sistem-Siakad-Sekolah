# LAPORAN MASTER — COMPLETE MOBILE API (FASE 1–6 KONSOLIDASI)

**Tanggal:** 17 November 2026 · **Status:** PASS WITH LIMITATIONS
**Stack:** Laravel 12 · Livewire 3 · MySQL 8 · Sanctum · prefix `/api/v1`

---

## 1. Audit kondisi awal (sebelum pekerjaan ini)

| Fase | Status saat audit | Catatan |
|---|---|---|
| Fase 1 — API Foundation & Mobile Authentication | **Ada & berfungsi** | Sanctum, throttle `api-login`, token 30 hari, middleware role/akun/password, error JSON konsisten |
| Fase 2 — Student Academic API | **Ada & berfungsi** | beranda, jadwal, absensi harian/per jam, nilai, rapor (read-only) |
| Fase 3 — Announcement & Notification API | **Ada & berfungsi** | pengumuman & notifikasi siswa |
| Fase 4/4.1 — Finance API & Hardening | **Ada & berfungsi, terbatas** | ownership siswa/wali (`siswa_id`), payment attempt server-side; Midtrans Sandbox E2E belum pernah diuji (kredensial tidak tersedia) |
| Fase 5 — Teacher API | **Ada & berfungsi** | 29 endpoint (+ master ini menambah 1: `absensi/rekap-kelas`) |
| Fase 6 — Guardian API | **TIDAK ADA SAMA SEKALI** | belum ada route/controller/test; web punya guarded akademik (`WaliAkademikComponent`) + `WalimuridService` lengkap |

Kesenjangan yang ditemukan saat audit dan dikerjakan:
1. Guardian API (Fase 6) — implementasi penuh: profil, beranda, daftar/detail relasi + histori, beranda anak, jadwal, absensi harian/per jam/periode, nilai, rapor (+detail), pengajuan penghentian (`permintaan-putus`), notifikasi.
2. Download dokumen keuangan privat untuk API mobile — `bukti`, `kuitansi`, `dokumen refund` (sebelumnya hanya web).
3. `GET /api/v1/siswa/profil` — belum ada (Fase 2 hanya beranda).
4. `GET /api/v1/guru/absensi/rekap-kelas` — belum ada (diwajibkan Fase 6).
5. Filter `?hari=` untuk `/siswa/jadwal` (aditif, `tanggal` tetap menang).
6. Temuan sebelumnya (Fase 5): renderer `HttpExceptionInterface` 4xx untuk API — tetap berlaku, menjamin seluruh 403/404 layanan menghasilkan JSON.
7. `RaporSiswaService::rekapKehadiran` menuntut auth level `siswa` → disesuaikan menjadi `siswa`/`wali` (kebijakan Fase 2B.8 diperluas ke API; tidak ada role lain yang diizinkan).
8. Response download kuitansi: nama file disanitasi (nomor mengandung `/` → header `Content-Disposition` tidak valid; diperbaiki di jalur API; web dibiarkan).

## 2. API yang sudah tersedia sebelum pekerjaan (tetap dipakai, tidak diubah)

- Fase 1: `/auth/*` (login, logout, logout-all, me, password).
- Fase 2: seluruh endpoint siswa.
- Fase 3: `/siswa/pengumuman*`, `/siswa/notifikasi*`.
- Fase 4: `/keuangan/*` (8 endpoint; ownership siswa/wali).
- Fase 5: seluruh endpoint guru.

## 3. API yang ditambahkan

### Fase 6 — Guardian API (19 endpoint, role `wali`)
- `GET /wali/profil`, `GET /wali/beranda`
- `GET /wali/anak`, `GET /wali/anak/{siswaId}` (+`histori`), `POST /wali/anak/{siswaId}/permintaan-putus`
- `GET /wali/anak/{siswaId}/beranda`, `…/jadwal`, `…/jadwal/hari-ini`, `…/absensi/harian`, `…/absensi/periode`, `…/absensi/perjam`, `…/nilai`, `…/rapor`, `…/rapor/{semesterId}/{kelasId}`
- `GET /wali/notifikasi`, `…/unread-count`, `…/{id}`, `PATCH …/{id}/read`, `PATCH …/read-all`

### Tambahan lainnya
- `GET /siswa/profil` (S) · `GET /guru/absensi/rekap-kelas` (G)
- `GET /keuangan/pembayaran/{id}/bukti` · `GET /keuangan/pembayaran/{id}/kuitansi` · `GET /keuangan/refund/dokumen/{id}` (siswa/wali, ownership diperiksa server-side)
- `?hari=1..7` pada `GET /siswa/jadwal` (aditif)

**Total endpoint API mobile sekarang: 82** (auth 5 · siswa 17 · guru 30 · wali 19 · keuangan 11).

## 4. API yang diperbaiki

| File | Perbaikan |
|---|---|
| `app/Exceptions/Handler.php` | Renderer `HttpExceptionInterface` API-only: 4xx polos (403 dari `abort()` di service) tidak lagi menjadi 500. (Fase 5, masih berlaku; diverifikasi regression.) |
| `app/Services/RaporSiswaService.php` | `rekapKehadiran`: `abort_unless(level==='siswa')` → `in_array(level, ['siswa','wali'])`. Akses wali sesuai kebijakan Fase 2B.8; siswa tidak berubah. |
| `app/Http/Controllers/Api/V1/KeuanganFileController.php` | Nama file download disanitasi (`preg_replace [^A-Za-z0-9._-]`); sebelumnya `Kuitansi-<nomor ber-slash>` akan membuat `Content-Disposition` tidak valid. |
| `routes/api.php` | Grup `wali.*` + 3 route unduh dokumen + 2 route (`siswa/profil`, `guru/absensi/rekap-kelas`) |

## 5. API yang TETAP belum tersedia (disengaja / blocker)

1. **Pengumuman untuk guru & wali** — modul `PengumumanService` siswa-scoped (snapshot `pengumuman_baca` per siswa; penerima guru/wali tidak ada). Menambahkannya = kebijakan baru (perubahan schema/pengumuman target + approval pemilik) — **tidak dilakukan tanpa persetujuan**.
2. **Push notification FCM/APNs** — belum disepakati; tidak ditambahkan package/konfigurasi.
3. **Upload foto profil mobile** — di luar scope master (keputusan Fase 2).
4. **API administratif admin/superadmin** — eksplisit di luar scope.
5. **Endpoint penghapusan/penolakan relasi oleh wali** — keputusan relasi hanya admin/superadmin (W4); wali hanya bisa mengajukan `permintaan-putus`.

## 6. Daftar endpoint final — lihat DOKUMENTASI_REST_API_SIAKAD.md §4
(82 endpoint, seluruh method, parameter, contoh response nyata.)

## 7. Service yang digunakan ulang
`WalimuridService` (guard akademik, `relasiWaliAktifSiswa`, `buatPermintaanPutus`, `labelKelasSiswa`, `detailRekapKehadiranWali` — tidak diubah), `BerandaSiswaService`, `JadwalSiswaService`, `RekapAbsensiService`, `RaporSiswaService` (1 baris diubah), `NilaiSiswaService`, `AbsensiHarianService`, `AbsensiPerjamService`, `JadwalPertemuanService`, `NotifikasiService`, `PengumumanService`, `KonteksAkademikService`, `LoginBlockService`, `PaymentService`, `MidtransGateway` (tidak diubah), `RefundDokumenService::verifikasiIntegritas`.

Service baru: `GuruApiService`, `BerandaGuruService` (Fase 5), `BerandaWaliService` (Fase 6).

## 8. File yang dibuat/diubah (Master Phase)

**Baru:** `WaliController`, `WaliAnakController`, `WaliNotifikasiController`, `KeuanganFileController`, `PermintaanPutusRequest`, `WaliBerandaService`, `tests/Feature/Api/V1/WaliApiTest.php`, `tests/Feature/Api/V1/KeuanganFileApiTest.php`, `DOKUMENTASI_REST_API_SIAKAD.md`, `LAPORAN_MASTER_COMPLETE_MOBILE_API.md`.

**Diubah:** `routes/api.php`, `app/Http/Controllers/Api/V1/SiswaController.php` (+`profil`), `app/Http/Controllers/Api/V1/GuruAbsensiController.php` (+`rekapKelas`), `app/Http/Requests/Api/V1/JadwalIndexRequest.php` (+`hari`), `app/Services/RaporSiswaService.php`, `app/Exceptions/Handler.php` (dari Fase 5), `tests/Feature/Api/V1/StudentApiTest.php` (+2 test), `tests/Feature/Api/V1/GuruApiTest.php` (+4 test), `ROADMAP.md`.

**Fase 5 yang masih relevan:** seluruh file Teacher API (lihat `LAPORAN_IMPLEMENTASI_TEACHER_API_FASE_5.md`).

**Pra-ada (bukan pekerjaan ini, belum ter-commit):** `app/Services/NotifikasiService.php`, `app/Services/PengumumanService.php`, controller/request/resource/test Fase 3–4, laporan Fase 3–4.

## 9. Security & IDOR test (keseluruhan API mobile)

| Area | Skenario | Hasil |
|---|---|---|
| Auth | tanpa token / token invalid / role salah (siswa↔guru↔wali↔admin) | 401/403 ✓ |
| Siswa | data siswa lain (rapor/nilai/absensi) | 404 ✓ (Fase 2) |
| Guru | kelas/jadwal/nilai/absensi guru B | 404/403 (Fase 5) ✓ |
| Wali | anak wali B; relasi pending/rejected/terminated/berakhir; kehilangan akses seketika | 403 `AKSES_DITOLAK` (baru) ✓ |
| Wali | detail/notifikasi/absensi/nilai anak lain | 403/404 ✓ |
| Notifikasi | penerima lain (guru/wali/siswa) | 404 ✓ |
| Dokumen | bukti/kuitansi/refund: siswa lain, wali lain, superadmin via API mobile | 403 ✓ (baru, 9 test) |
| Keuangan | tagihan/saldo/pembayaran anak wali lain; anak pending | 403 ✓ |
| Payload | `guru_id`/`siswa_id` palsu di body; duplikat `records`; siswa luar kelas; pertemuan milik guru lain | 404/422 ✓ |
| Method | HTTP method di luar yang didaftarkan | 405 `METHOD_NOT_ALLOWED` (JSON) ✓ |
| Akun | nonaktif / wajib ganti password | 403 `ACCOUNT_INACTIVE`/`PASSWORD_CHANGE_REQUIRED` ✓ |

## 10. Regression & hasil test

Perintah: `php artisan test` (17 Nov 2026)
**Tests: 182 passed (534 assertions) — Duration: 62.82s**

Catatan kejujuran: salah satu eksekusi suite penuh (setelah Pint) menunjukkan **1 test gagal** (533 assertions). Test gagal tersebut tidak dapat diidentifikasi karena output terminal saat itu terpotong; 4 eksekusi suite penuh berikutnya seluruhnya hijau (182/534). Tidak ada perubahan kode setelah kegagalan selain penghapusan file sementara. Diduga flaky pada boundary waktu; **tidak dapat dipastikan dari bukti yang tersedia**.

| Suite | Test | Assertions |
|---|---|---|
| AuthApiTest (Fase 1) | 28 | 63 |
| StudentApiTest (Fase 2) | 26 | 111 |
| AnnouncementNotificationApiTest (Fase 3) | 22 | 74 |
| FinanceApiTest (Fase 4) | 2 | 2 |
| FinanceHardeningTest (Fase 4.1) | 3 | 5 |
| GuruApiTest (Fase 5) | 69 | 177 |
| WaliApiTest (Fase 6) | 21 | 88 |
| KeuanganFileApiTest (Fase 6/Master) | 9 | 12 |
| ExampleTest (Feature+Unit) | 2 | 2 |
| **Total** | **182** | **534** |

Seluruh suite hijau; tidak ada test lama yang diubah untuk membuat test baru lulus (StudentApiTest hanya ditambah 2 test).

## 11. Concurrency testing — TIDAK BISA DIUKUR
Tidak dijalankan (tidak ada mekanisme independent DB/process test di lingkungan ini). Lock (`lockForUpdate`) mengikuti implementasi web; **tidak diklaim aman concurrency**.

## 12. Status payment gateway
**MIDTRANS SANDBOX — BLOCKED** (tidak ada kredensial merchant; tidak pernah diuji E2E). Endpoint `attempts` ada dan memiliki test server-side (`test_payment_attempt_server_side_nominal`). Klaim "pembayaran online berhasil" **tidak dapat diberikan**. Ledger/refund/koreksi tidak diubah.

## 13. Private file access
**PASS** dengan test khusus: ownership (siswa/wali verified), role lain (termasuk superadmin) 403, path traversal ditolak (`realpath` compare), checksum refund diverifikasi, nama file download disanitasi, absolute path tidak pernah dikirim.

## 14. Risiko tersisa
1. Midtrans Sandbox belum E2E; risiko gagal transaksi nyata sampai kredensial & rehearsal produksi.
2. Tidak ada concurrency test.
3. Web `kuitansi` download memiliki cacat nama file (nomor ber-`/`) — API sudah diperbaiki; **web belum** (perlu persetujuan terpisah).
4. Pengumuman guru/wali belum tersedia (butuh keputusan kebijakan).
5. Lapisan UI Flutter belum diuji (formatting tanggal/mata uang, error mapping, deep-link Snap).
6. Roadmap API Fase 3–5/6 belum ter-commit (di luar wewenang — tidak ada Git write command yang dijalankan).

## 15. Kesiapan integrasi Flutter
**SIAP UNTUK DEVELOPMENT/DEMO** — seluruh endpoint lengkap, otorisasi backend, envelope konsisten, `DOKUMENTASI_REST_API_SIAKAD.md` tersedia dengan contoh nyata. **Belum** untuk UAT perangkat/produksi: perlu build Flutter nyata, pengujian Midtrans Sandbox, deployment rehearsal, HTTPS, backup/drill, monitoring, dan review keamanan terpisah.

## 16. Kesiapan produksi
**NOT READY** — lihat §12 & §14. Automated test hijau ≠ production-ready.
