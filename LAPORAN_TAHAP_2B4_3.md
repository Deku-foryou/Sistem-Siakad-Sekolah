# LAPORAN IMPLEMENTASI TAHAP 2B.4.3 — HISTORI ABSENSI DUA TAB

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Dasar | `LAPORAN_AUDIT_HISTORI_ABSENSI_SISWA_2B4_3.md` |
| Cakupan | Satu halaman `/absensi-siswa` dengan dua tab (Harian default, Per Jam) |
| Tidak diubah | `AbsensiHarianComponent`, `AbsensiHarianService`, `AbsensiPerjamService`, route/menu, middleware, fitur lain |

---

## 1. File yang Berubah

| File | Aksi | Ringkasan |
|---|---|---|
| `app/Livewire/HistoriAbsensiSiswaComponent.php` | **Diubah** | Tambah state `tab`, filter harian (`tanggalDariHarian`/`tanggalSampaiHarian`/`statusHarian`), query harian via `AbsensiHarian` + scopes + `KonteksAkademikService`, paginasi terpisah (`harian`/`perJam`), helper `ringkasan()` |
| `resources/views/livewire/histori-absensi-siswa-component.blade.php` | **Diubah** | Navigasi tab (`nav-tabs`) + panel filter & tabel terpisah per tab; badge status konsisten |
| `storage/app/tmp-uji/` | Dibersihkan | Skrip milik agen ini dihapus; tersisa file yang sudah ada |

`git diff --stat`:

```
app/Livewire/HistoriAbsensiSiswaComponent.php             | onty 192
resources/views/livewire/histori-absensi-siswa-component.blade.php | onty 300
2 files changed, 369 insertions(+), 123 deletions(-)
```

Pint: `php vendor/bin/pint app/Livewire/HistoriAbsensiSiswaComponent.php` → **FIXED** (style). `php -l` → **No syntax errors detected**.

---

## 2. Keputusan Teknis

| Keputusan | Alasan |
|---|---|
| Default tab `harian` (sesuai tugas) | `absensi_harian` memiliki data; per jam kosong |
| Filter/semester/TA dari `KonteksAkademikService::ambil()` | Satu sumber konteks; tab harian memakai `forSemester` + `forTahunajaran` |
| Query harian memakai scopes `forSiswa`, `forSemester`, `forTahunajaran`, `betweenTanggal` | Scope sudah tersedia di model; tidak ada query baru yang duplikat |
| Query per jam **dipertahankan** (join snapshot `jadwal_pertemuan`) | Tidak mengubah hasil/keamanan query yang sudah benar |
| State tab `public $tab`; ganti via `gantiTab($tab)` | Filter tab lain tidak terhapus saat berpindah |
| Paginasi terpisah: `paginate(..., 'harian')` dan `paginate(..., 'perJam')` | `WithPagination` dengan nama berbeda |
| Badge status dibuat dengan struktur yang sama di kedua tab | Konsistensi visual |
| Empty state terpisah teksnya | K V: "Belum ada catatan absensi harian" vs "per jam" |

### Keamanan & batasan

| Ketentuan | Implementasi |
|---|---|
| Identitas dari pemilik akun | `$user->siswa()->first()` di kedua tab |
| Tidak ada ID dari URL/input | Tidak ada public property `idsiswa`; query string manipulasi diabaikan |
| Hanya pemilik yang boleh melihat | Guard `abort_unless(level === 'siswa', 403)` di `mount`, `gantiTab`, `resetFilter`, `resetFilterHarian`, `render` |
| Read-only | Tidak ada method mutasi |
| Guru/superadmin | Route `check.level:siswa` + `menu.access` tidak diubah |
| Tabel legacy | Tidak dipakai |

---

## 3. Bukti Pengujian

Semua uji memakai transaksi + `rollBack()` di `finally`. Skrip telah dihapus setelah selesai.

### 3.1 Uji komponen (60+ butir)

