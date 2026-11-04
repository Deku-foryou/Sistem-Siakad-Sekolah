# LAPORAN DESAIN PENGUMUMAN SISWA â€” TAHAP 2B.4.6A (REVISI)

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal audit | 4 November 2026 |
| Sifat | **Audit + desain saja.** Tidak ada kode, database, migration, seeder, atau konfigurasi yang diubah; tidak membaca `.env`; Git read-only |
| Metode | Pemeriksaan pola CRUD, menu/permission, relasi, notifikasi, migration/model/service/Livewire/Blade, transaksi/validasi/otorisasi + query skema & data read-only (transaksi + rollback) |
| Tujuan | Merancang sistem pengumuman sekolah yang aman, terstruktur, dan terintegrasi dengan Student Portal |
| **Revisi** | Seluruh keputusan berikut sudah final dan tidak lagi membutuhkan persetujuan terpisah. |

---

## 1. Kondisi Sistem Saat Ini

### 1.1 Pola CRUD superadmin yang sudah ada

| Komponen | Pola |
|---|---|
| `JurusanComponent` | `validate()` (unique), `WithPagination`, CRUD penuh; guard per method |
| `TahunajaranComponent` | `validate()` + `DB::transaction()` + `abort_unless(level === 'superadmin', 403)` di semua method publik |
| `SiswaComponent` | `validate()` + `DB::transaction()` + guard superadmin; `PaketMenuService::sinkronkanSemua('siswa')` |
| `KelassiswaComponent` | CRUD dasar (tanpa guard/validasi lengkap seperti Tahunajaran) |

Pola yang terbukti aman: **`validate()` â†’ `DB::transaction()` â†’ `abort_unless(level==='superadmin',403)` â†’ relasi & scope â†’ paginasi**.

### 1.2 Struktur menu dan permission

- `appmenunew` (menu) + `appmenucat` (kategori) + `role` (paket) + `usermenu` (hak per user) + `rolecat` (paket default).
- `PaketMenuService::sinkronkanSemua(string $level)` menyalin paket ke `usermenu` semua user pada level tersebut.
- Route dinamis memakai middleware `menu.access` (route name = `appmenunew.url`).
- Menu siswa saat ini: `berandasiswa`, `jadwalsiswa`, `absensi-siswa`, `profil-siswa`, `setting`.

### 1.3 Relasi yang ditemukan

| Relasi | Model | Jenis |
|---|---|---|
| `User::siswa()` | `User` | `hasOne(Siswa, user_iduser, id)` |
| `Siswa::user()` | `Siswa` | `belongsTo(User, user_iduser, id)` |
| `Siswa::kelassiswa()` | `Siswa` | `hasMany(Kelassiswa, siswa_idsiswa, idsiswa)` |
| `Kelas::jurusan()` | `Kelas` | `belongsTo(Jurusan, jurusan_id, idjurusan)` |
| `Kelas::wali()` | `Kelas` | `belongsTo(Guru, guru_idguru, idguru)` |
| `Kelas::kelassiswa()` | `Kelas` | `hasMany(Kelassiswa, kelas_idkelas, idkelas)` |
| `Kelassiswa::siswa()` | `Kelassiswa` | `belongsTo(Siswa, siswa_idsiswa, idsiswa)` |
| `Kelassiswa::kelas()` | `Kelassiswa` | `belongsTo(Kelas, kelas_idkelas, idkelas)` |

### 1.4 Sistem notifikasi yang sudah tersedia

| Item | Kondisi |
|---|---|
| `app/Notifications/` | **Tidak ada** |
| Tabel `notifications` (Laravel) | **Tidak ada** (`Schema::hasTable = false`) |
| `User` memakai `Notifiable` | **Ya** (trait) â€” tetapi tidak ada class notifikasi yang menggunakannya |

### 1.5 Pola migration, model, service, Livewire, dan Blade

