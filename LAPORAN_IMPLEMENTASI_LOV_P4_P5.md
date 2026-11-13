# LAPORAN IMPLEMENTASI LOV P4 + P5 — KELASSISWA, ICON PICKER, & PEMBERSIHAN SCAFFOLD

**Tanggal:** 13 November 2026 · **Sumber:** kode aktual + uji rollback + UAT browser (Edge headless, CAPTCHA_DISABLE=true, data demo sintetis UJILOV, dibersihkan setelahnya) · **Cakupan:** hanya P4 & P5 (P1–P3 tidak di-ulang/diubah).

---

## 1. File yang diperiksa & diubah

| File | Peran | Keterangan |
|---|---|---|
| `app/Livewire/KelassiswaComponent.php` | diubah (P4A) | Ganti pemilihan siswa (`<select multiple>` + query `limit(50)`, pencarian hanya nama/NISN) → **LOV multi** (trait `LovSiswa`, ID `$siswaIds`, nama/NIS/NISN, aktif saja, maks 20 hasil). **`boot()` otorisasi superadmin per request** (route tetap `check.level:superadmin` — kebijakan tidak diubah; guard ditambah untuk pertahanan). Validasi ulang backend: siswa wajib AKTIF (`LovService::selected`); **siswa yang sudah punya penempatan non-`pindah` pada TA yang dipilih ditolak di backend** (mereplikasi filter UI lama, kini tidak bisa di-bypass via request Livewire); kelas harus sesuai TA; `keluarkan()` dipertahankan (delete relasi); ganti TA/kelas membersihkan state LOV; members tetap paginasi |
| `resources/views/livewire/kelassiswa-component.blade.php` | diubah (P4A) | Input pencarian + select multiple → partial `lov-siswa` (mode multi); error `siswaIds`/`lovSearch` inline; sisa tampilan (TA/kelas picker, members, import) dipertahankan |
| `app/Livewire/AppmenunewComponent.php` | diubah (P4B + P5) | **Cache daftar nama icon**: `Cache::remember('appmenunew.icons.'.mtimeDir, 30 menit)` menyimpan `array<string>` nama file (bukan `File` object, bukan state user). Key memasukkan `filemtime` direktori → file ditambah/dihapus otomatis invalidasi; TTL 30 menit jaga-jaga; `File::files()` hanya dipanggil di dalam closure. Komentar scaffold LOV (`searchlov`, `pageLOV`, `appmenuLOV`) dihapus |
| 11 komponen admin (Appmenucat, Appmenunew, Docs, Docscat, Permissions, Rolecat, Role, RoleHasPermissions, Roles, Usermenu, Users) | diubah (P5) | Hapus properti `public $searchlov`, method `updatingSearchlov()`, `resetPage('pageLOV')`, method `xxxLOV($id)` (9 method), dan komentar scaffold LOV (`ModelLOV::search`, `aktifkan dan sesuaikan…`) |
| 10 Blade komponen admin | diubah (P5) | Hapus komentar `{{-- @include('livewire.*-lov') --}}`, blok komentar JS "copy dan aktifkan jika ingin menggunakan LOV" (ikon-picker listener dipertahankan), dan komentar `@include usermenus-lov` (view tidak ada) |
| 5 HTTP controllers (`Model_has_roles`, `Permissions`, `Roles`, `Role_has_permissions`, `Users`) | diubah (P5) | Hapus method `xxxLOV(Request)` (placeholder scaffold; view `*lov` tidak ada, route tidak terdaftar). Import `Datatables` dipertahankan (dipakai method `index()`) |
| 12 view `resources/views/livewire/*-lov.blade.php` | **dihapus (P5)** | `appmenucat-lov`, `appmenunew-lov`, `docs-lov`, `docscat-lov`, `model_has_roles-lov`, `permissions-lov`, `role-lov`, `rolecat-lov`, `roles-lov`, `role_has_permissions-lov`, `usermenu-lov`, `users-lov`. Alasan: tidak ada referensi aktif; hanya komentar `{{-- @include --}}` (dan `appmenunew-lov`/`role-lov`/`usermenu-lov` tanpa referensi sama sekali) |
| `resources/views/livewire/icon-lov.blade.php` | **dipertahankan** | Di-include aktif di `appmenunew-component.blade.php`; adalah icon picker yang sedang dioptimasi |
| `app/Traits/PaginationLov.php`, `app/Traits/PaginationTrait.php` | **dihapus (P5)** | Tidak dipakai komponen mana pun (0 `use` statement); scaffold pagination LOV mati |
| `app/Traits/FileUpload.php` | tidak diubah | Tidak mengandung scaffold LOV & tidak direferensikan di mana pun — **di luar cakupan P5** (dilaporkan, tidak dihapus) |
| `routes/web.php`, route, middleware, schema, migration | tidak diubah | — |

