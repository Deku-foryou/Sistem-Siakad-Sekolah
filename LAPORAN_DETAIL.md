# Laporan Detail Siswa dan Guru

## File yang diubah

- `app/Livewire/SiswaComponent.php`
- `app/Livewire/GuruComponent.php`
- `resources/views/livewire/siswa-component.blade.php`
- `resources/views/livewire/guru-component.blade.php`
- `tests/Feature/DetailDisplayVerificationTest.php` (uji dengan fixture dalam transaksi, rollback di `finally`)
- `LAPORAN_DETAIL.md`

Worktree sudah memiliki perubahan belum di-commit pada file Kelas/sidebar sebelum tugas ini. Perubahan tersebut dipertahankan dan tidak disentuh sebagai bagian fitur Detail.

## Verifikasi (D1-D10)

### D1 — Detail siswa dengan kelas aktif dan riwayat

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D1 PASS {"active_year":"2026\/2027","active_parent":{"nama":"Kelas Induk Uji","tingkat":"10","wali":"Guru Wali 3ce21f5610"},"active_electives":["Mapel Pilihan Uji"],"history":[{"year":"2026\/2027","class":"Mapel Pilihan Uji","type":"mapel_pilihan","wali":null,"active":true},{"year":"2026\/2027","class":"Kelas Induk Uji","type":"induk","wali":"Guru Wali 3ce21f5610","active":true},{"year":"2025\/2026","class":"Kelas Lama Uji","type":"induk","wali":null,"active":false}]}
```

PASS — kelas induk, tingkat, wali, mapel pilihan, serta riwayat terurut dan penanda tahun aktif cocok dengan fixture.

### D2 — Tanpa kelas dan tanpa tahun ajaran aktif

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D2_ACTIVE PASS {"message":"Belum ditempatkan di kelas"}
D2_NO_ACTIVE PASS {"active_year_null":true,"message":"Belum ada tahun ajaran aktif"}
```

PASS — kedua kondisi dirender tanpa error.

### D3 — Siswa dengan kolom opsional kosong

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D3 PASS {"optional_null_columns":["nis","tempat_lahir","agama","alamat","nama_ayah","nama_ibu","telepon_ortu","tahun_masuk"],"rendered_dash_values":8}
```

PASS — seluruh kolom opsional fixture null dan tampil sebagai `-`.

### D4 — Guru wali dan guru tanpa kelas

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D4 PASS {"wali_classes":[{"nama":"Kelas Induk Uji","year":"2026\/2027","active":true}],"no_class_count":0,"no_class_message":true}
```

PASS — satu kelas induk guru wali bertanda aktif; guru tanpa kelas menampilkan teks yang diminta.

### D5 — Otorisasi dan penerimaan superadmin

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D5 PASS {"forbidden":{"siswa":{"detailSiswa":403,"detailGuru":403},"guru":{"detailSiswa":403,"detailGuru":403}},"superadmin_detail_ids":[580,41],"events":[["show-detail-siswa-modal"],["show-detail-guru-modal"]]}
```

PASS — level siswa dan guru ditolak 403; superadmin diterima dan masing-masing event buka modal dikirim.

### D6 — Pemilihan kolom akun dan data view

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D6 PASS {"siswa_columns":["idsiswa","user_iduser","nisn","nis","nama","jenis_kelamin","tempat_lahir","tanggal_lahir","agama","alamat","nama_ayah","nama_ibu","telepon_ortu","tahun_masuk","status"],"user_columns":["id","username","wajib_ganti_password"],"user_sql":"select `id`, `username`, `wajib_ganti_password` from `users` where `users`.`id` in (671)"}
```

PASS — relasi akun hanya memilih `id`, `username`, dan `wajib_ganti_password`; tidak memilih `password`, hash, atau `remember_token`.

### D7 — Jumlah query dengan lima riwayat

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D7 PASS {"history_rows":5,"queries_with_three_history_rows":9,"queries_on_open":9}
```

PASS — jumlah query pembukaan detail tetap 9 untuk tiga maupun lima riwayat; tidak bertambah mengikuti jumlah riwayat.

### D8 — Ukuran state publik Livewire

**Perintah:** `php artisan test tests\Feature\DetailDisplayVerificationTest.php`

**Output:**

```text
D8 PASS {"public_properties_json":"{\"idsiswa\":null,\"detailId\":580,\"user_iduser\":null,\"nisn\":null,\"nis\":null,\"nama\":null,\"jenis_kelamin\":\"L\",\"tempat_lahir\":null,\"tanggal_lahir\":null,\"agama\":null,\"alamat\":null,\"nama_ayah\":null,\"nama_ibu\":null,\"telepon_ortu\":null,\"tahun_masuk\":null,\"foto\":null,\"status\":\"aktif\",\"search\":\"\",\"perPage\":10,\"fileImport\":null,\"importUuid\":null,\"importJenis\":\"\",\"importOffset\":0,\"importTotal\":0,\"importBerhasil\":0,\"importGagal\":0,\"importSelesai\":false,\"importBerjalan\":false,\"importPeringatan\":[],\"importPesan\":\"\",\"paginators\":[]}","json_length":520}
```

PASS — state publik berisi ID skalar, bukan model atau array detail; JSON seluruh state komponen panjangnya 520 byte.

### D9 — Lint, Pint, dan rute

**Perintah dan output lint:**

```text
php -l app\Livewire\SiswaComponent.php
No syntax errors detected in app\Livewire\SiswaComponent.php

