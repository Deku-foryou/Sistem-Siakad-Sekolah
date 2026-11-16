# Laporan Penyusunan README.md — SIAKAD

Tanggal: 16 November 2026
Output: `README.md` (ditulis ulang; sebelumnya skeleton Laravel)
Cakupan: README utama repository; file dokumentasi lain tidak diubah.

## 1. Sumber informasi (audit)

- `AGENTS.md` (aturan kerja, konvensi, dual jadwal system, urutan/pola seeder, tabel menu)
- `composer.json` + `composer.lock` (dependency aktual; PHPUnit 11, Livewire 3, Midtrans via guzzle/Http, dompdf, phpspreadsheet, activitylog, log-viewer; `spatie/laravel-permission` **tidak terpasang**)
- `package.json`, `vite.config.js`, `.env.example` (nama variabel saja; `.env` **tidak dibaca**)
- `config/`: `livewire.php` (layout `layout.mainlayout`), `services.php` (blok `midtrans`, default sandbox, key kosong = gateway nonaktif), `siakad.php`, `database.php` (charset connection default `utf8mb4`), `captcha.php`
- `routes/web.php` (232 baris ekuivalen, ±95 deklarasi route eksplisit + loop dinamis dari `appmenunew`) dan `php artisan route:list` (110 entri, disimpan sementara sebagai bukti)
- `app/`: Models (67), Livewire (70 komponen), Services (absensi, keuangan, payments, import, akademik), Support (KonteksAkademik, dll.), Http (Controllers, Middleware: `CheckUserLevel`→404, `MenuAccessMiddleware`→404 via `usermenu`, `CekAkunAktif`, `ForcePasswordChange`, `VerifyCsrfToken` dengan pengecualian `webhook/midtrans`), Console (kernel + 12 perintah; signature diverifikasi satu per satu), Providers (`AppServiceProvider` — view composers sidebar/docs_sidebar)
- `database/migrations/` (89 file), `database/seeders/` (41 seeder)
- `ROADMAP.md` (status sprint/UAT; konfirmasi Midtrans Sandbox BLOCKED — tidak ada kredensial; uji `Http::fake()` saja)
- Verifikasi langsung: `LoginBlockService` (blokir per level), `PaymentService` (alur attempt→webhook→settle→alokasi, `lockForUpdate`, idempotensi), `MidtransGateway` (REST, signature SHA512, server-side Get Status), `NotifikasiService` (kanal database; penerima divalidasi; superadmin bukan penerima), `PengumumanService` (target `semua|jurusan|kelas|siswa`, snapshot penerima saat publikasi), `BerandaguruComponent` (beranda guru = akun + kelas wali), penggunaan `Pdf::` (rapor **tidak** memakai dompdf; kuitansi/refund/bukti publikasi/ekspor menu-docs memakai dompdf)
- Statistik repo: 149 file views, 48 artikel docs

## 2. Bagian README yang didokumentasikan

Seluruh 18 bagian sesuai permintaan: Overview, Fitur per Modul (keuangan dipecah: konfigurasi/penagihan vs pembayaran/ledger, termasuk alur Tarif→Beasiswa→Tagihan→Attempt→Verifikasi→Ledger→Alokasi→Rekonsiliasi→Refund/Koreksi), Peran & Kontrol Akses (level-based + `check.level`/`menu.access`/scoping), Teknologi, Arsitektur (lapisan + ledger append-only + route dinamis), Struktur Direktori (sesuai isi aktual, tanpa folder fiktif), Alur Bisnis Inti (7 alur + perbedaan status attempt vs status tagihan), Gambaran Database (per domain, tanpa ERD karangan), Instalasi, Konfigurasi, Integrasi Midtrans (jujur: belum pernah diuji end-to-end), Keamanan & Integritas Data, Pengujian & QA (jujur: 2 tes skeleton; UAT sintetis terarah), Scheduled Tasks (kernel + cara aktivasi cron), Deployment (termasuk catatan `route:cache` tidak bisa karena closure di `routes/web.php`), Panduan Pengembangan, Status & Keterbatasan, Lisensi (belum ditetapkan).

