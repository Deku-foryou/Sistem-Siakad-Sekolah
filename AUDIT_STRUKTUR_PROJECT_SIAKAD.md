# AUDIT STRUKTUR PROJECT SIAKAD

## 1. Ringkasan Teknologi dan Arsitektur

- **Laravel Version**: 12.44.0
- **Livewire Version**: 3.7.x
- **PHP Version**: 8.2.10
- **Database**: MySQL
- **Auth/Permission**: Spatie Laravel Permission
- **Captcha**: MewsCaptcha
- **Architecture**: Laravel 12 + Livewire 3 dengan pola Livewire Components + Blade Views
- **Middleware Kustom**: `check.level`, `menu.access`, `force.password`, `cek.akun`, `auth.session`

## 2. Peta Direktori Utama

### app/Models (37 model)
- Akademik: Tahunajaran, Semester, Guru, Siswa, Mapel, Kelas, Kelassiswa, Polajam, Polajamhari, Jampelajaran, Pengampu, Jadwal, Publikasijadwal
- Auth/Security: User, Roles, Permissions, Model_has_roles, Role_has_permissions
- Master Data: Appmenucat, Appmenunew, Usermenu, Rolecat, Roles, Role_has_permissions

### app/Livewire (48 component)
- Dashboard: DashboardComponent
- Data Induk: GuruComponent, SiswaComponent, MapelComponent, KelasComponent, KelassiswaComponent, TahunajaranComponent, SemesterComponent
- Akademik: PengampuComponent, JadwalComponent, PolajamComponent, JampelajaranComponent
- Menu Management: UsersComponent, RolesComponent, PermissionsComponent, RolecatComponent, AppmenucatComponent, AppmenunewComponent, UsermenuComponent
- Pendukung: BerandaguruComponent, BerandasiswaComponent, BuktiPublikasiComponent, DokscatComponent, DocsComponent

### app/Services (30+ service)
- Core: KelasService, SiswaService, GuruService, MapelService, PolajamService, JampelajaranService, PengampuService, JadwalService, PenempatanService
- Import: ImportKelas, ImportSiswa, ImportGuru, ImportMapel, ImportPenempatan
- Bantuan: AkunService, LoginBlockService, PaketMenuService, JenisKelamin, Hari, BerandaUrl

### resources/views
- layouts: mainlayout.blade.php, docslayout.blade.php, pdf_layout.blade.php
- livewire: 48 komponen livewire + partials + vendor pagination
- assets: sidebar.blade.php, navbar.blade.php, style.blade.php, scripts.blade.php, docs_sidebar.blade.php

### routes/web.php
- 38 terdaftar route (plus api routes)
- Route groups: auth, check.level:superadmin, menu.access
- Dynamic route registration dari Appmenunew + explicit route untuk akademik features

### database/migrations (23 migrasi)
- Activity log, users fields, tahunajaran, semester, guru, siswa, mapel, kelas, kelassiswa
- polajam, polajamhari, jampelajaran, pengampu, jadwal, publikasijadwal
- Identitas lengkap siswa (nik, telepon, email, wali)
- Index/composite foreign key migrations

### database/seeders (15 seeder)
- DataIndukMenuSeeder: Guru, Siswa, Mapel, Kelas, Penempatan Siswa
- AkademikMenuSeeder: Tahun Ajaran, Semester
- JadwalMenuSeeder: Pola Jam, Jam Pelajaran, Pengampu, Jadwal
- PaketDefaultSeeder, IkonMenuSeeder, BerandaMenuSeeder
- PaketDefaultSeeder, RaksSeeder

## 3. Daftar Seluruh Menu dan Route Aktual

### Dashboard
- `/` -> login (guest)
- `/dashboard` -> DashboardComponent (superadmin cek level)
- `/welcome-docs` -> HomedocsComponent

