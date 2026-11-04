# LAPORAN IMPLEMENTASI TAHAP 2B.4.1 — FONDASI PORTAL SISWA

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Dasar | `LAPORAN_AUDIT_PORTAL_SISWA_2B4.md` (audit 2B.4) |
| Cakupan | Tahap 1 saja: Beranda, route/menu, pesan wajib ganti password |
| Tidak dikerjakan | Profil Siswa (Tahap 2), Absensi Harian (Tahap 3), Rekap (Tahap 4), pemolesan UX (Tahap 5) |
| Batas dipatuhi | Tanpa migrasi, tanpa perubahan struktur DB, tanpa operasi Git tulis, tanpa membaca `.env` |

---

## 1. Daftar File yang Diubah / Dibuat

| File | Aksi | Ringkasan |
|---|---|---|
| `app/Livewire/BerandasiswaComponent.php` | **Diubah** | Beranda memakai `KonteksAkademikService::ambil()` + `jadwalkelas`/`jamhari`; filter `kelassiswa.status='aktif'` + tahun ajaran aktif; kirim `konteks`, `kelasList`, `jadwalHariIni` (Collection) |
| `app/Services/JadwalSiswaService.php` | **Baru** | Sumber tunggal `kelasAktif()`, `jadwal()`, `urutkan()`, `perHari()` dengan aturan `jadwalkelas`+`jamhari` |
| `resources/views/livewire/berandasiswa-component.blade.php` | **Diubah** | Tampilkan TA, semester, kelas, jurusan, wali kelas; tabel responsif; empty state informatif; hapus placeholder "Segera tersedia"/"Tagihan SPP" |
| `routes/web.php` | **Diubah** | Tambahkan `berandasiswa`, `jadwalsiswa`, `absensi-siswa` ke `$explicitUrls` |
| `database/seeders/JadwalSiswaMenuSeeder.php` | **Baru** | Mendaftarkan menu `jadwalsiswa` + `absensi-siswa`, role paket Siswa, sinkron `usermenu` (idempoten) |
| `database/seeders/DatabaseSeeder.php` | **Diubah** | Tambahkan `JadwalSiswaMenuSeeder` pada daftar seeder |
| `resources/views/assets/sidebar.blade.php` | **Diubah** | Hapus blok hardcoded siswa; menu siswa kini dinamis dari `usermenu`; daftar menu khusus peran diperluas |
| `resources/views/setting.blade.php` | **Diubah** | Penjelasan langkah & efek pembatasan saat `wajib_ganti_password=1` |
| `LAPORAN_AUDIT_PORTAL_SISWA_2B4.md` | Tidak diubah | Dokumen audit (dari tahap sebelumnya) |
| `storage/app/tmp-uji/` | Skrip milik sendiri **dihapus** | Meyisakan file yang sudah ada: `check_sidebar.php`, `inspect_siswa.php`, `test_histori.php` |

`git diff --stat` (file ter-track):

```
app/Livewire/BerandasiswaComponent.php             | onty 86
database/seeders/DatabaseSeeder.php                | 1+
resources/views/assets/sidebar.blade.php           | onty 18
resources/views/livewire/berandasiswa-component.blade.php | onty 216
resources/views/setting.blade.php                 | 4+-
routes/web.php                                     | 2+-
6 files changed, 179 insertions(+), 148 deletions(-)
```

Pint: `php vendor/bin/pint` pada file PHP yang berubah → **PASS, 4 files**. `php -l` pada tiga file PHP diubah → **No syntax errors detected**.

---

## 2. Ringkasan Implementasi

### 2.1 Beranda
- `BerandasiswaComponent` tidak lagi mengimpor `Publikasijadwal`, `Kelas`, `Semester`, `Tahunajaran`.
- Konteks diambil dari `KonteksAkademikService::ambil()` (satu-satunya sumber TA & semester aktif).
- Kelas dari `Kelassiswa` dengan `status='aktif'` dan `kelas.tahunajaran_idtahunajaran = TA aktif`, eager-load `kelas.jurusan` dan `kelas.wali`.
- Jadwal hari ini dari `JadwalKelas` dengan relasi `jamhari`, `mapel`, `guru`; filter semester aktif, `jamhari.kelas_id ∈ kelas aktif`, `jamhari.hari = now()->dayOfWeekIso`; diurutkan jam ke- lalu waktu mulai.
- Guard komponen tetap `abort_unless(level==='siswa', 403)` (pertahanan berlapis).
- `JadwalSiswaService` menyediakan logika yang sama secara reusable untuk komponen jadwal, sehingga Beranda dan `/jadwalsiswa` konsisten.

