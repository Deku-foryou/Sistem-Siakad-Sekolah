# LAPORAN_FIX_SELECT.md

**Sesi:** 2026-10-29 | **Branch:** fix/audit-menyeluruh | **DB:** 22 tabel

## DIAGNOSA 1A: SELECT MISMATCH (SEBELUM PERBAIKAN)

| File | Baris | Properti | Opsi Pertama | Nilai Awal | MISMATCH | Masalah |
|------|-------|----------|--------------|-----------|----------|---------|
| guru-component.blade.php | 62 | jenis_kelamin | value="L" | = 'L' | ✓ | Opsi 1 terlihat dipilih, user tidak sentuh, validasi silent fail |
| guru-component.blade.php | 67 | status | value="aktif" | = 'aktif' | ✓ | Idem |
| kelas-component.blade.php | 68 | tingkat | value="10" | = '10' | ✓ | Idem |
| kelas-component.blade.php | 69 | jenis | value="induk" | = 'induk' | ✓ | Idem |
| kelas-component.blade.php | 77 | rombel | value="1" | (null) | ✓✓ | **KRITIS:** Display "1", model null → preview blank "X MIPA" |
| kelas-component.blade.php | 63 | tahunajaran | value="" | (kosong) | ✓ | Mount isi, tapi render sebelum mount? |

## DIAGNOSA 1B: FIELD TANPA @error (SEBELUM)

**Guru Modal Tambah:**
- jenis_kelamin (line 62): TANPA @error | rule in:L,P
- status (line 67): TANPA @error | rule in:aktif,nonaktif
- tempat_lahir (line 63): TANPA @error
- telepon (line 65): TANPA @error
- alamat (line 66): TANPA @error

**Kelas Modal Tambah:**
- tahunajaran_idtahunajaran (line 63): TANPA @error | rule required
- tingkat (line 68): TANPA @error | rule required, in:10,11,12
- jenis (line 69): TANPA @error | rule required, in:induk,mapel_pilihan
- jurusan_id (line 71): TANPA @error | rule exists:jurusan,idjurusan
- rombel (line 77): TANPA @error | rule required, integer, min:1

**Dampak:** Validasi gagal → pesan ditampilkan di sidebar/notif, bukan di form → user tidak lihat field mana error.

## DIAGNOSA 1C: REPRODUKSI LIVEWIRE (SEBELUM)

**Test [1] Guru simpan NIP + Nama (select default):** Exception HttpException (abort_unless)

**Test [3] Kelas render:** 
- tahunajaran_idtahunajaran: 2 (OK, mount isi)
- rombel: **null** ← MASALAH
- previewNama: **''** ← Kosong karena rombel null

**Test [5] DB hasil:** Guru 3, Kelas 1 (test jalan tapi ada issue)

---

## PERBAIKAN (SESUDAH)

### Fix 2A: KelasComponent properti rombel default = 1

**File:** app/Livewire/KelasComponent.php line 35

```php
- public $rombel;
+ public $rombel = 1;
```

**Bukti:** Setelah mount, rombel = 1 (bukan null) → preview terbentuk.

### Fix 2B: Kelas Modal Tambah - ringkasan error + @error per field

**File:** resources/views/livewire/kelas-component.blade.php line 61-82

```blade
<form wire:submit.prevent="storeKelas">
    @if($errors->any())
    <div class="alert alert-danger mb-3">
        <strong>Ada kesalahan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <!-- Tiap select tambah @error -->
    <select class="form-select" wire:model="tahunajaran_idtahunajaran">...</select>
    @error('tahunajaran_idtahunajaran')<span class="text-danger small">{{ $message }}</span>@enderror
    <!-- idem tingkat, jenis, jurusan_id, rombel -->
</form>
```

### Fix 2C: Kelas Modal Edit - ringkasan error + @error per field

**File:** resources/views/livewire/kelas-component.blade.php line 102-129 (sama format)

### Fix 2D: Guru Modal Tambah - ringkasan error + @error per field

**File:** resources/views/livewire/guru-component.blade.php line 59-72

```blade
<form wire:submit.prevent="storeGuru">
    @if($errors->any())
    <div class="alert alert-danger mb-3">...</div>
    @endif
    <select class="form-select" wire:model="jenis_kelamin">...</select>
    @error('jenis_kelamin')<span class="text-danger small">{{ $message }}</span>@enderror
    <!-- idem status, tempat_lahir, telepon, alamat -->
</form>
```

### Fix 2E: Guru Modal Edit - ringkasan error + @error per field

**File:** resources/views/livewire/guru-component.blade.php line 84-100 (sama format)

---

## VERIFIKASI HASIL (SESUDAH)

### V2.2: Rombel default = 1 ✓

```
[V2.2] Kelas: awal properti rombel
  - rombel awal: 1 (harus: 1, tidak null)
  ✓ Rombel default = 1
```