**Catatan jujur:** menjalankan Pint pada seluruh direktori `app/Livewire` & `app/Http/Controllers` ikut memformat beberapa file **di luar** P4/P5 (DocsCreateComponent, JurusanComponent, MapelComponent, PengampuComponent, WaliAkademikComponent, WaliAntrianVerifikasiComponent, FileUpload) — hanya pemformatan (class_attributes_separation, ordered_imports, dll.), tanpa perubahan logika; sudah lolos `php -l` & `php artisan test`. Ke depan hanya file spesifik yang dijalankan Pint.

---

## 2. P4A — Implementasi LOV Kelassiswa

Sebelum: `<select multiple size=8>` dari query `Siswa::where('status','aktif')->when(TA, filter belum punya kelas…)->orderBy('nama')->limit(50)->get()` — hanya memuat 50 siswa pertama, pencarian hanya nama/NISN, siswa di luar 50 pertama tidak pernah tampil; otorisasi hanya di route (`check.level:superadmin`) tanpa guard komponen.

Sesudah:
- **LOV multi** (partial `lov-siswa`): ketik ≥2 karakter → maks 20 hasil, **siswa AKTIF saja**, menampilkan nama + NIS + NISN; tombol Pilih per hasil; chips terpilih; dedup otomatis; **cari NIS** (sebelumnya tidak bisa).
- **Identitas terpilih jelas**: chips berisi nama + NIS (partial P1).
- **Backend validation** (`tambahSiswa()`), dengan urutan:
  1. Kelas wajib ada & `kelas.tahunajaran_idtahunajaran` harus sama dengan TA terpilih (mencegah penempatan lintas TA).
  2. Untuk setiap `siswaIds`: registry `LovService::selected()` → harus **AKTIF**; ID palsu/lulus/pindah/keluar ditolak dengan pesan per-siswa.
  3. **Filter UI lama ditegakkan di backend**: siswa yang sudah punya `kelassiswa` `status != 'pindah'` pada TA yang dipilih ditolak (sebelumnya hanya filter tampilan — bisa di-bypass via POST).
  4. `PenempatanService::tambah()` tetap menjadi penjaga terakhir: duplikat kelas sama, kelas Induk (wajib wali aktif + satu induk per siswa per TA), siswa tidak aktif — aturan bisnis tidak diubah.
- **State lama tidak tertinggal**: ganti TA/kelas → `kelas_id`/pagination/LOV di-reset; setelah `tambahSiswa()` sukses/galat → `lovReset()` (chips, `siswaIds`, `lovSearch`, `lovHasil` bersih).
- **Otorisasi**: route tetap superadmin; komponen kini juga punya `boot()` + `abort_unless(level === 'superadmin', 403)` di setiap method publik → request Livewire langsung oleh non-superadmin ditolak 403 (diuji).
- Members tetap paginasi 10; kolom NISN menampilkan anggota kelas; `Keluarkan` menghapus baris (perilaku existing).

---

