# LAPORAN AUDIT PROFIL SISWA — TAHAP 2B.4.2

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal audit | 4 November 2026 |
| Sifat | Audit saja. Tidak ada file aplikasi yang dibuat atau diubah; tidak ada migration; tidak membaca `.env`; tidak ada operasi Git tulis |
| Metode | Pembacaan model/migration/kode + query skema & data read-only + render Beranda + request HTTP nyata, semua dalam transaksi + rollback |
| Cakupan | Model `Siswa`, relasi `User–Siswa`, skema tabel, ketersediaan identitas, `/setting`, fitur unggah foto, relasi kelas/jurusan/wali/TA, keamanan & IDOR, komponen yang bisa digunakan kembali |

---

## 1. Struktur Database dan Relasi

### 1.1 Tabel `siswa` (26 kolom)

| Kolom | Tipe | Null | Constraint | Sumber |
|---|---|---|---|---|
| `idsiswa` | int unsigned AI | NO | PK | `2026_10_26_000006` |
| `user_iduser` | bigint unsigned | NO | UNIQUE + FK → `users.id` (RESTRICT) | `2026_10_26_000006` |
| `nisn` | varchar(10) | NO | UNIQUE | `2026_10_26_000006` |
| `nis` | varchar(20) | YES | — | `2026_10_26_000006` |
| `nik` | varchar(20) | YES | UNIQUE | `2026_10_29_000001` |
| `nama` | varchar(255) | NO | — | `2026_10_26_000006` |
| `jenis_kelamin` | enum('L','P') | NO | — | `2026_10_26_000006` |
| `tempat_lahir` | varchar(255) | YES | — | `2026_10_26_000006` |
| `tanggal_lahir` | date | NO | — | `2026_10_26_000006` |
| `agama` | varchar(255) | YES | — | `2026_10_26_000006` |
| `alamat` | text | YES | — | `2026_10_26_000006` |
| `telepon_siswa` | varchar(20) | YES | — | `2026_10_29_000001` |
| `email` | varchar(255) | YES | — | `2026_10_29_000001` |
| `nama_ayah` | varchar(255) | YES | — | `2026_10_26_000006` |
| `telepon_ayah` | varchar(20) | YES | — | `2026_10_29_000001` |
| `nama_ibu` | varchar(255) | YES | — | `2026_10_26_000006` |
| `telepon_ibu` | varchar(20) | YES | — | `2026_10_29_000001` |
| `nama_wali` | varchar(255) | YES | — | `2026_10_29_000001` |
| `telepon_wali` | varchar(20) | YES | — | `2026_10_29_000001` |
| `telepon_ortu` | varchar(255) | YES | — | `2026_10_26_000006` |
| `tahun_masuk` | year | YES | — | `2026_10_26_000006` |
| `asal_sekolah` | varchar(255) | YES | — | `2026_10_29_000001` |
| `foto` | varchar(255) | YES | — | `2026_10_26_000006` |
| `status` | enum('aktif','lulus','pindah','keluar') | NO | default 'aktif' | `2026_10_26_000006` |
| `created_at`, `updated_at` | timestamp | YES | — | bawaan |

Kolom wajib hanya 4 (`nisn`, `nama`, `jenis_kelamin`, `tanggal_lahir`); 20 kolom identitas lainnya nullable sehingga tampilan profil **wajib** menangani data kosong.

### 1.2 Relasi

| Relasi | Model | Jenis | Gre |
|---|---|---|---|
| `User::siswa()` | `User` | `hasOne(Siswa::class, 'user_iduser', 'id')` | `User.php:72-75` |
| `Siswa::user()` | `Siswa` | `belongsTo(User::class, 'user_iduser', 'id')` | `Siswa.php:56-59` |
| `Siswa::kelassiswa()` | `Siswa` | `hasMany(Kelassiswa::class, 'siswa_idsiswa', 'idsiswa')` | `Siswa.php:61-64` |
| `Kelas::jurusan()` | `Kelas` | `belongsTo(Jurusan::class, 'jurusan_id', 'idjurusan')` | `Kelas.php` |
| `Kelas::wali()` | `Kelas` | `belongsTo(Guru::class, 'guru_idguru', 'idguru')` | `Kelas.php` |
| `Kelas::kelassiswa()` | `Kelas` | `hasMany(Kelassiswa::class, ...)` | `Kelas.php` |