### Data Induk
- `/guru` -> GuruComponent (superadmin)
- `/siswa` -> SiswaComponent (superadmin)
- `/mapel` -> MapelComponent (superadmin)
- `/kelas` -> KelasComponent (superadmin)
- `/kelassiswa` -> KelassiswaComponent (superadmin)
- `/tahunajaran` -> TahunajaranComponent (superadmin)
- `/semester` -> SemesterComponent (superadmin)

### Akademik
- `/polajam` -> PolajamComponent (superadmin)
- `/jampelajaran` -> JampelajaranComponent (superadmin)
- `/pengampu` -> PengampuComponent (superadmin)
- `/jadwal` -> JadwalComponent (superadmin)

### Menu Management (superadmin)
- `/rolecat` -> RolecatComponent
- `/role/{id}` -> RoleComponent
- `/appmenucat` -> AppmenucatComponent
- `/appmenunew` -> AppmenunewComponent
- `/users` -> UsersComponent (superadmin)
- `/usermenu/{id}` -> UsermenuComponent

### Non Akademik
- `/docs` -> DocsComponent
- `/docscat` -> DocscatComponent
- `/berandaguru` -> BerandaguruComponent (guru)
- `/berandasiswa` -> BerandasiswaComponent (siswa)
- `/setting` -> AuthController@index (auth session)
- `/profil/{id}` -> AuthController@update

### Route Name Mapping (dari web.php)
- `dashboard`, `welcome-docs`, `tahunajaran`, `semester`, `guru`, `siswa`, `mapel`, `kelas`, `kelassiswa`, `polajam`, `jampelajaran`, `pengampu`, `jadwal`, `bukti-publikasi/{id}`
- `import/template/{jenis}`, `import/gagal/{uuid}`
- `usermenu/{id}`

## 4. Daftar Seluruh Modul dan Tabel Database

### 1. Tahun Ajaran
- **Tabel**: `tahunajaran` (idtahunajaran, nama, aktif)
- **Relasi**: 1..* semester
- **CRUD**: store, update, delete (jika semester kosong), aktifkan
- **Middleware**: superadmin

### 2. Semester
- **Tabel**: `semester` (idsemester, tahunajaran_idtahunajaran, nama [Ganjil/Genap], aktif)
- **Relasi**: 1..* pengampu, 1..* publikasijadwal
- **FK**: tahunajaran_idtahunajaran -> tahunajaran.idtahunajaran (restrict)
- **CRUD**: store, update, delete, aktifkan

### 3. Guru
- **Tabel**: `guru` (idguru, user_iduser, nip, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, telepon, alamat, foto, status)
- **Relasi**: belongsTo user, hasMany kelas (hanya induk), hasManyThrough kelassiswa
- **FK**: user_iduser -> users.id (restrict)
- **CRUD**: store, update, delete (jika kelas kosong)

### 4. Siswa
- **Tabel**: `siswa` (idsiswa, user_iduser, nisn, nis, nik, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, telepon_siswa, email, nama_ayah, telepon_ayah, nama_ibu, telepon_ibu, nama_wali, telepon_wali, telepon_ortu, tahun_masuk, asal_sekolah, foto, status)
- **Relasi**: belongsTo user, hasMany kelassiswa
- **FK**: user_iduser -> users.id (restrict)
- **CRUD**: store, update, delete (jika kelassiswa kosong)

### 5. Mapel
- **Tabel**: `mapel` (idmapel, kode, nama, kelompok [wajib/pilihan])
- **Relasi**: hasMany pengampu, hasMany jadwal
- **CRUD**: store, update, delete (jika pengampu/jadwal terkait)

### 6. Kelas
- **Tabel**: `kelas` (idkelas, tahunajaran_idtahunajaran, nama, tingkat [10/11/12], jenis [induk/mapel_pilihan], guru_idguru)
- **Relasi**: belongsTo tahunajaran, belongsTo guru (wali), hasMany kelassiswa, belongsToMany siswa
- **FK**: tahunajaran_idtahunajaran -> tahunajaran.idtahunajaran (restrict), guru_idguru -> guru.idguru (restrict)
- **CRUD**: store, update, delete (jika kelassiswa kosong)

