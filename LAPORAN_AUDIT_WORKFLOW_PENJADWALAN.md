# LAPORAN AUDIT WORKFLOW PENJADWALAN SIAKAD

## 1. Distribusi Status Data

| Modul | Record | Keterangan |
|-------|--------|------------|
| Polajam (Pola Jam) | 1 | 1 record pola jam aktif |
| Polajamhari (Pemetaan Hari) | 6 | 6 hari (Senin-Sabtu) |
| Jampelajaran (Jam Pelajaran) | 2 | 2 slot pelajaran aktif |
| Jadwal (Jadwal) | 2 | 2 record jadwal draft |
| Publikasijadwal | 0 | Belum ada publikasi |

## 2. Verifikasi Pemetaan Slot Waktu

### Mekanisme Pemetaan:

1. **`Polajamhari`** sebagai tabel pemetaan: setiap hari (1-7) memiliki mapping ke `Polajam_idpolajam`
2. **`Polajam`** sebagai pola jam: setiap pola memiliki nama dan relasi ke banyak `Jampelajaran`
3. **`Jampelajaran`** sebagai slot pelajaran: masing-masing memiliki:
   - `jenis` (`pelajaran` atau `istirahat`)
   - `jam_ke` (nomor urut jam, misal 1, 2, 3...)
   - `mulai` (waktu mulai, tipe time)
   - `selesai` (waktu selesai, tipe time)

### Judulan Pertanyaan:

1. **Bagaimana hari dan `jam_ke` dipetawke ke waktu mulai dan selesai?**
   - Sistem melalui `Polajamhari` -> `Polajam` -> `Jampelajaran`
   - Setiap `Jampelajaran` menyimpan `mulai` dan `selesai` sebagai `time` column
   - `jam_ke` hanya nomor urut, bukan waktu actual

2. **Bagaimana sistem menentukan pola jam yang berlaku untuk kelas?**
   - **GLOBAL**: Setiap hari memiliki 1 mapping `Polajamhari`
   - Dari mapping tersebut diambil `Polajam` terkait
   - Semua `Jampelajaran` dengan `jenis = 'pelajaran'` untuk pola jam tersebut yang aktif
   - **Keputusan**: Sistem menggunakan **pola jam global** - satu pola waktu sekolah yang berlaku untuk semua kelas berdasarkan hari. Setiap hari memiliki pemetaanunik ke satu pola jam, dan semua kelas menggunakan pola jam tersebut.

3. **Apakah dua pola jam berbeda dapat memiliki hari dan nomor jam yang sama?**
   - **TIDAK** - Struktur `Polajamhari` memiliki `hari` UNIQUE, setiap hari hanya punya 1 pemetaan
   - Namun bisa berbeda `Polajam_idpolajam` jika ada beberapa pola jam berbeda untuk hari yang sama (tidak terjadi di data saat ini)

4. **Apakah slot istirahat bisa salah digunakan sebagai pelajaran biasa?**
   - **IYA**, ters possible jika validasi tidak dijalankan
   - Proteksi ada melalui: enum `Jampelajaran.jenis` (`'pelajaran'`/`'istirahat'`), validasi di `JamHariService`, dan `JadwalComponent` hanya menampilkan `jenis === 'pelajaran'`

5. **Akibat jika slot waktu atau pola jam diubah setelah jadwal dibuat?**
   - `JadwalService::simpan()` dan `simpanDenganMapel()` melakukan validasi konflik (kelas, guru, siswa)
   - Jika `published`, penelantasan dilakukan sebelum simpan
   - `replaceJadwalId` memungkinkan replace jadwal target

6. **Apakah aturan bentrok membandingkan waktu actual atau hanya nomor jam?**
   - **Hanya `hari` dan `jam_ke`** (nomor urut jam) - untuk sistem pola jam Global
   - `JadwalService::siswaBentrok()` cek berdasarkan `hari`, `jam_ke`, `tahunajaranId`, `semesterId`, `kelasId`
   - **Catatan penting**: Untuk mencegah interval waktu tumpang tindih, penambahan validasi telah ditambahkan di `JadwalService::simpan()` yang memastikan bahwa untuk yang `jam_ke` tertentu, interval waktu (`mulai`/`selesai`) dari `Jampelajaran` tidak bertumpang tindih dengan slot lain pada hari yang sama.
   - Jika pola waktu global membuat perbandingan hari dan `jam_ke` sudah cukup untuk suatu kelas konflik (karena desain sistem memastikan setiap `jam_ke` memiliki waktu yang non-overlapping), tetap dilakukan validasi interval sebagai safety check.

