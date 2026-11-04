# LAPORAN AUDIT PORTAL SISWA — TAHAP 2B.4

| Item | Keterangan |
|---|---|
| Proyek | SIAKAD Laravel 12 + Livewire 3 (`C:\project-reyhan\template web`) |
| Tanggal audit | 4 November 2026 |
| Sifat | Read-only. Tidak ada perubahan kode, migrasi, konfigurasi, atau data permanen. |
| Metode | Pembacaan kode statis + `php artisan route:list -v` + query DB read-only + render komponen Livewire & request HTTP nyata di dalam `DB::beginTransaction()` → `rollBack()` |
| Cakupan | Dashboard, profil, konteks akademik, jadwal (`jadwalkelas`+`jamhari`), absensi harian & per jam, navigasi/menu/route/middleware/otorisasi, responsif/UI, IDOR |
| Di luar cakupan | Implementasi fitur, perubahan data, pengujian browser/visual, performa beban, keamanan infrastruktur |

---

## Ringkasan Eksekutif

1. Portal siswa **sudah memiliki 3 halaman fungsional**: Beranda (`/berandasiswa`), Jadwal Pelajaran (`/jadwalsiswa`), dan Histori Absensi per jam (`/absensi-siswa`), ditambah Profil (`/setting`).
2. **Jadwal siswa sudah benar** memakai `jadwalkelas` + `jamhari` + `KonteksAkademikService`. Terbukti menampilkan 3 sesi, nama guru, dan pengelompokan per hari.
3. **Beranda bermasalah**: masih membaca `publikasijadwal` (sistem jadwal legacy). Pada pengujian, hari Senin memiliki 2 sesi `jadwalkelas` untuk kelas siswa, tetapi Beranda tetap menulis "Tidak ada jadwal hari ini." karena `publikasijadwal` kosong (0 baris).
4. **Riwayat absensi harian untuk siswa belum tersedia**, padahal data `absensi_harian` ada (3 baris; 2 di antaranya milik siswa uji). Belum ada service/komponen sisi siswa yang membacanya.
5. **Histori absensi per jam tersedia dan aman dari IDOR**, tetapi saat audit `absensi_perjam` berisi 0 baris sehingga halaman selalu menampilkan empty state.
6. **Profil siswa praktis belum ada**: `/setting` hanya menampilkan akun (nama/email/password). NISN, NIS, kelas, jurusan, alamat, kontak, dan data orang tua tidak ditampilkan.
7. **Menu siswa tidak dikelola sistem menu**: `/jadwalsiswa` dan `/absensi-siswa` di-hardcode di sidebar dan tidak terdaftar di `appmenunew`/`usermenu`, sehingga admin tidak dapat mengatur atau mencabutnya.
8. **`/berandasiswa` kehilangan `check.level:siswa`** karena route dinamis dari loop `appmenunew` menimpa route eksplisit. Kompensasi: komponen tetap `abort_unless(level==='siswa', 403)` (terbukti: superadmin → 403).
9. **Tidak ditemukan IDOR/kebocoran data antar siswa** pada ketiga komponen: identitas selalu diambil dari `auth()`, parameter URL (`?siswa=`, `?kelas=`, `?siswa_idsiswa=`) diabaikan, dan tidak ada public property identitas.
10. **Pengujian browser/visual belum dilakukan** (lihat Lampiran B). Kesimpulan responsif hanya berdasarkan struktur view.

---

## 1. Kondisi Fitur Saat Ini

### 1.1 Peta halaman siswa dan kontrol aksesnya

| Halaman | Komponen / Handler | Route name | Middleware terpasang (aktual) | Status |
|---|---|---|---|---|
| `/berandasiswa` | `BerandasiswaComponent` | `berandasiswa` | `web, Authenticate, CekAkunAktif, ForcePasswordChange, MenuAccessMiddleware` | Tersedia, **bermasalah** (lihat M-01) |
| `/jadwalsiswa` | `JadwalSiswaComponent` | `jadwalsiswa` | `web, Authenticate, CekAkunAktif, ForcePasswordChange, CheckUserLevel:siswa` | Tersedia |
| `/absensi-siswa` | `HistoriAbsensiSiswaComponent` | `absensi-siswa` | `web, Authenticate, CekAkunAktif, ForcePasswordChange, CheckUserLevel:siswa` | Tersedia (data kosong) |
| `/setting` | `AuthController@index` | `setting` | `web, Authenticate, CekAkunAktif, ForcePasswordChange` | Tersedia (hanya akun) |
| `/absensi-harian` | `AbsensiHarianComponent` | `absensi-harian` | `menu.access` (guru/superadmin) | Bukan untuk siswa (404) |
| `/jadwalkelas`, `/kelas`, `/siswa`, dst. | komponen admin | menu dinamis | `menu.access` | Bukan untuk siswa (404) |

Catatan penting: `routes/web.php` mendaftarkan `berandasiswa`, `jadwalsiswa`, dan `absensi-siswa` di dalam grup `check.level:siswa` (baris 97–101). Namun `berandasiswa` **juga** dihasilkan oleh loop route dinamis (baris 122–129) karena tidak ada di `$explicitUrls` (baris 54). Route dinamis menang, sehingga `/berandasiswa` hanya memakai `menu.access`.
Bukti: `php artisan route:list --path=berandasiswa -v` tidak menampilkan `CheckUserLevel`, dan `superadmin` (yang memiliki menu `berandasiswa` di `usermenu`) mendapat **403 dari komponen**, bukan 404 dari middleware level.

### 1.2 Status per area

