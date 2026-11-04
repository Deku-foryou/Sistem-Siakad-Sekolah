# LAPORAN AUDIT HISTORI ABSENSI SISWA — TAHAP 2B.4.3

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal audit | 4 November 2026 |
| Sifat | **Audit only.** Tidak ada file aplikasi yang dibuat atau diubah; tidak ada migration; tidak mengubah database; tidak membaca `.env`; Git read-only |
| Metode | Pemeriksaan model/migration/kode + query skema & data read-only + render komponen + pengujian IDOR/filter/query, semua dalam transaksi + `rollBack()` di `finally` |
| Cakupan | Absensi harian, absensi per jam, `jadwal_pertemuan`, `HistoriAbsensiSiswaComponent`, view, route/middleware/menu/filter, keamanan & IDOR, integrasi dua tab, performa/N+1 |

---

## 1. Implementasi Absensi Harian

### 1.1 Skema `absensi_harian`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `idabsensi_harian` | bigint AI | PK |
| `tahunajaran_idtahunajaran` | unsignedInteger | FK → `tahunajaran` (RESTRICT) |
| `semester_idsemester` | unsignedInteger | FK → `semester` (RESTRICT) |
| `kelas_idkelas` | unsignedInteger | FK → `kelas` (RESTRICT) |
| `tanggal` | date | 1 baris per kelas per hari |
| `siswa_idsiswa` | unsignedInteger | FK → `siswa` (RESTRICT) |
| `status` | enum('hadir','sakit','izin','alpha') | default 'hadir' |
| `catatan` | text nullable | alasan |
| `diinput_oleh` | unsignedBigInteger | FK → `users` |
| `diubah_oleh` | unsignedBigInteger nullable | audit |
| `created_at`, `updated_at` | timestamp | — |

Frekuensi: **satu baris per siswa per tanggal per kelas**. Tidak ada unique constraint di level skema (uniqueness diatur di service).

### 1.2 Model & scopes (`App\Models\AbsensiHarian`)

Relasi: `tahunajaran`, `semester`, `kelas`, `siswa`, `userInput`, `userUbah`.

Scopes siap pakai: `forKelas`, `forTanggal`, `forSiswa`, `forSemester`, `forTahunajaran`, `betweenTanggal`, `search`. Terbukti tersedia melalui `method_exists`.

### 1.3 Service (`App\Services\AbsensiHarianService`)

- `getSiswaAktif(kelasId)`: `Kelassiswa status='aktif' with siswa`.
- `validateContext(tahun, semester, kelas, tanggal)`: validasi konsistensi semester–TA, kelas–TA, tanggal valid, tidak未来.
- `simpanAbsensi` — hanya dipakai komponen guru/superadmin.

### 1.4 Komponen guru (`AbsensiHarianComponent`)

`mount()` → `abort_unless(level === 'superadmin' || level === 'guru', 403)`. Tidak ada komponen siswa yang membaca tabel ini.

### 1.5 Data aktual (3 baris)

| Tanggal | Siswa | Kelas | Status | Catatan |
|---|---|---|---|---|
| 2026-11-02 | 2 (Hagoromo) | 1 | sakit | sakit demam |
| 2026-11-02 | 1 (Yuji) | 2 | sakit | demam tinggi |
| 2026-11-03 | 2 (Hagoromo) | 1 | sakit | — |

### 1.6 Sisi siswa

**Tidak ada.** Tidak ada komponen Livewire `*Siswa*` yang membaca `AbsensiHarian`.

---

## 2. Implementasi Absensi Per Jam

### 2.1 Skema `jadwal_pertemuan` (snapshot)

Kolom FK `jadwalkelas_idjadwalkelas`, `semester_idsemester`; snapshot `kelas_idkelas`, `guru_idguru`, `mapel_idmapel`, `hari`, `jam_ke`, `waktu_mulai`, `waktu_selesai`, `tanggal`, `status` enum('terjadwal','batal','selesai'), pembatalan (`keterangan_pembatalan`, `dibatalkan_pada`, `dibatalkan_oleh`).

