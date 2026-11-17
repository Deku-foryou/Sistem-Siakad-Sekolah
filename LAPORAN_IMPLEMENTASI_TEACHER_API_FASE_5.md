# LAPORAN IMPLEMENTASI TEACHER API (FASE 5)

**Tanggal:** 17 November 2026 · **Status:** PASS WITH LIMITATIONS
**Stack:** Laravel 12 · Livewire 3 · MySQL 8 · Sanctum · prefix `/api/v1`

---

## 1. Ringkasan audit sebelum implementasi

### 1.1 Autentikasi & otorisasi (sudah tersedia, dipakai ulang)
- Login Sanctum: `POST /api/v1/auth/login` (throttle `api-login`).
- Middleware API: `auth:sanctum`, `api.role:<roles>` (EnsureApiRole), `api.akun` (EnsureApiAccountActive — kebijakan `LoginBlockService::cekBolehLogin`, token dicabut saat akun tidak layak), `api.password` (EnsureApiPasswordChanged — memblokir endpoint operasional bila `wajib_ganti_password`).
- Pola role web (`check.level`, `menu.access`) **tidak** dipakai di API.
- Konvensi error: `ApiResponse::fail(message, status, errors?, code?)` → `{success:false, message, errors?, code?}`; seluruh error API dipaksa JSON di `app/Exceptions/Handler.php`.
- Route mobile memakai grup per role; Fase 5 menambah grup `api.role:guru` (nested di grup `siswa,guru,wali`).

### 1.2 Data akademik guru (sumber aktual)
- Identitas: `guru` (PK `idguru`, FK `user_iduser` → `users`); akun user `level='guru'`.
- "Mengajar" otorisasi kanonis: `jadwalkelas` + `jamhari` (`jenis='pelajaran'`, `jamhari.kelas_id` = kelas) pada **semester aktif** (KonteksAkademikService).
- Wali kelas: `kelas.guru_idguru` pada **tahun ajaran aktif** (dipakai dashboard `/berandaguru`).
- Mapel guru: pivot `gurumapel`.
- Penugasan mengajar (otorisasi **nilai**): `penugasan_mengajar` — dipakai `NilaiSiswaService`, **bukan** `jadwalkelas` (dengan sengaja: dosen bisa sekolah minggu, penugasan bisa ada tanpa jadwal).
- Tahun ajaran/semester aktif: `tahun_ajaran.aktif` / `semester.aktif` lewat `KonteksAkademikService::ambil()`.

### 1.3 Absensi (audit komponen web)
- Web: `/absensi-harian` (`AbsensiHarianComponent`) dan `/absensi-perjam` (`AbsensiPerjamComponent`).
- Logika bisnis **seluruhnya** berada di `AbsensiHarianService` / `AbsensiPerjamService`; komponen/service dipakai ulang oleh API tanpa diduplikasi:
  - Harian: validasi konteks TA/semester (cocok), tanggal valid + tidak masa depan, guru harus mengajar kelas itu (`checkGuruAccess` — `jadwalkelas` semester aktif), hanya **hari ini & kemarin (H+1)**, transaksi + `lockForUpdate()`, duplikat dicek (unique check + pesan ramah).
  - Per jam: snapshot `jadwal_pertemuan` (dibuat `JadwalPertemuanService`, idempoten), pertemuan harus `terjadwal`, tidak masa depan, guru harus ditugaskan, siswa harus punya `kelassiswa` aktif di kelas pertemuan, unique `(jadwal_pertemuan, siswa)` (cek + `QueryException` 23000/1062 → DomainException), **hanya edit H+1**; create absensi tidak dibatasi H+1 (aturan web dipertahankan apa adanya).
- Kebijakan dipertahankan: guru = aktor pencatat; siswa tidak absen mandiri; superadmin **tidak** otomatis memperoleh hak mencatat absensi guru (API guru digate `api.role:guru`; service tetap mengizinkan superadmin pada jalur web — tidak diubah).

### 1.4 Penilaian (audit komponen web `InputNilaiComponent`)
- Sumber otorisasi tunggal: `penugasan_mengajar` via `NilaiSiswaService`.
- `daftarPenugasan`, `penugasan`, `siswaPenugasan`, `komponenAktif`, `konfigurasiValid`, `rekap`, `simpan`, `simpanBanyak`, `terkunci`, `validasiNilai` — dipakai ulang apa adanya.
- Aturan dipertahankan: total bobot komponen aktif harus tepat 100% (input diblokir bila tidak), nilai 0–100 (maks 2 desimal), siswa harus aktif & terdaftar di kelas penugasan, komponen harus milik semester+mapel penugasan & aktif, nilai terkunci bila rapor kelas sudah final (`finalisasi_rapor` STATUS_FINAL) atau snapshot `nilai_akhir` sudah ada, input hanya semester aktif.
- Konfigurasi komponen = kewenangan superadmin (`KomponenNilaiService` menuntut superadmin) → API guru **hanya baca** komponen.