| No | Area | Status | Ringkasan bukti |
|---|---|---|---|
| 1 | Dashboard siswa | **Bermasalah** | Jadwal hari ini dari `publikasijadwal` (0 baris) → selalu kosong; placeholder "Segera tersedia" |
| 2 | Profil & identitas siswa | **Belum tersedia** | `/setting` hanya form akun; tidak ada NISN/NIS/kelas/alamat/ortu/foto |
| 3 | Kelas, jurusan, tahun ajaran, semester | **Sebagian** | Kelas induk tampil di Beranda; jurusan/TA/semester tidak tampil; sumber semester tidak konsisten |
| 4 | Jadwal dari `jadwalkelas`+`jamhari` | **Tersedia** | 3 sesi, nama guru, kelompok hari, konteks TA/semester tampil |
| 5 | Riwayat absensi harian | **Belum tersedia** | Data `absensi_harian` ada, tetapi tidak ada halaman siswa |
| 6 | Riwayat absensi per jam | **Tersedia (data kosong)** | Filter tanggal/status, ringkasan, pagination, empty state; `absensi_perjam` = 0 |
| 7 | Navigasi/sidebar/route/menu | **Sebagian** | 2 menu hardcoded di sidebar, tidak ada di `appmenunew`/`usermenu` |
| 8 | Middleware & otorisasi role | **Tersedia dengan catatan** | Guru/superadmin ke halaman siswa → 404; komponen → 403; `check.level:siswa` hilang di Beranda |
| 9 | Responsif & konsistensi UI | **Sebagian** | Pola `container-fluid` + `table-responsive` ada; tabel Beranda tidak responsif; browser belum diuji |
| 10 | IDOR / kebocoran data siswa lain | **Aman (3 pengujian)** | Parameter URL diabaikan; identitas dari `auth()`; tidak ada property publik identitas |

### 1.3 Detail per area

#### a. Dashboard siswa (`BerandasiswaComponent`)

- Menampilkan: inisial avatar, nama, NISN, NIS, kelas induk + wali kelas, tahun masuk, badge status siswa, username, status ganti password, dan kartu "Jadwal hari ini".
- Sumber jadwal: `Publikasijadwal` (legacy) dengan `where('status','published')` lalu membaca `snapshot` (`app/Livewire/BerandasiswaComponent.php:56-67`). Model `Jadwal`, `Jampelajaran`, `Polajam`, `Publikasijadwal` termasuk sistem legacy menurut `AGENTS.md`.
- Semester dipilih dengan `orderByDesc('idsemester')` pada tahun ajaran aktif, **bukan** `Semester::aktif`/`KonteksAkademikService::ambil()` (`:39-42`).
- Kelas induk dicari dengan `whereHas('kelassiswa', ...)` **tanpa** filter `kelassiswa.status` (`:52`).
- Kartu "Absensi hari ini" dan "Tagihan SPP" masih placeholder "Segera tersedia" (`resources/views/livewire/berandasiswa-component.blade.php:119-126`).
- Hasil uji aktual: Senin 2 Nov 2026, kelas 1 memiliki 2 sesi (`Matematika` jam 1, `Agama` jam 2) → Beranda menulis **"Tidak ada jadwal hari ini."** dan tidak memuat kata `Matematika`/`Agama`.

#### b. Profil / identitas siswa (`/setting`)

- `AuthController@index` hanya mengirim `$userFind` (akun user). View `resources/views/setting.blade.php` berisi form nama/email/password dan catatan foto placeholder.
- Tidak ada NISN, NIS, NIK, tempat/tanggal lahir, agama, alamat, telepon, email siswa, data ayah/ibu/wali, asal sekolah, maupun foto.
- Model `Siswa` sudah memiliki seluruh field tersebut (`app/Models/Siswa.php:16-41`), jadi hambatannya ada di UI, bukan skema.
- Kelengkapan data aktual: siswa 1 kosong pada 10 field (alamat, telepon, email, ortu, foto); siswa 2 kosong pada 13 field (termasuk NIK, tempat lahir, asal sekolah, foto).

#### c. Konteks akademik (kelas/jurusan/tahun ajaran/semester)

- `KonteksAkademikService::ambil()` membaca `Tahunajaran::aktif()` + `Semester where aktif = true`, lalu divalidasi oleh `KonteksAkademik::valid()`.
- Hanya `JadwalSiswaComponent` yang memakai service ini. Beranda memakai query sendiri; `HistoriAbsensiSiswaComponent` tidak menampilkan konteks sama sekali.
- Kelas memiliki relasi `jurusan()`, tetapi tidak ada halaman siswa yang menampilkannya.
- Data aktual: tahun ajaran `2026/2027` aktif, semester `Ganjil` aktif (1 baris). Dengan kondisi ini perbedaan sumber semester belum terlihat, tetapi berisiko saat ada semester kedua.

#### d. Jadwal pelajaran (`JadwalSiswaComponent`)

- Query: `JadwalKelas` difilter `semester_idsemester` = semester aktif, `whereHas('jamhari', kelas_id ∈ kelas siswa aktif pada tahun ajaran aktif)` (`app/Livewire/JadwalSiswaComponent.php:38-51`).
- Penempatan difilter `kelassiswa.status = 'aktif'` dan tahun ajaran aktif — lebih ketat daripada Beranda.
- Tampilan: dikelompokkan per hari, kolom waktu (mulai–selesai), kelas, mapel, guru; `table-responsive`; empty state "Belum ada jadwal" / "Belum memiliki kelas aktif" / "Konteks akademik belum valid".
- Kekurangan: tidak ada penanda "hari ini"/jam berlangsung, tidak ada navigasi pekan, tidak ada cetak/unduh, tidak ada tautan dari Beranda.
- Hasil uji: siswa 2 (kelas 1) melihat Matematika, Agama, Bahasa Indonesia + guru; siswa 1 (kelas 2, `jamhari` = 0) melihat empty state tanpa kebocoran jadwal kelas lain.

#### e. Riwayat absensi per jam (`HistoriAbsensiSiswaComponent`)