### 7. Kelassiswa (Penempatan Siswa)
- **Tabel**: `kelassiswa` (idkelassiswa, kelas_idkelas, siswa_idsiswa)
- **Relasi**: belongsTo kelas, belongsTo siswa, unique(kelas_idkelas, siswa_idsiswa)
- **FK**: kelas_idkelas -> kelas.idkelas (restrict), siswa_idsiswa -> siswa.idsiswa (restrict)
- **CRUD**: tambah (via PenempatanService), keluarkan (delete)

### 8. Polajam (Pola Jam)
- **Tabel**: `polajam` (idpolajam, nama)
- **Relasi**: hasMany polajamhari, hasMany jampelajaran
- **CRUD**: store, update, delete (jika polajamhari/jampelajaran terkait)

### 9. Polajamhari (Pemetaan Hari ke Pola)
- **Tabel**: `polajamhari` (idpolajamhari, polajam_idpolajam, hari [1-7 unique])
- **Relasi**: belongsTo polajam, hasMany jampelajaran
- **FK**: polajam_idpolajam -> polajam.idpolajam (restrict)
- **Catatan**: hari harus unik global

### 10. Jampelajaran (Jam Pelajaran per Hari)
- **Tabel**: `jampelajaran` (idjampelajaran, polajam_idpolajam, jenis [pelajaran/istirahat], jam_ke, mulai, selesai, keterangan [100 char])
- **Relasi**: belongsTo polajam, hasMany jadwal
- **FK**: polajam_idpolajam -> polajam.idpolajam (restrict)
- **Unique**: (polajam_idpolajam, jam_ke)
- **CRUD**: simpan, ubah, hapus (jika jadwal terkait)

### 11. Pengampu
- **Tabel**: `pengampu` (idpengampu, semester_idsemester, kelas_idkelas, mapel_idmapel, guru_idguru)
- **Relasi**: belongsTo semester, kelas, mapel, guru; hasMany jadwal
- **FK**: semester_idsemester -> semester.idsemester (restrict), kelas_idkelas -> kelas.idkelas (restrict), mapel_idmapel -> mapel.idmapel (restrict), guru_idguru -> guru.idguru (restrict)
- **Unique**: (semester_idsemester, kelas_idkelas, mapel_idmapel)
- **CRUD**: simpan, update, hapus (jika jadwal terkait)

### 12. Jadwal
- **Tabel**: `jadwal` (idjadwal, pengampu_idpengampu, hari [1-7], jam_ke, status [draft/published])
- **Relasi**: belongsTo pengampu, hasMany (tidak ada)
- **FK**: pengampu_idpengampu -> pengampu.idpengampu (restrict)
- **Unique**: (pengampu_idpengampu, hari, jam_ke)
- **Index**: (hari, jam_ke)
- **CRUD**: simpan, hapus, publikasikan, tarik publikasi

### 13. Publikasijadwal
- **Tabel**: `publikasijadwal` (idpublikasijadwal, nomor, semester_idsemester, kelas_idkelas, versi, status [published/withdrawn], published_at, published_by, snapshot [json])
- **Relasi**: belongsTo semester, kelas, publisher (user)
- **FK**: semester_idsemester -> semester.idsemester (cascade), kelas_idkelas -> kelas.idkelas (cascade)
- **CRUD**: create versi terbaru, tarik publikasi

### 14. Appmenucat (Kategori Menu)
- **Tabel**: `appmenucat` (idappmenucat, appmenucat, urut)
- **Relasi**: hasMany appmenunew
- **CRUD**: store, update, delete

### 15. Appmenunew (Menu Item)
- **Tabel**: `appmenunew` (idappmenunew, appmenunew, url, icon, urut, appmenucat_idappmenucat, created_by, updated_by)
- **Relasi**: belongsTo appmenucat, hasMany roles, hasMany usermenu
- **CRUD**: store, update, delete

