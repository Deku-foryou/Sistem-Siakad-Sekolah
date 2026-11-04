# LAPORAN REDESIGN PROFIL SISWA + PERBAIKAN DUPLIKASI MENU

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Cakupan | Perbaikan nama menu (setting ↔ profil-siswa) dan redesign tampilan `/profil-siswa` |
| Tidak diubah | Business logic `ProfilSiswaComponent`, identitas dari `auth()->user()->siswa`, middleware/otorisasi, fitur `/setting`, fitur lain |

---

## 1. File yang Berubah / Dibuat

| File | Aksi | Ringkasan |
|---|---|---|
| `database/seeders/BerandaMenuSeeder.php` | **Diubah** | Label menu `setting` diubah `Profil Saya` → `Pengaturan Akun`, ikon `fa-id-card` → `fa-gear` |
| `database/seeders/MenuLabelSettingCleanupSeeder.php` | **Baru** | Seeder idempoten: update label & ikon menu `setting` yang sudah ada; url/urutan/kategori/role tidak diubah |
| `database/seeders/DatabaseSeeder.php` | **Diubah** | Tambahkan `MenuLabelSettingCleanupSeeder` setelah `ProfilSiswaMenuSeeder` |
| `resources/views/livewire/profil-siswa-component.blade.php` | **Diubah** | Redesign penuh: header profil, 4 kartu ringkasan, grid pribadi, grid kontak/keluarga, kartu akun |
| `resources/views/assets/sidebar.blade.php` | *(tidak diubah pada redesign)* | Menu dinamis dari `usermenu`; `profil-siswa` sudah ada di `$menuKhususRoleLain` |
| `routes/web.php` | *(tidak diubah pada redesign)* | Route `profil-siswa` tetap `check.level:siswa` + `menu.access` |

`git diff --stat` (file ter-track):

```
database/seeders/BerandaMenuSeeder.php   | 2 +-
database/seeders/DatabaseSeeder.php      | 2 ++
resources/views/assets/sidebar.blade.php | 2 +-
routes/web.php                           | 3 ++-
4 files changed, 6 insertions(+), 3 deletions(-)
```

Pint: `php vendor/bin/pint` pada file PHP yang berubah → **PASS, 3 files**. `php -l` → **No syntax errors detected**.

---

## 2. Ringkasan Desain

### 2.1 Perbaikan duplikasi menu

| Menu (url) | Sebelum | Sesudah | Ikon |
|---|---|---|---|
| `setting` | Profil Saya (`fa-id-card`) | **Pengaturan Akun** (`fa-gear`) | Gear (pengaturan) |
| `profil-siswa` | Profil Saya (`fa-id-card`) | **Profil Saya** (`fa-id-card`) | ID card (identitas) |

Sumber label diperbaiki tiga lapis agar konsisten:
1. `BerandaMenuSeeder` (definisi label untuk fresh install).
2. `MenuLabelSettingCleanupSeeder` (update idempoten untuk DB yang sudah ada).
3. Database aktif dijalankan sehingga `appmenunew.setting.appmenunew = "Pengaturan Akun"`.

### 2.2 Redesign halaman profil (struktur baru)

**A. Header profil** — kartu ringkas berisi foto/inisial 72 px, nama lengkap, NIS/NISN, dan badge status (`bg-success`/`bg-info`/`bg-secondary`/`bg-danger` sesuai status).

**B. Ringkasan akademik** — 4 kartu ringkasan (Tahun Ajaran, Semester, Kelas, Jurusan).

**C. Informasi pribadi** — grid field: NIK (tersamarkan), tempat lahir, tanggal lahir, jenis kelamin (Laki-laki/Perempuan), agama, tahun masuk, asal sekolah.

**D. Kontak & keluarga** — grid field terpisah: alamat, telepon siswa, email siswa, ayah (nama+telepon), ibu (nama+telepon), wali (nama+telepon).

**E. Informasi akun** — username, status password, dan tombol `Pengaturan Akun` menuju `/setting` (terpisah dari data pribadi).

**F. Empty state** — field kosong → "Belum diisi" abu-abu; foto kosong → avatar inisial; kelas tidak ada → "Belum ditempatkan di kelas aktif"; akun tanpa relasi → alert "belum terhubung ke data siswa".