- Hanya membaca `absensi_perjam` yang di-join ke snapshot `jadwal_pertemuan`, `kelas`, `mapel`, `guru`; filter `siswa_idsiswa` selalu dari akun login (`:124-144`).
- Fitur: filter tanggal dari/sampai + status, ringkasan (Total/Hadir/Sakit/Izin/Alpha) dihitung dari seluruh hasil filter (bukan per halaman), pagination 15, empty state, validasi format tanggal.
- Bukan fitur: rekap persentase, ekspor, tampilan kalender, ringkasan per mapel, absensi harian.
- Hasil uji: 0 baris data → "Belum ada catatan absensi"; siswa hanya melihat namanya sendiri.

#### f. Riwayat absensi harian

- Tabel `absensi_harian` menyimpan `tahunajaran_idtahunajaran`, `semester_idsemester`, `kelas_idkelas`, `tanggal`, `siswa_idsiswa`, `status`, `catatan`, audit `diinput_oleh`/`diubah_oleh`.
- `AbsensiHarianService` + `AbsensiHarianComponent` hanya untuk `superadmin`/`guru` (`abort_unless` di `mount`). Tidak ada pemakaian sisi siswa.
- Model menyediakan `scopeForSiswa`, `scopeForSemester`, `scopeBetweenTanggal` — bahan siap pakai untuk fitur baru.
- Data aktual: 3 baris (siswa 2: 2 baris tanggal 2–3 Nov 2026 status `sakit`; siswa 1: 1 baris).

#### g. Navigasi, route, menu, dan otorisasi

- Sidebar siswa di-hardcode (`resources/views/assets/sidebar.blade.php:109-120`): "Jadwal Pelajaran" → `/jadwalsiswa`, "Histori Absensi" → `/absensi-siswa`.
- `appmenunew` tidak memiliki entri `jadwalsiswa`/`absensi-siswa`, dan `usermenu` siswa hanya berisi `berandasiswa` + `setting`. Artinya: menu tidak dapat diatur lewat fitur Kelola Menu/Paket Menu, dan tidak ada jejak otorisasi menu untuk kedua halaman.
- `MenuAccessMiddleware` mencocokkan **nama route** dengan `usermenu` → `appmenunew.url`; jika tidak ada → `abort(404)`.
- `CheckUserLevel` → `abort(404)` (bukan 403), sehingga pengguna sah yang salah peran melihat "Halaman Tidak Ditemukan".
- `CekAkunAktif` memakai `LoginBlockService`: siswa harus punya data `siswa.status = aktif`; guru harus `guru.status = aktif`.
- `ForcePasswordChange`: seluruh route siswa dialihkan ke `/setting` selama `wajib_ganti_password = 1`.

### 1.4 Data aktual saat audit

| Data | Jumlah / isi |
|---|---|
| User | 6 (superadmin 1, guru 3, siswa 2) |
| Siswa | 2, keduanya `aktif`, keduanya terhubung ke user |
| Penempatan (`kelassiswa`) | siswa 2 → kelas 1 `X MIPA 1`; siswa 1 → kelas 2 `XI MIPA 1` (keduanya `aktif`) |
| Kelas | 3 (`X MIPA 1`, `XI MIPA 1`, `X KOMPUTER 1`), semua `jenis = induk`, TA 1 |
| Tahun ajaran / semester | `2026/2027` aktif; `Ganjil` aktif |
| `jamhari` | 8 baris, seluruhnya untuk kelas 1 |
| `jadwalkelas` | 3 baris (kelas 1: Matematika, Bahasa Indonesia, Agama); kelas 2 = 0 |
| `jadwal_pertemuan` | 3 baris |
| `absensi_perjam` | 0 baris |
| `absensi_harian` | 3 baris |
| `publikasijadwal` | 0 baris |
| `usermenu` siswa | `berandasiswa`, `setting` |
| `wajib_ganti_password` | user 4 (Yuji) = 1; user 3 dan 6 (guru) = 1 |

---

## 2. Daftar Masalah dan Risiko

Tingkat: **T**inggi / **S**edang / **R**endah.

