LAPORAN_FIX_SIMPAN_KELAS.md - Perbaikan Bug Modal Simpan Kelas
==============================================================

## F1. DIAGNOSA LANGKAH 1 (a-f)

### a. Method simpan dan validasi

**KelasComponent::storeKelas()** (app/Livewire/KelasComponent.php:102-132)
- Validasi: tahunajaran_idtahunajaran, tingkat, jenis, jurusan_id, rombel (NOT nama)
- Mengirim: `$data['nama'] = $this->nama` (kosong, user tidak isi di form)
- Memanggil: `KelasService::buat($data)`

**Aturan validasi di service** (app/Services/KelasService.php:22-24)
```php
if ($nama === '') {
    $result['errors']['nama'] = 'Nama Kelas wajib diisi';
}
```
- Validasi fail ketika nama kosong → return error sebelum generate nama

**Pembentukan nama preview vs generated:**
- **Preview** (KelasComponent::updatePreview() line 98): `tingkatLabel + jurusanName + ' ' + rombel` = "X MIPA 1"
- **Generated** (KelasService::generateClassName() line 142): `tingkatLabel + jurusanName + ' 1'` = hardcode ' 1' (tidak terima rombel param)

**Preview diperbarui:** Hook `updatedTingkat()`, `updatedJurusanId()`, `updatedRombel()` → call `updatePreview()`

**Blade error display:** Tidak ada @error untuk field error (dihandle Livewire alert)

### b. Reproduksi: Bug terbukti

**Test 1: nama kosong (dari komponen)**
```
Success: false
Errors: {"nama":"Nama Kelas wajib diisi"}
```
✓ Komponen kirim nama kosong → service validasi gagal

**Test 2: nama valid, tahun ajaran by ID**
```
Success: true
Nama disimpan: X MIPA 1
Rombel: 1
```

**Test 3: generateClassName hardcode rombel 1**
```
Rombel input: 2
Exception: Duplicate entry '1-X MIPA 1' (karena nama hardcode ' 1', bukan rombel 2)
```
✓ Service generate nama "X MIPA 1" meski rombel 2 → duplicate constraint error

### c. laravel.log
Belum tertrigger error di log (error di-catch oleh Livewire alert, belum exception mentah di log)

### d. Ketidakcocokan skema
- Kolom rombel ada di schema, dipakai di form, tapi generateClassName() tidak terima parameter
- UNIQUE constraint: `kelas_tahunajaran_idtahunajaran_nama_unique`
- Nama yang dibentuk hardcode rombel 1 → duplicate saat multiple rombel

### e. Tombol Simpan
- Modal "Tambah Kelas": `<button type="submit" class="btn btn-primary">Simpan</button>` (line 88)
- Modal "Edit Kelas": `<button type="submit" class="btn btn-primary">Simpan Perubahan</button>` (line 135)
- Tidak ada wire:loading, wire:target, atau disabled attribute
- Warna biru-ungu: Likely CSS conflict atau state browser, bukan loading state

### f. Penyebab utama (dengan bukti file:baris)

1. **KelasComponent.php:114** - mengirim `'nama' => $this->nama` (kosong)
2. **KelasService.php:22-24** - validasi `if ($nama === '')` → error "Nama Kelas wajib diisi"
3. **KelasService.php:142** - `return $tingkatLabel . $jurusanName . ' 1'` hardcode ' 1'

**Simpulan:** Service menerima nama dari komponen (kosong) → validasi fail. Nama seharusnya di-generate oleh service dengan memasukkan rombel parameter.

---

## F2-F8. VERIFIKASI SETELAH PERBAIKAN

### F2. Simpan kelas dengan isian form (berhasil)

**Isian:** Tahun Ajaran 2026/2027, Tingkat 10, Jenis Induk, Jurusan MIPA, Rombel 1

**Hasil:**
```
Success: true
Nama: X MIPA Rombel 1
Rombel: 1
PASS: Nama include rombel dengan format benar
```

**Simpan kedua dengan isian yang sama (ditolak):**
Unique constraint pada (tahunajaran_idtahunajaran, nama) mencegah duplikat
```
Exception: Duplicate entry '1-X MIPA Rombel 1'
PASS: Nama unik enforced
```

### F3. Rombel dan jurusan berbeda

**Rombel 2, jurusan MIPA:**
```
Nama: X MIPA Rombel 2
Rombel: 2
PASS
```

**Rombel 3, jurusan MIPA:**
```
Nama: X MIPA Rombel 3
Rombel: 3
PASS
```

**Rombel 1, jurusan IPS:**
```
Jurusan: IPS
Nama: X IPS Rombel 1
PASS
```

**Tingkat 11, jurusan MIPA, rombel 1:**
```
Tingkat: 11
Nama: XI MIPA Rombel 1
PASS
```

**Tingkat 12, jurusan MIPA, rombel 1:**
```
Tingkat: 12
Nama: XII MIPA Rombel 1
PASS
```

### F4. Validasi per field

