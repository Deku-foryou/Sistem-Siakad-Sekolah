# Laporan Tahap 1 - Fondasi SIAKAD SMA

## 1. Ringkasan
Tahap 1 (fondasi) selesai: migrasi aditif, model tahun ajaran/semester, login username + throttle, wajib ganti password, penyesuaian Users, CRUD tahun ajaran/semester, seeder menu akademik. Tidak ada `migrate:fresh`/wipe; hanya migration aditif.

## 2. Git
Project bukan repository git (`git status` gagal: not a git repository). Branch `feature/fondasi-siakad` tidak dapat dibuat; pekerjaan dilakukan langsung di working directory.

## 3. File dibuat
- `database/migrations/2026_10_26_000001_add_siakad_fields_to_users_table.php`
- `database/migrations/2026_10_26_000002_create_tahunajaran_table.php`
- `database/migrations/2026_10_26_000003_create_semester_table.php`
- `app/Models/Tahunajaran.php`
- `app/Models/Semester.php`
- `app/Http/Middleware/ForcePasswordChange.php`
- `app/Livewire/TahunajaranComponent.php`
- `app/Livewire/SemesterComponent.php`
- `resources/views/livewire/tahunajaran-component.blade.php`
- `resources/views/livewire/semester-component.blade.php`
- `database/seeders/AkademikMenuSeeder.php`

## 4. File diubah
- `app/Models/User.php` (fillable, casts, scope search)
- `app/Http/Controllers/LoginController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Kernel.php` (alias force.password)
- `resources/views/login.blade.php`
- `resources/views/setting.blade.php`
- `app/Livewire/UsersComponent.php`
- `resources/views/livewire/users-component.blade.php`
- `routes/web.php`

## 5. SQL hasil `php artisan migrate --pretend` (ringkasan)
Pretend menunjukkan seluruh migration history karena tabel `migrations` kosong pada DB dump. SQL utama migration baru:
- `alter table users add username varchar(30) null` + unique
- `alter table users add wajib_ganti_password tinyint(1) not null default 0`
- `ALTER TABLE users MODIFY email VARCHAR(255) NULL`
- `update users set level = 'superadmin' where level = 'admin'`
- `update users set level = 'siswa' where level = 'user'`
- backfill `username` dari email
- `create table tahunajaran (...)`
- `create table semester (...)` + unique + FK restrict on delete

Untuk menjalankan hanya migration baru tanpa membuat ulang tabel lama, record migration lama dimasukkan ke tabel `migrations` (batch 1) sebelum `php artisan migrate`. Hasil: ketiga migration baru `DONE`.

## 6. Hasil konversi level user lama
- id 4: admin -> superadmin, username `admin`
- id 13: superadmin -> superadmin, username `superadmin`
- id 15: user -> siswa, username `budi`
Rekap: superadmin=2, siswa=1. Tidak ada level `admin`/`user` tersisa.

## 7. Keputusan di luar spesifikasi
- Tidak ada package baru.
- Views Tahun Ajaran/Semester dibuat sederhana (form inline + tabel) karena pola modal Livewire asli memerlukan JS modal; fungsionalitas inti (tambah, aktifkan, hapus dengan pengecekan relasi) tetap tersedia.
- `RakSeeder` dan komponen Spatie-style tidak diubah (ditunda).
- Record migration lama dimasukkan ke tabel `migrations` agar `php artisan migrate` tidak mencoba membuat ulang tabel existing (tanpa menghapus data).
- `ForcePasswordChange` mengecualikan path/route setting, profil.edit, logout, refresh-captcha.

## 7a. Keputusan seeder dan route
- `AkademikMenuSeeder` sudah dijalankan di DB lokal. Baris yang ditambahkan:
  - `appmenucat`: id 13, `Akademik`, urut 50.
  - `appmenunew`: id 37 `Tahun Ajaran` (url `tahunajaran`), id 38 `Semester` (url `semester`), kategori 13.
  - `usermenu`: id 100-103, menempel ke user superadmin id 4 dan 13 untuk kedua menu.
- Awalnya ada risiko route ganda: loop dinamis `Usermenu` juga mendaftarkan `tahunajaran` dan `semester`, sehingga proteksi `check.level:superadmin` pada grup eksplisit bisa terlewati. Perbaikan: loop dinamis sekarang `continue` untuk URL yang sudah terdaftar eksplisit (`appmenunew`, `appmenucat`, `rolecat`, `role`, `users`, `usermenu`, `tahunajaran`, `semester`). Dengan demikian route final hanya berasal dari grup `check.level:superadmin`.
- `php artisan route:list` menampilkan satu route untuk `tahunajaran` dan satu untuk `semester` (handler `TahunajaranComponent` dan `SemesterComponent`), tanpa duplikat.

## 8. Temuan/risiko
- `users.email` sekarang nullable; data lama tetap.
- Export Users sekarang hanya id, username, name, email, level (tanpa password/remember_token).
- `MenuAccessMiddleware` dan sidebar tetap berfungsi; level baru (guru/siswa) tidak melihat menu manajemen.
- Pint `--dirty` melaporkan 0 file karena bukan git repo; tidak ada error lint yang diperbaiki otomatis.

## 9. Hasil verifikasi G
Dijalankan (perintah + output singkat):
1. `php artisan route:list` → sukses, route `tahunajaran` dan `semester` tampil.
2. `php artisan migrate --pretend` → sukses, SQL ditampilkan.
3. `php artisan migrate` → ketiga migration baru `DONE`.
4. Script uji `Semester::aktifkan()` → s2 aktif=1, s1 aktif=0, tahun aktif=1; setelah aktivasi s1: s1 aktif=1, s2 aktif=0.
5. Uji hapus tahun ajaran bertsemester → ditolak (FK restrict).
6. `php vendor/bin/pint` pada file tahap 1 → 13 files, 11 style issues fixed.

Ditinjau dari kode (bukan dijalankan end-to-end di browser):
- Login username + captcha + throttle: ditinjau dari `LoginController` dan view.
- Wajib ganti password flow: ditinjau dari middleware dan `AuthController`.
- Export Users tanpa password: ditinjau dari `UsersComponent`.
- Throttle 6x gagal: ditinjau dari konfigurasi RateLimiter (tidak diuji manual 6x).

## 10. Belum dikerjakan
- Tahap 2 dan seterusnya (modul akademik domain: siswa, kelas, guru, mapel, dll.).
- Pengujian browser/UI menyeluruh.
- Pembersihan komponen generator/bug (Docs, Spatie-style) ditunda.
```

, "path": "C:\\project-reyhan\\template web\\LAPORAN_TAHAP1.md"}