### 2.2 Route & menu
- `$explicitUrls` kini memuat `berandasiswa`, `jadwalsiswa`, `absensi-siswa` → loop dinamis skip ketiganya → ketiganya memakai `check.level:siswa`.
- `JadwalSiswaMenuSeeder`:
  - Kategori `Jadwal & Absensi` (`firstOrCreate`).
  - Menu `jadwalsiswa` (Jadwal Pelajaran, urut 10) dan `absensi-siswa` (Histori Absensi, urut 11) via `unguarded + firstOrCreate`.
  - Role hanya pada paket `Siswa` (`Role::firstOrCreate`).
  - `PaketMenuService::sinkronkanSemua('siswa')` → hanya menambah `usermenu` milik user `level=siswa`; menu user lain tidak disentuh.
  - Idempoten: dijalankan dua kali tidak menambah duplikat menu/role (teruji).
- `DatabaseSeeder` memanggil seeder baru setelah `AbsensiHarianMenuSeeder`.
- Sidebar: blok `@if (level === 'siswa')` dihapus; menu `jadwalsiswa`/`absensi-siswa` tampil lewat loop dinamis `usermenu`. `$menuKhususRoleLain` kini mencakup keempat menu khusus peran agar superadmin tidak melihatnya (DB superadmin sudah memiliki seluruh menu).

### 2.3 Pesan wajib ganti password
- Alert di `/setting` diperjelas: langkah (Password Lama, Password minimal 8 karakter, Konfirmasi, Save Change) dan efek (tetap ke `/setting`, belum bisa membuka Beranda/Jadwal/Histori, diteruskan ke Beranda setelah berhasil).
- **Alur keamanan tidak diubah**: middleware `ForcePasswordChange` dan logic `AuthController@update` tidak disentuh.

---

## 3. Hasil Pengujian dengan Bukti Output

Semua uji dijalankan dalam transaksi + `rollBack()` (di `finally`). Skrip telah dihapus setelah selesai.

### 3.1 Uji komponen (render Livewire, 60+ butir — paksa `Carbon::setTestNow`)

```
=== T1: Seeder idempoten (JadwalSiswaMenuSeeder) ===
PASS | S1. Menu jadwalsiswa terdaftar
PASS | S2. Menu absensi-siswa terdaftar
PASS | S3. Kategori menu Jadwal & Absensi
PASS | S4. Paket Siswa berisi 2 menu baru
PASS | S5. Paket Siswa lama utuh (berandasiswa, setting masih ada)
PASS | S6. Paket Guru TIDAK berisi menu siswa
PASS | S7. User siswa 4/5 mendapatkan 2 menu baru
PASS | S8. User 1/2/3/6 TIDAK mendapatkan menu siswa
PASS | S9. Seeder kedua kali tidak menambah duplikat menu
PASS | S10. Seeder kedua kali tidak menambah duplikat role
PASS | S11. BerandaMenuSeeder tetap berjalan tanpa error

=== T2: Beranda vs Jadwal konsisten (Senin, siswa 2 / kelas 1) ===
PASS | B1. Beranda menampilkan Matematika
PASS | B2. Beranda menampilkan Agama
PASS | B3. Beranda menampilkan guru Matsumoto uchiha
PASS | B4. Beranda menampilkan Tahun Ajaran 2026/2027
PASS | B5. Beranda menampilkan Semester Ganjil
PASS | B6. Beranda menampilkan kelas X MIPA 1
PASS | B7. Beranda menampilkan jurusan
PASS | B8. Beranda menampilkan wali kelas
PASS | B9. Beranda tidak lagi menulis "Tidak ada jadwal hari ini"
```

**PASS | B10. Beranda tidak memuat placeholder "Segera tersedia"**
**PASS | B11. Beranda tidak memuat placeholder "Tagihan SPP"**
**PASS | B12. Beranda memakai table-responsive**
**PASS | B13. Beranda tidak memuat data siswa lain (Yuji itadori)**
**PASS | B16. Konsisten: Beranda & /jadwalsiswa sama-sama memuat Matematika dan Agama**