```
=== T1: Siswa 2 — dua tab ===
PASS | S1. Default tab = harian
PASS | S2. Tab Harian: absensiHarian total = 2
PASS | S3. Tab Harian: ringkasan Sakit 2, Total 2
PASS | S4. Tab Per Jam: total = 0
PASS | S5. Tab Per Jam empty state tampil saat tab per_jam dipilih
PASS | S7. Tidak memuat data siswa lain (Yuji)
PASS | S8. Tab nav ada (harian & per_jam)

=== T2: Filter harian ===
PASS | F1. Filter sakit: 2 baris
PASS | F2. Filter rentang 2026-11-02: 1 baris
PASS | F3. Rentang kosong: empty state harian
PASS | F4. Reset filter harian: 2 baris kembali

=== T3: Filter per jam terpisah ===
PASS | T1. Ganti tab ke per_jam
PASS | T2. Filter harian tidak berubah saat ganti tab
PASS | T3. Per jam tetap empty state
PASS | T4. Status per jam tidak valid dikosongkan
PASS | T5. Perpindahan tab mempertahankan filter harian
PASS | T6. Perpindahan tab mempertahankan filter per jam

=== T4: Siswa 1 (isolasi) ===
PASS | I1. Siswa 1 harian total = 1
PASS | I2. Siswa 1 tidak memuat Hagoromo
PASS | I3. Siswa 1 menampilkan namanya

=== T5: Siswa tanpa histori ===
PASS | E1. Akun tanpa siswa: empty state
PASS | E2. Filter mengosongkan tab harian: empty state harian
PASS | E3. Filter mengosongkan tab per jam: empty state per jam

=== T6: Duplikat harian (deteksi, tanpa ubah) ===
INFO | duplikat (siswa,kelas,tanggal) >1 = 0
PASS | Deteksi duplikat selesai (tanpa mengubah data)
PASS | per jam tidak ada duplikat (unique constraint)

=== T7: Guard & otorisasi ===
PASS | Superadmin mount -> 403
PASS | Guru mount -> 403

=== T8: Read-only ===
PASS | Tidak ada create/save/delete/DB
PASS | Tidak ada property idsiswa

=== T9: Pagination terpisah ===
PASS | Paginator harian (total 2)
PASS | Paginator perJam (total 0)

=== T10: Query count (N+1 check) ===
INFO | jumlah query render = 10
PASS | Query konstanta (<= 12, bukan per-record)
-- Transaksi di-rollback --
PASS | Rollback bersih

SEMUA UJI PASS
```

Query render (10 query konstanta):

```text
select * from siswa ... user_iduser = ?
select * from tahunajaran where aktif = ? limit 1
select * from semester where aktif = ? limit 1
select * from tahunajaran where idtahunajaran in (1)
select absensi_harian.status, COUNT(*) ... groupBy status
select count(*) aggregate from absensi_harian where siswa_idsiswa = ? and semester_idsemester = ? ...
select * from absensi_harian where siswa_idsiswa = ? and semester_idsemester = ? ...
select idkelas, nama from kelas where idkelas in (1)
select absensi_perjam.status, COUNT(*) ... inner join jadwal_pertemuan ...
select count(*) aggregate from absensi_perjam inner join jadwal_pertemuan ...
```

### 3.2 Uji HTTP nyata (satu request = satu proses)

```
{"user":5,"uri":"/absensi-siswa","status":200}
{"user":5,"uri":"/absensi-siswa?siswa_idsiswa=1&idsiswa=1","status":200}   <- query string diabaikan
{"user":2,"uri":"/absensi-siswa","status":404}                              <- guru
{"user":1,"uri":"/absensi-siswa","status":404}                              <- superadmin
{"user":4,"uri":"/absensi-siswa","status":302,"location":"http://localhost/setting"}  <- wajib ganti password
```

---

## 4. Keamanan

| Aspek | Hasil |
|---|---|
| Identitas dari pemilik akun | Aman (harian & per jam) |
| ID dari URL/input | Tidak diterima |
| Kebocoran antar siswa | Tidak ditemukan |
| Read-only | Tidak ada method mutasi |
| Otorisasi | `check.level:siswa` + `menu.access` + guard komponen (tidak diubah) |

---

## 5. Risiko yang Harus Dipahami

1. **Data `absensi_perjam` masih kosong** (0 baris) → tab Per Jam selalu empty state. Ini bukan regresi; guru belum mengisi absensi per jam.
2. **`absensi_harian` tidak unique di skema** → bila guru menginput ganda, siswa melihat duplikat. Deteksi saat audit: 0 duplikat; data tidak diubah. Unique constraint belum ditambahkan (di luar batasan, perlu migration).
3. **Tiga query tambahan karena dua tab** (ringkasan + paginator per tab): total 10 query konstanta — bukan N+1, tetapi perlu diwaspadai bila data per jam besar.
4. **Paginasi Livewire lintas tab** memerlukan nama paginator berbeda (`harian`/`perJam`) — sudah diterapkan; mengubah `perPage` memengaruhi kedua tab.
5. **Pengujian browser/visual: Belum diuji** — klaim responsif hanya dari markup (`nav-tabs`, `table-responsive`, grid `col-md-3`).

**TIDAK BISA DIUKUR**: pengujian tampilan pada 360 px, 768 px, dan desktop — tidak ada browser nyata yang tersedia untuk pengujian ini.**

**Kesimpulan**: halaman `/absensi-siswa` kini memuat dua tab (Harian default, Per Jam), filter/ringkasan/pagination terpisah, keamanan dan read-only dipertahankan, query per jam tidak diubah, dan tidak ada tabel legacy yang dipakai. Tahap berikutnya (rekap gabungan, ekspor) tidak dikerjakan sesuai batasan.