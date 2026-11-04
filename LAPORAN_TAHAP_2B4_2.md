# LAPORAN IMPLEMENTASI TAHAP 2B.4.2 — PROFIL SISWA READ-ONLY

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Dasar | `LAPORAN_AUDIT_PROFIL_SISWA_2B4_2.md` |
| Cakupan | Halaman profil siswa read-only (`/profil-siswa`) |
| Tidak dikerjakan | Edit profil, unggah foto, pengajuan perubahan data, absensi, fitur guru/admin |

---

## 1. File yang Berubah / Dibuat

| File | Aksi | Ringkasan |
|---|---|---|
| `app/Livewire/ProfilSiswaComponent.php` | **Baru** | Identitas dari `auth()->user()->siswa`; penempatan aktif + konteks akademik; foto dari disk `public` (atau inisial); tanpa method mutasi |
| `resources/views/livewire/profil-siswa-component.blade.php` | **Baru** | Identitas, kontak & orang tua, informasi akademik, akun; NIK tersamarkan; "Belum diisi"; tanpa placeholder eksternal |
| `routes/web.php` | **Diubah** | Tambahkan `profil-siswa` ke `$explicitUrls` dan route `GET profil-siswa` dalam grup `check.level:siswa` + `menu.access` |
| `database/seeders/ProfilSiswaMenuSeeder.php` | **Baru** | Kategori `Jadwal & Absensi`, menu `profil-siswa` (Profil Saya, urut 12), role paket Siswa, `sinkronkanSemua('siswa')` (idempoten) |
| `database/seeders/DatabaseSeeder.php` | **Diubah** | Tambahkan `ProfilSiswaMenuSeeder` setelah `JadwalSiswaMenuSeeder` |
| `resources/views/assets/sidebar.blade.php` | **Diubah** | Tambahkan `profil-siswa` ke `$menuKhususRoleLain` (superadmin tidak melihatnya) |
| `storage/app/tmp-uji/` | Dibersihkan | Skrip milik agen ini dihapus; tersisa file yang sudah ada |

`git status --short`:

```
 M database/seeders/DatabaseSeeder.php
 M resources/views/assets/sidebar.blade.php
 M routes/web.php
?? LAPORAN_AUDIT_PROFIL_SISWA_2B4_2.md   (audit sebelumnya)
?? app/Livewire/ProfilSiswaComponent.php
?? database/seeders/ProfilSiswaMenuSeeder.php
?? resources/views/livewire/profil-siswa-component.blade.php
```

Pint: `php vendor/bin/pint` pada file PHP yang berubah → **PASS, 4 files**. `php -l` → **No syntax errors detected**.

---

## 2. Ringkasan Implementasi

### 2.1 Komponen (`ProfilSiswaComponent`)

- Guard: `abort_unless(level === 'siswa', 403)` (pertahanan berlapis).
- Identitas: `$user->siswa()->first()` — selalu milik akun login, tidak pernah dari parameter URL/input.
- Konteks: `KonteksAkademikService::ambil()` (TA & semester aktif).
- Kelas: `Kelassiswa` `status='aktif'` + `kelas.tahunajaran_idtahunajaran = TA aktif`, eager-load `kelas.jurusan`, `kelas.wali`.
- Foto: `Storage::disk('public')->exists($siswa->foto)` → `url()`; bila kosong/file hilang → avatar inisial (pola Beranda). **Tanpa** gambar placeholder eksternal.
- **Tidak ada method mutasi**: tidak ada `update/delete/save/upload` dan **tidak ada public property** yang dapat ditulis siswa (verifikasi refleksi).
- NIK ditampilkan `***-****-****-1234` (4 digit terakhir) atau "Belum diisi"; nilai penuh tidak dirender.

### 2.2 View (`profil-siswa-component.blade.php`)

- Identitas: NISN, NIS, NIK tersamarkan, nama, tempat/tanggal lahir, jenis kelamin (Laki-laki/Perempuan), agama.
- Kontak & orang tua: alamat, telepon, email, asal sekolah, nama/telepon ayah, ibu, wali.
- Informasi akademik: TA, semester, kelas, jurusan, wali kelas, tahun masuk.
- Akun: username, status ganti password, tombol ganti password.
- Empty state: konteks tidak valid / akun belum terhubung ke data siswa; field kosong → "Belum diisi".
- Keterangan baca-saja: "Jika ada data yang belum benar atau belum terisi, silakan hubungi admin sekolah untuk pengajuan perubahan."
- Semua tabel dalam `table-responsive`; ikon dekoratif `aria-hidden="true"`.

