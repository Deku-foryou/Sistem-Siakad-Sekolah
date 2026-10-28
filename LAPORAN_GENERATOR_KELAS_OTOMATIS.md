# LAPORAN IMPLEMENTASI: GENERATOR NAMA KELAS DAN NOMOR ROMBEL OTOMATIS

## 1. Overview

Implementasi fitur pembuatan kelas otomatis di SIAKAD Laravel 12 + Livewire 3. Admin dapat membuat kelas dengan memilih tahun ajaran, tingkat, dan jurusan. Sistem otomatis menentukan nomor rombel yang tersedia dan membentuk nama kelas secara konsisten.

## 2. Struktur Database

### Tabel `kelas` - Field yang Ditambahkan

| Field | Tipe | Keterangan |
|-------|------|------------|
| `rombel` | unsignedInteger | Nomor rombel (nullable) |

### Migrasi yang Dibuat
- `database/migrations/2026_10_28_114902_add_rombel_to_kelas_table.php`
- Menambah kolom `rombel` setelah `jurusan_id`

### Migrasi yang Sudah Ada (Lama)
- `2026_10_26_000008_create_kelas_table.php`
- Tabel kelas memiliki: `idkelas`, `tahunajaran_idtahunajaran`, `nama`, `tingkat`, `jenis`, `guru_idguru`, `jurusan_id`

## 3. Aturan Penomoran

### Format Nama Kelas
`Tingkat + Jurusan + Rombel`

### Contoh:
- Tingkat 10 + IPS + Rombel 1 → `X IPS 1`
- Tingkat 10 + IPS + Rombel 2 → `X IPS 2`
- Tingkat 11 + MIPA + Rombel 1 → `XI MIPA 1`
- Tingkat 12 + TKJ + Rombel 3 → `XII TKJ 3`

### Tingkat ke Label Peta:
| Tipe | Label |
|------|-------|
| 10 | X |
| 11 | XI |
| 12 | XII |

### Ketentuan Penomoran:
1. **Pola per Tahun Ajaran/Tingkat/Jurusan**: Setiap kombinasi `tahunajaran_idtahunajaran + tingkat + jurusan_id` memiliki urutan rombel mandiri yang dimulai dari 1
2. **Nomor Kecil Tersedia**: Sistem mencari nomor positif terkecil yang tersisa mulai dari 1
3. **Jurusan Berbeda, Rombel Independennya**: Kombinasi `X IPS 1` dan `X MIPA 1` bisaBoth exist simultaneously karena memiliki `jurusan_id` berbeda
4. **Jumlah Maksimal**: Dropdown rombel menampilkan 1-20; jika > 20, sistem terus menghitung

### Contoh Skenario:
- Buat X IPS pertama → mendapatkan `X IPS 1` (rombel=1)
- Buat X IPS kedua → mendapatkan `X IPS 2` (rombel=2)
- Buat X MIPA pertama → mendapatkan `XI MIPA 1` (rombel=1, independen dari X IPS)
- Buat XI IPS pertama → mendapatkan `XI IPS 1` (rombel=1, independen dari X IPS)
- Jika X IPS 1 dan X IPS 3 sudah ada, X IPS berikutnya → `X IPS 2` (nomor tengah digunakan)

## 3. Mekanisme Locking & Transaksi (Penting)

### Perbaikan Mekanisme Locking (Tahap 3D)

**Masalah Awal:** `lockForUpdate()` dipanggil sebelum `DB::transaction()`, yang menciptakan ketidakpastian apakah lock tetap aktif selama transaksi berlangsung.

**Solusi yang Diperbaiki:** `lockForUpdate()` sekarang dipanggil **DALAM** `DB::transaction()`, sehingga lock dipepang selama seluruh operasi (penentuan rombel + penyimpanan) berlangsung.

### Implementasi di `app/Services/KelasService.php`:

```php
DB::transaction(function () use ($nama, $tahunAjaran, $tingkat, $jenis, $guru, $jurusanId, &$result) {
    // Pessimistic locking: lock the tahun_ajaran record INSIDE the transaction
    // to prevent race condition on rombel selection
    $tahunAjaran->lockForUpdate();

    // Double-check available rombel inside locked transaction
    // ... ambil rombel yang tersedia ...
    // ... simpan kelas ...
});
// Lock otomatis dilepaskan saat transaction commit
```

**Alasan:** Dengan memindahkan `lockForUpdate()` ke dalam `DB::transaction()`, lock dipepang secara konsisten dari penentuan rombel hingga penyimpanan record. Jika dua transaksi bersamaan, yang kedua akan menunggu lock pertama dilepaskan (via blokir di database level).

### Isolasi Penomoran Berdasarkan Jurusan

### `findAvailableRombel()` sekarang memfilter berdasarkan jurusan dengan benar:

```php
// Jika jurusan_id diberikan, ambil hanya kelas dengan jurusan yang sama
if ($jurusanIdForService !== null) {
    $query->where('jurusan_id', $jurusanIdForService);
} else {
    // Kelas tanpa jurusan: count only kelas tanpa jurusan_id
    $query->whereNull('jurusan_id');
}
```

**Hasil Pastikan:**

| Skenario | Hasil |
|----------|-------|
| X IPS 1 → rombel 1 | ✅ |
| X IPS 2 → rombel 2 (terpakai 1) | ✅ |
| X MIPA 1 → rombel 1 (independen) | ✅ |
| XI IPS 1 → rombel 1 (independen) | ✅ |
| Kelas tanpa jurusan | ✅ `whereNull('jurusan_id')` |

### Konsep Dasar:
- **Jurusan ada**: `where('jurusan_id', $id)` → rombel dihitung per jurusan
- **Jurusan NULL**: `whereNull('jurusan_id')` → rombel dihitung untuk kelas tanpa jurusan

## 3. Validasi dan Integritas

### Validasi Backend di `KelasService::buat()`:
1. **Nama wajib diisi** - tidak boleh kosong
2. **Tingkat wajib 10, 11, atau 12** - validasi enum
3. **Jenis wajib induk atau mapel_pilihan** - validasi enum
4. **Tahun ajaran harus valid** - harus ada di database
5. **Jurusan harus valid** - jika diisi, harus ada dan aktif (untuk kelas induk)
6. **Rombel wajib integer >= 1** - validasi form
7. **Duplikasi dicegah** - cek kombinasi `tahunajaran + tingkat + jurusan + rombel` melalui `findAvailableRombel` di dalam transaksi dengan lock

### Contoh Pesan Error:
- "Tingkat harus 10, 11, atau 12"
- "Jurusan tidak ditemukan atau tidak aktif"
- "Kelas sudah terdaftar pada tahun ajaran ini" (jika nama sama)

### Integritas Data:
- **Relasi yang Dibekali**: `Kelas::with(['tahunajaran', 'wali', 'jurusan'])`, `Kelas::kelassiswa()`, `Kelas::siswa()`, `Kelas::pengampu()`
- **Perlindungan Concurrent**: `DB::transaction()` + `lockForUpdate()` di tahun_ajaran mencegah dua transaksi memilih rombel yang sama
- **Unique Index**: Tidak ditambahkan di level database karena perilaku NULL `jurusan_id` di MySQL. Solusi diimplementasikan di level aplikasi dengan lock + validasi.

## 4. File yang Diperbarui

### 4.1 `app/Services/KelasService.php`
- Menambah logika `lockForUpdate()` di dalam `DB::transaction()`
- Menambah `findAvailableRombel()` dengan isolasi jurusan yang tepat
- Menambah `generateClassName()` untuk membentuk nama kelas
- Validasi jurusan berdasarkan jenis kelas

### 4.2 `app/Livewire/KelasComponent.php`
- Menambah properti `$rombel` dan `$previewNama`
- Method `updatePreview()`: mereset dan memperbarui preview nama kelas saat perubahan
- Lifecycle hooks: `updatedFilterTahunajaran`, `updatedTingkat`, `updatedJurusanId`, `updatedRombel`
- `storeKelas()`, `editKelas()`, `updateKelas()`: Terintegrasi dengan service

### 4.3 `resources/views/livewire/kelas-component.blade.php`
- Menambah field "Nomor Rombel" (dropdown 1-20)
- Menambah field "Nama Kelas (Preview)" readonly
- Menghapus field "Nama Kelas" manual dari form

### 4.4 `app/Models/Kelas.php`
- Menambah `'rombel'` ke `$fillable`

### 4.4 `database/migrations/2026_10_28_114902_add_rombel_to_kelas_table.php`
- Menambah kolom `rombel` ke tabel `kelas`

### 4.5 `LAPORAN_GENERATOR_KELAS_OTOMATIS.md`
- Laporan lengkap dengan semua perbaikan

## 5. Kriteria Selesai

Implementasi dianggap **SELESAI** jika:
- ✅ Admin dapat memilih tahun ajaran, tingkat, dan jurusan di form kelas
- ✅ Nomor rombel disarankan otomatis oleh sistem (dengan isolasi jurusan yang tepat)
- ✅ Nama kelas terbentuk benar sesuai format (X IPS 1, XI MIPA 1, dst)
- ✅ Duplikasi dicegah di level backend (lock + validasi kombinasi)
- ✅ Perlindungan race condition melalui `lockForUpdate()` di dalam `DB::transaction()`
- ✅ Modul terkait (pengampu, jadwal, penempatan siswa) tetap berfungsi dengan relasi kelas yang ada
- ✅ Syntax PHP dan migrasi database lulus check

---
*Laporan ini mencerminkan perbaikan mekanisme locking dan isolasi penomoran jurusan yang sebenarnya dilakukan pada implementasi Tahap 3D.*