| Pola | Contoh terbukti |
|---|---|
| Migration: `increments`/`bigIncrements`, enum, FK ON DELETE RESTRICT, unique composite | `2026_11_02_000001_create_absensi_harian_table.php`, `2026_11_07_000005_create_absensi_perjam_table.php` |
| Model: scopes + relasi + `$casts` | `AbsensiHarian` (`scopeForSiswa`, `scopeForSemester`, `scopeBetweenTanggal`) |
| Service: transaksi + validasi + audit (`diinput_oleh`/`diubah_oleh`) | `AbsensiHarianService`, `SiswaService` (`DB::transaction`) |
| Livewire: guard `abort_unless`, `validate`, `WithPagination`, `resetPage` | `TahunajaranComponent`, `HistoriAbsensiSiswaComponent` |
| Blade: `container-fluid`, `card`, `table-responsive`, `detail-card`, badge status | `histori-absensi-siswa-component.blade.php` |

---

## 2. ERD Relasi Tabel

```
â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
â”‚            pengumuman        â”‚
â”œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¤
â”‚ idpengumuman (PK)            â”‚
â”‚ judul                        â”‚
â”‚ isi                          â”‚
â”‚ kategori                     â”‚
â”‚ prioritas                    â”‚
â”‚ status (draf/terbit/arsip)   â”‚
â”‚ waktu_terbit                 â”‚
â”‚ berlaku_dari                 â”‚
â”‚ berlaku_sampai               â”‚
â”‚ target (semua/jurusan/kelas/ â”‚
â”‚         siswa)               â”‚
â”‚ dibuat_oleh (FK users)       â”‚
â”‚ diperbarui_oleh (FK users)   â”‚
â””â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”˜
   â”‚           â”‚           â”‚
   â”‚           â”‚           â”‚
   â”‚           â”‚           â”‚
   â–¼           â–¼           â–¼
â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â” â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â” â”Œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”
â”‚ pengumuman_targetâ”‚ â”‚ pengumuman_baca  â”‚ â”‚ (siswa pembaca)  â”‚
â”œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¤ â”œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¤ â”œâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”¤
â”‚ idpengumuman_    â”‚ â”‚ idpengumuman_    â”‚ â”‚ siswa            â”‚
â”‚   target (PK)    â”‚ â”‚   baca (PK)      â”‚ â”‚                  â”‚
â”‚ pengumuman_id... â”‚ â”‚ pengumuman_id... â”‚ â”‚ idsiswa (PK)     â”‚
â”‚ jurusan_idjurus..â”‚ â”‚ siswa_idsiswa    â”‚ â”‚                  â”‚
â”‚ kelas_idkelas    â”‚ â”‚ dibaca_pada      â”‚ â”‚ user_iduser (FK) â”‚
â”‚ siswa_idsiswa    â”‚ â”‚                  â”‚ â”‚                  â”‚
â””â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”´â””â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”˜ â””â”€â”€â”€â”€â”€â”€â”¬â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”˜
     â”‚      â”‚            â”‚      â”‚                â”‚
     â–¼      â–¼            â–¼      â–¼                â–¼
  jurusan  kelas      siswa   siswa           users
```

Relasi:
- `pengumuman` 1 â”€â”€< `pengumuman_target` (target penerima)
- `pengumuman` 1 â”€â”€< `pengumuman_baca` (snapshot + status baca)
- `jurusan` 1 â”€â”€< `pengumuman_target`
- `kelas` 1 â”€â”€< `pengumuman_target`
- `siswa` 1 â”€â”€< `pengumuman_target`, 1 â”€â”€< `pengumuman_baca`
- `siswa` 1 â”€â”€< `kelassiswa` >â”€â”€ `kelas` >â”€â”€ `jurusan`

---

## 3. Struktur Final Setiap Tabel

### 3.1 Tabel `pengumuman`