### 16. Usermenu (Assign Menu ke User)
- **Tabel**: `usermenu` (idusermenu, user_iduser, appmenunew_idappmenunew)
- **Relasi**: belongsTo user, belongsTo appmenunew
- **FK**: user_iduser -> users.id, appmenunew_idappmenunew -> appmenunew.idappmenunew
- **Composite Index**: (user_iduser, appmenunew_idappmenunew)

### 17. Rolecat (Kategori Role)
- **Tabel**: `rolecat` (idrolecat, rolecat)
- **Relasi**: hasMany roles
- **CRUD**: store, update, delete

### 18. Roles
- **Tabel**: `roles` (id, name, guard_name, rolecat_idrolecat, appmenunew_idappmenunew)
- **Relasi**: hasMany role_has_permissions, hasMany model_has_roles
- **FK**: rolecat_idrolecat -> rolecat.idrolecat, appmenunew_idappmenunew -> appmenunew.idappmenunew

### 19. Model_has_roles
- **Tabel**: `model_has_roles` (role_id, model_id)
- **Relasi**: belongsTo roles, belongsTo User (model)
- **Catatan**: model_type tidak digunakan dalam konfigurasi

### 20. Role_has_permissions
- **Tabel**: `role_has_permissions` (permission_id, role_id)
- **Relasi**: belongsTo permissions, belongsTo roles

### 21. Users
- **Tabel**: `users` (id, username [30 char unique], email, password, level [superadmin/guru/siswa], wajib_ganti_password)
- **Relasi**: hasOne guru, hasOne siswa, hasMany model_has_roles, hasMany usermenu
- **Level**: superadmin, guru, siswa

### 22. Docs / Docscat
- **Tabel**: docscat (iddocscat, docscat, urut), docs (iddocs, title, content, urut, docscat_iddocscat)
- **Relasi**: belongsTo/docscat, hasMany/docs

## 5. Peta Relasi Antarmodel

### Hierarki Utama (Akademik)
```
tahunajaran 1..* semester
semester 1..* pengampu
tahunajaran 1..* kelas
kelas 1..* pengampu
kelas 1..* kelassiswa
kelassiswa *-- siswa
guru 1..* kelas (hanya induk)
guru 1..* pengampu
mapel 1..* pengampu
mapel 1..* jadwal
polajam 1..* polajamhari
polajam 1..* jampelajaran
polajamhari *-- jampelajaran (polajam_idpolajam)
pengampu 1..* jadwal
siswa 1..* kelassiswa
```

### Akses & Otorisasi
```
users 1..* usermenu
usermenu *-- appmenunew
appmenunew *-- roles (many-to-many via role_has_permissions)
roles *-- rolecat
roles *-- appmenunew (via rolecat)
usermenu *-- role (many-to-many)
```

### Aktivasi
- **Tahun Ajaran Aktif** -> Semester Aktif -> Kelas Tahun Ajaran
- Setiap tingkat (tahunajaran → semester → kelas → kelassiswa → siswa) memiliki field `aktif`

### Alur Data Induk → Penjadwalan
```
Tahun Ajaran (aktif) → Semester (aktif) → Kelas (jenis: induk)
                    ↓
Guru (wali kelas induk) → Pengampu (semester + kelas + mapel + guru)
                    ↓
Polajam → Polajamhari (pemetaan hari) → Jampelajaran (jam per slot)
                    ↓
Jadwal (hari + jam_ke + pengampu)
                    ↓
Validasi: bentrok guru, bentrok siswa, kelas terbit status
                    ↓
Publikasi → Publikasijadwal (snapshot + versi)
```

## 6. Penjelasan Workflow Data Induk sampai Penjadwalan

### 1. Pendirian Tahun Ajaran & Semester
- Superadmin membuat tahun ajaran (format YYYY/YYYY)
- Set tahun ajaran aktif, maka semester (Ganjil/Genap) bisa dibuat
- Semester aktif menandai tahun ajaran dan kelas terkait

