# Audit Workflow Jadwal Pelajaran

## 1. Ringkasan arsitektur aktual

Aplikasi Laravel 12 + Livewire 3. Fitur jadwal terdiri dari Polajam, Jampelajaran, Pengampu, Jadwal. Semua route superadmin. Tidak ada status draft/publikasi. Tidak ada ruang kelas.

## 2. Alur kerja setiap halaman

- Polajam: CRUD pola dan pemetaan hari.
- Jampelajaran: jam per hari (copy-on-write).
- Pengampu: penugasan guru-mapel-kelas-semester.
- Jadwal: grid hari x slot, validasi bentrok.

## 3. Diagram alur workflow yang direkomendasikan

Tahun ajaran/semester -> kelas/mapel/guru -> pola jam -> jam per hari -> pengampu -> jadwal grid -> validasi.

## 4. Peta relasi database

tahunajaran 1..* semester; semester 1..* pengampu; kelas 1..* pengampu; jadwal *..1 pengampu; polajam 1..* jampelajaran; polajam 1..* polajamhari (hari unik); jadwal (hari,jam_ke).

## 5. Masalah prioritas

Kritis: tidak ada publikasi. Tinggi: pola jam global (bukan per kelas). Sedang: UI mobile. Rendah: teks.

## 6. Rekomendasi perubahan minimal

Pertahankan struktur; tambahkan status publikasi hanya jika diperlukan; jangan migration tanpa kebutuhan.

## 7. File yang mungkin perlu diubah

JadwalComponent, JadwalService, models, views.

## 8. Risiko kompatibilitas/migrasi

Data lama tetap valid; penambahan status memerlukan migration.

## 9. Rencana implementasi bertahap

Tahap 1: audit (ini). Tahap 2: publikasi (opsional). Kriteria: tes hijau.

## 10. Daftar pengujian

Render, validasi bentrok, transaksi.

## Pertanyaan terbuka

Apakah jadwal perlu publikasi? Apakah ruang kelas digunakan?
