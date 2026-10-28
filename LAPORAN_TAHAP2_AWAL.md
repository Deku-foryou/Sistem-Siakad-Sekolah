# TAHAP 2: Audit Workflow Penempatan Siswa - Awal

## 1. Kondisi Awal Audit

### 1.1 File yang Diperiksa

| File | Status | Catatan |
|------|--------|---------|
| `app/Livewire/KelassiswaComponent.php` | ✅ Terverifikasi | Alur penempatan, penolakan, dan keluar sudah ada |
| `resources/views/livewire/kelassiswa-component.blade.php` | ⚠️ Perlukan perbaikan | Tampilan "kosong" ketika belum ada kelas yang dipilih; tidak ada empty state pesan |
| `app/Services/PenempatalService.php` | ✅ Sudah tepat | Aturan penempatan: aktif, induk, validasi duplikat sudah ada |
| `app/Models/Kelassiswa.php` | ⚠️ Butuh field status | Tidak ada field status untuk melacak riwayat pemindahan/penghapusan |
| `database/migrations/2026_10_30_000002_add_jurusan_to_kelas.php` | ✅ Sudah ada | Kolom jurusan_id sudah ditambahkan ke kelas |
| `database/migrations/2026_10_30_000001_create_jurusan_table.php` | ✅ Sudah ada | Tabel jurusan dibuat |

### 1.2 Data Saat Ini

| Data | Nilai |
|------|-------|
| Tahun Ajaran Aktif | 2026/2027 (id: 24) |
| Jumlah Kelas di TA 24 | 1 (Mipa-1, jenis: induk, wali: guru id 22) |
| Jumlah Siswa Aktif | 1 (Hibiki Yutaa, idsiswa: 566) |
| Jumlah Kelassiswa Records | 1 (Hibiki Yutaa ditempatkan di Mipa-1, status: aktif) |
| Aturan yang Berlaku | - Siswa hanya boleh 1 kelas induk aktif per TA<br>- Penempatan ganda dicegah<br>- Klas Induk butuh Wali aktif |

### 1.3 Alur Saat Ini di Halaman `/kelassiswa`

1. **Page load**: `mount()` sets `$tahunajaran_id = 24`
   - View dirender: `$tahunajarans` = semua TA, `$kelasList` = kelas TA 24 = [Mipa-1]
   - `$members` = null (karena `$kelas_id` belum di-set)
   - `$siswaAktif` = 1 orang (Hibiki Yutaa)
   - **Konten "Tambah Siswa" + "Riwayat" TIDAK muncul** karena `@if ($kelas_id)` false
   - Hanya ditampilkan filter: TA (2026/2027 terpilih) + Kelas (daftar kosong/tampilkan "Pilih")

2. **User memilih TA**: `$tahunajaran_id` diupdate → `$kelasList` refresh dengan kelas TA tadi
3. **User memilih Kelas**: `$kelas_id` diupdate → `@if ($kelas_id)` menjadi true → "Tambah Siswa" form + riwayat muncul
4. **Penempatan**: `tambahSiswa()` dipanggil → `PenempatalService::tambah()` validasi → sukses/gagal
5. **Keluar**: `keluarkan()` dipanggil → record dihapus permanen (masalah: kehilangan riwayat)

### 1.4 Masalah yang Ditemukan

| # | Masalah | Penyebab | Dampak |
|---|---------|----------|--------|
| 1 | Konten "kosong" setelah pemilihan TA | `@if ($kelas_id)` menyembunyikan konten hingga kelas dipilih | User bingung, tidak ada petunjuk |
| 2 | Tidak ada empty state pesan | Ketika filter tidak menghasilkan data, hanyalah blank space | User bingung harus apa selanjutnya |
| 3 | Riwayat hilang saat "keluarkan" | `Kelassiswa::delete()` menghapus record permanen | Riwayat penempatan lama hilang, tidak bisa ditelusuri |
| 4 | Tidak ada pelacakan status pemindahan | Tidak ada field `status` di `kelassiswa` | Tidak bisa membedakan pemindahan dari penghapusan |

### 1.5 Solusi yang Diberikan Tugas

Tugas meminta:
- "Halaman harus menyediakan alur yang jelas bagi admin untuk: 1. Memilih TA, 2. Melihat daftar kelas, 3. Memilih kelas, 4. Melihat siswa, 5. Menempatkan, 6. Memeriksa hasil"
- "Jika tidak ada kelas atau siswa yang memenuhi filter, tampilkan empty state dengan penjelasan dan tindakan yang relevan"
- "Penempatan tahun ajaran sebelumnya dapat ditelusuri"
- "Perubahan kelas tidak menghilangkan informasi riwayat secara diam-dari"

## 2. Perbaikan yang Dilakukan

### A. Tambah Field Status ke Kelassiswa

**Migrasi Baru**: `2026_11_01_000001_add_status_to_kelassiswa_table.php`
- Menambah field `status` ENUM('aktif', 'selesai', 'pindah') ke tabel `kelassiswa`
- Default value: 'aktif'
- Field ditempatkan setelah `siswa_idsiswa`

