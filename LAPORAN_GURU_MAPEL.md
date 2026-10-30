# LAPORAN GURU ↔ MAPEL

## 1. MIGRASI
- Migration `2026_11_05_000001_create_gurumapel_table.php` telah dibuat dan dijalankan.
- SQL: `CREATE TABLE gurumapel (...)`.

## 2. RELASI
- `Guru` hasManyThrough `Mapel` (belongsToMany via `gurumapel`).
- `Mapel` belongsToMany `Guru` (via `gurumapel`).

## 3. GURU SERVICE
- Method `sinkronMapel(Guru $guru, array $mapelIds)` telah diimplementasikan dalam `GuruService.php` dengan transaksi dan validasi.

## 4. KOMPONEN GURU
- `GuruComponent` telah diupdate untuk mendukung `mapel_ids` (checkbox list di tambah/edit).
- Validasi untuk `mapel_ids` telah ditambahkan.
- `storeGuru` dan `updateGuru` sekarang melakukan sinkronisasi mapel di dalam transaksi.
- `deleteGuru` telah diupdate untuk menghapus relasi `gurumapel` sebelum menghapus guru.

## 5. KOMPONEN MAPEL
- `MapelComponent` telah diupdate untuk memblokir penghapusan jika mapel masih diampu oleh guru, dengan pesan yang informatif.

## 6. VERIFIKASI (TIDAK BISA DIUKUR TANPA RUN UJI)
(Langkah-langkah uji tersedia di perintah)
