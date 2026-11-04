# LAPORAN AUDIT PORTAL SISWA — TAHAP 2B.4.5

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal audit | 4 November 2026 |
| Sifat | **Audit only.** Tidak ada file aplikasi yang dibuat atau diubah; tidak ada migration/seeder; tidak mengubah database; tidak membaca `.env`; Git read-only |
| Metode | Pemeriksaan komponen/view/route/middleware + query skema & data read-only + render halaman siswa, semua dalam transaksi + `rollBack()` di `finally` |
| Cakupan | Jadwal Pelajaran, Pengumuman, Informasi akademik, Navigasi/sidebar, Responsivitas, Keamanan & isolasi data |

---

## 1. Kondisi Implementasi Saat Ini

Portal siswa terdiri dari **5 halaman** yang semuanya berawal dari `auth()->user()->siswa` dan `KonteksAkademikService::ambil()`:

| Halaman | Komponen | Info utama |
|---|---|---|
| `/berandasiswa` | `BerandasiswaComponent` | Identitas ringkas, TA/semester, kelas, jurusan, wali kelas, jadwal hari ini (dari `jadwalkelas`+`jamhari`) |
| `/jadwalsiswa` | `JadwalSiswaComponent` | Jadwal mingguan per hari (waktu, kelas, mapel, guru) + TA/semester |
| `/absensi-siswa` | `HistoriAbsensiSiswaComponent` | Histori harian & per jam (status, kelas, mapel, guru) + TA/semester |
| `/profil-siswa` | `ProfilSiswaComponent` | Identitas lengkap + 4 kartu statistik + kontak & keluarga |
| `/setting` | `AuthController@index` | Pengaturan akun (username/nama/email/password) |

### Status per area

| No | Area | Status |
|---|---|---|
| 1 | Jadwal Pelajaran siswa | **Tersedia** (dengan 3 catatan UX) |
| 2 | Pengumuman untuk siswa | **Belum tersedia** |
| 3 | Informasi akademik siswa | **Tersedia & konsisten** (wali kelas kini tertutup di Profil) |
| 4 | Navigasi & sidebar | **Tersedia & berlapis** |
| 5 | Responsivitas | **Markup responsif; browser Belum diuji** |
| 6 | Keamanan & isolasi data | **Aman** |

---

## 2. Temuan dan Bukti Lokasi File

### 2.1 Jadwal Pelajaran siswa

| # | Temuan | Bukti (file & contoh) |
|---|---|---|
| J-1 | Jadwal menampilkan Matematika & guru (Matsumoto uchiha) untuk kelas 1 | Render `JadwalSiswaComponent` → "Matematika", "Matsumoto uchiha" |
| J-2 | Dikelompokkan per hari (Senin, Selasa) | Render `jadwal-siswa-component.blade.php` |
| J-3 | Beranda & Jadwal konsisten (Matematika Senin) | Uji `Carbon::setTestNow('2026-11-02')` → Matematika di keduanya |
| J-4 | Sumber `jadwalkelas` + `jamhari`, bukan legacy | `BerandasiswaComponent.php` (query `JadwalKelas`, tanpa `Publikasijadwal`) |
| J-5 | Tabel responsif | `table-responsive` di `jadwal-siswa-component.blade.php` |
| J-6 | **Tidak menandai hari ini** | Tidak ada string "Hari ini"/"Sedang berlangsung" pada Beranda/Jadwal |
| J-7 | **Tidak ada navigasi pekan** | Tidak ada kontrol sebelum/sesudah minggu |
| J-8 | **Tidak ada cetak/unduh** | Tidak ada print/unduh pada Jadwal |

### 2.2 Pengumuman untuk siswa

| # | Temuan | Bukti |
|---|---|---|
| P-1 | Tidak ada komponen Livewire pengumuman | `app/Livewire/` tidak berisi file `Pengumuman*`/`Announcement*` |
| P-2 | Tidak ada tabel pengumuman | `Schema::hasTable('pengumuman') = false` |
| P-3 | Tidak ada referensi pengumuman di route/view | `grep pengumuman` → 0 hasil |

### 2.3 Informasi akademik siswa

