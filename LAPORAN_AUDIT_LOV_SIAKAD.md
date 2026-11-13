# LAPORAN AUDIT LOV (List of Values) — SIAKAD

**Tanggal:** 13 November 2026 · **Jenis:** AUDIT SAJA — tidak ada kode yang diubah · **Sumber:** inspeksi langsung source code (component, view, service, schema) pada `C:\project-reyhan\template web`
**Konteks data:** database `templatedb` (read-only); ID referensi diverifikasi dari DB bila diperlukan.

> Cara membaca: kolom **Mode** = mekanisme pemilihan aktual (diperiksa di kode, bukan asumsi). Status mengikuti 5 klasifikasi pada mandat audit. Semua temuan disertai path file.

---

## 1. Ringkasan Angka

| Ukuran | Jumlah |
|---|---|
| Livewire component yang diperiksa | **67** (seluruh `app/Livewire/*.php`) |
| View Blade yang diperiksa | **±150** (seluruh `resources/views/livewire/**/*.blade.php`) |
| Form/halaman dengan field relasional | **46** |
| Mekanisme pemilihan yang diperiksa | **100** titik `form-select` + **13** `*-lov.blade.php` + 1 pola search-input + 2 checkbox group |
| **LOV berfungsi** (pola `search → pilih`) | **2** (icon picker `appmenunew`; siswa picker `wali-relasi`) |
| **LOV butuh perbaikan** | **2** (`icon-lov`; beasiswa assignment — UI tidak tersambung) |
| **Belum ada LOV (dead code superadmin/modal scaffold)** | **12** view `*-lov` (semuanya dikomentari) + **13** komponen scaffold (`searchlov`, `*LOV($id)`, `updatingSearchlov()`) yang tidak pernah dipanggil |
| **Candidate LOV baru (BELUM ADA LOV)** | **4** (lihat prioritas) |
| **Dropdown sudah memadai** | **~78** seluruh enum/filter/TA/semester/mapel/guru/kelas/kategori/level/jenis/status/perPage |
| **Tidak membutuhkan LOV** | **~20** (input teks/tanggal/angka, konteks otomatis, export, laporan) |

**Kesimpulan singkat:** implementasi LOV di SIAKAD **belum konsisten**. Yang "mirip LOV" pada dasarnya adalah (a) **scaffold LOV mati** yang tersisa dari pembuatan awal kode (modal tidak pernah dirender), dan (b) **dua implementasi manual yang hidup** (icon picker `appmenunew`, dan siswa picker di `wali-relasi`). Tidak ada komponen LOV reusable, tidak ada service pencarian bersama, dan tidak ada pola yang dipakai lintas halaman.

---

## 2. Temuan Kunci (TL;DR)

