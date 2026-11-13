# LAPORAN IMPLEMENTASI LOV P3 — TARIF INDIVIDU & DATA TAGIHAN

**Tanggal:** 13 November 2026 · **Sumber:** kode aktual + uji rollback + UAT browser (Edge headless, CAPTCHA_DISABLE=true, data demo) · **Cakupan:** hanya P3 (sesuai instruksi).

---

## 1. File yang diperiksa & diubah

| File | Peran | Keterangan |
|---|---|---|
| `app/Livewire/TarifComponent.php` | diubah | Properti `siswa_idsiswa` → `siswaId` + `siswaLabel` (trait `LovSiswa`); `boot()` otorisasi per request; `updatedJenis()` mereset LOV siswa saat jenis != individu; store/update **wajib** siswa aktif (divalidasi registry `LovService::selected`) bila jenis=individu; `edit()` mengisi label dari relasi `$row->siswa`; `resetForm()` membersihkan LOV; **limit 500 dihapus**; nilai FK kosong jadi `null` (fix latent bug insert) |
| `resources/views/livewire/tarif-component.blade.php` | diubah | Dropdown siswa (` siswaOption`) → `<x-lov-siswa>` single mode (Tambah & Edit); pesan error `siswaId` tampil inline |
| `app/Livewire/DataTagihanComponent.php` | diubah | Properti `siswaId` (trait); `boot()` otorisasi per request; validasi cakupan siswa: **wajib** `siswaId` saat scope=siswa + registry aktif (defense in depth); `updatedScope()` mereset semua state siswa/scope; `resetForm()` membersihkan LOV; **limit 500 dihapus** (inline query siswa di view juga dihapus) |
| `resources/views/livewire/data-tagihan-component.blade.php` | diubah | Scope siswa: `<select>` manual `orderBy('nama')->limit(500)` (query inline di view) **dihapus** → partial LOV single; tidak ada lagi pembatasan 500 siswa |
| `app/Livewire/Concerns/LovSiswa.php` | diperiksa, tidak diubah | Fondasi P1 dipertahankan apa adanya (kontrak kompatibel) |
| `app/Services/LovService.php` | diperiksa, tidak diubah | — |
| `resources/views/livewire/partials/lov-siswa.blade.php` | diperiksa, tidak diubah | — |

Tidak ada perubahan schema, migrasi, route, middleware, dependency, atau `.env`. Tidak ada perubahan kebijakan tarif/beasiswa/nominal/penerbitan/otorisasi.

## 2. Implementasi per fitur

### Tarif Individu (`jenis=individu`)
- Dropdown siswa yang load **seluruh 72 siswa aktif** dengan `orderBy('nama')->limit(500)` → diganti pencarian LOV nama/NIS (maks 20 hasil, debounce 300ms).
- Tombol "Pilih" di tiap hasil; terpilih menampilkan label ("Siswa terpilih: Andre Santoso") + tombol **Ganti** / **Hapus**.
- Ganti jenis ke global/kelas → siswa di-reset otomatis; ganti siswa → label terbaru; edit tarif → label dari relasi (meski siswa sudah nonaktif) ditampilkan.
- **Validasi ulang**: `required_if:jenis,individu|exists:siswa,idsiswa` + registry `LovService::selected('siswa', …)` → siswa harus AKTIF — percobaan siswa palsu/lulus ditolak (tidak ada record dibuat).
- Sinkron kolom DB `siswa_idsiswa` tetap dipertahankan (tidak mengubah struktur/data existing).

### Data Tagihan (scope `siswa`)
- Inline query `Siswa::where('status','aktif')->orderBy('nama')->limit(500)->get()` di Blade → dihapus; diganti LOV single mode (sama seperti Tarif).
- `scopeId` sekarang hanya dipakai untuk scope `kelas` (exists:kelas,idkelas). Siswa memakai `siswaId` terpisah.
- Validasi: scope=siswa → `siswaId` wajib + registry aktif; scope=kelas → `scopeId` wajib + `exists:kelas,idkelas`. Pesan lain tidak berubah. Scope `semua` tidak berubah.
- `updatedScope()` membersihkan `scopeId`, `siswaId`, `siswaLabel`, dan state pencarian saat pengguna ganti cakupan. `resetForm()` juga membersihkan LOV.
- Alur `terbitInsidental()` (`TagihanService`), penghitungan nominal, diskon, beasiswa, `tagihan_siswa_jenis_periode_unique`, pembatalan/pembebasan — tidak diubah.