`user_iduser` UNIQUE menjamin **satu user ↔ satu siswa**, sehingga `$user->siswa()->first()` deterministik.

### 1.3 Penempatan & konteks akademik

- `kelassiswa` punya `status` enum('aktif','selesai','pindah') default 'aktif' (`2026_11_01_000001`).
- Penempatan aktif untuk tahun ajaran aktif diambil seperti pada Beranda:
  `Kelassiswa where siswa_idsiswa AND status='aktif' with kelas.jurusan, kelas.wali where kelas.tahunajaran_idtahunajaran = TA aktif`.
- Konteks TA/semester aktif dari `KonteksAkademikService::ambil()`.
- Data aktual: siswa 1 → kelas 2 (`XI MIPA 1`), siswa 2 → kelas 1 (`X MIPA 1`); jurusan kelas 1 = `MIPA`.

---

## 2. Matriks Ketersediaan Kolom Identitas Siswa

`✔` = ada di skema + `$fillable` model. Terisi = data aktual (2 siswa, dalam transaksi).

| # | Field | Ada | Terisi (data aktual) |
|---|---|---|---|
| 1 | NIS | ✔ | 2/2 |
| 2 | NISN | ✔ | 2/2 |
| 3 | NIK | ✔ | 1/2 |
| 4 | Nama lengkap | ✔ | 2/2 |
| 5 | Tempat lahir | ✔ | 1/2 |
| 6 | Tanggal lahir | ✔ | 2/2 |
| 7 | Jenis kelamin | ✔ | 2/2 |
| 8 | Agama | ✔ | 2/2 |
| 9 | Alamat | ✔ | 0/2 |
| 10 | Telepon siswa | ✔ | 0/2 |
| 11 | Email siswa | ✔ | 0/2 |
| 12 | Asal sekolah | ✔ | 1/2 |
| 13 | Nama ayah | ✔ | 0/2 |
| 14 | Telepon ayah | ✔ | 0/2 |
| 15 | Nama ibu | ✔ | 0/2 |
| 16 | Telepon ibu | ✔ | 0/2 |
| 17 | Nama wali | ✔ | 0/2 |
| 18 | Telepon wali | ✔ | 0/2 |
| 19 | Telepon ortu (legacy) | ✔ | 1/2 |
| 20 | Tahun masuk | ✔ | 2/2 |
| 21 | Foto | ✔ | 0/2 |

**Tidak ada kolom yang belum tersedia.** Semua yang diminta tugas audit sudah ada di skema dan `$fillable` model `Siswa`. Keterbatasan utama adalah **kelengkapan data** (data induk belum diisi) dan **tampilan belum menampilkan**.

---

## 3. Kondisi Halaman Profil Saat Ini

### 3.1 `/berandasiswa` (sudah ada, ringkas)
Menampilkan: avatar inisial, nama, NISN, NIS, TA + semester, kelas, jurusan, wali kelas, tahun masuk, badge status, username, status ganti password. **Tidak** menampilkan: NIK, tempat/tanggal lahir, alamat, telepon, email, asal sekolah, data ortu, foto.

Beranda hanya `select` 7 kolom (`idsiswa, user_iduser, nisn, nis, nama, tahun_masuk, status`), jadi kolom identitas tidak dimuat.

### 3.2 `/setting` (akun saja)
`AuthController@index` hanya mengirim `$userFind` (model `User`). View hanya form akun:

| Bagian | Isi |
|---|---|
| Alert | `wajib_ganti_password` (sudah diperjelas pada 2B.4.1) |
| Kiri | Placeholder foto eksternal (gambar dari `startbootstrap`) |
| Kanan | Username (readonly), Nama, Email, Password Lama, Password Baru, Konfirmasi, Simpan |

