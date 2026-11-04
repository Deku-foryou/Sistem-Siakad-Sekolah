# LAPORAN REVIEW & FIX PENGUMUMAN 2B.4.6

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Status | **PASS** |

---

## 1. Temuan Awal
- `waktu_terbit` memungkinkan nilai `NULL` pada filter daftar aktif siswa, yang berisiko menampilkan pengumuman yang belum direncanakan terbit.
- Pengumuman dengan `berlaku_dari` di masa depan belum difilter secara eksplisit pada query siswa, berpotensi muncul sebelum waktunya.
- Query `daftarAktif` dan `riwayat` berpotensi memiliki celah tumpang tindih jika status `terbit` dan `arsip` tidak dikelola secara eksklusif berdasarkan waktu berlaku.

## 2. File yang Diperiksa dan Diubah
- `app/Services/PengumumanService.php`: Query diperketat.
- `app/Livewire/PengumumanSiswaComponent.php`: Docblock diperbarui agar konsisten.

## 3. Perbaikan
- **Pengecekan Waktu**: `waktu_terbit` diwajibkan `NOT NULL` dan `waktu_terbit <= now()` pada `daftarAktif`, `riwayat`, dan `detailUntukSiswa`.
- **Isolasi Query**: `daftarAktif` (terbit & valid) dan `riwayat` (arsip atau terbit & kedaluwarsa) dipastikan terpisah secara logis.
- **Filter Masa Depan**: Penambahan filter `berlaku_dari <= today` pada seluruh query siswa untuk mencegah pengumuman di masa depan tampil.

## 4. Output Pengujian Aktual (`storage/app/tmp-uji/uji-pengumuman-final.php`)

```
--- Setup Selesai ---
Aktif count: 1
 - Aktif
Riwayat count: 2
 - Arsip
 - Expired
PASS | IDOR terdeteksi (Siswa2 tidak bisa lihat p1)
SEMUA UJI PASS
--- Rollback Selesai ---
```

## 5. Hasil Authorization dan HTTP
- **Otorisasi**: Seluruh komponen Livewire menggunakan `abort_unless(auth()->user()?->level === 'siswa'/'superadmin', 403)`.
- **Detail IDOR**: `PengumumanService::detailUntukSiswa` menggunakan `firstOrFail` yang secara otomatis melempar 404 jika siswa tidak memiliki `PengumumanBaca` (snapshot) untuk pengumuman tersebut, mencegah akses ke pengumuman milik siswa lain.

## 6. Status Akhir
- **PASS**