| Kolom | Tipe (pola proyek) | Keterangan |
|---|---|---|
| `idpengumuman` | unsignedInteger increments (PK) | Sama seperti `idsiswa`/`idkelas`/`idjurusan` |
| `judul` | string(255) | Wajib, tidak null |
| `isi` | text | Wajib, tidak null |
| `kategori` | enum('akademik','acara','penting','darurat') | Default 'akademik' |
| `prioritas` | enum('rendah','sedang','tinggi','mendesak') | Default 'sedang' |
| `status` | enum('draf','terbit','arsip') | Default 'draf' |
| `waktu_terbit` | timestamp nullable | Diisi saat publishing |
| `berlaku_dari` | date nullable | Periode berlaku |
| `berlaku_sampai` | date nullable | Periode berlaku |
| `target` | enum('semua','jurusan','kelas','siswa') | Default 'semua' |
| `dibuat_oleh` | unsignedBigInteger (FK `users.id`) | Audit pembuat |
| `diperbarui_oleh` | unsignedBigInteger nullable (FK `users.id`) | Audit pengubah |
| `created_at`, `updated_at` | timestamp | |

### 3.2 Tabel `pengumuman_target`

| Kolom | Tipe (pola proyek) | Keterangan |
|---|---|---|
| `idpengumuman_target` | unsignedInteger increments (PK) | Sama seperti PK lain |
| `pengumuman_idpengumuman` | unsignedInteger (FK `pengumuman.idpengumuman`) | |
| `jurusan_idjurusan` | unsignedInteger nullable (FK `jurusan.idjurusan`) | Target jurusan |
| `kelas_idkelas` | unsignedInteger nullable (FK `kelas.idkelas`) | Target kelas |
| `siswa_idsiswa` | unsignedInteger nullable (FK `siswa.idsiswa`) | Target spesifik |
| `created_at`, `updated_at` | timestamp | |

**Constraint** (tidak bergantung kolom nullable):
- UNIQUE (`pengumuman_idpengumuman`, `jurusan_idjurusan`)
- UNIQUE (`pengumuman_idpengumuman`, `kelas_idkelas`)
- UNIQUE (`pengumuman_idpengumuman`, `siswa_idsiswa`)
- Validasi aplikasi: **tepat satu** dari ketiga kolom target terisi sesuai `pengumuman.target`.

### 3.3 Tabel `pengumuman_baca`

| Kolom | Tipe (pola proyek) | Keterangan |
|---|---|---|
| `idpengumuman_baca` | bigint increments unsigned (PK) | Pola `idabsensi_harian` |
| `pengumuman_idpengumuman` | unsignedInteger (FK `pengumuman.idpengumuman`) | |
| `siswa_idsiswa` | unsignedInteger (FK `siswa.idsiswa`) | |
| `dibaca_pada` | timestamp nullable | null = belum dibaca |
| `created_at`, `updated_at` | timestamp | |

UNIQUE (`pengumuman_idpengumuman`, `siswa_idsiswa`).

### 3.4 Tipe FK (sama persis dengan PK tujuan)

| FK | PK tujuan | Tipe |
|---|---|---|
| `pengumuman.dibuat_oleh` | `users.id` | unsignedBigInteger |
| `pengumuman_target.pengumuman_idpengumuman` | `pengumuman.idpengumuman` | unsignedInteger increments |
| `pengumuman_target.jurusan_idjurusan` | `jurusan.idjurusan` | unsignedInteger increments |
| `pengumuman_target.kelas_idkelas` | `kelas.idkelas` | unsignedInteger increments |
| `pengumuman_target.siswa_idsiswa` | `siswa.idsiswa` | unsignedInteger increments |
| `pengumuman_baca.pengumuman_idpengumuman` | `pengumuman.idpengumuman` | unsignedInteger increments |
| `pengumuman_baca.siswa_idsiswa` | `siswa.idsiswa` | unsignedInteger increments |

FK dengan `ON DELETE RESTRICT` (pola `absensi_harian`).

---

## 4. Keputusan Final (Tidak Lagi Membutuhkan Persetujuan)