### 1.5 Pengumuman & notifikasi
- `NotifikasiService` bersifat **user-scoped** (`daftar`, `detailUntukUser`, `tandaiDibaca`, `tandaiSemuaDibaca`, `hitungBelumDibaca`) dan role guru termasuk penerima → **dapat dipakai ulang apa adanya** untuk API guru (5 endpoint, lihat §3.G).
- `PengumumanService` bersifat **siswa-scoped** (snapshot penerima `pengumuman_baca` per siswa; tidak ada penerima guru di sistem pengumuman mana pun). Membuka pengumuman untuk guru berarti kebijakan baru (target role baru + snapshot baru) di luar scope Fase 5 → **tidak diimplementasikan** (lihat §14).

### 1.6 Temuan audit yang relevan (tanpa mengubah kebijakan)
1. **`Handler` API tidak menangkap `HttpException` 4xx polos**: `abort(403)` di dalam service melempar `Symfony\...\HttpException` (bukan `AccessDeniedHttpException`) sehingga jatuh ke `Throwable` → **500 SERVER_ERROR** untuk request yang seharusnya 403. Ini bug permukaan-error Fase 1, bukan kebijakan. **Diperbaiki** (lihat §9.3 & §12).
2. Absensi harian web mengizinkan superadmin; API guru sengaja tidak memberi akses itu (sesuai mandat).
3. Riwayat absensi harian API dibatasi kelas yang diajar pada semester aktif + wali TA aktif (pilihan paling ketat yang konsisten dengan workflow web).

---

## 2. Daftar endpoint final (29)

Semua di bawah `/api/v1/guru`, middleware: `auth:sanctum` + `api.role:siswa,guru,wali` (luar) + `api.role:guru` (dalam) + `api.akun` + `api.password`.

### A. Profil & beranda
| Method | Path |
|---|---|
| GET | `/profil` |
| GET | `/beranda` |

### B. Kelas & penugasan
| Method | Path |
|---|---|
| GET | `/kelas` |
| GET | `/kelas/{id}` |
| GET | `/kelas/{id}/siswa` |
| GET | `/penugasan` |

### C. Jadwal mengajar
| Method | Path |
|---|---|
| GET | `/jadwal?tanggal=YYYY-MM-DD` atau `?hari=1..7` |
| GET | `/jadwal/hari-ini` |
| GET | `/jadwal/{id}` (`id = idjadwalkelas`) |

### D. Absensi harian
| Method | Path |
|---|---|
| GET | `/absensi/harian` (riwayat paginated; filter `dari`,`sampai`,`status`,`kelas_id`,`siswa_id`,`per_page`,`page`) |
| GET | `/absensi/harian/{id}` |
| POST | `/absensi/harian` (simpan massal, upsert) |
| PATCH | `/absensi/harian/{id}` (ubah status/catatan) |
| GET | `/kelas/{kelasId}/absensi?tanggal=YYYY-MM-DD` (grid pengisian) |

### E. Absensi per jam
| Method | Path |
|---|---|
| GET | `/absensi/perjam` (riwayat paginated; filter `dari`,`sampai`,`status`,`kelas_id`,`siswa_id`,`per_page`,`page`) |
| GET | `/absensi/perjam/pertemuan?tanggal=YYYY-MM-DD` (default hari ini) |
| GET | `/absensi/perjam/pertemuan/{id}` (grid pertemuan: siswa + absensi) |
| POST | `/absensi/perjam` (catat massal pada satu pertemuan, upsert) |
| GET | `/absensi/perjam/{id}` (detail record) |
| PATCH | `/absensi/perjam/{id}` (ubah status/keterangan, H+1) |

### F. Penilaian
| Method | Path |
|---|---|
| GET | `/penilaian/penugasan/{id}` (penugasan + komponen/bobot + status kunci) |
| GET | `/penilaian/penugasan/{id}/siswa` (daftar siswa + nilai + nilai draft) |
| PUT | `/penilaian/penugasan/{id}/nilai` (batch upsert) |
| PATCH | `/penilaian/penugasan/{id}/nilai` (satu nilai) |

