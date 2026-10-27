# Laporan Tahap 3A — Pola Jam, Pengampu, dan Jadwal

## Ringkasan perubahan

Ditambahkan lima migration aditif, lima model untuk tabel baru dan satu helper `App\Support\Hari`. Validasi domain ditempatkan pada `PolajamService`, `JampelajaranService`, `PengampuService`, dan `JadwalService`. Empat komponen Livewire mempunyai pemeriksaan level superadmin pada setiap metode publik milik komponen; aksi alert dari trait dibuat protected agar tidak menjadi metode Livewire yang bisa dipanggil tanpa pemeriksaan.

Grid jadwal memuat pemetaan dan data jadwal dengan eager loading, bukan query per sel. Penulisan jadwal dijalankan dalam transaksi, mengunci semester untuk serialisasi perubahan pada semester yang sama, lalu mengunci baris jadwal, pemetaan hari, pola, dan jam yang relevan. Bentrok siswa dicari dengan satu query join terpadu.

`JadwalMenuSeeder` membuat kategori dan empat item secara idempoten, kemudian memberi menu hanya kepada user superadmin lewat `usermenu`. Seeder tidak dipanggil dari `DatabaseSeeder`. Tabel `polajam`, `polajamhari`, dan `jampelajaran` tidak diisi oleh seeder.

### File dibuat

- `database/migrations/2026_10_27_000001_create_polajam_table.php`
- `database/migrations/2026_10_27_000002_create_polajamhari_table.php`
- `database/migrations/2026_10_27_000003_create_jampelajaran_table.php`
- `database/migrations/2026_10_27_000004_create_pengampu_table.php`
- `database/migrations/2026_10_27_000005_create_jadwal_table.php`
- `app/Models/Polajam.php`, `app/Models/Polajamhari.php`, `app/Models/Jampelajaran.php`, `app/Models/Pengampu.php`, `app/Models/Jadwal.php`
- `app/Services/PolajamService.php`, `app/Services/JampelajaranService.php`, `app/Services/PengampuService.php`, `app/Services/JadwalService.php`
- `app/Livewire/PolajamComponent.php`, `app/Livewire/JampelajaranComponent.php`, `app/Livewire/PengampuComponent.php`, `app/Livewire/JadwalComponent.php`
- `app/Support/Hari.php`
- `database/seeders/JadwalMenuSeeder.php`
- `resources/views/livewire/polajam-component.blade.php`
- `resources/views/livewire/jampelajaran-component.blade.php`
- `resources/views/livewire/pengampu-component.blade.php`
- `resources/views/livewire/jadwal-component.blade.php`
- `LAPORAN_TAHAP3A.md`

### File diubah

- `routes/web.php` — mendaftarkan empat URL jadwal secara eksplisit di grup `check.level:superadmin` dan mengecualikannya dari route dinamis. File ini sudah memiliki perubahan lokal pekerjaan Beranda; perubahan tersebut dipertahankan.
- Skrip verifikasi sementara `storage/app/tmp-uji/JadwalVerification.php` dibuat untuk pengujian, lalu dihapus sesudah semua uji selesai.

Tidak ada package, migration lama, tabel lama, atau file `.env` yang diubah/dibaca. Tidak ada commit atau operasi tulis Git.

## Keputusan dan risiko

- Hari disimpan sebagai angka 1–5 (Senin–Jumat); batasan unik global pada `polajamhari.hari` memastikan satu pola aktif untuk satu hari.
- Penggantian jadwal pada sel yang sudah terisi memerlukan `idjadwal` yang sama dengan jadwal yang sedang diganti. Penyimpanan tanpa ID pengganti menolak kelas bentrok, bukan menimpa jadwal lama secara diam-diam.
- Lock pada baris semester menyerialkan perubahan jadwal dalam semester yang sama, termasuk saat belum ada baris jadwal pada slot tersebut. Ini memperkuat konsistensi, dengan konsekuensi penulisan paralel pada satu semester lebih serial.
- Pemeriksaan konkurensi paralel melalui dua request database bersamaan: **TIDAK BISA DIUKUR** dalam sesi ini. Bukti yang tersedia adalah query `FOR UPDATE` aktual pada verifikasi di bawah.
- UI/UX tidak diuji di browser; detail interaksi modal, navigasi antar-menu, dan tampilan responsif perlu diperiksa manual.
- Branch aktual tetap `feature/beranda`, bukan `feature/jadwal`. Branch tidak diganti karena instruksi Git hanya baca. Perubahan lokal Beranda yang telah ada tetap dipertahankan.