| # | Temuan | Lokasi | Dampak |
|---|---|---|---|
| T1 | **12 view `*-lov` tidak pernah dirender** — semua `@include`-nya dikomentari (`{{-- @include('livewire.xxx-lov') --}}`). Isinya adalah tabel kolom mentah (users-lov bahkan menampilkan `password` dan `remember_token`) tanpa pencarian yang berfungsi. | `resources/views/livewire/*-lov.blade.php` | Dead code, tidak dipakai |
| T2 | **Scaffold LOV di setiap komponen** — `$searchlov`, `updatingSearchlov()`, `resetPage('pageLOV')`, `xxxLOV($id)`, komentar `// aktifkan dan sesuaikan jika menggunakan LOV` — tidak pernah dipanggil dari view mana pun. Pola yang sama juga ada di 5 controller superadmin (`UsersController`, `RolesController`, `PermissionsController`, `Model_has_rolesController`, `Role_has_permissionsController`), method `*lov()` yang tidak memiliki route terdaftar (diverifikasi dengan grep `lov` pada folder `routes/` — hanya menemukan berkas legacy `aplikasiroute/usersroute.php` yang juga tidak dimuat di mana pun). Method `usersLOV()`, `usersOption()`, `appmenuLOV()` dll. memanggil `User::get()` (semua user, semua kolom) jika dieksekusi. | `app/Livewire/UsersComponent.php:40,240,114-117` — pola sama di `DocsComponent`, `DocscatComponent`, `AppmenucatComponent`, `AppmenunewComponent`, `RolecatComponent`, `RolesComponent`, `PermissionsComponent`, `ModelHasRolesComponent`, `RoleHasPermissionsComponent`, `UsermenuComponent` + 5 controller di atas | Beban mental, risiko bila "diaktifkan" tanpa audit; logika authorization tidak konsisten dengan konvensi `abort_unless(superadmin)` |
| T3 | **`assignToSiswa`/`revokeFromSiswa` Beasiswa tidak punya UI** — tidak ada pemanggil di view `beasiswa-component.blade.php`. Beasiswa tidak bisa dihubungkan ke siswa lewat aplikasi (hanya lewat DB). | `app/Livewire/BeasiswaComponent.php:146,170`; `resources/views/livewire/beasiswa-component.blade.php` | Fitur beasiswa tidak operasional untuk assign manual |
| T4 | **Silent limit 500 siswa** pada dropdown `tarif` (jenis individu) & scope `siswa` di `data-tagihan` — siswa ke-501+ tidak bisa dipilih, tanpa pesan. | `app/Livewire/TarifComponent.php:~30` (`Siswa::where('status','aktif')->orderBy('nama')->limit(500)->get()`); `resources/views/livewire/data-tagihan-component.blade.php` (dan PHP terkait) | Siswa tertentu tidak dapat ditagih tarif individu / ditagih manual |
| T5 | **`Pembayaran Manual` memuat SEMUA siswa aktif** (tanpa limit/pencarian) ke satu dropdown. | `app/Livewire/PembayaranManualComponent.php` (`$siswas = Siswa::where('status','aktif')->orderBy('nama')->get();`) | Performa jatuh saat data besar; UX tidak bisa dipakai |
| T6 | **`Pengumuman` target siswa memuat SEMUA siswa aktif** (`Siswa::where('status','aktif')->orderBy('nama')->get(...)`), multi-select biasa (`<select multiple size="5">`). | `app/Livewire/PengumumanComponent.php:264-ish (render data)` | Performa render buruk; salah klik risk |
| T7 | **Hanya 2 LOV yang nyata berfungsi** — icon picker (`appmenunew`, filter `stripos()` per render, scan direktori `public_path('asset_offline/icon')` **setiap render**) dan siswa picker `wali-relasi` (search + pilih, pola terbaik yang ada). | `app/Livewire/AppmenunewComponent.php:292-330` (icon), `app/Livewire/WaliRelasiComponent.php:66-90` (relasi siswa) | LOV tidak konsisten; UX tidak seragam |
| T8 | **Backend validasi `exists:` tetap ketat** — mis. `jurusanIds.* => integer exists:jurusan,idjurusan`, `siswaIds.* => exists:siswa,idsiswa`, `kelasIds.* => exists:kelas,idkelas`. | `app/Livewire/PengumumanComponent.php:148-153` | Relasi tidak bisa "colong data" dari request Livewire palsu |
| T9 | **Tidak ada LOV/autocomplete di luar aplikasi** — tidak ada TomSelect/Select2; satu-satunya filter berbasis pencarian teks adalah Livewire `search` + `wire:model.live.debounce` atau pola `cariSiswaRelevan(wali, search, 20)`. | seluruh view; `WalimuridService::cariSiswaRelevan()` | Tidak ada dependency baru yang perlu diaudit |
| T10 | **Ketergantungan menu LOV pada `usermenu`** — halaman superadmin (`users`, `rolecat`, `appmenucat`, `appmenunew`, `setting`) tidak memiliki LOV dan menurunkan dari `usermenu` (tidak berubah). | `app/Providers/AppServiceProvider.php:26-38` | LOV bukan sumber authorization; hanya tampilan |

---

## 3. Matriks Audit LOV per Modul

Legenda status: **LOV SUDAH BAIK** · **LOV PERLU PERBAIKAN** · **BELUM ADA LOV** · **DROPDOWN SUDAH CUKUP** · **TIDAK MEMBUTUHKAN LOV**.

### 3.1 Manajemen Pengguna

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Pengguna | Users (`users`, `users-component`) | `level` | `<select>` enum (6 nilai) | DROPDOWN SUDAH CUKUP | — | — |
| Pengguna | Users | `usermenu` (assign paket menu) | halaman terpisah `UsermenuComponent` | BELUM ADA LOV (scaffold mati) | Dropdown user dinamis perlu pencarian bila jumlah user >100 | B |
| Pengguna | Usermenu | `appmenunew_idappmenunew` | `<select>` (menu ~46, statis) | DROPDOWN SUDAH CUKUP | — | — |
| Pengguna | Users | — (password, name, email, username) | Input teks | TIDAK MEMBUTUHKAN LOV | — | — |