### G. Notifikasi
| Method | Path |
|---|---|
| GET | `/notifikasi` (filter `status=read|unread`,`jenis`,`per_page`,`page`) |
| GET | `/notifikasi/unread-count` |
| GET | `/notifikasi/{id}` |
| PATCH | `/notifikasi/{id}/read` |
| PATCH | `/notifikasi/read-all` |

Pengumuman guru: **tidak ada** — lihat §14.

---

## 3. Deskripsi fungsi & role (semua: role `guru` saja)

| # | Endpoint | Fungsi | Kewenangan/ownership |
|---|---|---|---|
| 1 | `GET /profil` | Identitas guru + mapel (`gurumapel`) + akun (username, wajib ganti password) | data sendiri |
| 2 | `GET /beranda` | Identitas, konteks akademik, kelas (mengajar+wali), ringkasan (jumlah kelas/penugasan/pertemuan+absensi hari ini), jadwal hari ini | data sendiri |
| 3 | `GET /kelas` | Daftar kelas (mengajar pada semester aktif ∪ wali pada TA aktif) + sumber + jumlah siswa + mapel | hanya kelasnya |
| 4 | `GET /kelas/{id}` | Detail kelas + wali kelas + jurusan + mapel + penugasan + jumlah siswa | 404 bila bukan kelasnya |
| 5 | `GET /kelas/{id}/siswa` | Siswa aktif kelas (id, nisn, nis, nama, jenis_kelamin) | 404 bila bukan kelasnya |
| 6 | `GET /penugasan` | `penugasan_mengajar` milik guru pada semester aktif | milik sendiri |
| 7 | `GET /jadwal` | Jadwal guru semester aktif; filter `tanggal` (menang) atau `hari` | milik sendiri; `guru_id` dari request diabaikan |
| 8 | `GET /jadwal/hari-ini` | Jadwal hari ini | milik sendiri |
| 9 | `GET /jadwal/{id}` | Detail satu `jadwalkelas` | 404 bila bukan miliknya |
| 10 | `GET /absensi/harian` | Riwayat absensi harian paginated (join kelas+siswa), ter-scope kelas akses | kelas akses saja |
| 11 | `GET /absensi/harian/{id}` | Detail satu record | 404 bila kelas di luar akses |
| 12 | `POST /absensi/harian` | Simpan massal (upsert per siswa); upsert = buat bila belum ada, ubah bila sudah ada | mengajar kelas itu saja; TA/semester dari konteks server, **bukan** payload |
| 13 | `PATCH /absensi/harian/{id}` | Ubah status/catatan (≤ H+1) | mengajar kelas record itu; deadline ditegakkan service |
| 14 | `GET /kelas/{kelasId}/absensi` | Grid pengisian (siswa + absensi tercatat per tanggal) | hanya kelas yang diajar (bukan wali-only), sesuai web |
| 15 | `GET /absensi/perjam` | Riwayat absensi per jam paginated (snapshot `jadwal_pertemuan`) | hanya pertemuan miliknya |
| 16 | `GET /absensi/perjam/pertemuan` | Daftar pertemuan guru pada tanggal (default hari ini) + jumlah siswa/absensi. **Membuat snapshot pertemuan yang belum ada (idempoten)** — konsisten dengan mount `/absensi-perjam` dan dibutuhkan agar mobile melihat pertemuan hari ini | milik sendiri |
| 17 | `GET /absensi/perjam/pertemuan/{id}` | Grid pertemuan (siswa + absensi) | 404 bila bukan pertemuannya |
| 18 | `POST /absensi/perjam` | Catat massal pada satu pertemuan (upsert per siswa; record yang ada diubah, yang belum dibuat) | pertemuan miliknya; siswa aktif di kelas pertemuan; ≤ hari ini; `terjadwal` |
| 19 | `GET /absensi/perjam/{id}` | Detail satu record absensi per jam | 404 bila pertemuan bukan miliknya |
| 20 | `PATCH /absensi/perjam/{id}` | Ubah status/keterangan (≤ H+1) | pertemuan miliknya; H+1 ditegakkan service |
| 21 | `GET /penilaian/penugasan/{id}` | Detail penugasan + komponen (id,nama,bobot,urutan), total bobot, `konfigurasi_valid`, `terkunci`, jumlah siswa | 404 bila penugasan bukan miliknya |
| 22 | `GET /penilaian/penugasan/{id}/siswa` | Rekap: nilai per komponen, `nilai_draft`, `lengkap`, bobot terisi/total | 404 bila bukan miliknya |
| 23 | `PUT /penilaian/penugasan/{id}/nilai` | Batch upsert `{nilai:{siswa_id:{komponen_id:nilai}}}`; nilai kosong dilewati | 404/422 bila bukan miliknya / aturan dilanggar |
| 24 | `PATCH /penilaian/penugasan/{id}/nilai` | Satu nilai `{siswa_id, komponen_id, nilai}` | 404/422 sama |
| 25 | `GET /notifikasi` | Daftar notifikasi milik user login + `belum_dibaca` | milik sendiri (penerima) |
| 26 | `GET /notifikasi/unread-count` | Jumlah belum dibaca | milik sendiri |
| 27 | `GET /notifikasi/{id}` | Detail notifikasi | 404 bila bukan penerimanya |
| 28 | `PATCH /notifikasi/{id}/read` | Tandai dibaca (idempoten) | milik sendiri |
| 29 | `PATCH /notifikasi/read-all` | Tandai semua dibaca (scoped user) | milik sendiri |