| ID | Tingkat | Masalah | Bukti | Dampak / risiko |
|---|---|---|---|---|
| M-01 | **T** | Beranda membaca `publikasijadwal` legacy, bukan `jadwalkelas`+`jamhari` | `BerandasiswaComponent.php:56-67`; uji B6/B7: 2 sesi Senin ada, Beranda kosong | Siswa melihat informasi jadwal yang salah; kepercayaan pada portal turun; duplikasi sumber kebenaran |
| M-02 | **T** | Riwayat absensi harian siswa belum ada | `absensi_harian` = 3 baris; tidak ada komponen siswa; `AbsensiHarianComponent.php:52` hanya guru/superadmin | Siswa tidak bisa memantau kehadiran harian; pertanyaan ke wali kelas meningkat |
| M-03 | **S** | Profil siswa tidak menampilkan identitas apa pun | `resources/views/setting.blade.php`; uji P3–P5 | Siswa tidak bisa memverifikasi NISN/kelas/ortu; data induk tidak pernah divalidasi pemiliknya |
| M-04 | **S** | Konteks akademik tidak konsisten dan tidak tampil | `BerandasiswaComponent.php:39-42` (`orderByDesc`) vs `KonteksAkademikService::ambil()`; uji B10/B11 | Risiko semester salah saat ada 2 semester; siswa tidak tahu TA/semester/jurusan yang berlaku |
| M-05 | **S** | Kelas induk di Beranda tidak memfilter `kelassiswa.status` | `BerandasiswaComponent.php:52` | Penempatan lama (pindah/keluar) dapat tampil sebagai kelas aktif |
| M-06 | **S** | Menu jadwal/absensi siswa hanya hardcoded, tidak ada di `appmenunew`/`usermenu` | `sidebar.blade.php:109-120`; uji M6/M7 | Admin tidak bisa mengatur/mencabut; tidak ada otorisasi menu; sidebar mudah tidak sinkron |
| M-07 | **S** | `/berandasiswa` kehilangan `check.level:siswa` karena route dinamis menimpa yang eksplisit | `routes/web.php:54,97-101,122-129`; `route:list -v`; uji R10 (superadmin → 403 dari komponen) | Pertahanan berlapis hilang; jika guard komponen dilepas, halaman terbuka untuk peran lain |
| M-08 | **R** | `CheckUserLevel` memakai 404 untuk salah peran | `CheckUserLevel.php:21-23`; uji R6–R8, R11 | UX membingungkan; sulit membedakan "tidak ada" vs "tidak berhak"; monitoring akses tidak akurat |
| M-09 | **R** | Siswa dengan `wajib_ganti_password` tidak bisa membuka halaman apa pun selain `/setting` | `ForcePasswordChange.php:16-32`; uji R12–R15 (302 ke `/setting`) | Siswa baru bingung; perlu pesan/penjelasan yang jelas di `/setting` |
| M-10 | **R** | Placeholder "Absensi hari ini" & "Tagihan SPP" = "Segera tersedia" | `berandasiswa-component.blade.php:119-126`; uji B8/B9 | Ekspektasi palsu; terkesan belum jadi |
| M-11 | **R** | Tabel Beranda tidak dibungkus `table-responsive` | `berandasiswa-component.blade.php:41,75,94`; uji U4 | Potensi overflow horizontal di layar kecil |
| M-12 | **R** | Kelengkapan data identitas siswa rendah | Query profil: siswa 1 kosong 10 field, siswa 2 kosong 13 field | Fitur profil tidak akan lengkap tanpa perbaikan data induk |
| M-13 | **R** | `absensi_perjam` kosong sehingga halaman histori selalu kosong | Uji H3 | Siswa menyimpulkan fitur rusak, padahal belum ada pengisian oleh guru |
| M-14 | **R** | Belum ada rekap persentase kehadiran & ekspor | `HistoriAbsensiSiswaComponent` (tidak ada method rekap/ekspor) | Siswa/orang tua tidak punya ringkasan periodik |
| M-15 | **R** | `$user->siswa()->first()` tanpa filter status/konsistensi di Beranda & Histori | `BerandasiswaComponent.php:20-25`; `HistoriAbsensiSiswaComponent.php:68` | Jika satu user terhubung ke >1 data siswa, data yang tampil bisa tidak deterministik |
| M-16 | **R** | Jadwal tidak menandai hari ini/jam berlangsung, tanpa cetak/unduh | `jadwal-siswa-component.blade.php` | Siswa harus mencari manual; kurang berguna di HP |
| M-17 | **R** | `php artisan test` gagal sejak sebelum audit | `Tests\Feature\ExampleTest` → `GET /` 404 | Menghambat CI dan verifikasi otomatis ke depan (di luar scope perubahan) |

### Risiko keamanan (ringkasan)

| Aspek | Hasil |
|---|---|
| IDOR via parameter URL | **Aman.** `/jadwalsiswa?kelas=2&siswa=1`, `/absensi-siswa?siswa_idsiswa=1`, `/berandasiswa?siswa=1` tetap menampilkan data pemilik akun |
| IDOR via property Livewire | **Aman.** `JadwalSiswaComponent` tidak punya public property; `HistoriAbsensiSiswaComponent` hanya punya filter tanggal/status/perPage |
| Kebocoran nama/data siswa lain | **Tidak ditemukan** pada render Beranda/Jadwal/Histori |
| Akses guru/superadmin ke halaman siswa | **Ditolak** (404 middleware; 403 komponen) |
| Akses siswa ke halaman guru/admin | **Ditolak** (`/absensi-harian` → 404) |
| Otorisasi menu | **Berfungsi**, tetapi 2 halaman siswa tidak terdaftar di menu (M-06) |

---

## 3. Fitur yang Perlu Ditambahkan

| ID | Fitur | Prioritas | Nilai bagi siswa | Bahan yang sudah ada |
|---|---|---|---|---|
| F-01 | **Beranda 2.0**: jadwal hari ini dari `jadwalkelas`+`jamhari`, konteks TA/semester/jurusan/wali, ringkasan kehadiran, absensi hari ini | Tinggi | Informasi inti harian | `JadwalSiswaComponent`, `KonteksAkademikService`, `AbsensiHarian`, `AbsensiPerjam` |
| F-02 | **Profil Siswa**: identitas read-only (NISN/NIS/NIK/TTL/agama/alamat/kontak/ortu/foto) + bagian akun | Tinggi | Verifikasi data & kontak darurat | `Siswa` (semua field), `setting.blade.php` |
| F-03 | **Riwayat Absensi Harian**: daftar + filter bulan/semester + ringkasan status | Tinggi | Transparansi kehadiran harian | `absensi_harian`, `AbsensiHarian` scope |
| F-04 | **Halaman Absensi bertab** ("Harian" & "Per Jam") atau dua halaman terpisah dengan navigasi jelas | Sedang | Satu pintu masuk absensi | `HistoriAbsensiSiswaComponent` |
| F-05 | **Rekap & persentase kehadiran** per mapel (per jam) dan per bulan (harian) | Sedang | Evaluasi mandiri, kesiapan rapor | Data absensi + agregasi query |
| F-06 | **Peningkatan Jadwal**: penanda hari ini/jam berlangsung, navigasi pekan, cetak/unduh PDF | Sedang | Kegunaan harian di HP | `JadwalKelas`, `Jamhari`, `Hari` |
| F-07 | **Menu resmi siswa** di `appmenunew` + `usermenu` + `role` via seeder idempoten | Tinggi | Admin dapat mengatur akses | Pola `*MenuSeeder`, `PaketMenuService` |
| F-08 | **Pengumuman/informasi ringan** (jadwal berubah, alpha hari ini) | Rendah | Komunikasi proaktif | Belum ada tabel; perlu desain baru |
| F-09 | **Ekspor rekap absensi** (CSV/PDF) | Rendah | Bukti untuk orang tua | `barryvdh/laravel-dompdf` sudah dipakai proyek |
| F-10 | **Komponen UI bersama** (kartu, badge status, empty state, filter) | Sedang | Konsistensi lintas halaman | Pola `detail-card` sudah muncul di beberapa view |

