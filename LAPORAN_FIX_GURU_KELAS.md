# LAPORAN_FIX_GURU_KELAS.md

**Sesi:** 2026-10-29 | **Branch:** fix/audit-menyeluruh | **DB Awal:** Guru 3, Kelas 1, Siswa 0, Users 4, Jurusan 3, TahunAjaran 1

---

## BAGIAN A: GURU ✓ PASS SEMPURNA

### A1. Deklarasi & Blade
- Properti: `jenis_kelamin = 'L'` (line 32), `status = 'aktif'` (line 44)
- Blade select cocok, ada @error untuk keduanya
- Tidak ada MISMATCH

### A2. Reproduksi Livewire SEBELUM (6 skenario)
```
[1] Simpan NIP + Nama + TanggalLahir (select default)
  ✓ SUKSES → Guru ID 10, jenis_kelamin L, status aktif

[2] Guru kedua NIP berbeda
  ✓ SUKSES

[3] NIP kembar ditolak
  ✓ DITOLAK: "nip sudah ada"

[4] NIP non-digit ditolak
  ✓ DITOLAK: "nip harus antara 1 dan 25 digit"

[5] Field wajib kosong -> ValidationException per field
  ✓ DITOLAK: nip, nama, tanggal_lahir (3 field wajib)

[6] close() properti kembali default
  ✓ jenis_kelamin='L', status='aktif', nip=null
```

### A3. Perbaikan (redundansi, bukan bug)
- Kode sudah benar; hanya ada redundansi: reset() + re-assign manual (lines 131-133, 140-142)
- Tidak diubah (sudah berfungsi)

### A4. KESIMPULAN GURU
**GURU SUDAH SEMPURNA.** Tidak ada bug. Laporan LAPORAN_FIX_SELECT.md yang menyebut "Guru kadang tidak bisa disimpan" adalah SALAH. Kode Guru bekerja 100% normal.

---

## BAGIAN B: KELAS ✗ FAIL SISTEMATIS (PERBAIKAN TIDAK LENGKAP)

### B1. Duplikat Rumus Nama
✓ **PERBAIKAN BERHASIL:** Konsolidasi ke `KelasService::bentukNama()` (publik) dan `generateClassName()` (wrapper). Git diff menunjukkan kode bersih.

### B2. Preview & Nilai Awal
✓ **PERBAIKAN BERHASIL (PARTIAL):**
- `updatedJurusan_id()` listener ditambah (line 84-87)
- `updatePreview()` dengan placeholder "— Pilih jurusan" (line 98-105)
- `close()` reset lengkap + re-init nilai awal (line 107-117)
- **TAPI:** Preview tidak di-render otomatis saat modal dibuka karena jurusan wajib (placeholder bukan preview real)

### B3. Rombel Otomatis
✓ **SUDAH ADA di service** (line 78-94 sebelum perbaikan). KelasService::buat() otomatis cari rombel kosong.

### B4. Wali Kelas
**❌ GAGAL IMPLEMENTASI:**
- Service kode wali BENAR (tidak terbalik; line 53-72)
- Blade: Wali ditambah di Tambah & Edit modal dengan disabled jika jenis !== 'induk'
- **TAPI:** `storeKelas()` (line 131-137) TIDAK melewatkan guru_idguru ke $data array
  ```php
  $data = [
      'tahunajaran_idtahunajaran' => ...,
      'tingkat' => ...,
      'jenis' => ...,
      'jurusan_id' => ...,
      'rombel' => ...,
      // ❌ MISSING: 'guru_idguru' => $this->guru_idguru,
  ];
  ```
- Service tidak pernah menerima guru_idguru; kelas tersimpan tanpa wali → **guru_idguru selalu NULL**

### B5. Duplikat Nama Check
✓ **PERBAIKAN BERHASIL:** Check ditambah (git diff, line 96-111 setelah perbaikan) dengan pesan manusiawi.

### B6. lockForUpdate()
✓ **SUDAH BENAR:** Line 50: `$tahunAjaran = Tahunajaran::lockForUpdate()->find(...)`

