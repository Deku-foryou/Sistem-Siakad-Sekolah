# Laporan Jadwal V2

## File diubah

- `app/Support/Hari.php` — menambahkan Sabtu dan Minggu serta memperbarui pesan rentang hari.
- `app/Services/PolajamService.php` — menerima pemetaan hari 1–7.
- `app/Services/JadwalService.php` — memperbarui validasi hari dan menambahkan `simpanDenganMapel()` yang membungkus pembuatan pengampu dan penyimpanan jadwal dalam transaksi; validasi bentrok tetap memakai `simpan()`.
- `app/Livewire/JadwalComponent.php` — memilih Mapel/Guru, otomatis mengisi dan mengunci Guru untuk pengampu terdaftar, serta membentuk grid dari hari yang dipetakan.
- `resources/views/livewire/jadwal-component.blade.php` — menampilkan kolom hari terpetakan dan modal Mapel/Guru dengan keadaan kosong.

Tidak ada migration. `hari` pada `polajamhari` dan `jadwal` bertipe `tinyint unsigned`, yang cukup untuk nilai 1–7. Tidak ada perubahan pada perilaku `PengampuService`, route, atau database permanen.

## Verifikasi

Skrip probe sementara dibuat di `storage/app/tmp-uji/JadwalV2Probe.php`. Seluruh fixture dibuat dalam satu `DB::beginTransaction()` dan dikembalikan dengan `DB::rollBack()` di `finally`. Data pola, pemetaan, dan jam awal dibandingkan kembali setelah rollback.

### H1 — Inventaris sebelum perubahan

| File:baris | Teks/kode lama | Perubahan |
|---|---|---|
| `app/Support/Hari.php:12` | `5 => 'Jumat'` (hari terakhir pada daftar) | Tambah Sabtu (6) dan Minggu (7). |
| `app/Support/Hari.php:17` | `Hari harus Senin sampai Jumat.` | `Hari harus Senin sampai Minggu.` |
| `app/Services/PolajamService.php:25` | Validasi hari `[1, 2, 3, 4, 5]` | Validasi menggunakan `array_keys(Hari::NAMA)`. |
| `app/Services/PolajamService.php:26` | `Pilih minimal satu hari dari Senin sampai Jumat.` | `Pilih minimal satu hari dari Senin sampai Minggu.` |
| `app/Services/JadwalService.php:20-21` | Validasi melalui `Hari::NAMA`, pesan `Hari harus Senin sampai Jumat.` | Rentang helper menjadi 1–7 dan pesan diperbarui. |
| `app/Livewire/JadwalComponent.php:59-60` | Validasi melalui `Hari::NAMA`, pesan `Hari harus Senin sampai Jumat.` | Rentang helper menjadi 1–7 dan pesan diperbarui. |
| `app/Livewire/JadwalComponent.php:142-143` | Query grid `hari >= 1` dan `hari <= 5` | Query menggunakan hari yang benar-benar dipetakan. |
| `resources/views/livewire/polajam-component.blade.php` | Tidak ada teks/rentang hard-coded; pilihan mengikuti `$hariOptions`. | `$hariOptions` sekarang berisi tujuh hari. |
| `resources/views/livewire/jadwal-component.blade.php` | Tidak ada rentang hari hard-coded; grid sebelumnya memakai seluruh `$hariOptions`. | Grid memakai hari hasil pemetaan. |
| `routes`, `database/seeders` | Tidak ditemukan batasan atau teks hari yang cocok. | Tidak diubah. |

Hitungan awal: `mapel=1, guru=1, kelas=1, kelassiswa=1, semester=1, polajam=1, polajamhari=5, jampelajaran=1, pengampu=1, jadwal=0`. `SHOW COLUMNS`: `polajamhari.hari=tinyint unsigned, Null=NO, Key=UNI`; `jadwal.hari=tinyint unsigned, Null=NO, Key=MUL`. **H1: PASS.**

