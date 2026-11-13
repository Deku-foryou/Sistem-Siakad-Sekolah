# LAPORAN IMPLEMENTASI LOV P2 — BEASISWA ASSIGN & REVOKE

**Tanggal:** 13 November 2026 · **Sumber:** kode aktual + uji rollback + UAT browser (Edge headless, CAPTCHA_DISABLE=true, data demo + data UJILOV yang dibersihkan setelahnya) · **Cakupan:** hanya P2 (sesuai instruksi).

---

## 1. File yang diperiksa & diubah

| File | Peran | Keterangan |
|---|---|---|
| `app/Livewire/BeasiswaComponent.php` | diubah | Tambah trait `LovSiswa` (+ deklarasi `$siswaId`), `boot()` otorisasi per request, modal Kelola Penerima (`penerimaId`, `alasanPenerima`), `kelolaPenerima()`, `tutupPenerima()`, `tambahPenerima()` (assign), `cabutPenerima()` (revoke by PK), `render()` mengirim `penerimaBeasiswa` & `penerimaList` (aktif dulu lalu histori berhenti), `store/update` FK kosong → `null`. **Tidak ada pemanggilan hapus**; assign/revoke tidak menyentuh `Tagihan` |
| `resources/views/livewire/beasiswa-component.blade.php` | diubah | Kolom Aksi: tombol "Kelola Penerima" (ikon `fa-users`) per beasiswa; modal `#penerimaBeasiswaModal` (info beasiswa, LOV single, alasan opsional, daftar penerima aktif+histori, tombol Cabut `wire:confirm`); script show/hide modal penerima |
| `app/Models/Beasiswa.php` | diperiksa, tidak diubah | `penerima()` hasMany, `berlakuPada()` — perilaku tidak diubah |
| `app/Models/BeasiswaSiswa.php` | diperiksa, tidak diubah | PK `idbeasiswa_siswa`, fillable lengkap, relasi `siswa()`/`kreator()` — tidak diubah |
| `app/Services/Keuangan/TarifService.php` | diperiksa, tidak diubah | `beasiswaBerlaku()` menyaring `status='aktif'` + periode berlaku — tagihan terbit tidak terpengaruh |
| `app/Services/Keuangan/DiskonService.php` | diperiksa, tidak diubah | Beasiswa hanya berlaku untuk tarif non-`individu` (kebijakan prioritas individu > beasiswa dipertahankan) |
| `resources/views/livewire/partials/lov-siswa.blade.php`, `app/Livewire/Concerns/LovSiswa.php`, `app/Services/LovService.php`, `app/Support/Lov.php` | diperiksa, tidak diubah | Fondasi P1 dipakai apa adanya — tidak ada LOV kedua |

Tidak ada perubahan schema, migrasi, route, middleware, dependency, `.env`, atau kebijakan keuangan. Kolom DB `siswa_idsiswa` dst. tidak di-rename.

## 2. Implementasi UI assign/revoke

- Tabel **Beasiswa**: tombol baru (ikon users, tooltip "Kelola penerima") per baris → `kelolaPenerima(id)` menyimpan `penerimaId`, mereset state LOV (`lovReset()`), membuka modal.
- **Modal Kelola Penerima** (`modal-lg`):
  - Ringkasan beasiswa: jenis, nilai terformat, berlaku, badge status.
  - **Tetapkan Siswa**: partial `lov-siswa` (single mode) — pencarian nama/NIS/NISN (min. 2 karakter, debounce 300ms, maks 20 hasil, hanya siswa AKTIF); tombol Pilih per hasil; terpilih menampilkan "Siswa terpilih" + Ganti/Hapus; textarea Alasan (opsional); tombol **Tetapkan ke Beasiswa**.
  - **Daftar Penerima**: `$penerimaList` — nama (+alasan bila ada), NIS, periode berlaku, badge **Aktif/Berhenti**; tombol **Cabut** hanya untuk baris aktif dengan `wire:confirm` ("Cabut beasiswa dari siswa …? Histori penetapan tetap tersimpan.") dan `show-tooltip`; histori berhenti ditampilkan dengan "—" di kolom aksi.
  - Empty state bila belum ada penerima; tombol Tutup membersihkan state.
- Setelah assign/revoke: daftar penerima & kolom "N penerima" di tabel utama otomatis ter-refresh (query ulang pada render), notifikasi konsisten via `LivewireAlert` (`success`/`warning` + `@error` inline), state LOV dibersihkan.

