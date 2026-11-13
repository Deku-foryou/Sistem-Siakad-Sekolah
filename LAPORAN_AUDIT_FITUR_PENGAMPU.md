# LAPORAN AUDIT FITUR PENGAMPU

**Tanggal:** 13 November 2026 · **Jenis:** audit (read-only; tidak ada file/route/menu/data yang diubah) · **Metode:** tracing kode statis + query katalog DB (read-only) · **Sumber:** kode aktual di `app/`, `resources/`, `routes/`, `database/`, `AGENTS.md`.

---

## 1. Ringkasan kondisi fitur

**Pengampu adalah fitur LEGACY (sistem jadwal lama) yang dalam praktiknya sudah ditinggalkan oleh modul aktif, tetapi masih dipakai oleh dua calon penghapus-agar tidak boleh dihapus: modul JadwalComponent (route legacy yang sengaja dibiarkan hidup) dan console command audit.**

- Route `/pengampu` ada, eksplisit, superadmin-only, **menu TAMPIL** di sidebar superadmin (kategori "Jadwal Pelajaran", urut 3).
- Route `/jadwal` (JadwalComponent — konsumen utama data pengampu) via loop dinamis + `menu.access`, **menu DISEMBUNYIKAN** dari sidebar superadmin; hanya superadmin yang punya usermenu.
- Tabel `pengampu` berisi **18 baris** (seluruhnya demo semester Ganjil, dari `DemoAkademikSeeder` untuk 2 kelas jadwal penuh); tabel `jadwal` = **0 baris**; `publikasijadwal` = **0 baris** — tidak ada data jadwal historis/nyata yang terikat pengampu.
- **Modul aktif (nilai, absensi harian & per jam, rapor, dashboard, jadwal guru/siswa, keuangan) TIDAK ada yang membaca tabel `pengampu`** — mereka memakai `penugasan_mengajar` (nilai) dan `jadwalkelas` (jadwal).
- Dokumentasi aplikasi sendiri sudah menandai Pengampu sebagai **"Fitur Legacy … sistem jadwal lama … dipertahankan hanya untuk data lama"** (`resources/views/docs/artikel/navigasi.blade.php`).

**Keputusan: SEMBUNYIKAN MENU, PERTAHANKAN IMPLEMENTASI** (detail §7).

---

## 2. Daftar file, route, tabel, relasi terkait

### Implementasi aktif
| Jenis | Nama |
|---|---|
| Route | `GET /pengampu` → `App\Livewire\PengampuComponent` (`name('pengampu')`) — grup `check.level:superadmin` (routes/web.php:253); route `/jadwal` via loop dinamis + `menu.access` (seluruh `appmenunew` non-eksplisit), tidak di `$explicitUrls` |
| Component | `app/Livewire/PengampuComponent.php` (CRUD: store/update/delete via `PengampuService`; semua method publik `abort_unless(level === 'superadmin', 403)`) |
| View | `resources/views/livewire/pengampu-component.blade.php` |
| Service | `app/Services/PengampuService.php` (simpan: TA-kelas-semester cocok, guru aktif, lockForUpdate, unique (semester,kelas,mapel), deteksi bentrok guru via join jadwal+pengampu+kelas; hapus: `jadwal()->exists()` → tolak) |
| Model | `app/Models/Pengampu.php` (belongsTo semester/kelas/mapel/guru; hasMany jadwal) |
| Migration | `database/migrations/2026_10_27_000004_create_pengampu_table.php` |
| Seeder | `database/seeders/JadwalMenuSeeder.php` (menu Pengampu urut 3), `IkonMenuSeeder.php` (`fa-clipboard-user`), `DemoAkademikSeeder.php` (baris demo), `DatabaseSeeder.php` (urutan seeder) |
| Sidebar | `resources/views/assets/sidebar.blade.php` baris ~306: label fallback `'pengampu' => 'Pengampu'` |
| Docs | glosarium, referensi-tabel, referensi-hubungan, navigasi — menandai legacy |