## Verifikasi (J1–J17)

Semua fixture pengujian dibuat setelah `DB::beginTransaction()` dan dikembalikan dengan `DB::rollBack()` di blok `finally`. Skrip sementara dihapus setelah selesai.

### Acuan awal

Hitungan awal sesi (Tinker):

```text
array:11 [
  "users" => 5
  "siswa" => 1
  "guru" => 1
  "mapel" => 1
  "kelas" => 1
  "kelassiswa" => 1
  "tahunajaran" => 1
  "semester" => 1
  "usermenu" => 22
  "rolecat" => 2
  "role" => 4
]
```

### J1 — Migration aditif

Perintah:

```text
php artisan migrate --pretend
```

Output SQL:

```text
create table `polajam` (`idpolajam` int unsigned not null auto_increment primary key, `nama` varchar(255) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `polajam` add unique `polajam_nama_unique`(`nama`)
create table `polajamhari` (`idpolajamhari` int unsigned not null auto_increment primary key, `polajam_idpolajam` int unsigned not null, `hari` tinyint unsigned not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `polajamhari` add unique `polajamhari_hari_unique`(`hari`)
alter table `polajamhari` add index `polajamhari_polajam_idpolajam_index`(`polajam_idpolajam`)
alter table `polajamhari` add constraint `polajamhari_polajam_idpolajam_foreign` foreign key (`polajam_idpolajam`) references `polajam` (`idpolajam`) on delete restrict
create table `jampelajaran` (`idjampelajaran` int unsigned not null auto_increment primary key, `polajam_idpolajam` int unsigned not null, `jenis` enum('pelajaran', 'istirahat') not null, `jam_ke` smallint unsigned null, `mulai` time not null, `selesai` time not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `jampelajaran` add unique `jampelajaran_polajam_idpolajam_jam_ke_unique`(`polajam_idpolajam`, `jam_ke`)
alter table `jampelajaran` add index `jampelajaran_polajam_idpolajam_index`(`polajam_idpolajam`)
alter table `jampelajaran` add constraint `jampelajaran_polajam_idpolajam_foreign` foreign key (`polajam_idpolajam`) references `polajam` (`idpolajam`) on delete restrict
create table `pengampu` (`idpengampu` int unsigned not null auto_increment primary key, `semester_idsemester` int unsigned not null, `kelas_idkelas` int unsigned not null, `mapel_idmapel` int unsigned not null, `guru_idguru` int unsigned not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `pengampu` add unique `pengampu_semester_kelas_mapel_unique`(`semester_idsemester`, `kelas_idkelas`, `mapel_idmapel`)
alter table `pengampu` add index `pengampu_kelas_idkelas_index`(`kelas_idkelas`)
alter table `pengampu` add index `pengampu_mapel_idmapel_index`(`mapel_idmapel`)
alter table `pengampu` add index `pengampu_guru_idguru_index`(`guru_idguru`)
alter table `pengampu` add constraint `pengampu_semester_idsemester_foreign` foreign key (`semester_idsemester`) references `semester` (`idsemester`) on delete restrict
alter table `pengampu` add constraint `pengampu_kelas_idkelas_foreign` foreign key (`kelas_idkelas`) references `kelas` (`idkelas`) on delete restrict
alter table `pengampu` add constraint `pengampu_mapel_idmapel_foreign` foreign key (`mapel_idmapel`) references `mapel` (`idmapel`) on delete restrict
alter table `pengampu` add constraint `pengampu_guru_idguru_foreign` foreign key (`guru_idguru`) references `guru` (`idguru`) on delete restrict
create table `jadwal` (`idjadwal` int unsigned not null auto_increment primary key, `pengampu_idpengampu` int unsigned not null, `hari` tinyint unsigned not null, `jam_ke` smallint unsigned not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `jadwal` add unique `jadwal_pengampu_hari_jam_unique`(`pengampu_idpengampu`, `hari`, `jam_ke`)
alter table `jadwal` add index `jadwal_hari_jam_index`(`hari`, `jam_ke`)
alter table `jadwal` add constraint `jadwal_pengampu_idpengampu_foreign` foreign key (`pengampu_idpengampu`) references `pengampu` (`idpengampu`) on delete restrict
```

Perintah dan hasil penerapan:

```text
php artisan migrate
INFO  Running migrations.
2026_10_27_000001_create_polajam_table ............................................................... 388.85ms DONE
2026_10_27_000002_create_polajamhari_table ........................................................... 214.71ms DONE
2026_10_27_000003_create_jampelajaran_table .......................................................... 199.96ms DONE
2026_10_27_000004_create_pengampu_table .............................................................. 592.85ms DONE
2026_10_27_000005_create_jadwal_table ................................................................ 131.31ms DONE
```

`php artisan migrate:status | Select-String '2026_10_27'`:

```text
2026_10_27_000001_create_polajam_table ............................................................ [4] Ran
2026_10_27_000002_create_polajamhari_table ........................................................ [4] Ran
2026_10_27_000003_create_jampelajaran_table ....................................................... [4] Ran
2026_10_27_000004_create_pengampu_table ........................................................... [4] Ran
2026_10_27_000005_create_jadwal_table ............................................................. [4] Ran
```

**PASS** — hanya lima tabel baru dibuat, setiap foreign key memakai `ON DELETE RESTRICT`.

### J2–J8 — Validasi, pengampu, konflik, dan penghapusan

Perintah: `php storage\app\tmp-uji\JadwalVerification.php`

Output akhir:

```text
J2_PATTERN_AND_WEEKDAYS PASS weekday_count=5
J3_PERIOD_VALIDATION PASS pelajaran=6 istirahat=1 istirahat_jam_ke=null
J3_DUPLICATE_PATTERN_NAME PASS Nama pola sudah digunakan.
J3_INVALID_WEEKDAY PASS Pilih minimal satu hari dari Senin sampai Jumat.
J4_DUPLICATE_JAM_KE PASS Nomor jam tersebut sudah ada pada pola ini.
J4_ZERO_JAM_KE PASS Jam ke wajib berupa angka minimal 1 untuk jam pelajaran.
J4_OVERLAPPING_PERIOD PASS Rentang waktu bertumpang tindih dengan baris lain pada pola ini.
J4_INVALID_TIME_ORDER PASS Jam selesai harus setelah jam mulai.
J5_INACTIVE_TEACHER PASS Guru pengampu harus berstatus aktif.
J5_YEAR_MISMATCH PASS Kelas harus berasal dari tahun ajaran semester yang dipilih.
J5_DUPLICATE_ASSIGNMENT PASS Pengampu untuk semester, kelas, dan mata pelajaran tersebut sudah ada.
J6_UNAVAILABLE_SLOT PASS Jam ke-99 tidak tersedia pada pola hari Senin.
J6_INVALID_WEEKDAY PASS Hari harus Senin sampai Jumat.
J7_CLASS_CONFLICT PASS Kelas bentrok dengan J3A Mapel 1 oleh J3A Guru 0.
J7_TEACHER_CONFLICT PASS Guru bentrok dengan kelas J3A-Induk-07551520 (J3A Mapel 1).
J7_STUDENT_CONFLICT PASS Siswa bentrok: J3A Siswa 1.
J8_PERIOD_DELETE_BLOCKED PASS Jam tidak dapat dihapus karena sudah dipakai jadwal.
J8_ASSIGNMENT_DELETE_BLOCKED PASS Pengampu tidak dapat dihapus karena masih memiliki jadwal.
J8_MAPPING_CHANGE_BLOCKED PASS Pemetaan hari tidak dapat diubah karena masih ada jadwal di luar jam pola baru.
J6_VALID_SCHEDULES PASS schedule_ids=12,13
J6_EXPLICIT_CELL_REPLACEMENT PASS replacement requires matching jadwal id.
J8_DELETE_AFTER_SCHEDULE_REMOVED PASS jadwal and period removed.
TRANSACTION_ROLLED_BACK
```

**PASS** — waktu tumpang tindih/terbalik, nomor jam duplikat, tahun ajaran kelas, slot tidak tersedia, hari tidak valid, serta bentrok kelas/guru/siswa ditolak; penghapusan yang masih berelasi ditolak.

### J9 dan J12 — Satu query siswa dan transaksi/locking

Output query counter dan SQL:

```text
J9_STUDENT_CONFLICT_ONE_JOIN_QUERY PASS queries=1
J12_TRANSACTION_ROW_LOCKS PASS for_update_queries=5
J12_LOCK_SQL select * from `semester` where `semester`.`idsemester` = ? limit 1 for update
J9_STUDENT_CONFLICT_SQL select distinct `siswa`.`idsiswa`, `siswa`.`nama` from `kelassiswa` as `anggota_aktif` inner join `kelassiswa` as `anggota_lain` on `anggota_lain`.`siswa_idsiswa` = `anggota_aktif`.`siswa_idsiswa` inner join `kelas` as `kelas_lain` on `kelas_lain`.`idkelas` = `anggota_lain`.`kelas_idkelas` inner join `pengampu` as `pengampu_lain` on `pengampu_lain`.`kelas_idkelas` = `kelas_lain`.`idkelas` inner join `jadwal` as `jadwal_lain` on `jadwal_lain`.`pengampu_idpengampu` = `pengampu_lain`.`idpengampu` inner join `siswa` on `siswa`.`idsiswa` = `anggota_aktif`.`siswa_idsiswa` where `anggota_aktif`.`kelas_idkelas` = ? and `kelas_lain`.`tahunajaran_idtahunajaran` = ? and `kelas_lain`.`idkelas` != ? and `pengampu_lain`.`semester_idsemester` = ? and `jadwal_lain`.`hari` = ? and `jadwal_lain`.`jam_ke` = ? order by `siswa`.`nama` asc limit 20
J12_FAILED_OPERATION_ROLLED_BACK PASS no partial pattern row.
TRANSACTION_ROLLED_BACK
```

**PASS** — bentrok siswa diukur sebagai satu query join, bukan query per siswa; query `FOR UPDATE` teramati pada transaksi.

### J10 — Otorisasi komponen dan route

Output:

```text
J10_ALL_PUBLIC_METHODS_AUTHORIZED PASS methods=36 level=siswa => 403
J10_ROUTE_LEVEL_MIDDLEWARE PASS student=404 superadmin=200
```

`php artisan route:list --path=polajam -v` (pola sama pada tiga route berikutnya):

```text
GET|HEAD polajam polajam › App\Livewire\PolajamComponent
  web
  App\Http\Middleware\Authenticate
  App\Http\Middleware\CekAkunAktif
  App\Http\Middleware\ForcePasswordChange
  App\Http\Middleware\CheckUserLevel:superadmin
```

`jampelajaran`, `pengampu`, dan `jadwal` masing-masing juga tampil satu route dengan middleware yang sama. **PASS**.

### J11, J15, J16, J17 — Grid, state, halaman kosong, default jam

```text
J11_EMPTY_SCREENS PASS Pola, jam, jadwal render tanpa data pola.
J13_GRID_QUERY_SCALING PASS cells=10/30 queries=16/16
J15_PUBLIC_STATE_SCALAR_ONLY PASS json_encode={"filterSemester":16,"filterKelas":107,"hari":"","jam_ke":"","pengampu_idpengampu":"","idjadwal":""} length=100
J17_DEFAULT_NEXT_PERIOD PASS mulai=12:45 jam_ke=7
J14_PATTERN_TABLES_EMPTY_BEFORE_FIXTURES PASS {"polajam":0,"polajamhari":0,"jampelajaran":0}
J14_PATTERN_TABLES_DURING_FIXTURE {"polajam":1,"polajamhari":5,"jampelajaran":6}
TRANSACTION_ROLLED_BACK
```

**PASS** — grid 10 dan 30 sel masing-masing menjalankan 16 query; properti publik berisi state skalar, bukan koleksi model; keadaan kosong dirender tanpa error; nilai default berikutnya dimulai pukul 12:45 pada jam ke-7. Tiga tabel pola/jam kembali kosong sesudah rollback.

### J13 — Seeder menu dan idempotensi

Sebelum seeder:

```text
appmenucat=5
appmenunew=13
usermenu=22
superadmin_usermenu=15
polajam=0
polajamhari=0
jampelajaran=0
```

Perintah pertama: `php artisan db:seed --class=JadwalMenuSeeder`

```text
INFO  Seeding database.
appmenucat=6
appmenunew=17
usermenu=30
superadmin_usermenu=23
jadwal_menus=4
jadwal_usermenu=8
```

Perintah kedua dijalankan untuk menguji idempotensi. Output hitungan setelahnya:

```text
appmenucat=6
appmenunew=17
role=4
usermenu=30
jadwal_items=4
jadwal_usermenu=8
jadwal_superadmin_usermenu=8
jadwal_siswa_guru_usermenu=0
```

Delta yang terukur dari eksekusi pertama: kategori `+1`, item menu `+4`, paket `role` `+0`, `usermenu` `+8` (empat item untuk masing-masing dari dua superadmin). Eksekusi kedua menambah `0`. **PASS**.