```
=== T3: Empty state & konteks ===
PASS | E1. Rabu: empty state "Tidak ada jadwal hari ini" tampil
PASS | E2. Rabu: tautan ke jadwal mingguan tampil
PASS | E3. Siswa tanpa jadwal: empty state tampil
PASS | E4. Siswa tanpa jadwal: tetap menampilkan kelas XI MIPA 1
PASS | E5. Siswa tanpa jadwal: tidak melihat Matematika milik kelas lain
PASS | E6. Siswa tanpa jadwal: tidak memuat data siswa lain (Hagoromo)

=== T4: Semester aktif vs ID terbesar (dalam transaksi) ===
INFO | semester insert id = 22
INFO | konteks semester id (harus 1) = 1
PASS | K1. Konteks memakai semester aktif (id 1), bukan ID terbesar
PASS | K2. Beranda tetap menampilkan jadwal semester aktif (Matematika ada)
PASS | K3. Beranda menampilkan Semester Ganjil (bukan Genap)

=== T5: Penempatan nonaktif tidak ditampilkan ===
PASS | N1. Kelas nonaktif X KOMPUTER 1 tidak tampil di Beranda
PASS | N2. Kelas aktif X MIPA 1 tetap tampil

=== T6: Tidak ada legacy pada jalur siswa ===
PASS | L1. BerandasiswaComponent tidak menyebut Publikasijadwal
```

**PASS | L2. BerandasiswaComponent tidak menyebut model legacy Jadwal**
**PASS | L3. BerandasiswaComponent memakai KonteksAkademikService::ambil()**
**PASS | L4. BerandasiswaComponent memakai JadwalKelas (jadwalkelas)**
**PASS | L5. View Beranda tidak menyebut "Segera tersedia"**
**PASS | L6. View Beranda tidak menyebut "Tagihan SPP"**

```
=== T7: Sidebar dinamis ===
PASS | D1. Sidebar siswa memuat /jadwalsiswa dinamis
PASS | D2. Sidebar siswa memuat /absensi-siswa dinamis
PASS | D3. Sidebar tidak lagi hardcoded
PASS | D4. Sidebar guru TIDAK memuat /jadwalsiswa
PASS | D5. Sidebar guru TIDAK memuat /absensi-siswa
PASS | D6. Sidebar superadmin TIDAK memuat menu siswa
PASS | D7. Sidebar superadmin tetap memuat Dashboard

=== T8: Pencabutan menu via usermenu ===
PASS | R1. Setelah usermenu jadwalsiswa dicabut: sidebar tidak memuat /jadwalsiswa
PASS | R2. Menu absensi-siswa tetap ada

=== T9: Pesan wajib ganti password ===
PASS | W1. /setting menjelaskan cara ganti password (Password Lama, minimal 8 karakter)
PASS | W2. /setting menjelaskan efek pembatasan akses
PASS | W3. Alur keamanan tidak diubah (blok wajib_ganti_password tetap ada)

=== T10: Guard komponen (siswa saja) ===
PASS | G1. Superadmin render Beranda -> 403 (guard komponen)
PASS | G2. Guru render Beranda -> 403 (guard komponen)

Setelah rollback: {"appmenunew_jadwalsiswa":0,"kelassiswa":2,"semester":1,"jadwalkelas":3,"jamhari":8}
PASS | Rollback bersih (menu/penempatan/semester/jadwal tidak berubah)

SEMUA UJI PASS
```

### 3.2 Uji HTTP nyata (satu request = satu proses)

```
{"user":5,"uri":"/berandasiswa","status":200}
{"user":5,"uri":"/jadwalsiswa","status":200,"has_matematika":true}
{"user":5,"uri":"/absensi-siswa","status":200}
{"user":5,"uri":"/setting","status":200}
{"user":2,"uri":"/berandasiswa","status":404}   <- guru
{"user":2,"uri":"/jadwalsiswa","status":404}    <- guru
{"user":1,"uri":"/berandasiswa","status":404}   <- superadmin
{"user":1,"uri":"/jadwalsiswa","status":404}    <- superadmin
{"user":4,"uri":"/berandasiswa","status":302,"location":"http://localhost/setting"}  <- wajib ganti password
{"user":5,"uri":"/berandasiswa?siswa=1","status":200}                                 <- parameter diabaikan
```

---

## 4. Hasil Verifikasi Route dan Middleware

`php artisan route:list --path=berandasiswa -v` (setelah perubahan):