### 3.2 Data Master Akademik

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Akademik | Siswa | `status`, `jenis_kelamin` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Guru | `mapel_ids[]` (guru–mapel) | Checkbox grid + filter teks sisi klien (`mapelSearch`) | DROPDOWN SUDAH CUKUP | Filter kecil (`Mapel ~15`) — tetap ok; tambahkan tombol "pilih semua/bersihkan" saja | — |
| Akademik | Mapel | `kelompok` | `<select>` enum (wajib/pilihan) | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Kelas | `tahunajaran_idtahunajaran` | `<select>` (~1–3 TA) | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Kelas | `guru_idguru` (wali kelas) | `<select>` semua guru (~16) | DROPDOWN SUDAH CUKUP | Semakin bertambah guru, pertimbangkan LOV | B |
| Akademik | Kelas | `jurusan_id` | `<select>` (~4 jurusan) | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Kelas | `tingkat`, `jenis`, `rombel` | `<select>` enum / angka | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Kelassiswa | `tahunajaran_id`, `kelas_idkelas` | `<select>` | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Kelassiswa | `siswa_ids[]` (multi) | `<select multiple size="8">` maks 50 siswa per kelas | **BELUM ADA LOV** | **B (LOV direkomendasikan)** — search + tambah incremental saat 100+ siswa/kelas | P4 |
| Akademik | Jurusan | `aktif` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Akademik | Jurusan | `kode`, `nama`, `deskripsi` | Input teks | TIDAK MEMBUTUHKAN LOV | — | — |

### 3.3 Tahun Ajaran & Semester

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| TA/Semester | Tahun Ajaran | `nama` (2026/2027) | Input teks (format bebas) | TIDAK MEMBUTUHKAN LOV | — | — |
| TA/Semester | Semester | `tahunajaran_idtahunajaran` | `<select>` (TA aktif) | DROPDOWN SUDAH CUKUP | — | — |
| TA/Semester | Semester | `nama` | `<select>` (Ganjil/Genap) | DROPDOWN SUDAH CUKUP | — | — |

### 3.4 Jadwal & Jam Pelajaran

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Jadwal | Jamhari | `jurusan_id`, `kelas_id` | `<select>` (jurusan ~4; kelas ~12) | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | Jamhari | `jenis` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | JadwalKelas | `semester_idsemester` | `<select>` | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | JadwalKelas | `jurusan_id` (filter) | `<select>` | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | JadwalKelas | `mapel_id` | `<select>` (~15 mapel) | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | JadwalKelas | `guru_idguru` | `<select>` semua guru aktif (~16) | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | Jadwal (Lama) | `filterSemester`, `filterKelas`, `mapelId`, `guruId` | `<select>`; guru dibatasi bila pengampu terpilih | DROPDOWN SUDAH CUKUP | (Legacy — jangan perlu diperbaiki bila akan dipensiunkan) | — |
| Jadwal | Pengampu | `semester_idsemester`, `kelas_idkelas`, `mapel_idmapel`, `guru_idguru` | `<select>` get() semua | DROPDOWN SUDAH CUKUP | — | — |
| Jadwal | Penugasan Mengajar | `semester_idsemester`, `kelas_idkelas`, `mapel_idmapel`, `guru_idguru` | `<select>` get() semua | DROPDOWN SUDAH CUKUP | — | — |

### 3.5 Absensi

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Absensi | Absensi Harian | `tahunAjaranId`, `semesterId`, `kelasId` | `<select>` | DROPDOWN SUDAH CUKUP | — | — |
| Absensi | Absensi Harian | `status` (per baris) | `<select>` sm | DROPDOWN SUDAH CUKUP | — | — |
| Absensi | Absensi Per Jam | tanggal (date input), guru = akun sendiri | — | TIDAK MEMBUTUHKAN LOV | — | — |
| Absensi | Histori Absensi (siswa) | `status*` filter | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |

### 3.6 Penilaian & Rapor

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Penilaian | Komponen Nilai | `filterSemester`, `mapelDipilih` (semester × mapel) | `<select>` | DROPDOWN SUDAH CUKUP | — | — |
| Penilaian | KKTP Mapel | `filterSemester`, `semester_idsemester`, `mapel_idmapel` | `<select>` | DROPDOWN SUDAH CUKUP | — | — |
| Penilaian | Input Nilai | `penugasanId` | `<select>` (hanya penugasan milik guru; ≤6) | DROPDOWN SUDAH CUKUP | — | — |
| Penilaian | Koreksi Nilai | `kelasId`, `mapelId` | `<select>` kelas (~12); mapel = hanya mapel rapor final (scoped) | DROPDOWN SUDAH CUKUP | — | — |
| Penilaian | Finalisasi Rapor | `kelasId` | `<select>` (kelas accessible user) | DROPDOWN SUDAH CUKUP | — | — |
| Penilaian | Rapor Siswa | `raporKunci` | `<select>` (semester × kelas milik siswa) | DROPDOWN SUDAH CUKUP | — | — |
| Penilaian | Rapor Wali Kelas | `kelasId` → `siswaId` | `<select>` siswa per kelas (≤40) | DROPDOWN SUDAH CUKUP | — | — |

### 3.7 Pengumuman & Notifikasi

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Pengumuman | Pengumuman (admin) | `jurusanIds[]` | `<select multiple size="5">` semua jurusan | DROPDOWN SUDAH CUKUP | — | — |
| Pengumuman | Pengumuman (admin) | `kelasIds[]` | `<select multiple size="5">` semua kelas (~12) | DROPDOWN SUDAH CUKUP | — | — |
| Pengumuman | Pengumuman (admin) | **`siswaIds[]`** | **`<select multiple size="5">` SEMUA siswa aktif tanpa limit/pencarian** | **BELUM ADA LOV** | **A — WAJIB LOV (multi-serach)**. Muat 100s–1000s option, lambat, risiko salah klik | **P1** |
| Pengumuman | Pengumuman | `kategori`, `prioritas`, `target`, `status` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Notifikasi | Notifikasi | `tab`, `filterJenis` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |

### 3.8 Portal Wali Murid

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Wali | Wali Relasi | **siswa yang diajukan** | **Search input + list hasil + tombol "Pilih" (`cariSiswaRelevan(..., 20)`)** — pola terbaik yang ada | **LOV SUDAH BAIK (manual)** | Pertahankan; jadikan acuan pola standar | — |
| Wali | Wali Akademik | `siswaId` (anak sendiri) | `<select>` (≤3 anak) | DROPDOWN SUDAH CUKUP | — | — |
| Wali | Wali Akun (admin) | `filterStatus` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Wali | Wali Antrian | `tab`, `filter*` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |

### 3.9 Keuangan

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Keuangan | Dashboard Keuangan | — | — (display) | TIDAK MEMBUTUHKAN LOV | — | — |
| Keuangan | Data Tagihan | `filterStatus`, `filterJenis`, `filterPeriode` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Data Tagihan | `scope` (semua/kelas/siswa) | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Data Tagihan | `scopeId` → kelas | `<select>` kelas (~12) | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Data Tagihan | **`scopeId` → siswa** | **`<select>` limit(500) semua aktif** | **BELUM ADA LOV** | **A — WAJIB LOV** (siswa >500 tidak bisa dipilih) | **P3** |
| Keuangan | Tarif | `jenis`, `filterStatus`, `filterJenis`, `status` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Tarif | `kelas_idkelas` | `<select>` (~12) | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Tarif | **`siswa_idsiswa`** | **`<select>` limit(500), aktif** | **BELUM ADA LOV** | **B — DIREKOMENDASIKAN** (limit silent → siswa >500 tidak muncul) | **P3** |
| Keuangan | Beasiswa | `filterStatus`, `jenis`, `status` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Beasiswa | **siswa penerima** | **`assignToSiswa()`/`revokeFromSiswa()` ada, UI tidak ada** | **BELUM ADA LOV** | **A — WAJIB LOV (assign)**: tidak ada cara assign siswa ke beasiswa dari aplikasi | **P2** |
| Keuangan | Pembayaran Manual | `tab`, `filterStatus` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Pembayaran Manual | `metode` | `<select>` enum (tunai/transfer/dll.) | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Pembayaran Manual | **`siswaId`** | **`<select>` SEMUA siswa aktif, tanpa search/limit** | **BELUM ADA LOV** | **A — WAJIB LOV** | **P1** |
| Keuangan | Pembayaran Manual | `tagihanId` | `<select>` tagihan siswa terpilih (sudah scoping; list kecil) | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Transaksi Pembayaran | `tab`, `filterStatus`, `filterPeriode` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Rekonsiliasi | `filterPeriode`, `filterKategori`, `filterStatus` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Refund & Koreksi | `tab`, `filterStatus`, `filterJenis` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Refund & Koreksi | `metodeRefund`, `metodeUsulan`, `jenisKoreksi` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Refund & Koreksi | `tagihanPenggantiId` | `<select>` tagihan siswa (sudah ter-scope, kecil) | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Pembebasan Denda | `tab`, `filterStatus` | `<select>` enum | DROPDOWN SUDAH CUKUP | — | — |
| Keuangan | Tagihan Pengingat | (via scheduler CLI) | — | TIDAK MEMBUTUHKAN LOV | — | — |
| Keuangan | Bayar Tagihan | `siswaId` (wali pilih anak) | `<select>` (≤3 anak) | DROPDOWN SUDAH CUKUP | — | — |