| # | Keputusan | Rationale |
|---|---|---|
| 1 | **Snapshot penerima wajib dibuat saat publikasi** | Tidak lazy; siswa yang tidak membuka detail tetap memiliki baris baca |
| 2 | `pengumuman_baca` 1 baris/siswa/pengumuman; `dibaca_pada` nullable; **hapus `jumlah_baca`** | Status baca cukup dari `dibaca_pada` |
| 3 | UNIQUE terpisah (3 constraint); tepat satu target terisi | Hindari ketergantungan kombinasi nullable |
| 4 | Status hanya `draf â†’ terbit â†’ arsip`; terbit tidak dapat diedit/dikembalikan ke draf; arsip tidak dapat diterbitkan ulang; revisi = pengumuman baru | Histori konsisten |
| 5 | Kedaluwarsa tidak muncul di daftar aktif; histori baca tetap | Periode berlaku ditegakkan query siswa |
| 6 | **Notifikasi Laravel, email, badge ditunda**; tabel `notifications` tidak dibuat pada tahap inti | Fokus inti pengumuman |
| 7 | Tipe FK sama persis dengan PK tujuan | Lihat 3.4 |
| 8 | Validasi `berlaku_sampai >= berlaku_dari` jika keduanya diisi | Mencegah periode terbalik |
| 9 | Snapshot memakai `CONTOH-DARI-TABEL-BERPINDAT` (lihat bagian 6) | Definisi eksplisit per jenis target |
| 10 | Siswa pindah kelas tetap memiliki akses ke pengumuman yang sudah masuk snapshot | Snapshot saat publikasi; bukan query dinamis kelassiswa |
| 11 | **Kolom status siswa & nilai aktif** | `siswa.status` enum('aktif','lulus','pindah','keluar'), nilai aktif = `'aktif'` (terverifikasi, lihat Lampiran B) |
| 12 | **Snapshot bebas duplikat `idsiswa`** | Query memakai `distinct()` sebelum insert |
| 13 | **Alur baca tanpa `firstOrCreate()`** | Cari snapshot; tidak ditemukan â†’ 404; null â†’ isi `now()`; terisi â†’ jangan ubah |
| 14 | **Histori vs daftar aktif** | Arsip/kedaluwarsa tidak di daftar aktif, penerima snapshot tetap bisa membuka melalui histori |
| 15 | **Publikasi atomik & tanpa penerima ditolak** | Validasi, lockForUpdate/status draf, target, snapshot unik, perubahan status dalam satu transaksi; penerima kosong â†’ throw |
| 16 | Typo `jurusan/catatan` diperbaiki | `jurusan/kelas/siswa` |

---

## 5. Aturan Target Penerima

| Target | Artinya | Snapshot saat publikasi | Query daftar siswa |
|---|---|---|---|
| `semua` | Semua siswa aktif | Seluruh `siswa status='aktif'` | Baris baca ada |
| `jurusan` | Siswa di jurusan X | `kelassiswa` aktif â†’ `kelas.jurusan_id = X` | Baris baca ada |
| `kelas` | Siswa di kelas Y | `kelassiswa` aktif dengan `kelas_idkelas = Y` | Baris baca ada |
| `siswa` | Penerima spesifik | `siswa_idsiswa` satu per baris target | Baris baca ada |

**Aturan**:
1. `target='semua'` tidak memerlukan baris `pengumuman_target`.
2. `target='jurusan'/kelas/siswa'` memerlukan â‰¥1 baris target yang sesuai.
3. Siswa menerima pengumuman bila: baris `pengumuman_baca` ada **dan** status `terbit` **dan** `waktu_terbit <= now()` **dan** (`berlaku_dari IS NULL` atau `berlaku_dari <= tanggal`) **dan** (`berlaku_sampai IS NULL` atau `tanggal <= berlaku_sampai`).
4. Siswa pindah kelas setelah publikasi **tetap** menerima pengumuman yang sudah masuk snapshotnya (tidak tergantung kelassiswa saat ini).

---

## 6. Alur Transaksi Publikasi dan Snapshot Penerima

### 6.1 Terbitkan (superadmin) â€” atomik