### 2. Pendirian Kelas
- Setiap kelas memiliki: nama, tingkat (10/11/12), jenis (induk atau mapel_pilihan), wali guru (opsional, hanya induk)
- Kelas induk wajib memiliki wali guru dan status aktif
- Kelas terikat ke tahun ajaran aktif

### 3. Pendaftaran Siswa
- User dibuat dengan level `siswa`
- Siswa terikat ke user via user_iduser
- Siswa memiliki data lengkap (nik, alamat, orang tua, tahun_masuk, status)

### 4. Penempatan Siswa ke Kelas (Kelassiswa)
- Siswa ditempatkan ke kelas via PenempatanService
- Validasi: siswa aktif, kelas induk wajib punya wali aktif, tidak ada penempatan duplikat
- Setiap siswa bisa memiliki riwayat kelas (multiple kelassiswa per masa waktu)

### 5. Penugasan Pengampu
- Superadmin menugaskan guru-mapel-kelas-semester ke tabel pengampu
- Validasi: semester dan kelas harus compatible, mapel cocok dengan guru, tidak ada duplikasi
- Setelah pengampu disimpan, bisa dibuat jadwal

### 6. Pembuatan Jadwal
- Setiap slot (hari + jam_ke) diisi oleh satu pengampu
- Validasi bentrok: guru, siswa, kelas
- Jadwal dalam status `draft` selama belum dipublikasi
- Bisa dipublikasikan untuk membuat versi publish berdasar snapshot

### 7. Publikasi Jadwal
- Semua jadwal draft untuk semester+kelas tertentu diubah menjadi `published`
- Buat entri Publikasijadwal dengan nomor versi, snapshot data, dan tanggal publish
- Bisa ditarik (withdrawn) untuk membatalkan publikasi

## 7. Audit Khusus: Jurusan, Kelas, Siswa, dan Penempatan Siswa

### A. Apakah Jurusan Sudah Ada?

**Status: TIDAK TERIMPLEMENTASI SEPENUHNYA** (Hanya berupa teks dalam nama kelas)

1. **Model/Tabel Jurusan**: Tidak ada model `Jurusan` atau tabel `jurusan` di database
2. **Kolom di Tabel Kelas**: Tidak ada kolom `jurusan_id` atau `jurusan` di tabel kelas
3. **Referensi di Model Siswa/Penempatan**: Tidak ada foreign key ke jurusan
4. **Nama Kelas sebagai Penanda**: Nama kelas menyimpan informasi jurusan sebagai teks (misal: "Mipa-1", "Mipa-2", "TKJ-1")
   - Contoh dari data: kelas "Mipa-1" menunjukkan jurusan MIPA
   - Tidak ada pemisahan master data jurusan terpisah
5. **Route/Livewire/Menu**: Tidak ada route, component, atau menu spesifik untuk jurusan
6. **Seeder**: Tidak ada seeder untuk master jurusan

**Kesimpulan**: Data jurusan ada tetapi terstruktur dalam nama kelas saja, bukan sebagai master data terpisah dengan CRUD sendiri.

### B. Bagaimana Kelas Dibentuk?

1. **Tingkat Kelas**: Diimplementasikan sebagai enum `10, 11, 12` (representing X, XI, XII)
2. **Jurusan/PROGRAM KEAHLIAN**: Hanya melalui nama kelas (contoh: "Mipa-1" = MIPA, "TKJ-1" = Teknik Komputer dan Jaringan)
3. **Rombel**: Tidak ada tabel rombel terpisah. Setiap kombinasi (tingkat + jurusan) adalah satu kelas.
4. **Nama Kelas**: Format bebas, contoh: "Mipa-1", "Mipa-2", "X MIPA 1" (tergantung sekolah)
5. **Wali Kelas**: Diimplementasikan di tabel `kelas` melalui `guru_idguru`, hanya untuk kelas jenis `induk`
6. **Tahun Ajaran**: Setiap kelas harus terikat ke `tahunajaran_idtahunajaran`

