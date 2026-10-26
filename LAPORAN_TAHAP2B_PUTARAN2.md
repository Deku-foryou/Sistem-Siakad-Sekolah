# Laporan Tahap 2B — Putaran 2

## Ruang lingkup

Putaran ini membangun mesin import XLSX tanpa antarmuka untuk siswa, guru, mapel, kelas, dan penempatan. Import memakai service CRUD yang tersedia, memproses tiap baris secara terisolasi, menyimpan status proses, dan mendukung template serta ekspor baris gagal. UI dan route Putaran 3 tidak dikerjakan.

Tidak ada migration, package, atau perubahan `.env` dalam pekerjaan ini. Data uji dijalankan di dalam transaksi dan di-rollback.

## File

File baru:

- `app/Services/Import/ImportBase.php`
- `app/Services/Import/ImportFileReader.php`
- `app/Services/Import/ImportSiswa.php`
- `app/Services/Import/ImportGuru.php`
- `app/Services/Import/ImportMapel.php`
- `app/Services/Import/ImportKelas.php`
- `app/Services/Import/ImportPenempatan.php`
- `app/Services/Import/ImportRunner.php`
- `app/Services/Import/TemplateGenerator.php`
- `app/Services/Import/GagalGenerator.php`
- `app/Services/Import/RowImportException.php`

File service yang diselaraskan dengan model dan skema yang tersedia:

- `app/Services/KelasService.php`
- `app/Services/MapelService.php`
- `app/Services/PenempatanService.php`

## Verifikasi akhir V1–V15

Hitungan awal saat verifikasi:

```text
{"users":3,"siswa":0,"guru":0,"kelassiswa":0,"usermenu":16,"rolecat":2}
```

Output verifikasi yang dijalankan:

```text
V1 PASS | siswa:OK, guru:OK, mapel:OK, kelas:OK, penempatan:OK; Data berisi header saja, format teks dan contoh di Petunjuk.
V2-NUMERIC-EXPECTED PASS | numeric 59143361 => 0059143361
V2 PASS | total=9 berhasil=1 gagal=8 duplicate="duplikat dalam file (sama dengan baris 2)" raw_numeric=int formula_raw==1+1 telepon_warning=1
V3-PACKAGE PASS | username=0059143361 level=siswa wajib_ganti_password=true hash_check=true package_menu=attached
V2-NUMERIC PASS | numeric 59143362 => 0059143362; berhasil=1
V3-NO-PACKAGE PASS | berhasil=2 peringatan=["Paket menu 'Siswa' belum dibuat; akun dibuat tanpa menu"] usermenu=0
V4 PASS | baris_gagal=8 kolom_alasan=alasan kolom_baris=baris_asli nisn_bernol_teks=true formula_tidak_dieksekusi=true
V5 PASS | user_yatim=0 baris_lain_berhasil=1 baris_gagal=1
V6 PASS | NIP_teks_18_digit=diterima numeric_ge_1e15=ditolak NIP_ganda=ditolak berhasil=1 gagal=2
V7-MAPEL PASS | kode_ganda=ditolak berhasil=1 gagal=1
V7-KELAS-PENEMPATAN PASS | kelas berhasil=1 gagal=3; penempatan berhasil=3 gagal=2; mapel_pilihan_dua=diterima; siswa_nonaktif=ditolak; satu_induk_per_tahun=diterapkan
V8 PASS | ukuran=DITOLAK, signature_zip_rusak=DITOLAK, ekstensi=DITOLAK, sheet=DITOLAK, header_kurang=DITOLAK, header_lebih=DITOLAK, header_salah=DITOLAK, baris=DITOLAK
HASH_BENCHMARK PASS | percobaan=10 total_ms=3317.75 rata_rata_ms=331.77 ukuran_bagian=24
V9-BATCH PASS | total=60 berhasil=60 gagal=0 ukuran=24 waktu_bagian_ms=7964.12,7771.02,4150.69
V9-RESUME PASS | awal_berhasil=30 unggah_ulang_berhasil=30 sudah_terdaftar=30 total_unik=60
V10 PASS | import_kedua_ditolak=true kunci_setelah_selesai=terlepas kunci_setelah_error=terlepas
V11 PASS | log_name=import properties={"jenis":"mapel","berhasil":1,"gagal":0} causer_id=4
V12 PASS | usermenu_target_sebelum=0 sesudah=5 manual_sebelum=1 manual_sesudah=1 added_panggilan_2=0 processed=134
V13 PASS | NISN_ganda_tidak_membuat_user_yatim=PASS kelas_induk_per_tahun=PASS wali_unik=PASS placement_status_dan_jenis=PASS
V15 PASS | sebelum={"users":3,"siswa":0,"guru":0,"kelassiswa":0,"usermenu":16,"rolecat":2} sesudah={"users":3,"siswa":0,"guru":0,"kelassiswa":0,"usermenu":16,"rolecat":2}
TRANSACTION_ROLLED_BACK
IMPORT_FILES_CLEANED | folder imports dihapus; semua file uji dibersihkan
VERIFICATION_COMPLETE PASS
```

### V14 — Pint dan pemeriksaan sintaks

Output Pint setelah perubahan terakhir:

```text
........✓.....

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel
    FIXED   ............................................................................ 14 files, 1 style issue fixed
  ✓ app\Services\Import\TemplateGenerator.php                                                          ordered_imports
```

Pemeriksaan Pint tanpa menulis perubahan:

```text
  ..............

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel
    PASS   .................................................................................................. 14 files
```

`php -l` dijalankan pada ke-14 file di atas; output untuk tiap file:

```text
No syntax errors detected in app\Services\Import\ImportBase.php
No syntax errors detected in app\Services\Import\ImportFileReader.php
No syntax errors detected in app\Services\Import\ImportSiswa.php
No syntax errors detected in app\Services\Import\ImportGuru.php
No syntax errors detected in app\Services\Import\ImportMapel.php
No syntax errors detected in app\Services\Import\ImportKelas.php
No syntax errors detected in app\Services\Import\ImportPenempatan.php
No syntax errors detected in app\Services\Import\ImportRunner.php
No syntax errors detected in app\Services\Import\TemplateGenerator.php
No syntax errors detected in app\Services\Import\GagalGenerator.php
No syntax errors detected in app\Services\Import\RowImportException.php
No syntax errors detected in app\Services\KelasService.php
No syntax errors detected in app\Services\MapelService.php
No syntax errors detected in app\Services\PenempatanService.php
```

`git diff --check` selesai tanpa output/error.

## Keputusan dan batasan

- XLSX divalidasi berdasarkan ekstensi, signature, ukuran maksimal 2 MB, sheet `Data`, header, formula, kolom tambahan, serta batas 1000 baris data.
- Pemindaian data memakai koordinat sel yang telah dimuat, bukan loop atas semua nomor baris worksheet. Ini menghindari pemindaian rentang kosong yang sangat besar.
- NISN numerik dinormalisasi ke 10 digit; NIP yang presisinya berisiko hilang ketika dibaca sebagai angka ditolak.
- Kesalahan satu baris tidak membatalkan keberhasilan baris lain; transaksi per baris mencegah user yatim pada kegagalan penyimpanan.
- Lock import berlaku selama 600 detik dan dilepas pada penyelesaian maupun error. Ukuran bagian yang terukur adalah 24 baris.
- Angka benchmark adalah hasil pengukuran pada eksekusi verifikasi ini dan dapat berubah sesuai lingkungan.
- V14–V15 lulus. Semua data uji di-rollback dan hitungan enam tabel kembali sama dengan hitungan awal.