```
DB::beginTransaction();
  validasi: judul, isi, target valid, tepat satu target terisi (bila bukan semua),
            berlaku_sampai >= berlaku_dari (bila keduanya diisi)
  kunci baris pengumuman: lockForUpdate()
  pastikan status saat ini = 'draf' (tolak bila bukan draf)
  hapus pengumuman_target lama (bila ada) dan tulis ulang sesuai target yang dipilih
  tentukan daftar idsiswa penerima (blok 6.2) dengan distinct()
  bila daftar penerima kosong -> throw (Publikasi tanpa penerima ditolak)
  insert pengumuman_baca untuk setiap idsiswa penerima (dibaca_pada = NULL)
  update pengumuman: status = 'terbit', waktu_terbit = now(), diperbarui_oleh = user
DB::commit();
```

### 6.2 Snapshot penerima (5 cabang â€” tanpa duplikat)

| Cabang | Query penerima (unik) |
|---|---|
| `target='semua'` | `Siswa::where('status','aktif')->distinct()->pluck('idsiswa')` |
| `target='jurusan'` | `Kelassiswa::where('status','aktif')->whereHas('kelas', jurusan_id = X)->distinct()->pluck('siswa_idsiswa')` |
| `target='kelas'` | `Kelassiswa::where('status','aktif')->where('kelas_idkelas', Y)->distinct()->pluck('siswa_idsiswa')` |
| `target='siswa'` | Daftar `siswa_idsiswa` dari baris `pengumuman_target` (sudah unik per UNIQUE) |
| Lainya | Gagal validasi (throw) |

Semua cabang memakai `distinct()` agar tidak ada duplikat `idsiswa` sebelum insert snapshot.

### 6.3 Arsipkan

```
DB::beginTransaction();
  update pengumuman: status = 'arsip', diperbarui_oleh = user
DB::commit();
```

Pengumuman arsip tidak muncul di daftar aktif siswa; `pengumuman_baca` tetap ada.

### 6.4 Pengumuman kedaluwarsa

Query siswa mengecek `berlaku_dari`/`berlaku_sampai`; yang kedaluwarsa tidak muncul di daftar aktif.
Histori `pengumuman_baca` tidak dihapus dan dapat ditampilkan di histori siswa yang pernah membacanya.

### 6.5 Histori vs daftar aktif

| Kondisi | Daftar aktif | Histori (detail via ID) |
|---|---|---|
| `terbit` + dalam periode + snapshot ada | Tampil | Tampil |
| `terbit` + kedaluwarsa + snapshot ada | **Tidak tampil** | **Tampil** (penerima snapshot tetap dapat membuka) |
| `arsip` + snapshot ada | **Tidak tampil** | **Tampil** (penerima snapshot tetap dapat membuka) |
| Tidak pernah menjadi penerima (snapshot tidak ada) | Tidak tampil | **404** |
| `draf` | Tidak tampil | Tidak tampil untuk siswa |

Akses detail histori dikontrol **secara eksplisit** oleh keberadaan baris `pengumuman_baca` untuk siswa login dan pengumuman yang sebelumnya pernah terbit â€” pengumuman yang tidak pernah ditujukan tetap 404 tanpa membuka akses ke pengumuman lain.

### 6.6 Alur pembacaan idempoten (tanpa `firstOrCreate`)

```
Siswa buka detail pengumuman:
  cari baris snapshot: pengumuman_baca where pengumuman_idpengumuman = id
                                       AND siswa_idsiswa = $siswaLogin
  bila tidak ditemukan -> 404
  bila ditemukan dan dibaca_pada IS NULL:
     update dibaca_pada = now()
  bila ditemukan dan dibaca_pada sudah terisi:
     jangan ubah (idempoten; tidak ada jumlah_baca)

tampilkan detail (judul, isi, kategori, prioritas, waktu terbit)
```

Snapshot dibuat **saat publikasi**; `firstOrCreate()` tidak digunakan.

### 6.5 Histori vs daftar aktif

| Kondisi | Daftar aktif | Histori (detail via ID) |
|---|---|---|
| `terbit` + dalam periode + snapshot ada | Tampil | Tampil |
| `terbit` + kedaluwarsa + snapshot ada | **Tidak tampil** | **Tampil** (penerima snapshot tetap dapat membuka) |
| `arsip` + snapshot ada | **Tidak tampil** | **Tampil** (penerima snapshot tetap dapat membuka) |
| Tidak pernah menjadi penerima (snapshot tidak ada) | Tidak tampil | **404** |
| `draf` | Tidak tampil | Tidak tampil untuk siswa |

