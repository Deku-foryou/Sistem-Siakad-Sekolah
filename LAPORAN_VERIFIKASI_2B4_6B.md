# LAPORAN VERIFIKASI TAHAP 2B.4.6B — DATABASE & MODEL PENGUMUMAN

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal verifikasi | 4 November 2026 |
| Sifat | **Read-only.** Tidak mengubah kode, migration, database, atau data; tidak membaca `.env`; Git read-only |
| Metode | `Schema::getColumnListing`, `SHOW CREATE TABLE`, `SHOW INDEX`, `INFORMATION_SCHEMA`, `migrate:status` |
| Fokus | Struktur 3 tabel, `created_at`/`updated_at`, foreign key, timestamp migration |

---

## 1. Struktur Aktual Ketiga Tabel

### 1.1 `pengumuman` — 14 kolom — **PASS**

| Kolom | Tipe (SHOW CREATE TABLE) | Null | Default | Keterangan |
|---|---|---|---|---|
| `idpengumuman` | int unsigned AUTO_INCREMENT | NOT NULL | — | PK |
| `judul` | varchar(255) | NOT NULL | — | |
| `isi` | text | NOT NULL | — | |
| `kategori` | enum('akademik','acara','penting','darurat') | NOT NULL | 'akademik' | |
| `prioritas` | enum('rendah','sedang','tinggi','mendesak') | NOT NULL | 'sedang' | |
| `status` | enum('draf','terbit','arsip') | NOT NULL | 'draf' | |
| `waktu_terbit` | timestamp | NULL | NULL | |
| `berlaku_dari` | date | NULL | NULL | |
| `berlaku_sampai` | date | NULL | NULL | |
| `target` | enum('semua','jurusan','kelas','siswa') | NOT NULL | 'semua' | |
| `dibuat_oleh` | bigint unsigned | NOT NULL | — | FK `users.id` |
| `diperbarui_oleh` | bigint unsigned | NULL | NULL | FK `users.id` |
| `created_at` | timestamp | NULL | NULL | ✔ |
| `updated_at` | timestamp | NULL | NULL | ✔ |

Akhirnya: `AUTO_INCREMENT=5` (berdasarkan 5 penulisan uji pada tahap 2B.4.6B yang sudah di-rollback).

### 1.2 `pengumuman_target` — 7 kolom — **PASS**

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `idpengumuman_target` | int unsigned AI | NOT NULL | PK |
| `pengumuman_idpengumuman` | int unsigned | NOT NULL | FK `pengumuman.idpengumuman` |
| `jurusan_idjurusan` | int unsigned | NULL | FK `jurusan.idjurusan` |
| `kelas_idkelas` | int unsigned | NULL | FK `kelas.idkelas` |
| `siswa_idsiswa` | int unsigned | NULL | FK `siswa.idsiswa` |
| `created_at` | timestamp | NULL | ✔ (timestamp, bukan date) |
| `updated_at` | timestamp | NULL | ✔ |

`AUTO_INCREMENT=3`.

### 1.3 `pengumuman_baca` — 6 kolom — **PASS**

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `idpengumuman_baca` | bigint unsigned AI | NOT NULL | PK (pola `idabsensi_harian`) |
| `pengumuman_idpengumuman` | int unsigned | NOT NULL | FK `pengumuman.idpengumuman` |
| `siswa_idsiswa` | int unsigned | NOT NULL | FK `siswa.idsiswa` |
| `dibaca_pada` | timestamp | NULL | ✔ |
| `created_at` | timestamp | NULL | ✔ |
| `updated_at` | timestamp | NULL | ✔ |

`AUTO_INCREMENT=3`.

### 1.4 Perbandingan dengan laporan `LAPORAN_TAHAP_2B4_6B.md`

| Tabel | Didesain | Aktual | Status |
|---|---|---|---|
| `pengumuman` | 14 kolom (termasuk `created_at`/`updated_at`) | 14 kolom | **PASS** |
| `pengumuman_target` | 7 kolom (termasuk `created_at`/`updated_at`) | 7 kolom | **PASS** |
| `pengumuman_baca` | 6 kolom (termasuk `created_at`/`updated_at`) | 6 kolom | **PASS** |

---

## 2. Foreign Key pada Ketiga Tabel

### 2.1 `pengumuman` (2 FK)

Bukti `SHOW CREATE TABLE`:

```sql
CONSTRAINT `pengumuman_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
CONSTRAINT `pengumuman_diperbarui_oleh_foreign` FOREIGN KEY (`diperbarui_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT
```

### 2.2 `pengumuman_target` (4 FK)

Bukti `SHOW CREATE TABLE`:

```sql
CONSTRAINT `pengumuman_target_jurusan_idjurusan_foreign` FOREIGN KEY (`jurusan_idjurusan`) REFERENCES `jurusan` (`idjurusan`) ON DELETE RESTRICT,
CONSTRAINT `pengumuman_target_kelas_idkelas_foreign` FOREIGN KEY (`kelas_idkelas`) REFERENCES `kelas` (`idkelas`) ON DELETE RESTRICT,
CONSTRAINT `pengumuman_target_pengumuman_idpengumuman_foreign` FOREIGN KEY (`pengumuman_idpengumuman`) REFERENCES `pengumuman` (`idpengumuman`) ON DELETE RESTRICT,
CONSTRAINT `pengumuman_target_siswa_idsiswa_foreign` FOREIGN KEY (`siswa_idsiswa`) REFERENCES `siswa` (`idsiswa`) ON DELETE RESTRICT
```

### 2.3 `pengumuman_baca` (2 FK)

Bukti `SHOW CREATE TABLE`:

```sql
CONSTRAINT `pengumuman_baca_pengumuman_idpengumuman_foreign` FOREIGN KEY (`pengumuman_idpengumuman`) REFERENCES `pengumuman` (`idpengumuman`) ON DELETE RESTRICT,
CONSTRAINT `pengumuman_baca_siswa_idsiswa_foreign` FOREIGN KEY (`siswa_idsiswa`) REFERENCES `siswa` (`idsiswa`) ON DELETE RESTRICT
```

### 2.4 Ringkasan verifikasi FK

| Constraint | Asal | Tujuan | Tipe FK | Tipe PK tujuan | ON DELETE |
|---|---|---|---|---|---|
| `pengumuman_dibuat_oleh_foreign` | `pengumuman.dibuat_oleh` | `users.id` | bigint unsigned | bigint unsigned | RESTRICT |
| `pengumuman_diperbarui_oleh_foreign` | `pengumuman.diperbarui_oleh` | `users.id` | bigint unsigned | bigint unsigned | RESTRICT |
| `pengumuman_target_jurusan_idjurusan_foreign` | `pengumuman_target.jurusan_idjurusan` | `jurusan.idjurusan` | int unsigned | int unsigned | RESTRICT |
| `pengumuman_target_kelas_idkelas_foreign` | `pengumuman_target.kelas_idkelas` | `kelas.idkelas` | int unsigned | int unsigned | RESTRICT |
| `pengumuman_target_pengumuman_idpengumuman_foreign` | `pengumuman_target.pengumuman_idpengumuman` | `pengumuman.idpengumuman` | int unsigned | int unsigned | RESTRICT |
| `pengumuman_target_siswa_idsiswa_foreign` | `pengumuman_target.siswa_idsiswa` | `siswa.idsiswa` | int unsigned | int unsigned | RESTRICT |
| `pengumuman_baca_pengumuman_idpengumuman_foreign` | `pengumuman_baca.pengumuman_idpengumuman` | `pengumuman.idpengumuman` | int unsigned | int unsigned | RESTRICT |
| `pengumuman_baca_siswa_idsiswa_foreign` | `pengumuman_baca.siswa_idsiswa` | `siswa.idsiswa` | int unsigned | int unsigned | RESTRICT |

Semua 8 FK memakai `ON DELETE RESTRICT` dan tipe sama persis dengan PK tujuan.

### 2.5 Indeks (UNIQUE + KEY)

`SHOW INDEX` pada ketiga tabel:

| Tabel | Index | Unik | Kolom |
|---|---|---|---|
| `pengumuman` | `PRIMARY` | Ya | `idpengumuman` |
| `pengumuman` | `pengumuman_dibuat_oleh_foreign` | Tidak | `dibuat_oleh` |
| `pengumuman` | `pengumuman_diperbarui_oleh_foreign` | Tidak | `diperbarui_oleh` |
| `pengumuman_target` | `PRIMARY` | Ya | `idpengumuman_target` |
| `pengumuman_target` | `pengumuman_target_jurusan_unique` | Ya | `pengumuman_idpengumuman`, `jurusan_idjurusan` |
| `pengumuman_target` | `pengumuman_target_kelas_unique` | Ya | `pengumuman_idpengumuman`, `kelas_idkelas` |
| `pengumuman_target` | `pengumuman_target_siswa_unique` | Ya | `pengumuman_idpengumuman`, `siswa_idsiswa` |
| `pengumuman_target` | 3 KEY `*_foreign` | Tidak | Kolom FK jurusan/kelas/siswa |
| `pengumuman_baca` | `PRIMARY` | Ya | `idpengumuman_baca` |
| `pengumuman_baca` | `pengumuman_baca_pengumuman_siswa_unique` | Ya | `pengumuman_idpengumuman`, `siswa_idsiswa` |
| `pengumuman_baca` | `pengumuman_baca_siswa_idsiswa_foreign` | Tidak | `siswa_idsiswa` |

---

## 3. Timestamp Migration

### 3.1 Migration pengumuman

| Item | Nilai |
|---|---|
| Nama file | `2026_11_08_000001_create_pengumuman_tables.php` |
| LastWriteTime file | 11/4/2026 1:39:21 PM |
| Batch ke- | `[8] Ran` |
| Urutan terakhir sebelumnya | `2026_11_07_000005_create_absensi_perjam_table.php` `[7] Ran` |

### 3.2 Perbandingan dengan migration terkait

| Migration | Timestamp | Batch |
|---|---|---|
| `2026_11_07_000003_create_jadwalkelas_table.php` | 10/30/2026 | `[3] Ran` |
| `2026_11_07_000004_create_jadwal_pertemuan_table.php` | 11/4/2026 9:16:37 AM | `[6] Ran` |
| `2026_11_07_000005_create_absensi_perjam_table.php` | 11/4/2026 9:26:23 AM | `[7] Ran` |
| `2026_11_08_000001_create_pengumuman_tables.php` | 11/4/2026 1:39:21 PM | `[8] Ran` |

`migrate:status` (aktual):

```
2026_11_07_000005_create_absensi_perjam_table .............. [7] Ran
2026_11_08_000001_create_pengumuman_tables ............... [8] Ran
```

### 3.3 Penilaian urutan

**PASS** — timestamp 2026_11_08_000001 didahulukan oleh `2026_11_07_000005_create_absensi_perjam_table` (batch 7), dan migration pengumuman menjadi batch 8. Urutan ini tepat karena tabel prasyarat (`users`, `siswa`, `kelas`, `jurusan`, `pengumuman`) sudah ada, sehingga FK dapat dibuat dalam migration yang sama.

> Catatan: tanggal file `2026_11_08` adalah hari berikutnya dari migration sebelumnya (`2026_11_07`), sesuai konvensi proyek yang menamai migration berdasarkan hari pembuatan (bukan hari eksekusi). Tidak ada urutan yang tumpang-tindih.

---

## 4. Status Setiap Poin

| Poin | Status |
|---|---|
| 1. Struktur ketiga tabel (kolom & tipe) | **PASS** |
| 2. `created_at`/`updated_at` tersedia dan jumlah kolom cocok dengan laporan | **PASS** |
| 3. Seluruh FK RESTRICT dan tipe sama persis dengan PK tujuan | **PASS** |
| 4. Urutan timestamp migration | **PASS** |

---

## Lampiran A — Bukti Pemeriksaan

`getColumnListing`:

```
pengumuman        (14 kolom): ["idpengumuman","judul","isi","kategori","prioritas","status","waktu_terbit","berlaku_dari","berlaku_sampai","target","dibuat_oleh","diperbarui_oleh","created_at","updated_at"]
pengumuman_target (7 kolom):  ["idpengumuman_target","pengumuman_idpengumuman","jurusan_idjurusan","kelas_idkelas","siswa_idsiswa","created_at","updated_at"]
pengumuman_baca   (6 kolom):  ["idpengumuman_baca","pengumuman_idpengumuman","siswa_idsiswa","dibaca_pada","created_at","updated_at"]
```

`SHOW CREATE TABLE pengumuman` (inti):

```sql
`idpengumuman` int unsigned NOT NULL AUTO_INCREMENT,
`dibuat_oleh` bigint unsigned NOT NULL,
`diperbarui_oleh` bigint unsigned DEFAULT NULL,
CONSTRAINT `pengumuman_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
CONSTRAINT `pengumuman_diperbarui_oleh_foreign` FOREIGN KEY (`diperbarui_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT
```

`SHOW CREATE TABLE pengumuman_target` (inti):

```sql
UNIQUE KEY `pengumuman_target_jurusan_unique` (`pengumuman_idpengumuman`,`jurusan_idjurusan`),
UNIQUE KEY `pengumuman_target_kelas_unique` (`pengumuman_idpengumuman`,`kelas_idkelas`),
UNIQUE KEY `pengumuman_target_siswa_unique` (`pengumuman_idpengumuman`,`siswa_idsiswa`),
CONSTRAINT `pengumuman_target_jurusan_idjurusan_foreign` ... ON DELETE RESTRICT,
CONSTRAINT `pengumuman_target_kelas_idkelas_foreign` ... ON DELETE RESTRICT,
CONSTRAINT `pengumuman_target_pengumuman_idpengumuman_foreign` ... ON DELETE RESTRICT,
CONSTRAINT `pengumuman_target_siswa_idsiswa_foreign` ... ON DELETE RESTRICT
```

`SHOW CREATE TABLE pengumuman_baca` (inti):

```sql
UNIQUE KEY `pengumuman_baca_pengumuman_siswa_unique` (`pengumuman_idpengumuman`,`siswa_idsiswa`),
CONSTRAINT `pengumuman_baca_pengumuman_idpengumuman_foreign` ... ON DELETE RESTRICT,
CONSTRAINT `pengumuman_baca_siswa_idsiswa_foreign` ... ON DELETE RESTRICT
```

**TIDAK BISA DIUKUR**: pengujian browser/visual tidak relevan untuk verifikasi database; tidak dilakukan.

**Kesimpulan verifikasi**: seluruh poin **PASS**. Struktur ketiga tabel, `created_at`/`updated_at`, FK RESTRICT, tipe FK sama persis, UNIQUE terpisah, dan urutan timestamp migration semua terverifikasi dari skema aktual. Tahap 2B.4.6C belum dimulai.