## 3. Verifikasi yang dilakukan

- `php artisan route:list` berjalan (tidak ada perubahan route); nama route yang disebut di README (`webhook/midtrans`, `bayar-publik/{token}`, `jadwalguru`, `jadwalsiswa`, `raporsiswa`, `docs`, `welcome-docs`, dll.) terverifikasi ada.
- Signature perintah Artisan diverifikasi dari sumber (`spp:terbit`, `tagihan:pengingat`, `pembayaran:cek-status`, `siakad:backup-db`, `audit:absensi-harian`, `audit:absensi-param-manip`, `verify:absensi-*`, `test:absensi-harian`, `verify:role-dashboard`).
- Penamaan/kolom migration dicek (pengumuman: `pengumuman`, `pengumuman_target`, `pengumuman_baca`); target pengumuman dari konstanta `Pengumuman::TARGET`.
- Anchor internal README dihitung dan dipetakan ke heading (slugify GitHub) — semua cocok.
- Tidak ada `.env` yang dibuka; tidak ada kredensial yang dituliskan (variabel Midtrans disebut namanya saja).
- Tidak ada perubahan source code, migrasi, route, middleware, atau dependency. `git status` setelah pekerjaan: hanya `README.md` yang dimodifikasi (plus file laporan ini).

## 4. Koreksi selama penulisan (temuan audit)

- `spatie/laravel-permission` tidak terpasang meskipun ada alias middleware `role`/`permission` dan komponen Roles/Permissions → didokumentasikan sebagai artefak skeleton yang inert, bukan bagian dari otorisasi aktif.
- `app/Notifications/` tidak ada (notifikasi custom via `NotifikasiService`); tree awal dikoreksi.
- Rapor tidak memakai dompdf (klaim awal dikoreksi setelah cek `Pdf::` per komponen).
- Target pengumuman adalah `semua|jurusan|kelas|siswa` (bukan "semua/siswa/guru/wali" seperti dugaan awal) — dikoreksi.
- Beranda guru berisi akun + kelas wali (bukan "jadwal hari ini") — dikoreksi.
- Claim "kolom boolean/status tanpa awalan is_" dihapus — bukan konvensi yang terdokumentasi di `AGENTS.md`.
- `PROSEDUR_DEPLOYMENT_SCHEDULER_SIAKAD.md` (dirujuk di `ROADMAP.md`) tidak ada di repository — dicatat di bagian Deployment.

## 5. Keterbatasan & catatan

- **Midtrans belum teruji end-to-end** — seluruh implementasi diverifikasi dari kode dan catatan UAT pada `ROADMAP.md` (bukan dari eksekusi gateway); README menyatakan ini secara eksplisit.
- **Tidak ada bukti penggunaan produksi oleh sekolah**; status "siap produksi" tidak diklaim.
- Detail 48 artikel `docs/artikel` tidak direview satu per satu; README hanya menyebut keberadaan modul Docs.
- Isi `templatedb.sql` (dump database di root) sengaja **tidak** dianalisis/terbaca (berpotensi memuat data); hanya dicatat sebagai file scratch yang sebaiknya tidak ikut distribusi.
- Keandalan UAT keuangan (42/42 pada `templatedb_uji_72`) berasal dari `ROADMAP.md`/laporan sprint, bukan eksekusi ulang pada sesi ini.
- `php artisan test` tidak dijalankan ulang untuk laporan ini (tidak ada perubahan kode); baseline terakhir: 2 passed.
- Inkonsistensi kecil yang diketahui di README (dibiarkan jujur, bukan disembunyikan): dokumen ini menyebut "67 model / 70 komponen / 89 migrasi / 41 seeder" — angka dihitung dari direktori, termasuk file legacy/scaffold.