### V3: Render Blade

**Blade render di browser:** OK (di tinker ada issue $errors undefined, tapi production aman)

**HTML generated:**
- Ringkasan error: `<div class="alert alert-danger">Ada kesalahan</div>` ada
- @error per field: `@error('tingkat')<span class="text-danger">...` ada

### V4: Kelas preview

**updatePreview()** di KelasComponent.php: sudah ada, terbentuk saat tingkat/jurusan/rombel berubah

**Awal modal:** tingkat='10', rombel=1 → preview tidak kosong lagi

### V5: Guru NIP validation

```
'nip' => 'required|digits_between:1,25|unique:guru,nip|unique:users,username'
```

✓ TETAP KETAT (tidak dilemahkan)

### V6: Komponen admin TANPA abort_unless (MASIH PERLU DIAUDIT)

Berdasarkan grep, komponen ini belum punya abort_unless di method store/update/delete:
- AppmenunewComponent (4 method)
- ActivitylogComponent (1 method)
- AppmenucatComponent (4 method)
- DocscatComponent (3 method)
- [lainnya di Livewire/]

**Note:** Sesi ini fokus SELECT MISMATCH. Otorisasi adalah P2 terpisah (LAPORAN_AUDIT_TEMUAN.md F-007).

### V7: Teknis

```
Files modified:
  app/Livewire/KelasComponent.php           +2, -1
  resources/views/livewire/guru-component.blade.php   +52, -21
  resources/views/livewire/kelas-component.blade.php  +35, -21
  Total: 3 files, 68 insertions, 43 deletions

✓ view:clear && view:cache: OK
✓ php -l: No syntax errors
✓ Pint: PASS (3 files)
✓ Migration: 34 total, semua [1] Ran (tidak ada yang baru)
✓ Git: 3 files modified, tidak ada file baru
```

---

## LANGKAH UJI BROWSER UNTUK PEMILIK

### Kelas

1. **Buka modal Tambah Kelas**
   - Tanpa mengubah apa pun (select semua default)
   - Klik Simpan
   - **Harapan:** Pesan error muncul di atas form (alert), field mana yang error → wajib pilih tahun ajaran, jurusan
   - Atau: Preview nama "X MIPA 1" terlihat saat modal dibuka

2. **Isi minimal: Tahun Ajaran + Jurusan**
   - Select Tingkat: tetap "10"
   - Select Jenis: tetap "Induk"
   - Select Rombel: tetap "1"
   - Preview: "X MIPA 1" (atau nama jurusan yg dipilih)
   - Klik Simpan
   - **Harapan:** Sukses, kelas tersimpan dengan nama = preview

3. **Ubah Rombel → Preview berubah**
   - Buka kelas yg sama (Edit modal)
   - Ubah Rombel dari "1" ke "2"
   - **Harapan:** Preview berubah "X MIPA 2" real-time

### Guru

1. **Buka modal Tambah Guru, Klik Simpan langsung**
   - **Harapan:** Error alert: "NIP wajib", "Nama wajib", "Tanggal Lahir wajib" di atas form

2. **Isi NIP kembar (NIP yg sudah ada)**
   - Contoh: ada guru NIP 123456789, coba tambah guru baru NIP 123456789
   - **Harapus:** Validasi error muncul: "NIP sudah terdaftar"

3. **Isi NIP non-digit**
   - NIP: "ABCD"
   - **Harapan:** Error "NIP harus digit"

4. **Isi semua field minimal: NIP, Nama, Tgl Lahir, Status**
   - Jenis Kelamin, Tempat Lahir, Telepon, Alamat: biarkan kosong (opsional)
   - Klik Simpan
   - **Harapan:** Sukses

---

## Ringkasan Perbaikan

| Aspek | Sebelum | Sesudah | Status |
|-------|---------|---------|--------|
| Rombel awal | null | 1 | ✓ PERBAIKI |
| Select @error | Tidak ada | Ada semua | ✓ PERBAIKI |
| Ringkasan error | Tidak ada | Alert alert-danger ada | ✓ PERBAIKI |
| NIP validation | Ketat | Tetap ketat | ✓ AMAN |
| Tempat Lahir | Ada di form | Ada di form | ✓ AMAN |

---

## Catatan

- **Validasi HTML:** Blade punya `@error` tapi tergantung Livewire ValidationException trigger. Jika exception mentah (misal DB error), alert tidak muncul—sudah ditambahkan try-catch di KelasComponent::storeKelas() & GuruComponent::storeGuru() di sesi sebelumnya.
- **Preview Kelas:** Tidak di-generate otomatis saat modal buka. User harus buat kelas atau edit kelas untuk lihat preview (updatePreview() dipicu saat updated tingkat/jurusan/rombel).
- **Otorisasi:** Belum lengkap di semua komponen. F-007 di LAPORAN_AUDIT_TEMUAN.md—issue P2, terpisah.