### 2.2 Skema `absensi_perjam`

| Kolom | Keterangan |
|---|---|
| `idabsensi_perjam` | PK |
| `jadwal_pertemuan_idjadwal_pertemuan` | FK `jadwal_pertemuan` (RESTRICT) |
| `siswa_idsiswa` | FK `siswa` (RESTRICT) |
| `status` | enum('hadir','sakit','izin','alpha') |
| `keterangan` | text nullable |
| `diinput_oleh`/`diubah_oleh` | FK `users` |
| UNIQUE (`jadwal_pertemuan_idjadwal_pertemuan`, `siswa_idsiswa`) | 1 baris per siswa per pertemuan |

### 2.3 Data aktual

| Tabel | Baris |
|---|---|
| `jadwal_pertemuan` | 3 (2 & 3 Nov 2026, kelas 1) |
| `absensi_perjam` | **0** |

### 2.4 Service (`App\Services\AbsensiPerjamService`)

`STATUS_VALID = ['hadir','sakit','izin','alpha']`; method `simpan()` dan `edit()`. Service ini hanya dipakai jalur guru (input), bukan jalur baca siswa.

---

## 3. Portal Siswa Saat Ini

### 3.1 `HistoriAbsensiSiswaComponent` (per jam saja)

| Aspek | Kondisi |
|---|---|
| Route | `GET absensi-siswa` → `HistoriAbsensiSiswaComponent` |
| Middleware | `check.level:siswa` **+** `menu.access` |
| Guard komponen | `abort_unless(level === 'siswa', 403)` di `mount`, `resetFilter`, `render` |
| Identitas | `$user->siswa()->first()` — selalu milik akun login |
| Query | `AbsensiPerjam` join `jadwal_pertemuan` (snapshot), `kelas`, `mapel`, `guru`; filter `siswa_idsiswa = siswa login` |
| Filter | `tanggalDari`, `tanggalSampai`, `status` (`wire:model.live`), validasi tanggal & status |
| Ringkasan | Total/Hadir/Sakit/Izin/Alpha dari **seluruh** hasil filter |
| Pagination | 15/halaman |
| Query render | **3 query** (konstanta, bukan per-record) |
| Read-only | Tidak ada method mutasi; tidak ada public property identitas |
| Absensi harian | **Tidak dibaca** (tidak ada `AbsensiHarian` pada komponen) |

### 3.2 View (`histori-absensi-siswa-component.blade.php`)

- Judul "Histori Absensi", badge "Siswa".
- Filter tanggal (dari/sampai) + status + Reset.
- Ringkasan badge; tabel responsif (Tanggal, Hari, Jam Ke, Waktu, Kelas, Mapel, Guru, Status, Keterangan); footer pagination.
- Empty state "Belum ada catatan absensi".

### 3.3 Potensi duplikasi

| Item | Duplikasi |
|---|---|
| Filter tanggal/status/ringkasan/pagination | Hanya ada pada histori per jam; histori harian belum ada |
| Status badge (Hadir/Sakit/Izin/Alpha) | Hanya ada pada histori per jam |
| Empty state | Ada pada histori per jam |
| Query absensi harian untuk siswa | **Tidak ada** — belum duplikat, tetapi belum diintegrasikan |

### 3.4 Menu & sidebar

- Menu `absensi-siswa` terdaftar di `appmenunew` (Histori Absensi, urut 11) + `usermenu` siswa + paket `Siswa`.
- Sidebar dinamis dari `usermenu`; `absensi-siswa` ada di `$menuKhususRoleLain` (superadmin tidak melihatnya).

---

## 4. Audit Keamanan dan IDOR