| # | Temuan | Bukti |
|---|---|---|
| A-1 | Kelas & jurusan menampilkan `X MIPA 1` / `MIPA` | Render Profil & Beranda |
| A-2 | TA 2026/2027 & Semester Ganjil tampil di 3 halaman | Render Beranda, Profil, Jadwal |
| A-3 | Wali kelas menampilkan `Kanben Daissuke` (kelas 1) | `ProfilSiswaComponent` (view ringkasan) — sebelumnya tidak tampil; **2B.4.4 menambahkannya** |
| A-4 | Status penempatan hanya `aktif` | `Kelassiswa where status='aktif'` |
| A-5 | Tidak ada halaman daftar mapel khusus | Mapel hanya dalam jadwal |

### 2.4 Navigasi dan sidebar

| # | Temuan | Bukti |
|---|---|---|
| N-1 | 5 menu siswa: `berandasiswa`, `jadwalsiswa`, `absensi-siswa`, `profil-siswa`, `setting` | `usermenu` user 5 = `["berandasiswa","setting","absensi-siswa","jadwalsiswa","profil-siswa"]` |
| N-2 | Label menu aktual | `Beranda Siswa`, `Jadwal Pelajaran`, `Histori Absensi`, `Profil Saya`, `Pengaturan Akun` |
| N-3 | Sidebar dinamis dari `usermenu` | Render `assets.sidebar` untuk siswa |
| N-4 | Guru & superadmin tidak melihat menu siswa | Render sidebar guru (tanpa `href="/jadwalsiswa"`), superadmin (tanpa `href="/profil-siswa"`) |
| N-5 | Hanya satu "Profil Saya" di sidebar siswa | `substr_count(sidebar, 'Profil Saya') = 1` |
| N-6 | Tidak ada menu pengumuman |-sidebar tidak memuat pengumuman |

### 2.5 Responsivitas tampilan

| # | Temuan | Bukti |
|---|---|---|
| R-1 | Kolom tabel responsif | `table-responsive` di Jadwal, Beranda, Histori |
| R-2 | Grid kartu ringkasan | `col-6 col-md-3` (Profil) |
| R-3 | Grid field | `col-12 col-sm-6`, `col-12 col-lg-6` |
| R-4 | Meta viewport | `resources/views/layout/mainlayout.blade.php` |
| R-5 | Tidak ada inline width lebar | `width: 180px/150px` tidak ada di view Profil |
| R-6 | `word-break`, `flex-wrap`, `min-w-0` | Ada pada Profil |

**Pengujian browser/visual: Belum diuji** (tidak ada browser nyata).

### 2.6 Keamanan akses dan isolasi data siswa

| # | Temuan | Bukti |
|---|---|---|
| S-1 | Identitas selalu dari `auth()->user()->siswa` | `ProfilSiswaComponent.php` (`Auth::user()`), Beranda, Jadwal, Histori |
| S-2 | Tidak menerima ID siswa dari URL/input | `ProfilSiswaComponent` tidak memiliki public property; Histori hanya filter tanggal/status |
| S-3 | Guru/superadmin → 403 (komponen) | Render komponen untuk user guru/superadmin |
| S-4 | Route siswa `check.level:siswa` + `menu.access` | `routes/web.php` grup siswa |
| S-5 | Guard komponen konsisten | Semua komponen siswa memakai `abort_unless(level === 'siswa', 403)` |
| S-6 | Isolasi antar siswa | Siswa 1 tidak memuat Hagoromo, siswa 2 tidak memuat Yuji |
| S-7 | Read-only | Tidak ada create/save/update/delete pada komponen siswa |

---

## 3. Masalah Keamanan atau Konsistensi Jika Ditemukan

### 3.1 Keamanan: tidak ada temuan kritis

Semua area aman (lihat 2.6). Tidak ditemukan IDOR, kebocoran data, atau akses tanpa otorisasi pada halaman siswa.

### 3.2 Konsistensi informasi

| Temuan | Bukti | Dampak |
|---|---|---|
| **A-3 (sudah teratasi 2B.4.4)**: Profil tidak menampilkan wali kelas | Audit 2B.4.4 menemukan 2 butir gagal; implementasi 2B.4.4 menambah kartu "Wali Kelas" di Profil | Kini Profil & Beranda sama-sama menampilkan `Kanben Daissuke` |
| Tidak ada inkonsistensi lain yang ditemukan | Beranda/Jadwal/Profil/Histori konsisten untuk TA, semester, kelas, jurusan, mapel, guru | — |

---

## 4. Rekomendasi Perbaikan Berdasarkan Prioritas

### 4.1 Masalah wajib diperbaiki (P0/P1)