### 3.10 Pengaturan & Menu/Ikon

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Pengaturan | Appmenunew | `appmenucat_idappmenucat` | `<select>` (6 kategori) | DROPDOWN SUDAH CUKUP | — | — |
| Pengaturan | Appmenunew | **`icon`** | **LOV ikon (modal `icon-lov`, aktif, filter `stripos` + paginate 84)** | **LOV PERLU PERBAIKAN** | Scan `public_path('asset_offline/icon')` **setiap render**; pindahkan index ke DB/manifest, atau cache; pertahankan pola search | P4 |
| Pengaturan | Appmenucat | — | Input teks/urutan | TIDAK MEMBUTUHKAN LOV | — | — |
| Pengaturan | Docs / Docscat | `docscat_iddocscat` | `<select>` (≤4 kategori) | DROPDOWN SUDAH CUKUP | — | — |
| Pengaturan | Docs | — (title/content) | Input teks | TIDAK MEMBUTUHKAN LOV | — | — |
| Pengaturan | Rolecat / Roles / Permissions / ModelHasRoles / RoleHasPermissions / Role | — | Input teks / enum level; scaffold LOV mati (11 view scaffold + 5 method `*lov()` di controller tanpa route terdaftar) | **BELUM ADA LOV** (konsisten dead code) | Hapus scaffold atau seragamkan dengan standar baru (lihat §6) | P5 |

### 3.11 Dashboard & Laporan

| Modul | Halaman/Form | Field Relasional | Mekanisme Saat Ini | Status LOV | Rekomendasi | Prioritas |
|---|---|---|---|---|---|---|
| Dashboard | Dashboard admin | — | — (display) | TIDAK MEMBUTUHKAN LOV | — | — |
| Dashboard | Dashboard Keuangan | — | — (display) | TIDAK MEMBUTUHKAN LOV | — | — |
| Laporan | Activitylog | `selectedUser`, `selectedAction`, `selectedMenu`, `selectedBulan` | `<select>` filter | DROPDOWN SUDAH CUKUP | — | — |
| Laporan | Export rekap/histori | — | — (aksi file) | TIDAK MEMBUTUHKAN LOV | — | — |

---

## 4. Kualitas LOV yang Sudah Ada (detail)

| LOV | File | Status | Catatan |
|---|---|---|---|
| Icon picker (`appmenunew`) | `appmenunew-component.blade.php` → `icon-lov.blade.php` → `AppmenunewComponent::selectIcon()` | **LOV PERLU PERBAIKAN** | Berfungsi (search + pilih + dispatch `close-modal-lov`). Masalah: direktori ikon di-scan dari disk **pada setiap render** (ratusan file), filter `stripos()` dilakukan di PHP, paginasi manual 84/halaman, tidak ada cache. Rekomendasi: caching manifest ikon atau tabel `icons` |
| Siswa picker (Wali Relasi) | `wali-relasi-component.blade.php` + `WaliRelasiComponent::pilihSiswa/gantiSiswa` → `WalimuridService::cariSiswaRelevan()` | **LOV SUDAH BAIK** | Debounce 300ms, limit 20 hasil, hanya siswa aktif, tombol "Pilih" per baris, reset setelah submit. Pola terbaik di codebase — jadikan acuan |
| Scaffold `*-lov` (12 view) | `users-lov`, `docs-lov`, `docscat-lov`, `appmenucat-lov`, `appmenunew-lov`, `rolecat-lov`, `roles-lov`, `role-lov`, `permissions-lov`, `model_has_roles-lov`, `role-has-permissions-lov`, `role_has_permissions-lov`, `usermenu-lov` | **BELUM ADA LOV** (dead code) | Semua `@include` dikomentari. Isi users-lov menampilkan kolom `password`, `remember_token`, `email_verified_at` sebagai tabel — **berbahaya bila diaktifkan** tanpa ditulis ulang. Kandidat penghapusan, bukan perbaikan |

