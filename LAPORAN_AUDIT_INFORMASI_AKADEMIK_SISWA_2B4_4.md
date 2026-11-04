# LAPORAN AUDIT INFORMASI AKADEMIK SISWA — TAHAP 2B.4.4

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal audit | 4 November 2026 |
| Sifat | **Audit only.** Tidak ada file aplikasi yang dibuat atau diubah; tidak ada migration/seeder; tidak mengubah database; tidak membaca `.env`; Git read-only |
| Metode | Pemeriksaan model/migration/relasi/service/Livewire/Blade/route/middleware + query skema & data read-only + render halaman siswa, semua dalam transaksi + `rollBack()` di `finally` |
| Cakupan | Identitas akademik siswa, wali kelas, mata pelajaran, halaman Portal Siswa (Beranda, Profil Saya, Jadwal, Histori Absensi), keamanan & isolasi data |

---

## 1. Ringkasan Kondisi Fitur

Portal siswa menampilkan informasi akademik yang **benar dan konsisten** pada semua halaman. Sumber data utama selalu konsisten:

- Identitas selalu dari `auth()->user()->siswa`.
- Konteks akademik selalu dari `KonteksAkademikService::ambil()` (TA 2026/2027 + Semester Ganjil).
- Penempatan selalu `kelassiswa.status = 'aktif'` pada tahun ajaran aktif.
- Mata pelajaran dari `jadwalkelas` + `jamhari` (bukan tabel legacy).
- Wali kelas tersedia di Beranda dan Profil (sebelumnya), namun pada redesign 2B.4.2 **dihapus dari kartu ringkasan Profil** sehingga Beranda dan Profil kini **tidak konsisten**.

### Status per fitur

| Fitur | Status | Keterangan |
|---|---|---|
| Kelas & jurusan aktif | ✔ Tersedia | Beranda & Profil menampilkan kelas + jurusan |
| TA & semester aktif | ✔ Tersedia | Konsisten di 3 halaman |
| Status penempatan | ✔ Tersedia | Hanya `aktif` yang ditampilkan; nonaktif tidak muncul |
| Wali kelas | ✔ Tersedia (Beranda saja) | Profil redesain tidak menampilkan wali kelas |
| Mata pelajaran | ✔ Tersedia | Dari `jadwalkelas` + `jamhari`; 3 mapel untuk kelas 1 |
| Guru pengampu | ✔ Tersedia | Dari `JadwalKelas.guru` dan `jamhari` |
| Halaman Biodata pribadi | ✔ Tersedia | Profil menampilkan NIK tersamarkan, TTL, JK, agama, kontak, ortu |
| Halaman daftar mapel khusus | ✖ Belum tersedia | Mata pelajaran hanya tampak dalam jadwal mingguan dan jadwal hari ini |
| Halaman kontak wali kelas | ✖ Belum tersedia | Hanya nama wali kelas di Beranda; kontak wali tidak tampil |
| Pengampu per mapel (dari `pengampu`) | ✖ Belum tersedia | Tidak dipakai halaman siswa; guru diambil dari `JadwalKelas.guru` |
| Navigasi menu siswa | ✔ Tersedia | `berandasiswa`, `jadwalsiswa`, `absensi-siswa`, `profil-siswa`, `setting` |

---

## 2. Daftar Halaman dan Informasi yang Ditampilkan

| Halaman (route) | Takaran info ditampilkan | Info akademik |
|---|---|---|
| `/berandasiswa` (`BerandasiswaComponent`) | NISN, NIS, kelas, jurusan, wali kelas, TA, semester, tahun masuk, status, badge akun | ✔ Lengkap |
| `/profil-siswa` (`ProfilSiswaComponent`) | Identitas lengkap + 4 kartu statistik (TA, semester, kelas, jurusan) + kontak | ✔ Paling lengkap |
| `/jadwalsiswa` (`JadwalSiswaComponent`) | Jadwal per hari (waktu, kelas, mapel, guru) + TA/semester | ✔ Jadwal |
| `/absensi-siswa` (`HistoriAbsensiSiswaComponent`) | Histori harian & per jam (status, kelas, mapel, guru pada per jam) + TA/semester | ✔ Absensi |
| `/setting` | Pengaturan akun (bukan informasi akademik) | — |

### Informasi yang duplikat atau tidak konsisten