**Catatan Penting**: Struktur kelas saat ini fleksibel tetapi kurang ternormalisasi. Informasi jurusan tidak bisa difilter atau dilaporkan secara terpisah dari nama kelas.

### C. Bagaimana Penempatan Siswa Bekerja?

1. **Menentukan Kelas Aktif Siswa**:
   - Cari `kelassiswa` terakhir berdasarkan `idkelassiswa` desc untuk siswa tertentu
   - Filter berdasarkan `tahunajaran_idtahunajaran` yang aktif
   - Cek `kelas.jenis` (induk atau mapel_pilihan)

2. **Menyimpan Riwayat Kenaikan/Perpindahan**:
   - Setiap kali siswa pindah kelas, dibuat entri baru di `kelassiswa`
   - Riwayat disimpan semua entri `kelassiswa` per siswa
   - Bisa diurutkan berdasarkan `idkelassiswa` desc untuk melihat sejarah

3. **Menghubungkan Kelas dan Tahun Ajaran**:
   - Setiap `kelassiswa` memiliki `kelas_idkelas`
   - `kelas` memiliki `tahunajaran_idtahunajaran`
   - Bisa ditelusuri: siswa → kelassiswa → kelas → tahunajaran

4. **Potensi Duplikasi Informasi**:
   - Informasi jurusan ada dua tempat: di nama kelas + tidak ada di struktur tabel
   - Informasi tingkat ada di tabel kelas (`tingkat`) dan bisa diekstrak
   - Risiko: jika nama kelas diubah, informasi jurusan hilang dari riwayat

5. **Dampak jika Jurusan Ditambahkan**:
   - Perlu perubahan migrasi: menambah kolom `jurusan` ke tabel `kelas`
   - Perlu model `Jurusan` baru (opsional, bisa enum saja)
   - Perlu update form tambah/edit kelas untuk memilih jurusan dari dropdown
   - Riwayat siswa tetap valid (tidak terpengaruh secara langsung)

### D. Rekomendasi

**Tidak merekomendasikan penambahan fitur secara otomatis.**

**Alasan:**
1. **Minimalitas**: Jurusan sudah tersedia melalui nama kelas, tidak ada data yang "terlupakan"
2. **Kompleksitas**: Menambah model/jurusan membutuhkan migrasi, update form, validasi, dan potentially merusak data existentes
3. **Alternatif Lebih Ramping**: Menambah kolom `jurusan` ke tabel `kelas` dengan enum atau string sederhana

**Rekomendasi Perubahan Minimal:**
1. **Tambah kolom `jurusan` ke tabel `kelas`** (enum: MIPA, TKJ, Bahasa, dll - atau string umum)
2. **Update form kelas** (store/edit) untuk memilih jurusan
3. **Update query** di SiswaComponent dan Beranda untuk menampilkan jurusan dari kelas
4. **Update laporan** detail siswa dan riwayat kelas untuk menampilkan field jurusan

**Bukan Perlu:**
- Membuat menu CRUD jurusan terpisah
- Membuat model Jurusan terpisah (kecuali jika butuh relasi banyak-ke-banyak dengan mapel/other)
- Merubah seluruh struktur database

## 8. Daftar Masalah berdasarkan Prioritas

### P1: Tidak Ada Master Data Jurusan Terpisah (Tinggi)
- **Masih ada di nama kelas saja**
- **Dampak**: Tidak bisa filtering/laporan berdasarkan jurusan secara terprogram
- **Solusi**: Tambah kolom `jurusan` ke tabel kelas

### P2: Kelas Tidak Memiliki Wali di Semester Non-Aktif (Medium)
- **Hanya guru wali untuk kelas induk**
- **Dampak**: Ketika semester berubah, data wali bisa tidak lengkap
- **Solusi**: Pastikan validasi di service mencakup semua semester

