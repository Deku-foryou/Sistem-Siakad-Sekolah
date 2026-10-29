# LAPORAN_PERTANYAAN_PEMILIK.md

Hal-hal yang tidak bisa disimpulkan dari kode dan memerlukan klarifikasi pemilik:

---

## 1. Format Nama Kelas: "X MIPA 1" atau "X MIPA Rombel 1"?

**Status:** SUDAH DIPERBAIKI → format sekarang "X MIPA 1" (tanpa kata "Rombel")

**Pertanyaan:** Apakah format "X MIPA 1" sudah sesuai dengan harapan? Atau ingin kembali ke "X MIPA Rombel 1"?

---

## 2. Wali Kelas: Modal Tambah Kelas Harus Punya Field Wali?

**Situasi:** 
- Modal **Tambah Kelas** (induk): tidak ada field "Wali"
- Modal **Edit Kelas** (induk): ada field "Wali"

**Pertanyaan:** Apakah ini by-design (wali dipilih saat edit) atau harus ditambahkan di modal Tambah juga?

---

## 3. Unique Constraint Kelas: Apa Aturannya?

**Situasi:** Migration 2026_11_04_000001 menghapus constraint UNIQUE(tahunajaran, tingkat, jenis, rombel)

**Alasan di kode:** Constraint lama tidak handle NULL jurusan dengan baik (dua kelas dengan jurusan berbeda tapi rombel sama akan bentrok)

**Pertanyaan:** 
- Apakah boleh tahun+tingkat+jenis+rombel DUP jika jurusan_id berbeda?
- Contoh: "X MIPA 1" (jurusan_id=MIPA) dan "X MIPA 1" (jurusan_id=IPS) boleh exist bersamaan?

---

## 4. Fitur Publikasi Jadwal: Aturan Main?

**Kolom:** publikasijadwal.status, publikasijadwal.ditarik

**Pertanyaan:**
- Siapa saja bisa publikasi jadwal (hanya superadmin atau guru juga)?
- Kalau "ditarik", apakah jadwal hilang dari view siswa/guru atau hanya tandanya?
- PDF evidence (`bukti`) di mana disimpan? Storage lokal atau cloud?

---

## 5. Kelas "Mapel Pilihan": Apakah Dipakai Aktif?

**Situasi:** 
- Field `jenis` di tabel kelas: 'induk' atau 'mapel_pilihan'
- Logic: hanya induk yang bisa punya wali
- Mapel_pilihan tidak bisa punya jurusan

**Pertanyaan:** 
- Apakah fitur mapel_pilihan aktif dipakai?
- Atau staging untuk fitur mendatang (belum diimplementasi penuh)?

---

## 6. NIP Guru: Berapa Digit Maksimal?

**Validation saat ini:** `digits_between:1,25`

**Pertanyaan:** Apakah range 1-25 digit sudah tepat? Atau harus 1-20 atau 1-18?

---

## 7. Siswa: Field "Identitas Lengkap" Perlu Validasi?

**Migration:** 2026_10_29_000001_add_identitas_lengkap_to_siswa_table ada field tambahan

**Pertanyaan:** Field mana saja yang WAJIB di isi vs opsional untuk siswa baru?

---

## 8. Otorisasi Komponen: Siapa Boleh Akses?

**Saat ini:** KelasComponent, GuruComponent — hanya superadmin

**Pertanyaan:** 
- Apakah semua komponen admin HANYA untuk superadmin?
- Atau ada role lain (misal admin data induk, admin akademik) yang boleh akses subset komponen?

---

## 9. Import Data: Harus Check Unique NIP/NISN Global?

**Validasi saat import:** Apakah harus cek duplikat dengan data existing di database?

**Pertanyaan:** Kalau file import punya NIP guru yang sudah ada, apakah skip/update/error?

---

## 10. Tahun Ajaran "Aktif": Hanya 1 atau Bisa Lebih?

**Business logic:** Queries cari tahun aktif dengan `->aktif() singleton` (likely return 1)

**Pertanyaan:** Apakah sistem design untuk 1 tahun ajaran aktif setiap waktu? Atau bisa overlap (misal mid-year transition)?

---