**Tidak ada** data siswa, kelas, kontak, atau foto siswa yang ditampilkan.

### 3.3 Fitur unggah foto

| Aspek | Kondisi |
|---|---|
| Kolom `siswa.foto` | Ada, nullable, varchar(255) — menyimpan **path**, bukan BLOB |
| Input admin | `SiswaComponent` punya `public $foto` dan menyimpan path ke DB |
| Disk | `public` → `storage/app/public` (`config/filesystems.php`) dengan `visibility=public` |
| Livewire upload | `temporary_file_upload.rules = null` → default `['required','file','max:12288']` (12 MB), disk implisit |
| Sisi siswa | **Tidak ada fitur unggah foto untuk siswa**; halaman siswa tidak menampilkan foto siswa |
| Akses file eksternal | `/setting` menampilkan gambar eksternal (placeholder); **bukan** file siswa |

---

## 4. Audit Keamanan dan IDOR

| Aspek | Hasil | Bukti |
|---|---|---|
| Identitas dari pemilik akun | **Aman** | `AuthController::index` memakai `auth()->user()->id`; `BerandasiswaComponent` memakai `Auth::user()` |
| `idsiswa` tidak diterima dari URL/input | **Aman** | Tidak ada route/properti yang menerima `idsiswa`; `GET /setting?idsiswa=1` tetap menampilkan `Hagoromo` |
| Siswa hanya melihat profil sendiri | **Aman** | `siswa` selalu `$user->siswa()` (relasi `hasOne`, UNIQUE FK) |
| Profil read-only | **Hanya untuk identitas siswa** | `AuthController::update` hanya mengubah `users` (name/email/password) dengan penjaga `abort_unless(auth()->id() === $id, 403)` |
| Guard komponen | **Ada** | `BerandasiswaComponent` `abort_unless(level === 'siswa', 403)` |
| Guard route & menu | **Ada** | Grup siswa memakai `check.level:siswa` + `menu.access` (FIX 2B.4.1) |
| Kebocoran data siswa lain | **Tidak ditemukan** | Audit sebelumnya: Beranda/Jadwal/Histori tidak memuat nama siswa lain |

**Risiko sisa**: tidak ada kolom yang diproteksi dari penulisan siswa karena form profil siswa memang belum ada. Jika kelak ditambahkan, penulisan harus tetap dilarang atau diizinkan hanya untuk field tertentu.

---

## 5. Fitur yang Bisa Digunakan Kembali

| Fitur | Keterangan |
|---|---|
| Relasi `User::siswa()` + FK `user_iduser` UNIQUE | Deterministik, IDOR-safe secara struktural |
| `Kelassiswa` + `KonteksAkademikService` + `kelas.jurusan` + `kelas.wali` | Sudah dipakai Beranda; bisa di-reuse tanpa query baru |
| Pola avatar inisial (Beranda) | Fallback bila foto kosong |
| Kolom `siswa.foto` + disk `public` + Livewire upload config | Mekanisme unggah sudah terskonfigurasi untuk admin; dipakai kembali untuk menampilkan foto |
| Guard `abort_unless(level === 'siswa', 403)` | Pertahankan di komponen profil baru |
| `menu.access` untuk route siswa | Sudah menutup akses langsung tanpa menu |
| Alert `wajib_ganti_password` di `/setting` | Komponen sudah ada; perlu ditempatkan bersama profil |
| Pola `table-responsive`, `detail-card`, `detail-table`, badge status | Konsistensi UI sudah terjalin |

---

## 6. Kekurangan dan Risiko