| Informasi | Beranda | Profil (redesain) | Konsistensi |
|---|---|---|---|
| TA & semester | Judul + kartu biodata | Header + kartu ringkasan | ✔ Konsisten |
| Kelas | Kartu biodata | Kartu ringkasan | ✔ Konsisten |
| Jurusan | Kartu biodata | Kartu ringkasan | ✔ Konsisten |
| Wali kelas | **Ada** ("Wali Kelas") | **Tidak ada** (label "Wali" dari data siswa) | **✘ Tidak konsisten** |
| Wali siswa (ortu/wali) | Tidak ada | Ada (Kontak & Keluarga) | — (berbeda sumber) |
| Mapel | Jadwal hari ini saja | Tidak tampil | — |
| Guru pengampu | Jadwal hari ini | Tidak tampil | — |

---

## 3. Pemetaan Sumber Data dan Relasi

| Tampilan | Sumber | Relasi |
|---|---|---|
| Nama, NISN, NIS, status | `siswa` (select 7 kolom) | `User::siswa()` → `Siswa` |
| TA & semester | `KonteksAkademikService::ambil()` | `Tahunajaran::aktif()`, `Semester where aktif = true` |
| Kelas aktif | `Kelassiswa` | `where siswa_idsiswa AND status='aktif' with kelas.jurusan, kelas.wali where kelas.tahunajaran_idtahunajaran = TA aktif` |
| Jurusan | `Kelas::jurusan()` | FK `jurusan_id` → `jurusan.idjurusan` |
| Wali kelas | `Kelas::wali()` | FK `guru_idguru` → `guru.idguru` |
| Jadwal hari ini | `JadwalKelas` | `where semester_idsemester = semester aktif whereHas jamhari (kelas_id ∈ kelas aktif AND hari = hari ini)` + `jamhari.kelas`, `mapel`, `guru` |
| Jadwal mingguan | `JadwalKelas` | Sama, tanpa filter hari, dikelompokkan per hari |
| Histori harian | `AbsensiHarian` | `forSiswa`, `forSemester`, `betweenTanggal` + `kelas` |
| Histori per jam | `AbsensiPerjam` + `jadwal_pertemuan` (snapshot) | Join `kelas`, `mapel`, `guru` |

### Tabel yang refres

| Tabel | Dipakai siswa? |
|---|---|
| `jadwal`, `jampelajaran`, `polajam`, `publikasijadwal` | **Tidak** (legacy; `publikasijadwal` 0 baris di DB) |
| `pengampu` | **Tidak** (hanya jalur publikasi lama) |

---

## 4. Hasil Audit Keamanan dan Isolasi Data

| Aspek | Hasil |
|---|---|
| Identitas dari `auth()->user()->siswa` | **Aman** — semua komponen siswa memakai `Auth::user()` |
| Tidak menerima ID siswa dari URL/input | **Aman** — komponen siswa tidak memiliki public property `idsiswa`; tidak ada parameter yang dapat mengubah identitas |
| Isolasi data antar siswa | **Aman** — siswa 1 tidak melihat data siswa 2 (nama, kelas, mapel, absensi) |
| Otorisasi route/menu | **Aman** — grup `check.level:siswa` + `menu.access`; guru/superadmin → 404 |
| Guard komponen | **Aman** — `abort_unless(level === 'siswa', 403)` pada semua komponen siswa |
| Manisip kesehatan | **Aman** — siswa hanya membaca data miliknya; query selalu difilter `siswa_idsiswa = siswa login` |

---

## 5. Fitur yang Sudah Tersedia

1. Kelas & jurusan aktif di Beranda dan Profil.
2. TA & semester aktif dari satu sumber (`KonteksAkademikService`).
3. Wali kelas di Beranda (nama `Kanben Daissuke` untuk kelas 1).
4. Mata pelajaran + guru dari `jadwalkelas` + `jamhari` (3 sesi kelas 1: Matematika, Agama, Bahasa Indonesia).
5. Jadwal hari ini di Beranda (konsisten dengan Jadwal mingguan).
6. Histori absensi harian & per jam (sudah terintegrasi sejak 2B.4.3).
7. Identitas lengkap di Profil (NIK tersamarkan, TTL, JK, agama, kontak, ortu).
8. Menu siswa: hanya satu "Profil Saya" milik `/profil-siswa`.

---

## 6. Fitur yang Belum Tersedia

| Fitur | Keterangan |
|---|---|
| Wali kelas di Profil (redesain 2B.4.2) | Kartu ringkasan tidak memuat wali kelas; Beranda masih menampilkan |
| Daftar mata pelajaran khusus | Mapel hanya tampak dalam jadwal; tidak ada halaman daftar mapelper kelas |
| Kontak wali kelas (telepon/email) | Tidak ditampilkan di halaman mana pun |
| Guru pengampu per mapel dari `pengampu` | Tidak dipakai; guru diambil dari `JadwalKelas.guru` |
| Riwayat penempatan kelas (sebelumnya) | Tidak ditampilkan (hanya kelas aktif) |
| Empty state / fallback guru/mapel kosong | "Belum ada jadwal" tersedia; guru/mapel kosong tidak terjadi pada data saat ini |