---

## 4. Rekomendasi Desain dan Alur Pengguna

### 4.1 Struktur navigasi siswa (usulan)

```
Beranda
Jadwal Pelajaran
Absensi
  ├─ Harian
  └─ Per Jam
Profil Saya
```

- "Beranda", "Jadwal Pelajaran", "Absensi" harus terdaftar sebagai menu (F-07) agar sidebar tidak lagi hardcoded.
- "Absensi" sebaiknya satu entri menu dengan halaman bertab, bukan dua entri terpisah, agar sidebar tetap pendek.
- Profil tetap di `/setting` (sudah menjadi tujuan `ForcePasswordChange` dan `BerandaUrl`), tetapi isinya diperluas.

### 4.2 Tata letak Beranda (usulan)

1. **Header identitas**: avatar/foto, nama, NISN · NIS, kelas + jurusan, TA + semester, badge status.
2. **Kartu "Jadwal hari ini"**: sumber `jadwalkelas`+`jamhari`; tandai sesi yang sedang berlangsung; tautan "Lihat semua jadwal".
3. **Kartu "Kehadiran hari ini"**: status absensi harian hari ini (jika sudah diisi) + ringkasan per jam (mis. "3 dari 4 jam tercatat").
4. **Kartu "Ringkasan bulan ini"**: Hadir/Sakit/Izin/Alpha + persentase.
5. **Kartu pengumuman** (opsional, fase lanjut) atau informasi akademik ringkas.

Aturan: bila data belum ada, tampilkan empty state informatif ("Belum diisi oleh wali kelas"), bukan "Segera tersedia".

### 4.3 Halaman Profil (usulan)

- **Bagian Akun**: nama akun, username, email, ubah password (tetap seperti sekarang).
- **Bagian Identitas Siswa** (read-only): NISN, NIS, NIK, jenis kelamin, tempat/tanggal lahir, agama, asal sekolah, tahun masuk.
- **Bagian Kontak & Orang Tua** (read-only): alamat, telepon siswa, email siswa, nama/telepon ayah, ibu, wali.
- **Bagian Foto**: tampilkan `siswa.foto` bila ada; jika kosong tampilkan inisial (pola yang sudah dipakai Beranda). Hindari placeholder eksternal seperti saat ini.
- Perubahan data: fase pertama **read-only** disertai teks "Hubungi admin sekolah untuk perubahan data". Fitur pengajuan perubahan data (request-change) dipertimbangkan setelah ada alur persetujuan.

### 4.4 Halaman Absensi (usulan)

- Tab **Harian**: tabel tanggal, hari, kelas, status, catatan; filter bulan/semester; ringkasan + persentase.
- Tab **Per Jam**: pertahankan komponen yang ada (filter tanggal/status, ringkasan, pagination).
- Urutan default: bulan berjalan; sertakan tombol "Bulan ini"/"Semester ini".
- Status memakai badge dan warna yang sama di kedua tab (Hadir hijau, Sakit kuning, Izin biru, Alpha merah) — sudah konsisten di komponen per jam; jadikan partial bersama.

### 4.5 Prinsip teknis wajib

1. **Satu sumber konteks akademik**: semua halaman siswa memakai `KonteksAkademikService::ambil()`; dilarang query semester sendiri.
2. **Satu sumber jadwal**: `jadwalkelas` + `jamhari`; `publikasijadwal`/`jadwal`/`polajam` tidak dipakai untuk siswa.
3. **Identitas dari sesi**: `siswa_idsiswa` selalu dari `auth()->user()->siswa()`, tidak pernah dari request.
4. **Guard berlapis**: route-level `check.level:siswa` **dan** guard komponen. Perbaiki `$explicitUrls` agar route dinamis tidak menimpa.
5. **Filter penempatan**: selalu `kelassiswa.status = 'aktif'` + tahun ajaran aktif.
6. **Menu terdaftar**: setiap halaman siswa punya entri `appmenunew` + `usermenu` melalui seeder idempoten (pola `BerandaMenuSeeder`/`AbsensiHarianMenuSeeder`).
7. **Read-only untuk siswa**: tidak ada aksi tulis di portal siswa tahap ini.
8. **Query efisien**: `select` kolom terbatas, eager loading relasi yang ditampilkan, hindari N+1 di daftar absensi.

### 4.6 Konsistensi & responsif

- Gunakan `container-fluid px-3 px-lg-4` dan grid `col-12 col-md-6 col-xl-4` seperti halaman siswa yang sudah ada.
- Semua tabel dibungkus `.table-responsive`; pertimbangkan `text-nowrap` hanya pada kolom pendek.
- Judul halaman konsisten (`h1.h4/h3 fw-bold`), badge peran "Siswa" di kanan atas seperti halaman jadwal/histori.
- Empty state selalu berisi penjelasan + langkah berikutnya, bukan hanya "tidak ada data".
- Target minimum lebar 360 px; tombol aksi minimal 44 px tinggi.

### 4.7 Aksesibilitas

- Hierarki heading benar (satu `h1` per halaman, `h2` untuk kartu).
- Ikon dekoratif memakai `aria-hidden="true"` (sudah dipakai di beberapa tempat).
- Badge status tidak boleh mengandalkan warna saja — sertakan teks (sudah dilakukan).
- Form filter memiliki `<label for>` (sudah ada di halaman histori).

---

## 5. Urutan Implementasi Bertahap

> Semua tahap bersifat **aditif** dan tidak memerlukan migrasi baru. Data yang dibutuhkan sudah ada (`absensi_harian`, `absensi_perjam`, `jadwalkelas`, `jamhari`, field `siswa`).

### Tahap 1 — Fondasi Portal Siswa (perbaikan + menu resmi)

**Tujuan**: menghilangkan informasi salah dan menyatukan sumber data.