| # | Kekurangan | Dampak |
|---|---|---|
| 1 | Profil siswa tidak tampil di halaman mana pun | Siswa tidak bisa memverifikasi identitas/kontak |
| 2 | Data induk tidak lengkap (alamat, telepon, email, ortu 0/2) | Profil akan menampilkan banyak "Belum diisi" — perlu penanganan khusus |
| 3 | Foto siswa tidak ada (0/2) dan tidak ada jalur unggah untuk siswa | Harus ada fallback inisial + penjelasan |
| 4 | `/setting` memakai placeholder foto eksternal | Tidak sesuai branding, membingungkan, mengandalkan jaringan eksternal |
| 5 | `foto` menyimpan path tanpa struktur direktori yang jelas | Potensi konflik penamaan bila unggah dimassalkan |
| 6 | Kolom `telepon_ortu` (legacy) dan `telepon_ayah/ibu/wali` (baru) bersamaan | Duplikasi sumber kebenaran kontak orang tua |
| 7 | Tidak ada indeks khusus untuk query profil siswa | Query kecil (satu baris), dampak rendah |
| 8 | Read-only untuk data induk | Siswa harus menghubungi admin untuk perubahan — perlu penjelasan di UI |

---

## 7. Rekomendasi Desain UI Profil Siswa

### 7.1 Struktur halaman (usulan)

```
Profil Saya                         [Badge: Siswa]
┌──────────────────────────────┬────────────────────────┐
│ [Foto / Inisial]  Nama       │ Status Akun            │
│ NISN · NIS                   │ Username               │
│ Kelas · Jurusan · Wali       │ Status ganti password  │
│ Tahun Ajaran · Semester      │ [Ganti password]       │
└──────────────────────────────┴────────────────────────┘

Identitas
  NIK | Tempat Lahir | Tanggal Lahir | Jenis Kelamin | Agama | Asal Sekolah

Kontak & Orang Tua
  Alamat | Telepon Siswa | Email Siswa
  Ayah (nama · telepon) | Ibu (nama · telepon) | Wali (nama · telepon)

Akun (tetap seperti sekarang)
  Username | Nama | Email | Password
```

### 7.2 Aturan tampilan

1. **Sumber data**: `$user->siswa()->first()` + `Kelassiswa` aktif + `KonteksAkademikService`. Tanpa parameter URL/input.
2. **Read-only** untuk seluruh data siswa; teks pendamping: "Hubungi admin sekolah untuk perubahan data."
3. **Field kosong**: tampilkan "Belum diisi" (bukan "-"), dengan warna muted.
4. **Foto**: gunakan `Storage::disk('public')->url($siswa->foto)` jika ada; jika kosong tampilkan inisial (pola Beranda). Hapus gambar placeholder eksternal.
5. **Bagian Akun** tetap seperti `setting.blade.php` (form + alert `wajib_ganti_password`); tidak mengubah alur `AuthController::update`.
6. **Layout**: `container-fluid`, kartu `detail-card`, tabel `table-responsive`, grid `col-12 col-lg-4` untuk akun/status — konsisten dengan Beranda.
7. **Mobil**: satu kolom vertikal; label singkat; tombol aksi besar.

### 7.3 Catatan keamanan

- Identitas **hanya** dari `auth()->user()->siswa`; tidak menerima `idsiswa` dari URL/input.
- Route profil harus memakai `check.level:siswa` + `menu.access` seperti route siswa bagusnya.
- Guard komponen `abort_unless(level === 'siswa', 403)`.
- Tidak ada operasi tulis pada data siswa di tahap ini.

---

## 8. Rekomendasi Tahapan Implementasi Tanpa Migration

### Tahap 1 — Profil read-only (tanpa migration)
- Perluas `AuthController@index` (atau komponen Livewire baru) untuk mengirim `$siswa` + `$konteks` + `$kelasList`.
- Tampilkan seluruh kolom identitas, kontak, dan relasi kelas/jurusan/wali.
- Tambahkan penanganan "Belum diisi" dan foto/inisial fallback.
- Daftarkan menu `profil-siswa` melalui seeder (jika halaman terpisah) atau tampilkan di `/setting`.
- Uji IDOR & read-only.

### Tahap 2 — Unggah foto (tanpa migration)
- Tambahkan unggah foto khusus siswa (disk `public`, validasi `mimes:jpg,png,jpg` + `max:2048`), simpan path ke `siswa.foto`, hapus file lama bila diganti.
- Perluasan route/storage: tidak perlu migration karena kolom sudah ada.

