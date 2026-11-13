# LAPORAN IMPLEMENTASI LOV REUSABLE + P1 — SIAKAD

**Tanggal:** 13 November 2026 · **Sumber kebenaran:** kode aktual + uji rollback di database demo · **Perintah:** implementasi standar LOV reusable (TAHAP A) + P1 (Pembayaran Manual, Pengumuman multi-siswa) dari `LAPORAN_AUDIT_LOV_SIAKAD.md`.

---

## 1. Arsitektur LOV yang dibuat

```
Browser ── input (debounce 300ms) ──► Component (Livewire)
                                        │  (LovSiswa trait)
                                        ▼
                                App\Services\LovService   ← fungsi search/selected/selectedMany
                                        │
                                        ▼
                                App\Support\Lov          ← registry WHITELIST (hanya kunci terdaftar)
                                        │
                                        ▼
                                App\Models\Siswa (status aktif)
```

**Aturan yang ditegakkan:**
- Registry **eksplisit** — tidak ada nama model/kolom dari request; hanya kunci (`Lov::KEY_SISWA`).
- Query selalu: `WHERE status = 'aktif'` + `LIKE` pada kolom terdaftar + `SELECT` kolom minimal (`idsiswa, nis, nisn, nama, status`).
- Pencarian butuh ≥2 karakter; hasil maks **20** (default) / 50 (clamp), diurutkan `nama` + tie-break `idsiswa`, `distinct`.
- Hasil browser selalu polos `[id, label, sublabel]` — tidak ada instance Eloquent/kolom sensitif dikirim ke client.
- Validasi ulang di backend sebelum simpan: `selected()` (aktif) & `selectedMany()` (aktif + dedup). Komponen pemanggil menentukan scope tambahan bila perlu.
- **Kontrak event Livewire:** `updatedLovSearch` → Livewire debounce 300ms → hasil masuk `lovHasil`. Komponen menambahkan method publik `pilihLovSiswa(id)`, `gantiLovSiswa()`, `hapusLovSiswa()`, `hapusSiswaTerpilih(id)`.
- **Hook** `setelahLovSiswaDipilih(Siswa)` / `setelahLovSiswaDihapus()` dipakai komponen untuk side-effect (mis. reset `tagihanId` di Pembayaran Manual).

### File dibuat
| File | Peran |
|---|---|
| `app/Support/Lov.php` | Registry whitelist (KEY_SISWA) + batas hasil (20/50) |
| `app/Services/LovService.php` | `search()`, `selected()`, `selectedMany()`, `labelUntuk()` |
| `app/Livewire/Concerns/LovSiswa.php` | Trait state+aksi (mode single/multi via `siswaId`/`siswaIds`) + sync chips↔`siswaIds` |
| `resources/views/livewire/partials/lov-siswa.blade.php` | Partial reusable: input pencarian + daftar hasil (max 20) + mode single (label terpilih + Ganti/Hapus) & multi (chips + × per item) |

### File diubah
| File | Perubahan |
|---|---|
| `app/Livewire/PembayaranManualComponent.php` | Dropdown siswa (semua siswa aktif, ~72) → LOV siswa; `pilihSiswa()` dihapus (digantikan LOV); `boot()` otorisasi tiap request; `catat()` **wajib** tagihan milik siswa (fallback ke tagihan siswa lain **DIHAPUS** — celah keamanan yang ditemukan & ditutup); `afterPilih` mereset `tagihanId` |
| `resources/views/livewire/pembayaran-manual-component.blade.php` | Select siswa → include partial LOV (single) |
| `app/Livewire/PengumumanComponent.php` | `siswaList` (semua siswa aktif) dirender per render → **dihapus**; `siswaIds` divalidasi `exists+aktif` + dedup; edit form mengisi chips dari DB; `boot()` otorisasi |
| `resources/views/livewire/pengumuman-component.blade.php` | `<select multiple size=5>` siswa → include partial LOV (multi/chips) |
| `app/Services/PengumumanService.php` | `siswa_ids.*` → `Rule::exists(...)->where('status','aktif')`; dedup jurusan/kelas/siswa |
| `storage/app/tmp-uji/uji-lov-p1.php` | Skrip uji (rollback) — dihapus setelah dijalankan |

**Tidak ada perubahan schema/migrasi/route/business rule alur pembayaran.** Notifikasi, verifikasi, per-tagihan scoping, dan snapshot pengumuman **dipertahankan**.

---

## 2. Cara penggunaan ulang (untuk modul selanjutnya)

