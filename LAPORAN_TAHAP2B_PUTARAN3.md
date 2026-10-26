# Laporan Tahap 2B — Putaran 3

## Konteks

- Branch yang diperiksa: `feature/import`. Git hanya dibaca; tidak ada checkout atau commit.
- Tidak membaca/mengubah `.env`, menjalankan migration, menambah package, atau membiarkan data uji di database.
- Pengujian data yang menjadi hasil akhir memakai transaksi dan rollback di `finally`. Satu probe awal membuat fixture `PROBE01` di luar transaksi; fixture itu dibersihkan secara spesifik dan tidak tersisa (rinciannya pada bagian percobaan yang gagal). File uji sementara dibersihkan.

## Verifikasi

### Langkah 0 — sebelum antarmuka

Hitungan awal baca-saja:

```text
{"users":3,"siswa":0,"guru":0,"kelassiswa":0,"usermenu":16,"rolecat":2}
```

**0a. Cache**

Perintah: `php artisan config:show cache.default`

```text
cache.default ................................................................................................. file
CACHE_LOCK_PROVIDER=yes
```

Driver `file` mendukung lock; tabel `cache_locks` tidak relevan untuk driver ini. Tidak mengubah `.env`.

**0b. V12 `processed=134`**

Skrip baca-saja di dalam transaksi:

```text
CACHE_LOCK_PROVIDER=yes
V12_CURRENT siswa_level_users=1 linked_siswa_rows=0
V12_CURRENT_USER id=15 username=budi level=siswa
TRANSACTION_ROLLED_BACK
```

`PaketMenuService::sinkronkanSemua($level)` mengambil akun dengan `User::where('level', $level)` lalu memprosesnya dalam chunk; ia tidak mengubah kolom `level`. Jadi `processed=134` pada laporan V12 berasal dari jumlah akun pada level yang diminta saat eksekusi historis itu. **TIDAK BISA DIUKUR:** identitas/asal masing-masing dari 134 akun pada saat itu karena fixture atau snapshot transaksi historis tidak tersedia; hitungan saat sesi ini adalah satu akun level siswa.

**0c. Diff service dan operasi CRUD**

`git diff HEAD^ HEAD` (baca saja):

- `KelasService`: mengganti referensi model/kolom lama yang tidak sesuai skema dengan `Tahunajaran`, `tahunajaran_idtahunajaran`, `guru_idguru`, dan validasi jenis kelas, wali aktif, serta keunikan kelas/wali.
- `MapelService`: menangkap `$data` pada closure transaksi sehingga `kelompok` ikut tersimpan.
- `PenempatanService`: memakai model/tabel aktual `Kelassiswa`, relasi `Tahunajaran`/`Kelas`, serta pemeriksaan siswa aktif, kelas induk, dan wali aktif.

`Livewire::test()` dicoba untuk CRUD tetapi gagal sebelum assertion:

```text
Trying to access array offset on value of type null
at vendor\livewire\livewire\src\Mechanisms\HandleComponents\HandleComponents.php:88
Tests: 1 failed (0 assertions)
```

Sebagai verifikasi jalur komponen tanpa HTTP, lifecycle Livewire `HandleComponents::mount/update` diuji dalam transaksi:

```text
P6 siswa PASS created=1
P6 guru PASS created=1
P6 mapel PASS created=1
P6 kelas PASS induk=1 mapel_pilihan=1
P6 penempatan PASS tambah=1 keluarkan=1
TRANSACTION_ROLLED_BACK
```

**V2 — 12 kasus per baris**

Skrip XLSX menjalankan seluruh kasus di dalam transaksi:

```text
V2_ROWS|baris Excel|input|hasil|alasan
2|nisn='1234567001'|berhasil|Import berhasil|PASS
3|nisn=59143361|berhasil|Import berhasil|PASS
4|nisn='1.98E+17'|gagal|NISN tidak boleh mengandung huruf E, titik, atau koma.|PASS
5|nisn='12345678901'|gagal|NISN maksimal 10 digit.|PASS
6|nisn='12345A7890'|gagal|NISN harus berupa angka.|PASS
7|nisn='1234567001'|gagal|duplikat dalam file (sama dengan baris 2)|PASS
8|nisn='1234567890'|gagal|NISN sudah terdaftar.|PASS
9|tanggal_lahir='31/02/2009'|gagal|Tanggal lahir harus berformat dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd, atau serial Excel yang valid.|PASS
10|nama=""|gagal|Kolom nama wajib diisi dengan teks.|PASS
11|jenis_kelamin=X|gagal|Jenis kelamin tidak valid.|PASS
12|nisn='=1+1'|gagal|Kolom nisn berisi formula; formula tidak diizinkan.|PASS
13|tanggal_lahir(serial)=40344.0|berhasil|Import berhasil|PASS
V2_SUMMARY PASS berhasil=3 gagal=9 selesai=true
TRANSACTION_ROLLED_BACK
```

### P1 — route dan unduhan template

`php artisan route:list --path=import -v`:

```text
GET|HEAD import/gagal/{uuid} ... ImportDownloadController@gagal
 ⇂ web
 ⇂ App\Http\Middleware\Authenticate
 ⇂ App\Http\Middleware\ForcePasswordChange
 ⇂ App\Http\Middleware\CheckUserLevel:superadmin
GET|HEAD import/template/{jenis} ... ImportDownloadController@template
 ⇂ web
 ⇂ App\Http\Middleware\Authenticate
 ⇂ App\Http\Middleware\ForcePasswordChange
 ⇂ App\Http\Middleware\CheckUserLevel:superadmin
Showing [2] routes
```

Output unduhan/controller:

```text
P1_TEMPLATE siswa PASS content_type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet signature=504b
P1_TEMPLATE guru PASS content_type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet signature=504b
P1_TEMPLATE mapel PASS content_type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet signature=504b
P1_TEMPLATE kelas PASS content_type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet signature=504b
P1_TEMPLATE penempatan PASS content_type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet signature=504b
P1_DENIED siswa PASS status=403
P1_DENIED guru PASS status=403
P1_TRAVERSAL PASS status=404
TRANSACTION_ROLLED_BACK
```

### P2 — lima halaman import

File XLSX valid diproses melalui lifecycle `HandleComponents::mount/update` sebagai superadmin. Untuk menguji hitungan gagal tanpa membuat data, setiap file memuat baris yang sengaja tidak lengkap; impor siswa memuat 60 baris tersebut. Properti trait setelah 60 baris berukuran 262 byte (batas uji `<1024`).

```text
P2 siswa PASS total=60 berhasil=0 gagal=60 selesai=true iterasi=4 rata_rata_hash_ms=467.51 ukuran_bagian=17 properti_bytes=262 properti={"fileImport":null,"importUuid":"…","importJenis":"siswa","importOffset":60,"importTotal":60,"importBerhasil":0,"importGagal":60,"importSelesai":true,"importBerjalan":false,"importPeringatan":[],"importPesan":"Import selesai."}
P2 guru PASS total=1 berhasil=0 gagal=1 selesai=true iterasi=1 rata_rata_hash_ms=441.65 ukuran_bagian=18 properti_bytes=258
P2 mapel PASS total=1 berhasil=0 gagal=1 selesai=true iterasi=1 rata_rata_hash_ms=419.19 ukuran_bagian=19 properti_bytes=259
P2 kelas PASS total=1 berhasil=0 gagal=1 selesai=true iterasi=1 rata_rata_hash_ms=463.32 ukuran_bagian=17 properti_bytes=259
P2 penempatan PASS total=1 berhasil=0 gagal=1 selesai=true iterasi=1 rata_rata_hash_ms=442.29 ukuran_bagian=18 properti_bytes=264
TRANSACTION_ROLLED_BACK
```

`Livewire::test()` sendiri belum bisa dipakai karena kegagalan snapshot null pada Langkah 0c; pengujian di atas menggunakan mount/update Livewire langsung dan bukan simulasi browser.

### P3 — otorisasi method langsung

Untuk masing-masing level `siswa` dan `guru`, method `bukaImport`, `mulaiImport`, `lanjutImport`, `batalImport`, dan `tutupImport` diuji pada kelima komponen; `sinkronkanPaket` diuji pada Siswa dan Guru.

```text
P3 siswa: 25/25 method import = 403 PASS; 2/2 method sinkron = 403 PASS
P3 guru: 25/25 method import = 403 PASS; 2/2 method sinkron = 403 PASS
P3 total: 54/54 penolakan = PASS
TRANSACTION_ROLLED_BACK
```

### P4–P5 — lock, batas waktu, dan kelanjutan

```text
P4_SECOND_LOCK PASS message=Import jenis mapel sedang berjalan. Coba lagi setelah proses tersebut selesai.
P4_COMPLETE PASS selesai=true lock_reacquired=true
P4_CANCEL PASS dibatalkan=true lock_reacquired=true
P4_ERROR_RELEASE PASS error=Ukuran bagian harus antara 1 dan 100 baris. selesai=true lock_reacquired=true
P5_BATCH_MEASURED rata_rata_hash_ms=432.65 ukuran_bagian=18 batas=5..50
P5_TIME_PART PASS offset=1 berhasil=1 selesai=false processed_calls=1 slow_row_complete=true
P5_RESUME_NO_DUPLICATE PASS offset=8 berhasil=8 gagal=0 calls=8 unique=8
TRANSACTION_ROLLED_BACK
```

Hash diukur tiga kali saat mulai; ukuran bagian disimpan pada manifest dan dibatasi 5–50. Simulasi baris pertama yang memerlukan lebih dari 8 detik menunjukkan bagian berhenti setelah satu baris utuh, lalu seluruh delapan baris terhitung tepat sekali saat dilanjutkan.

### P6 — regresi CRUD komponen