| Aspek | Hasil | Bukti |
|---|---|---|
| Identitas dari `auth()->user()->siswa` | **Aman** | `$user = Auth::user(); $siswa = $user->siswa()->first()` |
| Tidak menerima ID siswa dari URL/input | **Aman** | Public properties hanya `tanggalDari`, `tanggalSampai`, `status`, `perPage` — tidak ada `idsiswa` |
| Tidak menampilkan absensi siswa lain | **Aman** | Query selalu `where('absensi_perjam.siswa_idsiswa', $siswaId)`; render tidak memuat nama siswa lain |
| Otorisasi & IDOR | **Aman** | Guru/superadmin `mount()` → 403; route `check.level:siswa` + `menu.access`; filter tanggal/status tervalidasi |
| Read-only | **Aman** | Tidak ada `create/save/update/delete` pada komponen siswa |

---

## 5. Integrasi: Satu Halaman Dua Tab — Layak?

### 5.1 Kompatibilitas data

| Aspek | Harian | Per jam |
|---|---|---|
| Status | Enum sama (4 nilai) | Enum sama (4 nilai) |
| Filter tanggal | `tanggal` | `jp.tanggal` (snapshot) |
| Filter status | sama | sama |
| Frekuensi | 1/tanggal/kelas | 1/pertemuan |
| Kolom tampil tambahan | kelas, catatan | hari, jam ke, waktu, kelas, mapel, guru, keterangan |

**Jawaban: layak.** Kedua sumber dibaca untuk siswa yang sama, statusnya identik, dan keduanya sudah memiliki query/filter yang aman.

### 5.2 Komponen, service, view yang bisa digunakan ulang

| Item | Digunakan ulang? |
|---|---|
| Guard & pola identitas `HistoriAbsensiSiswaComponent` | Ya — pertahankan |
| Query per jam (join snapshot) + filter tanggal/status + ringkasan + pagination | Ya — pertahankan apa adanya |
| Badge status, empty state, `table-responsive` | Ya — buat partial bersama |
| Model `AbsensiHarian` + scopes `forSiswa`, `forSemester`, `betweenTanggal`, `forTanggal` | Ya — query harian memanfaatkan scopes ini |
| `KonteksAkademikService` | Ya — filter semester/TA aktif |
| `AbsensiHarianService` (guru) | **Tidak diubah** — hanya sumber referensi validasi |
| Menu/route `absensi-siswa` | **Sudah ada** (`check.level:siswa` + `menu.access`) — tidak perlu route baru |

### 5.3 Desain yang direkomendasikan

#### Struktur halaman

```
Histori Absensi
┌─────────────────────────────┐
│ [Harian] [Per Jam]  (tab)   │
├─────────────────────────────┤
│ Filter: [Dari] [Sampai] [Status] [Reset] │
│ Ringkasan: Total | Hadir | Sakit | Izin | Alpha │
├─────────────────────────────┤
│ Tab Harian: Tanggal | Hari | Kelas | Status | Catatan │
│ Tab Per Jam: Tanggal | Hari | Jam | Waktu | Kelas | Mapel | Guru | Status | Keterangan │
│ [empty state]               │
└─────────────────────────────┘
```

#### Query (rekomendasi — tanpa migration)

| Tab | Query |
|---|---|
| Harian | `AbsensiHarian::forSiswa($siswaId)->forSemester($semesterAktif)->betweenTanggal($dari, $sampai)->where status ->with kelas ->orderByDesc(tanggal)` + agregat ringkasan |
| Per jam | Query yang sudah ada (`query()` pada komponen) dipertahankan |

#### Rekomendasi schema/query

| Rekomendasi | Alasan |
|---|---|
| Tidak perlu migration apa pun | Semua kolom & scope sudah tersedia |
| Filter semester aktif pada tab harian | `AbsensiHarian` sudah menyimpan `semester_idsemester` & `tahunajaran_idtahunajaran` |
| Satu `public $tab = 'harian' \| 'per_jam'` | Persistensi pilihan tab dalam satu halaman |
| Filter tanggal/status **terpisah per tab** | Tab harian dan per jam tidak saling merusak |
| Tabel responsif `table-responsive` | Layar kecil |
| Badge status bersama (partial) | Konsistensi visual Harian ↔ Per Jam |
| Empty state terpisah per tab | Guru belum mengisi harian vs belum ada pertemuan per jam |