## 3. Integritas & validasi backend

`tambahPenerima()`:
1. `boot()` + middleware route tetap `check.level:superadmin,admin` (tidak diubah).
2. Validasi `siswaId` lewat registry `LovService::selected('siswa', …)` → wajib **siswa AKTIF**; ID palsu/non-aktif → `addError('siswaId', …)`, tidak ada baris dibuat.
3. Beasiswa harus ada (`findOrFail`).
4. Duplikat penerima **aktif** ditolak dengan pesan error (1 baris per pasangan beasiswa–siswa, sesuai unique `beasiswa_siswa_unique` di migrasi).
5. Jika baris pernah dicabut (`berhenti`) → **diaktifkan kembali** (status aktif, `berlaku_dari`=hari ini, `berlaku_sampai`=null) — unik tetap 1 baris; baris tidak dihapus.
6. Semua dalam `DB::transaction`.

`cabutPenerima(int $idbeasiswa_siswa)`:
1. Hanya baris berstatus **aktif** yang bisa dicabut; baris sudah `berhenti` → warning, tanpa perubahan.
2. Anti lintas-beasiswa: baris harus milik beasiswa yang sedang dikelola (`penerimaId`); jika beda → warning, ditolak.
3. Pencabutan = `status='berhenti'` + `berlaku_sampai=hari ini`. **Baris & histori tidak dihapus** (tidak ada `delete()` di komponen).

## 4. Integrasi LOV

Trait `LovSiswa` (P1) dipakai langsung; mode single (komponen tidak mendeklarasikan `siswaIds` → `lovMode()` = `'single'`). Registry `App\Support\Lov` whitelist hanya `'siswa'` — pencarian tetap query terbatas `status='aktif'` + `LIKE` nama/nis/nisn + `LIMIT 20`. Tidak ada dropdown seluruh siswa; tidak ada query siswa di Blade.

## 5. Hasil pengujian

Skrip uji rollback (`storage/app/tmp-uji/uji-lov-p2.php`, **dihapus** setelah dijalankan) + UAT browser.

| Kelompok | Hasil |
|---|---|
| LOV search nama & NIS (siswa uji dibuat dalam transaksi) | 3 PASS |
| Assign: `kelolaPenerima`, ID palsu & siswa `lulus` (non-aktif) ditolak, pilih via LOV, state bersih | 9 PASS |
| Duplikat penerima aktif ditolak (tetap 1 baris) | 2 PASS |
| Pengaruh backend: diskon 50%×Rp100.000 aktif saat assign; `beasiswaBerlaku` memuat beasiswa | 3 PASS |
| Revoke: status→`berhenti`, `berlaku_sampai`=hari ini, baris tidak dihapus, diskon kembali 0, `beasiswaBerlaku` kosong | 5 PASS |
| Cabut baris sudah berhenti (warning, aman) & lintas beasiswa (ditolak) | 2 PASS |
| Re-assign setelah revoke: baris sama aktif kembali, tetap 1 baris (unique) | 2 PASS |
| `render()` mengirim penerimaBeasiswa & penerimaList; `tutupPenerima` membersihkan state | 3 PASS |
| Regresi beasiswa: store/edit/toggleStatus/render | 4 PASS |
| Otorisasi lintas peran (siswa): boot/mount/tambahPenerima/cabutPenerima → 403 | 4 PASS |
| Komponen tidak menyentuh `Tagihan` / `delete()` | 2 PASS |
| `php artisan test` | 2 passed (2 assertions) |
| Pint (file diubah) & `view:cache`/`view:clear` | bersih |
| UAT browser: tambah beasiswa UJILOV via UI → assign Andre Santoso (cari "Andre Santoso" → Pilih → Tetapkan) → muncul di daftar; Cabut → status **Berhenti**, histori (alasan) tetap terlihat, notifikasi sukses | ✓ |

**Total: 37 PASS / 0 FAIL** (verifikasi browser memakai data demo sintetis UJILOV; seluruh baris UJILOV dibeasiswa & beasiswa_siswa **dihapus setelah UAT** — data asli (beasiswa id 18/19/20, 3 penerima demo) tidak tersentuh.)

## 6. Validasi keamanan & histori