### 2.3 Route & menu

- Route: `GET profil-siswa` → `ProfilSiswaComponent`, middleware `check.level:siswa` **+** `menu.access`.
- `$explicitUrls` ditambah `profil-siswa` agar loop dinamis tidak menduplikasi route.
- Menu `profil-siswa` (Profil Saya, urut 12, kategori `Jadwal & Absensi`) via `ProfilSiswaMenuSeeder` (idempoten). Role hanya paket Siswa; `sinkronkanSemua('siswa')` hanya menambah `usermenu` user `level=siswa`.
- Sidebar: menu tampil dinamis dari `usermenu`; `profil-siswa` ditambahkan ke `$menuKhususRoleLain` agar superadmin tidak melihatnya (DB superadmin sudah memiliki seluruh menu).

### 2.4 Prasyarat data

Seeder dijalankan (persist) pada database aktif. Verifikasi:

```
usermenu siswa 5: ["berandasiswa","setting","absensi-siswa","jadwalsiswa","profil-siswa"]
usermenu guru 2: ["berandaguru","setting","absensi-harian"]
```

---

## 3. Bukti Pengujian

Semua uji memakai transaksi + `rollBack()` di `finally`. Skrip telah dihapus setelah selesai.

### 3.1 Uji komponen (60+ butir)

```
=== T1: Seeder idempoten (ProfilSiswaMenuSeeder) ===
PASS | S1. Menu profil-siswa terdaftar
PASS | S2. Hanya 1 menu profil-siswa (idempoten)
PASS | S3. Paket Siswa berisi profil-siswa
PASS | S4. Paket Siswa lama utuh
PASS | S5. Paket Guru TIDAK berisi profil-siswa
PASS | S6. User siswa 5 mendapatkan profil-siswa
PASS | S7. User guru 2 TIDAK mendapatkan profil-siswa

=== T2: Siswa 2 (Hagoromo, kelas 1) ===
PASS | P1. Render OK
PASS | P2. Nama tampil (Hagoromo)
PASS | P3. NISN tampil
PASS | P4. NIS tampil
PASS | P5. NIK tersamarkan (***-****-****-1234 atau Belum diisi)
PASS | P6. NIK penuh TIDAK tampil
PASS | P7. Tempat Lahir tampil (label)
PASS | P8. Tanggal Lahir tampil (label)
PASS | P9. Jenis Kelamin tampil (label)
PASS | P10. Agama tampil (label)
PASS | P11. Alamat tampil (label + Belum diisi)
PASS | P12. Telepon tampil (label)
PASS | P13. Email tampil (label)
PASS | P14. Asal Sekolah tampil (label)
PASS | P15. Nama Ayah tampil (label + Belum diisi)
PASS | P16. Nama Ibu tampil
PASS | P17. Nama Wali tampil
PASS | P18. Kelas X MIPA 1 tampil
PASS | P19. Jurusan MIPA tampil
PASS | P20. Wali kelas tampil (label)
PASS | P21. Tahun Ajaran 2026/2027 tampil
PASS | P22. Semester Ganjil tampil
PASS | P23. Tahun masuk tampil
PASS | P24. Foto kosong -> avatar inisial (bukan gambar eksternal)
PASS | P25. Keterangan admin tampil
PASS | P26. Tidak memuat data siswa lain (Yuji itadori)
PASS | P27. Siswa di view data
PASS | P28. Komponen tidak punya public property idsiswa

=== T3: Siswa 1 (Yuji, kelas 2) ===
PASS | Q1. Render OK
PASS | Q2. Kelas XI MIPA 1 tampil
PASS | Q3. Tidak memuat data siswa lain (Hagoromo)
PASS | Q4. Tahun masuk tampil

=== T4: Foto tersedia (file fiktif dalam transaksi) ===
PASS | F1. Foto tampil dari disk public
PASS | F2. Avatar inisial tidak tampil saat foto ada

=== T5: Foto tidak ditemukan ===
PASS | F3. File hilang -> avatar inisial tampil
PASS | F4. Tidak ada gambar eksternal

=== T6: Siswa tanpa relasi data siswa ===
PASS | N1. Empty state tampil

=== T7: Guard komponen ===
PASS | G1. Superadmin render -> 403
PASS | G2. Guru render -> 403

=== T8: Read-only ===
PASS | Tidak ada method update/delete/save/upload
PASS | Tidak ada public property yang dapat ditulis siswa

=== T9: HTTP (menu terdaftar) ===
PASS | Siswa 5 GET /profil-siswa -> 200

=== T10: HTTP tanpa menu (akses langsung ditolak) ===
PASS | Siswa 5 tanpa usermenu profil-siswa -> 404

=== T11: Peran lain (tetap 404 oleh CheckUserLevel) ===
PASS | Guru 2 GET /profil-siswa -> 404
PASS | Superadmin 1 GET /profil-siswa -> 404

=== T12: Siswa wajib ganti password tetap berfungsi ===
PASS | Siswa 4 GET /profil-siswa -> 302 /setting
PASS | Siswa 4 GET /setting -> 200

=== T13: Sidebar dinamis ===
PASS | Sidebar siswa memuat /profil-siswa
PASS | Sidebar guru TIDAK memuat /profil-siswa
PASS | Sidebar superadmin TIDAK memuat /profil-siswa (tersembunyi khusus peran)
PASS | Sidebar superadmin tetap memuat Dashboard

=== T14: Query tidak N+1 (verifikasi agregat) ===
INFO | jumlah query render = 9
PASS | Query render <= 10 (tidak N+1, konstanta bukan per-record)
-- Transaksi di-rollback --
Setelah rollback: {"appmenunew_profil":1,"siswa_foto":null,"usermenu_siswa5":5}
PASS | Rollback bersih (menu profil 1; foto siswa 2 null; usermenu siswa 5 = 5)

SEMUA UJI PASS
```

