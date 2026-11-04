# LAPORAN IMPLEMENTASI TAHAP 2B.4.6B — DATABASE & MODEL PENGUMUMAN SISWA

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal | 4 November 2026 |
| Dasar | `LAPORAN_DESAIN_PENGUMUMAN_2B4_6A.md` (revisi final) |
| Cakupan | Migration 3 tabel + 3 model + relasi/scopes |
| Tidak dikerjakan | Service, Livewire component, Blade, route, menu, notifikasi (2B.4.6C ke atas) |

---

## 1. File yang Dibuat

| File | Aksi | Ringkasan |
|---|---|---|
| `database/migrations/2026_11_08_000001_create_pengumuman_tables.php` | **Baru** | 3 tabel: `pengumuman`, `pengumuman_target`, `pengumuman_baca`; FK RESTRICT; UNIQUE terpisah |
| `app/Models/Pengumuman.php` | **Baru** | `$fillable`, `$casts`, konstanta `STATUS`/`KATEGORI`/`PRIORITAS`/`TARGET`, relasi `targets`/`baca`/`pembuat`/`pengubah`, scopes `search`/`terbit`/`berlaku` |
| `app/Models/PengumumanTarget.php` | **Baru** | `$fillable`, konstanta `JENIS`, relasi `pengumuman`/`jurusan`/`kelas`/`siswa`, scope `search` |
| `app/Models/PengumumanBaca.php` | **Baru** | `$fillable`, `$casts`, relasi `pengumuman`/`siswa`, scopes `search`/`forSiswa`/`forPengumuman`/`belumDibaca`/`sudahDibaca` |
| `storage/app/tmp-uji/` | Dibersihkan | Skrip milik agen ini dihapus; tersisa file yang sudah ada |

`git status --short` (file baru):

```
?? app/Models/Pengumuman.php
?? app/Models/PengumumanBaca.php
?? app/Models/PengumumanTarget.php
?? database/migrations/2026_11_08_000001_create_pengumuman_tables.php
```

Pint: `php vendor/bin/pint` pada file PHP yang berubah → **FIXED** (indentasi). `php -l` → **No syntax errors detected**.

---

## 2. Hasil Pengujian

### 2.1 `php artisan migrate --pretend` (sebelum migrate)

```
INFO  Running migrations.

2026_11_08_000001_create_pengumuman_tables
⇂ create table `pengumuman` (`idpengumuman` int unsigned not null auto_increment primary key, ...)
⇂ alter table `pengumuman` add constraint `pengumuman_dibuat_oleh_foreign` ... on delete restrict
⇂ alter table `pengumuman` add constraint `pengumuman_diperbarui_oleh_foreign` ... on delete restrict
⇂ create table `pengumuman_target` (`idpengumuman_target` int unsigned not null auto_increment primary key, ...)
⇂ alter table `pengumuman_target` add unique `pengumuman_target_jurusan_unique`(`pengumuman_idpengumuman`, `jurusan_idjurusan`)
⇂ alter table `pengumuman_target` add unique `pengumuman_target_kelas_unique`(`pengumuman_idpengumuman`, `kelas_idkelas`)
⇂ alter table `pengumuman_target` add unique `pengumuman_target_siswa_unique`(`pengumuman_idpengumuman`, `siswa_idsiswa`)
⇂ alter table `pengumuman_target` add constraint `pengumuman_target_pengumuman_idpengumuman_foreign` ...
⇂ alter table `pengumuman_target` add constraint `pengumuman_target_jurusan_idjurusan_foreign` ...
⇂ alter table `pengumuman_target` add constraint `pengumuman_target_kelas_idkelas_foreign` ...
⇂ alter table `pengumuman_target` add constraint `pengumuman_target_siswa_idsiswa_foreign` ...
⇂ create table `pengumuman_baca` (`idpengumuman_baca` bigint unsigned not null auto_increment primary key, ...)
⇂ alter table `pengumuman_baca` add unique `pengumuman_baca_pengumuman_siswa_unique`(`pengumuman_idpengumuman`, `siswa_idsiswa`)
⇂ alter table `pengumuman_baca` add constraint `pengumuman_baca_pengumuman_idpengumuman_foreign` ...
⇂ alter table `pengumuman_baca` add constraint `pengumuman_baca_siswa_idsiswa_foreign` ...
```

Migration dijalankan: `2026_11_08_000001_create_pengumuman_tables ... 982.15ms DONE`.

`php artisan migrate:status`:

```
2026_11_08_000001_create_pengumuman_tables ................................................................. [8] Ran
```

`php artisan migrate --pretend` (sesudahnya): `INFO Nothing to migrate.`

### 2.2 Uji struktur, FK, UNIQUE, model (transaksi + rollback)

Semua uji dalam `DB::beginTransaction()` + `rollBack()` di `finally`. Skrip telah dihapus setelah selesai.