| Prioritas | Rekomendasi | Labs |
|---|---|---|
| P1 | Tampilkan penanda **"Hari ini"** pada kartu hari berjalan di Jadwal mingguan (badge atau border aksen) | Kemudahan membaca, tanpa migration |
| P2 | Tambahkan kosong pada kartu "Wali Kelas" di Profil apabila guru belum ditentukan (fallback "Belum ditentukan" sudah ada) | — |

### 4.2 Rekomendasi UX (P3)

| Rekomendasi | Labs |
|---|---|
| Navigasi pekan (sebelum/sesudah) atau lompat ke hari ini | State Livewire + query hari ini |
| Cetak/unduh jadwal (window.print / PDF) | Perlu desain cetak atau Dompdf |
| Ikon Pengumuman pada menu (jika ditambahkan) | Sesuai ikon menu yang sudah ada |

### 4.3 Fitur tambahan yang bisa ditunda (P4)

| Fitur | Keterangan |
|---|---|
| Pengumuman untuk siswa | Perlu tabel + komponen + seeder menu |
| Halaman daftar mapel per kelas | Perlu halaman baru |
| Kontak wali kelas (telepon/email) | Kolom sudah tersedia di `guru` |
| Rekap persentase kehadiran | Tergantung kebutuhan rapor |

### 4.4 Hal yang tidak perlu diubah

| Fitur | Alasan |
|---|---|
| Menu & route siswa (`check.level:siswa` + `menu.access`) | Sudah berlapis dan teruji |
| Query jadwal (`jadwalkelas`+`jamhari`) | Sudah benar, tidak memakai legacy |
| Identitas dari `auth()->user()->siswa` | Aman dan konsisten |
| `/setting` | Sudah diperjelas pada 2B.4.1/2B.4.2 |

---

## 5. Urutan Implementasi yang Disarankan

| Urutan | Rekomendasi | Labs |
|---|---|---|
| 1 | Penanda "Hari ini" pada Jadwal mingguan dan Beranda | Tanpa migration |
| 2 | Navigasi pekan (jika dibutuhkan) | State Livewire |
| 3 | Cetak/unduh jadwal | Desain cetak |
| 4 | Pengumuman (tabel + komponen + menu) | Perlu migration |
| 5 | Daftar mapel & kontak wali kelas (opsional) | Halaman baru |

---

## 6. Bukti Pengujian