## 3. P4B — Optimasi icon picker (appmenunew)

Sebelum: setiap `render()` memanggil `File::files(public_path('asset_offline/icon'))` (1.395 file, `stat` tiap file) — boros untuk setiap Livewire round-trip.

Sesudah (`render()`):
```php
$iconDir      = public_path('asset_offline/icon');
$iconDirMtime = is_dir($iconDir) ? (string) @filemtime($iconDir) : 'missing';
$filenames    = Cache::remember('appmenunew.icons.'.$iconDirMtime, now()->addMinutes(30), function () use ($iconDir) {
    return collect(File::files($iconDir))->map(fn ($f) => $f->getFilename())->sort()->values()->all();
});
```
- **Isi cache**: hanya `array<string>` nama file (sudah `sort()` deterministik) — bukan objek `Symfony File`, bukan state/preferensi user.
- **Invalidasi wajar**: key memuat `mtime` direktori → menambah/menghapus/mengubah-nama-file ikon otomatis menghasilkan key baru (ikon baru pasti muncul, ikon hilang hilang); key lama kedaluwarsa otomatis (TTL 30 menit). `Cache::clear` juga bisa menghapusnya. Tidak ada cache permanen.
- **Aman terhadap request Livewire**: cache global (bukan property komponen), tidak menyimpan data pengguna.
- **Format tidak berubah**: DB tetap menyimpan nama file (`selectIcon(basename)`), view `icon-lov` tidak diubah (`basename($image)` bekerja sama untuk string nama), 84 ikon/halaman dipertahankan, pencarian `stripos` tetap ada.

---

## 4. P5 — Scaffold LOV yang dihapus (dengan bukti referensi)

| Item | Bukti referensi sebelum dihapus | Catatan |
|---|---|---|
| 12 view `*-lov.blade.php` | grep `@include` seluruh blade & komponen → hanya komentar/0 referensi (lihat di bawah) | `users-lov` **terbukti menampilkan kolom `password` dan `remember_token`** — dikonfirmasi sebelum dihapus; tidak pernah dirender karena include-nya terkomentar |
| `users-lov.blade.php` | referensi = komentar `{{-- @include --}}` saja (users-component.blade.php:408) | Risiko kredensial terpapar bila suatu saat di-`@include` — dieliminasi dengan penghapusan |
| Method `usersLOV`, `rolesLOV`, `permissionsLOV`, `role_has_permissionsLOV`, `appmenuLOV`, `appmenucatLOV`, `docsLOV`, `docscatLOV`, `rolecatLOV` | 0 pemanggil di blade (komentar saja) & 0 pemanggil PHP di luar dirinya | `dispatch('close-modal-lov')` di method lama menunjuk modal yang tidak ada |
| Properti `public $searchlov` (11 komponen) | hanya dipakai view yang dihapus / komentar | — |
| `updatingSearchlov()` (10 komponen) | hook yang tidak pernah dipicu (input searchlov hanya di view terhapus) | — |
| `resetPage('pageLOV')` (seluruh app) | 21 baris; `pageLOV` tidak pernah dipakai paginate | — |
| Trait `PaginationLov`, `PaginationTrait` | 0 `use` statement di seluruh `app/` (grep `Traits\Pagination`) | — |
| Method `model_has_roleslov`, `permissionslov`, `roleslov`, `role_has_permissionslov`, `userslov` (HTTP controllers) | Route tidak terdaftar; `routes/aplikasiroute/*.php` tidak pernah di-`require` (grep di web.php/bootstrap/providers = 0) | `Datatables` import dipertahankan (dipakai `index()`) |
| Komentar scaffold (`// aktifkan dan sesuaikan…`, `// $namalov = ModelLOV::search…`, blok JS "copy dan aktifkan…", `{{-- @include …-lov --}}`) | tidak ada pemanggilan | «icon LOV» listener & komentar **dipertahankan** (dipakai) |

