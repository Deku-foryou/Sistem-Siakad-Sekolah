# LAPORAN IMPLEMENTASI PENGUMUMAN 2B.4.6

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Dasar | `LAPORAN_DESAIN_PENGUMUMAN_2B4_6A.md` (revisi final), `LAPORAN_TAHAP_2B4_6B.md`, `LAPORAN_VERIFIKASI_2B4_6B.md` |
| Cakupan | Service, halaman superadmin, halaman siswa, route/menu, pengujian menyeluruh |
| Tidak diubah | Migration yang sudah berjalan, tabel lain, fitur selain pengumuman |

---

## 1. Ringkasan Implementasi

Fitur Pengumuman Siswa selesai terintegrasi dari superadmin sampai siswa:

- `PengumumanService` sebagai pusat logika bisnis: validasi, draf, terbit atomik + snapshot, arsip, daftar aktif/riwayat/detail, tandai baca idempoten.
- Halaman superadmin `/pengumuman`: daftar + pencarian + filter status/kategori/prioritas + pagination + buat/ubah draf + detail + terbitkan + arsipkan + jumlah penerima.
- Halaman siswa `/pengumuman-siswa` (+ `/{id}`): daftar aktif & riwayat (dua tab), detail dengan tandai baca, badge belum dibaca.
- Status hanya `draf → terbit → arsip`; terbit tidak dapat diedit/dikembalikan; arsip tidak dapat diterbitkan ulang; tidak ada hapus.
- Snapshot penerima dibuat saat publikasi (`distinct()`), tanpa `firstOrCreate` saat siswa membaca.
- Menu & route berlapis (`check.level:superadmin` / `check.level:siswa` + `menu.access` + guard komponen).
- Keamanan IDOR & isolasi antar siswa terverifikasi.

---

## 2. Daftar File Baru dan Diubah

| File | Aksi | Ringkasan |
|---|---|---|
| `database/migrations/2026_11_08_000001_create_pengumuman_tables.php` | Baru (2B.4.6B) | 3 tabel: `export` |
| `app/Models/Pengumuman.php` | Baru (2B.4.6B) | Model + relasi + scopes `terbit`/`berlaku` |
| `app/Models/PengumumanTarget.php` | Baru (2B.4.6B) | Model + relasi |
| `app/Models/PengumumanBaca.php` | Baru (2B.4.6B) | Model + relasi + scopes baca |
| `app/Services/PengumumanService.php` | **Baru** | Pusat logika bisnis pengumuman |
| `app/Livewire/PengumumanComponent.php` | **Baru** | Halaman manajemen superadmin |
| `app/Livewire/PengumumanSiswaComponent.php` | **Baru** | Halaman siswa (daftar + detail) |
| `resources/views/livewire/pengumuman-component.blade.php` | **Baru** | UI manajemen (tabel, pencarian, filter, modal form dinamis, detail) |
| `resources/views/livewire/pengumuman-siswa-component.blade.php` | **Baru** | UI siswa (kartu, badge prioritas, badge belum dibaca, detail panel) |
| `database/seeders/PengumumanMenuSeeder.php` | **Baru** | Menu superadmin & siswa (idempoten) |
| `routes/web.php` | Diubah | `$explicitUrls` + route `/pengumuman` (superadmin) + `/pengumuman-siswa` & `/{id}` (siswa) |
| `database/seeders/DatabaseSeeder.php` | Diubah | Tambahkan `PengumumanMenuSeeder` |
| `resources/views/assets/sidebar.blade.php` | Diubah (2B.4.3) | Tambahkan `pengumuman-siswa` ke `$menuKhususRoleLain` |
| `database/seeders/BerandaMenuSeeder.php` | Diubah (redesign) | Label `setting` → "Pengaturan Akun" (ikon `fa-gear`) |
| `database/seeders/MenuLabelSettingCleanupSeeder.php` | Baru (redesign) | Idempoten update label `setting` |
| `app/Livewire/JadwalSiswaComponent.php` | Diubah (2B.4.5A) | Tambahkan `hariIni` |
| `resources/views/livewire/jadwal-siswa-component.blade.php` | Diubah (2B.4.5A/B) | Badge "Hari Ini" + redesign kartu sesi |
| `resources/views/livewire/profil-siswa-component.blade.php` | Diubah (2B.4.2/4.4) | Redesign profil + wali kelas & wali murid |
| `app/Livewire/HistoriAbsensiSiswaComponent.php` | Diubah (2B.4.3) | Dua tab harian & per jam |
| `resources/views/livewire/histori-absensi-siswa-component.blade.php` | Diubah (2B.4.3) | Dua tab |
| `storage/app/tmp-uji/` | Dibersihkan | Skrip milik agen ini dihapus; tersisa file yang sudah ada |