```
=== T1: Jadwal Siswa ===
PASS | Konteks valid
INFO | konteks = {"ta":"2026/2027","semester":"Ganjil"}
PASS | Jadwal menampilkan Matematika
PASS | Jadwal menampilkan guru (Matsumoto uchiha)
PASS | Jadwal dikelompokkan per hari (Senin, Selasa)
PASS | Jadwal memakai table-responsive
PASS | Beranda menampilkan jadwal hari ini (Matematika Senin)
PASS | Jadwal & Beranda konsisten
FINDING-ACTIVE | T1-FIND: Jadwal tidak menandai hari ini
FINDING-ACTIVE | T1-FIND: Jadwal tidak memiliki navigasi pekan (sebelum/sesudah)
FINDING-ACTIVE | T1-FIND: Jadwal tidak memiliki cetak/unduh (print/window.print)
PASS | Tidak ada legacy jadwal pada komponen siswa
INFO | publikasijadwal rows = 0

=== T2: Pengumuman siswa ===
PASS | Tidak ada komponen pengumuman
PASS | Tidak ada tabel pengumuman
FINDING-ACTIVE | T2-FIND: Fitur pengumuman belum tersedia

=== T3: Informasi akademik ===
PASS | Profil menampilkan kelas X MIPA 1
PASS | Profil menampilkan jurusan MIPA
PASS | Profil menampilkan wali kelas (Kanben Daissuke)
PASS | Profil menampilkan TA 2026/2027 & Semester Ganjil
PASS | Beranda menampilkan TA & semester
PASS | Beranda menampilkan kelas & jurusan
FINDING-CLEAR | T3-FIND: Profil menampilkan wali kelas
INFO | kelas siswa 2 = X MIPA 1
INFO | jurusan siswa 2 = MIPA
INFO | wali kelas siswa 2 = Kanben Daissuke

=== T4: Navigasi & sidebar ===
PASS | Sidebar memuat berandasiswa (Beranda Siswa)
PASS | Sidebar memuat jadwalsiswa (Jadwal Pelajaran)
PASS | Sidebar memuat absensi-siswa (Histori Absensi)
PASS | Sidebar memuat profil-siswa (Profil Saya)
PASS | Sidebar memuat setting (Pengaturan Akun)
PASS | Hanya satu "Profil Saya"
FINDING-CLEAR | T4-FIND: Sidebar guru memuat menu siswa
PASS | Sidebar guru tidak memuat menu siswa
PASS | Sidebar superadmin memuat Dashboard
PASS | Sidebar superadmin tidak memuat menu khusus siswa

=== T5: Responsivitas (markup statis) ===
PASS | Jadwal memakai table-responsive
PASS | Profil memakai profil-stat-card & profil-field
PASS | Profil memakai grid col-6 col-md-3
PASS | Histori memakai nav-tabs (siswa 5)
PASS | Beranda memakai table-responsive
PASS | Tidak ada inline width kolom tabel yang lebar
PASS | Layout punya meta viewport

=== T6: Keamanan & isolasi ===
PASS | Profil tidak punya public property (read-only)
PASS | Histori tidak punya property idsiswa
PASS | Profil memakai auth()
PASS | Profil memakai guard level siswa
PASS | Route siswa memakai check.level:siswa + menu.access
PASS | Siswa 1 tidak memuat Hagoromo
PASS | Siswa 1 kelas XI MIPA 1

=== T7: Guard komponen ===
PASS | ProfilSiswaComponent guard level siswa
PASS | BerandasiswaComponent guard level siswa
PASS | JadwalSiswaComponent guard level siswa
PASS | HistoriAbsensiSiswaComponent guard level siswa

=== T8: Duplikasi & konsistensi ===
PASS | Menu "Profil Saya" hanya satu di appmenunew
PASS | Tidak ada duplikat penempatan

=== T9: Query N+1 ===
INFO | query render profil = 8
PASS | Query profil <= 8 (bukan per-record)
-- Transaksi di-rollback --
PASS | Rollback bersih (kelassiswa 2, publikasijadwal 0)

FINDING/FAIL aktif: 4
 - T1-FIND: Jadwal tidak menandai hari ini
 - T1-FIND: Jadwal tidak memiliki navigasi pekan (sebelum/sesudah)
 - T1-FIND: Jadwal tidak memiliki cetak/unduh (print/window.print)
 - T2-FIND: Fitur pengumuman belum tersedia
```

**TIDAK BISA DIUKUR**: pengujian browser/visual pada beragam ukuran layar (Belum diuji).

Menu aktual di database:

```
[{"appmenunew":"Beranda Siswa","url":"berandasiswa","icon":"fa-solid fa-house","urut":1},
 {"appmenunew":"Pengaturan Akun","url":"setting","icon":"fa-solid fa-gear","urut":3},
 {"appmenunew":"Jadwal Pelajaran","url":"jadwalsiswa","icon":"fa-solid fa-calendar-week","urut":10},
 {"appmenunew":"Histori Absensi","url":"absensi-siswa","icon":"fa-solid fa-clipboard-list","urut":11},
 {"appmenunew":"Profil Saya","url":"profil-siswa","icon":"fa-solid fa-id-card","urut":12}]
```

---

## 7. Kesimpulan Kesiapan Implementasi

**AMAN DENGAN CATATAN.**

Portal Siswa berada pada kondisi baik:
- Jadwal Pelajaran memiliki sumber data benar (`jadwalkelas`+`jamhari`, bukan legacy), tabel responsif, dan tampilan konsisten dengan Beranda.
- Informasi akademik konsisten di semua halaman (TA, semester, kelas, jurusan, wali kelas kini tampil di Beranda **dan** Profil).
- Navigasi/sidebar berlapis (`check.level:siswa` + `menu.access` + guard komponen) dan tidak ada duplikasi nama menu.
- Keamanan akses dan isolasi data siswa terverifikasi; tidak ada IDOR atau kebocoran.
- Profil memakai kartu ringkasan + grid field yang rapi; histori memakai dua tab dengan filter/ringkasan/pagination terpisah.

**Catatan**:
- Fitur **Pengumuman** belum tersedia (perlu tabel + komponen + menu bila diperlukan).
- Penanda "Hari ini", navigasi pekan, dan cetak/unduh jadwal belum tersedia (rekomendasi UX, bukan bug).
- Pengujian visual browser **Belum diuji** — perlu verifikasi manual pada 360 px, 768 px, dan desktop sebelum deployment.
- Hindari mengubah fitur yang sudah berjalan baik (route/menu, query jadwal, identitas dari `auth()->user()->siswa`, `/setting`).