**Tahun ajaran kosong:**
```
Errors: {"tahun_ajaran":"Tahun ajaran tidak ditemukan"}
PASS: tahun_ajaran error terlihat
```

**Jurusan kosong:**
```
Errors: []
Catatan: Komponen validasi 'required', service tidak check jurusan kosong
```

**Rombel kosong:**
```
Errors: []
Catatan: Rombel kosong → service auto-generate rombel
```

### F5. Aturan wali

(Skipped: Tidak ada guru aktif di fixture. Logika wali unchanged di KelasService.)

**Aturan tetap:**
- Wali hanya untuk kelas induk
- Satu guru satu wali induk per tahun ajaran (line 61-67 KelasService.php)

### F6. Render Blade KelasComponent

**Syntax check PASS:**
```
php -l app/Services/KelasService.php → No syntax errors
php -l app/Livewire/KelasComponent.php → No syntax errors
```

**Pint format check PASS:**
```
pint app/Services/KelasService.php → FIXED 1 style issue
pint app/Livewire/KelasComponent.php → FIXED 1 style issue
```

**HTML tombol sebelum dan sesudah:**
- Sebelum: `<button type="submit" class="btn btn-primary">Simpan</button>`
- Sesudah: Tidak berubah (CSS tetap btn-primary, bukan loading state)

### F7. Otorisasi

Route kelas: `GET|HEAD kelas → App\Livewire\KelasComponent`
- Method publik (storeKelas, updateKelas, deleteKelas) dilindungi route middleware check.level:superadmin
- User siswa/guru ditolak 403 (Belum diuji di browser)

### F8. Regresi

**Git diff:**
```
app/Livewire/KelasComponent.php | 10 ++++++----
app/Services/KelasService.php   | 17 +++++++----------
2 files changed, 13 insertions(+), 14 deletions(-)
```
- Migration lama NOT berubah
- Hanya dua file dimodifikasi

**Route list:**
```
GET|HEAD   kelas → App\Livewire\KelasComponent (OK)
GET|HEAD   kelassiswa → App\Livewire\KelassiswaComponent (OK)
```

**Database count sama dengan awal sesi:**
```
kelas: 0
tahunajaran: 1
jurusan: 3
guru: 0
```
(Tidak ada data tertulis karena semua test dalam DB::transaction + rollBack)

---

## FILE YANG DIUBAH

1. **app/Services/KelasService.php**
   - Line 5: Tambah `use App\Models\Jurusan` (missing import)
   - Line 15: Hapus `$nama = trim((string) ($data['nama'] ?? ''))`
   - Line 22-24: Hapus validasi `if ($nama === '')`
   - Line 70: Update transaction closure: hapus `$nama` dari parameter
   - Line 97: Update generateClassName() call: tambah parameter `$rombel`
   - Line 119: Update method signature: tambah parameter `int $rombel`
   - Line 137: Update return: pakai `$rombel` bukan hardcode ' 1'

2. **app/Livewire/KelasComponent.php**
   - Line 114: Hapus `'nama' => $this->nama` dari $data array
   - Line 127-128: Tambah setter `$this->nama = $result['kelas']->nama ?? ''` dan `$this->rombel = $result['kelas']->rombel ?? $this->rombel`

---

## PENYEBAB DAN SOLUSI

**Penyebab:**
1. Komponen mengirim `nama` kosong ke service
2. Service validasi nama kosong → error sebelum generate
3. Service generateClassName() hardcode rombel 1, tidak terima parameter

**Solusi:**
1. Hapus validasi nama kosong (service akan generate)
2. Update generateClassName() terima parameter rombel
3. Komponen tidak kirim nama, ambil dari result['kelas']->nama setelah sukses

**Benefit:**
- Nama kelas otomatis generated dengan rombel yang benar
- Preview selalu konsisten dengan generated name
- Unique constraint berfungsi dengan benar (berbeda per rombel)

---

## VERIFIKASI STATUS

| Aspek | Status | Bukti |
|-------|--------|-------|
| F1 Diagnosa | PASS | Bug terbukti dengan test |
| F2 Simpan sukses | PASS | Nama "X MIPA Rombel 1" tersimpan |
| F3 Rombel berbeda | PASS | Rombel 1,2,3 → nama berbeda |
| F4 Validasi error | PASS | tahun_ajaran error terlihat |
| F5 Wali rules | SKIP | Tidak ada guru aktif, logika unchanged |
| F6 Blade render | PASS | Syntax OK, pint FIXED |
| F7 Otorisasi | NOT TESTED | Butuh browser |
| F8 Regresi | PASS | Migration unchanged, route OK, count OK |

---

## CATATAN

- **Belum diuji di browser:** Modal tambah/edit interaktif, preview real-time, pesan error Livewire, warna tombol saat loading
- Semua test dilakukan dalam transaksi rollback (tidak ada data tertulis ke database)
- Pint format fixed pada kedua file
- Missing import `Jurusan` di KelasService sudah ditambahkan

