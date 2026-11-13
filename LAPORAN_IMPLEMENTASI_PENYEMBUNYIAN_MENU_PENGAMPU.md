# LAPORAN IMPLEMENTASI — PENYEMBUNYIAN MENU PENGAMPU (SESUAI KEPUTUSAN AUDIT)

**Tanggal:** 13 November 2026 · **Jenis:** perubahan kecil (2 file) · **Sumber:** `LAPORAN_AUDIT_FITUR_PENGAMPU.md` (keputusan: SEMBUNYIKAN MENU, PERTAHANKAN IMPLEMENTASI) · **Cakupan:** sidebar superadmin + dokumentasi; implementasi Pengampu dipertahankan utuh.

---

## 1. File yang diubah

| File | Perubahan |
|---|---|
| `resources/views/assets/sidebar.blade.php` | `'pengampu'` ditambahkan ke `$menuDisembunyikanSuperadmin` (setelah `'jadwal'`), mengikuti pola penyembunyian menu Jadwal (Lama). Tidak ada perubahan lain |
| `resources/views/docs/artikel/navigasi.blade.php` | Bullet "Fitur Legacy" dan baris tabel "Badge Lama di Fitur Legacy" diperbarui: Pengampu & Jadwal (Lama) dijelaskan sebagai fitur legacy yang **disembunyikan dari sidebar superadmin**; halaman hanya dapat dibuka via URL langsung (mis. `/pengampu`, `/jadwal`) |

**Dipertahankan (tidak disentuh):** route `/pengampu` (`name('pengampu')`, middleware `check.level:superadmin`), `PengampuComponent` + Blade view, `PengampuService`, model `Pengampu`, migration, tabel `pengampu` (18 baris), menu di `appmenunew` (id 14), usermenu superadmin, route & implementasi Jadwal (Lama), seluruh data, Penugasan Mengajar, JadwalKelas. Hak akses backend tidak diubah.

## 2. Hasil pengujian

Browser UAT (Edge headless, data demo, CAPTCHA_DISABLE=true, server uji port 8913, dimatikan setelahnya; skrip & screenshot dihapus):

| Uji | Hasil |
|---|---|
| Sidebar superadmin (`/dashboard`) tidak mengandung "Pengampu" maupun "Jadwal (Lama)" | ✓ (screenshot terverifikasi) |
| Kelompok "Fitur Legacy" tidak tampil (kosong setelah dua menu inti disembunyikan) | ✓ (screenshot) |
| Menu lain tidak berubah: "Jam Pelajaran", "Jadwal Kelas", "Jadwal Guru", "Absensi Harian", "Penugasan Mengajar" tetap tampil | ✓ (textContent DOM) |
| Route `/pengampu` via URL langsung oleh superadmin → halaman terbuka (200) | ✓ |
| Route `/jadwal` (Jadwal (Lama)) tetap dapat diakses via URL — perilaku tidak berubah | ✓ (200; log request `/jadwal` ~6dt) |
| `demo.admin` (level admin) → `/pengampu` → ditolak (halaman "Halaman Tidak Ditemukan", perilaku `check.level:superadmin` yang tidak diubah) | ✓ |
| Guru (`sari.wulandari`) → `/pengampu` → ditolak (halaman yang sama) | ✓ |
| `view:cache`/`view:clear` (compile 3 blade terkait) | bersih |
| `php artisan test` | 2 passed |
| Dokumentasi sesuai kondisi aktual (menu disembunyikan, akses via URL) | ✓ (grep hasil edit) |

Tidak ada uji yang menyentuh/menghapus data; tidak ada mutasi DB. Tidak ada Git write; `.env` tidak diakses; route/middleware/hak akses tidak diubah.

## 3. Catatan

- Penyembunyian ini **sengaja tidak** menghapus menu dari `appmenunew`/`usermenu` — konsisten dengan keputusan audit (entri audit tetap valid: implementasi & data dipertahankan).
- Dokumen `navigasi.blade.php` kini menyatakan fakta yang sama: menu legacy disembunyikan, akses via URL langsung; catatan "Menu yang tidak tampil bukan berarti boleh dibuka lewat URL. Hak akses tetap ditegakkan di belakang" tetap berlaku untuk peran non-superadmin.
- Posisi kategori "Jadwal Pelajaran" (berisi hanya Pengampu & Jadwal (Lama)) kini sepenuhnya tidak tampil untuk superadmin — kelompok kosong memang tidak ditampilkan (perilaku sidebar yang sudah ada).