---

## 5. Temuan Keamanan & Validasi

1. **Scaffold LOV tidak memeriksa authorization.** Method `usersLOV($id)`, `appmenuLOV()`, `docsLOV()` dll. adalah method publik Livewire tanpa `abort_unless(superadmin)` — berbeda dengan konvensi AGENTS.md (semua method publik admin diawali `abort_unless(superadmin)`). Dampak nyata rendah (hanya set properti + alert), tetapi tidak sesuai standar.
2. **`usersOption()` memuat `User::get()`** — seluruh user + semua kolom termasuk `password` hash — jika dipanggil. Tidak ada pemanggil (dead), tetapi harus dihapus bila scaffold dibersihkan.
3. **Backend tetap ketat terhadap relasi** — `exists:` di validasi pengumuman (§2 T8) membuktikan tidak mungkin "memilih" siswa/jurusan di luar daftar lewat request Livewire palsu.
4. **Tidak ada tombol/aksi LOV yang ter-hardcode ke URL mencurigakan**; tidak ada endpoint LOV terpisah.
5. **`icon-lov` search hanya memfilter nama file** (`stripos`) — tidak ada injeksi.
6. **Tidak ada kebocoran data lintas peran** — siswa picker di wali-relasi sudah di-scope ke siswa aktif + `WalimuridService` (guard relasi); wali tidak bisa memilih siswa sembarangan untuk ditautkan tanpa verified (dikecek di service).
7. **Gambar ikon tidak divalidasi ekstensi** selain `.svg` dideteksi via `str_ends_with` — hanya untuk tampilan (`asset_offline/icon`), tidak untuk upload.

---

## 6. Rekomendasi Pola LOV Standar

Disarankan **kombinasi**, bukan satu pendekatan:

1. **Service pencarian bersama** (`app/Services/Lov/…` atau `PencarianService`): method generik `cari($model, $kolom, $q, $limit)` + mapper hasil → `[['id' => …, 'label' => …]]`. Satu tempat untuk aturan "hanya aktif", "scoping peran" (mis. siswa kelas X saja), limit (20–50), dan ordering.
2. **Komponen/Modal LOV reusable** (`x-lov-modal` atau Livewire component tunggal `LovSearchComponent`): props `target` (field Livewire property), `model`, `labelKeys`, `limit`; emit `lov-terpilih {id,label}`; dispatch `close-modal-lov`; hanya render bila ada data + pencarian. Jangan buat 12 view LOV per modul lagi.
3. **Per-komponen tetap**: pemanggilan modal LOV tetap per komponen (kebutuhan kontekstual seperti scoping siswa per kelas di wali-kelas).
4. **Kontrak standar**: input search debounce 300ms → `cari…()` di komponen → modal list → klik pilih → set `…Id` + `…Label` → `dispatch('close-modal-lov')`. Referensi pola: `wali-relasi-component` (sudah hampir seperti itu).
5. **Hapus scaffold LOV mati** (T1/T2) dan `*-lov` views — jangan dipertahankan "untuk berjaga-jaga".
6. **Kriteria aktivasi LOV**: relasi >100 baris aktif atau berkembang (siswa, guru-ish, tagihan, pembayaran). Selain itu dropdown tetap.

---

## 7. Prioritas Implementasi