### Konsumen eksternal (di luar fitur Pengampu sendiri)
| Modul | Bukti |
|---|---|
| **JadwalComponent (legacy Jadwal)** | `app/Livewire/JadwalComponent.php:10,73-100,191,223-227,238-271` — query `Pengampu::where(...)`, `Jadwal::with('pengampu')`, `selectedPengampu`; `resources/views/livewire/jadwal-component.blade.php` menampilkan `pengampu->mapel/guru` |
| **JadwalService** | `app/Services/JadwalService.php:7,16,22-28,31,47,54-73,92,99,125-149,167,169,185-245,265-271` — **membuat Pengampu otomatis** saat menyimpan jadwal bila belum ada (baris 137–148); konflik bentrok pakai join ke pengampu; publikasi/snapshot jadwal |
| **Model Jadwal** | `app/Models/Jadwal.php:13,15-18` (`pengampu_idpengampu` FK → pengampu) |
| **Model Kelas/Mapel/Semester/Guru** | relasi `Kelas::pengampu()` (`Kelas.php:55`) — tidak ada pemanggil lain (satu-satunya relasi kelas; tidak dipakai modul aktif) |
| **Console audit** | `app/Console/Commands/AuditAbsensiParamManipulation.php:8,47` dan `app/Console/Commands/VerifyAbsensiHardeningFix.php:8,50-57` — dipakai untuk audit kewenangan guru (tooling operasi, tidak terdaftar di scheduler) |

### Tidak terbukti bergantung pada `pengampu` (diperiksa, bukan diasumsikan)
Modul-modul berikut **tidak** membaca tabel `pengampu` (grep seluruh `app/`, `resources/views`, `routes/` → 0 hit):
- Nilai: `InputNilaiComponent`, `KoreksiNilaiComponent`, `NilaiSiswaService`, `FinalisasiNilaiService` → `penugasan_mengajar`.
- Absensi harian & per jam: `AbsensiHarianComponent`, `AbsensiPerjamComponent` → `kelassiswa`/jadwal pertemuan; tidak ada hit `pengampu`.
- Jadwal Guru/Siswa & Dashboard: `JadwalGuruComponent`, `BerandasiswaComponent` → `JadwalKelas` + `JadwalSiswaService`.
- Rapor: `RaporSiswaService`, `RaporWaliKelasService` → tidak menyentuh pengampu.
- Keuangan (tagihan/tarif/beasiswa/pembayaran/kuitansi/rekonsiliasi/refund/pembebasan): 0 hit.
- Import siswa/kelas/penempatan (`HandlesImport`): tidak menyentuh pengampu.

---

## 3. Tabel & relasi (database)

| Tabel / kolom | Detail |
|---|---|
| `pengampu` | PK `idpengampu`; FK `semester_idsemester`→semester, `kelas_idkelas`→kelas, `mapel_idmapel`→mapel, `guru_idguru`→guru (**restrictOnDelete**); unique `pengampu_semester_kelas_mapel_unique` (semester+kelas+mapel); index kelas/mapel/guru |
| `jadwal` | FK `pengampu_idpengampu`→pengampu (restrictOnDelete); unique (pengampu,hari,jam_ke) — **0 baris** |
| `publikasijadwal` | FK semester+kelas (cascade); snapshot jadwal lama — **0 baris** |
| `penugasan_mengajar` | PK `idpenugasan_mengajar`; kolom & FK identik + index `(guru_idguru, semester_idsemester)`; unique (semester,kelas,mapel) — **48 baris**; sumber otorisasi **tunggal** untuk penilaian |
| `jadwalkelas` | PK `idjadwalkelas`; FK kelas/semester/jamhari/mapel/guru — **27 baris**; jadwal baru; `jadwal_pertemuan` (snapshot) terikat ke sini, bukan ke jadwal/pengampu |
| `appmenunew` id 14 | menu Pengampu (Kategori "Jadwal Pelajaran", urut 3); usermenu: hanya superadmin (`idappmenunew` 14 & 29 dipegang `admin`); role: 0 |
| `appmenunew` id 29 | menu Jadwal (Lama) (urut 4), **disembunyikan** dari sidebar superadmin (`$menuDisembunyikanSuperadmin`), usermenu hanya superadmin, route hidup via `menu.access` |

Foreign key penting untuk opsi "hapus": `jadwal.pengampu_idpengampu` restrictOnDelete → tabel pengampu tidak bisa di-drop selama `jadwal` ada; `jadwal` wajib ditangani lebih dulu. `publikasijadwal` tidak ber-FK ke `jadwal`/`pengampu` (hanya ke semester/kelas).

---

## 4. Ketergantungan lintas modul (dengan bukti pemanggilan)