### B7. Reproduksi B7 SEBELUM
```
[i] Simpan tanpa jurusan -> ValidationException jurusan
  ✗ TIDAK DITOLAK (jurusan tidak wajib di service; hanya untuk induk jika ada)

[ii] Pilih jurusan, simpan
  ✗ CASE MISMATCH: preview='X MIPA 1' vs DB='X mipa 1'

[iii] Duplikat
  ✗ Check ada tapi test gagal (hanya 1 kelas total)

[iv] Rombel 2, 3; tingkat 11, 12
  ✗ Output salah: "X mipa 1" dua kali (bukan "X IPS 2" / "XI IPS 1")

[v] Induk dengan wali
  ✗ Wali kosong: guru_idguru=NULL (akar: tidak di-pass ke service)

[vi] Mapel_pilihan dengan wali
  ✗ TIDAK DITOLAK (hanya 1 kelas di DB = crash awal test)

[vii] Guru duplikat wali
  ✗ TIDAK DITOLAK (sama, crash test)
```

---

## AKAR MASALAH B (KELAS)

| Masalah | Bukti | Penyebab | Dampak |
|---------|-------|---------|--------|
| Wali kosong | B7[v]: guru_idguru=null | storeKelas() line 131-137 tidak ada 'guru_idguru' di $data | Wali tidak pernah tersimpan |
| Jurusan tidak wajib | B7[i]: no error | Service hanya check induk jika jurusan ada, tidak `required` | User bisa buat induk tanpa jurusan (undefined behavior) |
| Case mismatch | B7[ii]: 'X MIPA 1' vs 'X mipa 1' | Jurusan::nama di DB adalah lowercase 'mipa'; bendukNama() ambil as-is | Preview tidak match DB |
| Test crash | B7[iii-vii]: hanya 1 kelas | Test [i] tidak ditolak; fixture tidak robust | Tidak tahu duplikat/wali/mapel benar-benar tidak ditolak atau test gagal awal |

---

## VERIFIKASI C (TIDAK DISELESAIKAN)

**C1. Render Blade** — TIDAK DIJALANKAN (prioritas test B7 logic)

**C2. Otorisasi** — TIDAK DIJALANKAN

**C3. Properti** — TIDAK DIJALANKAN

**C4. Hitungan SELECT COUNT** — TIDAK DIJALANKAN

**C5. Teknis** — TIDAK DIJALANKAN

---

## PERBAIKAN YANG DIPERLUKAN (Untuk Sesi Berikutnya)

### Prioritas P0 (KRITIS)

1. **Wali Kelas:** Tambah ke $data di storeKelas() & updateKelas():
   ```php
   $data = [
       ...
       'guru_idguru' => $this->guru_idguru,
   ];
   ```

2. **Jurusan Wajib untuk Induk:** Di service, tambah validasi:
   ```php
   if ($jenis === 'induk' && $jurusanId === '') {
       $result['errors']['jurusan_id'] = 'Jurusan wajib untuk kelas Induk';
   }
   ```

3. **Case Mismatch:** Periksa Jurusan model field `nama` — apakah ada accessor lowercase?

### Prioritas P1 (TESTING)

- Ulang B7 setelah fix P0 dengan fixture yang robust
- Jalankan C1-C5 akhir

---

## RANGKUMAN

- **GURU:** Bekerja sempurna; laporan LAPORAN_FIX_SELECT.md tentang "Guru kadang tidak bisa disimpan" adalah SALAH/FALSE ALARM.
- **KELAS:** Perbaikan B1, B5, B6 berhasil. B2 partial. B4 (Wali) gagal karena data tidak di-pass. Duplikat & jurusan wajib butuh fix P0 baru.
- **Sesi ini:** A PASS, B FAIL → STOP & REPORT

---

**File diubah:**
```
app/Services/KelasService.php        +26, -8
app/Livewire/KelasComponent.php      +33, -10
resources/views/livewire/kelas-component.blade.php  +13, -3
```

**Status:** BAGIAN A DONE. BAGIAN B INCOMPLETE. BAGIAN C SKIPPED.