```
=== T1: Migration --pretend & struktur ===
PASS | Tabel pengumuman ada
PASS | Tabel pengumuman_target ada
PASS | Tabel pengumuman_baca ada
PASS | Kolom pengumuman lengkap (14)
PASS | Kolom wajib ada (judul,isi,kategori,prioritas,status,waktu_terbit,berlaku_dari,berlaku_sampai,target,dibuat_oleh)
PASS | Kolom target ada (jurusan,kelas,siswa)
PASS | Kolom baca ada (dibaca_pada)
PASS | Tidak ada kolom jumlah_baca

=== T2: Tipe FK === PK tujuan ===
PASS | FK pengumuman.dibuat_oleh = bigint
PASS | FK pengumuman_target.pengumuman_idpengumuman = int
PASS | FK pengumuman_target.jurusan_idjurusan = int
PASS | FK pengumuman_target.kelas_idkelas = int
PASS | FK pengumuman_target.siswa_idsiswa = int
PASS | FK pengumuman_baca.pengumuman_idpengumuman = int
PASS | FK pengumuman_baca.siswa_idsiswa = int
PASS | PK users.id = bigint
PASS | PK siswa.idsiswa = int
PASS | PK kelas.idkelas = int
PASS | PK jurusan.idjurusan = int
PASS | PK pengumuman.idpengumuman = int

=== T3: UNIQUE constraint ===
PASS | UNIQUE jurusan ada
PASS | UNIQUE kelas ada
PASS | UNIQUE siswa ada
PASS | UNIQUE (pengumuman,siswa) baca ada

=== T4: FK RESTRICT ===
PASS | Semua FK pengumuman RESTRICT

=== T5: Model & relasi ===
PASS | Pengumuman::targets() hasMany
PASS | Pengumuman::baca() hasMany
PASS | PengumumanTarget::pengumuman() belongsTo
PASS | PengumumanBaca::siswa() belongsTo
PASS | PengumumanBaca::scopeForSiswa ada
PASS | Pengumuman::scopeTerbit ada
PASS | Pengumuman::scopeBerlaku ada
INFO | Pengumuman STATUS const = ["draf","terbit","arsip"]
PASS | STATUS = draf/terbit/arsip
INFO | Pengumuman KATEGORI const = ["akademik","acara","penting","darurat"]
PASS | KATEGORI 4 nilai
INFO | Pengumuman PRIORITAS const = ["rendah","sedang","tinggi","mendesak"]
PASS | PRIORITAS 4 nilai
INFO | Pengumuman TARGET const = ["semua","jurusan","kelas","siswa"]
PASS | TARGET 4 nilai
INFO | Pengumuman fillable = ["judul","isi","kategori","prioritas","status","waktu_terbit","berlaku_dari","berlaku_sampai","target","dibuat_oleh","diperbarui_oleh"]
PASS | Fillable berisi judul/isi/kategori/prioritas/status/target/dibuat_oleh

=== T6: Insert & query model dalam transaksi ===
INFO | pengumuman dibuat id = 3
PASS | Insert pengumuman draf berhasil
INFO | pengumuman terbit id = 4
PASS | Scope terbit menemukan pengumuman terbit
PASS | Scope draf tidak memuat pengumuman terbit

=== T7: Target & baca ===
INFO | target dibuat id = 2
PASS | Insert target siswa berhasil
INFO | baca dibuat id = 2
PASS | Insert baca berhasil (dibaca_pada null)
PASS | Scope BelumDibaca menemukan baris

=== T8: Tidak ada perubahan tabel lain ===
PASS | Tabel lain tidak berubah (siswa 2, kelas 3, jurusan 3)
-- Transaksi di-rollback --
Setelah rollback: {"pengumuman":0,"target":0,"baca":0}
PASS | Rollback bersih (semua tabel pengumuman kosong)

SEMUA UJI PASS
```

Catatan: `Schema::getColumnType` melaporkan `bigint`/`int` (tanpa `unsigned`); `SHOW CREATE TABLE pengumuman` memastikan `int unsigned`/`bigint unsigned` sesuai PK tujuan.

---

## 3. Risiko yang Harus Dipahami

| # | Risiko | Keterangan |
|---|---|---|
| 1 | Tidak ada migration rollback yang aman | `down()` tersedia, tetapi tidak dijalankan; rollback migration tidak diuji |
| 2 | Bengali FK ke `users.id` dan `siswa.idsiswa` | `RESTRICT` mencegah hapus user/siswa yang masih dirujuk |
| 3 | Tidak ada service/component yet | 2B.4.6C ke atas belum dimulai; data pengumuman saat ini kosong (0 baris) |
| 4 | Tidak ada kolom indeks tambahan | Query daftar siswa selanjutnya perlu indeks pada (`pengumuman_idpengumuman`, `siswa_idsiswa`) — sudah ter-cover UNIQUE |

**TIDAK BISA DIUKUR**: rollback migration (`php artisan migrate:rollback`) — tidak dijalankan karena dapat mengubah struktur database.

**Kesimpulan**: fondasi database & model pengumuman selesai — 3 tabel additif, FK `RESTRICT` dengan tipe sama persis, UNIQUE terpisah, `$fillable`/`$casts`/relasi/scopes terverifikasi. Tahap 2B.4.6C belum dimulai.