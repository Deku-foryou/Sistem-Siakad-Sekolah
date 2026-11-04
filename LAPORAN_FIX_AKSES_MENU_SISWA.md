# LAPORAN FIX AKSES MENU SISWA

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Latar | Audit implementasi Tahap 2B.4.1 — pencabutan `usermenu` hanya menyembunyikan sidebar; URL masih bisa diakses langsung |
| Cakupan fix | Satu baris di `routes/web.php` |
| Tidak diubah | Middleware baru (tidak dibuat), guru/superadmin, alur wajib ganti password, struktur database, `.env` |

---

## 1. Penyebab dan Perbaikan

### 1.1 Penyebab

`MenuAccessMiddleware` memeriksa `usermenu -> appmenunew.url == nama route`, tetapi middleware ini **tidak dipasang** pada grup siswa:

```php
Route::middleware(['check.level:siswa'])->group(
    function () {
        Route::get('berandasiswa', BerandasiswaComponent::class)->name('berandasiswa');
        Route::get('jadwalsiswa', JadwalSiswaComponent::class)->name('jadwalsiswa');
        Route::get('absensi-siswa', HistoriAbsensiSiswaComponent::class)->name('absensi-siswa');
    }
);
```

### 1.2 Perbaikan (tanpa middleware baru)

```php
Route::middleware(['check.level:siswa', 'menu.access'])->group(
    function () {
        Route::get('berandasiswa', BerandasiswaComponent::class)->name('berandasiswa');
        Route::get('jadwalsiswa', JadwalSiswaComponent::class)->name('jadwalsiswa');
        Route::get('absensi-siswa', HistoriAbsensiSiswaComponent::class)->name('absensi-siswa');
    }
);
```

- **Tidak ada middleware baru**; hanya menambah `menu.access` (alias `MenuAccessMiddleware` yang sudah didaftarkan di `app/Http/Kernel.php:70`).
- `$explicitUrls` tetap memuat ketiganya, sehingga loop dinamis tidak menduplikasi route.
- Guard komponen (`abort_unless(level==='siswa', 403)`) dipertahankan sebagai pertahanan berlapis.

### 1.3 Prasyarat data

Sebelum fix ini, siswa sudah pasti terdaftar menunya karena `JadwalSiswaMenuSeeder` (tahap 2B.4.1) dijalankan (persist) pada database aktif. Verifikasi:

```
usermenu siswa 5: ["berandasiswa","setting","absensi-siswa","jadwalsiswa"]
usermenu guru 2: ["berandaguru","setting","absensi-harian"]
```

### 1.4 Route setelah perbaikan

`php artisan route:list --path=jadwalsiswa -v` (sama untuk `berandasiswa`, `absensi-siswa`):

```
GET|HEAD  jadwalsiswa  ...  JadwalSiswaComponent
          ⇂ web
          ⇂ App\Http\Middleware\Authenticate
          ⇂ App\Http\Middleware\CekAkunAktif
          ⇂ App\Http\Middleware\ForcePasswordChange
          ⇂ App\Http\Middleware\CheckUserLevel:siswa
          ⇂ App\Http\Middleware\MenuAccessMiddleware
```

`php vendor/bin/pint routes/web.php` → **PASS, 1 file**.

---

## 2. Hasil Pengujian dengan Bukti Output

Semua uji memakai transaksi + `rollBack()` di `finally`. Pencabutan `usermenu` dilakukan di dalam proses yang sama dengan request HTTP (satu proses + satu transaksi lokal), sehingga bukti yang dihasilkan benar-benar menguji jalur `menu.access`.