Cakupan:
1. Beranda memakai `jadwalkelas` + `jamhari` (melalui komponen/service yang sama dengan `/jadwalsiswa`).
2. Tampilkan TA + semester + jurusan + wali kelas.
3. Gunakan `KonteksAkademikService::ambil()`; filter `kelassiswa.status = 'aktif'`.
4. Tabel Beranda responsif; ganti placeholder menjadi empty state informatif.
5. Tambah `berandasiswa` ke `$explicitUrls`, dan daftarkan menu `jadwalsiswa` + `absensi-siswa` (seeder + `usermenu` + `role`), lalu ubah sidebar memakai menu dinamis.
6. Samakan pesan saat `wajib_ganti_password` (penjelasan di `/setting`).

Tidak termasuk: profil identitas, absensi harian, rekap, ekspor.

Definition of Done: Beranda menampilkan jadwal yang sama dengan `/jadwalsiswa`; menu dapat dikelola admin; tidak ada akses lintas peran; semua uji Tahap 1 lulus.

### Tahap 2 — Profil Siswa

**Tujuan**: siswa dapat memverifikasi identitas dan kontak.

Cakupan: halaman profil terpadu (akun + identitas + kontak/ortu + foto/avatar), penanganan field kosong ("Belum diisi"), tetap read-only untuk identitas.

Definition of Done: seluruh field `Siswa` yang relevan tampil; data kosong tidak menyebabkan error; tidak ada kebocoran data siswa lain.

### Tahap 3 — Riwayat Absensi Harian + Penyatuan Halaman Absensi

**Tujuan**: melengkapi transparansi kehadiran harian.

Cakupan: komponen/service read-only untuk `absensi_harian` (filter bulan/semester, ringkasan, pagination), halaman absensi bertab (Harian | Per Jam), empty state jelas saat guru belum mengisi.

Definition of Done: siswa melihat absensi harian miliknya; konsisten dengan data guru; tab per jam tetap berfungsi.

### Tahap 4 — Rekap & Persentase + Ekspor

**Tujuan**: memberi ringkasan periodik yang bisa dipakai siswa/orang tua.

Cakupan: rekap persentase per bulan dan per mapel, filter semester, ekspor CSV (dan PDF bila diperlukan).

Definition of Done: angka rekap cocok dengan data mentah pada skenario uji; ekspor hanya berisi data pemilik akun.

### Tahap 5 — Penyempurnaan UX

**Tujuan**: pengalaman harian di perangkat mobile.

Cakupan: penanda hari ini/jam berlangsung, navigasi pekan, cetak/unduh jadwal, pengumuman ringan (opsional), audit aksesibilitas.

Definition of Done: hasil uji manual browser pada 360 px, 768 px, dan 1280 px dinyatakan lulus.

---

## 6. Daftar Pengujian Setiap Tahap

Legenda jenis: **U** = unit, **F** = feature/Livewire, **H** = HTTP/middleware, **M** = manual browser.

### Tahap 1

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 1.1 | F | Render Beranda siswa yang punya jadwal pada hari uji | Sesi jadwal muncul, sama dengan `/jadwalsiswa` |
| 1.2 | F | Render Beranda siswa tanpa jadwal pada hari uji | Empty state informatif (bukan "Segera tersedia") |
| 1.3 | F | `Carbon::setTestNow` Senin/Selasa/Rabu | Jadwal mengikuti hari yang diuji |
| 1.4 | F | Siswa dengan 2 semester pada TA aktif | Memakai semester `aktif`, bukan `idsemester` terbesar |
| 1.5 | F | Penempatan `kelassiswa.status` = `pindah` | Tidak dianggap kelas aktif |
| 1.6 | H | Guru & superadmin `GET /berandasiswa` | 404 (middleware level), bukan 403 komponen |
| 1.7 | H | Siswa `GET /berandasiswa` | 200 |
| 1.8 | H | Siswa `GET /jadwalsiswa`, `/absensi-siswa` setelah seeder menu | 200 dan muncul di sidebar dinamis |
| 1.9 | H | Cabut menu di `usermenu` | 404 (menu.access) |
| 1.10 | M | Sidebar siswa pada 360 px | Tidak terpotong; menu dapat diakses |
| 1.11 | M | Beranda pada 360 px | Tidak ada scroll horizontal |

### Tahap 2

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 2.1 | F | Profil siswa dengan data lengkap | Semua section terisi |
| 2.2 | F | Profil siswa dengan field kosong (data aktual) | Teks "Belum diisi", bukan error |
| 2.3 | F | Profil siswa dengan `foto` kosong | Avatar inisial tampil |
| 2.4 | H | `GET /setting?siswa=1` oleh siswa 2 | Tetap data sendiri |
| 2.5 | F | User tanpa data siswa | Pesan "Akun belum terhubung ke data siswa" |
| 2.6 | M | Profil pada 360 px | Kartu bertumpuk rapi, label tidak terpotong |

### Tahap 3

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 3.1 | F | Absensi harian siswa 2 (2 baris: sakit) | 2 baris tampil, ringkasan Sakit 2 |
| 3.2 | F | Filter bulan tanpa data | Empty state + tombol reset |
| 3.3 | F | Absensi harian 0 baris | Empty state "Belum diisi" |
| 3.4 | F | Parameter `?siswa_idsiswa=` diabaikan | Data pemilik akun |
| 3.5 | F | Tab Harian ↔ Per Jam | State filter tidak saling merusak |
| 3.6 | H | Guru/superadmin ke halaman absensi siswa | 404/403 |
| 3.7 | M | Tab pada 360 px | Tab dapat di-scroll/di-tap dengan benar |

### Tahap 4

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 4.1 | U | Perhitungan persentase (hadir/total) | Sesuai rumus & pembulatan yang disepakati |
| 4.2 | U | Rekap per mapel dari `absensi_perjam` | Jumlah per status benar |
| 4.3 | F | Rekap lintas semester | Hanya semester yang dipilih |
| 4.4 | F | Ekspor CSV | Header & isi sesuai, hanya data sendiri |
| 4.5 | F | Ekspor saat data kosong | File tetap valid / pesan jelas |
| 4.6 | M | Unduh di mobile | File terunduh, terbuka di aplikasi spreadsheet |

