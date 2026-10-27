# Laporan Beranda Siswa dan Guru

## Perubahan

- Ditambahkan `App\Support\BerandaUrl` sebagai pemetaan tujuan login, dashboard, dan redirect setelah ganti password.
- Ditambahkan `BerandasiswaComponent` dan `BerandaguruComponent`, masing-masing mengambil profil hanya dari relasi `Auth::user()->siswa()` atau `Auth::user()->guru()`.
- Query biodata dan daftar siswa wali memilih kolom secara eksplisit; daftar siswa guru dimuat eager-loaded dan dibatasi ke kelas induk aktif milik guru login.
- Ditambahkan middleware `CekAkunAktif` (`cek.akun`) setelah autentikasi di grup utama. Status siswa/guru diperiksa melalui `LoginBlockService`; sesi yang diblokir dihapus dan diarahkan ke login.
- Ditambahkan `BerandaMenuSeeder`; tidak dipanggil dari `DatabaseSeeder`. Menu profil memakai URL `setting`, yang sudah memiliki route `/setting`. Sidebar membentuk tautan menu sebagai `/{url}`. Route setting eksplisit, sehingga tidak memerlukan komponen Livewire `SettingComponent`.
- Seeder memakai `firstOrCreate`. Pembuatan `Appmenunew` dibungkus `unguarded` agar atribut label lolos mass-assignment tanpa mengubah model yang sudah ada.
- Tampilan menggunakan kelas `.detail-card`, `.detail-card-title`, `.detail-table`, `.detail-avatar`, dan tema hijau yang sudah digunakan modal Detail. Tidak ada CSS/package/migration baru.
- File PHP sumber yang dibuat/diubah: `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/LoginController.php`, `app/Http/Kernel.php`, `app/Livewire/DashboardComponent.php`, `app/Http/Middleware/CekAkunAktif.php`, `app/Livewire/BerandaguruComponent.php`, `app/Livewire/BerandasiswaComponent.php`, `app/Support/BerandaUrl.php`, `routes/web.php`, `database/seeders/BerandaMenuSeeder.php`.
- File Blade yang dibuat: `resources/views/livewire/berandaguru-component.blade.php`, `resources/views/livewire/berandasiswa-component.blade.php`.
- Laporan hasil: `LAPORAN_BERANDA.md`.
- Skrip verifikasi sementara `storage/app/tmp-uji/BerandaVerificationTest.php` dijalankan dalam transaksi dan dihapus sesudahnya.

## Keputusan dan risiko

- Untuk level yang belum memiliki beranda khusus, `BerandaUrl` memakai `/setting` agar request tidak berputar kembali ke `/dashboard`. Level yang ada pada spesifikasi (`superadmin`, `guru`, `siswa`) memakai tujuan khusus.
- Nama class mengikuti pemetaan route dinamis: `berandasiswa` → `BerandasiswaComponent`, `berandaguru` → `BerandaguruComponent`.
- Jumlah menu superadmin tidak bertambah dan tidak ada URL beranda/profil baru di `usermenu` superadmin.
- `firstOrCreate` mencegah duplikasi pada eksekusi berulang berurutan. Tidak ada indeks unik baru; eksekusi seeder secara bersamaan berpotensi mengalami race condition.
- Belum diuji di browser: visual desktop/HP, interaksi tautan dan sidebar, serta alur perubahan password secara UI.

## Verifikasi

### Awal sesi dan hitungan akhir

Perintah awal:

```text
php artisan tinker --execute "dump(['users' => DB::table('users')->count(), 'siswa' => DB::table('siswa')->count(), 'guru' => DB::table('guru')->count(), 'kelas' => DB::table('kelas')->count(), 'kelassiswa' => DB::table('kelassiswa')->count(), 'usermenu' => DB::table('usermenu')->count(), 'rolecat' => DB::table('rolecat')->count(), 'role' => DB::table('role')->count()]);"
```

Output:

```text
array:8 [
  "users" => 5
  "siswa" => 1
  "guru" => 1
  "kelas" => 1
  "kelassiswa" => 1
  "usermenu" => 16
  "rolecat" => 2
  "role" => 0
]
```

### B1 — Route beranda dan middleware

Perintah: `php artisan route:list --path=beranda -v`