Akses detail histori dikontrol **secara eksplisit** oleh keberadaan baris `pengumuman_baca` untuk siswa login dan pengumuman yang sebelumnya pernah terbit â€” pengumuman yang tidak pernah ditujukan tetap 404 tanpa membuka akses ke pengumuman lain.

### 6.6 Alur pembacaan idempoten (tanpa `firstOrCreate`)

```
Siswa buka detail pengumuman:
  cari baris snapshot: pengumuman_baca where pengumuman_idpengumuman = id
                                       AND siswa_idsiswa = $siswaLogin
  bila tidak ditemukan -> 404
  bila ditemukan dan dibaca_pada IS NULL:
     update dibaca_pada = now()
  bila ditemukan dan dibaca_pada sudah terisi:
     jangan ubah (idempoten; tidak ada jumlah_baca)

tampilkan detail (judul, isi, kategori, prioritas, waktu terbit)
```

Snapshot dibuat **saat publikasi**; `firstOrCreate()` tidak digunakan.

---

## 7. Alur Pembacaan Idempoten

```
Siswa buka detail pengumuman:
  cari baris snapshot: pengumuman_baca where pengumuman_idpengumuman = id
                                       AND siswa_idsiswa = $siswaLogin
  bila tidak ditemukan -> 404
  bila ditemukan dan dibaca_pada IS NULL:
     update dibaca_pada = now()
  bila ditemukan dan dibaca_pada sudah terisi:
     jangan ubah (idempoten; tidak ada jumlah_baca)
tampilkan detail (judul, isi, kategori, prioritas, waktu terbit)
```

---

## 8. Strategi Pencegahan Duplikasi Penerima

| Mekanisme | Detail |
|---|---|
| UNIQUE terpisah pada `pengumuman_target` | `(pengumuman, jurusan)`, `(pengumuman, kelas)`, `(pengumuman, siswa)` â€” satu target per jenis per pengumuman |
| UNIQUE pada `pengumuman_baca` | `(pengumuman, siswa)` â€” satu baris baca per siswa per pengumuman |
| Validasi tepat satu target terisi | Aplikasi menolak target yang tidak konsisten dengan `pengumuman.target` |
| `distinct()` snapshot | Query penerima memakai `distinct()` sebelum insert |
| Transaksi | Seluruh publikasi dalam `DB::transaction()` sehingga tidak ada snapshot parsial |

---

## 9. Pembagian Hak Akses Superadmin dan Siswa

| Hak | Superadmin | Siswa |
|---|---|---|
| Buat/ubah (draf)/terbitkan/arsipkan pengumuman | âœ” | âœ˜ |
| Kelola target penerima | âœ” | âœ˜ |
| Lihat semua pengumuman (termasuk draf) | âœ” | âœ˜ |
| Lihat pengumuman terbit yang ditujukan kepadanya (snapshot ada) | â€” | âœ” |
| Lihat detail & tandai sudah dibaca | â€” | âœ” |
| Lihat pengumuman tidak ditujukan kepadanya | â€” | âœ˜ (404) |

Guard: komponen superadmin `abort_unless(level === 'superadmin', 403)`; komponen siswa `abort_unless(level === 'siswa', 403)`.

---

## 10. Rancangan Route, Livewire Component, Service, dan Menu

### 10.1 Route

| Route | Komponen | Middleware |
|---|---|---|
| `GET /pengumuman` (superadmin) | `PengumumanComponent` | `check.level:superadmin` |
| `GET /pengumuman-siswa` (siswa daftar) | `PengumumanSiswaComponent` | `check.level:siswa` + `menu.access` |
| `GET /pengumuman-siswa/{id}` (siswa detail) | `PengumumanSiswaComponent` (mode detail) | `check.level:siswa` + `menu.access` |

Tambahkan `pengumuman-siswa` ke `$explicitUrls`.

### 10.2 Livewire component