php -l app\Livewire\GuruComponent.php
No syntax errors detected in app\Livewire\GuruComponent.php

php -l tests\Feature\DetailDisplayVerificationTest.php
No syntax errors detected in tests\Feature\DetailDisplayVerificationTest.php
```

**Pint dijalankan hanya pada file PHP yang dibuat/diubah, satu path per perintah:**

```text
php vendor\bin\pint --test app\Livewire\SiswaComponent.php
PASS ... 1 file

php vendor\bin\pint --test app\Livewire\GuruComponent.php
PASS ... 1 file

php vendor\bin\pint --test tests\Feature\DetailDisplayVerificationTest.php
PASS ... 1 file
```

**Perintah dan output rute:**

```text
php artisan route:list --path=siswa
GET|HEAD  kelassiswa  App\Livewire\KelassiswaComponent
GET|HEAD  siswa       App\Livewire\SiswaComponent
Showing [2] routes

php artisan route:list --path=guru
GET|HEAD  guru  App\Livewire\GuruComponent
Showing [1] routes

php artisan route:list | Select-Object -Last 3
Showing [57] routes
```

**Perintah dan output pemeriksaan perubahan rute/migration:**

```text
git --no-pager diff --stat -- routes database\migrations
[tidak ada output]
```

PASS — lint dan Pint lulus; semua 57 rute dapat didaftarkan; perubahan rute/migration tidak ada.

Percobaan awal Livewire test endpoint tidak dapat dipakai pada harness proyek; setelah itu skenario dijalankan melalui PHPUnit dengan komponen dan view langsung, dalam transaksi yang di-rollback di `finally`. Error percobaan yang diselesaikan:

```text
<warning> DEPRECATED </warning> Creation of dynamic property Illuminate\Testing\TestResponse::$original is deprecated in vendor\livewire\livewire\src\Features\SupportTesting\InitialRender.php on line 42.
<warning> WARNING </warning> Trying to access array offset on value of type null in vendor\livewire\livewire\src\Mechanisms\HandleComponents\HandleComponents.php on line 88.
<warning> WARNING </warning> Trying to access array offset on value of type null in vendor\livewire\livewire\src\Mechanisms\HandleComponents\HandleComponents.php on line 89.
<warning> WARNING </warning> Trying to access array offset on value of type null in vendor\livewire\livewire\src\Mechanisms\HandleComponents\Checksum.php on line 9.
Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException Livewire encountered corrupt data when trying to hydrate a component.
Ensure that the [name, id, data] of the Livewire component wasn't tampered with between requests.

php artisan test tests\Feature\DetailDisplayVerificationTest.php --display-output
Unknown option "--display-output"

INITIAL_HTML <!DOCTYPE html>
<html lang="en">
<title>Halaman Tidak Ditemukan</title>
Trying to access array offset on value of type null
at vendor\livewire\livewire\src\Mechanisms\HandleComponents\HandleComponents.php:88

ViewException Undefined variable $errors (View: C:\project-reyhan\template web\resources\views\livewire\siswa-component.blade.php)

ViewException Undefined variable $__livewire (View: C:\project-reyhan\template web\resources\views\livewire\partials\import-modal.blade.php) (View: C:\project-reyhan\template web\resources\views\livewire\partials\import-modal.blade.php)

Failed asserting that 6 is identical to 5.
at tests\Feature\DetailDisplayVerificationTest.php:199

Expected: select `id`, `username`, `wajib_ganti_password` from `users` where `users`.`id` in (655)
Not to contain: password
```

### D10 — Jumlah baris setelah rollback

Jumlah awal yang dicatat sebelum perubahan:

```text
users=5, siswa=1, guru=1, kelas=1, kelassiswa=1
```

**Perintah:** `php artisan tinker --execute="dump(['users' => DB::table('users')->count(), 'siswa' => DB::table('siswa')->count(), 'guru' => DB::table('guru')->count(), 'kelas' => DB::table('kelas')->count(), 'kelassiswa' => DB::table('kelassiswa')->count()]);"`

**Output sesudah uji:**

```text
array:5 [
  "users" => 5
  "siswa" => 1
  "guru" => 1
  "kelas" => 1
  "kelassiswa" => 1
]
```

PASS — jumlah sama dengan acuan awal; seluruh fixture uji dibatalkan.

## Keputusan

- Kedua komponen menyimpan hanya `detailId` skalar; detail diambil saat `render()`.
- Query detail memakai eager loading dan memilih kolom akun secara eksplisit.
- Modal menggunakan grid responsif Bootstrap: dua kolom mulai ukuran `lg`, satu kolom di layar kecil.
- Detail hanya baca; tidak mengubah alur simpan, edit, atau hapus.

## Risiko dan belum diuji

- **Belum diuji (butuh browser):** modal terbuka/tertutup secara visual, tata letak dua kolom, tampilan HP, dan tiga tombol Aksi tidak turun baris.
- Uji automated memeriksa event buka/tutup dan hasil render, tetapi tidak menggantikan pemeriksaan interaksi visual browser.
