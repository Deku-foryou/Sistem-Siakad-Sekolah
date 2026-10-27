# Laporan Perbaikan Ikon Sidebar

## Diagnosis I1 (sebelum perubahan)

### Sidebar dan composer

`resources/views/assets/sidebar.blade.php` berisi menu tetap superadmin secara langsung: Paket Menu (`fa-folder-open`), Kategori Menu (`fa-folder-tree`), Kelola Menu (`fa-folder-closed`), dan Users (`fa-users-rectangle`). Blok tersebut tidak diubah.

Composer `assets.sidebar` di `app/Providers/AppServiceProvider.php` mengambil kategori dan `Usermenu` milik user login, mengurutkan menurut urut menu, lalu meneruskan `appMenu` dan `appMenuCat` ke view. Ini tidak diubah.

Sebelum perubahan, ikon dinamis menggunakan pola berikut:

```blade
@if ($datas->appmenunew->icon)
    @if (str_contains($datas->appmenunew->icon, '<i'))
        <span class="me-3" style="display:inline-flex; width:16px; justify-content:center;">{!! $datas->appmenunew->icon !!}</span>
    @else
        <img src="{{ asset('asset_offline/icon/' . $datas->appmenunew->icon) }}" class="icon_sidebar me-3" style="max-width: 16px;">
    @endif
@else
    <i class="fa-regular fa-folder-open me-3"></i>
@endif
```

Jadi, nilai SVG diarahkan ke `public/asset_offline/icon/`, dan nilai kosong memakai ikon folder. Nilai Font Awesome berbentuk class salah diperlakukan sebagai nama berkas gambar; HTML `<i>` lama dirender tidak ter-escape. SVG lama `vector-square.svg` dan `1.svg` ada di folder tersebut. `.icon_sidebar` memberi gambar ukuran 17×17px serta filter warna abu-abu dan filter hijau saat aktif.

### Font Awesome

`resources/views/layout/mainlayout.blade.php` memasukkan `assets.style`; `resources/views/assets/style.blade.php` memuat:

```html
<link href="{{ asset('asset_offline/fontawesome/css/all.css') }}" rel="stylesheet">
```

Header `public/asset_offline/fontawesome/css/all.css` menyatakan Font Awesome Free **6.6.0**. Tombol Detail menggunakan `fa-eye`; pencarian `Select-String` pada CSS yang dilink menemukan `fa-eye`.

### Form Kelola Menu

`AppmenunewComponent::selectIcon()` mengisi `$this->icon` dengan nama file. `render()` membangun daftar LOV dengan `File::files(public_path('asset_offline/icon'))`, jadi LOV hanya berisi file ikon folder SVG. Rules `storeAppmenu()` dan `updateAppmenu()` tidak memvalidasi `icon`. `editAppmenu()` menyalin kolom database ke `$this->icon`; `updateAppmenu()` menyimpan nilai properti itu kembali. Dengan demikian, pengeditan hanya nama mempertahankan ikon selama nilai field tetap terisi. Field input teks `icon` sudah ada dan terikat pada properti yang sama; perubahan hanya memperjelas label/petunjuk untuk Font Awesome dan mempertahankan tombol pemilih SVG.

### SELECT awal semua menu

Perintah `php artisan tinker --execute="dump(DB::table('appmenunew')->select('idappmenunew', 'appmenunew', 'url', 'icon')->orderBy('idappmenunew')->get()->toArray());"`:

```text
array:17 [
  0 => {#5686
    +"idappmenunew": 34
    +"appmenunew": "Activity Log"
    +"url": "activitylog"
    +"icon": "vector-square.svg"
  }
  1 => {#5683
    +"idappmenunew": 35
    +"appmenunew": "Menu 1"
    +"url": "menu1"
    +"icon": "1.svg"
  }
  2 => {#5682
    +"idappmenunew": 36
    +"appmenunew": "Menu 2"
    +"url": "menu2"
    +"icon": "2.svg"
  }
  3 => {#5681
    +"idappmenunew": 37
    +"appmenunew": "Tahun Ajaran"
    +"url": "tahunajaran"
    +"icon": null
  }
  4 => {#5680
    +"idappmenunew": 38
    +"appmenunew": "Semester"
    +"url": "semester"
    +"icon": null
  }
  5 => {#5679
    +"idappmenunew": 39
    +"appmenunew": "Guru"
    +"url": "guru"
    +"icon": null
  }
  6 => {#5678
    +"idappmenunew": 40
    +"appmenunew": "Siswa"
    +"url": "siswa"
    +"icon": null
  }
  7 => {#5677
    +"idappmenunew": 41
    +"appmenunew": "Mapel"
    +"url": "mapel"
    +"icon": null
  }
  8 => {#5676
    +"idappmenunew": 42
    +"appmenunew": "Kelas"
    +"url": "kelas"
    +"icon": null
  }
  9 => {#5675
    +"idappmenunew": 43
    +"appmenunew": "Penempatan Siswa"
    +"url": "kelassiswa"
    +"icon": null
  }
  10 => {#5674
    +"idappmenunew": 46
    +"appmenunew": "Beranda Siswa"
    +"url": "berandasiswa"
    +"icon": null
  }
  11 => {#5673
    +"idappmenunew": 47
    +"appmenunew": "Beranda Guru"
    +"url": "berandaguru"
    +"icon": null
  }
  12 => {#5672
    +"idappmenunew": 48
    +"appmenunew": "Profil Saya"
    +"url": "setting"
    +"icon": null
  }
  13 => {#5671
    +"idappmenunew": 49
    +"appmenunew": "Pola Jam"
    +"url": "polajam"
    +"icon": "fa-regular fa-clock"
  }
  14 => {#5670
    +"idappmenunew": 50
    +"appmenunew": "Jam Pelajaran"
    +"url": "jampelajaran"
    +"icon": "fa-solid fa-clock"
  }
  15 => {#5669
    +"idappmenunew": 51
    +"appmenunew": "Pengampu"
    +"url": "pengampu"
    +"icon": "fa-solid fa-chalkboard-user"
  }
  16 => {#5668
    +"idappmenunew": 52
    +"appmenunew": "Jadwal"
    +"url": "jadwal"
    +"icon": "fa-regular fa-calendar-days"
  }
] // vendor\psy\psysh\src\ExecutionClosure.php(40) : eval()'d code:1
```

## Perubahan

- Sidebar kini merender class yang diawali `fa-` atau `fa ` sebagai elemen `<i>` dengan class yang di-escape. Class memiliki kotak 16×17px, font 17px, rata tengah, dan `me-3`, menggunakan warna ikon/sidebar yang sudah ada. Nilai berakhiran `.svg` masih memakai `<img>` dan `.icon_sidebar`; nilai kosong/tidak dikenal jatuh ke ikon folder lama. Tidak ada HTML icon mentah.
- Form tambah/edit mempertahankan field `icon` yang sama, dengan label `Icon (Font Awesome, opsional)` dan petunjuk `fa-solid fa-book`; LOV SVG tetap tersedia.
- Tabel Kelola Menu menampilkan pratinjau SVG, Font Awesome, atau fallback folder dengan format yang sesuai.
- `IkonMenuSeeder` hanya menjalankan update kolom `icon` untuk URL yang ditentukan. Seeder tidak dipanggil dari `DatabaseSeeder`.
- `AkademikMenuSeeder`, `DataIndukMenuSeeder`, `JadwalMenuSeeder`, dan `BerandaMenuSeeder` mengisi nilai icon tersebut untuk data baru; logika `firstOrCreate` dan assignment menu tidak diubah.
- Tidak ada perubahan pada composer, `AppmenunewComponent.php`, route, tabel selain kolom `appmenunew.icon`, atau file `.env`.

## Verifikasi I1–I6

### I2 — Font Awesome icon availability

Perintah PowerShell menggunakan `Select-String -Quiet` pada `public\asset_offline\fontawesome\css\all.css`:

```text
url | icon | status
tahunajaran | fa-solid fa-calendar-check | ADA
semester | fa-solid fa-layer-group | ADA
guru | fa-solid fa-person-chalkboard | ADA
siswa | fa-solid fa-user-graduate | ADA
mapel | fa-solid fa-book | ADA
kelas | fa-solid fa-school | ADA
kelassiswa | fa-solid fa-users | ADA
polajam | fa-solid fa-clock | ADA
jampelajaran | fa-solid fa-hourglass-half | ADA
pengampu | fa-solid fa-clipboard-user | ADA
jadwal | fa-solid fa-calendar-days | ADA
berandasiswa | fa-solid fa-house | ADA
berandaguru | fa-solid fa-house | ADA
setting | fa-solid fa-id-card | ADA
fa-eye=True
```

Tidak ada ikon yang perlu diganti.

### I3 — Seeder dan hitungan

Sebelum seeder, SELECT untuk 14 URL target:

```text
array:14 [
  0 => {#5848 +"url": "berandaguru" +"icon": null}
  1 => {#5843 +"url": "berandasiswa" +"icon": null}
  2 => {#5845 +"url": "guru" +"icon": null}
  3 => {#5841 +"url": "jadwal" +"icon": "fa-regular fa-calendar-days"}
  4 => {#5839 +"url": "jampelajaran" +"icon": "fa-solid fa-clock"}
  5 => {#5838 +"url": "kelas" +"icon": null}
  6 => {#5837 +"url": "kelassiswa" +"icon": null}
  7 => {#5836 +"url": "mapel" +"icon": null}
  8 => {#5835 +"url": "pengampu" +"icon": "fa-solid fa-chalkboard-user"}
  9 => {#5834 +"url": "polajam" +"icon": "fa-regular fa-clock"}
  10 => {#5833 +"url": "semester" +"icon": null}
  11 => {#5832 +"url": "setting" +"icon": null}
  12 => {#5831 +"url": "siswa" +"icon": null}
  13 => {#5830 +"url": "tahunajaran" +"icon": null}
]
```

Hitungan sebelum:

```text
appmenucat=6
appmenunew=17
usermenu=30
role=4
```

Perintah yang dijalankan sekali: `php artisan db:seed --class=IkonMenuSeeder`.

```text
INFO  Seeding database.
```

SELECT sesudah:

```text
array:14 [
  0 => {#5848 +"url": "berandaguru" +"icon": "fa-solid fa-house"}
  1 => {#5843 +"url": "berandasiswa" +"icon": "fa-solid fa-house"}
  2 => {#5845 +"url": "guru" +"icon": "fa-solid fa-person-chalkboard"}
  3 => {#5841 +"url": "jadwal" +"icon": "fa-solid fa-calendar-days"}
  4 => {#5839 +"url": "jampelajaran" +"icon": "fa-solid fa-hourglass-half"}
  5 => {#5838 +"url": "kelas" +"icon": "fa-solid fa-school"}
  6 => {#5837 +"url": "kelassiswa" +"icon": "fa-solid fa-users"}
  7 => {#5836 +"url": "mapel" +"icon": "fa-solid fa-book"}
  8 => {#5835 +"url": "pengampu" +"icon": "fa-solid fa-clipboard-user"}
  9 => {#5834 +"url": "polajam" +"icon": "fa-solid fa-clock"}
  10 => {#5833 +"url": "semester" +"icon": "fa-solid fa-layer-group"}
  11 => {#5832 +"url": "setting" +"icon": "fa-solid fa-id-card"}
  12 => {#5831 +"url": "siswa" +"icon": "fa-solid fa-user-graduate"}
  13 => {#5830 +"url": "tahunajaran" +"icon": "fa-solid fa-calendar-check"}
]
```

Hitungan sesudah:

```text
appmenucat=6
appmenunew=17
usermenu=30
role=4
```

**PASS** — hitungan tidak berubah; seeder hanya mengubah kolom `icon` untuk 14 URL.

### I4 — Render sidebar superadmin

Perintah `php storage\app\tmp-uji\IconMenuVerification.php` merender view sidebar untuk superadmin yang memiliki item Activity Log. Output:

```text
I4_ACTIVITY_SVG_BEFORE (legacy svg branch) <img src="http://localhost/192.168.1.100/asset_offline/icon/vector-square.svg" class="icon_sidebar me-3" style="max-width: 16px;">
I4_ACTIVITY_SVG_AFTER <li class="nav-item"> <a class="nav-link " href="/activitylog"> <img src="http://localhost/192.168.1.100/asset_offline/icon/vector-square.svg" class="icon_sidebar me-3" style="max-width: 16px;"> Activity Log </a> </li>
I4_JADWAL_AFTER <li class="nav-item"> <a class="nav-link " href="/jadwal"> <i class="fa-solid fa-calendar-days icon_sidebar_fa me-3" aria-hidden="true"></i> Jadwal </a> </li>
I4_JADWAL_ICON_MARKUP PASS <li class="nav-item"> <a class="nav-link " href="/jadwal"> <i class="fa-solid fa-calendar-days icon_sidebar_fa me-3" aria-hidden="true"></i> Jadwal </a> </li>
I4_ACTIVITY_SVG_UNCHANGED PASS before=<img src="http://localhost/192.168.1.100/asset_offline/icon/vector-square.svg" class="icon_sidebar me-3" style="max-width: 16px;">; after=<li class="nav-item"> <a class="nav-link " href="/activitylog"> <img src="http://localhost/192.168.1.100/asset_offline/icon/vector-square.svg" class="icon_sidebar me-3" style="max-width: 16px;"> Activity Log </a> </li>
I4_NULL_ICON_ITEM url=tahunajaran <li class="nav-item"> <a class="nav-link " href="/tahunajaran"> <i class="fa-regular fa-folder-open me-3"></i> Tahun Ajaran </a> </li>
I4_NULL_ICON_FALLBACK PASS <li class="nav-item"> <a class="nav-link " href="/tahunajaran"> <i class="fa-regular fa-folder-open me-3"></i> Tahun Ajaran </a> </li>
TRANSACTION_ROLLED_BACK
```

Sebelum adalah markup yang dibentuk cabang SVG lama dari nilai Activity Log yang tersimpan sebelum seeder; sesudah adalah output render Blade aktual. Null fallback diuji dengan set icon sementara dalam transaksi dan dikembalikan lewat rollback.

### I5 — Edit menu

Skrip yang sama menjalankan `editAppmenu()` dan `updateAppmenu()` dalam transaksi:

```text
I5_RENAME_PRESERVES_FONT_AWESOME PASS name=Jadwal temporary icon=fa-solid fa-calendar-days
I5_FORM_SAVES_FONT_AWESOME PASS stored_icon=fa-solid fa-book
TRANSACTION_ROLLED_BACK
```

### I6 — Regresi

`php artisan view:clear`, `php artisan view:cache`, `php artisan view:clear`:

```text
INFO  Compiled views cleared successfully.
INFO  Blade templates cached successfully.
INFO  Compiled views cleared successfully.
```

`php -l` dijalankan untuk masing-masing file PHP yang berubah:

```text
No syntax errors detected in database\seeders\AkademikMenuSeeder.php
No syntax errors detected in database\seeders\DataIndukMenuSeeder.php
No syntax errors detected in database\seeders\JadwalMenuSeeder.php
No syntax errors detected in database\seeders\BerandaMenuSeeder.php
No syntax errors detected in database\seeders\IkonMenuSeeder.php
No syntax errors detected in storage\app\tmp-uji\IconMenuVerification.php
```

Pint per file:

```text
database\seeders\AkademikMenuSeeder.php — PASS 1 file
database\seeders\DataIndukMenuSeeder.php — PASS 1 file
database\seeders\JadwalMenuSeeder.php — PASS 1 file
database\seeders\BerandaMenuSeeder.php — PASS 1 file
database\seeders\IkonMenuSeeder.php — PASS 1 file
storage\app\tmp-uji\IconMenuVerification.php — PASS 1 file
```