| Komponen | Peran |
|---|---|
| `PengumumanComponent` (superadmin) | CRUD + publikasi + arsip + kelola target (status hanya drafâ†’terbitâ†’arsip); `validate()`, `DB::transaction()` |
| `PengumumanSiswaComponent` (siswa) | Daftar + detail + baca idempoten; identitas dari `auth()->user()->siswa` |

### 10.3 Service

| Service | Peran |
|---|---|
| `PengumumanService` (baru) | Validasi target & periode, terbitkan + snapshot, arsipkan, query siswa (baris baca + periode) |

### 10.4 Menu

| Menu | Url | Paket |
|---|---|---|
| Pengumuman (superadmin) | `pengumuman` | Superadmin (via seeder) |
| Pengumuman (siswa) | `pengumuman-siswa` | Siswa (via seeder idempoten) |

### 10.5 Notifikasi (ditunda)

Notifikasi Laravel, email, dan badge **ditunda**; tabel `notifications` tidak dibuat pada tahap inti. Badge belum dibaca dapat dihitung dari `pengumuman_baca.dibaca_pada IS NULL` tanpa mengirim notifikasi berulang.

---

## 11. Strategi Pengujian Rollback dan Isolasi Siswa

| Uji | Skenario | Ekspektasi |
|---|---|---|
| 1 | Superadmin buat draf â†’ terbitkan â†’ arsip | Status berurutan; tidak dapat kembali ke draf |
| 2 | Arsip diterbitkan lagi | Ditolak (harus buat baru) |
| 3 | Terbit tidak dapat diedit | Ditolak |
| 4 | Snapshot: publikasi target jurusan | Hanya siswa jurusan tersebut menerima |
| 5 | Snapshot: publikasi target kelas | Hanya siswa kelas tersebut menerima |
| 6 | Snapshot: publikasi target siswa spesifik | Hanya penerima spesifik menerima |
| 7 | Snapshot: target semua | Semua siswa aktif menerima |
| 8 | Siswa pindah kelas setelah publikasi | Tetap menerima pengumuman yang sudah masuk snapshot |
| 9 | Siswa buka detail pengumuman | `dibaca_pada` terisi; buka lagi tidak berubah |
| 10 | IDOR: siswa A buka pengumuman siswa B | 404 |
| 11 | Pengumuman draf tidak tampil untuk siswa | Tidak muncul di daftar/detail |
| 12 | Pengumuman kedaluwarsa tidak tampil di daftar aktif | Tidak muncul; histori baca tetap |
| 13 | Guru/superadmin ke halaman siswa | 404 (middleware) |
| 14 | `berlaku_sampai < berlaku_dari` | Ditolak validasi |
| 15 | Duplikat target ditolak | UNIQUE + validasi tepat satu target |
| 16 | Query count render (N+1) | Konstanta |

**Strategi rollback**:
- Semua pengujian memakai `DB::beginTransaction()` + `rollBack()` di `finally`.
- Skrip uji sementara di `storage/app/tmp-uji/`, dihapus setelah selesai.
- Publikaci dan pembacaan memakai transaksi agar tidak ada snapshot parsial.

**Isolasi siswa**:
- `siswa_idsiswa` selalu dari `auth()->user()->siswa`.
- Detail siswa memvalidasi baris `pengumuman_baca` ada; bila tidak ada â†’ 404.
- Pengumuman draf/arsip tidak dapat diakses siswa secara langsung.

---

## 12. Urutan Implementasi Setiap Subtahap

| Subtahap | Cakupan | Uji |
|---|---|---|
| 1 | Migration `pengumuman`, `pengumuman_target`, `pengumuman_baca` + model + scopes | `php artisan migrate --pretend`; schema sesuai desain |
| 2 | `PengumumanService` (terbitkan + snapshot, arsipkan, query siswa) | Unit service |
| 3 | `PengumumanComponent` (superadmin CRUD + publikasi + target) + seeder menu | CRUD + guard superadmin |
| 4 | `PengumumanSiswaComponent` (daftar + detail + baca idempoten) + seeder menu siswa | Isolasi + IDOR |
| 5 | Badge belum dibaca (query) tanpa notifikasi berulang | Badge jumlah benar |