Kewenangan yang **tidak** disediakan (disampaikan eksplisit):
- Mengubah kelas, kurikulum, penugasan, komponen/bobot (admin/superadmin).
- Mengakses/merubah nilai lintas penugasan atau lintas kelas.
- Membuat pengumuman (web: superadmin; API guru: tidak ada).
- Membaca nilai siswa lain di luar kelas penugasannya.

---

## 4. Role & permission
- Mobile: **guru saja** untuk semua endpoint di §2. siswa/wali → 403 `ROLE_NOT_ALLOWED` (dari middleware luar `api.role:siswa,guru,wali` + dalam `api.role:guru`).
- **admin/superadmin → 403** `ROLE_NOT_ALLOWED` (dengan sengaja; akses guru tidak otomatis bagi admin).
- Akun guru nonaktif → 403 `ACCOUNT_INACTIVE` (+ token dicabut). Wajib ganti password → 403 `PASSWORD_CHANGE_REQUIRED`.
- Wali tidak memiliki akses (belum ada use-case wali-mengajar; bukan kewenangan walikelas).

---

## 5. Contoh request & response (hasil aktual dari pengujian)

### 5.1 `GET /api/v1/guru/profil` (contoh aktual)
```json
{
  "success": true,
  "message": "Berhasil",
  "data": {
    "id": 1285,
    "nip": "000000000005083",
    "kode_guru": null,
    "nama": "Guru Uji 2356085439 2356085439",
    "jenis_kelamin": "L",
    "tempat_lahir": null,
    "tanggal_lahir": "1980-01-01",
    "telepon": null,
    "alamat": null,
    "status": "aktif",
    "mapel": [],
    "akun": { "username": "uuji5_guru_2356085439", "wajib_ganti_password": false }
  }
}
```

### 5.2 `GET /api/v1/guru/beranda` (contoh aktual)
```json
{
  "success": true,
  "message": "Berhasil",
  "data": {
    "guru": { "id": 1285, "nip": "000000000005083", "kode_guru": null, "nama": "Guru Uji 2356085439 2356085439", "status": "aktif" },
    "akun": { "username": "uuji5_guru_2356085439", "wajib_ganti_password": false },
    "konteks": {
      "valid": true,
      "peringatan": [],
      "tahun_ajaran": { "id": 960, "nama": "UJI-TA-FASE5" },
      "semester": { "id": 2642, "nama": "Ganjil" }
    },
    "kelas": [
      { "id": 2048, "nama": "UJI-5A", "tingkat": "10", "jenis": "induk", "rombel": 1, "sumber": ["mengajar", "wali"], "jumlah_siswa": 2 },
      { "id": 2050, "nama": "UJI-5C", "tingkat": "10", "jenis": "induk", "rombel": 3, "sumber": ["wali"], "jumlah_siswa": 0 },
      { "id": 2051, "nama": "UJI-5D", "tingkat": "10", "jenis": "induk", "rombel": 4, "sumber": ["wali"], "jumlah_siswa": 1 }
    ],
    "ringkasan": {
      "jumlah_kelas": 3,
      "jumlah_penugasan": 3,
      "pertemuan_hari_ini": 1,
      "absensi_perjam_hari_ini": 1,
      "absensi_harian_hari_ini": 3
    },
    "nama_hari": "Selasa",
    "jadwal_hari_ini": [
      { "id": 1810, "hari": 2, "nama_hari": "Selasa", "jam_ke": 1, "mulai": "07:00", "selesai": "07:45",
        "mapel": { "id": 1912, "kode": "UJI5-MTK", "nama": "Matematika Uji" },
        "kelas": { "id": 2048, "nama": "UJI-5A", "tingkat": "10" },
        "semester": { "id": 2642, "nama": "Ganjil" } }
    ]
  }
}
```