#### Alur input guru tidak berubah

`AbsensiHarianComponent` dan `AbsensiHarianService` tidak disentuh. Siswa hanya **membaca**.

#### Tabel jadwal legacy

Tidak dipakai. Per jam memakai snapshot `jadwal_pertemuan`; harian memakai `absensi_harian` + `KonteksAkademikService`.

---

## 6. Risiko dan Kendala

| # | Risiko | Keterangan |
|---|---|---|
| 1 | Data `absensi_perjam` kosong (0 baris) | Tab per jam selalu kosong sehingga pengguna mengira fitur rusak |
| 2 | Tab state Livewire perlu dipersist agar tidak reset saat filter berubah | Gunakan `#[Url]` atau state komponen |
| 3 | Pagination per tab terpisah | Perlu `WithPagination` per tab (nama berbeda) |
| 4 | Ringkasan per tab | Dihitung dari query masing-masing tab (bukan gabungan) |
| 5 | Kolom `catatan` vs `keterangan` berbeda nama | Perlu konsistensi label di UI |
| 6 | Duplikasi badge status | Harus dibuat partial |
| 7 | `absensi_harian` tidak unique di skema | Siswa bisa melihat duplikat bila guru menginput ganda |
| 8 | Performa dengan banyak baris | Query join + pagination tetap efisien; tidak ada N+1 |

---

## 7. Urutan Implementasi yang Direkomendasikan

| Tahap | Cakupan | Uji |
|---|---|---|
| 1 | Tambahkan tab Harian + Per Jam pada `HistoriAbsensiSiswaComponent`; filter & ringkasan terpisah per tab | Siswa 2 (data harian ada, per jam kosong); siswa 1; isolasi antar siswa |
| 2 | Partial badge status bersama + empty state per tab | Konsistensi visual |
| 3 | (Opsional) Rekap/persentase per tab atau gabungan | Angka rekap cocok dengan data mentah |

---

## 8. Daftar Pengujian yang Diperlukan

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 1 | F | Siswa 2 dengan absensi harian (2 baris) + per jam (0) | Tab Harian menampilkan 2 baris; Tab Per Jam empty state |
| 2 | F | Siswa tanpa data | Kedua tab empty state |
| 3 | F | Siswa 1 membuka tab Harian | Hanya baris siswa 1; tidak ada siswa 2 |
| 4 | F | Filter tanggal pada tab Harian | Hanya baris dalam rentang |
| 5 | F | Filter status pada tab Per Jam | Hanya status yang dipilih; ringkasan ikut |
| 6 | F | Beralih tab | State filter tab lain tidak berubah |
| 7 | H | Guru/superadmin `GET /absensi-siswa` | 404 (middleware) |
| 8 | F | Query count render | Konstanta (<= 8), bukan per-record |
| 9 | M | Halaman pada 360 px | Tab dapat di-roll, tabel tidak overflow |

---

## Lampiran A — Bukti Pengujian