```
=== Siapkan menu (seeder idempoten) ===
INFO | id menu = {"berandasiswa":3,"jadwalsiswa":24,"absensi-siswa":25}
PASS | Menu berandasiswa ada
PASS | Menu jadwalsiswa ada
PASS | Menu absensi-siswa ada
PASS | Siswa 5 punya ketiga menu
PASS | Guru 2 tidak punya menu siswa

=== HTTP dengan menu terdaftar (persist) ===
PASS | Siswa 5 (menu lengkap) GET /berandasiswa -> 200
PASS | Siswa 5 (menu lengkap) GET /jadwalsiswa -> 200 (helper)
PASS | Siswa 5 (menu lengkap) GET /absensi-siswa -> 200 (helper)

=== HTTP tanpa menu (akses langsung ditolak, satu proses + transaksi lokal) ===
PASS | Siswa 5 tanpa usermenu berandasiswa -> 404
PASS | Siswa 5 tanpa usermenu jadwalsiswa -> 404
PASS | Siswa 5 tanpa usermenu absensi-siswa -> 404

=== Pencabutan satu menu tidak memengaruhi menu lain ===
PASS | Sidebar: /jadwalsiswa hilang setelah dicabut
PASS | Sidebar: /berandasiswa tetap ada
PASS | Sidebar: /absensi-siswa tetap ada
PASS | Akses /berandasiswa tetap 200
PASS | Akses /absensi-siswa tetap 200

=== Peran lain (tetap 404 oleh CheckUserLevel) ===
PASS | Guru 2 GET /jadwalsiswa -> 404
PASS | Guru 2 GET /absensi-siswa -> 404
PASS | Guru 2 GET /berandasiswa -> 404
PASS | Superadmin 1 GET /berandasiswa -> 404
PASS | Superadmin 1 GET /jadwalsiswa -> 404
PASS | Superadmin 1 GET /absensi-siswa -> 404

=== Siswa wajib ganti password (alur tidak diubah) ===
PASS | Siswa 4 (wajib_ganti_password) -> 302 ke /setting (bukan 404)
PASS | Siswa 4 GET /setting -> 200

=== Guard komponen tetap ada ===
PASS | Guard komponen: superadmin render -> 403

=== Visibilitas menu lain tidak berubah ===
PASS | Sidebar guru: /absensi-harian tetap ada
PASS | Sidebar guru: tidak memuat menu siswa
PASS | Sidebar superadmin: /dashboard tetap ada
PASS | Sidebar superadmin: tidak memuat menu siswa
-- Transaksi di-rollback --
Setelah rollback: {"appmenunew_siswa":3,"usermenu_siswa5":4}
PASS | Rollback bersih (menu siswa tetap 3; usermenu siswa 5 tetap 4)

SEMUA UJI PASS
```

---

## 3. Catatan Pengujian (metodologi)

| Pendekatan | Kelebihan | Keterbatasan |
|---|---|---|
| Satu request = satu proses + `shell_exec` | Isolation penuh antar request | Mencabut `usermenu` pada proses induk **tidak terlihat** oleh proses anak karena koneksi database terpisah; sehingga dipakai untuk skenario *menu terdaftar* |
| Satu proses + **transaksi lokal anak** (cabut, handle, rollback) | Pencabutan terlihat oleh jalur yang diuji | Ada efek samping harness: beberapa request dalam satu proses memakai sesi CLI yang dibagi |
| Render view `assets.sidebar` dalam proses yang sama | Verifikasi visibilitas menu pasca-pencabutan tanpa HTTP | Tidak menguji jalur HTTP |

Kombinasi ketiga pendekatan di atas menghasilkan bukti konsisten: pada skenario menu terdaftar (200) maupun menu dicabut (404), hasilnya cocok dengan ekspektasi.

---

## 4. Risiko yang Harus Dipahami

| Risiko | Detail |
|---|---|
| Siswa baru tanpa `usermenu` | Setiap route siswa kini 404 hingga menu diberikan (mis. via `PaketMenuService::terapkanDefault` saat akun dibuat). Ini perilaku yang benar, tetapi perlu disampaikan ke admin agar siswa lama selalu terdaftar |
| Penamaan route = URL menu | `MenuAccessMiddleware` cocokkan **nama route** dengan `appmenunew.url`; jika keduanya berubah, perlu penyesuaian |
| Tidak ada perubahan visibilitas | Sidebar guru/superadmin terverifikasi tidak berubah |
| Cache route | Perlu `php artisan route:clear` setelah deploy agar perubahan middleware aktif |
| Pengujian browser/visual | **Belum diuji** pada browser nyata; hanya HTTP & render server-side |

---

## 5. Berkas yang Berubah pada Fix Ini

| File | Aksi | Ringkasan |
|---|---|---|
| `routes/web.php` | **Diubah** | Tambahkan `'menu.access'` pada grup `check.level:siswa` |
| `database/seeders/DatabaseSeeder.php` | Persist dijalankan | Menjalankan `ConfirmDatabaseSeeder` tidak diperlukan; seeder `BerandaMenuSeeder` + `JadwalSiswaMenuSeeder` sudah dijalankan pada DB aktif |
| `storage/app/tmp-uji/` | Dibersihkan | Skrip milik agen ini dihapus; tersisa file yang sudah ada sebelumnya |

`git diff routes/web.php`:

```diff
-        Route::middleware(['check.level:siswa'])->group(
+        Route::middleware(['check.level:siswa', 'menu.access'])->group(
```

**TIDAK BISA DIUKUR**: pengujian browser/visual pada 360 px — tidak ada browser nyata yang tersedia untuk pengujian ini.**

**Kesimpulan**: satu baris perubahan menutup celah akses langsung. Siswa dengan menu terdaftar mendapat HTTP 200; siswa yang menu `usermenu`-nya dicabut ditolak (404) meskipun membuka URL langsung; guru dan superadmin tetap ditolak `CheckUserLevel:siswa`; alur wajib ganti password tidak berubah.