### 5.3 `GET /api/v1/guru/kelas` (potongan aktual)
```json
{ "success": true, "message": "Berhasil",
  "data": { "konteks": { "...": "..." },
    "kelas": [
      { "id": 2048, "nama": "UJI-5A", "tingkat": "10", "jenis": "induk", "rombel": 1,
        "tahunajaran": { "id": 960, "nama": "UJI-TA-FASE5" }, "jurusan": null,
        "sumber": ["mengajar", "wali"], "jumlah_siswa": 2,
        "mapel": [ { "id": 1912, "kode": "UJI5-MTK", "nama": "Matematika Uji" },
                   { "id": 1913, "kode": "UJI5-BIN", "nama": "B. Indonesia Uji" } ] }
    ] } }
```

### 5.4 `GET /api/v1/guru/jadwal/hari-ini` (contoh aktual)
```json
{ "success": true, "message": "Berhasil",
  "data": { "tanggal": "2026-11-17", "hari": 2, "nama_hari": "Selasa",
    "konteks": { "...": "..." },
    "jadwal": [ { "id": 1810, "hari": 2, "nama_hari": "Selasa", "jam_ke": 1, "mulai": "07:00", "selesai": "07:45",
                  "mapel": { "...": "..." }, "kelas": { "...": "..." }, "semester": { "...": "..." } } ] } }
```

### 5.5 `GET /api/v1/guru/kelas/{kelasId}/absensi` (contoh aktual)
```json
{ "success": true, "message": "Berhasil",
  "data": { "tanggal": "2026-11-17", "kelas_id": 2048, "konteks": { "...": "..." },
    "siswa": [
      { "id": 2694, "nisn": "0000035595", "nama": "Siswa Uji A1",
        "absensi": { "id": 3100, "status": "hadir", "catatan": null } },
      { "id": 2695, "nisn": "0000035602", "nama": "Siswa Uji A2",
        "absensi": { "id": 3101, "status": "izin", "catatan": null } }
    ] } }
```

### 5.6 `POST /api/v1/guru/absensi/harian` (contoh aktual)
Request:
```json
{
  "tanggal": "2026-11-16",
  "kelas_id": 2048,
  "records": [ { "siswa_id": 2695, "status": "hadir", "catatan": "contoh" } ]
}
```
Response `200`:
```json
{ "success": true, "message": "Absensi berhasil disimpan.",
  "data": { "tanggal": "2026-11-16", "kelas_id": 2048, "dibuat": 1, "diubah": 0 } }
```

### 5.7 `PUT /api/v1/guru/penilaian/penugasan/{id}/nilai`
Request:
```json
{ "nilai": { "2694": { "3101": 80, "3102": 90 }, "2695": { "3101": 70 } } }
```
Response `200`:
```json
{ "success": true, "message": "3 nilai berhasil disimpan.", "data": { "tersimpan": 4 } }
```

### 5.8 Error (contoh)
```json
{ "success": false, "message": "Akses ditolak untuk role ini.", "code": "ROLE_NOT_ALLOWED" }
```
```json
{ "success": false, "message": "Kelas tidak ditemukan.", "code": "KELAS_NOT_FOUND" }
```
```json
{ "success": false, "message": "Anda wajib mengganti password terlebih dahulu.", "code": "PASSWORD_CHANGE_REQUIRED" }
```
```json
{ "success": false, "message": "Guru hanya dapat mencatat atau mengubah absensi untuk hari ini dan kemarin (H+1 deadline).", "code": "GAGAL_UBAH" }
```

---

## 6. Validasi & error response