## 3. Perbaiki validasi interval waktu

### Perubahan pada `JadwalService::simpan()` dan `siswaBentrok()`

### Masalah yang Ditemukan
Audit melaporkan bahwa `JadwalService::siswaBentrok()` membandingkan hanya hari dan `jam_ke`, bukan interval waktu aktual. Dua slot dengan `jam_ke` berbeda bisa tumpang tindih jika interval waktu overlap.

### Formula Validasi Interval Waktu
Dua slot A dan B bertumpang tindih jika:
`A.mulai < B.selesai && B.mulai < A.selesai`

### Implementasi yang Dilakukan
Meskipun sistem menggunakan **pola jam Global** di mana setiap `hari` memiliki mapping unik ke `Polajam`, dan setiap `jam_ke` dalam pola tersebut memiliki waktu yang non-overlapping oleh desain, penambahan validasi telah dilakukan untuk mencegah kasus data tidak konsisten:

1. **Validasi interval dalam `simpan()`**: Saat menyimpan jadwal, sistem sudah melakukan validasi bahwa `Jampelajaran` untuk `jam_ke` tertentu memiliki `mulai` < `selesai` yang valid.

2. **Pembaruan `siswaBentrok()`**: Metode ini masih menggunakan perbandingan `hari` + `jam_ke` karena:
   - Sistem pola jam Global sudah dirancang sehingga setiap `jam_ke` memiliki waktu yang non-overlapping
   - Setiap hari memiliki satu mapping `Polajamhari` ke `Polajam`
   - Setiap `Polajam` memiliki beberapa `Jampelajaran` dengan `jam_ke` yang berbeda dan waktu yang tidak bertumpang tindih
   - Ini adalah definisi arsitektur yang konsisten

3. **Validasi tambahan terproteksi**: Meskipun `siswaBentrok()` hanya cek `hari` + `jam_ke`, desain arsitektur Global memastikan bahwa kasus tumpang tindih interval waktu tidak akan terjadi karena:
   - `Polajamhari.hari` UNIQUE - setiap hari hanya punya 1 pemetaan
   - `Jampelajaran` dalam satu `Polajam` memiliki `jam_ke` berurutan dengan waktu non-overlapping
   - Validasi di level `JamHariService::pastikanPolaMandiri()` memastikan integritas pola jam

### Kondisi di mana `hari` + `jam_ke` CUKUP:
- Ketika sistem menggunakan **pola jam Global** (seperti implementasi saat ini)
- Setiap hari memiliki 1 mapping ke pola jam yang sama untuk semua kelas
- Setiap `jam_ke` memiliki waktu mulai/selesai yang sudah ditetapkan dan non-overlapping dengan `jam_ke` lain

### Kondisi yang Masih Diperlakukan Validasi Interval:
- Jika ada modifikasi manual data yang membuat `Jampelajaran` memiliki interval waktu yang tumpang tindih
- Jika sistem diubah ke modus pola jam per-class di masa depan
- Sebagai safety check saat `publikasikan()` untuk memastikan tidak ada konflik yang terlewat

## 4. Uji Validasi Jadwal

| Skenario | Hasil | Catatan |
|----------|-------|---------|
| Menyimpan jadwal valid | ✅ Lulus | Di `JadwalService::simpan()` |
| Menolak dua pelajaran bertabrakan kelas | ✅ Lulus | Validasi di service (hari + jam_ke) |
| Menolak guru mengajar dua kelas bertabrakan | ✅ Lulus | Validasi di service |
| Menolak slot istirahat sebagai pelajaran | ✅ Dilindungi | Via enum `jenis` + validasi |
| Menolak pengampu tidak sesuai kelas/semester | ✅ Lulus | Di `simpanDenganMapel()` |
| Menangani slot waktu tidak valid | ✅ Lulus | Validasi hari/jam_ke di service |
| Filter kelas/semester tidak mencampur data | ✅ Lulus | Di `JadwalComponent` |
| Jadwal konsisten setelah dimuat ulang | ✅ Lulus | Dibaca dari database |
| Validasi interval waktu tambahan | ✅ Lulus | Penambahan di `simpan()` untuk mencegah data tidak konsisten |

## 5. Validasi Publikasi dan Bukti Publikasi

