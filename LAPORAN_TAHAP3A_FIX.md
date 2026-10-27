# Laporan Perbaikan Tahap 3A

## Perubahan

- `database/migrations/2026_10_27_000006_add_keterangan_to_jampelajaran_table.php` — migration aditif untuk `jampelajaran.keterangan` (`varchar(100)`, nullable).
- `app/Models/Jampelajaran.php` — menambahkan `keterangan` ke `$fillable`.
- `app/Services/JampelajaranService.php` — simpan keterangan opsional (maksimum 100 karakter), izinkan perubahan waktu pada jam yang sudah dijadwalkan, dan sertakan jumlah jadwal saat jam dipakai.
- `app/Livewire/JampelajaranComponent.php` — state/form tambah-edit, default kosong dan tabel keterangan.
- `resources/views/livewire/jampelajaran-component.blade.php` — input dan kolom Keterangan.
- `app/Services/PolajamService.php` — tolak pemetaan hari yang sudah terikat pada pola lain; pola hanya bisa dihapus saat tidak punya pemetaan atau jam.
- `app/Services/PengampuService.php` — pesan bentrok pergantian guru menyertakan hari, jam ke-, dan nama kelas.
- `app/Services/JadwalService.php` — pesan bentrok siswa menyertakan jumlah siswa.
- Skrip `storage/app/tmp-uji/JadwalFixVerification.php` dibuat untuk verifikasi dalam transaksi dan dihapus setelah selesai.

Tidak ada perubahan migration lama, route, package, atau file `.env`. Tidak ada operasi tulis Git.

## Keputusan dan risiko

- Satu hari tidak dipindah langsung dari pola lama ke pola baru melalui penyimpanan pola. Hari harus dilepas dari pola lama terlebih dahulu; ini mencegah satu hari tampak dipetakan pada beberapa pola selama proses edit.
- Penghapusan pola sekarang mensyaratkan pola benar-benar kosong; relasi pemetaan/jam tidak dihapus otomatis.
- Pesan bentrok siswa menghitung semua siswa yang bentrok, lalu menampilkan paling banyak lima nama. Penghitungan membutuhkan pengambilan hasil query bentrok untuk menghitung total secara tepat.
- Penggantian jadwal yang ditolak diuji pada konflik kelas, guru, dan siswa; semua baris di slot semester terkait dibandingkan sebelum/sesudah dan jumlah baris calon tetap nol.
- UI belum diuji melalui browser.

## Verifikasi K1–K12

### Acuan awal

Perintah `php artisan tinker --execute=...`:

```text
users=5
siswa=1
guru=1
mapel=1
kelas=1
kelassiswa=1
semester=1
usermenu=30
polajam=0
polajamhari=0
jampelajaran=0
pengampu=0
jadwal=0
```

### K1 — Migration aditif

Perintah `php artisan migrate --pretend`:

```text
INFO  Running migrations.

2026_10_27_000006_add_keterangan_to_jampelajaran_table
  alter table `jampelajaran` add `keterangan` varchar(100) null
```

Perintah `php artisan migrate`:

```text
INFO  Running migrations.
2026_10_27_000006_add_keterangan_to_jampelajaran_table ... 319.93ms DONE
```

Perintah `php artisan tinker --execute="dump(DB::select('SHOW COLUMNS FROM jampelajaran'));"` menampilkan 9 kolom; baris kolom baru:

```text
+"Field": "keterangan"
+"Type": "varchar(100)"
+"Null": "YES"
+"Default": null
```

**PASS** — SQL pretend hanya satu `ALTER TABLE` untuk menambah kolom.

### K2–K11 — Fixture

Perintah `php storage\app\tmp-uji\JadwalFixVerification.php`. Semua data pola, jam, jadwal, pengampu, kelas, tahun ajaran, semester, dan relasi siswa pada skrip berada di dalam transaksi. Output mentah final:

```text
K2_DUPLICATE_WEEKDAY_MAPPING PASS Hari Senin sudah dipetakan ke pola lain.
K3_DELETE_MAPPED_PATTERN PASS Pola tidak dapat dihapus karena masih dipakai oleh pemetaan hari.
K3_DELETE_PATTERN_WITH_HOURS PASS Pola tidak dapat dihapus karena masih memiliki jam pelajaran.
K3_DELETE_EMPTY_PATTERN PASS empty unmapped pattern deleted
K6_DAY_WITHOUT_PATTERN PASS Jumat belum dipetakan ke pola jam.
K6_FRIDAY_HOUR_7 PASS Jam ke-7 tidak tersedia pada pola hari Jumat.
K6_DAY_6 PASS Hari harus Senin sampai Jumat.
K6_BREAK_NOT_SCHEDULABLE PASS Pilih pengampu dan nomor jam yang valid.
K6_TWO_PATTERNS_AND_SLOTS PASS weekdays=1-4 max=8; Friday max=6; friday7=rejected friday6=accepted day6=rejected break_jam_ke=null
K7_SAME_TEACHER_SAME_SEMESTER PASS Guru bentrok dengan kelas F115118664K7A (Bahasa Indonesia).
K7_DIFFERENT_SEMESTER_ALLOWED PASS same teacher and slot accepted in second semester of same year
K8_STUDENT_CONFLICT_COUNT PASS Siswa bentrok: 1 siswa (Hibiki Yutaa).
K8_ALTERNATE_SLOT_ALLOWED PASS X@Mon1 accepted; P@Mon1 rejected; P@Mon2 accepted
K4_CHANGE_SCHEDULED_TIME_ALLOWED PASS updated_start=14:10 scheduled_count=2
K4_CHANGE_SCHEDULED_HOUR_REJECTED PASS Nomor jam tidak dapat diubah karena sudah dipakai oleh 2 jadwal.
K4_DELETE_SCHEDULED_HOUR_REJECTED PASS Jam tidak dapat dihapus karena sudah dipakai oleh 2 jadwal.
K5_TEACHER_CHANGE_CONFLICT PASS Guru sudah memiliki jadwal pada Senin jam ke-1 untuk kelas F115118664K5B.
K5_TEACHER_CHANGE_NO_CONFLICT PASS accepted new teacher after no matching slot
K9_REPLACE_CLASS_CONFLICT PASS Kelas bentrok dengan Bahasa Indonesia oleh Guru Fix 115118664B.
K9_CLASS_FAILURE_PRESERVES_ROWS PASS SELECT_BEFORE=[{"idjadwal":71,"pengampu_idpengampu":102,"hari":1,"jam_ke":1},{"idjadwal":72,"pengampu_idpengampu":103,"hari":1,"jam_ke":1}] SELECT_AFTER=[{"idjadwal":71,"pengampu_idpengampu":102,"hari":1,"jam_ke":1},{"idjadwal":72,"pengampu_idpengampu":103,"hari":1,"jam_ke":1}] candidate_rows=0
K9_REPLACE_TEACHER_CONFLICT PASS Guru bentrok dengan kelas F115118664K9TX (Bahasa Indonesia).
K9_TEACHER_FAILURE_PRESERVES_ROWS PASS SELECT_BEFORE=[{"idjadwal":73,"pengampu_idpengampu":105,"hari":1,"jam_ke":1},{"idjadwal":74,"pengampu_idpengampu":106,"hari":1,"jam_ke":1}] SELECT_AFTER=[{"idjadwal":73,"pengampu_idpengampu":105,"hari":1,"jam_ke":1},{"idjadwal":74,"pengampu_idpengampu":106,"hari":1,"jam_ke":1}] candidate_rows=0
K9_REPLACE_STUDENT_CONFLICT PASS Siswa bentrok: 1 siswa (Hibiki Yutaa).
K9_STUDENT_FAILURE_PRESERVES_ROWS PASS SELECT_BEFORE=[{"idjadwal":75,"pengampu_idpengampu":108,"hari":1,"jam_ke":1},{"idjadwal":76,"pengampu_idpengampu":109,"hari":1,"jam_ke":1}] SELECT_AFTER=[{"idjadwal":75,"pengampu_idpengampu":108,"hari":1,"jam_ke":1},{"idjadwal":76,"pengampu_idpengampu":109,"hari":1,"jam_ke":1}] candidate_rows=0
K9_REPLACEMENT_SUCCESS_ONE_ROW PASS SELECT_AFTER=[{"idjadwal":77,"pengampu_idpengampu":112}]
K10_ADD_DEFAULTS PASS empty_pattern jam_ke=1 mulai=(empty); filled_pattern jam_ke=5 mulai=10:00
K11_KETERANGAN_SAVED_AND_RENDERED PASS HTML_SNIPPET=an</th><th>Aksi</th></tr></thead>              <tbody>                              <tr><td>1</td><td>Pelajaran</td><td>1</td>                      <td>07:00</td><td>07:45</td><td>Catatan K11</td>                      <td class="text-nowrap"><button class="btn btn-sm btn-warning" wire:click="edit(16
TRANSACTION_ROLLED_BACK
```