### P3: Riwayat Kelassiswa Tidak Mudah Dibaca (Medium)
- **Banyak entri kelassiswa per siswa**
- **Dampak**: Ribet untuk menampilkan riwayat kenaikan kelas
- **Solusi**: Tambah fitur di SiswaComponent untuk meringkas riwayat

### P4: Validasi Penempatan Siswa Tergantung Tahun Ajaran (Medium)
- **Penempatan hanya valid di tahun ajaran yang sama**
- **Dampak**: Sulit memindahkan siswa ke tahun ajaran baru
- **Solusi**: Tambah logika di PenempatanService untuk cross-tahun ajaran

### P5: Nama Kelas Bebas Format (Low)
- **Tidak ada aturan format wajib**
- **Dampak**: Inkonistensi data antar sekolah
- **Solusi**: Dokumenisasi format nama kelas (opsional)

## 9. Rekomendasi Arsitektur Master Data Akademik

### Struktur yang Disarankan

1. **Tabel `kelas` ditambah kolom `jurusan`** (enum/string)
   - Memudahkan filtering dan laporan
   - Tidak merusak data existentes

2. **Tidak perlu model `Jurusan` terpisah**
   - Jurusan hanya berfungsi sebagai klasifikasi kelas
   - Bisa ditambahkan sebagai enum di model `Kelas`

3. **Tingkatkan validasi PenempatanService**
   - Tambah validasi cross-tahun ajaran
   - Tambah laporan riwayat yang lebih baik di UI

4. **Standarisasi format nama kelas** (opsional)
   - Misal: `[Tingkatan] [Jurusan] [Rombel]` atau `[Rombel] [Tingkatan] [Jurusan]`

5. **Integrasi dengan Penjadwalan**
   - Pastikan saat pembuatan jadwal, info jurusan tergambar melalui kelas → pengampu → mapel

### Dependensi Modul

```
Tahun Ajaran → Semester → Kelas (dengan jurusan) → Kelassiswa → Siswa
           ↓                      ↓
        Guru (wali)          Pengampu → Jadwal → Publikasi
           ↓
    Mapel (pelajaran di kelas)
```

## 10. Rekomendasi Urutan Implementasi

### Tahap 1: Audit & Dokumentasi (SEMENTARA - SEkarang)
- Selesai: Laporan ini
- Tujuan: Memahami status quo sebelum berubah

### Tahap 2: Tambah Kolom Jurusan ke Tabel Kelas
- Buat migrasi menambah kolom `jurusan` ke `kelas` (nullable dulu)
- Update model `Kelas` untuk fillable dan cast
- Update `KelasService::buat()` dan `updateKelas()` untuk validasi jurusan
- Update form Livewire `KelasComponent` (tambah field select jurusan)

### Tahap 3: Update Tampilan & Laporan
- Update `SiswaComponent` - detail siswa menampilkan jurusan
- Update `KelassiswaComponent` - menampilkan jurusan kelas
- Update `Beranda` component untuk menampilkan jurusan siswa/wali
- Tambah fitur search/filter jurusan di dashboard

### Tahap 4: Validasi & Testing
- Test import kelas (pastikan jurusan terimport)
- Test update kelas lama dengan jurusan
- Test hubungan jurusan -> kelas -> siswa -> penempatan
- Verifikasi tidak ada data yang hilang

### Tahap 5: Optimalisasi (Opsional)
- Standarisasi format nama kelas
- Tambah fitur filter/jurusan di laporan
- Review ulang kebutuhan model Jurusan terpisah

## 11. Daftar File yang Kemungkinan Perlu Diubah pada Fase Berikutnya

### Wajib Diubah (Tahap 2-3):
1. `database/migrations/2026_10_26_000008_create_kelas_table.php` - Add kolom jurusan
2. `app/Models/Kelas.php` - Tambah fillable/cast jurusan
3. `app/Livewire/KelasComponent.php` - Tambah field dan validasi jurusan
4. `resources/views/livewire/kelas-component.blade.php` - Tambah field jurusan di form
5. `app/Services/KelasService.php` - Update metode buat/update