| Pri | Item | Ukuran | Alasan |
|---|---|---|---|
| **P1** | Pembayaran Manual — LOV siswa | M | Setiap pembayaran manual; peran kasir salah pilih = jurnal salah |
| **P1** | Pengumuman — LOV multi siswa (ganti `<select multiple>`) | M | Target salah = pengumuman ke siswa salah; daftar tidak manageable |
| **P2** | Beasiswa — wire-up assign/revoke UI + LOV siswa | S–M | Fitur beasiswa tanpa UI assign = tidak bisa dipakai harian |
| **P3** | Tarif (individu) & Data Tagihan scope siswa — LOV siswa (hapus limit 500 silent) | S | Siswa ke-501+ hilang dari dropdown (silent failure) |
| **P4** | Kelassiswa — LOV/search tambah siswa ke kelas | S | Kelas besar (100+) susah pakai multi-select statis |
| **P4** | Icon picker — cache manifest ikon (hapus scan disk per render) | S | Setiap buka halaman Menu = ratusan `File::files()` |
| **P5** | Bersihkan scaffold LOV mati + `*-lov` views (hapus atau konsolidasi ke §6) | S | Dead code + risiko bila diaktifkan mentah |

---

## 8. Daftar Komponen & File Terkait

**Komponen dengan field relasional (46):** Users, Usermenu, Roles, Rolecat, Permissions, ModelHasRoles, RoleHasPermissions, Role, Appmenucat, Appmenunew, Siswa, Guru, Mapel, Kelas, Kelassiswa, Jurusan, Tahunajaran, Semester, Jamhari, Jadwal (legacy), JadwalKelas, Pengampu, PenugasanMengajar, AbsensiHarian, AbsensiPerjam, HistoriAbsensiSiswa, KomponenNilai, KktpMapel, InputNilai, KoreksiNilai, FinalisasiRapor, RaporSiswa, RaporWaliKelas, Pengumuman, Notifikasi, WaliAkun, WaliRelasi, WaliAntrianVerifikasi, WaliAkademik, WaliBeranda, Tarif, Beasiswa, DataTagihan, PembayaranManual, TransaksiPembayaran, Rekonsiliasi, RefundKoreksi, DendaPembebasan, BayarTagihan, Dashboard(2), Activitylog.

**File kunci:**
- `app/Livewire/*.php` — semua di atas & `BeasiswaComponent.php`, `PengumumanComponent.php`, `TarifComponent.php`, `PembayaranManualComponent.php`, `KelassiswaComponent.php`, `AppmenunewComponent.php` (icon LOV), `WaliRelasiComponent.php` (pola baik)
- `resources/views/livewire/*.blade.php` — semua form-select
- `resources/views/livewire/*-lov.blade.php` — 13 view LOV scaffold (12 mati + `icon-lov` aktif)
- `resources/views/livewire/partials/import-modal.blade.php` — import data (tidak relasional)
- `resources/views/setting.blade.php` — profil (tidak relasional)
- `App\Providers\AppServiceProvider.php` — view composer sidebar (usermenu — authorization, bukan LOV)

**Tidak ada file yang diubah** (audit saja).

---

## 9. Kesimpulan

**Apakah implementasi LOV SIAKAD sudah konsisten?** **Tidak.** Terdapat satu pola yang menyerupai LOV dan bekerja (`wali-relasi`), satu LOV teknis yang berfungsi tetapi lambat (`appmenunew` icon picker), 11–12 view LOV mati, puluhan method LOV mati tanpa pemanggil, dan 4 form relasional yang masih mengandalkan dropdown biasa padahal skala datanya besar.

**Fitur mana yang paling membutuhkan LOV?** Pembayaran Manual (kasus penggunaan harian, salah pilih = kesalahan jurnal) dan Pengumuman target siswa (blast radius massal). Keduanya mengalahkan kebutuhan lain.

**Fitur mana yang sudah baik?** Siswa picker di Wali Relasi (search + pilih + scope aktif + limit) dan scaffold lainnya — tetapi scaffold LOV per modul adalah anti-pola (dead code), bukan standar.

**Urutan implementasi yang paling masuk akal:** P1 (Pembayaran Manual, Pengumuman multi-siswa) → P2 (Beasiswa assign) → P3 (Tarif/Data Tagihan scope siswa; hilangkan limit 500) → P4 (Kelassiswa, icon cache) → P5 (bersihkan scaffold). Semua dibangun di atas pola §6 (service pencarian bersama + modal LOV reusable), bukan disalin per-halaman.

**Peringatan untuk owner:** jangan mengaktifkan view `*-lov` yang ada tanpa penulisan ulang — `users-lov.blade.php` mengekspos kolom `password` dan `remember_token` pada saat dirender. Ikuti §6, hindari membangkitkan scaffold lama.