```text
GET|HEAD       berandaguru ......................................... berandaguru › App\Livewire\BerandaguruComponent
                 ⇂ web
                 ⇂ App\Http\Middleware\Authenticate
                 ⇂ App\Http\Middleware\CekAkunAktif
                 ⇂ App\Http\Middleware\ForcePasswordChange
                 ⇂ App\Http\Middleware\MenuAccessMiddleware
  GET|HEAD       berandasiswa ...................................... berandasiswa › App\Livewire\BerandasiswaComponent
                 ⇂ web
                 ⇂ App\Http\Middleware\Authenticate
                 ⇂ App\Http\Middleware\CekAkunAktif
                 ⇂ App\Http\Middleware\ForcePasswordChange
                 ⇂ App\Http\Middleware\MenuAccessMiddleware

                                                                                                    Showing [2] routes
```

**PASS** — route list menunjukkan middleware `auth`, `cek.akun`, `force.password`, dan `menu.access` (output Laravel menampilkan nama class middleware).

### B2 — Tujuan login dan `/dashboard`

Perintah: PHPUnit sementara di `storage/app/tmp-uji/BerandaVerificationTest.php`, memakai `DB::beginTransaction()` dan `rollBack()` di `finally`.

```text
B2_LOGIN_TARGETS superadmin=/dashboard guru=/berandaguru siswa=/berandasiswa PASS
B2_DASHBOARD_REDIRECTS guru=/berandaguru siswa=/berandasiswa superadmin_status=200 PASS
```

**PASS** — helper menghasilkan tujuan yang diminta; request dashboard guru/siswa redirect ke beranda, dashboard superadmin merespons 200.

### B3 — Beranda siswa

```text
B3_STUDENT_ACTIVE {"siswa":{"idsiswa":618,"nisn":"9520757774","nis":null,"nama":"Siswa Verifikasi A","tahun_masuk":null,"status":"aktif"},"kelas":{"idkelas":87,"nama":"Kelas Verifikasi A","tingkat":"10","jenis":"induk"},"wali":"Guru Verifikasi A"} PASS
B3_STUDENT_ROUTE status=200 own_name_present=yes PASS
B3_STUDENT_NO_CLASS=Belum ditempatkan di kelas PASS
B3_STUDENT_NO_ACTIVE_YEAR=Belum ada tahun ajaran aktif; no error PASS
B3_STUDENT_WITHOUT_ROW=Data siswa belum terhubung. Hubungi admin sekolah. PASS
```

**PASS** — data view berisi kelas induk aktif dan wali; keadaan tanpa kelas/tahun aktif/profil siswa ditampilkan tanpa error.

### B4 — Isolasi siswa dan otorisasi komponen

```text
B4_ISOLATION {"login_A_idsiswa":618,"login_B_idsiswa":619,"component_public_properties":[],"render_parameters":0} PASS
B4_COMPONENT_AUTH siswa_as_guru/superadmin=403,403 guru_as_siswa=403 PASS
B4_VIEW_DATA_ONLY_LOGIN_STUDENT=Siswa Verifikasi A PASS
```

**PASS** — profil mengikuti akun login, komponen tidak mempunyai properti publik atau parameter render untuk mengganti siswa, serta level yang salah ditolak 403.

### B5 — Beranda guru dan batas kelas

```text
B5_TEACHER_ROSTER {"classes":["Kelas Verifikasi A"],"student_count":3,"students":["Siswa Verifikasi A","Siswa Verifikasi C","Siswa Verifikasi D"],"other_teacher_class_included":false} PASS
B5_TEACHER_ROUTE status=200 roster_is_scoped=yes PASS
B5_TEACHER_NO_CLASS=Anda tidak menjadi wali kelas; no_profile=Data guru belum terhubung. Hubungi admin sekolah. PASS
```

**PASS** — kelas wali berisi tiga siswa; kelas guru lain dan siswanya tidak ikut. Guru non-wali dan guru tanpa baris profil mendapat pesan masing-masing.

### B6 — Kolom biodata dan daftar siswa yang dipilih

```text
B6_BIODATA_COLUMNS=idsiswa,user_iduser,nisn,nis,nama,tahun_masuk,status
B6_BIODATA_SQL=select `idsiswa`, `user_iduser`, `nisn`, `nis`, `nama`, `tahun_masuk`, `status` from `siswa` where `siswa`.`user_iduser` = ? and `siswa`.`user_iduser` is not null limit 1
B6_ROSTER_SQL=select `idsiswa`, `nisn`, `nama`, `jenis_kelamin` from `siswa` where `siswa`.`idsiswa` in (618, 620, 621)
B6_SENSITIVE_COLUMNS=ABSENT PASS
```