| Situasi | Status | Code |
|---|---|---|
| Token tidak ada / invalid | 401 | `UNAUTHENTICATED` |
| Role bukan guru | 403 | `ROLE_NOT_ALLOWED` |
| Akun tidak layak (nonaktif) | 403 | `ACCOUNT_INACTIVE` (+ token dicabut) |
| Wajib ganti password | 403 | `PASSWORD_CHANGE_REQUIRED` |
| Validasi request (Form Request) | 422 | `VALIDATION_ERROR` (body Berisi `errors`) |
| Konteks akademik tidak valid (TA/semester belum aktif / tidak cocok) | 422 | `KONTEKS_TIDAK_VALID` |
| Tanggal hari ini/kemarin di luar kebijakan | 422 | `GAGAL_SIMPAN` / `GAGAL_UBAH` |
| Siswa di luar kelas / tidak aktif | 422 | `GAGAL_SIMPAN` |
| Duplikat siswa dalam `records` | 422 | `DUPLIKAT_SISWA` |
| Pertemuan bukan `terjadwal` / masa depan / H+1 lewat | 422 | `GAGAL_SIMPAN` / `GAGAL_UBAH` |
| Ownership kelas/pertemuan/penugasan/absensi/notifikasi gagal | 404 | `KELAS_NOT_FOUND` / `PERTEMUAN_NOT_FOUND` / `ABSENSI_NOT_FOUND` / `PENUGASAN_NOT_FOUND` / `NOTIFIKASI_NOT_FOUND` |
| Kewenangan mengajar gagal (akses ditolak service via `abort(403)`) | 403 | `FORBIDDEN` |
| Konfigurasi komponen tidak valid (bobot ≠ 100%) | 422 | `GAGAL_SIMPAN` |
| Rapor kelas sudah final / nilai akhir ada | 422 | `GAGAL_SIMPAN` |
| Nilai di luar 0–100 / bukan angka | 422 | `VALIDATION_ERROR` |
| Resource tidak ditemukan rute/ID | 404 | `NOT_FOUND` |
| Error internal | 500 | `SERVER_ERROR` (detail hanya di log) |

Catatan desain: pada jalur **baca**, kegagalan ownership mengembalikan **404** (menghindari probing eksistensi resource). Pada jalur **tulis**, service menegakkan otorisasi yang sama; penolakan otentisitas guru → 404 (sebelum masuk service) atau 403 (dari abort service). Semua 4xx menghasilkan JSON (§1.6.1).

---

## 7. Service existing yang digunakan kembali
`AbsensiHarianService`, `AbsensiPerjamService`, `JadwalPertemuanService` (generate & grid pertemuan — pemanggilan `generateUntukTanggal` dari GET list pertemuan, idempoten), `JadwalSiswaService` (`urutkan`/`perHari`), `NilaiSiswaService` (semua: daftarPenugasan, penugasan, siswaPenugasan, komponenAktif, konfigurasiValid, rekap, simpan, simpanBanyak, terkunci), `KomponenNilaiService` (tidak diubah; hanya baca bobot), `NotifikasiService`, `KonteksAkademikService`, `LoginBlockService` (via middleware), `RekapAbsensiService` (tidak diubah; tidak dipakai langsung).

## 8. Service / file baru
Baru: `app/Services/GuruApiService.php` (agregasi & otorisasi kelas/jadwal milik guru; read-only), `app/Services/BerandaGuruService.php` (beranda guru).
Resource baru: `GuruBerandaResource`, `GuruJadwalResource`, `GuruAbsensiHarianResource`, `GuruAbsensiPerjamResource`, `PenugasanResource`.
Request baru: `GuruJadwalIndexRequest`, `GuruAbsensiRiwayatRequest`, `AbsensiHarianStoreRequest`, `AbsensiHarianUpdateRequest`, `AbsensiPerjamStoreRequest`, `AbsensiPerjamUpdateRequest`, `PertemuanIndexRequest`, `SimpanNilaiRequest`, `NilaiSingleRequest`.
Tidak ada tabel/kolom/migration/seeder/dependency baru. Tidak ada perubahan `.env`.

---

## 9. Security & IDOR

### 9.1 Keputusan arsitektur otorisasi
- Middleware: grup luar `siswa,guru,wali`, grup dalam `guru` saja → siswa/wali/admin/superadmin/role lain = 403 `ROLE_NOT_ALLOWED`.
- Semua identitas (`guru_id`, `siswa_id`, identitas pencatat) **selalu dari `auth()->user()`**; payload tidak pernah menjadi dasar otorisasi (payload `guru_id` diabaikan — diuji).
- Otorisasi dua lapis: pre-check ownership (404, anti-probing) + penegakan service existing (domain rule, H+1, snapshot).
- Response tidak memuat field sensitif/internal (tidak ada password, token, atau ID user internal; `diinput_oleh`/`diubah_oleh` tidak diekspos).