**Bukti tidak ada referensi rusak (sesudah penghapusan, grep seluruh `app/`, `resources/views`, `routes/`):** `searchlov|updatingSearchlov|pageLOV|ModelLOV|PaginationLov|PaginationTrait|appmenuLOV|appmenucatLOV|docsLOV|docscatLOV|permissionsLOV|rolecatLOV|role_has_permissionsLOV|rolesLOV|usersLOV|model_has_roleslov|permissionslov|roleslov` → **0 hasil**; `@include` ke view terhapus → **0 hasil**; `php -l` (17 file) ✓; `view:cache`/`view:clear` ✓; `php artisan test` **2 passed**; `route:list` semua route utama terdaftar ✓; Pint `--test` untuk 13 file diubah P4/P5 **PASS**.

---

## 5. Scaffold yang DIPERTAHANKAN + alasannya

| Item | Alasan |
|---|---|
| `icon-lov.blade.php` | Di-include aktif (`appmenunew-component.blade.php`); isinya icon picker P4B; **bukan scaffold mati** |
| Route `appmenunew`, `appmenucat`, `docs`, `docscat`, `users`, `usermenu`, `rolecat` | Terdaftar & ter-route; menu dipakai; tabel ada (`appmenunew`, `appmenucat`, `docs`, `users`, `usermenu`, `rolecat` verified di DB) |
| Komponen `PermissionsComponent`, `RoleHasPermissionsComponent`, `RolesComponent` | **Tidak dihapus** — pembersihan dibatasi scaffold LOV. Catatan: tabel `permissions`/`roles`/`role_has_permissions` **memang tidak ada di DB** dan ketiganya tidak ter-route — komponen tersebut scaffold lama yang mati di tingkat aplikasi, tapi menghapus komponen utuh (bukan sekadar scaffold LOV) di luar cakupan P5 & berisiko → dilaporkan sebagai observasi untuk keputusan pemilik |
| HTTP controllers `Model_has_rolesController` dkk. (selain method `*lov`) | Method `index()` dsb. dipertahankan (route `aplikasiroute` tidak di-load, tapi penghapusan controller utuh di luar cakupan P5) |
| `app/Traits/FileUpload.php` | Bukan scaffold LOV; tidak direferensikan; ditinggalkan (di luar cakupan) |
| Trait `HandlesImport`, `LovSiswa` | Dipakai aktif (import log, LOV) |

---

## 6. Hasil pengujian

Skrip uji rollback `storage/app/tmp-uji/uji-lov-p4p5.php` (**dihapus** setelahnya) — seluruh mutasi dalam `DB::beginTransaction()` + `rollBack()`:

| Kelompok | Hasil |
|---|---|
| P4A Kelassiswa: mount TA aktif; LOV cari nama/NIS; siswa `lulus` tidak muncul; pilih ganda → 1 chip; tambah relasi → tersimpan; state LOV bersih; duplikat kelas sama ditolak; **penempatan di TA sama ditolak backend**; siswa lulus ditolak; ID palsu ditolak; `keluarkan` terhapus; members paginate; **siswa login → 403** di boot/mount/tambahSiswa/keluarkan | 17 PASS |
| P4B Icon cache: `Cache::remember` dipakai; `File::files` hanya 1× (di dalam closure); closure mengembalikan array; `render()` mengisi cache key berbasis mtime dir; invalidasi key saat mtime beda; `icon-lov` tetap di-include | 6 PASS |
| P5: 12 view terhapus ✓; 2 trait terhapus ✓; `icon-lov` dipertahankan ✓ | 16 PASS |
| Regresi render (superadmin): Pembayaran Manual, Pengumuman, Beasiswa, Tarif, Data Tagihan, Kelassiswa, Appmenunew, Appmenucat, Docs, Docscat, Rolecat, Role, Usermenu, Users | 14 PASS |
| `php artisan test` | 2 passed |
| `view:cache`/`view:clear`, `php -l`, Pint `--test` (13 file) | bersih |
| UAT browser: `/kelassiswa` (superadmin) — LOV multi tampil hasil + chips, tambah → muncul di tabel, `Keluarkan` → hilang ✓ · `/appmenunew` — modal icon picker terbuka, daftar 1.395 ikon tampil, cari "angle" → 14 ikon cocok, pilih ikon → terpilih & toast, tanpa menyimpan menu ✓ | ✓ |