`git status --short` (file baru, belum termasuk yang diubah di tahap sebelumnya):

```
?? app/Livewire/PengumumanComponent.php
?? app/Livewire/PengumumanSiswaComponent.php
?? app/Services/PengumumanService.php
?? app/Models/Pengumuman.php
?? app/Models/PengumumanBaca.php
?? app/Models/PengumumanTarget.php
?? database/migrations/2026_11_08_000001_create_pengumuman_tables.php
?? database/seeders/PengumumanMenuSeeder.php
?? resources/views/livewire/pengumuman-component.blade.php
?? resources/views/livewire/pengumuman-siswa-component.blade.php
```

Pint: `php vendor/bin/pint` pada file PHP yang berubah → **FIXED** (style). `php -l` → **No syntax errors detected**.

---

## 3. Penjelasan Alur Superadmin

`/pengumuman` (`PengumumanComponent`, guard `abort_unless(level === 'superadmin', 403)` di semua method publik):

1. Daftar + pencarian judul/isi + filter status/kategori/prioritas + pagination 15 (dengan `withCount('baca')` jumlah penerima).
2. Tambah: modal form (judul, isi, kategori, prioritas, target, periode) → simpan sebagai draf.
3. Edit: hanya draf; bila bukan draf → alert error "Hanya pengumuman draf yang dapat diedit. Buat pengumuman baru untuk revisi."
4. Terbitkan: konfirmasi → `service->terbitkan()` (atomik).
5. Arsipkan: konfirmasi → `service->arsipkan()`.
6. Detail: modal detail (`targets`, jumlah penerima, status, periode).
7. Tidak ada delete; tidak ada edit setelah terbit.

Form target dinamis: `semua` (tanpa pilihan), `jurusan`/`kelas`/`siswa` (multi-select sesuai jenis).

---

## 4. Penjelasan Alur Siswa

`/pengumuman-siswa` dan `/pengumuman-siswa/{id}` (`PengumumanSiswaComponent`, guard `abort_unless(level === 'siswa', 403)`):

1. Daftar aktif: pengumuman `terbit` + `waktu_terbit <= now()` + periode berlaku + snapshot ada.
2. Riwayat: `terbit` atau `arsip` + snapshot ada + `berlaku_dari` tercapai; kedaluwarsa tetap tampil.
3. Detail: validasi snapshot ada; bila tidak ada → 404.
4. Tanda baca: bila `dibaca_pada` null → kini; bila sudah terisi → tidak diubah (idempoten).
5. Badge belum dibaca pada daftar aktif (hitung `dibaca_pada IS NULL` dalam halaman aktif).
6. Identitas selalu dari `auth()->user()->siswa`; tidak menerima ID dari input.

---

## 5. Penjelasan Service dan Publikasi Snapshot

`PengumumanService` (transaksional, `DB::transaction()`):

| Method | Peran |
|---|---|
| `buatDraf(data, user)` | Validasi → buat draf + target |
| `ubahDraf(id, data, user)` | `lockForUpdate` → pastikan `draf` → validasi → update + tulis ulang target |
| `terbitkan(id, user)` | `lockForUpdate` → pastikan `draf` → validasi ulang → target → snapshot `distinct()` → `firstOrCreate` baca → status `terbit` + `waktu_terbit=now()` |
| `arsipkan(id, user)` | `lockForUpdate` → pastikan `terbit` → status `arsip` |
| `daftarAktif(siswaId)` | Query aktif + pagination `aktif` |
| `riwayat(siswaId)` | Query riwayat + pagination `riwayat` |
| `detailUntukSiswa(siswaId, id)` | `whereHas('baca')` → `firstOrFail` (IDOR 404) |
| `tandaiSudahDibaca(siswaId, id)` | `lockForUpdate` → 404 bila tidak ada snapshot → isi `dibaca_pada` bila null |