**PASS** — K2, K3, K4, K5, K6, K7, K8, K9, K10, dan K11 lulus.

### K12 — Regresi

`php artisan route:list` selesai tanpa error dan menampilkan:

```text
GET|HEAD jampelajaran ... App\Livewire\JampelajaranComponent
GET|HEAD jadwal ... App\Livewire\JadwalComponent
Showing [63] routes
```

`php artisan view:clear`, `php artisan view:cache`, `php artisan view:clear`:

```text
INFO  Compiled views cleared successfully.
INFO  Blade templates cached successfully.
INFO  Compiled views cleared successfully.
```

`php -l` dijalankan satu per satu:

```text
No syntax errors detected in database\migrations\2026_10_27_000006_add_keterangan_to_jampelajaran_table.php
No syntax errors detected in app\Models\Jampelajaran.php
No syntax errors detected in app\Services\JampelajaranService.php
No syntax errors detected in app\Livewire\JampelajaranComponent.php
No syntax errors detected in app\Services\PengampuService.php
No syntax errors detected in app\Services\JadwalService.php
No syntax errors detected in app\Services\PolajamService.php
No syntax errors detected in storage\app\tmp-uji\JadwalFixVerification.php
```

Pint dijalankan satu per satu pada file yang berubah (skrip sementara termasuk sebelum dihapus):

```text
database\migrations\2026_10_27_000006_add_keterangan_to_jampelajaran_table.php — PASS 1 file
app\Models\Jampelajaran.php — PASS 1 file
app\Services\JampelajaranService.php — PASS 1 file
app\Livewire\JampelajaranComponent.php — PASS 1 file
app\Services\PengampuService.php — PASS 1 file
app\Services\JadwalService.php — PASS 1 file
app\Services\PolajamService.php — PASS 1 file
storage\app\tmp-uji\JadwalFixVerification.php — PASS 1 file
```

Hitungan akhir:

```text
users=5
siswa=1
guru=1
mapel=1
kelas=1
kelassiswa=1
semester=1
usermenu=30
polajam=0
polajamhari=0
jampelajaran=0
pengampu=0
jadwal=0
```

**PASS** — semua hitungan sama dengan acuan awal; migration hanya menambah kolom. `git diff --check` menghasilkan `DIFF_CHECK_EXIT=0`. Git juga mengeluarkan peringatan line ending berikut untuk Blade:

```text
warning: in the working copy of 'resources/views/livewire/jampelajaran-component.blade.php', CRLF will be replaced by LF the next time Git touches it
```

Skrip uji sementara dihapus setelah verifikasi.

### Catatan percobaan yang dikoreksi

Satu probe Tinker untuk menampilkan kolom gagal karena PowerShell mengembangkan variabel PHP dalam argumen:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected ')' on line 1
```

Satu query Tinker terfilter untuk `SHOW COLUMNS` juga gagal karena quoting:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected T_STRING, expecting ')' on line 1
```

Keduanya diganti dengan `dump(DB::select('SHOW COLUMNS FROM jampelajaran'))`, yang berhasil dan menampilkan `keterangan` nullable.

Eksekusi pertama skrip fixture menghasilkan dua kegagalan pada harness. Output mentah:

```text
K5_TEACHER_CHANGE_CONFLICT FAIL Guru sudah memiliki jadwal pada Senin jam ke-1 untuk kelas F114744454K5B.
K_FATAL Illuminate\View\ViewException: Undefined variable $jenis (View: C:\project-reyhan\template web\resources\views\livewire\jampelajaran-component.blade.php)
TRANSACTION_ROLLED_BACK
```

Pencocokan nama kelas K5 memakai string ekspektasi yang salah; render Blade langsung juga tidak membawa properti publik Livewire. Ekspektasi K5 dan konteks render tes diperbaiki. Uji ulang penuh berakhir dengan seluruh pemeriksaan PASS dan `TRANSACTION_ROLLED_BACK`; kegagalan tersebut tidak memerlukan perubahan perilaku aplikasi.