**PASS** — SQL dan kolom terpilih tidak memuat password, hash, remember token, alamat, nama orang tua, atau telepon orang tua.

### B7 — Akses silang dan halaman admin

```text
B7_CROSS_ACCESS {"siswa_to_admin":{"/users":404,"/guru":404,"/siswa":404,"/kelas":404,"/tahunajaran":404,"/kelassiswa":404},"siswa_to_berandaguru":404,"guru_to_berandasiswa":404} PASS
```

**PASS** — akses siswa ke halaman superadmin dan ke beranda guru, serta akses guru ke beranda siswa ditolak 404 oleh mekanisme otorisasi route.

### B8 — Penguncian akun nonaktif dan query middleware

```text
B8_MIDDLEWARE active_student_queries=1 inactive_student_queries=1 inactive_student_redirect=http://localhost/login message=Akun tidak dapat digunakan. Hubungi admin sekolah. inactive_guru_redirect=http://localhost/login inactive_guru_queries=1 superadmin_queries=0 actual_student_request=/login actual_guru_request=/login PASS
```

**PASS** — uji mengukur kontribusi `CekAkunAktif` langsung melalui listener query: satu query status untuk siswa/guru, nol untuk superadmin. Request berikutnya setelah status berubah mengeluarkan sesi dan mengarahkan ke login dengan pesan tersebut.

### B9 — Seeder menu dan sinkronisasi

Perintah: `php artisan db:seed --class=BerandaMenuSeeder`

```text
INFO  Seeding database.
```

Hitungan sebelum seeder (output Tinker):

```text
appmenucat=4
appmenunew=10
rolecat=2
role=0
usermenu=16
superadmin_usermenu=15
Akihabara_usermenu=0
Hibiki_usermenu=0
```

Hitungan setelah seeder (output Tinker):

```text
appmenucat=5
appmenunew=13
rolecat=2
role=4
usermenu=22
superadmin_usermenu=15
Akihabara Kejosuke=2
Hibiki Yutaa=2
budi=3
Beranda.urut=-1
```

Relasi paket yang terbaca:

```text
Guru | Beranda Guru | berandaguru
Guru | Profil Saya | setting
Siswa | Beranda Siswa | berandasiswa
Siswa | Profil Saya | setting
```

URL baru pada `usermenu` superadmin dan hitungannya:

```text
new_beranda_or_setting_usermenu_for_superadmin=0
superadmin_usermenu_total=15
```

Sinkronisasi kedua:

```text
array:3 [
  "processed" => 2
  "added" => 0
  "paket_tidak_ada" => false
]
array:3 [
  "processed" => 1
  "added" => 0
  "paket_tidak_ada" => false
]
```

**PASS** — baris bertambah: `appmenucat=1`, `appmenunew=3`, `rolecat=0`, `role=4`, `usermenu=6`. Hibiki dan Akihabara mendapat masing-masing dua menu; hitungan usermenu superadmin tetap 15 dan tidak mendapat URL beranda/profil.

### B10 — Regresi halaman, login/logout, dan ganti password

Output request halaman admin sebagai superadmin:

```text
B10_ADMIN_PAGES {"\/users":200,"\/guru":200,"\/siswa":200,"\/kelas":200,"\/kelassiswa":200} PASS
B10_FORCE_PASSWORD redirect=/setting PASS
B10_PASSWORD_CHANGE redirect=/berandasiswa PASS
B10_LOGOUT redirect=/login PASS
B10_LOGIN_FLOW_CHECK=Auth::login + BerandaUrl target; HTTP GET /dashboard; route:list confirms login/logout routes. PASS
```

`route:list --path=login -v` menampilkan `GET|HEAD login` dan `POST login`; `route:list --path=logout -v` menampilkan `POST logout`. `route:list --path=import -v` tetap menampilkan `import/gagal/{uuid}` dan `import/template/{jenis}` dengan middleware superadmin. `/setting` terdaftar sebagai `AuthController@index`, jadi link menu Profil Saya menuju route yang tersedia.

**PASS** — halaman admin yang diminta merespons 200 untuk superadmin; paksa ganti password tetap menuju `/setting`; setelah password berhasil diganti, siswa diarahkan ke `/berandasiswa`; logout menuju `/login`. Login sukses disimulasikan memakai `Auth::login` dan helper sesuai opsi verifikasi.

