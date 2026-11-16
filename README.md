# SIAKAD — Sistem Informasi Akademik Sekolah

SIAKAD adalah aplikasi web manajemen sekolah yang menggabungkan pengelolaan akademik (data induk, jadwal, absensi, penilaian, rapor) dengan modul keuangan sekolah (tagihan, pembayaran, alokasi, rekonsiliasi) serta portal siswa dan portal wali murid. Aplikasi ini dibangun di atas Laravel 12 dan Livewire 3.

Repository ini berisi implementasi aktual yang dikembangkan bertahap (lihat `ROADMAP.md` untuk riwayat sprint). README ini adalah pintu masuk dokumentasi teknis; detail per-modul dibahas di dalam kode dan `ROADMAP.md`.

> **Status singkat:** aplikasi berfungsi dan telah menjalani audit/UAT internal (termasuk UAT browser dan skenario keuangan pada basis data sintetis), tetapi belum digunakan resmi oleh sebuah sekolah. Integrasi Midtrans sudah diimplementasikan tetapi belum teruji end-to-end karena kredensial merchant belum tersedia. Lihat bagian [Status Proyek & Keterbatasan](#17-status-proyek--keterbatasan).

---

## Daftar Isi

1. [Overview](#1-overview)
2. [Fitur Utama per Modul](#2-fitur-utama-per-modul)
3. [Peran Pengguna & Kontrol Akses](#3-peran-pengguna--kontrol-akses)
4. [Teknologi](#4-teknologi)
5. [Arsitektur Sistem](#5-arsitektur-sistem)
6. [Struktur Direktori](#6-struktur-direktori)
7. [Alur Bisnis Inti](#7-alur-bisnis-inti)
8. [Gambaran Database](#8-gambaran-database)
9. [Instalasi & Pengembangan Lokal](#9-instalasi--pengembangan-lokal)
10. [Konfigurasi](#10-konfigurasi)
11. [Integrasi Pembayaran (Midtrans)](#11-integrasi-pembayaran-midtrans)
12. [Keamanan & Integritas Data](#12-keamanan--integritas-data)
13. [Pengujian & QA](#13-pengujian--qa)
14. [Scheduled Tasks](#14-scheduled-tasks)
15. [Catatan Deployment](#15-catatan-deployment)
16. [Pengembangan Lanjutan](#16-pengembangan-lanjutan)
17. [Status Proyek & Keterbatasan](#17-status-proyek--keterbatasan)
18. [Lisensi & Kredit](#18-lisensi--kredit)

---

## 1. Overview

**Nama proyek:** SIAKAD (Sistem Informasi Akademik Sekolah).

**Permasalahan yang diselesaikan.** Pengelolaan sekolah umumnya terfragmentasi: data siswa/kelas dikelola manual, absensi dan nilai tidak terhubung ke rapor, tagihan dan pembayaran dicatat terpisah, dan tidak ada satu sumber kebenaran untuk ledger keuangan sekolah. SIAKAD menyatukan data induk akademik, proses belajar mengajar, penilaian, dan keuangan sekolah dalam satu aplikasi dengan kontrol akses per peran.

**Tujuan pengembangan.**

- Menyediakan satu sistem operasional untuk akademik (jadwal, absensi, penilaian, rapor) dan keuangan sekolah (tagihan, pembayaran, alokasi, rekonsiliasi).
- Memberikan portal mandiri untuk siswa (jadwal, histori absensi, rapor, tagihan) dan wali murid (monitoring akademik read-only dan pembayaran tagihan anak).
- Menjaga integritas data keuangan: transaksi tidak dihapus/diubah, koreksi dilakukan lewat pengajuan dan reversal, bukan mutasi langsung.
- Menyediakan fondasi yang bisa diaudit (activity log, audit trail, perintah audit absensi, rekonsiliasi otomatis).

**Lingkup penggunaan.** Sekolah dengan kebutuhan dasar: master data, jadwal, absensi (harian dan per jam pelajaran), penilaian/rapor kurikulum KKTP, pengumuman, notifikasi, serta keuangan sekolah (SPP dan tagihan insidental). Bukan sistem akuntansi umum (tidak ada jurnal umum/neraca); modul keuangan adalah manajemen tagihan dan pembayaran sekolah.

**Status saat ini.** Dalam pengembangan aktif dan sudah melalui audit teknis serta UAT internal bertahap (lihat `ROADMAP.md`). Belum ada bukti penggunaan produksi oleh sekolah nyata; integrasi Midtrans menunggu kredensial merchant. Selengkapnya di [bagian 17](#17-status-proyek--keterbatasan).

---

## 2. Fitur Utama per Modul

Pengelompokan berikut mengikuti modul yang benar-benar tersedia di kode (komponen, service, migrasi). "Tersedia" berarti terimplementasi; verifikasi pengujian dijelaskan di bagian 13 dan 17.

### 2.1 Autentikasi & Manajemen Pengguna

- Login dengan **username + password** dan **captcha** (`mews/captcha`, endpoint `refresh-captcha`).
- Blokir login berdasarkan status data induk (`LoginBlockService`): mis. siswa harus `status = aktif` di tabel `siswa`, guru harus aktif di tabel `guru`; level tidak dikenal ditolak secara default.
- **Ganti password wajib** pada login pertama/pintu masuk tertentu (`force.password`), dan **cek status akun** (`cek.akun` / `CekAkunAktif`).
- Manajemen pengguna (`UsersComponent`), kategori peran kustom (`rolecat`, `role`) dan menu (`appmenucat`, `appmenunew`) dengan assignment menu per pengguna (`usermenu`). Ini mekanisme navigasi dinamis SIAKAD, bukan Spatie Permission (lihat [bagian 17](#17-status-proyek--keterbatasan)).
- Manajemen menu: pengelolaan kategori/menu aplikasi oleh superadmin; menu di-seed per paket peran (`PaketDefaultSeeder`, seeder menu lainnya).
- Activity log melalui `spatie/laravel-activitylog` (tabel `activity_log`).

### 2.2 Dashboard & Manajemen Sistem

- Dashboard per peran: admin (ringkasan operasional), guru (`BerandaguruComponent`: akun + kelas yang diampu sebagai wali kelas), siswa, wali.
- Log viewer (`opcodesio/log-viewer`) — akses dibatasi dan telah melalui remediasi keamanan (lihat `ROADMAP.md` 2B.27-R1); bukan untuk pengguna umum.
- Modul **Docs**: pusat dokumentasi internal berbasis database (`docs`, `docscat`) — dapat diakses superadmin untuk membaca/mengelola artikel panduan; tersedia juga halaman publik `welcome-docs`.

### 2.3 Data Master Akademik

- Tahun ajaran dan semester (`TahunajaranComponent`, `SemesterComponent`).
- Data siswa, guru, mata pelajaran, kelas, jurusan, dan pemetaan rombel (`rombel` di `kelas`).
- Penempatan siswa ke kelas per semester (`KelassiswaComponent`, `SiswaService`/`PenempatanService`).
- Guru–mata pelajaran (`gurumapel`) dan penugasan mengajar (`penugasan_mengajar`) — dasar jadwal guru.
- KKTP per mata pelajaran (`KKTPMapelComponent`) dan komponen nilai (`KomponenNilaiComponent`).
- Import data master massal (siswa, guru, mapel, kelas, penempatan) lewat mekanisme import di `app/Services/Import` dengan template, contoh file, dan pelaporan baris gagal.

### 2.4 Jadwal Pelajaran & Penugasan Mengajar

Dua sistem jadwal yang sengaja tidak dicampur (lihat `AGENTS.md`):

- **Jadwal (legacy, aktif)** — `JadwalComponent` / `JadwalService`: jadwal global per semester, deteksi bentrok, **publikasi** jadwal dengan snapshot ke `publikasijadwal`, dan tarik publikasi. Snapshot publikasi adalah dasar absensi per jam.
- **JadwalKelas (baru)** — `JadwalKelasComponent` / `JadwalKelasService`: jadwal per kelas berbasis `Jamhari` (konfigurasi jam per kelas); belum memiliki fitur publikasi.
- **Jamhari** — `JamhariComponent`: definisi hari + jam pelajaran per kelas (dipakai sistem baru). **Jampelajaran** — konfigurasi jam global (dipakai sistem lama).
- Tampilan jadwal per peran: **Jadwal Guru** (`JadwalGuruComponent`), **Jadwal Siswa** (`JadwalSiswaComponent`), panel beranda masing-masing.
- Pembacaan jadwal di portal terpusat lewat `JadwalSiswaService` (penempatan aktif siswa, semester aktif, tahun ajaran aktif) — tidak ada query jadwal duplikat di komponen portal.

### 2.5 Absensi

- **Absensi harian** (`AbsensiHarianComponent`, `AbsensiHarianService`): diisi guru per kelas/pertemuan; ada perintah audit (`AuditAbsensiHarian`, `AuditAbsensiParamManipulation`, `VerifyAbsensi*`) untuk verifikasi integritas data.
- **Absensi per jam pelajaran** (`AbsensiPerjamComponent`, `AbsensiPerjamService`): berbasis `jadwal_pertemuan` (snapshot publikasi jadwal) + `absensi_perjam`; mencatat kehadiran per pertemuan.
- Histori kehadiran siswa di portal siswa (`HistoriAbsensiSiswaComponent`): rekap harian per semester aktif dan riwayat per jam (berbasis snapshot, tidak dibatasi semester), dengan ekspor CSV rekap.

### 2.6 Penilaian & Rapor

- Input nilai per komponen (`InputNilaiComponent`, `NilaiSiswaService`, `NilaiSiswa`/`NilaiAkhir` model) berdasarkan KKTP.
- **Finalisasi rapor** oleh wali kelas (`FinalisasiRaporComponent`, `FinalisasiNilaiService`, `finalisasi_rapor`) — mengunci nilai akhir.
- **Koreksi nilai** lewat pengajuan (`KoreksiNilaiComponent`, `KoreksiPengajuan`) — bukan edit langsung atas nilai final.
- Rapor siswa (`RaporSiswaComponent`), rapor wali kelas (`RaporWaliKelasComponent`), catatan rapor (`catatan_rapor`), dan log perubahan nilai (`nilai_perubahan_log`).
- Portal wali: akses akademik **read-only** (`WaliAkademikComponent`) termasuk ringkasan kehadiran dan nilai sesuai relasi terverifikasi.

### 2.7 Pengumuman & Notifikasi

- Pengumuman (superadmin): draf → target (`semua`, `jurusan`, `kelas`, `siswa`) → publikasi atomik; snapshot penerima dibuat saat publikasi; status baca tercatat per penerima.
- Notifikasi **kanal database saja** (`NotifikasiService`, tabel `notifikasi` + `notifikasi_penerima`): bell di navbar (`NotificationBellComponent`), halaman notifikasi (`NotifikasiComponent`), notifikasi untuk guru/siswa/wali dibuat hanya lewat pemicu server-side (bukan form bebas di UI). Penerima divalidasi per peran; superadmin bukan penerima otomatis. Tidak ada pengiriman email untuk notifikasi pada saat ini.

### 2.8 Portal Siswa

- Beranda siswa (ringkasan: jadwal hari ini, pengumuman, tagihan).
- Jadwal siswa (dengan filter hari, cetak), histori absensi (rekap harian + per jam) dengan ekspor CSV.
- Profil siswa (read-only), rapor siswa, pengumuman siswa.
- Pembayaran: `/bayartagihan` (pembayaran tagihan sendiri; online Snap atau gunakan saldo) dan `/bayar-publik` (tautan pembayaran pihak ketiga berbasis token publik, tanpa login — `BayarPublikComponent`).

### 2.9 Portal Wali Murid

- Pendaftaran dan verifikasi akun wali: `WaliAkunComponent` + antrian verifikasi (`WaliAntrianVerifikasiComponent`) oleh admin; setiap perubahan status akun tercatat di `waliakun_history` dengan **trigger immutable di level database** (`add_history_immutable_triggers`).
- Relasi wali ↔ siswa (`WaliRelasiComponent`, `WaliAkademikComponent`): pengajuan relasi, verifikasi admin, permintaan putus (`walimurid_permintaan_putus`) dengan histori `walimurid_history` (juga immutable via trigger).
- Wali hanya melihat data siswa yang relasinya **terverifikasi dan aktif**; tidak ada akses lintas siswa.
- Akses akademik read-only: kehadiran, nilai/rapor anaknya sendiri, pengumuman. Ekspor CSV rekap kehadiran wali tersedia.
- Pembayaran tagihan anak melalui `/bayartagihan` dengan peran pembayar `wali` (tercatat di `payment_attempt.payer_jenis`).

### 2.10 Keuangan Sekolah

Modul keuangan adalah bagian terbesar dan paling banyak di-audit. Yang tersedia:

**Konfigurasi & penagihan**

- **Tarif** (`TarifComponent`, `TarifService`): tarif per jenis (spp/ujian/seragam/buku/pendaftaran/insidental/lainnya), riwayat perubahan tarif (`tarif_riwayat`), diskon (`DiskonService`).
- **Beasiswa** (`BeasiswaComponent`): beasiswa + penerima (`beasiswa_siswa`) — membebaskan/memotong tagihan.
- **Tagihan** (`DataTagihanComponent`, `TagihanService`): penerbitan SPP massal (perintah `spp:terbit`, dijadwalkan bulanan) dan insidental; status `belum_dibayar | dibayar_sebagian | lunas | dibebaskan | dibatalkan`; denda keterlambatan (`denda`) dengan pengajuan pembebasan (`DendaPembebasanService`, `denda_pengajuan`); penyesuaian tagihan (`tagihan_adjustment`).
- **Pengingat tagihan** (`TagihanPengingat`, perintah `tagihan:pengingat`): H-7/H-3/H/H+1 lalu tiap 7 hari; berhenti untuk tagihan lunas/dibebaskan/dibatalkan; dijeda bila ada pembayaran manual pending yang menutup sisa tagihan.

**Pembayaran & ledger**

- **Pembayaran online** via Midtrans Snap (`MidtransGateway`, `PaymentService`): pembuatan *payment attempt* per tagihan, webhook `POST /webhook/midtrans` (signature SHA512 wajib + verifikasi server-side Get Status API + cross-check nominal), idempotent; perintah pengaman `pembayaran:cek-status` (tiap 5 menit) sebagai jaring pengaman status.
- **Pembayaran manual** (`PembayaranManualComponent`): input pembayaran tunai/transfer oleh admin; masuk status `pending` → diverifikasi/ditolak superadmin atau admin lain; histori verifikasi (`diverifikasi_oleh/pada`, alasan gagal).
- **Ledger** (`pembayaran`): satu baris per pembayaran terverifikasi; berisi provider (`midtrans`/`manual`/`saldo`/`koreksi`), nominal, metode, dan `payment_attempt_id` untuk pembayaran online. Ledger **tidak diedit/dihapus**; koreksi lewat pengajuan.
- **Alokasi** (`pembayaran_alokasi`): setiap pembayaran dialokasikan ke tagihan (mengurangi sisa) atau menjadi **saldo siswa** bila lebih bayar; alokasi `reversal-refund`/`reversal-koreksi` membalik alokasi asli tanpa menghapus histori.
- **Saldo siswa**: dipakai untuk membayar tagihan lain (`gunakanSaldo`) dengan alokasi otomatis (jatuh tempo terdekat dulu, pokok didahulukan); anomali (saldo negatif) dibatalkan eksplisit dan di-log.
- **Kuitansi** (`KuitansiService`): penerbitan kuitansi untuk pembayaran terverifikasi.
- **Rekonsiliasi keuangan** (`RekonsiliasiKeuanganComponent`, `RekonsiliasiKeuanganService`): 13 pemeriksaan konsistensi (attempt sukses tanpa ledger, alokasi melebihi nominal, status tagihan tidak cocok dengan alokasi, saldo negatif, kuitansi tanpa pembayaran valid, reversal melebihi alokasi, dll.). Hasil dan temuan tersimpan (`rekonsiliasi_hasil`, `rekonsiliasi_temuan`) dengan ekspor CSV (ringkasan dan temuan; anti formula-injection). Halaman read-only; tidak memperbaiki data otomatis.
- **Refund & koreksi** (`RefundKoreksiComponent`, `KoreksiKeuanganService`, `RefundDokumenService`): pengajuan oleh admin → persetujuan superadmin → reversal alokasi + (untuk koreksi) pembayaran pengganti ber-provider `koreksi`; dokumen refund berupa PDF (dompdf) dengan pengunduhan terkontrol (`RefundDokumenDownloadController`); self-approval ditolak; reversal tidak mengubah histori asli.
- **Dashboard keuangan** (`DashboardKeuanganComponent`): ringkasan tagihan, status pembayaran, dan kinerja tahun ajaran aktif.
- **Bukti pembayaran / dokumen**: unduh PDF bukti dan dokumen refund melalui controller download khusus (akses terkontrol per peran).

Alur keterkaitan: **Tarif → Beasiswa → Tagihan → (pembuatan) Payment Attempt → Verifikasi (webhook atau manual) → Ledger (pembayaran) → Alokasi → status tagihan → Rekonsiliasi**; penyimpangan ditangani lewat **Refund/Koreksi**. Detail di [bagian 7.9](#79-alur-keuangan-sekolah-end-to-end).

---

## 3. Peran Pengguna & Kontrol Akses

SIAKAD memakai **otorisasi berbasis level** pada kolom `users.level` (bukan Spatie Permission — paket tersebut tidak terpasang; lihat [bagian 17](#17-status-proyek--keterbatasan)). Pembatasan dilakukan berlapis:

1. **Middleware `check.level:...`** pada grup route (`CheckUserLevel`) — role yang tidak sesuai mendapat `abort(404)`.
2. **Middleware `menu.access`** (`MenuAccessMiddleware`) — memeriksa entri `usermenu` pengguna untuk nama route; tanpa entri → `abort(404)`.
3. **`abort_unless(...)`** di awal metode publik komponen Livewire (pola wajib, terutama komponen admin/superadmin).
4. **Scope kepemilikan** di service/query (mis. wali hanya data siswa yang relasinya terverifikasi; siswa hanya data dirinya; penerima notifikasi divalidasi).

| Peran | Tanggung jawab | Akses utama |
|---|---|---|
| **Superadmin** | Pengelola sistem & keuangan tertinggi: manajemen pengguna, menu, pengumuman, refund/koreksi (persetujuan), rekonsiliasi, pembebasan denda (dengan pengajuan), konfigurasi akademik (TA/semester, jurusan) | Semua modul, plus manajemen menu/pengguna dan persetujuan transaksi keuangan |
| **Admin** | Operasional: input pembayaran manual, pengajuan refund/koreksi, verifikasi akun wali, beasiswa, tagihan; **tidak** dapat menyetujui pengajuan sendiri (self-approval ditolak) | Keuangan operasional + modul manajemen tertentu; tidak mengubah konfigurasi sistem inti |
| **Guru** | Akademik: mengisi absensi, input nilai, wali kelas finalisasi rapor, melihat jadwal mengajar | Modul akademik yang terkait kelas/mapel yang diampu; portal guru |
| **Siswa** | Portal siswa: jadwal, histori absensi, rapor, tagihan dan pembayaran | Hanya data miliknya |
| **Wali** | Portal wali: memantau anak (read-only), membayar tagihan anak | Hanya siswa dengan relasi terverifikasi-aktif |
| `walikelas` | *(belum diimplementasikan; login ditolak secara default oleh `LoginBlockService`)* | — |

Catatan: level yang tidak dikenal ditolak secara default (safe default). Menu yang tampil di sidebar berasal dari tabel `usermenu` per pengguna — menu yang tidak ditugaskan tidak tampil dan route-nya tidak bisa diakses (404).

---

## 4. Teknologi

| Lapisan | Teknologi | Fungsi dalam proyek |
|---|---|---|
| Bahasa | PHP ^8.2 | Runtime |
| Framework | Laravel ^12.0 | MVC, routing, Eloquent, migration, queue/scheduler, Blade |
| UI interaktif | Livewire ^3.7 (+ `jantinnerezo/livewire-alert`) | Komponen full-page reaktif tanpa SPA terpisah |
| Frontend | Bootstrap 5 + CoreUI (aset offline di `public/`), Font Awesome offline, font DM Sans | Tampilan; **tidak** mengandalkan CDN untuk aset inti |
| Build frontend | Vite ^4 + `laravel-vite-plugin` + axios | Hanya untuk `resources/css/app.css` dan `resources/js/app.js` (opsional; aset utama sudah berupa file statis) |
| Database | MySQL (default Laragon/lokal) | Penyimpanan utama; database `templatedb` pada lingkungan pengembangan |
| Auth | Laravel Auth kustom (level), `mews/captcha` ^3.4 | Login + captcha; bukan starter kit |
| PDF | `barryvdh/laravel-dompdf` ^3.1 | Kuitansi, dokumen refund, bukti publikasi, ekspor PDF (menu/docs) |
| Excel | `phpoffice/phpspreadsheet` ^1.29 | Import/ekspor data (mis. import master, ekspor) |
| Log | `spatie/laravel-activitylog` ^4.12 | Activity log |
| Log viewer | `opcodesio/log-viewer` ^3.15 | Pembacaan log terkontrol |
| HTTP client | `guzzlehttp/guzzle` ^7.2 | REST ke Midtrans (lewat `Http` facade) |
| Testing | PHPUnit ^11.5 | Unit/Feature test (`tests/`) |
| Formatting | Laravel Pint ^1.0 | Code style |
| Lainnya | `laravel/sanctum` ^4.0 (terpasang, belum menjadi mekanisme API utama), `laravel/tinker` | — |

Tidak ada perubahan dependency tanpa persetujuan pemilik proyek (aturan `AGENTS.md`).

---

## 5. Arsitektur Sistem

**Gaya arsitektur:** Laravel monolith dengan **Livewire full-page components** sebagai lapisan presentasi yang stateful per request, lapisan **service** untuk aturan bisnis, dan **Eloquent models** untuk akses data.

```
Browser (Livewire) → Component (app/Livewire) → Service (app/Services) → Model (Eloquent) → MySQL
                                  ↑                         ↓
                           Middleware (auth,          Support classes
                           check.level, menu.access,  (app/Support),
                           cek.akun, force.password)   Events/Notifikasi (DB)
```

Tanggung jawab per lapisan:

- **Components** (`app/Livewire`, 70 komponen) — state UI, validasi input ringan, otorisasi awal (`abort_unless`), memanggil service. Contoh: `RekonsiliasiKeuanganComponent`, `PembayaranManualComponent`.
- **Services** (`app/Services`) — aturan bisnis: `PaymentService` (attempt → verifikasi → ledger → alokasi), `TagihanService`, `RekonsiliasiKeuanganService`, `AbsensiHarianService`, `NotifikasiService`, `WalimuridService`, dll. Termasuk `Import/*` untuk import massal dan `Payments/*` untuk gateway.
- **Support** (`app/Support`) — helper murni: `KonteksAkademik` (tahun ajaran/semester aktif), `Lov` (list-of-values), `Hari`, `NamaFile`, `HelpCenter`.
- **Models** (`app/Models`, 67) — Eloquent; relasi dan cast. Penamaan tabel lowercase tanpa plural, PK `id{tabel}`, FK `{tabel}_id{pk}`, `ON DELETE RESTRICT` (lihat `AGENTS.md`).
- **Middleware** — `CheckUserLevel` (`check.level`), `MenuAccessMiddleware` (`menu.access`), `CekAkunAktif` (`cek.akun`), `ForcePasswordChange` (`force.password`), `Authenticate`, plus middleware framework standar (CSRF, dll.).
- **Providers** — `AppServiceProvider` berisi **view composers** (sidebar & docs_sidebar) yang dieksekusi setiap request; jaga query tetap ringan.
- **Routes** (`routes/web.php`) — gabungan route eksplisit (dengan middleware per grup) **dan** pembuatan route dinamis dari tabel `appmenunew` (URL menu yang tidak ada di daftar eksplisit → `App\Livewire\{Ucfirst}Component`). Dynamic route inilah sebab nama route menu harus konsisten dengan komponen.
- **Notifikasi** — custom (tabel `notifikasi`, `notifikasi_penerima`), di-dispatch server-side, ditampilkan via bell di navbar; bukan Laravel Notifications bawaan.
- **Payment gateway** — abstraksi `PaymentGatewayInterface` + implementasi `MidtransGateway` (REST langsung, tanpa package tambahan). Webhook masuk ke `MidtransWebhookController` (server-to-server, tanpa session auth, CSRF dikecualikan; keamanan lewat signature + verifikasi server-side).
- **Ledger keuangan** — pola *append-only*: `payment_attempt` → (verifikasi) → `pembayaran` (ledger) → `pembayaran_alokasi` (`jenis`: `tagihan` | `saldo` | `reversal-refund` | `reversal-koreksi`). Sisa tagihan dan saldo dihitung dari alokasi; reversal menetralkan alokasi tanpa menghapus histori.

Prinsip yang dijaga: **read-only untuk pemeriksaan** (mis. rekonsiliasi tidak memperbaiki data), **semua mutasi keuangan lewat transaksi DB + `lockForUpdate`**, **idempotent** untuk webhook dan perintah scheduler, **history immutable** untuk tabel histori wali (trigger DB).

---

## 6. Struktur Direktori

```text
project-root/
├── app/
│   ├── Console/Commands/       # spp:terbit, tagihan:pengingat, pembayaran:cek-status, siakad:backup-db, audit absensi, dll.
│   ├── Http/
│   │   ├── Controllers/        # auth, webhook Midtrans, download (bukti/kuitansi/refund/import), controller scaffold
│   │   └── Middleware/         # check.level, menu.access, cek.akun, force.password, dll.
│   ├── Livewire/               # 70 komponen full-page (admin, guru, siswa, wali, keuangan, docs)
│   ├── Models/                 # 67 model Eloquent
│   ├── Providers/              # AppServiceProvider (view composers sidebar), RouteServiceProvider, dll.
│   ├── Services/               # aturan bisnis: Absensi*, Keuangan/*, Payments/*, Import/*, akademik lainnya
│   ├── Support/                # helper: KonteksAkademik, Lov, Hari, NamaFile, HelpCenter, ...
│   ├── Traits/                 # FileUpload (juga di Http/Traits)
│   └── View/Components/        # komponen view support
├── bootstrap/                  # bootstrap aplikasi (Kernel klasik)
├── config/                     # app, auth, database, services (midtrans), siakad, livewire, ...
├── database/
│   ├── migrations/             # 89 migrasi (aditif; jangan migrate:fresh)
│   └── seeders/                # 41 seeder (menu per paket + demo seeder)
├── public/                     # aset publik; css/main.css (CoreUI), fontawesome offline, favicon
├── resources/
│   ├── css/app.css             # input Vite (opsional)
│   ├── js/app.js               # input Vite (opsional)
│   └── views/
│       ├── assets/             # partial bersama: sidebar, navbar, style, akademik (design system sa-*)
│       ├── docs/artikel/       # artikel dokumentasi internal (48 entri)
│       ├── layout/             # mainlayout (dipasang via config livewire.php), dll.
│       ├── livewire/           # view tiap komponen (kebab-case)
│       └── ...
├── routes/web.php              # route eksplisit + route dinamis dari appmenunew
├── storage/app/tmp-uji/        # skrip uji sementara (dijalankan lalu dihapus; bukan tes permanen)
├── tests/                      # PHPUnit: ExampleTest (Feature & Unit) — belum ada tes khusus modul
├── AGENTS.md                   # aturan kerja proyek (wajib dibaca sebelum mengubah kode)
├── ROADMAP.md                  # riwayat sprint & status audit/UAT per tahap
├── artisan
└── composer.json / package.json / vite.config.js / phpunit.xml
```

**Tempat mencari kode (developer baru):**

- Halaman admin/guru/siswa/wali → `app/Livewire/*Component.php` + `resources/views/livewire/<kebab>.blade.php`.
- Aturan bisnis (absensi, tagihan, pembayaran, rekonsiliasi) → `app/Services/**`.
- Sidebar/menu per pengguna → `assets.sidebar` (Blade) + `AppServiceProvider::register()` (view composer) + tabel `usermenu`/`appmenunew`.
- Menambah menu → seeder (`*MenuSeeder.php`) mengikuti pola di `AGENTS.md`; menu baru harus punya komponen + URL unik.
- Migrasi schema → file baru di `database/migrations/` (aditif).

---

## 7. Alur Bisnis Inti

### 7.1 Login dan pengelolaan akses

1. Pengguna membuka `/` → view `login` → POST `/login` (`LoginController`): validasi username + password + **captcha**; cek blokir via `LoginBlockService` (mis. siswa harus aktif).
2. Sukses → middleware `cek.akun` (akun harus aktif) dan `force.password` (jika wajib ganti password) dilewati → redirect ke beranda sesuai peran.
3. Sidebar dibentuk dari `usermenu` (per pengguna) — pengguna hanya melihat menu yang ditugaskan.
4. Setiap route menu diproteksi `check.level` + `menu.access`; komponen tetap memproteksi diri (`abort_unless`).

### 7.2 Persiapan tahun ajaran dan semester

1. Superadmin membuat **Tahun Ajaran** dan **Semester** (`TahunajaranComponent`, `SemesterComponent`); satu semester aktif.
2. `KonteksAkademik`/`KonteksAkademikService` menjadi sumber tunggal tahun ajaran/semester aktif untuk seluruh modul.
3. Setelah data master siap, penempatan siswa ke kelas dilakukan per semester.

### 7.3 Pengelolaan data siswa dan kelas

1. Buat kelas (bisa per tingkat/jurusan; field `rombel`), mapel, dan jurusan.
2. Import/entry siswa dan guru; penempatan siswa ke kelas (`kelassiswa`) — menentukan rombel aktif yang dipakai jadwal, absensi, dan keuangan (penagihan per siswa).
3. Promosi/kenaikan kelas dilakukan lewat penempatan semester baru (tidak menghapus histori).

### 7.4 Penugasan guru dan jadwal

1. Guru dipetakan ke mapel (`gurumapel`) dan penugasan mengajar (`penugasan_mengajar`).
2. **Sistem baru (JadwalKelas):** konfigurasi `Jamhari` per kelas → susun jadwal → `JadwalKelasService` memvalidasi bentrok.
3. **Sistem lama (Jadwal):** susun jadwal → **publikasikan** → snapshot ke `publikasijadwal` (dipakai untuk absensi per jam). Tarik publikasi membatalkan snapshot.
4. Guru melihat jadwal mengajarnya (`JadwalGuruComponent`); siswa melihat jadwalnya (`JadwalSiswaComponent`); wali melihat jadwal anak (read-only).

### 7.5 Pengelolaan absensi

1. Guru membuka kelas → isi **absensi harian** (atau per jam). Per jam memakai snapshot `jadwal_pertemuan` (bukan jadwal berjalan) sehingga konsisten dengan yang sudah dipublikasikan.
2. `AbsensiHarianService`/`AbsensiPerjamService` memvalidasi keikutsertaan rombel dan menulis ke `absensi_harian`/`absensi_perjam`.
3. Rekap tampil di portal siswa (dengan filter semester aktif untuk harian; per jam = riwayat lengkap) dan dapat diekspor CSV.
4. Perintah audit tersedia untuk verifikasi massal (`audit:absensi-harian` dll.).

### 7.6 Penilaian dan penerbitan rapor

1. Guru menginput nilai per komponen (`InputNilaiComponent`) sesuai komponen yang ditetapkan per mapel (KKTP + `komponen_nilai`).
2. Wali kelas melakukan **finalisasi rapor** (`FinalisasiRaporComponent`) → `nilai_akhir` terkunci; perubahan setelah finalisasi melalui **pengajuan koreksi** (`KoreksiPengajuan`), bukan edit langsung; semua perubahan tercatat di `nilai_perubahan_log`.
3. Rapor dapat dilihat siswa (`RaporSiswaComponent`), wali kelas (cetak/`RaporWaliKelasComponent`), dan wali murid (read-only, `WaliAkademikComponent`).

### 7.7 Pengumuman dan notifikasi

1. Superadmin membuat pengumuman (draf) dengan target (`semua`/`jurusan`/`kelas`/`siswa`) → dipublikasikan atomik → snapshot penerima (`pengumuman_target`).
2. Penerima melihat di navbar (bell) dan halaman notifikasi; status baca tercatat (`pengumuman_baca`).
3. Notifikasi sistem (mis. verifikasi akun wali, pembayaran) dikirim lewat `NotifikasiService::buat()` oleh pemicu server-side; penerima divalidasi (guru/siswa/wali saja).

### 7.8 Hubungan akun wali murid dengan siswa

1. Wali mendaftar/aktivasi akun → status menunggu verifikasi.
2. Admin memproses di **antrian verifikasi wali** (`WaliAntrianVerifikasiComponent`): terima/tolak; setiap transisi tercatat di `waliakun_history` (immutable — trigger DB).
3. Wali mengajukan relasi ke siswa (NIS) → admin memverifikasi → relasi aktif; wali mengajukan **putus relasi** bila diperlukan (`walimurid_permintaan_putus` + `walimurid_history`).
4. Seluruh akses wali ke data siswa disaring oleh relasi terverifikasi-aktif (scoping di query/service).

### 7.9 Alur keuangan sekolah end-to-end

```text
Tarif ──► Beasiswa ──► Tagihan ──► [Payment Attempt] ──► Verifikasi ──► Ledger ──► Alokasi ──► Status Tagihan
(master)   (diskon/      (spp:terbit      (online: Snap /      (manual: admin/      (pembayaran)  (tagihan/    (lunas/
            beasiswa)     atau manual)      atau manual: input)  atau webhook)                      saldo/         sebagian/
                                                                                                      reversal)      dll.)
                                                                              │
                                                                              ▼
                                                                    Rekonsiliasi (13 pemeriksaan)
                                                                              │
                                                                              ▼
                                                                   Refund / Koreksi (reversal +
                                                                   pengganti provider 'koreksi')
```

Penjelasan:

1. **Tarif** menetapkan nominal per jenis; **beasiswa** dapat menggugurkan/memotong kewajiban penerima (`beasiswa_siswa`).
2. **Tagihan** diterbitkan massal (SPP bulanan; perintah `spp:terbit`, tanggal 1 00:30, idempotent) atau insidental oleh admin; status mengikuti alokasi.
3. **Pembayaran manual**: admin mencatat → status `pending` → diverifikasi (sukses) atau ditolak (dengan alasan) oleh admin/superadmin → ledger dibuat saat verifikasi.
4. **Pembayaran online**: sistem membuat **payment attempt** (`status: created → pending → success|failed|expired`); Snap token diberikan ke pembayar; **webhook Midtrans** memverifikasi (signature wajib) kemudian memverifikasi ulang ke Get Status API, mencocokkan nominal, lalu menulis ledger **hanya** untuk transaksi terverifikasi; webhook idempotent (attempt `success` tidak diproses ulang). Perintah `pembayaran:cek-status` (tiap 5 menit) adalah jaring pengaman bila webhook terlewat.
5. **Ledger → Alokasi**: pembayaran dialokasikan ke tagihan sebesar sisa tagihan; kelebihan menjadi **saldo siswa**. Sisa tagihan = `total_tagihan − (Σ alokasi tagihan − Σ reversal)`. Status tagihan diperbarui (`dibayar_sebagian`/`lunas`).
6. **Rekonsiliasi**: pemeriksaan periodik atas konsistensi (13 jenis pemeriksaan) — **read-only**, hasil tersimpan, ekspor CSV; tidak memperbaiki data.
7. **Refund/Koreksi**: pengajuan (admin) → persetujuan superadmin → alokasi asli dinetralkan lewat `reversal-refund`/`reversal-koreksi`; untuk koreksi dibuat pembayaran pengganti ber-provider `koreksi`; self-approval ditolak; histori asli tidak diubah; dokumen refund berupa PDF.
8. **Pengingat**: `tagihan:pengingat` mengirim pengingat terjadwal (halaman guru/admin dapat melihat; untuk pengiriman ke pihak luar, lihat [bagian 14](#14-scheduled-tasks) dan [bagian 17](#17-status-proyek--keterbatasan)).

**Perbedaan status attempt vs status tagihan.** `payment_attempt.status` menggambarkan *upaya pembayaran* (dibuat/menunggu/berhasil/gagal/kadaluarsa) — attempt tidak mengurangi sisa tagihan. `tagihan.status` menggambarkan *kewajiban yang sudah atau belum terpenuhi* dan hanya berubah lewat ledger+alokasi. Keduanya independen: attempt `pending` tidak mengubah tagihan; tagihan `lunas` berarti alokasi (bukan attempt) sudah menutup kewajiban.

---

## 8. Gambaran Database

- **MySQL** (default pengembangan: `templatedb` di Laragon). Penamaan: tabel lowercase tanpa plural, PK `id{tabel}` (mis. `idsiswa`), FK `{tabel}_id{pk}` (mis. `siswa_idsiswa`), `ON DELETE RESTRICT`. Charset default koneksi MySQL: `utf8mb4`.
- **Migrasi aditif saja** — jangan `migrate:fresh`/`reset`/`db:wipe`; lihat `AGENTS.md`.
- **Tidak ada ERD terdokumentasi**; relasi dapat ditelusuri dari FK di migrasi dan `app/Models` (relasi Eloquent). Tabel dikelompokkan per domain:

**Pengguna & keamanan:** `users`, `rolecat`, `role`, `appmenucat`, `appmenunew`, `usermenu`, `activity_log` (spatie).
**Akademik (master & operasional):** `tahunajaran`, `semester`, `jurusan`, `mapel`, `guru`, `siswa`, `kelas`, `kelassiswa`, `gurumapel`, `penugasan_mengajar`, `kktp_mapel`, `komponen_nilai`, `nilai_siswa`, `nilai_akhir`, `finalisasi_rapor`, `catatan_rapor`, `nilai_perubahan_log`, `pengampu` (legacy).
**Jadwal & absensi:** `polajam`, `polajamhari`, `jampelajaran` (legacy), `jamhari`, `jadwal`, `publikasijadwal`, `jadwalkelas`, `jadwal_pertemuan`, `absensi_harian`, `absensi_perjam`.
**Pengumuman & notifikasi:** `pengumuman`, `pengumuman_target`, `pengumuman_baca`, `notifikasi`, `notifikasi_penerima`.
**Wali murid:** `walimurid`, `walimurid_permintaan_putus`, `waliakun_history`, `walimurid_history` (history immutable via trigger DB).
**Keuangan:** `tarif`, `tarif_riwayat`, `beasiswa`, `beasiswa_siswa`, `tagihan`, `tagihan_adjustment`, `denda`, `denda_pengajuan`, `tagihan_pengingat`, `payment_attempt`, `pembayaran`, `pembayaran_alokasi`, `kuitansi`, `refund_pengajuan`, `refund_dokumen`, `koreksi_pengajuan`, `rekonsiliasi_hasil`, `rekonsiliasi_temuan`.
**Dokumentasi internal:** `docscat`, `docs`.

Relasi penting: `kelassiswa` (siswa ↔ kelas per semester), `jadwal_pertemuan` (snapshot publikasi ↔ pertemuan), `pembayaran → pembayaran_alokasi → tagihan`, `payment_attempt → pembayaran` (1:1 saat sukses), `walimurid` (user wali ↔ siswa + status verifikasi).

Perubahan schema selalu melalui migrasi baru; seeder mengikuti pola di `AGENTS.md` (menu di-seed per paket peran; seeder cleanup berjalan setelah seeder pembuat menu).

---

## 9. Instalasi & Pengembangan Lokal

**Prasyarat:** PHP ^8.2, Composer 2, MySQL/MariaDB, Node.js + npm (opsional, hanya bila mengubah aset Vite), ekstensi PHP umum (mbstring, openssl, pdo_mysql, gd, zip, intl, bcmath). Laragon atau XAMPP cocok untuk pengembangan.

```bash
# 1. Clone
git clone <url-repository> siakad
cd siakad

# 2. Dependency PHP
composer install

# 3. Environment
cp .env.example .env
#   isi DB_*, APP_URL, dan kredensial lain seperlunya (jangan komit .env)
php artisan key:generate

# 4. Database (buat database kosong terlebih dahulu, mis. siakad / templatedb)
#    edit DB_DATABASE di .env, lalu:
php artisan migrate

# 5. Seeder (menu per paket peran + akun admin awal + data contoh bila diinginkan)
php artisan db:seed
#    DemoSeeder tersedia (DemoSiakadSeeder, dll.) untuk data contoh — JANGAN dipakai di data nyata

# 6. Aset frontend (opsional)
npm install
npm run build

# 7. Storage link (bila memakai unggahan file ke storage/app/public)
php artisan storage:link

# 8. Jalankan
php artisan serve
# Buka http://127.0.0.1:8000 — login dengan akun yang dibuat seeder
```

Catatan:

- `php artisan route:cache` **tidak dapat dipakai** karena `routes/web.php` berisi closure; gunakan `config:cache`, `view:cache`, dan biarkan route dinamis bekerja.
- `php artisan migrate --pretend` dianjurkan sebelum migrasi di environment bersama; jangan pernah `migrate:fresh` pada data nyata.
- Seeder membuat akun admin awal (lihat `DatabaseSeeder`); **ganti kata sandi** pada instalasi nyata.
- Lingkungan pengembangan referensi memakai Laragon + MySQL dan database `templatedb` (bukan nilai wajib).
- Mail dan queue tidak wajib (notifikasi kanal database; queue default `sync`).

---

## 10. Konfigurasi

Variabel environment (nama saja; lihat `.env.example` — **jangan** menuliskan nilai rahasia di dokumentasi publik):

| Kelompok | Variabel | Catatan |
|---|---|---|
| Aplikasi | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | `APP_ENV=production`, `APP_DEBUG=false` di produksi |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Default `mysql`; charset utf8mb4 |
| Session/Cache | `SESSION_DRIVER` (default `file`), `SESSION_LIFETIME` (menit), `CACHE_DRIVER` (default `file`), `QUEUE_CONNECTION` (default `sync`), `FILESYSTEM_DISK` | Tanpa Redis; semua default aman untuk satu server |
| Mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Tersedia di konfigurasi; **belum dipakai** untuk pengiriman notifikasi |
| Midtrans | `MIDTRANS_MODE` (`sandbox` default / `production`), `MIDTRANS_MERCHANT_ID`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` | Baca `config/services.php`. Bila server/client key kosong → gateway nonaktif (`isConfigured() === false`); transaksi tidak dibuat |
| Captcha | (lihat `config/captcha.php`) | Dipakai pada form login |
| Log | `LOG_CHANNEL`, `LOG_LEVEL`, `LOG_DEPRECATIONS_CHANNEL` | Default `stack`/`debug`; produksi: `error` |
| SIAKAD | `config/siakad.php` (mis. paket default), `config('siakad.backup.enabled')` | Backup terjadwal nonaktif default |
| Vite | `VITE_APP_NAME`, kredensial Pusher/Redis (tidak dipakai aktif) | — |

Catatan keamanan: `.env` tidak boleh dibaca/ditampilkan/di-commit (sudah `.gitignore`); semua kredensial hanya lewat environment. Tidak ada kredensial di kode (lihat `MidtransGateway` — config-only).

---

## 11. Integrasi Pembayaran (Midtrans)

**Arsitektur.** `PaymentGatewayInterface` → `MidtransGateway` (REST via `Http` facade; tidak menambah package). Konfigurasi tunggal `config('services.midtrans')`; kosong = gateway nonaktif (fail-safe; tidak pernah membuat transaksi).

**Alur Snap (implementasi).**

1. `PaymentService::buatAttempt()` — dalam transaksi DB + `lockForUpdate` pada tagihan: validasi sisa > 0 dan status tagihan dapat dibayar → buat `PaymentAttempt` (`order_id` unik, `public_token`, status `created`) → panggil `createSnapTransaction()` (`POST /snap/v1/transactions`, Basic Auth server_key) → simpan `snap_token`, `snap_redirect_url`, `payload_request`, `expired_pada` (fallback 24 jam).
2. Pembayar membayar di halaman Snap (embedded atau redirect).
3. **Webhook** `POST /webhook/midtrans` (`MidtransWebhookController`): payload divalidasi; **signature SHA512** (`order_id+status_code+gross_amount+server_key`) diverifikasi dengan `hash_equals`; lalu attempt dicari; lalu **Server-side Get Status API** (`GET /v2/{order_id}/status`) menjadi sumber kebenaran; nominal dicocokkan dengan payload dan attempt; hanya setelah itu `settle()` menulis ledger + alokasi dalam 1 transaksi ber-`lockForUpdate`. Payload disimpan dengan whitelist (tanpa data kartu/VA mentah).
4. **Idempotensi**: attempt yang sudah `success` dikembalikan sebagai `duplicate`; ledger maksimal satu baris per attempt (`payment_attempt_id` unik); posting alokasi diproteksi (tidak ganda).
5. **Pengaman**: `pembayaran:cek-status` (setiap 5 menit) — memeriksa ulang attempt `created/pending` via Get Status API (tanpa-op bila Midtrans tidak dikonfigurasi; tidak menggagalkan scheduler).
6. **Mapping status**: `capture` (fraud_status ≠ challenge) & `settlement` → `success`; `pending` → `pending`; `deny`/`cancel` → `failed`; `expire` → `expired`; status lain (refund/chargeback/dll.) → `ignored` (dicatat; tindak lanjut manual — **tidak** menyentuh ledger).

**Verifikasi server-side selalu** dilakukan — webhook tanpa verifikasi server-side tidak pernah memposting ledger, meskipun signature valid.

**Status sandbox/produksi (jujur):** implementasi lengkap, tetapi **BELUM PERNAH diuji ke Midtrans** — tidak ada kredensial merchant; pengujian dilakukan dengan `Http::fake()` (HTTP palsu) dan UAT sintetis. `MIDTRANS_MODE=sandbox` (default) dan `production` tersedia di konfigurasi; menaikkan ke `production` tanpa UAT akan berisiko. Refund via API (`provider_api`) sengaja diblokir; refund diproses manual melalui pengajuan + reversal. Pergantian key/aktivasi merchant adalah tanggung jawab pemilik sekolah/penyedia sistem.

**Pembayaran pihak ketiga (`/bayar-publik`).** Tautan publik berbasis `public_token`; pembayar tidak login; tetap melalui alur attempt yang sama; nama pembayar wajib diisi.

---

## 12. Keamanan & Integritas Data

- **Otentikasi:** Auth kustom (bukan starter kit); password di-hash (bcrypt/argon2 sesuai konfigurasi `Hash`); captcha pada login; `LoginBlockService` (status data induk menentukan boleh/tidak login).
- **Otorisasi:** `check.level` (404 bila bukan level diizinkan), `menu.access` (404 bila menu tidak ditugaskan), `abort_unless` di komponen; scoping kepemilikan di service (wali↔siswa terverifikasi, siswa↔diri, guru↔kelas/mapelnya).
- **CSRF:** aktif untuk semua POST web; **dikecualikan** untuk `webhook/midtrans` (server-to-server) — keamanan lewat signature + verifikasi server-side.
- **Transaksi DB:** seluruh mutasi keuangan/absensi/nilai membungkus `DB::transaction` + `lockForUpdate` pada baris kritis (tagihan, attempt, siswa).
- **Idempotensi:** webhook Midtrans (duplicate → tanpa efek), settlement, posting alokasi, perintah scheduler (`spp:terbit`, `tagihan:pengingat`) idempotent.
- **Integritas keuangan:** ledger tidak diedit; koreksi lewat reversal (`reversal-refund`/`reversal-koreksi`) + pengganti (`provider=koreksi`); self-approval ditolak; audit trail (`activity_log` + log aplikasi) untuk operasi penting (rekonsiliasi dijalankan, dsb.).
- **Immutable history:** `waliakun_history`/`walimurid_history` dilindungi trigger database (tidak bisa UPDATE/DELETE).
- **Anti injection:** ekspor CSV menetralkan sel berawalan `= + - @`; prepared statement via Eloquent; validasi backend di setiap komponen (bukan hanya frontend).
- **Captcha & rate limiting:** captcha wajib saat login; `ThrottleRequests` tersedia di grup `api` (belum menjadi mekanisme utama).
- **Header/SSL:** WAF/HTTPS ditangani di level web server — lihat [bagian 15](#15-catatan-deployment).

---

## 13. Pengujian & QA

**Automated (kondisi aktual):**

- PHPUnit ^11.5; `tests/Feature/ExampleTest.php` dan `tests/Unit/ExampleTest.php` (2 tes, skema skeleton). **Tidak ada tes modul khusus di `tests/`.**
- Pengujian terarah per sprint berjalan sebagai skrip sementara di `storage/app/tmp-uji` (transaksi + rollback, lalu dihapus) dan lewat **UAT browser** (Edge headless/CDP) pada basis data sintetis. Riwayat: UAT penilaian (2B.13/25), UAT refund/koreksi/rekonsiliasi Sprint 7.2 — 42/42 langkah PASS pada DB sintetis `templatedb_uji_72`.
- `php artisan test` → 2 passed (baseline).
- `php vendor/bin/pint --test <file>` untuk style; `php artisan view:cache` untuk verifikasi compile Blade seluruh view.

**Manual/UAT:** skenario bisnis diverifikasi manual per sprint (login, verifikasi wali, alur keuangan dari tagihan sampai rekonsiliasi). Tidak ada Selenium/Playwright permanen di repo.

**Keterbatasan (jujur):**

- Tidak ada tes otomatis modul di `tests/` — regresi dicakup oleh UAT terarah dan audit manual, bukan suite otomatis.
- Midtrans belum teruji end-to-end (tanpa kredensial).
- Performa belum diukur pada data skala sekolah besar (batas 500 temuan per kategori di rekonsiliasi adalah pengaman, bukan pengganti pengukuran).
- Tidak ada bukti penggunaan produksi oleh sekolah.

---

## 14. Scheduled Tasks

Didefinisikan di `app/Console/Kernel.php` (Laravel Scheduler):

| Perintah | Jadwal | Fungsi |
|---|---|---|
| `spp:terbit` | bulanan, tanggal 1, 00:30 (zona aplikasi, default `Asia/Jakarta`) | Menerbitkan tagihan SPP massal; idempotent |
| `tagihan:pengingat` | harian, 07:00 | Pengingat tagihan: H-7, H-3, H, H+1, lalu tiap 7 hari; idempotent; berhenti untuk lunas/dibebaskan/dibatalkan |
| `pembayaran:cek-status` | setiap 5 menit | Jaring pengaman status attempt Midtrans (no-op terlog bila gateway belum dikonfigurasi) |
| `siakad:backup-db` | harian, 02:30 | Backup DB; **nonaktif default** (`config('siakad.backup.enabled') !== true`) |

**Cara mengaktifkan di server** (wajib agar terjadwal berjalan):

```cron
* * * * * cd /path/ke/siakad && php artisan schedule:run >> /dev/null 2>&1
```

(Windows Task Scheduler: perintah yang sama setiap menit.) `php artisan schedule:list` memeriksa daftar terdaftar. Konfigurasi di kode **tidak** berarti scheduler aktif di server — verifikasi `schedule:list` dan cron entry di environment deployment. Catatan: perintah audit (`audit:absensi-harian`, `verify-absensi-*`, dsb.) tidak dijadwalkan; dijalankan manual.

---

## 15. Catatan Deployment

1. **Environment:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain (https); database produksi terpisah dari DB pengembangan; kredensial hanya di `.env` server (jangan komit).
2. **Kode & dependensi:** `composer install --no-dev --optimize-autoloader`; `php artisan key:generate` hanya sekali (per instance).
3. **Database:** buat DB kosong → `php artisan migrate --pretend` (tinjau) → `php artisan migrate --force` → `php artisan db:seed` (menu + akun awal). Jangan `migrate:fresh`.
4. **Cache:** `php artisan config:cache`, `php artisan view:cache`. **`route:cache` tidak bisa** (routes berisi closure).
5. **Storage:** direktori `storage/` dan `bootstrap/cache/` writable; `php artisan storage:link` bila memakai disk `public`.
6. **Scheduler & queue:** pasang cron/Task Scheduler `schedule:run` setiap menit (bagian 14); queue `sync` (tidak perlu worker) kecuali diubah.
7. **HTTPS:** wajib (login + data pribadi); TLS berakhir di web server (Nginx/Apache) atau reverse proxy; amankan header (HSTS) di web server.
8. **Midtrans production:** set `MIDTRANS_MODE=production` + key produksi **hanya setelah** UAT sandbox lulus; webhook URL produksi `https://<domain>/webhook/midtrans` didaftarkan di dashboard Midtrans; verifikasi `CekStatusPembayaran` berjalan.
9. **Backup:** aktifkan `siakad:backup-db` (set `config('siakad.backup.enabled')=true` + path eksternal) atau backup DB level server (mysqldump terjadwal); uji pemulihan secara berkala.
10. **Log & monitoring:** `LOG_CHANNEL=stack`, `LOG_LEVEL=error` di produksi; log-viewer hanya untuk superadmin di jaringan terpercaya; pantau disk (`storage/logs`), dan uptime endpoint `/login`.
11. **Hal yang belum tersedia/diverifikasi:** tidak ada CI/CD pipeline di repo; tidak ada healthcheck endpoint; tidak ada konfigurasi Docker/queue worker; backup off-site belum diaktifkan; `PROSEDUR_DEPLOYMENT_SCHEDULER_SIAKAD.md` (dirujuk di `ROADMAP.md`) tidak ada di repository ini.

---

## 16. Pengembangan Lanjutan

- **Baca `AGENTS.md` lebih dulu** — aturan kerja (migrasi aditif, pola menu/seeder, konvensi penamaan, pembatasan operasi git/database) mengikat seluruh perubahan.
- Ikuti struktur Laravel yang ada; jangan perkenalkan pola kedua untuk hal yang sama (mis. jangan query jadwal langsung — pakai `JadwalSiswaService`).
- **Otorisasi tidak boleh diturunkan**: komponen publik baru tetap `abort_unless(...)`; route baru masuk grup middleware yang sesuai; jangan `menu.access` ditambahkan untuk route yang memang harus publik (mis. webhook).
- **Integritas keuangan**: jangan mutasi ledger/alokasi; tulis reversal; gunakan service (`PaymentService`, `KoreksiKeuanganService`, `RekonsiliasiKeuanganService`) — jangan buat jalur tulis baru.
- **Migrasi schema**: file aditif; jangan sentuh migrasi lama; jalankan `--pretend` dulu.
- **UI**: ikuti design system `.sa-*` (`resources/views/assets/akademik.blade.php`) dan kelas Bootstrap/CoreUI yang sudah dipakai; komponen Livewire baru = class PascalCase + view kebab-case.
- **Pengujian**: perubahan penting (aturan bisnis keuangan, absensi, penilaian) sertakan pengujian terarah (skrip di `storage/app/tmp-uji` dengan transaksi+rollback lalu hapus, atau tes PHPUnit permanen dengan persetujuan pemilik).
- **Dokumentasi**: perbarui README/`ROADMAP.md` bila menambah modul, route, atau perintah; catatan implementasi per sprint diletakkan sebagai laporan terpisah (pola `LAPORAN_*.md`), bukan di README.
- **Kualitas kode**: `php vendor/bin/pint <file>` untuk file yang diubah; `php artisan view:cache` setelah mengubah Blade; jangan menambah dependency tanpa izin.
- **Git**: jangan commit/push/reset/stash tanpa persetujuan eksplisit pemilik; periksa `git status` dan `git diff` dulu.

---

## 17. Status Proyek & Keterbatasan

**Terimplementasi dan diuji (internal/sintetis):** portal siswa & wali (termasuk verifikasi akun wali + immutable history), absensi harian & per jam, penilaian + finalisasi rapor + koreksi, pengumuman & notifikasi, modul keuangan lengkap sampai rekonsiliasi (Sprint 1–7.x, UAT terarah lulus pada DB sintetis), scheduler tagihan, import master data.

**Terimplementasi, BELUM teruji end-to-end:** Midtrans Snap (tidak ada kredensial; HTTP palsu untuk uji unit; belum ada satu pun transaksi nyata/sandbox). `provider_api` refund sengaja diblokir.

**Keterbatasan yang diketahui:**

- Tidak ada bukti penggunaan produksi oleh sekolah; "selesai dikembangkan" ≠ "siap produksi" untuk seluruh sistem.
- Suite tes otomatis minimal (2 tes skeleton); QA bergantung pada UAT terarah/manual.
- Scheduler aktif di kode, tetapi aktivasi cron/Task Scheduler di server deployment harus dilakukan dan diverifikasi manual.
- `spatie/laravel-permission` **tidak terpasang**; scaffold `RolesComponent`/`PermissionsComponent`/`ModelHasRoles*`/`RoleHasPermissions*` dan alias middleware `role`/`permission` adalah artefak skeleton dan **tidak digunakan** (gunakan `check.level`/`menu.access`). Ada juga `Model_has_roles` dsb. di `app/Models` — ikut inert.
- Dua sistem jadwal (legacy `jadwal` + baru `jadwalkelas`) hidup berdampingan; jangan campur; legacy disembunyikan dari sidebar tapi route tetap aktif untuk superadmin.
- Backup otomatis nonaktif default; dokumen deployment yang dirujuk ROADMAP tidak ada di repo; root repo berisi skrip scratch pengembangan (`count.php`, `levels.php`, `templatedb.sql`, `tmp_patch.php`, dll.) yang bukan bagian aplikasi dan sebaiknya dikeluarkan/diabaikan.
- Notifikasi hanya kanal database; email belum dikirim.
- Rapor tidak memiliki jalur ekspor PDF (dompdf tidak dipakai di komponen rapor); belum ada bukti pencetakan fisik rapor di lingkungan sekolah.
- Akurasi rekonsiliasi tunduk pada batas 500 temuan per kategori (pengaman, bukan kebenaran penuh bila data besar).

**Yang dibutuhkan sebelum dipakai sekolah secara resmi:** UAT sandbox Midtrans lulus → aktivasi produksi; pengisian data master nyata; aktivasi scheduler + backup terverifikasi; pelatihan pengguna; buku panduan operasional; kebijakan retensi data/log; penetapan lisensi dan kepemilikan aset.

---

## 18. Lisensi & Kredit

**Lisensi belum ditetapkan.** `composer.json` mewarisi `"license": "MIT"` dari skeleton Laravel dan bukan deklarasi lisensi proyek. Repository tidak memuat `LICENSE`/`COPYING`. Hak cipta dan atribusi aset pihak ketiga (Bootstrap, CoreUI, Font Awesome, DM Sans — aset offline di `public/` dan `resources/views/docs`) mengikuti lisensi masing-masing; verifikasi sebelum distribusi.

**Kredit:** dibangun di atas Laravel (MIT), Livewire (MIT), CoreUI (MIT), Bootstrap (MIT), Font Awesome Free (CC-BY/SIL OFL/MIT sesuai bagian). Kontributor tercantum di riwayat Git; keputusan arsitektur terdokumentasi di `AGENTS.md` dan `ROADMAP.md`.