`git diff --stat` dibatasi pada file sidebar, form Kelola Menu, dan empat seeder lama:

```text
database/seeders/AkademikMenuSeeder.php            |  6 +++---
database/seeders/BerandaMenuSeeder.php             |  8 +++----
database/seeders/DataIndukMenuSeeder.php           | 12 +++++------
database/seeders/JadwalMenuSeeder.php              |  8 +++----
resources/views/assets/sidebar.blade.php           | 25 +++++++++++++++-------
resources/views/livewire/appmenunew-component.blade.php | 23 +++++++++++++-------
6 files changed, 49 insertions(+), 33 deletions(-)
```

Status file dalam lingkup:

```text
M  database/seeders/AkademikMenuSeeder.php
M  database/seeders/BerandaMenuSeeder.php
M  database/seeders/DataIndukMenuSeeder.php
M  database/seeders/JadwalMenuSeeder.php
M  resources/views/assets/sidebar.blade.php
M  resources/views/livewire/appmenunew-component.blade.php
?? database/seeders/IkonMenuSeeder.php
```

File seeder baru masih untracked sehingga memang tidak masuk `git diff --stat`; terlihat pada `git status --short`. `AppServiceProvider.php` dan `AppmenunewComponent.php` tidak diubah. `git diff --check`:

```text
warning: in the working copy of 'resources/views/livewire/jampelajaran-component.blade.php', CRLF will be replaced by LF the next time Git touches it
DIFF_CHECK_EXIT=0
```

Peringatan CRLF berasal dari perubahan lokal Tahap 3A sebelumnya, bukan file yang diubah pada perbaikan ikon; file itu tidak disentuh di tugas ini.

### Percobaan verifikasi yang diperbaiki

Percobaan awal Tinker untuk merender sidebar gagal akibat quoting PowerShell. Output error yang diterima:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected T_NS_SEPARATOR, expecting ')' on line 1
```

Percobaan berikutnya:

```text
InvalidArgumentException
Unexpected end of input
```

Query Tinker berikutnya juga kehilangan tanda petik nama tabel:

```text
Error Undefined constant "appmenunew".
```

Versi berikut menggunakan skrip PHP sementara untuk bootstrap Laravel, mengautentikasi superadmin yang memiliki menu Activity Log, dan menjalankan query SELECT. Skrip uji akhir berhasil dan dihapus setelah selesai.

Percobaan render pertama memilih superadmin tanpa assignment Activity Log dan mencari label kategori “Jadwal” sebagai item; kedua hal tersebut menghasilkan pemeriksaan harness yang keliru:

```text
I4_ACTIVITY_SVG_AFTER
I4_JADWAL_AFTER <li class="nav-item"><a class="nav-link " href="/users"><i class="fa-solid fa-users-rectangle me-3"></i> Users</a></li> <li class="nav-title">Jadwal Pelajaran</li>
I4_JADWAL_ICON_MARKUP FAIL <li class="nav-item"><a class="nav-link " href="/users"><i class="fa-solid fa-users-rectangle me-3"></i> Users</a></li> <li class="nav-title">Jadwal Pelajaran</li>
I4_ACTIVITY_SVG_UNCHANGED FAIL before=<img src="http://localhost/192.168.1.100/asset_offline/icon/vector-square.svg" class="icon_sidebar me-3" style="max-width: 16px;">; after=
I4_NULL_ICON_FALLBACK PASS <li class="nav-item"> <a class="nav-link " href="/tahunajaran"> <i class="fa-regular fa-folder-open me-3"></i> Tahun Ajaran </a> </li>
I5_RENAME_PRESERVES_FONT_AWESOME PASS name=Jadwal temporary icon=fa-solid fa-calendar-days
I5_FORM_SAVES_FONT_AWESOME PASS stored_icon=fa-solid fa-book
TRANSACTION_ROLLED_BACK
```

Skrip diperbaiki memilih user superadmin dengan menu Activity Log dan memilih elemen berdasarkan URL. Uji ulang I4–I5 di atas lulus dan berakhir dengan rollback.