Satu pemanggilan Tinker setelah seeder pertama gagal diparse karena closure PowerShell:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected T_OBJECT_OPERATOR on line 1
```

Seeder sudah selesai dengan sukses sebelum kegagalan perintah hitung tersebut. Hitungan dikoreksi menggunakan ekspresi Tinker sederhana dan berhasil seperti output di atas.

Saat menghitung ulang seluruh tabel di akhir, satu percobaan juga gagal karena bentuk kutip PowerShell menghapus tanda kutip string PHP:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected '.' on line 1
```

Percobaan dikoreksi menjadi ekspresi Tinker tanpa array/variabel; hasil mentah final tertera di bagian J15.

### J14 — Route, Blade, lint, Pint, dan Git baca-saja

`php artisan route:list --json` dihitung dengan parsing JSON PowerShell:

```text
JSON_TYPE=System.Object[]
ROUTE_COUNT=63
route_count=63
duplicate_method_uri_count=0
```

Keempat route jadwal tampil dengan `auth`, `cek.akun`, `force.password`, dan `check.level:superadmin`. Route admin `users`, `guru`, `siswa`, `kelas`, `kelassiswa`, `mapel`, `semester`, dan `tahunajaran` tetap terdaftar dengan middleware superadmin.

`php artisan view:clear`, `php artisan view:cache`, `php artisan view:clear`:

```text
INFO  Compiled views cleared successfully.
INFO  Blade templates cached successfully.
INFO  Compiled views cleared successfully.
```

`php -l` untuk semua file PHP yang dipertahankan:

```text
No syntax errors detected in app\Support\Hari.php
No syntax errors detected in app\Models\Polajam.php
No syntax errors detected in app\Models\Polajamhari.php
No syntax errors detected in app\Models\Jampelajaran.php
No syntax errors detected in app\Models\Pengampu.php
No syntax errors detected in app\Models\Jadwal.php
No syntax errors detected in app\Services\PolajamService.php
No syntax errors detected in app\Services\JampelajaranService.php
No syntax errors detected in app\Services\PengampuService.php
No syntax errors detected in app\Services\JadwalService.php
No syntax errors detected in app\Livewire\PolajamComponent.php
No syntax errors detected in app\Livewire\JampelajaranComponent.php
No syntax errors detected in app\Livewire\PengampuComponent.php
No syntax errors detected in app\Livewire\JadwalComponent.php
No syntax errors detected in database\migrations\2026_10_27_000001_create_polajam_table.php
No syntax errors detected in database\migrations\2026_10_27_000002_create_polajamhari_table.php
No syntax errors detected in database\migrations\2026_10_27_000003_create_jampelajaran_table.php
No syntax errors detected in database\migrations\2026_10_27_000004_create_pengampu_table.php
No syntax errors detected in database\migrations\2026_10_27_000005_create_jadwal_table.php
No syntax errors detected in database\seeders\JadwalMenuSeeder.php
No syntax errors detected in routes\web.php
```

Pint per file (`php vendor/bin/pint <path-file-spesifik>`), output setiap pemanggilan: `PASS ... 1 file`:

```text
app\Support\Hari.php — PASS 1 file
app\Models\Polajam.php — PASS 1 file
app\Models\Polajamhari.php — PASS 1 file
app\Models\Jampelajaran.php — PASS 1 file
app\Models\Pengampu.php — PASS 1 file
app\Models\Jadwal.php — PASS 1 file
app\Services\PolajamService.php — PASS 1 file
app\Services\JampelajaranService.php — PASS 1 file
app\Services\PengampuService.php — PASS 1 file
app\Services\JadwalService.php — PASS 1 file
app\Livewire\PolajamComponent.php — PASS 1 file
app\Livewire\JampelajaranComponent.php — PASS 1 file
app\Livewire\PengampuComponent.php — PASS 1 file
app\Livewire\JadwalComponent.php — PASS 1 file
database\migrations\2026_10_27_000001_create_polajam_table.php — PASS 1 file
database\migrations\2026_10_27_000002_create_polajamhari_table.php — PASS 1 file
database\migrations\2026_10_27_000003_create_jampelajaran_table.php — PASS 1 file
database\migrations\2026_10_27_000004_create_pengampu_table.php — PASS 1 file
database\migrations\2026_10_27_000005_create_jadwal_table.php — PASS 1 file
database\seeders\JadwalMenuSeeder.php — PASS 1 file
routes\web.php — PASS 1 file
storage\app\tmp-uji\JadwalVerification.php — PASS 1 file (sementara, kemudian dihapus)
```

