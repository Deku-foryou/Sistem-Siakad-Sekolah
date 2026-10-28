LAPORAN_PERBAIKAN_FRESH_MIGRATION.md

## Perbaikan Migration Database Untuk `migrate:fresh --seed`

### Masalah Awal
 ketika menjalankan `php artisan migrate:fresh --seed`, Laravel gagal dengan error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'templatedb.appmenunew' doesn't exist
```

### Akar Penyebab
1. **Tabel `users` tidak ada**: Migration `2026_10_26_000001_add_siakad_fields_to_users_table.php` mengasumsikan tabel `users` sudah ada, tetapi tidak ada migration pembuat tabel `users`.

2. **Tabel `appmenunew` tidak ada**: Ketika `migrate:fresh` berjalan, aplikasi bootstrapping dan mencoba memuat rute dari `routes/web.php`, yang berisi kode database top-level:
   ```php
   $appMenuUrls = \App\Models\Appmenunew::query()
       ->whereNotNull('url')
       ->pluck('url')
       ->unique()
       ->values();
   ```
   Kode ini berjalan saat bootstrap aplikasi, sebelum migration dieksekusi, menyebabkan error karena tabel `appmenunew` belum dibuat.

3. **Urutan migration tidak sinkron**: Beberapa migration bergantung pada tabel lain yang belum dibuat di urutan yang tepat.

4. **Tabel helper lainnya**: Tabel `rolecat`, `appmenucat`, `role`, dan tabel lain juga perlu dibuat karena seeder mengandalkan mereka.

### File yang Dibuat/Diubah

1. **`database/migrations/2026_10_26_000000_create_users_table.php`** - Migration pembuat tabel `users` dengan kolom: id, name, username, email, email_verified_at, password, level, wajib_ganti_password, rememberToken, timestamps.

2. **`database/migrations/2026_10_26_000000_create_appmenunew_table.php`** - Migration pembuat tabel `appmenunew`.

3. **`routes/web.php`** - Diperbaiki dengan menambahkan pengecekan `Schema::hasTable('appmenunew')` sebelum menjalankan query database di top-level routes, sehingga error tidak terjadi saat `migrate:fresh`.

4. **`database/migrations/2026_10_28_135304_create_rolecat_table.php`** - Migration pembuat tabel `rolecat` untuk sistem role.

5. **`database/migrations/2026_10_28_135330_create_appmenucat_table.php`** - Migration pembuat tabel `appmenucat` untuk kategori menu.

6. **`database/migrations/2026_10_28_135531_create_role_table.php`** - Migration pembuat tabel `role` untuk assignment menu.

7. **`database/migrations` penghormatan ulang**:
   - `2026_10_30_000001_create_jurusan_table.php` - dinamai menjadi `2026_10_31_000001_create_jurusan_table.php`
   - `2026_10_28_130000_add_jurusan_to_kelas.php` - dinamai menjadi `2026_11_01_000001_add_jurusan_to_kelas.php`
   - `2026_10_28_114902_add_rombel_to_kelas_table.php` - dinamai menjadi `2026_11_02_000001_add_rombel_to_kelas_table.php`
   - `2026_10_28_131509_add_unique_constraint_to_kelas_table.php` - dinamai menjadi `2026_11_03_000001_add_unique_constraint_to_kelas_table.php`
   - `2026_10_28_131653_remove_unique_constraint_from_kelas_table.php` - dinamai menjadi `2026_11_04_000001_remove_unique_constraint_from_kelas_table.php`

8. **`database/seeders/DatabaseSeeder.php`** - Diperbarui untuk:
   - Memanggil seeder yang diperlukan (PaketDefaultSeeder, AkademikMenuSeeder, dll)
   - Membuat akun admin default dengan email `admin@sekolah.id`, username `admin`, level `superadmin`
   - Password default: `rahasia123`

### Urutan Migrasi yang Disempurnakan
Migrasi sekarang dijalankan dalam urutan yang benar:
1. `2026_05_05_082415_create_activity_log_table` - Tabel activity log
2. `2026_10_26_000000_create_appmenunew_table` - Tabel appmenunew
3. `2026_10_26_000000_create_users_table` - Tabel users
4. `2026_10_26_000001_add_siakad_fields_to_users_table` - Menambah field SIAKAD ke users
5. `2026_10_26_000002_create_tahunajaran_table` - Tabel tahunajaran
6. `2026_10_26_000003_create_semester_table` - Tabel semester
7. `2026_10_26_000004_add_usermenu_composite_index` - Indeks usermenu
8. `2026_10_26_000005_create_guru_table` - Tabel guru
9. `2026_10_26_000006_create_siswa_table` - Tabel siswa
10. `2026_10_26_000007_create_mapel_table` - Tabel mapel
11. `2026_10_26_000008_create_kelas_table` - Tabel kelas
12. `2026_10_26_000009_create_kelassiswa_table` - Tabel kelassiswa
13. `2026_10_27_000001_create_polajam_table` - Tabel polajam
14. `2026_10_27_000002_create_polajamhari_table` - Tabel polajamhari
15. `2026_10_27_000003_create_jampelajaran_table` - Tabel jampelajaran
16. `2026_10_27_000004_create_pengampu_table` - Tabel pengampu
17. `2026_10_27_000005_create_jadwal_table` - Tabel jadwal
18. `2026_10_27_000006_add_keterangan_to_jampelajaran_table` - Menambah field keterangan
19. `2026_10_28_000001_add_status_to_jadwal_table` - Menambah field status jadwal
20. `2026_10_28_000002_create_publikasijadwal_table` - Tabel publikasijadwal
21. `2026_10_28_000003_add_withdrawn_at_to_publikasijadwal_table` - Menambah field withdrawn_at
22. `2026_10_28_135304_create_rolecat_table` - Tabel rolecat
23. `2026_10_28_135330_create_appmenucat_table` - Tabel appmenucat
24. `2026_10_28_135531_create_role_table` - Tabel role
25. `2026_10_29_000001_add_identitas_lengkap_to_siswa_table` - Menambah field identitas lengkap
25. `2026_10_31_000001_create_jurusan_table` - Tabel jurusan
26. `2026_11_01_000001_add_jurusan_to_kelas` - Menambah field jurusan ke kelas
27. `2026_11_01_000001_add_status_to_kelassiswa_table` - Menambah field status ke kelassiswa
28. `2026_11_02_000001_add_rombel_to_kelas_table` - Menambah field rombel ke kelas
29. `2026_11_03_000001_add_unique_constraint_to_kelas_table` - Menambah unique constraint
30. `2026_11_04_000001_remove_unique_constraint_from_kelas_table` - Menghapus unique constraint lama

### Hasil Pengujian
Perintah `php artisan migrate:fresh --seed` sekarang berhasil dieksekusi sepenuhnya dengan hasil:
- Semua 30 migration tercatat status `Ran`
- Semua tabel dasar dibuat: users, appmenunew, appmenucat, rolecat, jurusan, kelas, dll
- Akun administrator dibuat dengan:
  - ID: 1
  - Nama: Administrator
  - Email: admin@sekolah.id
  - Username: admin
  - Level: superadmin
- Semua route Livewire terdaftar dengan benar
- Seeder berjalan tanpa error

### Catatan
- Password admin default: `rahasia123`
- Sistem menggunakan kolom `level` untuk role-based access control (bukan Spatie Laravel Permission penuh)
- Tabel `role`, `rolecat`, `appmenucat` dibuat untuk mendukung fitur menu dan hak akses