- ID siswa tidak dipercaya dari browser: divalidasi registry (aktif) **sebelum** insert/update; duplikat aktif dicegah di level aplikasi **dan** constraint unique database.
- Otorisasi ganda: route `check.level:superadmin,admin` (tidak diubah) + `boot()` per request di komponen (mencegah bypass via request Livewire langsung) + `abort_unless` di setiap method publik.
- Histori penetapan: pencabutan hanya `status`/`berlaku_sampai`; baris, `created_at`, dan `alasan` tetap ada. Daftar penerima menampilkan histori berhenti (read-only) di UI.
- Re-aktivasi menulis `berlaku_dari` baru; jejak tanggal berhenti lama pada baris yang sama digantikan (unique beasiswa+siswa). Kehilangan detail periode siklus lama **pada baris yang sama** adalah keterbatasan bawaan desain satu baris (lihat §9). Tidak ada penghapusan histori baris.
- Tidak ada data tagihan yang diterbitkan/dialokasikan ulang; `TarifService::beasiswaBerlaku()` sudah menyaring `beasiswa_siswa.status='aktif'` sehingga revoke otomatis mengecualikan beasiswa dari perhitungan tagihan **masa depan**.

## 7. Dampak terhadap tarif / tagihan

| Skenario | Perilaku backend (tidak diubah) |
|---|---|
| Tarif **individu** + beasiswa | Beasiswa **tidak** diterapkan (`DiskonService`: hanya jika tarif bukan individu) — prioritas individu > beasiswa dipertahankan |
| Tarif **kelas/global** + beasiswa aktif | Beasiswa berlaku pada `DiskonService::hitung`; `beasiswaBerlaku` mengembalikan beasiswa (dengan filter `beasiswa_siswa.status='aktif'` + periode) |
| Tagihan **sudah diterbitkan** saat assign/revoke | **Tidak berubah** — alokasi & nominal tagihan lama tidak dihitung ulang; hanya perhitungan pembayaran/tagihan berikutnya yang memakai resolver |
| Revoke | Tagihan terbit tetap apa adanya; diskon beasiswa langsung tidak berlaku lagi untuk hitungan baru (diverifikasi: `beasiswaBerlaku` kosong, diskon 0) |
| Batas diskon 100% & pembebasan penuh (`beasiswaBerlaku`/`DiskonService`) | Tidak diubah |

Tidak ada kebijakan keuangan yang diubah atau dibuat. Tidak ada penghapusan tagihan.

## 8. Konsistensi data setelah uji

- Data UJILOV (beasiswa + penerima) dari UAT **dihapus** di akhir; data demo asli utuh (beasiswa id 18/19/20; 3 penerima demo tidak tersentuh — dicek `SELECT` sesudah cleanup).
- Uji rollback memakai `DB::beginTransaction()` + `rollBack()` — tidak ada baris uji menempel.
- Skrip uji & screenshot UAT di `storage/app/tmp-uji/` **dihapus**.

## 9. Risiko & keterbatasan

1. **Histori per-siklus pada baris yang sama**: karena `beasiswa_siswa` unique (beasiswa, siswa), re-assign setelah revoke memakai ulang baris yang sama → `berlaku_dari` diganti (histori *periode* siklus sebelumnya tidak tersimpan di kolom), sedangkan baris & `created_at` tetap. Audit penuh per-siklus memerlukan tabel histori/migrasi — **di luar batasan** (tidak boleh menambah schema); dapat dipertimbangkan pada keputusan pemilik proyek.
2. **`dibuat_oleh`** mencatat pengaktif/terakhir mengaktifkan, bukan jejak per-siklus (konsekuensi unique baris tunggal).
3. **Penerima historis non-aktif ditampilkan** di daftar (dengan badge Berhenti) meski siswa sudah lulus/pindah — disengaja (histori tidak disembunyikan); tidak ada data PII baru.
4. **Jumlah penerima besar**: daftar penerima di modal memakai `->get()` tanpa pagination (konsisten dengan daftar beasiswa existing); bila suatu saat beasiswa punya ribuan penerima, perlu pagination — tidak diperlukan saat ini.
5. **Demo data**: hanya 72 siswa; siswa non-aktif diuji memakai status `lulus` (enum siswa = aktif/lulus/pindah/keluar).
6. **P4 (Kelassiswa/icon picker) & P5 tidak disentuh** sesuai batasan. LOV P3/P1 tidak diubah.

## 10. Konfirmasi

- Tidak ada perubahan `.env` · tidak ada Git write · tidak ada schema/migration/route/middleware/dependency yang diubah · tidak ada data produksi yang diubah permanen.
- Otorisasi route `check.level:superadmin,admin` tidak diubah; `boot()` ditambahkan tanpa mengganti kebijakan level.