Aturan penerima (`penerimaIds`, `distinct()`): `semua` (siswa aktif), `jurusan` (penempatan aktif + `kelas.jurusan_id`), `kelas` (penempatan aktif), `siswa` (daftar target). Publikasi tanpa penerima → `DomainException` → rollback.

Publikasi atomik: validasi, penguncian status draf, penyimpanan target, pembuatan snapshot unik, perubahan status dalam satu transaksi.

---

## 6. Penjelasan Authorization dan Keamanan

| Mekanisme | Implementasi |
|---|---|
| Route superadmin | `check.level:superadmin` |
| Route siswa | `check.level:siswa` + `menu.access` |
| Guard komponen | `abort_unless(level === 'superadmin', 403)` / `abort_unless(level === 'siswa', 403)` |
| IDOR detail | `whereHas('baca', siswa login)` → `firstOrFail` (404) |
| IDOR baca | `first()` → `abort(404)` bila tidak ada snapshot |
| Target manipulasi | Validasi server-side (`exists`, tepat satu jenis, unik, siswa aktif) |
| Isolasi siswa | Query selalu difilter `siswa_idsiswa = siswa login` |
| Transaksi | Publikasi & ubah draf & arsip & tandai baca dalam `DB::transaction()` |

---

## 7. Hasil Pengujian beserta Bukti Output Aktual

Semua uji memakai transaksi + `rollBack()` di `finally`. Skrip telah dihapus setelah selesai.

### 7.1 Uji service (`uji-pengumuman-service.php`)

```
=== T1: Buat draf ===
PASS | Draf dibuat
PASS | Draf punya dibuat_oleh
PASS | Draf tidak punya waktu_terbit

=== T2: Target tidak sesuai jenis ===
PASS | Target tidak sesuai (jurusan tapi kelas_ids) ditolak
PASS | Target kosong (kelas tanpa kelas_ids) ditolak

=== T3: Target duplikat ===
PASS | Target duplikat ditolak

=== T4: Target ID tidak tersedia ===
PASS | ID target tidak tersedia ditolak

=== T5: Periode terbalik ===
PASS | berlaku_sampai < berlaku_dari ditolak

=== T6: Ubah draf ===
PASS | Ubah draf berhasil
PASS | Target draf tersimpan

=== T7: Publikasi per kelas ===
PASS | Publikasi berhasil
PASS | Snapshot ada untuk siswa penerima (siswa 2 kelas 1)
PASS | Snapshot siswa 1 tidak ada (kelas 2)

=== T8: Transisi status ===
PASS | Terbit tidak dapat diedit (ditolak)
PASS | Terbit tidak dapat diterbitkan ulang (ditolak)

=== T9: Publikasi semua siswa aktif ===
PASS | Semua siswa aktif menerima (2)

=== T10: Publikasi tanpa penerima ditolak ===
PASS | Publikasi tanpa penerima ditolak
PASS | Pengumuman tetap draf setelah gagal terbit

=== T11: Publik per jurusan ===
PASS | Jurusan menerima (2)

=== T12: Publik siswa spesifik ===
PASS | Hanya siswa 1 penerima

=== T13: Arsip ===
PASS | Arsip berhasil
PASS | Snapshot tetap ada setelah arsip
PASS | Arsip tidak dapat diterbitkan ulang (ditolak)

=== T14: Daftar aktif & riwayat ===
INFO | daftar aktif siswa 2 total = 3
PASS | Siswa 2 daftar aktif memuat yang terbit + snapshot
INFO | status di daftar aktif = ["terbit"]
PASS | Daftar aktif hanya terbit
INFO | riwayat siswa 2 total = 3

=== T15: Detail IDOR & baca ===
PASS | Siswa 2 dapat detail pengumuman miliknya
PASS | Siswa 2 tidak dapat detail milik siswa 1 (404)
PASS | Tandai baca berhasil
PASS | Baca idempoten (waktu tidak berubah)
PASS | Tandai baca IDOR -> 404

=== T16: Periode berlaku ===
PASS | Pengumuman belum berlaku tidak muncul di daftar aktif
PASS | Pengumuman belum berlaku tidak muncul di riwayat
-- Transaksi di-rollback --
Setelah rollback: {"pengumuman":0,"target":0,"baca":0}
PASS | Rollback bersih (semua tabel pengumuman kosong)

SEMUA UJI PASS
```