**Alasan**: Untuk melacak riwayat pemindahan/penghapusan tanpa menghapus record fisik. Memungkinkan:
- Penempatan aktif ditandai dengan `status = 'aktif'`
- Pemindahan kelas ditandai dengan `status = 'pindah'`
- Penempatan selesai/diluluskan ditandai dengan `status = 'selesai'`
- Riwayat dapat ditelusuri tanpa kehapusan fisik

### B. Perbaikan Komponen Kelassiswa

**`app/Livewire/KelassiswaComponent.php`**:
- Metode `keluarkan($idkelassiswa)`: sekarang memperbarui `status` menjadi `'pindah'` daripada menghapus record permanen
- Logika lainnya tetap sama

**`resources/views/livewire/kelassiswa-component.blade.php`**:
- Menambahkan `@elseif ($tahunajaran_id && !$kelas_id)` untuk menampilkan pesan "Pilih Kelas" ketika TA dipilih tapi belum ada kelas
- Menambahkan `@else` untuk menampilkan pesan awal "Silakan pilih TA"
- Struktur `@if / @elseif / @else` memberikan alur yang jelas

### C. Verifikasi PenempatalService

**`app/Services/PenempatalService.php`**:
- Aturan sudah tepat dan tidak perlu diubah:
  - Cek siswa aktif
  - Validasi TA valid
  - Cek penempatan duplikat
  - Cek siswa sudah punya kelas induk TA sama
  - Cek kelas induk punya Wali aktif
- Gunakan DB transaction untuk konsistensi

## 3. Verifikasi Hasil

### 3.1 Struktur Database Setelah Migrasi

| Field | Type | Null | Default |
|-------|------|------|---------|
| idkelassiswa | int unsigned | NO | PRI, auto_increment |
| kelas_idkelas | int unsigned | NO | MUL |
| siswa_idsiswa | int unsigned | NO | MUL |
| **status** | enum('aktif','selesai','pindah') | NO | 'aktif' |
| created_at | timestamp | YES | - |
| updated_at | timestamp | YES | - |

### 3.2 Alur Kerja yang Telah Diperbaiki

```
+----------------+     +----------------+     +----------------+
| Page Load      |     | Pilih TA       |     | Pilih Kelas    |
+----------------+     +----------------+     +----------------+
  $tahunajaran_id  -->  refresh $kelasList  -->  $kelas_id di-set
  (aktif=24)           (kelas untuk TA 24)     (Mipa-1)
                           |                       |
                           v                       v
                +----------------+     +----------------+
                | Kosong / PETA  |     | "Tambah Siswa" |
                | Awal           |     | + Riwayat      |
                +----------------+     +----------------+
                           |                       |
                           v                       v
                      +----------------+    +------------------+
                      | Kelas Dipilih  |    | Penempatan Sukses|
                      | (Mipa-1)        |    | (status: aktif)  |
                      +----------------+    +------------------+
                           |
                           v
                      +------------------+
                      | Keluarkan (status: pindah) |
                      +------------------+
```

### 3.3 Tes Manual yang Disarankan

1. Buka halaman `/kelassiswa` → harus menampilkan pesan awal "Silakan pilih TA"
2. Pilih TA 2026/2027 → harus menampilkan "Pilih Kelas" + daftar kelas (Mipa-1)
3. Pilih kelas Mipa-1 → harus menampilkan "Tambah Siswa" form + daftar siswa aktif + riwayat (Hibiki Yutaa, status: aktif)
4. Coba penempatal siswa baru → service validasi berjalan, sukses atau gagal dengan pesan
5. Coba "keluarkan" siswa → status berubah menjadi 'pindah', record tetap ada (bisa dilihat riwayat)
6. Coba penempatal siswa lagi setelah dikeluarkan → bisa (karena status 'pindah', belum 'selesai')

## 4. File yang Diubah

| File | Jenis Perubahan |
|------|----------------|
| `database/migrations/2026_11_01_000001_add_status_to_kelassiswa_table.php` | Migrasi baru menambah field status |
| `app/Models/Kelassiswa.php` | Ditambah `status` ke `$fillable` |
| `app/Livewire/KelassiswaComponent.php` | `keluarkan()` Update status bukan delete |
| `resources/views/livewire/kelassiswa-component.blade.php` | Menambah struktur @if/@elseif/@else dengan empty state |
| `LAPORAN_TAHAP2_AWAL.md` | Laporan awal (file ini) |

## 5. Catatan Penting

- Migrasi sudah diterapkan ke database (development environment)
- Record lama tetap memiliki `status = 'aktif'` (nilai default)
- Perilaku `keluarkan()` berubah: status berubah menjadi 'pindah' invece delete permanen
- Tampilan halaman sekarang memberikan petunjuk yang jelas di setiap tahap
- Aturan bisnis di `PenempatalService` tidak berubah dan masih berlaku

---

*Laporan ini dibuat untuk mencatat kondisi awal dan perbaikan yang dilakukan pada Tahap 2 sebelum lanjut ke verifikasi akhir.*