### Tahap 5

| No | Jenis | Skenario | Ekspektasi |
|---|---|---|---|
| 5.1 | F | Penanda "hari ini" & jam berlangsung | Benar pada beberapa jam uji |
| 5.2 | F | Navigasi pekan | Berpindah pekan tanpa error |
| 5.3 | F | Cetak/unduh jadwal | PDF rapi, hanya jadwal sendiri |
| 5.4 | M | Uji visual 360/768/1280 px | Semua halaman konsisten |
| 5.5 | M | Uji keyboard & screen reader dasar | Fokus terlihat, label terbaca |
| 5.6 | U | Regresi uji Tahap 1–4 | Semua tetap lulus |

---

## 7. Dependensi Antarfitur

```
F-07 (menu & route resmi) ──┬──> F-01 (Beranda 2.0) ──> F-06 (jadwal lanjutan)
                             ├──> F-02 (Profil)
                             └──> F-03 (Absensi harian) ──> F-04 (tab absensi)
                                                             └──> F-05 (rekap) ──> F-09 (ekspor)
F-01 ── butuh: KonteksAkademikService, jadwalkelas+jamhari, filter kelassiswa aktif
F-02 ── butuh: kelengkapan data induk siswa (peran admin) + penyimpanan foto
F-03 ── butuh: pengisian absensi_harian oleh wali kelas/guru (proses non-teknis)
F-05 ── butuh: absensi_perjam terisi (proses guru) agar persentase per mapel bermakna
F-08 (pengumuman) ── independen, butuh desain tabel baru
```

| Fitur | Bergantung pada | Catatan |
|---|---|---|
| F-01 Beranda 2.0 | F-07, `KonteksAkademikService`, `jadwalkelas`/`jamhari` | Tidak perlu migrasi |
| F-02 Profil | Kelengkapan data `siswa`, penanganan foto | Perlu keputusan kebijakan edit |
| F-03 Absensi harian | Data `absensi_harian` terisi | Perlu koordinasi dengan wali kelas |
| F-04 Tab absensi | F-03 + komponen per jam yang sudah ada | Refactor ringan |
| F-05 Rekap | F-03, `absensi_perjam` terisi | Butuh definisi rumus persentase |
| F-06 Jadwal lanjutan | F-01 | Bisa dikerjakan paralel setelah F-01 |
| F-07 Menu resmi | Seeder + `usermenu` + `role` | Prasyarat F-01 agar tidak hardcoded |
| F-09 Ekspor | F-05 | Dompdf sudah tersedia |
| F-10 Komponen UI | Tidak ada | Disarankan lebih awal agar tidak refactor berulang |

**Ketergantungan non-teknis yang perlu diputuskan lebih dulu**

1. Apakah siswa boleh mengubah data kontak sendiri, atau semua read-only (usulan: read-only).
2. Apakah "Absensi" menjadi satu halaman bertab atau dua menu terpisah (usulan: satu halaman bertab).
3. Definisi persentase kehadiran (per jam, per hari, atau keduanya).
4. Kebijakan foto siswa (unggah mandiri vs admin).

---

## 8. Rekomendasi Tahap Implementasi Pertama

**Rekomendasi: kerjakan Tahap 1 — Fondasi Portal Siswa.**

### Alasan

1. M-01 adalah satu-satunya temuan yang membuat siswa melihat **informasi salah** hari ini. Memperbaiki sumber jadwal adalah perbaikan bernilai tertinggi dengan risiko terendah.
2. F-01 + F-07 saling menguatkan: memperbaiki Beranda sekaligus memindahkan menu siswa ke sistem menu resmi mencegah utang teknis bertambah.
3. Tahap 1 tidak memerlukan migrasi maupun data baru; semua sumber sudah tersedia (`jadwalkelas` 3 baris, `jamhari` 8 baris, konteks akademik valid).
4. Tahap 1 menjadi prasyarat teknis bagi Tahap 2–5 (menu, guard, konteks akademik tunggal).

### Cakupan yang disarankan (dan yang tidak)

**Dikerjakan**
- `app/Livewire/BerandasiswaComponent.php`: ganti sumber jadwal ke `JadwalKelas`+`Jamhari`, pakai `KonteksAkademikService::ambil()`, filter `kelassiswa.status = 'aktif'`, tampilkan TA/semester/jurusan/wali.
- `resources/views/livewire/berandasiswa-component.blade.php`: tabel responsif, empty state informatif, hapus/geser placeholder yang menyesatkan.
- `routes/web.php`: tambahkan `berandasiswa` ke `$explicitUrls` agar `check.level:siswa` berlaku.
- Seeder menu baru (pola `BerandaMenuSeeder`/`AbsensiHarianMenuSeeder`) untuk `jadwalsiswa` dan `absensi-siswa` + `usermenu` + `role`.
- `resources/views/assets/sidebar.blade.php`: ganti blok hardcoded siswa dengan menu dinamis.
- `resources/views/setting.blade.php`: tambahkan penjelasan singkat saat `wajib_ganti_password`.

**Tidak dikerjakan pada tahap ini**
- Profil identitas lengkap (Tahap 2).
- Absensi harian & rekap (Tahap 3–4).
- Perubahan skema database.
- Perubahan pada sistem guru/admin.

### Kriteria penerimaan

1. Beranda menampilkan jadwal yang identik dengan `/jadwalsiswa` untuk hari yang sama.
2. Superadmin/guru tidak bisa membuka `/berandasiswa` (404 dari middleware level).
3. Menu "Jadwal Pelajaran" dan "Histori Absensi" muncul dari `usermenu`; mencabut `usermenu` membuat halaman 404.
4. Tidak ada penyebutan `publikasijadwal`/`jadwal` legacy di jalur siswa.
5. Seluruh pengujian Tahap 1 (6.1–6.11) lulus, termasuk uji manual 360 px.
6. `php vendor/bin/pint` bersih pada file yang diubah.