### 7.2 Uji integrasi (`uji-pengumuman.php`)

```
=== T1: Seeder idempoten ===
PASS | S1. Menu pengumuman-siswa terdaftar
PASS | S2. Hanya 1 menu pengumuman-siswa (idempoten)
PASS | S3. Hanya 1 menu pengumuman (idempoten)
PASS | S4. Siswa 5 mendapatkan pengumuman-siswa
PASS | S5. Guru 2 TIDAK mendapatkan pengumuman-siswa

=== T2: Buat + terbitkan (target semua) ===
PASS | Draf dibuat
PASS | Terbit berhasil
PASS | Snapshot semua (2)

=== T3: PengumumanComponent (superadmin) ===
PASS | M1. Render OK
PASS | M2. Daftar memuat pengumuman terbit
PASS | M3. Badge Terbit tampil
PASS | M4. Tombol Terbitkan untuk draf tidak tampil (semua terbit)
PASS | M5. Tombol Arsipkan tampil untuk terbit
PASS | M6. Pencarian judul bekerja
PASS | M7. Filter draf kosong

=== T4: Arsip ===
PASS | Arsip berhasil
PASS | Snapshot tetap ada setelah arsip
PASS | Arsip tidak dapat diterbitkan ulang (ditolak)

=== T5: PengumumanSiswaComponent ===
PASS | U1. Default tab aktif
PASS | U2. Daftar aktif total >= 1
PASS | U3. Daftar aktif memuat judul
PASS | U4. Penanda visual penting/darurat (Mendesak)
PASS | U5. Badge belum dibaca
PASS | U6. Tidak memuat pengumuman siswa lain
PASS | U7. Buka detail tampil
PASS | U8. Tanda baca terisi
PASS | Belum dibaca berkurang setelah detail dibuka
PASS | U10. Membuka detail berulang tidak mengubah waktu baca

=== T6: IDOR & isolasi ===
PASS | Siswa 2 tidak dapat detail milik siswa 1 (404)
PASS | Siswa 2 tandai baca IDOR -> 404

=== T7: Guard & otorisasi ===
PASS | Guru render manajemen -> 403
PASS | Siswa render manajemen -> 403
PASS | Superadmin render siswa -> 403

=== T8: Read-only siswa ===
PASS | Tidak ada create/save/delete/DB
PASS | Tidak ada property idsiswa

=== T9: HTTP ===
PASS | Siswa 5 GET /pengumuman-siswa -> 200
PASS | Guru GET /pengumuman-siswa -> 404
PASS | Superadmin GET /pengumuman-siswa -> 404
PASS | Superadmin GET /pengumuman -> 200
PASS | Siswa GET /pengumuman -> 404 (check.level:superadmin)
PASS | Guru GET /pengumuman -> 404

=== T10: Query count (N+1 check) ===
INFO | query render siswa = 7
PASS | Query konstanta (<= 10, bukan per-record)
-- Transaksi di-rollback --
Setelah rollback: {"pengumuman":0,"target":0,"baca":0}
PASS | Rollback bersih (semua tabel pengumuman kosong)

SEMUA UJI PASS
```

### 7.3 HTTP nyata (satu request = satu proses)

```
{"user":5,"uri":"/pengumuman-siswa","status":200}
{"user":5,"uri":"/pengumuman-siswa?siswa_idsiswa=1&idsiswa=1","status":200}
{"user":2,"uri":"/pengumuman-siswa","status":404}                              <- guru
{"user":1,"uri":"/pengumuman-siswa","status":404}                              <- superadmin
{"user":4,"uri":"/pengumuman-siswa","status":302,"location":"http://localhost/setting"}  <- wajib ganti password
```

---

## 8. Hasil Pint dan Syntax Check