### Tahap 3 — Pengajuan perubahan data (opsional, fase lanjut)
- Alur request-change (tabel baru) atau integrasi ke modul admin; bukan pembaruan langsung oleh siswa.

> Semua tahap di atas **tidak memerlukan migration**: seluruh kolom yang dibutuhkan sudah tersedia di skema.

---

## 9. Daftar Pengujian yang Diperlukan

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 1 | F | Profil siswa 2 (Hagoromo, kelas 1) | NISN, NIS, kelas X MIPA 1, jurusan MIPA, wali, TA 2026/2027, Semester Ganjil tampil |
| 2 | F | Profil siswa 1 (Yuji itadori, kelas 2) | Kelas **XI MIPA 1** (bukan X MIPA 1) |
| 3 | F | Field kosong (alamat, telepon, ortu) | "Belum diisi" tanpa error |
| 4 | F | Foto kosong | Avatar inisial tampil, bukan placeholder eksternal |
| 5 | F | `GET /setting?idsiswa=1` | Tetap profil pemilik akun (Hagoromo) |
| 6 | F | `GET /setting?siswa_idsiswa=1` | Tetap profil pemilik akun |
| 7 | H | Guru/superadmin `GET /setting` | Tetap dapat membuka (akun), tetapi data siswa tidak ditampilkan |
| 8 | H | Siswa tanpa data siswa | Pesan "Akun belum terhubung ke data siswa" |
| 9 | H | Siswa `wajib_ganti_password` membuka profil | Tetap bisa melihat profil, tetapi alur ganti password tidak berubah |
| 10 | M | Profil pada 360 px | Kartu bertumpuk, label tidak terpotong |
| 11 | M | Profil pada 768 px & 1280 px | Grid rapi, tabel tidak overflow |

---

## Lampiran A — Bukti Pengujian