| Modul | Bergantung pada pengampu? | Bukti |
|---|---|---|
| Jadwal (lama) + Publikasijadwal | **Ya — penuh** | JadwalComponent & JadwalService di atas; `JadwalService::simpanAtauBuatBaru` memanggil `PengampuService::simpan()` otomatis; pesan error menyuruh "Ubah di menu Pengampu" |
| Penilaian & Rapor | **Tidak** | NilaiSiswaService docblock: "Sumber otorisasi tunggal: `penugasan_mengajar`… tidak membaca atau menulis `pengampu` maupun `jadwalkelas`"; input/koreksi nilai & finalisasi hanya query penugasan_mengajar |
| Absensi Harian | **Tidak** | 0 hit `pengampu` (pencarian menyeluruh di komponen & service terkait) |
| Absensi Per Jam | **Tidak** | query `jadwalkelas.jamhari` + `jadwal_pertemuan` (snapshot); 0 hit `pengampu` |
| Jadwal Guru/Siswa | **Tidak** | `JadwalSiswaService` sumber tunggal; `JadwalKelas` |
| Dashboard/beranda | **Tidak** | 0 hit |
| Keuangan (tagihan/tarif/beasiswa) | **Tidak** | 0 hit |
| Console audit (tooling) | **Ya — referensi** | 2 command di atas; tidak terdaftar di scheduler; dijalankan manual |

---

## 5. Perbandingan dengan modul pengganti

### Pengampu vs Penugasan Mengajar (nilai/penilaian)
| Aspek | Pengampu | PenugasanMengajar |
|---|---|---|
| Struktur kolom | semester, kelas, mapel, guru | identik |
| Unique | (semester, kelas, mapel) | identik |
| FK | semester/kelas/mapel/guru, restrict | identik + index (guru, semester) |
| Validasi | TA kelas↔semester, guru aktif, TA sama, duplikat, **bentrok jadwal** (hanya bila row sudah punya jadwal) | TA kelas↔semester, guru aktif, duplikat; **blokir ubah** bila sudah ada nilai |
| Tujuan | Jadwal mengajar (legacy) | Otorisasi guru menginput nilai |
| Cakupan | terkait jadwal (`jadwal`) | nilai & finalisasi rapor |
| Status | legacy (menu "Fitur Legacy") | aktif (menu Penilaian) |

### Pengampu vs JadwalKelas (jadwal)
| Aspek | Pengampu → Jadwal (lama) | JadwalKelas (baru) |
|---|---|---|
| Sumber jam | `jampelajaran` (global, dari `polajam`) — dihapus dari menu; route redirect ke `/jamhari` | `jamhari` (per kelas) |
| Konflik | bentrok hari/jam kelas & guru via query | bentrok per kelas |
| Publikasi | `publikasijadwal` (snapshot; **0 baris**) | belum ada (direncanakan) |
| Jalur kerja utama | tidak (legacy) | ya |
| Status data | `jadwal` kosong | 27 baris |

**Kesimpulan perbandingan:** Penugasan Mengajar **tidak** menggantikan fungsi jadwal; `jadwalkelas` + `jamhari` yang menggantikannya. Tidak ada satu modul pun yang sekaligus menggantikan "pengampu + jadwal legacy" selain keduanya; tanpa migrasi data (jadwal kosong), `pengampu` hanya jadi penopang bagi `JadwalComponent` + `JadwalService`.

---

## 6. Risiko per skenario

### A. Menu disembunyikan (SEPERTI `jadwal` saat ini)
- **Risiko fungsional: nihil** — route hidup; superadmin tetap bisa akses via URL (pola yang sama telah dipakai untuk `/jadwal` dan docs menyebutnya disengaja).
- Tidak ada modul yang mengandalkan menu; usermenu tetap ada agar akses tidak putus.
- **Risiko:** kebingungan admin lama yang terbiasa melihat "Pengampu" di sidebar (mitigasi: docs/Fitur Legacy, badge "Lama"); superadmin lupa URL (/pengampu) — bisa diberi tahu lewat docs.
- Data 18 baris dan `jadwal`(0) tetap utuh; tidak ada risiko FK.

### B. Implementasi dihapus (component + service + route + view + model)
- **Jadwal Component rusak seketika** (query model Pengampu & `PengampuService` hilang) meski menunya disembunyikan; JadwalService rusak juga; console audit fatal (`Class "App\Models\Pengampu" not found`).
- Route `/pengampu` 404; jika `/jadwal` juga dihapus, maka feature set "Fitur Legacy" hilang seluruhnya.
- Tidak ada dampak ke nilai/absensi/rapor/keuangan.
- **Risiko FK:** tabel pengampu masih direferensikan `jadwal` (restrictOnDelete) — menghapus model tidak menghapus tabel; skema tersisa tanpa kode.