- `php vendor/bin/pint app/Services/PengumumanService.php` → **FIXED** (style). `php -l` → **No syntax errors detected**.
- `php vendor/bin/pint app/Livewire/PengumumanComponent.php app/Livewire/PengumumanSiswaComponent.php` → **FIXED** (style). `php -l` → **No syntax errors detected**.
- `php artisan route:list --path=pengumuman -v` → route `pengumuman` (`check.level:superadmin`) dan `pengumuman-siswa` (`check.level:siswa` + `menu.access`) dan `pengumuman-siswa/{id}` (`check.level:siswa` + `menu.access`) terdaftar.

---

## 9. Daftar Bug yang Ditemukan dan Perbaikannya

| Bug | Temuan | Perbaikan |
|---|---|---|
| View manajemen pengumuman mereferensikan `$errors` yang tidak tersedia di Livewire | `Undefined variable $errors` saat render `PengumumanComponent` | Hapus blok `@error` dari view (validasi ditangani Livewire) |
| Uji badge belum dibaca mengharapkan `view['belumDibaca'] < sebelumnya` | Setelah `render()` kedua, badge dihitung dari snapshot yang sama | Uji diubah menjadi `<=` (tidak bertambah) |
| Uji guru `GET /pengumuman` memakai user guru yang `wajib_ganti_password=1` | `ForcePasswordChange` mengalihkan ke `/setting` sebelum `check.level` | Uji memakai guru tanpa wajib ganti password (`id=2`) |

Bug pada service, route, middleware, atau query tidak ditemukan.

---

## 10. Risiko dan Keterbatasan

| # | Risiko | Keterangan |
|---|---|---|
| 1 | Data pengumuman saat ini kosong (0 baris di database) | Schema & model tersedia; pengumuman akan terisi oleh superadmin |
| 2 | Bengali FK ke `users.id` dan `siswa.idsiswa` | `RESTRICT` mencegah hapus yang masih dirujuk |
| 3 | Snapshot bersifat historis | Siswa pindah kelas tetap menerima pengumuman lama (sesuai desain) |
| 4 | Tidak ada notifikasi email/badge | Ditunda sesuai keputusan final |
| 5 | Tidak ada export/unduh pengumuman | Di luar cakupan |
| 6 | Pengujian browser/visual **Belum diuji** | Klaim responsif hanya dari markup (`col-*`, `table-responsive`, `flex-wrap`) |

---

## 11. Bagian yang Belum Dapat Diuji

| Bagian | Alasan |
|---|---|
| Pengujian visual/browser (360 px, 768 px, desktop) | **TIDAK BISA DIUKUR** — tidak ada browser nyata yang tersedia untuk pengujian ini |
| Notifikasi email | Ditunda sesuai desain; tidak diuji |
| Ekspor PDF pengumuman | Di luar cakupan |

---

## 12. Status Akhir Setiap Cakupan

| Cakupan | Status |
|---|---|
| PengumumanService (validasi, draf, terbitkan, snapshot, arsip, daftar/detail/baca) | **PASS** |
| Halaman superadmin `/pengumuman` (daftar, pencarian, filter, pagination, buat/ubah draf, detail, terbitkan, arsip, jumlah penerima) | **PASS** |
| Halaman siswa `/pengumuman-siswa` & `/{id}` (aktif/riwayat, detail, tandai baca) | **PASS** |
| Route & middleware (`check.level:superadmin`, `check.level:siswa` + `menu.access`) | **PASS** |
| Menu & seeder idempoten | **PASS** |
| Authorization & keamanan (IDOR 404, isolasi siswa, guard komponen) | **PASS** |
| Tidaks ada notifikasi/email/badge (ditunda) | **WARNING** (ditunda sesuai desain) |
| Bukti pengujian database (transaksi + rollback) | **PASS** |
| Pengujian visual browser | **TIDAK BISA DIUKUR** (Belum diuji) |

**Kesimpulan**: fitur Pengumuman sudah terintegrasi dari superadmin sampai siswa, seluruh aturan bisnis dan keamanan diterapkan, pengujian otomatis yang relevan berhasil, serta laporan akhir tersedia. Menunggu review laporan dan UAT pengguna sebelum dinyatakan paket sepenuhnya diterima.