### 3.2 Uji HTTP nyata (satu request = satu proses)

```
{"user":5,"uri":"/profil-siswa?idsiswa=1&siswa_idsiswa=1","status":200}   <- query string diabaikan
{"user":1,"uri":"/profil-siswa","status":404}                              <- superadmin
{"user":2,"uri":"/profil-siswa","status":404}                              <- guru
```

Route (setelah `route:clear`, `route:list -v`):

```
GET|HEAD  profil-siswa ...
          ⇂ web
          ⇂ App\Http\Middleware\Authenticate
          ⇂ App\Http\Middleware\CekAkunAktif
          ⇂ App\Http\Middleware\ForcePasswordChange
          ⇂ App\Http\Middleware\CheckUserLevel:siswa
          ⇂ App\Http\Middleware\MenuAccessMiddleware
```

---

## 4. Keamanan

| Aspek | Hasil |
|---|---|
| Identitas dari `auth()->user()->siswa` | **Aman** — selalu pemilik akun |
| `idsiswa` dari URL/query/input | **Tidak diterima** — komponen tidak punya public property; `?idsiswa=1` tidak mengubah identitas |
| Kebocoran data siswa lain | **Tidak ditemukan** — siswa 2 tidak memuat `Yuji itadori`, sebaliknya |
| Read-only | **Aman** — tidak ada method mutasi; tidak ada public property yang dapat ditulis |
| NIK | **Tersamarkan** — hanya 4 digit terakhir; nilai penuh tidak dirender |
| Otorisasi route/menu | **Berlapis** — `check.level:siswa` + `menu.access` + guard komponen |
| Pencabutan menu | **Tertutup** — `usermenu` dicabut → akses langsung 404 |

---

## 5. Risiko yang Harus Dipahami

1. **Data induk belum lengkap** (alamat/telepon/email/ortu 0/2) → profil menampilkan banyak "Belum diisi". Perlu perbaikan data induk oleh admin.
2. **Foto siswa belum ada** (0/2) → avatar inisial dipakai; bila foto harus selalu ada, perlu pengisian data.
3. **Siswa baru tanpa `usermenu`** tidak dapat membuka `/profil-siswa` (404) hingga menu diberikan via `terapkanDefault`. Ini perilaku benar.
4. **Route `profil-siswa` adalah route eksplisit baru**; `menu.access` memerlukan nama route = `url` menu (sudah memenuhi).
5. **`$menuKhususRoleLain` perlu diperbarui** setiap ditambah menu khusus peran agar superadmin tidak melihatnya.
6. **Pengujian browser/visual belum dilakukan** — klaim responsif hanya dari struktur view.

**TIDAK BISA DIUKUR**: pengujian browser/visual pada beragam ukuran layar (Belum diuji).

**Kesimpulan**: halaman profil siswa read-only kini tersedia di `/profil-siswa`, lengkap dengan identitas, kontak, orang tua, dan informasi akademik; aman dari IDOR, NIK tersamarkan, dan menu/route terdaftar resmi. Tahap berikutnya (edit, unggah foto, pengajuan perubahan) tidak dikerjakan sesuai batasan.