```php
// Komponen — cukup pakai trait & sertakan partial:
use App\Livewire\Concerns\LovSiswa;
class TarifLamaComponent extends Component {
    use LovSiswa;
    // (punya public string $siswaId = ''; untuk mode single; atau public array $siswaIds = [] untuk mode multi)
    protected function setelahLovSiswaDipilih(Siswa $row): void { /* side-effect, mis. reset field terkait */ }
}
```
```blade
@include('livewire.partials.lov-siswa', ['lovMode' => 'single', 'siswaLabel' => $siswaLabel, 'siswaTerpilih' => []])
```
Menambah tipe data baru (mis. tagihan): daftarkan di `App\Support\Lov::semua()` + panggil `LovService::search('tagihan', ...)`. Tidak ada perubahan di partial.

---

## 3. Kontrak event & API (ringkas)

| Method | Input | Output/Efek |
|---|---|---|
| `updatedLovSearch(string $v)` | teks ≥ 2 char | `$lovHasil` = array `{id,label,sublabel}` maks 20 |
| `pilihLovSiswa(int $id)` | id dari hasil | validasi `selected()` (aktif) → set `siswaId`/`siswaLabel` (single) atau tambah chip + sync `siswaIds` (multi); dedup; hook `setelahLovSiswaDipilih()` |
| `gantiLovSiswa()` / `hapusLovSiswa()` | — | mode single: bersihkan label/pilihan (+ hook) |
| `hapusSiswaTerpilih(int $id)` | — | mode multi: hapus chip; sync `siswaIds` |
| `isiTerpilihDariIds(array $ids)` | (edit) | chips dari DB, **aktif saja** |
| `lovReset()` | — | kosongkan state LOV (chips/label/`siswaIds`) |

---

## 4. Hasil pengujian

Skrip `storage/app/tmp-uji/uji-lov-p1.php` — `DB::beginTransaction()` + `rollBack()` (tidak ada data operasional tersentuh). **35 PASS / 0 FAIL**, 2 skenario SKIP karena demo DB tidak punya siswa nonaktif / tagihan terbuka tertentu (dicatat apa adanya).

| Area | Hasil |
|---|---|
| Registry & service: pencarian nama/NIS, limit 20/50, aktif-only, dedup, exception untuk key liar | 13 PASS |
| Pembayaran Manual: search→pilih→label→reset tagihan→catat sukses→**tagihan siswa lain ditolak**→ganti siswa→Siswa palsu ditolak | 8 PASS |
| Pengumuman multi-LOV: pilih→chips→dedup→hapus→siswa palsu ditolak (validation)→manipulatI siswaIds ditolak→snapshot target deduplikat | 11 PASS |
| Otorisasi lintas peran: siswa login memanggil `boot()` komponen admin/superadmin → **403** pada keduanya | 2 PASS |
| `php artisan test` | 2 passed |
| `view:cache`/`view:clear` (Blade compile) | bersih |
| Browser UAT (Edge headless, CAPTCHA_DISABLE=true, demo admin/superadmin) | pembayaran modal: cari "Andre"→pilih→label terpilih ✓; pengumuman modal: 2 siswa via LOV → 2 chips ✓ |
| Pint (file yang diubah) | bersih |

---

## 5. Risiko & pekerjaan tersisa

1. **Demo data tidak memuat siswa nonaktif** — skenario "nonaktif tidak muncul" terverifikasi lewat kode registry, tetapi UAT live untuk kasus itu menunggu data nonaktif tersedia (skrip sudah menyiapkan cabang ini bila data ada).
2. **Registry baru berisi `siswa`.** Modul lain (tarif individu, data-tagihan scope siswa, kelassiswa) **belum** dipindahkan ke LOV — sesuai instruksi (jangan lanjut P2/P3/P4/P5).
3. **Scaffold LOV mati** (11 view `*-lov`, method `*LOV()`, `searchlov`) **dibiarkan** — sesuai instruksi jangan diaktifkan; pembersihan = keputusan terpisah (P5).
4. **Tidak ada screenshot produk terlampir yang melanggar data asli** — seluruh uji memakai data demo sintetis.
5. **Hook `boot()`** di kedua komponen memastikan otorisasi berjalan di setiap request Livewire (termasuk update) — ini bukan perubahan aturan bisnis, hanya mempertahankan kebijakan yang sudah ada pada seluruh method publik.

## 6. Konfirmasi

- Tidak ada perubahan `.env`, tidak ada kredensial yang dibaca/ditampilkan/ditulis.
- Tidak ada Git write (`add`/`commit`/`push`/`reset`).
- Tidak ada migrasi/schema/data produksi yang diubah; seluruh uji di-rollback.
- Tidak ada route/middleware/authorization yang diubah; `boot()` hanya MENCEGAH akses yang sebelumnya juga ditolak (defense-in-depth, perilaku tetap sama untuk role yang sah).
- Badge/dokumen: ROADMAP.md ditambahkan 1 baris entri (sesuai aturan proyek, lihat diff).