### 9.2 Matriks uji (seluruhnya otomatis, rollback per test)
| # | Uji | Hasil |
|---|---|---|
| 1 | Siswa → `/guru/*` | 403 `ROLE_NOT_ALLOWED` |
| 2 | Wali → `/guru/*` | 403 `ROLE_NOT_ALLOWED` |
| 3 | Superadmin/admin → `/guru/*` | 403 `ROLE_NOT_ALLOWED` |
| 4 | Guru A baca kelas guru B | 404 (`kelas`, `kelas/{id}`, `kelas/{id}/siswa`, grid) |
| 5 | Guru A baca jadwal guru B | 404 (`jadwal/{id}` guru B) |
| 6 | Guru A baca siswa kelas B | 404 |
| 7 | Guru A catat absensi kelas B (harian/per jam) | 403 (service abort) / 404 (pre-check) / 422 |
| 8 | Guru A ubah absensi kewenangan guru B | 404 (harian & per jam) |
| 9 | Guru A baca/ubah nilai penugasan guru B | 404 (detail/rekap/PUT/PATCH) |
| 10 | Manipulasi ID URL/payload (`guru_id` palsu, siswa luar kelas, pertemuan/record orang lain, komponen luar mapel, siswa duplikat) | 404/422; record tersimpan dengan `diinput_oleh` = guru A |
| 11 | Akun nonaktif / wajib ganti password | 403 `ACCOUNT_INACTIVE` / `PASSWORD_CHANGE_REQUIRED` |
| 12 | Tanpa token / token invalid | 401 `UNAUTHENTICATED` |

### 9.3 Hardening temuan audit (perubahan keamanan)
`app/Exceptions/Handler.php`: ditambahkan renderer `HttpExceptionInterface` (API-only): status 4xx polos (mis. `abort(403)` di service → sebelumnya **500 SERVER_ERROR**) kini menjadi JSON dengan kode `FORBIDDEN`/`NOT_FOUND`/`UNAUTHENTICATED`/`HTTP_<status>` sesuai status; 5xx sengaja dibiarkan ke fallback generic (detail tidak bocor). Perilaku web tidak diubah (renderable hanya untuk `api/*`). Regresi Fase 1–4 tetap hijau (§11).

### 9.4 Yang tidak bisa diklaim
- **Concurrency**: tidak diuji dengan beban paralel. `lockForUpdate()`/transaksi mengikuti implementasi web apa adanya, tetapi tidak ada bukti multi-klien; klaim "aman concurrency" **tidak** diberikan (lihat §14).
- Browser/UAT manual Flutter: **Belum diuji**.

---

## 10. Hasil automated test
- `tests/Feature/Api/V1/GuruApiTest.php` (baru): **65 tests / 167 assertions — PASS** (termasuk 12 kelompok: auth/role, profil/beranda, kelas/penugasan, jadwal, absensi harian (grid, upsert, IDOR, deadline, payload), per jam (H+1, snapshot, IDOR), penilaian (batch, rentang, finalisasi, konfigurasi bobot), notifikasi).
- Contoh keluaran (17 Nov 2026): `Tests: 65 passed (167 assertions)`.
- Contoh keluaran beranda/profil/etc. adalah output aktual dari run tersebut (§5).

## 11. Hasil regression
Suite penuh: `php artisan test` → **146 tests / 406 assertions — PASS** (`Duration: 50.84s`).
Rincian relevan:
- `AuthApiTest` (Fase 1), `StudentApiTest` (Fase 2, 79 tests termasuk Fase 1/3-adjacent), `AnnouncementNotificationApiTest` (Fase 3), `FinanceApiTest` + `FinanceHardeningTest` (Fase 4) — semuanya PASS.
- Tidak ada test lama yang diubah (GuruApiTest baru; GuruApiTest ditulis ulang dari nol setelah Pint, tanpa menyentuh test lama).

## 12. Daftar file yang berubah (FASE 5)

**Baru:**
- `app/Services/GuruApiService.php`
- `app/Services/BerandaGuruService.php`
- `app/Http/Controllers/Api/V1/GuruController.php`
- `app/Http/Controllers/Api/V1/GuruKelasController.php`
- `app/Http/Controllers/Api/V1/GuruJadwalController.php`
- `app/Http/Controllers/Api/V1/GuruAbsensiController.php`
- `app/Http/Controllers/Api/V1/GuruPenilaianController.php`
- `app/Http/Controllers/Api/V1/GuruNotifikasiController.php`
- `app/Http/Requests/Api/V1/{GuruJadwalIndexRequest,GuruAbsensiRiwayatRequest,AbsensiHarianStoreRequest,AbsensiHarianUpdateRequest,AbsensiPerjamStoreRequest,AbsensiPerjamUpdateRequest,PertemuanIndexRequest,SimpanNilaiRequest,NilaiSingleRequest}.php`
- `app/Http/Resources/Api/V1/{GuruBerandaResource,GuruJadwalResource,GuruAbsensiHarianResource,GuruAbsensiPerjamResource,PenugasanResource}.php`
- `tests/Feature/Api/V1/GuruApiTest.php`
- `LAPORAN_IMPLEMENTASI_TEACHER_API_FASE_5.md`