---

## 7. Temuan Bug atau Inkonsistensi

| ID | Temuan | Dampak |
|---|---|---|
| BUG-1 | **Profil (redesain 2B.4.2) tidak menampilkan wali kelas**, tetapi Beranda masih menampilkannya. Sumber data sama (`Kelas::wali`), tampilannya berbeda. | Siswa melihat informasi hilang di Profil; tidak konsisten antarhalaman |
| BUG-2 | Label "Wali" pada Profil berasal dari `siswa.nama_wali` (ortu/wali murid), bukan wali kelas. Nama keduanya mudah tertukar. | Kebingungan; ortu/wali dan wali kelas sulit dibedakan |
| BUG-3 | Beranda memakai `select` 7 kolom siswa, sehingga field identitas tidak tersedia di Beranda (tidak ada NIK/TTL/alamat). | Beranda tidak dapat menampilkan identitas lengkap tanpa query ulang |
| BUG-4 | Menu `setting` dilabeli "Pengaturan Akun" di database, tetapi sidebar guru juga menampilkan label yang sama untuk konfigurasi akun guru. | Konsisten secara nama, namun tidak ada penanda peran khusus |

---

## 8. Risiko Database dan Data Duplikat

| Risiko | Bukti |
|---|---|
| `publikasijadwal` kosong (0 baris) | Beranda lama membaca tabel ini dan selalu kosong; perbaikan 2B.4.1 sudah mengalihkannya ke `jadwalkelas` |
| Duplikat penempatan | Tidak ditemukan (unique + query selalu difilter) |
| Siswa dengan >1 penempatan aktif | Tidak terjadi (siswa 1 → 1 kelas; siswa 2 → 1 kelas) |
| Data induk kosong (alamat/telepon/ortu 0/2) | Profil menampilkan "Belum diisi" — bukan error |
| Konsistensi jurusan/wali | Jurusan dan wali diambil dari `Kelas` (FK terdaftar); tidak ada duplikasi |

---

## 9. Rekomendasi Urutan Implementasi

| Urutan | Rekomendasi | Labs |
|---|---|---|
| 1 | Tampilkan **wali kelas** pada kartu ringkasan Profil (sumber `Kelas::wali`, sama seperti Beranda) | Konsistensi antarhalaman |
| 2 | Ganti label "Wali" pada Profil menjadi "Wali Murid" agar tidak tertukar dengan wali kelas | Kejelasan |
| 3 | Perluasan `select` Beranda agar field identitas tersedia tanpa query ulang (jika diperlukan) | Kinerja membaca |
| 4 | Halaman daftar mata pelajaran per kelas (dari `JadwalKelas` + `jamhari`) — opsional | Kelengkapan akademik |

**Jangan melakukan `migrate:fresh`, `migrate:reset`, atau `db:wipe`** — tidak diperlukan; semua sumber data sudah tersedia.

---

## 10. Bukti Pengujian