```
GET|HEAD  berandasiswa  ...  BerandasiswaComponent
          ⇂ web
          ⇂ App\Http\Middleware\Authenticate
          ⇂ App\Http\Middleware\CekAkunAktif
          ⇂ App\Http\Middleware\ForcePasswordChange
          ⇂ App\Http\Middleware\CheckUserLevel:siswa
```

Urut middleware untuk `jadwalsiswa` dan `absensi-siswa` identik. **Sebelumnya** `/berandasiswa` hanya memiliki `MenuAccessMiddleware` (tanpa `CheckUserLevel`), sehingga superadmin yang mempunyai menu tersebut lolos ke komponen lalu mendapat 403. Kini keempat rute portal siswa konsisten `CheckUserLevel:siswa` → peran lain mendapat **404** (terbukti di §3.2).

| Skenario | Hasil |
|---|---|
| Siswa → `/berandasiswa` | 200 |
| Siswa → `/jadwalsiswa` | 200 |
| Siswa → `/absensi-siswa` | 200 |
| Guru → `/berandasiswa` / `/jadwalsiswa` | 404 |
| Superadmin → `/berandasiswa` / `/jadwalsiswa` | 404 (sebelumnya 403 dari komponen) |
| Siswa `wajib_ganti_password` → `/berandasiswa` | 302 → `/setting` |

---

## 5. Hasil Verifikasi Menu dan Sidebar Setiap Role

### 5.1 Data menu (dalam transaksi, setelah `JadwalSiswaMenuSeeder`)

- `appmenunew`: `jadwalsiswa` (Jadwal Pelajaran, urut 10), `absensi-siswa` (Histori Absensi, urut 11), kategori `Jadwal & Absensi`.
- `role` (paket Siswa): berisi 2 menu baru **dan** tetap berisi `berandasiswa` + `setting`.
- `role` (paket Guru): tidak berisi menu siswa.
- `usermenu`: user siswa 4 & 5 mendapatkan 2 menu baru; user 1, 2, 3, 6 tidak mendapatkan menu siswa.
- Idempoten: seeder kedua kali tidak menambah duplikat menu maupun role.

### 5.2 Sidebar (render view `assets.sidebar`)

| Role | `/berandasiswa` | `/jadwalsiswa` | `/absensi-siswa` | Referensi lain |
|---|---|---|---|---|
| Siswa (5) | ✔ (dinamis) | ✔ (dinamis) | ✔ (dinamis) | — |
| Guru (2) | — | — | — | absensi-harian tetap ada |
| Superadmin (1) | — (disembunyikan) | — | — | Dashboard tetap ada |

### 5.3 Pencabutan menu

Baris `usermenu` siswa 5 untuk `jadwalsiswa` dihapus dalam transaksi → sidebar siswa **tidak lagi** memuat `/jadwalsiswa`, sedangkan `absensi-siswa` tetap muncul. (Dampak pada akses halaman: `MenuAccessMiddleware` tidak relevan untuk route eksplisit; pembatasan akses tetap melalui `CheckUserLevel:siswa` + guard komponen. Pecabutan `usermenu` mengontrol **visibilitas menu**.)

---

## 6. Risiko atau Kendala yang Belum Terselesaikan

| No | Risiko / kendala | Keterangan |
|---|---|---|
| 1 | `/jadwalsiswa` dan `/absensi-siswa` tetap route eksplisit tanpa `menu.access` | Sesuai batasan tahap ini; consistency berlapis belum diterapkan |
| 2 | `JadwalSiswaComponent` masih memiliki query sendiri (duplikasi dengan `JadwalSiswaService`) | Beranda dan `/jadwalsiswa` konsisten hari ini, tetapi refactor `/jadwalsiswa` ke service dipertimbangkan di tahap berikut |
| 3 | Sidebar guru/superadmin tidak berubah | Terverifikasi; `jadwalguru`/`absensi-perjam` tetap hardcoded seperti sebelumnya |
| 4 | Jadwal Beranda belum menandai jam berlangsung & tanpa navigasi pekan | Di luar cakupan tahap ini (Tahap 5) |
| 5 | `git status` menunjukkan file `LAPORAN_AUDIT_PORTAL_SISWA_2B4.md` sebagai untracked | File audit dibuat di luar scope Git oleh agen sebelumnya |
| 6 | Pengujian browser/visual **Belum diuji** | Responsif hanya dari struktur view; perlu uji manual 360 px | 