**Diubah:**
- `routes/api.php` (grup `api.v1.guru.*`, 29 route; grup sebelumnya tidak diubah)
- `app/Exceptions/Handler.php` (renderer `HttpExceptionInterface` untuk API-only, §9.3)
- `ROADMAP.md` (entri Fase 1–5)

**Pra-ada (bukan Fase 5, belum ter-commit, tidak disentuh):** `app/Services/NotifikasiService.php`, `app/Services/PengumumanService.php`, controller/request/resource API Fase 3–4, laporan Fase 3–4 — pekerjaan Fase 1–4 yang belum di-commit; tidak diubah oleh Fase 5.

## 13. Perubahan database / dependency / konfigurasi
**TIDAK ADA.** Tidak ada migration, tabel, kolom, seeder, index, trigger, dependency Composer/npm, dan tidak ada akses/pengubahan `.env`. Satu-satunya perubahan perilaku di luar fitur baru: perbaikan renderer API (§9.3) dan penambahan route `guru`.

---

## 14. Keterbatasan & pekerjaan yang ditunda
1. **Pengumuman guru tidak diimplementasikan**: modul pengumuman adalah siswa-scoped (snapshot `pengumuman_baca` per siswa; guru bukan penerima). Mengekspos pengumuman ke guru = kebijakan baru + snapshot penerima baru → menunggu keputusan pemilik proyek.
2. **Concurrency tidak diuji** dengan beban paralel/lock-test; klaim keamanan hanya sampai level kode (transaksi + `lockForUpdate()` mengikuti web).
3. **Riwayat absensi harian** dibatasi kelas yang diajar pada **semester aktif** + wali pada **TA aktif** (pilihan paling konservatif; riwayat semester lampau di kelas yang sama tidak tampil bila guru tidak lagi mengajar/wali). Belum ada endpoint ekspor rekap guru (CSV) — menunggu kebutuhan klien.
4. **GET `/absensi/perjam/pertemuan` memicu `generateUntukTanggal()`** (menulis snapshot pertemuan yang belum ada, idempoten) — konsisten secara sengaja dengan mount halaman `/absensi-perjam`; terdokumentasi agar tidak mengejutkan (itu satu-satunya `GET` Fase 5 yang menulis, dan menulis snapshot, bukan mengubah data akademik/absensi).
5. **Upsert vs "penolakan duplikat"**: POST absensi adalah upsert per (pertemuan/siswa, tanggal/kelas/siswa) — kiriman ulang memperbarui status, bukan membuat baris ganda (konsisten dengan tombol "Simpan" web). Penolakan duplikat tetap ada di service (`QueryException` 23000/1062 → `DomainException`) sebagai backstop; pengujian ganda-kirim mengonfirmasi tidak ada baris ganda.
6. **UAT browser / Flutter E2E belum diuji** ("Belum diuji"); Payment gateway (Fase 4) tetap BLOCKED sesuai keputusan Fase 4.
7. **Perubahan `Handler.php`** berdampak ke seluruh endpoint API (Fase 1–4): error 4xx polos kini JSON konsisten (sebelumnya 500). Sudah diverifikasi dengan regression penuh (146/406 hijau).
8. Endpoint notifikasi guru memanfaatkan service yang sama dengan siswa/wali — tidak ada jenis notifikasi khusus guru yang dibuat.

## 15. Status kesiapan integrasi Flutter
**Siap untuk integrasi teknis (belum UAT)**: kontrak API lengkap (29 endpoint), error code konsisten (`code` field), otorisasi ditegakkan penuh di backend, contoh request/response tersedia (§5). Sebelum rilis mobile: (a) uji dengan aplikasi Flutter nyata, (b) putuskan pengumuman guru (§14.1), (c) uji concurrency bila diperlukan, (d) review lebar respons di jaringan lambat (tidak ada kompresi diukur di Fase 5).

## 16. Perintah verifikasi
```
php artisan route:list --path=api/v1/guru   # 29 route
php artisan test --filter=GuruApiTest        # 65 passed, 167 assertions
php artisan test                             # 146 passed, 406 assertions
php vendor/bin/pint <file>                   # dijalankan untuk file yang berubah
```
