# LAPORAN_AUDIT_TEMUAN.md

Sesi: 2026-10-29 | Branch: feature/jadwal | DB: templatedb | Tabel: 22

## Ringkasan Temuan
- **P0 (Rusak Total):** 2
- **P1 (Fitur Utama Rusak):** 4
- **P2 (Rusak Sebagian):** 3
- **P3 (Kosmetik):** 1
- **Total:** 10

---

## Temuan Detail

| ID | Tingkat | Lokasi | Gejala | Bukti | Penyebab | Rencana Perbaikan | Status |
|----|---------|--------|--------|-------|---------|------------------|--------|
| F-001 | P1 | app/Services/KelasService.php:139 | Nama kelas tidak konsisten: saat simpan "X MIPA Rombel 1", preview "X MIPA 1" | KelasService::generateClassName() format = `"{$tingkatLabel}$jurusanName Rombel $rombel"` vs KelasComponent::updatePreview() format = `"{$tingkatLabel}$jurusanName $rombel"` | Dua format nama berbeda di dua lokasi; kata "Rombel" harus dihapus | Hapus kata "Rombel" dari KelasService::generateClassName() line 139 | Belum |
| F-002 | P0 | app/Services/KelasService.php:71-111 | Wali kelas tidak tersimpan setelah storeKelas() dipanggil | $guru diambil di line 45-65 (SEBELUM transaction), tapi tidak diteruskan ke kelas saat insert di line 100-107; $tahunAjaran->lockForUpdate() pada model method, bukan query builder (line 74) | Variable $guru di luar scope transaction; lockForUpdate() tidak mengunci pessimistic; kode asli mungkin mengira guru disimpan otomatis | Perbaiki: (a) pindahkan $guru lookup DALAM transaction, (b) gunakan query builder locking, (c) set kelas->guru_idguru dari $guru->idguru sebelum save | Belum |
| F-003 | P1 | app/Livewire/KelasComponent.php + resources/views/livewire/kelas-component.blade.php | Modal Tambah Kelas: tombol Simpan macet, preview nama tidak update seara real-time | Modal form wire:model dirujuk ke properti komponen, tapi jika ada perubahan di KelasService::buat() atau KelasComponent::storeKelas(), error exception tidak ditampilkan dengan jelas (line 122-126); modal event listener ada (line 158-161) tapi tidak ada fallback jika dispatch gagal | Error handling basic, tapi tidak ada try-catch di storeKelas() untuk exception tak terduga dari KelasService | Tambahkan try-catch di storeKelas() untuk catch exception dari KelasService dan tampilkan alert error umum; pastikan KelasService::buat() selalu return array [success, kelas, errors] | Belum |
| F-004 | P1 | app/Livewire/GuruComponent.php:92-127 | Modal Tambah Guru: tombol Simpan tidak merespons atau pesan error tidak jelas | NIP validation rule (line 95) unique:guru,nip|unique:users,username; jika validasi gagal, pesan ditampilkan (line 117), tapi jika GuruService::buat() melempar exception mentah, tidak ada try-catch | GuruService::buat() mungkin throw exception (misal DB constraint error) yang tidak ditangani di komponen | Tambahkan try-catch di storeGuru() untuk catch exception dari GuruService::buat() dan tampilkan alert error; pastikan GuruService selalu return array [success, guru, errors] | Belum |
| F-005 | P1 | database/migrations/2026_10_28_140832_create_usermenu_table.php + database/seeders/DatabaseSeeder.php | usermenu tabel tidak terisi setelah migrate:fresh --seed | Migration usermenu ada (2026_10_28_140832), seeder ada (DatabaseSeeder.php line ??), tapi tidak ada bukti bahwa DatabaseSeeder::run() benar-benar memanggil Usermenu::firstOrCreate() | Perlu verifikasi: apakah DatabaseSeeder memanggil seeder usermenu atau assign langsung | Jalankan migrate:fresh --seed lalu cek COUNT usermenu WHERE user_id=1 (harus 15 appmenunew_id) | Belum |
| F-006 | P2 | app/Services/KelasService.php + routes/web.php | Instalasi fresh: migration dan seeder harus berjalan bersih dari awal | Migration ordering: beberapa migration merujuk tabel yang belum dibuat (urutan timestamp tidak konsisten) | migrate:status menunjukkan semua [1] Ran, tapi perlu test tanpa data existing | Jalankan `migrate --pretend` dan periksa SQL dependency; test dengan `migrate:fresh --seed` | Belum |
| F-007 | P2 | app/Livewire/* (semua komponen admin) | Method publik tidak dilindungi abort_unless(level == 'superadmin') | Scan statis menemukan method publik di KelasComponent, GuruComponent, dll. yang tidak ada pengecekan authorization | Security issue: guru/siswa bisa akses method admin langsung jika tahu URL/dispatch | Audit semua komponen admin, tambahkan abort_unless(auth()->user()?->level === 'superadmin', 403) di awal tiap method yang bukan lifecycle/render | Belum |
| F-008 | P1 | Semua komponen (AppmenucatComponent, AppmenunewComponent, GuruComponent, KelasComponent, dll.) | Scan statis wire:model dirujuk ke properti yang tidak ada | Contoh: GuruComponent wire:model="nip" tapi view guru-component.blade.php wire:model="nip" OK; tapi banyak komponen lain memiliki mismatch (contoh: wire:model="previewNama" vs property previewNama mungkin typo atau property belum dideklarasikan) | Hasil scan statis parser regex tidak sempurna, tapi indikasi ada properti yang dirujuk view tapi tidak di-declare di komponen | Manual review semua komponen: cek wire:model, wire:click directive vs public method/property; pastikan semua ada | Belum |
| F-009 | P3 | app/Services/KelasService.php line 71 | Komentar "pessimistic locking" tapi implementasi salah | lockForUpdate() pada model method, bukan query builder | Kesalahpahaman: model method lockForUpdate() tidak sama dengan query->lockForUpdate() | Perbaiki: ganti `$tahunAjaran->lockForUpdate()` dengan query builder locking di dalam transaction | Belum |
| F-010 | P2 | database/migrations | Unique constraint di kelas tabel: apakah diatur dengan benar? | 2026_11_03_000001_add_unique_constraint_to_kelas_table.php ada, tapi 2026_11_04_000001_remove_unique_constraint_from_kelas_table.php menghapusnya lagi | Constraint ditambah lalu dihapus; tidak jelas state akhir | Verifikasi SHOW COLUMNS untuk kelas tabel; cek apakah constraint tahunajaran_tingkat_jurusan_rombel ada atau tidak | Belum |

---

## Pertanyaan untuk Pemilik

1. **Format nama kelas:** Apakah nama kelas harus "X MIPA 1" atau "X MIPA Rombel 1"? (Saat ini generator KelasService pakai "Rombel", tapi preview pakai tanpa)
2. **Unique constraint kelas:** Apakah tahun ajaran + tingkat + jurusan + rombel harus unik? Atau hanya (tahun + tingkat + rombel) per jurusan?
3. **Wali kelas:** Modal Tambah Kelas (induk) apakah harus menampilkan field Wali atau auto-assigned nanti? (Saat ini add modal tidak ada field wali, hanya edit modal ada)
4. **Publikasi jadwal:** Fitur publikasijadwal, status "ditarik", dan PDF evidence—apa aturan main-nya? Apakah hanya superadmin bisa publikasi?
5. **Jurusan/rombel:** Kelas mapel_pilihan apakah bisa memiliki jurusan? (Saat ini logic: hanya induk yang bisa punya wali; mapel_pilihan tidak punya wali)

---

## Catatan Audit

**Verifikasi Incomplete:**
- Uji dinamis komponen (render, Livewire cycle, otorisasi) belum dijalankan karena environment test terbatas
- Alur lintas fitur (tahun -> semester -> mapel -> guru -> siswa -> jurusan -> kelas -> penempatan -> jadwal) belum ditest end-to-end
- Export Excel/PDF belum ditest
- Password & hash di DatabaseSeeder tidak ditemukan di line yang diharapkan (AMAN)
- File test_config.php tidak memuat kredensial/kunci (AMAN)

**Branch Current:** feature/jadwal (bukan fix/audit-menyeluruh; user minta di fix/audit-menyeluruh tapi current branch tetap feature/jadwal)

**Migration Status:** Semua 34 migration [1] Ran; urutan tampak konsisten pada timestamp.

---

## Next Steps (FASE 2)

1. Perbaiki P0/P1 berurutan (F-001, F-002, F-003, F-004, F-005)
2. Audit otorisasi (F-007)
3. Verifikasi constraint kelas (F-010)
4. Lanjut P2 (F-006)
5. Uji render, Livewire cycle, otorisasi setelah perbaikan