### 2.3 Standar UI/UX yang diterapkan

| Standar | Implementasi |
|---|---|
| Bootstrap tersedia | `container-fluid`, `row g-3`, `col-*`, `d-flex flex-wrap` |
| Warna & font template | Putih (`#fff`), aksen `#0e7d6a` (sama dengan sidebar), aksen abu `#6b7280` |
| Border-radius | 12 px pada semua kartu (konsisten) |
| Tanpa tabel panjang | Seluruh `<table>` diganti grid `profil-field` |
| Spacing konsisten | `g-3`/`g-2`; kartu tidak memakai tinggi tetap (`height:100%` pada kartu dalam grid yang sama baris) |
| Tanpa gambar eksternal | Avatar inisial saat foto kosong |
| Mobile-first | Kartu ringkasan `col-6 col-md-3`; field `col-12 col-sm-6`; kolom besar `col-12 col-lg-6`; `word-break: break-word`, `min-w-0`, `flex-wrap` |

### 2.4 Yang tidak diubah (jaminan batasan)

- `ProfilSiswaComponent` tetap read-only, identitas dari `auth()->user()->siswa`, NIK tersamarkan, guard & middleware utuh.
- `/setting` tetap 200 dan alur wajib ganti password tetap berfungsi (teruji).
- Tidak ada edit/upload; tidak ada migration; tidak ada library baru.

---

## 3. Bukti Pengujian

Semua uji memakai transaksi + `rollBack()` di `finally`. Skrip telah dihapus setelah selesai.

### 3.1 Label menu & sidebar (60+ butir)

```
PASS | M1. /setting berlabel "Pengaturan Akun"
PASS | M2. /profil-siswa berlabel "Profil Saya"
PASS | M3. Ikon berbeda
PASS | M4. Duplikat nama "Profil Saya" untuk setting hilang
PASS | M5. URL tidak berubah
PASS | M6. Hanya satu menu bernama "Profil Saya"

PASS | SB1. Sidebar memuat "Profil Saya"
PASS | SB2. Sidebar memuat "Pengaturan Akun"
PASS | SB3. Sidebar href /profil-siswa ada
PASS | SB4. Sidebar href /setting ada
PASS | SB5. href /setting berada setelah label Pengaturan Akun
PASS | SB6. href /profil-siswa berada setelah label Profil Saya
PASS | SB7. Sidebar guru TIDAK memuat Profil Saya siswa
PASS | SB8. Sidebar guru TIDAK memuat Pengaturan Akun siswa
PASS | SB9. Sidebar superadmin TIDAK memuat profil-siswa
PASS | SB10. Sidebar superadmin tidak memuat duplikat menu siswa
PASS | SB11. Sidebar superadmin tetap memuat Dashboard

=== T3: Profil redesain (siswa 2) ===
PASS | Render memuat Profil Saya
PASS | Render memuat Hagoromo
PASS | Render memuat NISN
PASS | Render memuat 2026/2027
PASS | Render memuat Ganjil
PASS | Render memuat X MIPA 1
PASS | CSS kelas profil-summary ada
PASS | CSS kelas profil-stat-card ada
PASS | CSS kelas profil-field ada
PASS | CSS kelas profil-field-label ada
PASS | CSS kelas profil-field-value ada
PASS | CSS kelas profil-empty ada
PASS | Header profil berisi avatar
PASS | Tidak ada <table> di view profil baru (diganti field-card)
PASS | Tidak ada width: 180px/150px lebar kolom
PASS | Field NIK tampil
PASS | Field Tempat Lahir tampil
PASS | Field Tanggal Lahir tampil
PASS | Field Jenis Kelamin tampil
PASS | Field Agama tampil
PASS | Field Tahun Masuk tampil
PASS | Field Asal Sekolah tampil
PASS | Field Alamat tampil
PASS | Field Telepon Siswa tampil
PASS | Field Email Siswa tampil
PASS | Field Ayah tampil
PASS | Field Ibu tampil
PASS | Field Wali tampil
PASS | Field Username tampil
PASS | Field Status Password tampil
PASS | Tidak ada placeholder eksternal
PASS | NIK tersamarkan
PASS | NIK penuh tidak tampil (2098...)
PASS | Tombol Pengaturan Akun menuju /setting
PASS | Keterangan admin tampil
PASS | Tidak memuat data siswa lain (Yuji)
PASS | Belum diisi tampil untuk field kosong

=== T4: Siswa 1 (Yuji) ===
PASS | Yuji render
PASS | Tidak memuat Hagoromo

=== T5: Foto & fallback ===
PASS | Foto tampil dari /storage
PASS | File hilang -> fallback inisial (profil-avatar-fallback)

=== T6: Empty state ===
PASS | Empty state akun tanpa siswa

=== T7: Guard & otorisasi ===
PASS | Superadmin render -> 403
PASS | Guru render -> 403

=== T8: Read-only ===
PASS | Tidak ada method mutasi
PASS | Tidak ada public property

=== T9: HTTP ===
PASS | Siswa 5 GET /profil-siswa -> 200
PASS | Guru GET /profil-siswa -> 404
PASS | Superadmin GET /profil-siswa -> 404
PASS | Siswa wajib ganti password -> 302 /setting
PASS | GET /setting tetap 200 (pengaturan akun tidak rusak)
PASS | GET /setting siswa wajib ganti tetap 200

=== T10: Responsive markup (statis) ===
PASS | Grid col-6/col-12/col-md-3 untuk kartu ringkasan
PASS | Grid col-12 col-sm-6 untuk field
PASS | Grid col-12 col-lg-6 untuk dua kolom besar
PASS | Flex-wrap pada header & akun
PASS | word-break pada nilai field (tidak overflow)
PASS | min-w-0 pada header (tidak overflow)
-- Transaksi di-rollback --
Setelah rollback: {"setting_label":"Pengaturan Akun","profil_label":"Profil Saya","siswa_foto":null}
PASS | Rollback bersih (label menu tetap; foto null)

SEMUA UJI PASS
```