```
=== 1. STRUKTUR TABEL siswa (schema) ===
PASS | Tabel memiliki 26 kolom
INFO | kolom siswa = ["idsiswa","user_iduser","nisn","nis","nik","nama","jenis_kelamin","tempat_lahir","tanggal_lahir","agama","alamat","telepon_siswa","email","nama_ayah","telepon_ayah","nama_ibu","telepon_ibu","nama_wali","telepon_wali","telepon_ortu","tahun_masuk","asal_sekolah","foto","status","created_at","updated_at"]

=== 2. RELASI User-Siswa ===
PASS | Siswa::user belongsTo users
INFO | contoh user_iduser = {"idsiswa":2,"user_iduser":5}

=== 3. MATRIKS KETERSEDIAAN KOLOM IDENTITAS (data aktual 2 siswa) ===
INFO | siswa.alamat: 0/2 terisi
INFO | siswa.telepon_siswa: 0/2 terisi
INFO | siswa.email: 0/2 terisi
INFO | siswa.nama_ayah: 0/2 terisi
INFO | siswa.telepon_ayah: 0/2 terisi
INFO | siswa.nama_ibu: 0/2 terisi
INFO | siswa.telepon_ibu: 0/2 terisi
INFO | siswa.nama_wali: 0/2 terisi
INFO | siswa.telepon_wali: 0/2 terisi
INFO | siswa.nik: 1/2 terisi
INFO | siswa.tempat_lahir: 1/2 terisi
INFO | siswa.asal_sekolah: 1/2 terisi
INFO | siswa.nis: 2/2 terisi
INFO | siswa.nisn: 2/2 terisi
INFO | siswa.tahun_masuk: 2/2 terisi
INFO | siswa.status: 2/2 terisi

=== 4. DATA AKTUAL: relasi kelas aktif ===
INFO | konteks = {"ta":"2026/2027","semester":"Ganjil"}
PASS | Konteks valid
INFO | siswa 1 (Yuji itadori) penempatan aktif = [2]
INFO | siswa 2 (Hagoromo) penempatan aktif = [1]
PASS | Relasi Kelas->jurusan
INFO | jurusan kelas 1 = MIPA
PASS | Relasi Kelas->wali

=== 5. Beranda: apakah profil lengkap tampil? ===
PASS | Beranda menampilkan label NISN
PASS | Beranda menampilkan label NIS
PASS | Beranda menampilkan label Kelas
PASS | Beranda menampilkan label Jurusan
PASS | Beranda menampilkan label Wali Kelas
PASS | Beranda menampilkan label Tahun Ajaran
PASS | Beranda menampilkan label Semester
PASS | Beranda menampilkan label Tahun Masuk
PASS | Beranda menampilkan label Status
PASS | Beranda menampilkan nama siswa
PASS | Beranda tidak menampilkan NIK
PASS | Beranda tidak menampilkan tempat/tanggal lahir
PASS | Beranda tidak menampilkan alamat
PASS | Beranda tidak menampilkan telepon/ortu
PASS | Beranda menampilkan avatar inisial (bukan foto)
PASS | Beranda tidak menampilkan foto siswa
PASS | Beranda hanya select 7 kolom siswa

=== 6. /setting (AuthController) ===
PASS | AuthController::index memakai auth()->user()->id
PASS | AuthController::index memakai User::findOrFail
PASS | View setting tidak menyebut Siswa model
PASS | View setting tidak menampilkan NISN/NIK/kelas
PASS | View setting memakai placeholder foto eksternal
PASS | View setting menampilkan pesan wajib ganti password
INFO | GET /setting siswa 5 = {"status":200,"has_nisn":false,"has_nik":false,"name":"Hagoromo"}
PASS | GET /setting -> 200
PASS | GET /setting tidak memuat NISN (profil siswa tidak ada di sini)

=== 7. Keamanan & IDOR ===
PASS | AuthController::update punya penjaga IDOR (auth()->id)
PASS | Beranda selalu dari auth()
PASS | Tidak ada route profil/{idsiswa} selain profil/{id} untuk user
PASS | Tidak ada route siswa/{id} untuk profil
INFO | GET /setting?idsiswa=1 siswa 5 = {"name":"Hagoromo","has_nisn":false,"has_nik":false}
PASS | GET /setting?idsiswa=1 tetap menampilkan pemilik akun (Hagoromo)

=== 8. Fitur unggah foto ===
PASS | SiswaComponent punya property foto
PASS | SiswaComponent menyimpan path foto ke DB
INFO | disk public root = C:\project-reyhan\template web\storage\app/public
INFO | livewire upload rules = null
PASS | Livewire temporary_file_upload terkonfigurasi (disk implisit)
PASS | Tidak ada halaman siswa yang menampilkan foto siswa

=== 9. Read-only? ===
PASS | BerandasiswaComponent tidak punya method mutasi
PASS | AuthController::update hanya mengubah akun (bukan tabel siswa)

=== 10. Data kosong & risiko ===
INFO | siswa 1 field kosong = ["alamat","telepon_siswa","email","nama_ayah","telepon_ayah","nama_ibu","telepon_ibu","nama_wali","telepon_wali","foto"]
INFO | siswa 2 field kosong = ["nik","alamat","telepon_siswa","nama_ayah","telepon_ayah","nama_ibu","telepon_ibu","nama_wali","telepon_wali","asal_sekolah","foto"]
PASS | Risiko: 2 dari 2 siswa tidak memiliki foto
-- Transaksi di-rollback --
PASS | Rollback bersih (siswa 2, usermenu tidak berubah)

SEMUA UJI PASS
```

**TIDAK BISA DIUKUR**: pengujian browser/visual pada beragam ukuran layar (Belum diuji).

**Kesimpulan**: skema `siswa` lengkap (26 kolom, semua identitas tersedia); fondasi relasi dan keamanan sudah sangat baik; tinggal menampilkan read-only dari `auth()->user()->siswa` + konteks akademik + foto/inisial tanpa perlu migration apa pun.