| Poin | Perintah / pemeriksaan | Output mentah (ringkas) | Hasil |
|---|---|---|---|
| H2 | `php storage\app\tmp-uji\JadwalV2Probe.php` | `H2: {"H2_map_day6":true,"H2_map_day7":true,"H2_invalid_days":{"0":{"accepted":false,"message":"Pilih minimal satu hari dari Senin sampai Minggu."},"8":{"accepted":false,"message":"Pilih minimal satu hari dari Senin sampai Minggu."},"H2_duplicate_day":{"accepted":false,"message":"Hari Sabtu sudah dipetakan ke pola lain."},"H2_schedule_saturday":{"accepted":true,"message":""},"H2_unmapped_day":{"accepted":false,"message":"Minggu belum dipetakan ke pola jam."},"H2_invalid_schedule_days":{"0":{"accepted":false,"message":"Hari harus Senin sampai Minggu."},"8":{"accepted":false,"message":"Hari harus Senin sampai Minggu."}}}` | PASS |
| H3 | Probe merender view Jadwal untuk fixture hari 1–4, 1/2/6, dan tanpa pemetaan | `H3: {"days_1_4":["Jam","Senin","Selasa","Rabu","Kamis"],"days_1_2_6":["Jam","Senin","Selasa","Sabtu"],"no_mappings":{"empty_message":true,"grid_table":false}}` | PASS |
| H4 | `Get-ChildItem app,resources -Recurse -File \| Select-String -SimpleMatch 'Senin sampai Jumat'` | `NO_MATCH: Senin sampai Jumat in app and resources` | PASS |
| H5 | Probe `simpanDenganMapel()` dengan Mapel baru; hitung sebelum/sesudah | `H5_before={"pengampu":2,"jadwal":2}`; `H5_after={"pengampu":3,"jadwal":3}` — tepat bertambah satu pengampu dan satu jadwal. | PASS |
| H6 | Probe pengampu terdaftar dengan guru berbeda dan sama; render pilihan Guru | Berbeda: `accepted=false`, pesan `Mapel ini di kelas ini sudah diampu oleh Guru A. Ubah di menu Pengampu atau pilih guru yang sama.`; jumlah sebelum/sesudah tetap `pengampu=3, jadwal=3`. Guru sama: `accepted=true`, jumlah pengampu tetap `3`, jadwal menjadi `4`. Modal: `registered_teacher_id` sama dengan `expected_teacher_id`; `registered_message=true`; `registered_teacher_disabled=true`; untuk Mapel baru `new_mapel_teacher_empty=true`, `new_mapel_teacher_enabled=true`. | PASS |
| H7 | Probe assignment baru pada slot bentrok kelas, guru, dan siswa; hitung pengampu sebelum/sesudah | Kelas: `Kelas bentrok ...`, `3 -> 3` pengampu. Guru: `Guru bentrok dengan kelas ...`, `4 -> 4`. Siswa: `Siswa bentrok: 1 siswa (Siswa Uji Bentrok).`, `5 -> 5`. | PASS |
| H8 | Probe guru nonaktif, ID Mapel tidak ada, dan kelas pada tahun ajaran berbeda; hitung pengampu | Nonaktif: `Guru pengampu harus berstatus aktif.`, `5 -> 5`; Mapel tidak ada: `Semester, kelas, mata pelajaran, atau guru tidak ditemukan.`, `5 -> 5`; tahun berbeda: `Kelas harus berasal dari tahun ajaran semester yang dipilih.`, `5 -> 5`. | PASS |
| H9 | Probe penggantian sel dengan `idjadwalPengganti`; SELECT baris lama sebelum/sesudah kegagalan | Berhasil: `accepted=true`, jumlah jadwal untuk sel tetap `1`. Gagal karena guru bentrok: `accepted=false`; SELECT sebelum dan sesudah sama: `idjadwal=206, pengampu_idpengampu=159, hari=5, jam_ke=4`; `H9_failure_rows_same=true`. | PASS |
| H10 | Probe semua method publik komponen dengan user level `siswa`, `guru`, dan `superadmin` | `public_methods=["mount","updatedFilterSemester","updatedFilterKelas","updatedMapelId","bukaSlot","simpan","hapus","close","render"]`; `unauthorized_calls=18`; `forbidden_calls=18`; `superadmin_calls=9`; `superadmin_accepted=9`. | PASS |
| H11 | Hitung query render grid dengan 10 dan 30 sel | `H11={"cells10":15,"cells30":15}` | PASS |
| H12 | `json_encode` properti publik setelah `bukaSlot()` | `{"filterSemester":"72","filterKelas":"206","hari":1,"jam_ke":1,"mapelId":"84","guruId":"108","idjadwal":208}`; `length=108`. Hanya nilai skalar. | PASS |
| H13 | Render template dengan collection Mapel dan Guru aktif kosong | `H13={"no_mapel_message":true,"no_guru_message":true,"save_disabled":true}`; tidak terjadi exception. | PASS |
| H14 | `route:list --name=jadwal`, `route:list --name=polajam`, `view:clear`, `view:cache`, `php -l` untuk empat path di bawah, Pint untuk empat path di bawah, hitungan akhir, `git diff --stat` untuk lima file sumber | Route: satu route Jadwal dan satu route Pola Jam; view: `Compiled views cleared successfully`, `Blade templates cached successfully`; PHP lint: keempat file `No syntax errors detected`; Pint: `PASS ... 4 files`; hitungan awal/akhir: `mapel=1, guru=1, kelas=1, kelassiswa=1, semester=1, polajam=1, polajamhari=5, jampelajaran=1, pengampu=1, jadwal=0`; pembanding data: `counts_match=true`, `polajamhari_rows_match=true`, `polajam_rows_match=true`, `jampelajaran_rows_match=true`; diff stat: `5 files changed, 141 insertions(+), 31 deletions(-)`. | PASS |

Perintah lint yang dijalankan satu per satu:

```text
php -l app\Support\Hari.php
php -l app\Services\PolajamService.php
php -l app\Services\JadwalService.php
php -l app\Livewire\JadwalComponent.php
```

Perintah Pint hanya untuk file PHP yang diubah:

```text
php vendor\bin\pint app\Support\Hari.php app\Services\PolajamService.php app\Services\JadwalService.php app\Livewire\JadwalComponent.php
```

Diff stat terfokus:

```text
 app/Livewire/JadwalComponent.php                   | 72 ++++++++++++++++------
 app/Services/JadwalService.php                     | 52 +++++++++++++++-
 app/Services/PolajamService.php                    |  4 +-
 app/Support/Hari.php                               |  4 +-
 .../views/livewire/jadwal-component.blade.php      | 40 +++++++++---
 5 files changed, 141 insertions(+), 31 deletions(-)
```

### Keputusan dan batas pengujian

- Pemetaan lama hari 1–5 sementara dihapus hanya di dalam transaksi probe untuk membentuk fixture grid hari 1–4 dan 1/2/6. Setelah rollback, isi dan jumlah pemetaan, pola, serta jam sama persis seperti semula.
- H13 mengirim collection kosong langsung ke view untuk menguji tampilan keadaan kosong. Database saat ini memiliki Mapel yang direferensikan pengampu; karena itu pengujian tidak menghapus data atau menonaktifkan foreign key demi memaksa query komponen menghasilkan nol Mapel.
- Belum diuji di browser: tata letak modal pada viewport nyata, interaksi klik sel, dan tampilan responsif.