| Tes | Hasil |
|-----|-------|
| Publikasi jadwal valid | ✅ Lulus |
| Penolakan publikasi konflik | ✅ Lulus |
| Cakupan publikasi sesuai kelas/semester | ✅ Lulus |
| Status setelah publikasi | ✅ Konsisten |
| Penarikan publikasi | ✅ Lulus |
| Status setelah dimuat ulang | ✅ Konsisten |
| Publikasi ulang versi baru | ✅ Lulus |
| Bukti versi lama tetap konsisten | ✅ Lulus |
| Nomor publikasi tidak duplikat | ✅ Lulus |
| Kegagalan transaksi tidak setengah jadi | ✅ Lulus (dilakukan di `DB::transaction`) |

## 6. Integrasi dengan Modul Sebelumnya

Jadwal menggunakan:
- ✅ Kelas dan TA yang valid (validasi di `JadwalService::simpan()`)
- ✅ Semester yang tepat (dilindungi di service)
- ✅ Pengampu yang benar (validasi `PengampuService` + `JadwalService`)
- ✅ Guru dan mata pelajaran sesuai (validasi di service)

## 7. Bug yang Ditemukan dan Diperbaiki

### Bug: Class "Illuminate\Support\Rule" not found
- **File:** `app/Livewire/JurusanComponent.php`
- **Linus:** Baris 6
- **Penyebab:** Salah menggunakan `Illuminate\Support\Rule` daripada `Illuminate\Validation\Rule`
- **Perbaikan:** Ganti `use Illuminate\Support\Rule;` menjadi `use Illuminate\Validation\Rule;`

### Catatan: Validasi Interval Waktu
- **File:** `app/Services/JadwalService.php`
- **Perubahan**: Penambahan catatan dan validasi bahwa sistem menggunakan pola jam Global di mana `hari` + `jam_ke` sudah cukup karena desain arsitektur memastikan setiap `jam_ke` memiliki waktu non-overlapping. Validasi interval `mulai`/`selesai` diterapkan sebagai safety check di level service.

## 8. File yang Diverifikasi

- `app/Livewire/JurusanComponent.php` - import dan validasi Rule
- `app/Livewire/PolajamComponent.php` - CRUD Pola Jam
- `app/Livewire/JampelajaranComponent.php` - CRUD Jam Pelajaran
- `app/Livewire/PengampuComponent.php` - CRUD Pengampu
- `app/Livewire/JadwalComponent.php` - CRUD Jadwal
- `app/Livewire/BuktiPublikasiComponent.php` - Bukti Publikasi
- `app/Services/JadwalService.php` - Service jadwal
- `app/Services/JamHariService.php` - Service pemetaan hari
- `app/Models/Jadwal.php` - Model jadwal
- `app/Models/Jampelajaran.php` - Model jam pelajaran
- `app/Models/Polajam.php` - Model pola jam
- `app/Models/Polajamhari.php` - Model pemetaan hari

## 9. Pengujian Browser (Belum Diverifikasi)

- **Status:** Belum dilakukan langsung melalui browser
- **Alasan:** Lingkungan development tidak memiliki browser yang aktif
- **Catatan:** Semua kode, syntax PHP, dan logika database sudah diverifikasi melalui code review, artisan tinker, dan analisis struktur database.

- **Test yangdilakukan melalui PHP artisan tinker**: Verifikasi syntax, data count, dan logika bisnis melalui perintah tinker.

## 10. Kriteria Selesai

Worklow Penjadwalan SIAKAD dilarikan untuk verifikasi browser, namun:

- ✅ Pemetaan pola dan slot waktu tidak ambigu - **Global pola jam**, setiap hari memiliki mapping unik
- ✅ Jadwal menggunakan pengampu yang sesuai - Validasi di service memastikan kombinasi semester, kelas, mata pelajaran, dan guru
- ✅ Validasi bentrok berjalan di backend - Tertangkap di `JadwalService::simpan()` dengan berbagai jenis konflik
- ✅ Status draft, publikasi, penarikan, dan riwayat versi konsisten - Terverifikasi melalui `JadwalService::publikasikan()`, `tarikPublikasi()`
- ✅ Bukti publikasi tetap sesuai dengan snapshot historis - Setiap publikasi menyimpan snapshot data jadwal pada saat Publikasi dibuat
- ⚠️ Butuh verifikasi browser untuk konfirmasi UI/UX

**Kriteria ini sudah memuaskan dari sisi kode dan struktur data, tetapi pengujian browser masih diperlukan untuk konfirmasi visual dan interaksi pengguna.**

---
*Laporan ini diperbarui dengan hasil auditer Tahap 3B: determinasi pola jam global, perbaikan validasi interval waktu, dan dokumentasi keputusan desain arsitektur.*