### 3.2 Data menu aktual (setelah seeder)

```
[{"appmenunew":"Pengaturan Akun","url":"setting","icon":"fa-solid fa-gear","urut":3},
 {"appmenunew":"Profil Saya","url":"profil-siswa","icon":"fa-solid fa-id-card","urut":12}]
```

---

## 4. Keamanan

| Aspek | Hasil |
|---|---|
| Identitas dari pemilik akun | Tetap aman (tidak diubah) |
| NIK tersamarkan | Tetap `***-****-****-1234`; nilai penuh tidak dirender |
| Read-only | Tidak ada method mutasi; tidak ada public property |
| Otorisasi | Route `check.level:siswa` + `menu.access` + guard komponen tidak diubah |
| Kebocoran antar siswa | Tidak ditemukan (Yuji ↔ Hagoromo) |

---

## 5. Keterbatasan dan Risiko

| # | Keterangan |
|---|---|
| 1 | **Pengujian browser/visual: Belum diuji.** Klaim responsif hanya berdasarkan markup statis (grid, `word-break`, `flex-wrap`, `min-w-0`). Perlu uji manual pada 360 px, 768 px, dan desktop. |
| 2 | `profil-stat-card`/`profil-field` memakai `height: 100%` dalam grid yang sama baris; di layar sangat pendek label panjang dapat melingkar — bukan ruang kosong berlebihan. |
| 3 | Sidebar dinamis bergantung pada `usermenu`; bila menu dicabut, label tidak muncul (perilaku benar). |
| 4 | Menu `setting` kini berlabel "Pengaturan Akun" untuk **semua peran** (guru juga memakai label yang sama, sesuai sumber `BerandaMenuSeeder`). |
| 5 | `git status` menunjukkan file `LAPORAN_*.md` dan seeder baru sebagai untracked (belum di-commit). |

**TIDAK BISA DIUKUR**: pengujian visual/browser pada 360 px, 768 px, dan desktop — tidak ada browser nyata yang tersedia untuk pengujian ini.

**Kesimpulan**: duplikasi label menu teratasi (satu "Profil Saya" milik `/profil-siswa`, "Pengaturan Akun" milik `/setting`), dan halaman profil ter-redesign menjadi kartu ringkasan + grid field yang bersih, tanpa mengubah business logic, otorisasi, atau fitur `/setting`.