## 3. Hasil pengujian

Skrip uji rollback (`storage/app/tmp-uji/uji-lov-p3.php`, dihapus setelah dijalankan) + UAT browser.

| Kelompok | Hasil |
|---|---|
| Bukti limit 500 dihilangkan (grep lintas file) | 6 PASS |
| LOV search (nama alfabet terakhir, NIS terakhir) | 3 PASS |
| Registry: ID palsu & siswa non-aktif (status `lulus`) ditolak | 3 PASS |
| Tarif individu: siswa palsu/lulus ditolak, LOV pilih/tersimpan, label, edit menampilkan siswa lama, ganti/hapus siswa, ganti jenis reset | 11 PASS |
| Data Tagihan: siswa palsu/lulus ditolak, LOV pilih/tersimpan, terbit valid, ganti scope reset, resetForm bersih, scope kelas valid, scope kelas tanpa scopeId ditolak | 9 PASS |
| Otorisasi lintas peran (siswa login → boot Tarif/Tagihan) | 2 PASS (403) |
| `php artisan test` | 2 passed |
| `view:cache`/`view:clear` (Blade compile) | bersih |
| Pint | bersih (2 file) |
| UAT browser: NIS `20260001` → Andre Santoso terpilih (Tarif & Tagihan), label tampil, Ganti/Hapus berfungsi; livewire roundtrip sukses | ✓ |

**Total: 34 PASS / 0 FAIL**

## 4. Bukti silent limit 500 dihilangkan

```
grep limit(500) TarifComponent.php        → 0 hasil
grep limit(500) DataTagihanComponent.php  → 0 hasil
grep limit(500) tarif-component.blade.php → 0 hasil
grep limit(500) data-tagihan-component.blade.php → 0 hasil
grep siswaOption (tarif blade)            → 0 hasil
```
Pencarian memakai `LovService::search('siswa', …)` → query seluruh tabel `siswa` dengan `WHERE status='aktif'` + `LIKE nama/nis/nisn`, `LIMIT 20`. Siswa alfabet/NIS terakhir ("Zidan Hafiz") dan awalan NIS `2026` semuanya ditemukan.

## 5. Risiko / keterbatasan

1. **Tarif lama bisa mereferensikan siswa yang kini nonaktif** — UI tetap menampilkan label siswa (data historis), tetapi menyimpan tarif/tagihan BARU untuk siswa non-aktif ditolak. Ini disengaja (integritas + data historis tidak hilang).
2. **Demo data hanya 72 siswa**; bukti bahwa siswa ke-501+ kini selalu ditemukan bersifat struktural (registry tanpa limit), bukan diukur dengan data 500+ record.
3. **Tagihan lama (scope siswa) tidak dimigrasi** — hanya alur penerbitan baru yang memakai LOV; data historis tetap.
4. **Form tarif global/kelas** tidak berubah (tanpa LOV siswa — tidak diperlukan).
5. `TarifComponent::$siswa_idsiswa` masih dipakai sebagai kolom DB (`siswa_idsiswa`); sinkronisasi lewat camelCase `$siswaId` → kolom snake_case dipertahankan. Tidak ada rename kolom.
6. **Beasiswa (P2) dan Kelassiswa (P4)** tidak disentuh sesuai batasan.

## 6. Konfirmasi

- Tidak ada perubahan `.env` · tidak ada Git write · tidak ada data produksi yang diubah.
- Semua pengujian dilakukan dalam `DB::beginTransaction()` + `rollBack()` di `finally` — tidak ada data uji tersisa.
- Tidak ada migrasi/route/middleware/dependency yang diubah.