### Risiko tahap pertama dan mitigasi

| Risiko | Mitigasi |
|---|---|
| Jadwal kelas ganda (siswa punya >1 kelas aktif) | Tampilkan gabungan dengan label kelas, seperti `/jadwalsiswa` |
| Semester ganda pada satu TA | Gunakan `KonteksAkademikService::ambil()`; uji dengan 2 semester |
| Menu seeder dijalankan berulang | Gunakan `firstOrCreate` (idempoten) + cleanup seeder bila perlu |
| Sidebar berubah untuk peran lain | Batasi perubahan pada blok `level === 'siswa'`, uji semua peran |
| Data jadwal kosong pada hari tertentu | Empty state jelas, bukan "Segera tersedia" |

### Urutan kerja yang disarankan

1. Tambah menu via seeder + jalankan (tanpa menghapus menu lama).
2. Perbaiki `$explicitUrls` dan verifikasi `route:list -v`.
3. Refactor Beranda (komponen + view) dan verifikasi dengan data aktual.
4. Ganti sidebar hardcoded → menu dinamis.
5. Jalankan seluruh pengujian Tahap 1 (otomatis + manual browser).
6. Format dengan Pint, lalu perbarui dokumentasi singkat bila perlu.

---

## Lampiran A — Metode Audit dan Bukti

**Perintah yang dijalankan**

- `php artisan route:list --path=siswa|beranda|jadwalsiswa|absensi-siswa|setting -v`
- Skrip sementara di `storage/app/tmp-uji/` (dihapus setelah audit):
  - Query data aktual (user, siswa, kelas, penempatan, jamhari, jadwalkelas, absensi, publikasijadwal, menu).
  - Render komponen Livewire (`new Component` → `mount()` → `render()->render()`) dengan `Auth::loginUsingId()` dan `Carbon::setTestNow()`.
  - Request HTTP nyata per proses (`Illuminate\Contracts\Http\Kernel::handle(Request::create(...))`) untuk menguji middleware/otorisasi tanpa kontaminasi sesi antar-request.
  - Semua uji berjalan di dalam `DB::beginTransaction()` + `DB::rollBack()`; jumlah baris diverifikasi tidak berubah setelah rollback (`absensi_perjam=0, jadwal_pertemuan=3, absensi_harian=3, jadwalkelas=3, jamhari=8`).

**Ringkasan hasil uji aktual** (60+ butir, semuanya PASS sesuai kriteria yang ditetapkan):

| Kelompok | Hasil |
|---|---|
| Beranda (B1–B18) | Lulus; mencatat temuan jadwal kosong, konteks tidak tampil, placeholder |
| Jadwal siswa (J1–J13) | Lulus; 3 sesi tampil, pengelompokan hari benar, empty state benar |
| Histori per jam (H1–H9) | Lulus; empty state, IDOR-safe, filter tersedia |
| Route/middleware (R1–R15) | Lulus; matriks peran sesuai, redirect wajib ganti password benar |
| IDOR (I1–I3) | Lulus; parameter URL tidak berpengaruh |
| Sidebar/menu (M1–M7) | Lulus; 2 menu hardcoded, tidak terdaftar di `appmenunew`/`usermenu` |
| Profil (P1–P6) | Lulus; hanya akun, tanpa identitas siswa |
| Absensi harian (A1–A4) | Lulus; data ada, UI siswa tidak ada, scope model siap |
| UI/responsif statis (U1–U4) | Lulus; Beranda belum `table-responsive` |

**Catatan teknis**: pengujian beberapa request dalam satu proses PHP memunculkan `TypeError` pada `ForcePasswordChange` karena `redirect()` di-rebind oleh Livewire menjadi `Livewire\...\Redirector`. Ini **artefak harness** (satu proses banyak request), bukan bug produksi; pengujian ulang dengan satu proses per request menghasilkan 302 → `/setting` sesuai harapan. Temuan ini tidak dimasukkan sebagai masalah aplikasi, tetapi perlu diingat bila proyek kelak memakai Octane.

---

## Lampiran B — Keterbatasan dan Hal yang Belum Diuji

1. **Browser/visual: Belum diuji.** Tidak ada pengujian pada browser nyata; klaim responsif hanya berdasarkan struktur view (grid, `table-responsive`, meta viewport).
2. **Interaksi JavaScript/Livewire di browser: Belum diuji** (mis. pagination klik, filter tanggal, sidebar collapse di mobile).
3. **Data uji kecil** (2 siswa, 1 semester, 1 TA). Perilaku dengan banyak kelas, semester ganda, atau ribuan baris absensi belum diukur.
4. **Pengisian absensi oleh guru tidak diuji** (di luar cakupan); kosongnya `absensi_perjam` membatasi validasi tampilan data nyata.
5. **`php artisan test` tidak dapat dijadikan acuan** karena `Tests\Feature\ExampleTest` gagal `GET /` 404 sejak sebelum audit.
6. **Kebijakan produk belum diputuskan** (edit profil, definisi persentase, unggah foto) sehingga rekomendasi desain bersifat usulan.
7. **Tidak ada perubahan kode/database** yang dilakukan; laporan ini murni hasil pembacaan dan pengujian read-only.

---

## Penutup

Portal Siswa sudah memiliki fondasi yang benar pada **halaman jadwal** (sumber `jadwalkelas`+`jamhari`, konteks akademik, dan pembatasan IDOR yang baik), tetapi **Beranda belum memakai sumber yang sama**, **profil belum menampilkan identitas**, dan **absensi harian belum tersedia untuk siswa**. Langkah paling bernilai dan berisiko rendah adalah **Tahap 1: menyelaraskan Beranda dengan jadwal yang benar, menampilkan konteks akademik, dan memindahkan menu siswa ke sistem menu resmi**, sebelum menambah fitur profil dan absensi di tahap berikutnya.