### C. Tabel `pengampu` + data historis dihapus
- `jadwal` (0 baris) harus dihapus dulu atau FK-nya dinonaktifkan; `publikasijadwal` tetap ada (tidak ber-FK ke pengampu/jadwal, aman secara skema) — tetapi snapshot histori bila kelak ada → kehilangan konteks semester-kelas-mapel-guru historis.
- 18 baris demo (Ganjil) hilang; bukan data operasional nyata (sekolah data-produksi tidak ada di DB ini — ini adalah demo), **namun** `DemoAkademikSeeder` membuat ulang pengampu setiap seeder jalan → seeder akan gagal bila `Pengampu` model/service dihapus, atau membuat baris kembali bila hanya tabel yang drop.
- Migrasi `down()` ada; tidak akan dijalankan tanpa perintah eksplisit.
- FK restrict menjamin `DROP TABLE pengampu` diblokir MySQL selama `jadwal` ada → penghapusan tidak bisa "setengah jadi" secara diam-diam (baik untuk keamanan, tapi tetap harus direncanakan).

---

## 7. Rekomendasi akhir

# ► SEMBUNYIKAN MENU, PERTAHANKAN IMPLEMENTASI

**Mengapa bukan "AMAN DIHAPUS":**
- JadwalComponent/JadwalService (route legacy hidup) masih mem-query dan **bahkan membuat** baris `pengampu`; console audit tooling masih membacanya.
- `pengampu` adalah bagian dari rantai FK (`jadwal` restrictOnDelete) — penghapusan bukan operasi kecil.
- Docs aplikasi sendiri menyatakan fitur ini "dipertahankan hanya untuk data lama" — arahan produk yang sudah berjalan.
- Data `jadwal` memang kosong, tetapi keputusan menghapus baris penopang Jadwal legacy bukan keputusan implementasi — perlu keputusan pemilik (apakah Jadwal legacy akan dihapus total beserta `/jadwal`, menu-nya, dan data demo 18 pengampu).

**Mengapa bukan "MIGRASIKAN DEPENDENSI, LALU HAPUS":**
- Tidak ada dependensi dari modul aktif yang bisa "dimigrasikan" — nilai (`penugasan_mengajar`) dan jadwal (`jadwalkelas`) sudah terpisah; satu-satunya dependensi adalah JadwalComponent + console audit. Migrasi di sini berarti menghapus Jadwal legacy, bukan memindahkan datanya (jadwal = 0 baris → tidak ada yang dimigrasikan).

**Langkah implementasi yang disarankan (untuk tahap berikutnya, bila disetujui):**
1. Sembunyikan menu Pengampu dari sidebar superadmin dengan menambahkan `'pengampu'` ke `$menuDisembunyikanSuperadmin` di `resources/views/assets/sidebar.blade.php` (pola identik dengan `/jadwal`).
2. Pertahankan route, component, service, model, tabel, dan usermenu superadmin (akses langsung tetap tersedia).
3. Perbarui docs (`navigasi.blade.php`) agar menyebut Pengampu disembunyikan untuk superadmin (seperti Jadwal (Lama)).
4. JANGAN menghapus tabel, data 18 baris, route, atau implementasi — tidak ada urgensi; biaya pemeliharaan satu baris menu sama seperti Jadwal (Lama) yang sudah berhasil.
5. Jika suatu saat pemilik memutuskan penghapusan penuh: cakupannya = menu (cleanup seeder untuk id 14), route, component, service, model, console-command referensi, docs, baru kemudian `jadwal` (0 baris) → `pengampu`; **data demo 18 baris dihapus dengan persetujuan eksplisit**.

---

## 8. Fakta vs asumsi

| Fakta terverifikasi (dengan bukti) | Asumsi / belum dapat dibuktikan |
|---|---|
| Route, middleware, menu, usermenu, sembunyikan-sidebar (`sidebar.blade.php`) | Apakah pemilik menghendaki penghapusan total Jadwal legacy di masa depan |
| Data: pengampu 18 (Ganjil, demo), jadwal 0, publikasijadwal 0, penugasan_mengajar 48, jadwalkelas 27 | Apakah ada instalasi produksi lain (di luar DB demo) yang punya `jadwal` terisi — di luar cakupan audit ini; verifikasi produksi harus dilakukan di lingkungan tersebut |
| JadwalService membuat pengampu otomatis | — |
| Modul aktif tidak membaca pengampu (grep menyeluruh app/resources/routes/database) | — |
| Console audit command tidak dijadwalkan (tidak ada di `console.php`/`Kernel.php`) | Apakah pemilik masih menjalankan command audit secara manual untuk insiden absensi |
| Docs menandai Fitur Legacy | — |

**Status: audit selesai. Tidak ada perubahan kode/data/menu/route/schema/migrasi. Menunggu keputusan pemilik untuk tindakan penutupan.**