```
=== T1: Konteks akademik ===
INFO | konteks = {"ta":"2026/2027","semester":"Ganjil","valid":true}
PASS | Konteks valid

=== T2: Penempatan & identitas (siswa 2) ===
INFO | kelas aktif siswa 2 = ["X MIPA 1"]
PASS | Satu kelas aktif X MIPA 1
PASS | Jurusan tersedia (MIPA)
INFO | jurusan = MIPA
PASS | Wali kelas tersedia
INFO | wali kelas = Kanben Daissuke
INFO | penempatan nonaktif siswa 2 = 0

=== T3: Wali kelas tersedia di UI? ===
PASS | Profil menampilkan label "Wali" (dari data siswa)
PASS | Profil TIDAK menampilkan wali kelas (redesign 2B.4.2)
PASS | Beranda menampilkan "Wali Kelas"
PASS | Beranda menampilkan nama wali kelas (Kanben Daissuke)
PASS | Jadwal menampilkan nama guru (Matsumoto uchiha)

=== T4: Mata pelajaran siswa ===
INFO | jumlah sesi kelas 1 = 3
INFO | mapel kelas 1 = ["Matematika","Agama","Bahasa Indonesia"]
PASS | Kelas 1 memiliki 3 mapel (Matematika, Bahasa Indonesia, Agama)
PASS | Semua sesi punya mapel
PASS | Semua sesi punya guru
INFO | publikasijadwal rows = 0
PASS | Tidak ada ketergantungan publikasijadwal pada halaman siswa
PASS | BerandasiswaComponent tidak memakai tabel legacy (jadwal/publikasi)
PASS | ProfilSiswaComponent tidak memakai tabel legacy (jadwal/publikasi)
PASS | JadwalSiswaComponent tidak memakai tabel legacy (jadwal/publikasi)
PASS | HistoriAbsensiSiswaComponent tidak memakai tabel legacy (jadwal/publikasi)

=== T5: konsistensi antar halaman ===
PASS | Beranda memuat Matematika (Senin)
PASS | Jadwal memuat Matematika
PASS | Beranda & Jadwal konsisten (Matematika di keduanya)
PASS | Semua halaman menampilkan TA 2026/2027
PASS | Semua halaman menampilkan Semester Ganjil
PASS | Semua halaman menampilkan kelas X MIPA 1

=== T6: Isolasi & IDOR ===
PASS | Siswa 1 kelas XI MIPA 1 (profil)
PASS | Siswa 1 tidak memuat Hagoromo (profil)
PASS | Siswa 1 tidak memuat Hagoromo (beranda)
PASS | Siswa 1 tidak melihat mapel kelas 1 (beranda Senin)
PASS | Profil: identitas dari auth()
PASS | Profil: tidak ada public property idsiswa
PASS | Profil: read-only (tidak ada create/save/delete)
PASS | Histori: tidak menerima ID siswa dari input

=== T7: Guard & otorisasi ===
PASS | App\Livewire\ProfilSiswaComponent guard level siswa
PASS | App\Livewire\BerandasiswaComponent guard level siswa
PASS | App\Livewire\JadwalSiswaComponent guard level siswa
PASS | App\Livewire\HistoriAbsensiSiswaComponent guard level siswa
PASS | Guru render profil -> 403
PASS | Superadmin render profil -> 403

=== T8: Struktur kelas & jurusan ===
INFO | tabel kelas ada = 1
INFO | tabel jurusan ada = 1
PASS | Kelas 1 jurusan_id = 1
INFO | jurusan kelas 1 = MIPA
INFO | wali kelas 1 = Kanben Daissuke
INFO | pengampu rows = 2
PASS | Tidak ada halaman siswa yang memakai pengampu

=== T9: Duplikasi & inkonsistensi ===
INFO | menu siswa (usermenu user 5) = ["berandasiswa","setting","absensi-siswa","jadwalsiswa","profil-siswa"]
INFO | menu bernama Profil Saya = ["profil-siswa"]
PASS | Hanya satu menu "Profil Saya"

=== T10: Data duplikat ===
PASS | Tidak ada duplikat penempatan (siswa,kelas)
INFO | penempatan aktif siswa 2 = 1
PASS | Siswa 2 memiliki tepat 1 penempatan aktif
-- Transaksi di-rollback --
PASS | Rollback bersih (kelassiswa 2, publikasijadwal 0)

SEMUA UJI PASS
```

Menu aktif di database:

```
[{"appmenunew":"Beranda Siswa","url":"berandasiswa","icon":"fa-solid fa-house","urut":1},
 {"appmenunew":"Pengaturan Akun","url":"setting","icon":"fa-solid fa-gear","urut":3},
 {"appmenunew":"Jadwal Pelajaran","url":"jadwalsiswa","icon":"fa-solid fa-calendar-week","urut":10},
 {"appmenunew":"Histori Absensi","url":"absensi-siswa","icon":"fa-solid fa-clipboard-list","urut":11},
 {"appmenunew":"Profil Saya","url":"profil-siswa","icon":"fa-solid fa-id-card","urut":12}]
```

**TIDAK BISA DIUKUR**: pengujian browser/visual pada beragam ukuran layar (Belum diuji).

---

## Kesimpulan

**AMAN DENGAN CATATAN.**

Keamanan dan isolasi data akademik siswa terverifikasi: identitas selalu dari `auth()->user()->siswa`, tidak ada ID dari URL/input, otorisasi berlapis (`check.level:siswa` + `menu.access` + guard komponen), dan tidak ada kebocoran antar siswa. Sumber data (`jadwalkelas`+`jamhari`, `KonteksAkademikService`, penempatan aktif) konsisten antar Beranda/Profil/Jadwal/Histori dan tidak memakai tabel legacy.

Catatan: satu inkonsistensi tampilan pada halaman Profil (wali kelas tidak ditampilkan, tertukar label "Wali" dengan wali murid) perlu diperbaiki pada tahap implementasi berikutnya agar Beranda dan Profil konsisten. Tidak ada bug yang memengaruhi keamanan atau data.