Git baca-saja:

```text
branch: feature/beranda
 app/Http/Controllers/AuthController.php  |  7 ++++++-
 app/Http/Controllers/LoginController.php |  3 ++-
 app/Http/Kernel.php                      |  1 +
 app/Livewire/DashboardComponent.php      | 11 ++++++++++-
 routes/web.php                           | 12 ++++++++++--
5 files changed, 29 insertions(+), 5 deletions(-)
git diff --check: exit 0
```

`git diff --stat` hanya menghitung file terlacak; file baru Tahap 3A masih untracked pada saat output tersebut, sehingga daftar lengkapnya ada pada bagian “File dibuat”. Perubahan terlacak yang tampak pada stat berasal dari pekerjaan Beranda yang sudah ada, termasuk `routes/web.php`; tidak di-reset. Tidak ada pengukuran route baseline yang valid sebelum perubahan: percobaan PowerShell awal menghitung array JSON sebagai satu objek. Karena itu delta jumlah route **TIDAK BISA DIUKUR**; jumlah aktual sesudah perubahan adalah 63 dan tidak ada pasangan method/URI duplikat.

Percobaan awal skrip verifikasi menemukan bug yang kemudian diperbaiki: jadwal sel kelas bisa terganti tanpa ID sel eksplisit, dan refleksi test juga memanggil metode `confirm` publik dari trait dengan argumen uji yang salah. Output error awal:

```text
J7_CLASS_CONFLICT FAIL did not reject
J7_TEACHER_CONFLICT FAIL Siswa bentrok: J3A Siswa 1.
J7_STUDENT_CONFLICT FAIL Guru bentrok dengan kelas J3A-Induk-07479933 (J3A Mapel 2).
J8_ASSIGNMENT_DELETE_BLOCKED FAIL did not reject
J9_STUDENT_CONFLICT_ONE_JOIN_QUERY FAIL queries=0
J3A_FATAL TypeError: array_merge(): Argument #2 must be of type array, string given
```

Setelah mewajibkan ID eksplisit untuk mengganti sel, membatasi metode alert trait menjadi protected, dan menghitung query join yang benar, skrip akhir berjalan dengan exit code `0`; output PASS final tercatat pada J2–J17 di atas.

Saat menambahkan satu kasus uji terakhir, satu pemanggilan sementara mendahului pembuatan fixture dan gagal; transaksi tetap di-rollback, urutan fixture diperbaiki, dan uji akhir dijalankan ulang:

```text
J3A_FATAL ErrorException: Undefined variable $semester
C:\project-reyhan\template web\storage\app\tmp-uji\JadwalVerification.php:76
TRANSACTION_ROLLED_BACK
```

### J15 — Hitungan akhir setelah rollback

```text
users=5
siswa=1
guru=1
mapel=1
kelas=1
kelassiswa=1
tahunajaran=1
semester=1
usermenu=30
rolecat=2
role=4
polajam=0
polajamhari=0
jampelajaran=0
pengampu=0
jadwal=0
```

**PASS** — dibanding acuan, semua tabel selain `usermenu` tetap sama; `usermenu` bertambah 8, seluruhnya akibat menu seeder untuk dua superadmin. Data uji jadwal tidak tertinggal.

### J16 — Halaman tanpa data pola

```text
J11_EMPTY_SCREENS PASS Pola, jam, jadwal render tanpa data pola.
J14_PATTERN_TABLES_EMPTY_BEFORE_FIXTURES PASS {"polajam":0,"polajamhari":0,"jampelajaran":0}
```

**PASS** — halaman pola, jam, dan jadwal bisa menyiapkan state kosong; tabel jam tetap kosong setelah fixture rollback.

### J17 — Nilai awal jam berikut

```text
J17_DEFAULT_NEXT_PERIOD PASS mulai=12:45 jam_ke=7
```

**PASS** — nilai dihitung dari waktu selesai paling akhir dan nomor jam pelajaran terbesar pada pola.

### Belum diuji

**BELUM DIUJI (butuh browser):** alur modal tambah/edit/hapus, validasi pesan pada UI, navigasi “Atur Jam” ke pola terpilih, grid dan tombol di desktop/HP, dan kenyamanan visual kalender jadwal.