### B11 — Blade, lint, Pint, dan route

Output Blade:

```text
INFO  Compiled views cleared successfully.
INFO  Blade templates cached successfully.
INFO  Compiled views cleared successfully.
```

Output `php -l` untuk setiap file PHP yang dibuat/diubah:

```text
No syntax errors detected in app\Http\Controllers\LoginController.php
No syntax errors detected in app\Http\Controllers\AuthController.php
No syntax errors detected in app\Http\Kernel.php
No syntax errors detected in app\Livewire\DashboardComponent.php
No syntax errors detected in app\Http\Middleware\CekAkunAktif.php
No syntax errors detected in app\Livewire\BerandaguruComponent.php
No syntax errors detected in app\Livewire\BerandasiswaComponent.php
No syntax errors detected in app\Support\BerandaUrl.php
No syntax errors detected in routes\web.php
No syntax errors detected in database\seeders\BerandaMenuSeeder.php
No syntax errors detected in storage\app\tmp-uji\BerandaVerificationTest.php
```

Perintah Pint dijalankan masing-masing satu file:

```text
php vendor/bin/pint app\Http\Controllers\LoginController.php — PASS, 1 file
php vendor/bin/pint app\Http\Controllers\AuthController.php — PASS, 1 file
php vendor/bin/pint app\Http\Kernel.php — PASS, 1 file
php vendor/bin/pint app\Livewire\DashboardComponent.php — PASS, 1 file
php vendor/bin/pint app\Http\Middleware\CekAkunAktif.php — PASS, 1 file
php vendor/bin/pint app\Livewire\BerandaguruComponent.php — PASS, 1 file
php vendor/bin/pint app\Livewire\BerandasiswaComponent.php — PASS, 1 file
php vendor/bin/pint app\Support\BerandaUrl.php — PASS, 1 file
php vendor/bin/pint routes\web.php — PASS, 1 file
php vendor/bin/pint database\seeders\BerandaMenuSeeder.php — PASS, 1 file
php vendor/bin/pint storage\app\tmp-uji\BerandaVerificationTest.php — PASS, 1 file (sementara, kemudian dihapus)
```

Output route menampilkan dua route beranda beserta middleware pada B1. Login, logout, setting, dashboard, dan route import juga tampil pada B1/B10.

**PASS** — kompilasi Blade, seluruh lint, dan Pint berhasil. Skrip uji sementara telah dihapus (`TEMP_TEST_EXISTS=False`).

### B12 — Hitungan setelah rollback dan perubahan seeder

Output akhir:

```text
array:8 [
  "users" => 5
  "siswa" => 1
  "guru" => 1
  "kelas" => 1
  "kelassiswa" => 1
  "usermenu" => 22
  "rolecat" => 2
  "role" => 4
]
```

Dibanding acuan awal, `users`, `siswa`, `guru`, `kelas`, `kelassiswa`, dan `rolecat` tidak berubah. Selisih yang diminta dari seeder: `usermenu +6`, `role +4`; kategori/item menu bertambah masing-masing 1/3. PHPUnit menghasilkan `TRANSACTION_ROLLED_BACK`.

**PASS** — tidak ada fixture uji yang tertinggal.

### Catatan perintah uji yang diperbaiki

- Percobaan awal Tinker gagal akibat quoting PowerShell. Output parse error yang terlihat: `PHP Parse error: Syntax error, unexpected '=' on line 1` dan `PHP Parse error: Unexpected character "" (ASCII 2) on line 1`. Hitungan berhasil setelah memakai satu ekspresi `dump([...])`.
- Percobaan PHPUnit awal membentuk URL `http://localhost/192.168.1.100/dashboard`, lalu menghasilkan `Symfony\Component\HttpKernel\Exception\NotFoundHttpException`. Request uji diperbaiki memakai URL absolut `http://localhost/...`; rute beranda dan dashboard kemudian lulus.
- Uji sementara awal juga memakai assertion pada objek test case yang tidak tersedia (`Call to undefined method Tests\Temporary\BerandaVerificationTest::assertSessionHas()`); diperbaiki dengan assertion pada objek response. Model pengguna fixture juga di-refresh sebelum menguji middleware wajib ganti password.

### Belum diuji

- Belum diuji di browser: tampilan beranda siswa/guru pada desktop dan HP, tautan tombol, sidebar tiap level, serta alur ganti password dari UI.