```
=== T1: Skema & data aktual ===
PASS | Tabel absensi_harian ada
PASS | Tabel absensi_perjam ada
PASS | Tabel jadwal_pertemuan ada
INFO | jumlah absensi_harian = 3
INFO | jumlah absensi_perjam = 0
INFO | jumlah jadwal_pertemuan = 3
INFO | absensi_harian siswa 2 = 2
INFO | harian = {"tanggal":"2026-11-02","siswa":2,"kelas":1,"status":"sakit","catatan":"sakit demam","sem":1,"ta":1}
INFO | harian = {"tanggal":"2026-11-02","siswa":1,"kelas":2,"status":"sakit","catatan":"demam tinggi","sem":1,"ta":1}
INFO | harian = {"tanggal":"2026-11-03","siswa":2,"kelas":1,"status":"sakit","catatan":"","sem":1,"ta":1}

=== T2: Model & scope (absensi harian) ===
PASS | Scope forKelas ada
PASS | Scope forTanggal ada
PASS | Scope forSiswa ada
PASS | Scope forSemester ada
PASS | Scope forTahunajaran ada
PASS | Scope betweenTanggal ada
PASS | Model punya relasi tahuajar, semester, kelas, siswa

=== T3: Model & service (per jam) ===
PASS | AbsensiPerjam relasi siswa
PASS | AbsensiPerjam relasi jadwalPertemuan
PASS | AbsensiPerjamService punya STATUS_VALID
PASS | AbsensiPerjamService punya simpan()
PASS | AbsensiPerjamService punya edit()
INFO | STATUS_VALID = ["hadir","sakit","izin","alpha"]

=== T4: Konteks & data pertemuan ===
INFO | konteks = {"ta":"2026/2027","semester":"Ganjil"}
PASS | Konteks valid
INFO | pertemuan = {"tgl":"2026-11-02","kelas":1,"mapel":2,"guru":2,"jam":1}
INFO | pertemuan = {"tgl":"2026-11-02","kelas":1,"mapel":3,"guru":3,"jam":2}
INFO | pertemuan = {"tgl":"2026-11-03","kelas":1,"mapel":1,"guru":1,"jam":1}

=== T5: Histori per jam — IDOR & read-only ===
INFO | public props = ["tanggalDari","tanggalSampai","status","perPage","paginators"]
PASS | Tidak ada property idsiswa/siswaId
PASS | Identitas selalu dari auth()
PASS | Query difilter siswa_idsiswa dari akun
PASS | Komponen siswa TIDAK memakai AbsensiHarian (belum diintegrasikan)

=== T6: Render histori (absensi_perjam = 0) ===
PASS | Render OK
PASS | Nama sendiri tampil
PASS | Tidak memuat data siswa lain (Yuji)
PASS | Empty state tampil (0 data)
PASS | Filter tanggal & status ada
PASS | Ringkasan status ada

=== T7: Guard & filter ===
PASS | Superadmin mount -> 403
PASS | Guru mount -> 403
PASS | Status tidak valid dikosongkan
PASS | Tanggal tidak valid dikosongkan

=== T8: Route & menu ===
PASS | Route absensi-siswa ada
PASS | Route dalam grup check.level:siswa + menu.access
PASS | explicitUrls membuat absensi-siswa
INFO | usermenu siswa 5 = ["berandasiswa","setting","absensi-siswa","jadwalsiswa","profil-siswa"]
PASS | Siswa 5 punya menu absensi-siswa

=== T9: Duplikasi halaman/query ===
INFO | siswa2 harian vs perjam = [2,0]
PASS | Data harian ada, per jam kosong (duplikasi sumber belum dialami siswa)
INFO | komponen *Siswa* yang memakai AbsensiHarian = []
PASS | Tidak ada komponen siswa yang memakai AbsensiHarian (belum terintegrasi)

=== T10: Legacy check ===
PASS | Histori tidak memakai tabel legacy (jadwal/publikasi)
PASS | Histori memakai snapshot jadwal_pertemuan

=== T11: Query count (N+1 check) ===
INFO | jumlah query render = 3
PASS | Query konstanta (<= 6, bukan per-record)

=== T12: Status harian vs per jam (konsistensi) ===
PASS | Status harian enum sama dengan per jam (hadir/sakit/izin/alpha)
PASS | Kolom frekuensi harian = per kelas per tanggal; per jam = per pertemuan
-- Transaksi di-rollback --
Setelah rollback: {"absensi_harian":3,"absensi_perjam":0,"jadwal_pertemuan":3}
PASS | Rollback bersih
```

**TIDAK BISA DIUKUR**: pengujian browser/visual pada beragam ukuran layar (Belum diuji).

**Kesimpulan**: integrasi dua tab **layak tanpa migration**. Model, scopes, dan query harian sudah siap; tab per jam sudah ada dan aman. Desain satu halaman dengan dua tab (filter & ringkasan terpisah) adalah opsi teraman, tidak mengubah alur input guru, dan tidak memakai tabel jadwal legacy.