```text
P6 siswa PASS created=1
P6 guru PASS created=1
P6 mapel PASS created=1
P6 kelas PASS induk=1 mapel_pilihan=1
P6 penempatan PASS tambah=1 keluarkan=1
TRANSACTION_ROLLED_BACK
```

### P7 — sinkron paket melalui komponen

Lima akun siswa uji dan satu akun siswa yang sudah ada diproses. Menu khusus uji terpasang pada semua lima akun uji; pemanggilan kedua idempoten.

```text
P7_SYNC_FIRST PASS test_users_menu=5/5 total_menu_links=6
alert: 6 user diproses, 6 baris menu ditambahkan.
P7_SYNC_SECOND PASS test_users_menu=5/5
alert: 6 user diproses, 0 baris menu ditambahkan.
TRANSACTION_ROLLED_BACK
```

### P8 — Pint, sintaks, dan diff

Pint hanya dijalankan pada file PHP yang dibuat/diubah sesi ini:

```text
app\Http\Controllers\ImportDownloadController.php
app\Livewire\Concerns\HandlesImport.php
app\Livewire\SiswaComponent.php
app\Livewire\GuruComponent.php
app\Livewire\MapelComponent.php
app\Livewire\KelasComponent.php
app\Livewire\KelassiswaComponent.php
app\Services\Import\GagalGenerator.php
app\Services\Import\ImportRunner.php
routes\web.php
```

Output:

```text
FIXED ........................................................................... 10 files, 7 style issues fixed
```

`php -l` pada seluruh 10 file:

```text
No syntax errors detected in app\Http\Controllers\ImportDownloadController.php
No syntax errors detected in app\Livewire\Concerns\HandlesImport.php
No syntax errors detected in app\Livewire\SiswaComponent.php
No syntax errors detected in app\Livewire\GuruComponent.php
No syntax errors detected in app\Livewire\MapelComponent.php
No syntax errors detected in app\Livewire\KelasComponent.php
No syntax errors detected in app\Livewire\KelassiswaComponent.php
No syntax errors detected in app\Services\Import\GagalGenerator.php
No syntax errors detected in app\Services\Import\ImportRunner.php
No syntax errors detected in routes\web.php
GIT_DIFF_CHECK PASS
```

### P9 — hitungan akhir

```text
P9 PASS actual={"users":3,"siswa":0,"guru":0,"kelassiswa":0,"usermenu":16,"rolecat":2} expected={"users":3,"siswa":0,"guru":0,"kelassiswa":0,"usermenu":16,"rolecat":2}
TRANSACTION_ROLLED_BACK
```

Folder `storage/app/private/imports` kosong setelah pembersihan; file skrip uji sementara di `storage/app/tmp-uji` dihapus.

## Percobaan yang gagal dan koreksi

- Pemanggilan awal Tinker untuk cleanup gagal karena quoting PowerShell:

  ```text
  Psy\Exception\ParseErrorException
  PHP Parse error: Syntax error, unexpected '=', expecting ';' on line 1
  ```

  Cleanup dijalankan ulang dengan skrip PHP terpisah: `cleanup_deleted=1 remaining=0`. Baris `PROBE01` adalah fixture probe yang dibuat oleh pemanggilan lifecycle awal di luar transaksi; baris itu dihapus secara spesifik. Probe selanjutnya dan semua pengujian data dijalankan dalam transaksi rollback.
- Mount awal memakai alias `mapel` dan gagal:

  ```text
  Livewire\Exceptions\ComponentNotFoundException
  Unable to find component: [mapel]
  ```

  Pengujian lifecycle diperbaiki dengan class `App\Livewire\MapelComponent`.
- Percobaan awal P4 memakai header guru yang tidak lengkap:

  ```text
  InvalidArgumentException: Header baris 1 harus berisi tepat: alamat, jenis_kelamin, nama, nip, tanggal_lahir, telepon, tempat_lahir. Urutan kolom boleh berbeda.
  ```

  Fixture diperbaiki menggunakan header yang dikembalikan `ImportBase::dariJenis('guru')->kolom()`; pengujian P4/P5 berikutnya lulus.
- `Livewire::test()` tetap gagal dengan snapshot null sebagaimana dicatat pada Langkah 0c. P2/P6 dijalankan melalui lifecycle komponen Livewire langsung sebagai alternatif terukur; jalur HTTP Livewire perlu investigasi terpisah.

## Keputusan, risiko, dan belum diuji

- Ukuran batch tidak dikunci pada angka tetap; nilainya mengikuti rata-rata tiga pengukuran `Hash::make` pada server aktif, lalu dibatasi 5–50.
- Route/controller memeriksa level superadmin; method import dan sinkron juga memeriksa level secara langsung.
- Jalur HTTP Livewire belum teruji melalui `Livewire::test()` karena masalah snapshot null. Pengujian mount/update komponen tidak menggantikan pengujian browser.
- Belum diuji (butuh browser):
  - modal terbuka dan tertutup;
  - pembaruan bar progres dan loop Alpine;
  - unduhan template dan file baris gagal melalui browser;
  - tampilan di layar HP.