---

## 13. Risiko dan Keputusan yang Masih Membutuhkan Persetujuan

| # | Keputusan / risiko | Status |
|---|---|---|
| 1 | Eager snapshot vs lazily | **Final: eager saat publikasi** |
| 2 | Siswa pindah kelas setelah terbit | **Final: tetap menerima dari snapshot** |
| 3 | Pengumuman kedaluwarsa: tampil atau tidak | **Final: tidak tampil di aktif; histori tetap** |
| 4 | Notifikasi mail | **Final: ditunda** |
| 5 | Hapus pengumuman: dilarang | **Final: gunakan arsip** |
| 6 | Kategori & prioritas: daftar tetap | **Final: enum tetap** |
| 7 | Target jurusan memakai `kelas.jurusan_id` | **Final** |
| 8 | Duplikat target | **Final: UNIQUE terpisah + validasi** |
| 9 | Notifikasi berulang | **Final: ditunda; `notifications` tidak dibuat** |

Semua keputusan pada revisi ini **final** dan tidak lagi membutuhkan persetujuan terpisah.

---

## Lampiran A â€” Bukti Audit

```
=== T1: Pola CRUD superadmin yang sudah ada ===
INFO | JurusanComponent = {"validate":true,"transaction":false,"superadmin-guard":false,"pagination":true}
INFO | TahunajaranComponent = {"validate":true,"transaction":true,"superadmin-guard":true,"pagination":true}
INFO | KelassiswaComponent = {"validate":false,"transaction":false,"superadmin-guard":false,"pagination":true}
INFO | SiswaComponent = {"validate":true,"transaction":true,"superadmin-guard":true,"pagination":true}
PASS | Pola guard superadmin pada semua method publik

=== T2: Pola menu & permission ===
INFO | jumlah menu appmenunew = 18
INFO | usermenu siswa 5 = 5
PASS | Route siswa memakai check.level:siswa + menu.access
PASS | explicitUrls membuat berandasiswa
PASS | PaketMenuService tersedia

=== T3: Relasi siswa, kelas, jurusan, guru, tahun ajaran ===
PASS | Kelas::jurusan() ada
PASS | Kelas::wali() ada
PASS | Siswa::kelassiswa() ada
INFO | jurusan count = 3
INFO | guru aktif count = 3
INFO | kelas count = 3
INFO | siswa count = 2
INFO | kelas 1 jurusan_id = 1
PASS | Kelas punya jurusan_id

=== T4: Notifikasi ===
PASS | Tidak ada tabel notifications (belum dipakai)
PASS | Tidak ada app/Notifications
PASS | User memakai Notifiable trait

=== T5: Migration / model / service / Livewire / Blade pattern ===
PASS | Migration memakai format: increments/bigIncrements + foreign ON DELETE restrict
PASS | Model punya scopes
PASS | Service memakai DB::transaction
PASS | Komponen memakai WithPagination
PASS | Blade memakai card + table-responsive

=== T6: Konteks akademik ===
PASS | Konteks valid
INFO | konteks = ["2026/2027","Ganjil"]

=== T7: Guard & IDOR pattern ===
PASS | Identitas dari Auth::user()
PASS | Tidak ada public property idsiswa
PASS | Query difilter siswa login
PASS | Profil juga dari Auth::user()
-- Transaksi di-rollback --
PASS | Rollback bersih (appmenunew >= 15)
```

**TIDAK BISA DIUKUR**: pengujian browser/visual pada beragam ukuran layar (Belum diuji).

**Kesimpulan revisi**: desain pengumuman diperbarui sesuai 10 keputusan final â€” snapshot eager saat publikasi, satu baris baca per siswa, tiga UNIQUE terpisah, status hanya maju, kedaluwarsa hanya di daftar aktif, notifikasi ditunda, tipe FK sama persis, validasi periode, snapshot eksplisit per target, dan siswa pindah kelas tetap menerima. Semua keputusan final; implementasi belum dimulai.