### Diperlakukan (Tahap 3):
6. `resources/views/livewire/siswa-component.blade.php` - Tampilkan jurusan di detail
7. `app/Livewire/KelassiswaComponent.php` - Filter/jurusan di penempatan
8. `app/Livewire/GuruComponent.php` - Detail guru menampilkan jurusan kelas wali

### Perlu Diperhatikan (Tahap 4):
9. `resources/views/livewire/berandasiswa-component.blade.php` - Info kelas jurusan siswa
10. `resources/views/livewire/berandaguru-component.blade.php` - Info jurusan kelas wali
11. `app/Services/PenempatanService.php` - Validasi tambahan

### Tidak Perlu Segera:
12. `app/Models/Jurusan.php` - Bisa dibuat nanti jika butuh relasi kompleks
13. Route/menu jurusan terpisah
14. Semua modul lain (sudah berfungsi dengan struktur saat ini)

## 12. Pertanyaan yang Benar-benar Tidak Bisa Dijawab dari Kode

1. **Apa alasan desain memilih menyimpan jurusan di nama kelas rather than tabel terpisah?**
   - Tidak ditemukan catatan dokumentasi atau komentar yang menjelaskan keputusan arsitektur awal
   - Mungkin karena fase prototyping/migration dari sistem lama

2. **Apa batas antara "jurusan" dan "rombel" di aplikasi ini?**
   - Tidak ditemukan batas yang jelas dalam kode; keduanya tergantung nama kelas saja

3. **Apakah sistem akan merusak jika kita menambah kolom `jurusan` ke tabel `kelas` sekarang?**
   - Tidak bisa dijawab tanpa migrasi uji; butuh testing pada database produksi

4. **Bagaimana hubungan antara `kelassiswa` (penempatan) dengan perubahan jurusan kelas?**
   - Jika jurusan diubah, riwayat penempatan tetap valid (hanya berubah nama kelas)
   - Butuh verifikasi data nyata

5. **Mengapa tidak ada enum `tingkat` di luar kelas (misal tabel master_tingkat)?**
   - Karena cukup di dalam tabel `kelas` sebagai enum('10','11','12')
   - Tidak ada kebutuhan normalisasi lebih lanjut dari kode

6. **Apa batas penggunaan `polajamhari.hari` yang unik global?**
   - Code memastikan hari unik per pola, tetapi bisa ada hari yang sama di pola berbeda
   - Butuh studi kasus untuk batas konkret

7. **Apa alasan `publikasijadwal` menggunakan `withdrawn_at` daripada `deleted_at`?**
   - Untuk melacak riwayat pengubahan publikasi (audit trail)
   - Bisa dikembalikan (restore) jika perlu

8. **Apa mekanisme sinkronisasi paket menu antara role/siswa/guru?**
   - Dijalani oleh `PaketMenuService::sinkronkanSemua()` tetapi butuh running manual atau cron
   - Butuh verifikasi apakah sudah jalan sempurna di seluruh user

9. **Apa respons jika user level `guru` atau `siswa` coba akses route `jadwal`?**
   - Diblokir oleh `MenuAccessMiddleware` yang cek `usermenu` table
   - Tapi butuh verifikasi UI/UX apakah seharusnya disabled atau redirect

10. **Mengapa ada dua tabel `activity_log` entries untuk yang sama?**
    - Setiap kali ada aktivitas (create/update/delete) tercatat
    - Bisa akumulasi banyak baris untuk aktivitas massal
    - Butuh cleanup rutin atau konfigurasi TTL

---

*Laporan ini dibuat berdasarkan audit kode program tanpa mengedit database, model, atau implementasi apapun.*
*Semua fakta diambil dari kode aktual, migrasi, model, livewire component, routes, dan seeder yang ada di project.*