**Total uji otomatis: 52 PASS / 0 FAIL.** (Permissions/RoleHasPermissions/Roles tidak di-render: tabel scaffold tidak ada di DB & tidak ter-route — **diverifikasi pre-existing**, bukan akibat P5; file hanya dibersihkan via lint.)

Uji mutasi DB (siswa/kelas UJILOV) seluruhnya via transaksi rollback atau record uji yang dihapus setelahnya; DB diperiksa — 0 sisa data UJILOV.

---

## 7. Regresi P1–P5

| Fitur | Status |
|---|---|
| P1 Pembayaran Manual / Pengumuman | render ✓ (via skrip uji + artisan test) |
| P2 Beasiswa assign/revoke | render ✓ (skrip uji); UAT P2 sebelumnya ✓ (tidak diubah) |
| P3 Tarif individu / Data Tagihan | render ✓ (skrip uji) (tidak diubah) |
| P4 Kelassiswa | LOV ✓, backend validation ✓, otorisasi 403 ✓, members/pagination ✓ |
| P4 Icon picker | cache ✓, invalidasi ✓, UAT ✓ |
| P5 Pembersihan | grep bersih ✓, compile blade ✓, route:list ✓, Pint ✓ |

---

## 8. Risiko / keterbatasan

1. **Katalog icon mtime**: perubahan isi file ikon yang *isinya* berubah tanpa mengubah mtime direktori (mis. overwrite file dengan nama sama via alat yang mempertahankan mtime — jarang) tidak memicu invalidasi langsung; TTL 30 menit tetap menjamin kesegaran. `Cache::clear` memaksa refresh.
2. **Komponen scaffold mati non-LOV** (`PermissionsComponent`, `RoleHasPermissionsComponent`, `RolesComponent`, `app/Traits/FileUpload.php`): tidak dihapus karena di luar cakupan P5; tabel `permissions`/`roles` tidak ada — jika suatu saat di-route, akan selalu error. Butuh keputusan pemilik untuk penghapusan penuh/migrasi.
3. **Controller scaffold** (`Model_has_rolesController` dkk.): method `index()`/route `aplikasiroute` tidak ter-load; dipertahankan; kandidat penghapusan pada keputusan pemilik.
4. **Penerima histori** & filter "1 penempatan non-pindah per TA" kini ditegakkan di backend — jika kelak kelas `mapel_pilihan` boleh multi-kelas per siswa, backend check ini harus dikaji ulang bersama pemilik kebijakan (fungsionalitas tidak berubah secara diam-diam hari ini).
5. **Santunan Pint di luar scope** (7 file non-P4/P5 ikut terformat) — hanya format, tidak ada logika berubah; tercatat (lihat §1).
6. "**Layout cache** icons" tidak menghapus entri lama di cache — kedaluwarsa sendiri (TTL 30 menit); `storage` tidak dipenuhi.
7. Demo data: uji Kelassiswa memakai status `lulus` sebagai proksi "siswa tidak aktif" (enum siswa = aktif/lulus/pindah/keluar).

## 9. Konfirmasi

- Tidak ada perubahan `.env` · tidak ada Git write · tidak ada schema/migration/route/middleware/dependency diubah · tidak ada data operasional dihapus/diubah permanen · otorisasi route kelassiswa (`check.level:superadmin`) tetap; kebijakan keuangan/akademik tidak diubah.
- P4/P5 selesai, bukan sekadar audit: implementasi + uji + dokumentasi dalam satu tahap.
