# ROADMAP — SIAKAD (Laravel 12 + Livewire 3)

**Status per 10 November 2026.** Disusun dari laporan-laporan proyek (`LAPORAN_*.md`) dan `AGENTS.md`. Tidak ada tanggal historis yang dikarang; sumber setiap klaim disebutkan.

Legenda: **Selesai** · **Sedang berjalan** · **Direncanakan** (butuh persetujuan) · **Membutuhkan keputusan pengguna**

---

## Selesai

### Tahap fondasi & Portal Siswa (2B.4.x)
- Beranda Siswa, Jadwal Siswa, Histori Absensi Siswa (+ ekspor CSV), Profil Siswa, Rapor Siswa, Pengumuman Siswa.
- Sumber: `AGENTS.md` (Portal Siswa & 2B.4.7F), implementasi aktual di `routes/web.php`.

### Tahap LOV reusable (P1) — 13 November 2026
- Registry whitelist `App\Support\Lov` (hanya kunci terdaftar) + `App\Services\LovService` (search ≥2 char, aktif-only, maks 20/50 hasil, dedup, validasi ulang `selected`/`selectedMany`) + trait `LovSiswa` + partial `partials/lov-siswa`.
- Pembayaran Manual: dropdown siswa → LOV (cari nama/NIS); reset tagihan saat ganti siswa; tagihan WAJIB milik siswa terpilih (fallback ke tagihan siswa lain yang keliru dihapus); `boot()` otorisasi per request.
- Pengumuman: multi-select siswa → multi-LOV (chips, dedup, aktif-only, validasi `Rule::exists+status aktif` di komponen dan service).
- Uji (rollback): 35 PASS / 0 FAIL; `php artisan test` 2 passed; browser UAT modal (pilih siswa, chips, reset tagihan) ✓.
- Sumber: `LAPORAN_AUDIT_LOV_SIAKAD.md`, `LAPORAN_IMPLEMENTASI_LOV_P1.md`.

### Tahap LOV P2 — Beasiswa assign & revoke — 13 November 2026
- Tombol "Kelola Penerima" per beasiswa → modal: info beasiswa, penetapan siswa via LOV (cari nama/NIS), daftar penerima (aktif + histori berhenti), aksi Cabut dengan konfirmasi.
- Integritas: siswa wajib AKTIF (validasi registry di `tambahPenerima`), ID palsu/non-aktif ditolak; duplikat penerima aktif ditolak; pencabutan hanya mengubah status (`berhenti` + `berlaku_sampai=hari ini`) — baris histori tidak dihapus; re-assign setelah revoke mengaktifkan kembali baris yang sama (unique beasiswa+siswa); `cabutPenerima` by primary key + anti-lintas-beasiswa.
- Otorisasi: `boot()` per request (superadmin/admin); aksi assign/cabut ditolak 403 untuk peran lain.
- Tagihan terbit & alokasi pembayaran tidak disentuh; diskon beasiswa hanya memengaruhi perhitungan masa depan (`beasiswaBerlaku`); tarif individu tetap mengesampingkan beasiswa (kebijakan tidak diubah).
- Uji (rollback): 37 PASS / 0 FAIL; `php artisan test` 2 passed; browser UAT (tambah beasiswa UJILOV → assign Andre Santoso → revoked, histori tampil) ✓.
- Sumber: `LAPORAN_IMPLEMENTASI_LOV_P1.md`, `LAPORAN_IMPLEMENTASI_LOV_P2.md`.

### Tahap LOV P3 — Tarif individu & Data Tagihan — 13 November 2026
- Tarif individu: dropdown siswa (load semua aktif) → LOV (cari nama/NIS); siswa nonaktif/palsu ditolak; edit modal menampilkan siswa terpilih; reset saat jenis berubah.
- Data Tagihan (scope siswa): inline query `Siswa::…->limit(500)` di Blade dihapus → LOV single mode; `updatedScope()` membersihkan state siswa saat cakupan diganti; validasi registry (aktif) + exists kelas/siswa sesuai cakupan.
- Uji (rollback): 34 PASS / 0 FAIL; `php artisan test` 2 passed; browser UAT (Tarif & Tagihan: cari `20260001` → Andre Santoso terpilih) ✓.
- Sumber: `LAPORAN_IMPLEMENTASI_LOV_P1.md`, `LAPORAN_IMPLEMENTASI_LOV_P3.md`.

### Tahap LOV P4 — Kelassiswa & icon picker — 13 November 2026
- Kelassiswa: picker siswa (`<select multiple>` limit 50, cari nama/NISN saja) → LOV multi (cari nama/NIS/NISN, aktif-only, maks 20, chips dedup); backend validation: siswa wajib AKTIF, penempatan non-pindah pada TA yang sama ditolak (replika filter UI lama), kelas harus sesuai TA; ganti TA/kelas + setelah simpan membersihkan state LOV; `boot()` otorisasi superadmin per request (route `check.level:superadmin` tidak diubah).
- Icon picker (`appmenunew`): pemindaian `File::files()` dari 1.395 ikon pada setiap render → cache `Cache::remember('appmenunew.icons.'.mtimeDir, 30 menit)` berisi array nama file saja; key memasukkan mtime direktori (invalidasi otomatis saat sumber berubah); view `icon-lov` tidak diubah.
- Uji (rollback): 52 PASS / 0 FAIL; browser UAT (Kelassiswa assign/keluarkan, icon picker + filter "angle") ✓.
- Sumber: `LAPORAN_IMPLEMENTASI_LOV_P4_P5.md`.

### Tahap LOV P5 — Pembersihan scaffold LOV — 13 November 2026
- 12 view `*-lov` dihapus (termasuk `users-lov` yang menampilkan kolom password/remember_token; `icon-lov` DIPERTAHANKAN); 2 trait mati (`PaginationLov`, `PaginationTrait`) dihapus; 11 properti `$searchlov`, 10 `updatingSearchlov()`, 9 method `xxxLOV()`, 5 placeholder `xxxLOV()` di HTTP controllers, komentar scaffold `@include`/JS dihapus; grep seluruh repo: 0 referensi.
- Catatan: komponen scaffold non-LOV (Permissions/Roles/RoleHasPermissions) dipertahankan — tabel tidak ada di DB & tidak di-route; butuh keputusan pemilik untuk penghapusan penuh.
- Sumber: `LAPORAN_IMPLEMENTASI_LOV_P4_P5.md`.

### Pengampu — audit & penyembunyian menu (legacy) — 13 November 2026
- Audit: fitur legacy (Jadwal lama); tidak ada modul aktif yang membacanya (nilai→`penugasan_mengajar`, jadwal→`jadwalkelas`; 0 hit di absensi/rapor/dashboard/keuangan); data `jadwal` kosong; route hidup & dipakai `JadwalComponent` + console audit; docs sudah menandai legacy.
- Keputusan: SEMBUNYIKAN MENU, PERTAHANKAN IMPLEMENTASI — `'pengampu'` ditambahkan ke `$menuDisembunyikanSuperadmin` (pola identik `/jadwal`); route `/pengampu` tetap hidup untuk superadmin via URL langsung; implementasi, tabel (18 baris), usermenu, dan data dipertahankan.
- UAT browser: sidebar superadmin tanpa "Pengampu"/"Jadwal (Lama)"/"Fitur Legacy"; menu lain utuh; `/pengampu` via URL 200 (superadmin); non-superadmin ditolak (halaman "Halaman Tidak Ditemukan", middleware tidak diubah).
- Sumber: `LAPORAN_AUDIT_FITUR_PENGAMPU.md`, `LAPORAN_IMPLEMENTASI_PENYEMBUNYIAN_MENU_PENGAMPU.md`.

### 2B.7 — Portal Wali Murid (lengkap)
- Akun wali (self-service), relasi wali–siswa, antrean verifikasi admin, pemutusan relasi (P5a langsung, P5b dengan persetujuan pihak).
- Sumber: `LAPORAN_IMPLEMENTASI_2B_7_5B.md`, `LAPORAN_AUDIT_PENUTUPAN_2B_7.md`, `LAPORAN_SEEDER_DEMO_SIAKAD.md`.

### 2B.8 — Akses akademik read-only untuk wali
- Nilai/rapor final, absensi harian & per jam, jadwal — di-scope relasi verified.
- Sumber: `LAPORAN_IMPLEMENTASI_2B_10.md`, `LAPORAN_AUDIT_PENUTUP_2B_10.md`.

### 2B.9 — Regression audit Portal Wali Murid
- 27/27 PASS (temporary, rollback).
- Sumber: `LAPORAN_IMPLEMENTASI_2B_9.md`.

### 2B.10 — Fitur akademik tambahan Portal Wali (P0) + hardening
- Tab Rekap, Ketidakhadiran, Catatan, Perkembangan pada `/wali-akademik`; lalu hardening P1/P2/P3; verifikasi akhir.
- Sumber: `LAPORAN_IMPLEMENTASI_2B_10.md`, `LAPORAN_AUDIT_PENUTUP_2B_10.md`, `LAPORAN_HARDENING_2B_10.md`, `LAPORAN_VERIFIKASI_AKHIR_2B_10.md`.

### 2B.11 — Export CSV rekap kehadiran wali + penetapan roadmap
- Action `unduhRekap()` pada `/wali-akademik`; CSV UTF-8 BOM, delimiter `;`, anti formula-injection; scope TA/semester aktif + kelas aktif + periode relasi.
- Sumber: `LAPORAN_IMPLEMENTASI_2B_11.md`.

### 2B.12 — Notifikasi Wali Murid — **LULUS (9 November 2026)**
- Keputusan resmi: seluruh D1–D10 disetujui; implementasi selesai dan diverifikasi (59 assertions, rollback bersih); Guru & siswa tidak mengalami regresi; belum termasuk UAT browser.
- Sumber: `LAPORAN_AUDIT_DESAIN_NOTIFIKASI_WALI_2B_12.md`, `LAPORAN_IMPLEMENTASI_2B_12.md`.

### 2B.13 — Audit Modul Penilaian + hardening K1/K2/K4 + race KKTP
- Audit read-only; tidak ada celah kritis/tinggi.
- **K1 selesai**: ownership service-level `RaporWaliKelasService`.
- **K2 selesai**: nama file PDF disanitasi (`App\Support\NamaFile`).
- **K3**: kop PDF tetap `app.name` (placeholder).
- **K4 Opsi B disetujui**: koreksi KKTP tidak memblokir input nilai.
- **Race KKTP selesai**: `lockForUpdate()` pada pembacaan `kktp_mapel` di finalisasi (transaksi yang sama); terverifikasi rollback + lock lintas koneksi.
- Sumber: `LAPORAN_AUDIT_MODUL_PENILAIAN_2B_13.md`, `LAPORAN_HARDENING_MODUL_PENILAIAN_2B_13.md`, `LAPORAN_HARDENING_RACE_KKTP_2B_13.md`.

### 2B.14 — Audit & UAT Otomatis Menyeluruh
- 287 assertions PASS; matriks akses 5 role tervalidasi; alur bisnis kritis terverifikasi (finalisasi, rapor, PDF/CSV, notifikasi, pengumuman, force password); docs ditemukan rusak (temuan).
- Sumber: `LAPORAN_UAT_OTOMATIS_MENYELURUH_2B_14.md`.

### 2B.27 — Pengamanan Backup, Secret, dan Data Lokal **SELESAI (AUDIT + REKOMENDASI; BELUM EKSEKUSI) (11 November 2026)**
- Audit read-only artefak backup & data lokal (tidak ada file dihapus/dipindahkan/diubah; tidak ada Git; tidak ada `.env`; tidak ada migration/seeder/token rotation).
- **Temuan CRITICAL:** `opcodesio/log-viewer` terpasang tanpa konfigurasi — di `APP_ENV=local` siapa pun yang menjangkau server dapat membaca seluruh log (berisi PII); port 80/443 mati saat audit, tetapi risiko berlaku saat aplikasi dijalankan.
- **Temuan HIGH:** dump penuh `storage/app/backup-2b75a/backup-2b75a-full-*.sql` (45 tabel, data nyata siswa/guru/wali + hash bcrypt `users`) tanpa enkripsi; `storage/logs/laravel.log` 27 MB (156.206 baris, 2023–2026, 1.917 error, berisi penanda PII/kredensial); `storage/debugbar/` 143 capture dev era 10/26 (SQL berisi nilai nyata).
- **templatedb.sql (root)** — 3 akun nyata (bcrypt) + 23 Sanctum token hashes (`wakibota-mobile`) — **bukan plaintext** (koreksi atas laporan 2B.26: klaim "token plaintext" tidak akurat; nilai adalah SHA-256 hash) — ter-gitignore.
- Akses publik bersih: tidak ada backup di `public/`; `.htaccess` standar; `robots.txt` terbuka (tidak ada data sensitif di publik); `storage/` di luar web root; `.gitignore` (root + storage) mencakup dump/log/upload.
- **10 tindakan remediasi** disusun (prioritas: C1 → H1 → H2 → H3 → revokasi token → H4 → M1/M2 → L1/L2).
- Sumber: `LAPORAN_PENGAMANAN_BACKUP_DAN_DATA_LOKAL_2B_27.md`.

### 2B.44 — Finalisasi Modul Keuangan SIAKAD MVP Sprint 7.4 **SELESAI (12 November 2026)**
- **Pembebasan denda dua tahap** (`/pembebasandenda`, admin+superadmin; guru/siswa/wali 403/404): admin/superadmin mengajukan (alasan wajib), superadmin menyetujui/menolak; superadmin tidak dapat menyetujui pengajuannya sendiri (backend tolak); histori + aktor + waktu + alasan + nominal tercatat (`denda_pengajuan`, `tagihan_adjustment` jenis `denda_dibebaskan`); pembebasan menurunkan `tagihan.total_tagihan`; tagihan status dihitung ulang (lunas/dibayar_sebagian/belum_dibayar); **tidak ada baris pembayaran dibuat**; idempotent (setujui kedua kali → tetap `terproses`); denda tertutupi pembayaran tidak dapat dibebaskan; denda pada tagihan lunas tidak dapat dibebaskan.
- **Scheduler diaktifkan** (`app/Console/Kernel.php`): `spp:terbit` (bulanan) + **`tagihan:pengingat` baru** (H-7, H-3, H, H+1, lalu setiap 7 hari; idempotent via `tagihan_pengingat`) + `pembayaran:cek-status` (Midtrans safety net, setiap 5 menit, no-op bila tidak dikonfigurasi). `php artisan schedule:list` memverifikasi ketiga tugas terdaftar. Dokumentasi cron/Task Scheduler: `PROSEDUR_DEPLOYMENT_SCHEDULER_SIAKAD.md`.
- **Reminder tagihan**: penerima = siswa (akun aktif) + wali verified aktif; berhenti saat lunas/dibebaskan/dibatalkan; dijeda bila pembayaran manual pending menutup sisa; idempoten (unique tagihan+tahap+penerima); notifikasi dikirim via NotifikasiService (jenis `pengingat_tagihan`).
- **Notifikasi keuangan diperbaiki**: `JENIS_LIST` diperluas (`keuangan`, `pengingat_tagihan`); notifikasi pembayaran manual (menunggu verifikasi/disetujui/ditolak) yang sebelumnya dilempar `DomainException` dan ditelan diam-diam kini benar-benar terkirim; catch diperbaiki untuk mencatat `Log::warning`.
- **Bukti pembayaran & dokumen refund untuk siswa**: `/bayartagihan` menampilkan tombol "Bukti" (route `pembayaran.bukti`) dan kartu "Dokumen Refund" (route `refund.dokumen`) — otorisasi server-side per transaksi (siswa pemilik/wali verified 200; siswa lain/wali pending/guru 403).
- Migrasi aditif `_000023_create_denda_pengajuan_table`, `_000024_create_tagihan_pengingat_table` **[Ran]** pada Laragon `templatedb` (aditif; tabel kosong). `CHECKLIST_PRA_PRODUKSI_SIAKAD.md` bagian scheduler diperbarui.
- Regression rollback pada `templatedb_uji_72`: **52 PASS / 0 FAIL** (denda dua tahap, refund, koreksi, reminder, notifikasi, rekonsiliasi 13 pemeriksaan 0 kritis, idempotensi, authorization). Browser UAT (Edge headless CDP + fresh re-seed DB `templatedb_uji_72`): **38/38 langkah PASS** (alur pembebasan denda end-to-end, akses dokumen 8 skenario, rekonsiliasi export CSV, mobile viewport, sidebar superadmin, otorisasi matriks 7 role).
- Midtrans Sandbox tetap **BLOCKED** (kredensial merchant belum tersedia; refund `provider_api` tetap diblokir; tidak ada klaim transaksi nyata). MVP VA permanen di luar scope (Snap per tagihan tetap dipakai).
- Sumber: `LAPORAN_FINAL_MODUL_KEUANGAN_SPRINT_7_4.md`.

### 2B.43 — Hardening Koreksi Transaksi F-1 Sprint 7.3 **SELESAI**
- **Implementasi kebijakan F-1** (disetujui pemilik): guard saldo negatif pada koreksi keuangan diubah dari **pra-posting menjadi post-condition** (`setujuiKoreksi()` di `KoreksiKeuanganService.php`): reversal+replacement di-posting dulu, saldo akhir dihitung (`saldoSiswa()`); bila akhir < 0 → transaksi penuh di-rollback + `DomainException`. Replacement mengembalikan kelebihan saldo ke siswa; saldo yang sudah terpakai tetap tidak dikembalikan otomatis.
- **Guard integritas baru dua arah** (ajukan & setujui): pembayaran yang sudah dikoreksi (replacement tersimpan) atau sudah direfund (dana keluar) **ditolak** koreksinya — menutup double-posting alokasi asli yang ditemukan saat audit F-1. Reversal hanya untuk `jenis` `tagihan`/`saldo` (tidak pernah membalik reversal).
- UAT browser (Edge CDP) **7/7 PASS** pada `templatedb_uji_72` (fresh re-seed): koreksi P-A2 (saldo terpakai) **terproses** dengan replacement 95.000 (`provider='koreksi'`, `referensi='KOREKSI-13'`); saldo siswa A 20.000→15.000 (`masuk=95.000, dipakai=30.000, reversal=50.000`); tagihan A2 `lunas`; tombol Koreksi hilang saat refundable habis; backend tetap menolak koreksi ganda.
- Regresi **29/29 PASS** anti-regresi 7.3; re-run regresi 7.2 **23/23 PASS**; `php artisan test` 2 passed; pint PASS; rekonsiliasi 13 pemeriksaan, tetap **0 temuan kritis**. Koreksi saldo akhir negatif (siswa C) → **rollback penuh**; bukti rollback terverifikasi (histori/alokasi/tagihan/saldo tidak berubah).
- Pesan koreksi 7.2 terkoreksi: skenario "saldo sudah terpakai" kini **terproses** (perubahan kebijakan F-1 yang disetujui), bukan ditolak.
- Tidak ada migration/package/perubahan kebijakan lain; database Laragon tidak berubah; `.env` tidak disentuh. Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_7_3.md`.

### 2B.42 — UAT Refund, Koreksi & Rekonsiliasi Sprint 7.2 **SELESAI (12 November 2026)**
- **UAT browser (Edge headless via CDP, tanpa package baru)** terhadap database terpisah **`templatedb_uji_72`** (klon skema Laragon `templatedb` + fixture sintetis UJI72; database Laragon **read-only**, tidak diubah selain migrasi aditif `_000022`): **42/42 langkah PASS** — pengajuan admin → persetujuan/proses superadmin (refund), penolakan refund, PDF dibuat & diunduh & checksum SHA256 cocok (`RF/2026/11/000001`), akses dokumen 8 skenario (admin/superadmin/siswa pemilik/wali verified = 200; siswa lain/wali pending/guru = 403; tamu diarahkan ke `/login`), koreksi midtrans & manual (terproses), guard saldo negatif menolak koreksi P-A2 ("saldo siswa akan menjadi negatif"), saldo & tagihan setelah reversal diverifikasi (A1 dibuka kembali; A4 dibayar_sebagian 90.000), admin tidak melihat tombol Setujui/Proses/Tolak, self-approval ditolak.
- Regresi transaksi `php` (rollback) pada test DB: **23 PASS / 0 FAIL** (idempotensi proses refund/koreksi/generateUlang PDF, duplikat request, konflik refund↔koreksi dua arah, histori asli identik, settlement attempt tidak berubah, rekonsiliasi 13 pemeriksaan 0 temuan kritis). `php artisan test` 2 passed; pint PASS.
- **BUG-01 (EUR-UI):** select `jenisKoreksi` memakai `wire:model` (deferred) → field `pencatatan_manual`/`hubungan_tagihan` tidak pernah muncul; diperbaiki → `wire:model.live`.
- **BUG-02 (EUR-UI, kritis UX):** rantai `$set(...); $dispatch(...)` di `wire:click` → segmen kedua dievaluasi Alpine tanpa `$set` global → `ReferenceError: $set is not defined` → handler batal; modal Tolak **tidak pernah terbuka** (Tolak refund/koreksi tidak bisa dipakai dari UI). Diperbaiki: aksi komponen tanpa argumen membaca `$selectedPengajuanId` saat klik (`pilihTolakRefund`, `pilihTolakKoreksi`, `pilihProsesRefund`, `prosesRefundTerpilih`, `tolakRefundTerpilih`, `tolakKoreksiTerpilih`).
- **BUG-03 (EUR-UI, kritis):** tombol modal memakai `{{ $selectedPengajuanId }}` interpolated saat render (nilai awal 0) → `prosesRefund(0)` → `No query results for model [RefundPengajuan] 0`. Diperbaiki bersama BUG-02 (state dibaca saat klik, bukan dari render awal).
- Rekonsiliasi pada data UAT pasca-flow: 13 pemeriksaan, **2 temuan** (`PEMBAYARAN_VERIFIED_TANPA_KUITANSI` — perlu_ditinjau; 2 pembayaran manual sintetis tanpa kuitansi), **0 kritis**.
- **Koreksi dokumentasi Sprint 7.1:** insiden "database hilang" terbukti keliru — instance Laragon (MySQL 8.0.30, `C:\laragon\data\mysql-8`) berisi `templatedb` utuh; XAMPP adalah instance terpisah kosong. Migrasi `_000022` diaplikasikan ke Laragon (aditif). Laporan Sprint 7.1 direvisi (§0, §8, §11 + ROADMAP 2B.41).
- `templatedb_uji_72` (DB UAT sintetis) **dibiarkan** untuk inspeksi; dapat di-drop oleh pemilik kapan pun (tidak dipakai aplikasi; bukan data nyata). Midtrans Sandbox tetap **BLOCKED**; `provider_api` tetap diblokir. Sumber: `LAPORAN_UAT_PEMBAYARAN_SPRINT_7_2.md`.

### 2B.41 — Hardening Refund & Koreksi Keuangan Sprint 7.1 **SELESAI (12 November 2026)**
- **Dokumen PDF refund resmi:** setiap refund terproses otomatis menerbitkan PDF (`pdf/refund.blade.php`) via `RefundDokumenService` **setelah** reversal tersimpan; disimpan di **private storage** (`pembayaran/refund/`); tabel aditif `refund_dokumen` (`_000022` **[Ran]**) menyimpan nomor `RF/YYYY/MM/NNNNNN` (unik, tidak dipakai ulang), **checksum SHA256 + ukuran file**, serta **snapshot sekolah + dokumen** (identitas siswa, pembayaran asli, nominal, metode, alasan, persetujuan, pemroses). Gagal PDF **tidak** mengulang reversal/refund; tombol **Generate PDF** (idempotent) tersedia untuk superadmin bila dokumen belum ada; route `refund/dokumen/{id}` (`refund.dokumen`) dengan otorisasi pola kuitansi (admin/superadmin, siswa pemilik, wali `verified` aktif; lainnya 403) + verifikasi integritas checksum pada setiap unduhan. Nomor dokumen idempotent (refund terproses tidak pernah dibuatkan nomor kedua).
- **Refund per sumber dana:** refundable = alokasi tagihan (belum pernah direfund/dikoreksi) + min(alokasi saldo belum direfund, saldo tersedia siswa). Konsumsi **saldo (kelebihan) lebih dulu**, lalu tagihan (urutan diubah dari Sprint 7 — kewajiban tagihan tidak terbuka selama kelebihan masih ada). Refund dari alokasi tagihan **tidak mensyaratkan saldo mencukupi**; saldo yang sudah dipakai membayar tagihan **tidak** dikembalikan; saldo tidak pernah negatif; refund parsial menghitung sisa per sumber; dua refund (bahkan pada pembayaran berbeda) bersaing sumber saldo digagalkan deterministik dengan `lockForUpdate` siswa + re-cek sumber **di dalam** transaksi (refund kedua ditolak tanpa membuat reversal). Koreksi & refund aktif pada pembayaran yang sama saling memblokir (dua arah).
- **Koreksi online & manual:** diizinkan untuk `midtrans`, `manual`, `saldo`, dan replacement `koreksi`; **tidak** menyentuh settlement Midtrans (attempt/status); histori + alokasi asli immutable (reversal + replacement); snapshot_sebelum diperkaya `sumber` (provider, order_id, provider_ref, payment_attempt_id, nominal); koreksi **idempotent** (koreksi terproses tidak diterapkan ulang); guard saldo negatif untuk reversal alokasi saldo; rekonsiliasi diexclude `provider='koreksi'` dari `LEDGER_ONLINE_TANPA_ATTEMPT` → replacement tidak menimbulkan anomali palsu.
- **Rekonsiliasi 13 pemeriksaan:** +`REVERSAL_TANPA_REFERENSI` (reversal tanpa referensi pengajuan), +`REVERSAL_MELEBIHI_ALOKASI` (reversal per sumber melebihi alokasi asli per pembayaran). 11 pemeriksaan Sprint 5/6 tetap benar dengan reversal/replacement/refund parsial-penuh/saldo terpakai (uji terarah: **0 temuan kritis/palsu** setelah seluruh skenario).
- Migrasi aditif `_000022_create_refund_dokumen_table` **[Ran]**. Pengujian rollback **116 PASS / 0 FAIL** (script dihapus setelah lulus; `php artisan test` 2 passed; pint PASS). Uji concurrency deterministik koneksi-terpisah (PDO A vs PDO B, `innodb_lock_wait_timeout=2`): **5 PASS** (lock siswa & tagihan eksklusif antar koneksi); bukan stress-test multi-proses. Browser UAT via HTTP: login + `/refundkoreksi` 200, seluruh marker halaman ditemukan, tanpa pesan error; console JS **belum diverifikasi** (tidak ada browser automation di lingkungan ini); alur end-to-end di browser belum diuji.
- **Catatan lingkungan (DIKOREKSI pada Sprint 7.2, 12 November 2026):** laporan Sprint 7.1 menyatakan database `templatedb` hilang — itu **keliru**. Aplikasi memakai instance Laragon (MySQL 8.0.30, `C:\laragon\data\mysql-8`, port 3306) yang berisi `templatedb` **utuh** (migrasi [Ran] termasuk `_000021`; 0 baris pembayaran/tagihan/refund; 12 siswa + 24 users). XAMPP MySQL yang dipakai pada Sprint 7.1 ternyata instance terpisah dengan data directory kosong. Migrasi `_000022_create_refund_dokumen_table` diaplikasikan ke database Laragon pada Sprint 7.2 (aditif, `refund_dokumen` [Ran], kosong). Catatan lengkap koreksi & bukti: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_7_1.md` §0 (revisi Sprint 7.2) dan `LAPORAN_UAT_PEMBAYARAN_SPRINT_7_2.md`.
- Midtrans Sandbox tetap **BLOCKED**; `provider_api` refund tetap diblokir. Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_7_1.md`.

### 2B.40 — Implementasi Refund & Koreksi Keuangan Sprint 7 **SELESAI (11 November 2026)**
- **Refund** `/refundkoreksi` (superadmin/admin; guru/siswa/wali ditolak backend): admin mengajukan (nominal, alasan, metode `transfer_manual`/`tunai`/`lainnya`/`provider_api`, referensi), superadmin menyetujui/menolak (alasan wajib; admin tidak dapat menyetujui pengajuan sendiri), superadmin memproses refund manual; sistem membuat **reversal alokasi** (tagihan lalu saldo), recompute tagihan, mencegah saldo negatif & refund ganda (lockForUpdate + idempotent proses), nomor dokumen `RF/YYYY/MM/NNNNNN` (urut reset tahunan). **Jumlah ganda dicek**, histori asli tidak berubah.
- **Koreksi keuangan** (jenis `nominal`/`alokasi`/`pencatatan_manual`/`hubungan_tagihan`): admin mengajukan (snapshot_sebelum JSON tersimpan), superadmin menyetujui → reversal alokasi asli + replacement pembayaran (provider='koreksi', order_id=null) melalui posting jalur normal; histori asli utuh; audit trail lengkap (pengaju, waktu, setuju, proses).
- Rekonsiliasi sudah **reversal-aware** (net alokasi = SUM(tagihan) − SUM(reversal)); tidak menghasilkan anomali palsu (uji terarah).
- Migrasi aditif `_000019`, `_000020`, `_000021` **[Ran]**. Pengujian rollback **21 tests / 51 assertions — OK** (refund parsial/penuh/ganda/melebihi/gagal/simultan; dampak saldo & alokasi; histori tidak berubah; koreksi replacement; IDOR; otorisasi). `php artisan test` 2 passed. Browser UAT: halaman render **console bersih**; alur pengajuan/persetujuan **belum diuji browser**.
- Midtrans Sandbox tetap **BLOCKED**; `provider_api` refund **sengaja diblokir** eksplisit. Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_7.md`. 4 pertanyaan kebijakan finansial terbuka (bukti PDF reversal, refund saldo tidak mencukupi, scope pembayaran online vs manual, kebijakan top-up saldo siswa).

### 2B.39 — Penyempurnaan Rekonsiliasi & Audit Keuangan Sprint 6 **SELESAI (11 November 2026)**
- **Rekonsiliasi ditingkatkan:** ringkasan `per_kategori` + `total_ditemukan` + `total_tersimpan` + `terpotong`; kartu "Jumlah temuan" menampilkan total ditemukan; alert kuning "Hasil terbatas/truncated" bila kategori ≥ 500 temuan (dengan rincian total sebenarnya). Pemeriksaan ulang = run baru terpisah; riwayat run lama tetap bisa dibuka (filter periode juga menyaring riwayat).
- **Filter periode/kategori/status** konsisten dengan run terpilih dan dipakai juga saat filter riwayat; pagination Livewire mempertahankan filter.
- **Export CSV** (`/rekonsiliasikeuangan/export/{id}/ringkasan|temuan`, admin/superadmin, guru/siswa/wali ditolak 404): ringkasan + detail temuan (mengikuti filter kategori/status dari query string). BOM UTF-8; **anti formula-injection** (nilai diawali `= + - @ TAB CR` di-prefix `'`); file dibuat via stream (tidak disimpan di publik); nama file memuat id run & tanggal.
- **Detail temuan** kini menampilkan kategori (label ID), ref, siswa, tagihan, nominal selisih (Rp), keterangan, status, dan **Waktu Pemeriksaan**.
- Pengujian rollback **10 tests / 33 assertions — OK** (truncated 501→500 tersimpan + flag; run terpisah; export run terpilih; injection; IDOR/blocking guru/siswa; ledger tidak dimutasi). `php artisan test` 2 passed; pint PASS (exit 0). Browser UAT: halaman render + tombol "Jalankan Pemeriksaan" + alert hasil **console bersih**; export & interaksi filter **belum diuji browser**.
- Midtrans Sandbox tetap **BLOCKED**. Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_6.md`.

### 2B.38 — Implementasi Rekonsiliasi Keuangan Sprint 5 **SELESAI (11 November 2026)**
- **Rekonsiliasi keuangan** `/rekonsiliasikeuangan` (superadmin/admin; guru & siswa/wali ditolak backend): pemeriksaan **read-only** mengonsolidasikan `payment_attempt`, `pembayaran`, `pembayaran_alokasi`, `tagihan`, `kuitansi`, histori verifikasi manual.
- **11 pemeriksaan** (attempt sukses tanpa ledger; ledger online tanpa attempt; success tanpa alokasi; alokasi>nominal; alokasi tagihan siswa lain; status tagihan tidak cocok; saldo negatif; kuitansi tanpa pembayaran valid/data tidak cocok; pembayaran manual verified tanpa kuitansi; verifikasi manual tidak konsisten). Dedup per run; batas 500 temuan per kategori.
- Hasil disimpan di `rekonsiliasi_hasil` + `rekonsiliasi_temuan` (**bukan ledger**; aditif, [Ran]). Tidak ada tombol koreksi otomatis. UI `.sa-*` dengan riwayat hasil, filter periode/kategori/status, ringkasan per kategori, dan tombol "Jalankan Pemeriksaan".
- Pengujian terarah rollback **14 tests / 24 assertions — OK** (konsisten 0 temuan; pending diabaikan; 8 deteksi; IDOR/akses guru ditolak; tidak mengubah ledger). `php artisan test` 2 passed. Browser UAT: halaman render + tombol Jalankan OK, console bersih. Live DB tetap bersih.
- Midtrans Sandbox tetap **BLOCKED**. Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_5.md`.

### 2B.37 — Implementasi Penggunaan Saldo Siswa Sprint 4 **SELESAI (11 November 2026)**
- **Penggunaan saldo siswa** `/bayartagihan` (kartu "Saldo Siswa" + input + tombol "Gunakan Saldo"): saldo dari `pembayaran_alokasi.jenis='saldo'` **dikurangi** pemakaian (maker `pembayaran` provider='saldo'); satu mekanisme, tanpa ledger/saldo kedua.
- **Alokasi otomatis** dalam DB transaction + `lockForUpdate()`: urutan **jatuh tempo terdekat → tanggal terbit tertua → tagihan tanpa denda (pokok) sebelum ber-denda → idtagihan**. Kelebihan tetap saldo; tidak ada tagihan yang bisa dibayar → rollback.
- **UI/fungsi** `/transaksipembayaran` tab baru "Penggunaan Saldo Siswa" (admin/superadmin, read-only) — riwayat tanggal/siswa/tagihan/nominal/pembayar/pencatat/verifikator. Guru ditolak (backend 403/404). Siswa/wali verified+aktif hanya siswa terhubung.
- **Migrasi aditif** `2026_11_12_000016` (metode enum `saldo`, [Ran]). Pengujian terarah **16 tests / 30 assertions — OK** (urutan alokasi, pokok-denda, saldo tidak negatif, IDOR, wali bukan verified ditolak, admin baca riwayat, guru ditolak). `php artisan test` tetap 2 passed. Browser UAT: halaman render OK + console bersih (klik saldo belum diuji end-to-end, tidak meninggalkan data keuangan permanen).
- Midtrans Sandbox tetap **BLOCKED**. Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_4.md`.

**Pengujian:** terarah Sprint 4 **16 tests / 30 assertions — OK** (rollback) + hardening **5 tests / 14 assertions — OK**; `php artisan test` 2 passed; `.sa-*` konsisten; Sandbox tetap BLOCKED.

**Hardening (11 Nov 2026):**
- `hitungSaldoTersedia()` tidak lagi menyembunyikan saldo negatif: nilai mentah dideteksi eksplisit (`saldoSiswa()`), `Log::error`, dikembalikan 0; `gunakanSaldo()` membatalkan dengan `DomainException` saat anomali; histori tidak diubah.
- Concurrency: urutan lock konsisten (siswa → tagihan; online/manual tidak menyentuh baris siswa); **race condition sesungguhnya tidak diuji** (lingkungan tidak mendukung koneksi paralel deterministik) — hanya sequential.
- Pint `--test` pada file terkait: **PASS, exit code 0**.

### 2B.36 — Implementasi Pembayaran Manual Sprint 3 **SELESAI (11 November 2026)**
- **Pembayaran manual** `/pembayaranmanual` (admin+superadmin; guru ditolak): catat tunai/transfer/manual (siswa+tagihan, nominal, metode, tanggal, pembayar, referensi, bukti opsional, catatan). Berstatus **pending** dan **tidak** mengurangi tagihan.
- **Verifikasi**: setujui/tolak dengan alasan wajib. **Refactor `PaymentService`** mengekstrak posting ke `postingPembayaranSetelahVerifikasi()` dipakai online+manual (DB transaction + `lockForUpdate()`, cegah verifikasi ganda, audit verifikator/waktu). Kelebihan → **alokasi saldo**. Ledger konsisten.
- **Bukti** di **private storage** (`storage/app/private/pembayaran/bukti`), validasi tipe/ukuran, download berotorisasi (admin/superadmin, siswa/wali relasi verified+aktif), histori dipertahankan.
- **Kuitansi PDF** dibuat setelah verifikasi: nomor **`PAY/YYYY/MM/NNNNNN`** unik, **reset tahunan**, snapshot sekolah+kuitansi, disimpan permanen di private storage + **checksum SHA256**, akses berwenang, **tanpa tombol hapus** (refund dokumen terpisah). Terintegrasi di riwayat `/bayartagihan`.
- **Notifikasi** via `NotifikasiService` (diizinkan admin) ke pemilik tagihan & wali verified aktif (dicatat menunggu, disetujui, ditolak+alasan).
- **Pengujian terarah (rollback)** 14 tests/30 assertions OK (pending tak mengurangi tagihan, verifikasi ganda ditolak, alokasi ganda dicegah, kelebihan→saldo, bukti/kuitansi/otorisasi, online+manual konsisten). Menu ditambahkan; migrasi aditif [Ran].
- Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_3.md`.

### 2B.35 — Implementasi Payment Gateway Sprint 2 (Midtrans Snap) **SELESAI DENGAN CATATAN (11 November 2026)** — **Sandbox BLOCKED**
- **A. Aturan siswa tanpa tarif selesai**: SPP tidak membuat tagihan Rp 0; siswa tanpa tarif dihitung `tanpa_tarif` (query + double-check PHP); command `spp:terbit` menampilkan/mencatat `tanpa_tarif`; halaman Data Tagihan menampilkan peringatan jumlah tanpa tarif; idempotensi & snapshot dipertahankan; siswa tanpa tarif ditagih otomatis pada penerbitan berikutnya setelah tarif diperbaiki.
- **Arsitektur**: `PaymentGatewayInterface` + `MidtransGateway` (REST langsung via `Http`, tanpa package baru; mode sandbox/production dari `config('services.midtrans')`, tidak baca `.env`) + `PaymentService`. Config baru `midtrans` di `config/services.php` (server/client key, merchant_id, mode; default sandbox, key kosong = gateway nonaktif).
- **attempt → ledger → alokasi** dipisah tegas: `payment_attempt` (instruksi, tidak mengurangi tagihan; histori tidak dihapus; `order_id` unik), `pembayaran` (ledger — hanya settlement terverifikasi; `payment_attempt_id` **unique**), `pembayaran_alokasi` (`tagihan` → kurangi sisa; `saldo` → kelebihan siswa, tidak hilang). Semua dalam transaksi DB + `lockForUpdate()`. Fee tidak dikarang/tidak di-hardcode (`biaya_gateway` NULL attempt / 0 ledger dengan makna "tidak diketahui").
- **Webhook** `POST /webhook/midtrans`: signature SHA512 `order_id+status_code+gross_amount+SERVER_KEY` wajib (`hash_equals`); verifikasi server-side ke Get Status API; cross-check nominal; state machine capture/settlement/pending/deny/cancel/expire; fraud_status dihormati; idempotent (`success` tidak diproses ulang); payload whitelist (audit, tanpa data sensitif); CSRF dikecualikan (server-to-server). Command pengaman `pembayaran:cek-status {order_id?}` tersedia, belum dijadwalkan.
- **UI**: `/bayartagihan` (siswa+wali; IDOR-safe; wali hanya relasi verified+aktif), `/transaksipembayaran` (superadmin+admin; read-only), `/bayar-publik/{token}` (pihak ketiga; capability link; tanpa data akademik). Menu baru di kategori Keuangan (Bayar Tagihan → paket Siswa/Wali; Transaksi Pembayaran → Superadmin). Layout publik berdiri sendiri.
- **Pengujian**: temporary **20 tests / 69 assertions — OK** (signature, webhook success/pending/failed/expired/duplikat/late-notif, saldo overpay, IDOR, HTTP 200/404, tanpa_tarif) — dihapus setelah jalan. `php artisan test` 2 passed. Browser UAT: `/transaksipembayaran` & `/bayartagihan` render OK (console bersih); `/bayar-publik` 404 OK.
- **Sandbox BLOCKED** — tidak ada kredensial Midtrans; tidak ada satu pun request nyata ke Midtrans; seluruh jaringan diuji via `Http::fake()`. Kriteria "transaksi Sandbox lulus" TIDAK terpenuhi. **Menunggu**: merchant activation + kredensial → UAT end-to-end.
- 4 migrasi aditif baru [Ran]; `.env` tidak dibaca; Git tidak disentuh.
- Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_2.md`.

### 2B.34 — Implementasi Pembayaran Sprint 1.2 (Penyempurnaan SPP) **SELESAI (11 November 2026)**
- **Anti duplikasi logika SPP/Manual**: perhitungan tarif + diskon dipindah ke satu jalur `buatTagihanRecord()` di `TagihanService`. `DiskonService::hitung` menerima `?string $tanggal` (bukan lagi `now()` internal) sehingga scheduler & penerbitan manual memakai tarif/beasiswa yang berlaku pada periode (`tanggal_terbit`).
- **Perbaikan periode penagihan**: SPP bulanan hanya untuk siswa aktif; siswa dengan `tahun_masuk > tahun periode` tidak ditagih (validasi double-check PHP). Tarif/beasiswa di-resolve pada `tanggal_terbit` periode — perubahan tarif setelah tagihan terbit tidak mengubah tagihan lama (snapshot `tarif_*`, `kelas_*`, `tahunajaran_*`, `semester_*`, diskon tetap).
- **Aturan tarif & diskon diverifikasi via pengujian terarah (10 tests / 43 assertions — OK)**: prioritas tarif individu > kelas > global (termasuk tidak-berlaku di luar rentang tanggal); custom tarif individu mengesampingkan beasiswa; beasiswa tanpa custom tarif; diskon >100% dibatasi (maks 100%); pembebasan penuh → `status='dibebaskan'` (bukan lunas); siswa baru/keluar di tengah periode tidak ditagih; scheduler 2× tanpa duplikasi; manual & otomatis konsisten (100000 = 100000; insidental 75000); tagihan lama tidak berubah setelah tarif diupdate.
- **Browser UAT**: render-check 4 halaman `/dashboardkeuangan`, `/datatagihan`, `/tarif`, `/beasiswa` — title & h1 benar, layout `.sa-*`, console bersih, tidak ada error banner. **Catatan: UAT hanya render; tombol "Terbitkan SPP" tidak diklik** (akan membuat tagihan permanen pada live DB; tidak ada sandbox DB).
- **Belum selesai / keterbatasan**: siswa tanpa tarif berlaku menerima tagihan Rp 0 `belum_dibayar` (perilaku dari Sprint 1, dipertahankan — bukan kebijakan 1.2); kelas untuk snapshot tagihan memakai kelassiswa aktif saat ini (tabel `kelassiswa` tidak menyimpan riwayat periode); aktivasi scheduler production menunggu cron `schedule:run` di server; pembebasan denda 2-langkah belum ada UI.
- Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_1_2.md`.

### 2B.33 — Implementasi Pembayaran Sprint 1.1 (AuthZ + Scheduler + UAT) **SELESAI (with catatan) (11 November 2026)**
- **Authorization Keuangan** diubah dari superadmin-only menjadi **superadmin + admin** untuk: akses halaman `/dashboardkeuangan`, `/datatagihan`, `/tarif`, `/beasiswa`; semua aksi di komponen Livewire (CRUD tarif, CRUD beasiswa, terbit SPP/insidental, batalkan, lihat detail). **Pembebasan tagihan (`bebaskan`) tetap superadmin-only** (tindakan finansial khusus); tombol **Bebaskan** di UI hanya tampil untuk superadmin.
- **Scheduler SPP**: command `spp:terbit {periode?}` ditambahkan (idempoten: siswa+jenis+periode yang sudah ada dilewati); dijadwalkan `monthlyOn(1, '00:30')->withoutOverlapping()->runInBackground()` di `app/Console/Kernel.php` (timezone Asia/Jakarta). Aktivasi production menunggu cron `schedule:run` di server.
- **Pengujian**: temporary test **7 tests / 21 assertions OK** (superadmin & admin 200 ×4, guru & siswa 404 ×4, admin `bebaskan()` → 403, SPP scheduler 2× tanpa duplikasi, admin CRUD tarif) — dihapus setelah jalan. `php artisan test` 2 passed. Browser UAT headless Chrome CDP: 4 halaman (title + h1 benar, layout `.sa-*`, console bersih, empty state tampil); modal Tambah Beasiswa buka/tutup OK; mobile 375×812 render OK.
- **Catatan**: belum ada akun admin di live DB (tidak dibuat permanen); akun admin harus dibuat saat onboarding; pembebasan denda 2-langkah (admin ajukan → superadmin approve) belum diimplementasikan; scheduler production belum diaktifkan; Browser UAT hanya superadmin (bukan admin).
- Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_1_1.md`.

### 2B.32 — Implementasi Modul Pembayaran Sprint 1 (Fondasi & Tagihan) **SELESAI DENGAN CATATAN (11 November 2026)**
- **Implementasi:** 9 migration aditif (`tarif`, `tarif_riwayat`, `beasiswa`, `beasiswa_siswa`, `tagihan`, `tagihan_adjustment`, `denda`, `pembayaran`, FK `tagihan.denda_id`); 8 model; 3 service (`App\Services\Keuangan\{TarifService, DiskonService, TagihanService}`); 4 komponen Livewire (`DashboardKeuangan`, `DataTagihan`, `Tarif`, `Beasiswa`); 1 seeder menu (`PembayaranMenuSeeder`); 4 route `check.level:superadmin`.
- Prioritas tarif **individu > kelas > global**; beasiswa tidak ditambahkan pada tarif individu; diskon maksimal 100%; pembebasan penuh → `dibebaskan`; snapshot penuh disimpan pada tagihan; SPP bulanan idempoten; 1 denda unik per tagihan; adjustment immutable via `tagihan_adjustment`.
- **Verifikasi:** `migrate:status` ✅ · `view:cache`/`view:clear` ✅ · `php artisan test` **2 passed** · temporary Sprint 1 test (9 tests/22 assertions) **OK lalu dihapus** · HTTP smoke superadmin 200 semua route; guru 404.
- **Catatan:** SPP scheduler belum didaftarkan di `Kernel::schedule()`; bulk `terbitSppBulanan()` tersedia dan dipanggil manual dari UI (tombol "Terbitkan SPP") — perlu keputusan cron host; denda 2-langkah pengajuan admin + approval superadmin belum diimplementasikan (superadmin bisa membebaskan langsung); UI browser UAT **NOT TESTED**.
- Sumber: `LAPORAN_IMPLEMENTASI_PEMBAYARAN_SPRINT_1.md`.

### 2B.31 — Sandbox Spike Payment Gateway (Midtrans) **SELESAI (DOKUMENTASI; EKSEKUSI TERBLOKIR) (11 November 2026)**
- Audit repo: tidak ada artefak Midtrans/Snap/Xendit/iPaymu (composer.lock, .env.example, app/, config/, routes/ — 0 hasil). Tidak ada akun Sandbox, tidak ada kredensial di workspace.
- Verifikasi dokumentasi resmi Midtrans (2026-11-11): Snap Embedded, VA close amount (default), Custom VA number per order, BI-SNAP Multi-use VA (permanen per siswa, berulang, nominal variabel) — syarat aktivasi merchant; Open amount VA perlu aktivasi PIC; QRIS dinamis (0,7%) & statis (registrasi GoPay terpisah); e-wallet (GoPay 2%, DANA/OVO 1,5%, ShopeePay 2%); Bank transfer Rp4.000 + VAT; webhook `SHA512(order_id+status_code+gross_amount+SignatureSecretKey)`; idempotensi via `order_id`; refund Direct API hanya GoPay/QRIS/ShopeePay/Card/Akulaku/Kredivo (VA manual); service fee boleh ditambahkan di item_details.
- **VA permanen per siswa** hanya melalui **BI-SNAP openAPI Multi-use VA** (first payment 6 bulan, berikutnya indefinite) — bukan fitur default; butuh aktivasi merchant.
- **VA per tagihan (close amount)** — opsi paling cepat untuk MVP.
- **VA custom per order** — bukan permanen.
- **Akun Sandbox & semua eksekusi API: BLOCKED** — menunggu pendaftaran akun Midtrans oleh sekolah/pemilik + Server Key/Client Key/Signature Secret Key.
- **Tidak ada perubahan aplikasi, database, dependency, atau Git.** Satu-satunya file dibuat: laporan ini + pembaruan ROADMAP.
- Sumber: `LAPORAN_SANDBOX_SPIKE_PEMBAYARAN.md`.

### 2B.30 — Audit Teknis Modul Pembayaran **SELESAI (read-only) (11 November 2026)**
- Audit persiapan implementasi Modul Pembayaran SIAKAD terhadap `Modul pembayaran siakad.md` (SPESIFIKASI FINAL v1.0, status "DESAIN FUNGSIONAL: DISEPAKATI; AUDIT TEKNIS: BELUM DILAKUKAN").
- Tidak ada satu pun artefak pembayaran (tabel/model/service/route/package/kredensial/env) yang sudah ada; kebutuhan modul hampir seluruhnya baru.
- Yang **sudah tersedia & dapat digunakan ulang**: NotifikasiService (snapshot penerima, transaksi), WalimuridService::pastikanAksesAkademikWali, KonteksAkademikService (KonteksAkademik TA/semester aktif, lockForUpdate), pola transaksi + `lockForUpdate()` (FinalisasiNilaiService), pola streamDownload XLSX/CSV/PDF (DomPDF + phpspreadsheet), menu dinamis `appmenunew` + seeder pattern, middleware role-based (`check.level`, `cek.akun`, `force.password`), menu seeder pattern untuk modul baru.
- Yang **belum tersedia**: seluruh domain pembayaran (tarif, beasiswa, tagihan, cicilan, denda, VA, refund, ledger, audit khusus, bukti bayar, dashboard/laporan keuangan), payment gateway (tidak ada Midtrans/Xendit/iPaymu di composer; tidak ada `.env` variabel; `routes/api.php` hanya `GET /api/user`), scheduler keuangan, audit-log pervasif (`activity()` hanya dipakai di `ImportRunner.php`).
- **Keputusan produk diperlukan:** skema VA (tetap vs per-tagihan — spec §9.4 memperingatkan VA tetap belum terbukti), role `admin` (level enum ada tapi 0 user di DB; permission-based vs level-based), nominal tarif/kebijakan beasiswa, arah provider Sandbox (Midtrans vs lain), dan metode yang diaktifkan.
- **Risiko teknis:** atomicity transaksi provider (webhook asinkron), idempotency `order_id`, konkurensi cicilan (lockForUpdate), pembatalan vs alokasi, audit log wajib spec §19 (belum ada pola).
- **Usulan tahapan:** Sprint 0 (keputusan + spike Midtrans Sandbox) → Sprint 1 (fondasi schema + TarifService) → Sprint 2 (online payment + webhook) → Sprint 3 (manual + bukti PDF) → Sprint 4 (cicilan + denda + refund) → Sprint 5 (dashboard + laporan + audit) → Sprint 6 (UAT + deploy).
- Sumber: `LAPORAN_AUDIT_TEKNIS_MODUL_PEMBAYARAN.md`.

### 2B.29 — Security Configuration & Storage ACL Hardening **SELESAI SEBAGIAN (11 November 2026)**
- Audit konfigurasi (`config/app.php`, `filesystems.php`, `logging.php`, disk `local`/`public`/`hosting`), storage ACL (Windows NTFS read-only), symlink `public/storage` (file placeholder 69 B berisi path absolut upstream — **rusak, tidak menyalurkan data**), route file/authorization (semua pengunduhan file terotorisasi: siswa-only/wali-only/superadmin-only; `ImportDownloadController` traversal-proof; `unduhRekap` siswa/wali ter-scope; Log Viewer gate aktif).
- **Perbaikan kode diterapkan (10 file):** 8 komponen admin `exportToExcel` diubah dari menulis `exported-*.xlsx` ke **root proyek** → `streamDownload` + `php://output` (tidak ada file sementara di disk); validasi `foto` `nullable|image|mimes:jpeg,jpg,png,webp|max:2048` pada `storeGuru`, `updateGuru`, `storeSiswa`, `updateSiswa` (mencegah upload non-image pada form superadmin).
- **Temuan penting:** PDF Docs disajikan via `asset('storage/...')` tanpa autentikasi (butuh symlink untuk berfungsi) — bukan diubah (mengubah UX/flow), dicatat BLOCKED. ACL NTFS terlalu longgar (`Authenticated Users: Modify`) pada `storage/app/private/imports`, `storage/logs`, `storage/app/tmp-uji` — rekomendasi `icacls` dicatat, tidak dieksekusi.
- **Lingkungan tidak dapat diverifikasi:** nilai runtime `APP_ENV`/`APP_DEBUG`/`LOG_VIEWER_ENABLED`/`APP_KEY` — `.env` tidak boleh dibaca.
- Verifikasi: `view:cache`/`view:clear` ✅ · `php artisan test` 2 passed ✅ · pint PASS (1 fix) ✅ · xlsx-stream signature `PK\x03\x04` ✅ · Log Viewer tetap terkunci (superadmin 200 / lainnya 403).
- **H1 tetap DITUNDA**; `public/storage` rusak (foto & PDF docs 404); tindakan OS/deployment/B2/B3/B4/B5/B6/B7 menunggu keputusan pemilik.
- Sumber: `LAPORAN_SECURITY_CONFIGURATION_STORAGE_ACL_2B_29.md`.

### 2B.28 — Security Residual & Operational Retention **SELESAI SEBAGIAN (11 November 2026)**
- Audit read-only H1, H2, H4, M1, M2 + konfigurasi ENV/Log Viewer. Perbaikan non-destruktif diterapkan: normalisasi logging exception di 5 titik (`GuruComponent`, `KelasComponent`, `ImportRunner` — `get_class()` + `message`).
- **H1 tetap DITUNDA** (keputusan pemilik) — dump penuh `backup-2b75a-full-20261109-092544.sql` (SHA-256 tercatat di 2B.27-R2) tidak dienkripsi/dipindahkan/dihapus; risiko residual tertinggi.
- **H2:** rotasi harian 14 hari aktif; `laravel.log` legacy (27 MB) dipertahankan sebagai bukti; `LOG_LEVEL=debug` default; tidak ada regresi logging.
- **H4:** Live DB **tanpa** `personal_access_tokens` (nol token aktif); tidak ada `createToken()`; `User::$hidden` & `/api/user` aman; `templatedb.sql` upstream (3 bcrypt + 23 SHA-256 `wakibota-mobile`) diaudit read-only, tidak dirotasi (menunggu keputusan).
- **M1:** 2 berkas `*-gagal.json` (PII siswa) diaudit — mekanisme privat & minimal (hanya baris gagal + alasan), superadmin-only unduh, path-traversal-proof; retensi unbounded — kebijakan menunggu keputusan pemilik.
- **M2:** 48 artefak `tmp-uji/` diaudit — semua privat, ter-gitignore, tidak web-accessible; screenshot UAT dipertahankan sebagai bukti; `backup-pre-seed-20261105.json` & `usermenu-rollback-257-258.sql` ditandai menunggu H1; cleanup tidak diterapkan sepihak.
- **Log Viewer (R1):** gate `viewLogViewer` aktif (superadmin-only, guest 403, exception 500, kill-switch terverifikasi).
- Verifikasi: `view:cache`/`view:clear` ✅ · `php artisan test` 2 passed ✅ · smoke 4 tests/11 assertions ✅ · pint ✅.
- **BLOCKED / menunggu persetujuan:** H1 (enkripsi/pindah/hapus dump), retensi M1/M2, `LOG_VIEWER_ENABLED=false` produksi, ACL `storage/`, arsip/hapus `laravel.log` legacy.
- Sumber: `LAPORAN_SECURITY_RESIDUAL_OPERATIONAL_RETENTION_2B_28.md`.

### 2B.27-R2.1 — Pengamanan Log & Artefak (H2/H3/H4/M1/M2) **SELESAI SEBAGIAN (11 November 2026)**
- **Kode diubah (3 file):** `app/Livewire/GuruComponent.php`, `app/Livewire/KelasComponent.php`, `app/Services/Import/ImportRunner.php` — exception penuh (`['exception' => $e]`) di-log dinormalisasi menjadi `get_class() + message` (5 titik); diagnostik dipertahankan (class + message).
- **H2** — `storage/logs/laravel.log` legacy **dipertahankan** (rotasi daily 2B.21 tetap berlaku; log harian baru bersih); sumber logging baru aman; rekomendasi retensi/arsip didokumentasikan (menunggu keputusan pemilik).
- **H3** — 142 capture Debugbar usang (dibuat 2026-10-26, package **tidak terpasang**) dihapus; `storage/debugbar/.gitignore` dipertahankan.
- **H4** — Audit: live DB `templatedb` **tidak memiliki** `personal_access_tokens` (nol token aktif); `User::$hidden` & `/api/user` aman; `templatedb.sql` (read-only) berisi 3 akun bcrypt upstream + 23 Sanctum SHA-256 (`wakibota-mobile`); tidak ada createToken di kode.
- **M1** — 2 berkas `*-gagal.json` (PII siswa: nama/nis/ortu/alamat) diaudit; mekanisme impor sudah minimal (hanya kolom gagal + alasan), path privat, ter-gitignore, tidak web-accessible; file dipertahankan (operator perlu untuk perbaikan); retensi unbounded — rekomendasi auto-cleanup dicatat (BLOCKED, butuh persetujuan).
- **M2** — 48 artefak UAT diaudit; semua berada di `storage/app/tmp-uji/` (privat, ter-gitignore, tidak web-accessible); 3 artefak kelas-backup (`backup-pre-seed-20261105.json`, `usermenu-rollback-257-258.sql`, `verify_2b75a_final.txt`) ditandai menunggu keputusan H1; screenshot UAT dipertahankan sebagai bukti penerimaan fitur.
- **Verifikasi:** `view:cache`/`view:clear` OK; `php artisan test` 2 passed; HTTP smoke sementara (4 tests / 11 assertions PASS, dihapus); `pint` pada 3 file (1 style fix).
- **BLOCKED/menunggu persetujuan:** arsip/hapus `laravel.log`; hapus/arsip `*-gagal.json`; cleanup artefak `tmp-uji/`; revokasi/rotasi credential upstream; `APP_ENV/APP_DEBUG/LOG_VIEWER_ENABLED` di `.env`; retensi auto-cleanup gagal-import.
- **H1 tetap DITUNDA** — dump penuh `backup-2b75a-full-20261109-092544.sql` tidak disentuh; `templatedb.sql` tidak disentuh.
- Sumber: `LAPORAN_IMPLEMENTASI_SECURITY_HARDENING_2B_27.md`.

### 2B.27-R2 — Pengamanan Backup Database (H1) **DITUNDA ATAS KEPUTUSAN PEMILIK (11 November 2026)**
- Audit backup `storage/app/backup-2b75a/backup-2b75a-full-20261109-092544.sql`: 105.771 B, 2026-11-09 09:25:45, SHA-256 `A24DAA1FA818B0AF7D6576A75E48D4E50E19891C5F8985E6C10672D97AB42B9D`. Plaintext mysqldump (45 tabel, 45 CREATE/45 DROP/27 INSERT, 1.694 baris, berakhir `-- Dump completed`). Berisi data pribadi siswa/guru/wali + hash bcrypt `users` (19 penanda) + akun & relasi — satu-satunya dump penuh; `siakad:backup-db` tidak terkonfigurasi; hanya backup parsial lain (`templatedb.sql` root, pre-seed JSON).
- Metode direkomendasikan: **AES-256-GCM** via PHP openssl ext (OpenSSL 3.0.8, terdeteksi) — kunci 256-bit di luar repo; ciphertext diletakkan di folder privat (ter-gitignore). openssl CLI & gpg tidak tersedia; no package baru.
- **Pemilik memutuskan (2026-11-11): enkripsi dibatalkan — hanya rekomendasi.** Tidak ada ciphertext/kunci dibuat; file asli tidak disentuh (0 perubahan).
- **Dump plaintext tetap ada** — H1 dari 2B.27 belum tertutup. Menunggu keputusan: (A) eksekusi enkripsi + verifikasi + retensi plaintext; (B) hapus dump; (C) pindahkan ke media terenkripsi pemilik. Restore belum diuji (di luar izin).
- Sumber: `LAPORAN_PENGAMANAN_BACKUP_DATABASE_2B_27_R2.md`.

### 2B.27-R1 — Remediasi Akses Log Viewer (C1) **SELESAI (11 November 2026)**
- Penyebab (v3.17.1): di `APP_ENV=local`, tanpa Gate `viewLogViewer`/callback, `AuthorizeLogViewer` → `LogViewerService::auth()` **mengizinkan semua akses**.
- Perbaikan (1 file, `app/Providers/AuthServiceProvider.php`): `Gate::define('viewLogViewer', fn ($user) => isset($user->level) && $user->level === 'superadmin')`. Tanpa perubahan kode/route/middleware/artefak lain; package tidak diubah; `.env` tidak disentuh.
- Verifikasi HTTP (temporary test, dihapus setelah lolos — 9 tests/16 assertions): guest & guru/siswa/wali/admin → **403**; superadmin → **200**; API endpoints guru → 403; simulasi gate gagal (exception) → **500** (deny); `/login` & `/dashboard` normal.
- Kill-switch `LOG_VIEWER_ENABLED=false` terverifikasi (route tidak terdaftar); middleware nyata tiap route Log Viewer terkonfirmasi seluruhnya melalui `AuthorizeLogViewer`.
- `view:cache`/`view:clear` OK; `php artisan test` 2 passed; pint 1 file.
- Risiko tersisa: `APP_ENV`/`APP_DEBUG` di `.env` di luar cakupan; log lama & artefak backup belum dibersihkan (tahap berikutnya); tidak ada UAT manusia.
- Sumber: `LAPORAN_REMEDIASI_LOG_VIEWER_2B_27_R1.md`.

### 2B.26 — Rebranding Wakibota → SIAKAD Template **LULUS DENGAN CATATAN (11 November 2026)**
- Identitas visual aplikasi diganti dari **Wakibota Template** menjadi **SIAKAD Template**: logo baru (simbol institusi sekolah — atap segitiga + tiga pilar + alas dalam rounded-square teal `#0E7D6A`; lockup simbol + wordmark `SIAKAD` + subtitle `Template`), `favicon.ico` digenerate ulang (16/32/48, PNG-in-ICO), dan seluruh teks brand lama dihapus dari tampilan.
- Asset baru (SVG lokal): `public/img/brand/siakad-mark.svg`, `public/img/brand/siakad-logo.svg`. Sidebar & halaman login memakai `<img>` logo baru (alt "SIAKAD Template"); `<title>` utama `SIAKAD Template | @yield('title')`; login `SIAKAD Template | Login`; meta description/keyword/author layout utama & docs diganti; favicon link ditambahkan di login + halaman error 403/404; `config/app.php` default `APP_NAME` `Laravel` → `SIAKAD` (kop PDF tetap `config('app.name')`, K3/2B.13).
- Tidak ada perubahan business logic, route, middleware, otorisasi, menu, navigasi, tema/warna global, atau dashboard di luar elemen branding; `.env` tidak dibaca/diubah.
- Verifikasi: `view:cache`/`view:clear` OK; `php artisan test` **2 passed**; HTTP smoke branding sementara **5 tests / 20 assertions PASS** (dihapus); browser UAT Chrome headless CDP: login desktop & mobile, `/dashboard` superadmin, `/berandaguru`, `/wali-beranda` — logo tampil, title benar, console bersih; asset (`favicon.ico`, `siakad-logo.svg`, `siakad-mark.svg`) 200. Siswa demo 404 (pra-ada, tanpa `usermenu`); UAT manusia belum dilakukan; `templatedb.sql` masih memuat string brand lama (dump, perlu keputusan pemilik, tidak diubah).
- Sumber: `LAPORAN_REBRANDING_SIAKAD.md`.

### 2B.25-UI — Redesign UI/UX Seluruh Modul Penilaian **LULUS DENGAN CATATAN (10 November 2026)**
- Penamaan sengaja diberi sufiks `-UI`: nomor `2B.25` pada roadmap sudah dipakai untuk `LAPORAN_AUDIT_NAVIGASI_DAN_LIVEWIRE_2B_25.md`; tahap redesign Penilaian ini memakai nama berkas laporan `LAPORAN_REDESIGN_UI_UX_PENILAIAN_2B_25.md` sesuai instruksi tahap.
- Tujuh halaman kelompok PENILAIAN (`penugasanmengajar`, `komponennilai`, `kktpmapel`, `inputnilai`, `finalisasirapor`, `koreksinilai`, `raporwalikelas`) didesain ulang mengikuti sistem `.sa-*` 2B.22: header fungsional, filter terpisah, `.sa-table`, badge status berteks (Final/Draf/Terkunci/Lengkap draft), empty state, loading state, label `for/id`, `aria-label`, `@include('assets.akademik')` di dalam container root (root tetap `<div>`), skrip listener di `@script` setelah root.
- **Bug akar ditemukan & diperbaiki:** (1) `komponen-nilai` view memanggil `$baris->count()` pada properti array → `ViewException` → `livewire/update` HTTP 500 (2 entri di `laravel-2026-11-10.log`) — diubah `count($baris)`; (2) `koreksi-nilai` struktur div tidak seimbang + `<script>` di dalam modal → satu root, listener ke `@script`; (3) em-dash ter-double-encode pada Input Nilai → `-`; (4) include di luar root (7/7 view, varian 2B.23-FINAL) → dipindah ke dalam root.
- Tidak ada PHP aplikasi / route / middleware / service / query / aturan bisnis yang diubah; semua binding `wire:*` dipertahankan; database tidak dimutasi selama redesign.
- Verifikasi: `view:cache` OK (lalu clear); `php artisan test` **2 passed**; HTTP smoke temporary **5 tests / 25 assertions PASS** (superadmin 200 ×6, akses guru-only/role asing → 404 sesuai `CheckUserLevel`, guest 302) — skrip dihapus; browser UAT Chrome headless CDP (1366×768 & 375×812, akun superadmin id 1 & `DEMO_GURU_1` id 393): semua halaman 200, modal buka/tutup OK, filter/select binding Livewire berfungsi, POST `livewire/update` semua **200** (komponennilai, finalisasirapor, koreksinilai ×2 + submit validasi, raporwalikelas ×2, inputnilai), badge Final/Draf tampil benar, pesan validasi "Alasan koreksi wajib diisi." tampil tanpa tulis DB; console bersih setelah perbaikan; **transient** satu `log.error … 500` eksternal sekali (log Laravel bersih, tidak berulang) — dilaporkan apa adanya.
- Sesi UAT (cookie framework-generated) dihapus; screenshot after di `storage/app/tmp-uji/uat-2b25/`; **before tidak tersedia**.
- Belum diverifikasi: UAT manusia; jalur tulis (simpan nilai/finalisasi/koreksi dengan data valid, simpan catatan, submit modal Penugasan/KKTP, cetak PDF) — sengaja tidak dijalankan agar DB tidak berubah.
- Sumber: `LAPORAN_REDESIGN_UI_UX_PENILAIAN_2B_25.md`.

### 2B.25 — Audit Navigasi, Redirect Role & Integritas Root Livewire — **LULUS DENGAN CATATAN (10 November 2026)**
- `docs_sidebar.blade.php` & `tahunajaran-component.blade.php`: hardcode `/dashboard` → role-aware `BerandaUrl::untuk()`.
- Error pages 403/404: `history.back()` → link role-aware (guest → `/login`).
- Scan root Livewire components: 0 masalah struktural.
- HTTP temporary tests: 10/10 PASS, 38 assertions.
- Browser UAT (headless Chrome CDP): superadmin/guru/wali halaman utama 200, console bersih; wali "Kembali ke Halaman Awal" pada 404 → `/wali-beranda`; siswa demo tetap 404 (usermenu=0, pra-ada).
- Sumber: `LAPORAN_AUDIT_NAVIGASI_DAN_LIVEWIRE_2B_25.md`.

### 2B.24 — Redesign UI/UX Portal Relasi Wali–Siswa — **LULUS DENGAN CATATAN (10 November 2026)**
- `/wali-relasi` didesain ulang mengikuti sistem `.sa-*` 2B.22: header fungsional, daftar relasi `sa-table`, status berteks eksplisit (Menunggu verifikasi / Terverifikasi / Ditolak / Berakhir), empty state, loading state, panel "Arti Status Relasi", ringkasan siswa terpilih + tombol Ganti.
- Bug akar Livewire ditemukan & diperbaiki (sama dengan 2B.23-FINAL): `@include('assets.akademik')` sebelum root `<div>` → dipindah ke di dalam container.
- Verifikasi headless Chrome via CDP (`DEMO_WALI_1`): buka halaman, cari nama/NIS, pilih siswa, ganti siswa, tombol Ajukan tampil, mobile 390px tanpa overflow — **PASS**; console bersih.
- Keterbatasan: belum ada UAT manusia; hanya `DEMO_WALI_1` yang diuji; pengajuan nyata tidak dieksekusi (DB tidak berubah).
- Sumber: `LAPORAN_REDESIGN_UI_UX_WALI_RELASI_2B_24.md`.

### 2B.23 — Redesign + Penyempurnaan CRUD Pengumuman (+ Revisi 2B.23-FIX & 2B.23-FINAL) — **LULUS DENGAN CATATAN (10 November 2026)**
- **2B.23-FINAL**: akar masalah "tombol mati" = `@include('assets.akademik')` sebelum root `<div>` menjadikan `<style>` sebagai root Livewire component → semua direktif `wire:*` tidak pernah ter-init (berlaku untuk 12 view sejak 2B.22). Diperbaiki: include dipindah ke dalam container root DIV (semua 12 view); modal dibuka via dispatch+`@script`+`wire:ignore.self` (pola LR3 baku); redesign Ulang Modul Pengumuman konsisten sistem `.sa-*`2B.22.
- **Browser UAT (headless Chrome via CDP)**: 11/11 PASS (load, tambah→modal, validasi kosong, simpan draf, detail, edit, terbit, arsip, arsip=read-only, pencarian, mobile). Data uji dihapus; DB kembali pre-state. Console bersih. Screenshot di `storage/app/tmp-uji/final_*.png`.
- Browser UAT oleh manusia & UAT visual portal siswa (DEMO_SISWA_1 tanpa usermenu) masih tertunda (butuh sync menu/persetujuan).
- Sumber: `LAPORAN_REDESIGN_CRUD_PENGUMUMAN_2B_23.md`.

### 2B.22 — Redesign UI/UX Jadwal & Absensi — **SELESAI DENGAN CATATAN (10 November 2026)**
- 13 halaman Jadwal & Absensi (jadwalkelas, jadwal lama, jamhari, pengampu, jadwalguru, jadwalsiswa, absensi-harian, absensi-perjam, histori-absensi-siswa, wali-akademik tab Jadwal/Absensi) didesign ulang dengan satu sistem `.sa-*` (`assets/akademik.blade.php`) — header fungsional, kartu berikon, filter terpisah, `.sa-table`, badge status berteks+warna, empty state, loading state, fokus keyboard, `for/id` label. Identitas aplikasi (hijau `#0e7d6a`/`#0C8C57`, DM Sans) dipertahankan; sidebar/navbar tidak diubah.
- **Bug akar Livewire (ditemukan 2B.23-FINAL, diperbaiki di patch yang sama)**: `@include('assets.akademik')` sebelum root `<div>` menjadikan `<style>` root Livewire component → semua direktif `wire:*` tidak pernah di-init di browser (seluruh 2B.22 mati di browser saat itu). Perbaikan: include dipindah ke dalam container root DIV di 12 view.
- Interaksi Livewire, route, otorisasi, logic & aturan H+1 absensi **tidak berubah**; tidak ada perubahan schema/migration/seeder; tidak ada data dummy.
- Validasi: `view:cache` OK (lalu clear); temporary HTTP smoke **7 tests / 28 assertions PASS**; `php artisan test` 2 passed. Browser/cetak **belum diverifikasi** (tidak ada browser automation) → kemudian terverifikasi di 2B.23-FINAL (console bersih, 11/11 UAT Chrome headless).
- Sumber: `LAPORAN_REDESIGN_UI_UX_JADWAL_ABSENSI.md`.

### 2B.21 — Paket Final Production Readiness — **SELESAI DENGAN KONDISI (10 November 2026)**
- Backup command `siakad:backup-db` (mysqldump eksternal, validasi artefak, log non-sensitif, retensi nonaktif default, scheduler nonaktif default via `siakad.backup.enabled`); `php artisan about` → `Logs stack / daily` (rotasi 14 hari); run tanpa konfigurasi diuji (exit 1, DB tidak disentuh).
- Perbaikan: `exportToExcel` tidak lagi menulis file ke root (stream dari memory); 6 option builder di-escape (`e()` + atribut quoted) di Appmenunew/Appmenucat/Docscat/Docs components; pengumuman siswa `{{ }}`; verifikasi lewat temporary test **11 tests / 71 assertions PASS** (transaksi+rollback, dihapus).
- UAT role (HTTP/service-level, browser **NOT TESTED**): superadmin/guru/siswa/wali/guest diuji; siswa demo & admin 0 akun → BLOCKED; dokumentasi `MATRIKS_UAT_SEMUA_ROLE.md`.
- Anomali `public/storage` dikonfirmasi: file reguler 69 byte (bukan symlink) — rekomendasi perbaikan (perlu persetujuan).
- Keputusan akhir produksi: **READY WITH CONDITIONS** — blocker: environment (`local`/debug ON), no backup live/drill, no browser UAT, deploy belum direhearsed.
- Sumber: `LAPORAN_FINAL_PRODUCTION_READINESS.md`, `PROSEDUR_BACKUP_RESTORE_SIAKAD.md`, `PROSEDUR_DEPLOYMENT_ROLLBACK_SIAKAD.md`.

### 2B.20 — Pengamanan Artefak Backup & Data Lokal (P0) — **SELESAI SEBAGAI AUDIT + HARDENING IGNORE (10 November 2026)**
- Inventaris 15 kelompok artefak sensitif (path/tipe/ukuran) dibuat tanpa membuka isi: `storage/app/backup-2b75a/` (dump SQL penuh), `templatedb.sql` (root), `storage/debugbar/` (143 file, ±5.9 MB), `storage/logs/laravel.log` (±28 MB), `private/imports/*` (xlsx/json), `tmp-uji/*`, `foto-siswa-uji.jpg` (4 B dummy), `public/storage` (file 69 byte — anomali; bukan symlink).
- Koreksi 2B.19: backup dump **sebenarnya sudah tercakup** `storage/app/.gitignore` (pola `*`) — pernyataan "tidak ter-ignore" pada 2B.19 hanya berlaku untuk root `.gitignore`. Pernyataan itu diperbaiki di laporan 2B.20.
- Hardening (2 perubahan, non-destruktif, non-applikasi): root `.gitignore` +blok generik (`*.sql`, `*.sqlite(3)`, `*.dump`, `*.bak`, `backup-*/`); `storage/app/public/.gitignore` baru (`*` + `!.gitignore`) untuk menutup upload publik.
- Validasi: tidak ada file aplikasi yang tertangkap pola; semua artefak sensitif utuh byte-identical; 0 mutasi DB/aplikasi; tidak ada artefak dipindah/dihapus.
- **Risiko belum selesai:** dump & artefak debug/log masih di worktree; deployment manual (FTP/copy) tidak terlindungi ignore → perlu keputusan pemilik untuk memindah/menghapus (daftar 8 item); backup otomatis + restore drill tetap tertunda (P0).
- Sumber: `LAPORAN_PENGAMANAN_ARTEFAK_BACKUP_2B_20.md`.

### 2B.19 — Audit Konsolidasi Akhir & Kesiapan Produksi — **SELESAI (10 November 2026)**
- Audit read-only menyeluruh (2B.4–2B.18) + verifikasi kode aktual (route, middleware, role, modul akademik, keamanan, performa) + matriks risiko 30 temuan (0 Critical, 4 High).
- **Keputusan kesiapan produksi: READY WITH CONDITIONS** — bukan READY (debug aktif & environment local, tidak ada browser UAT, tidak ada backup teruji-restore, tidak ada monitoring/log-rotation); bukan NOT READY (tidak ada cacat Critical/High fungsional yang diketahui).
- Kondisi sebelum produksi: environment produksi + HTTPS, backup otomatis + restore drill, UAT browser per role, rotasi log, cache config/routes/views, smoke suite minimal permanen.
- Kepatuhan: 1 perintah Git dijalankan tidak sengaja (read-only, tanpa argumen, tidak ada perubahan state) — dilaporkan di laporan audit §15.
- Sumber: `LAPORAN_AUDIT_KONSOLIDASI_AKHIR_2B_19.md`, `CHECKLIST_PRA_PRODUKSI_SIAKAD.md`.

### 2B.18 — Logging Minimal Komponen (P3 / A4) — **LULUS (10 November 2026)**
- A4 dari penutupan UAT: 12 titik `Log::warning` ditambahkan pada 5 komponen Modul Penilaian (InputNilai, FinalisasiRapor, KoreksiNilai, RaporSiswa, WaliKelas) yang menelan `DomainException`/`FinalisasiException` menjadi pesan UI. Pola mengikuti 2B.10; jalur `render()` sengaja tidak dicatat (anti duplikasi); tidak ada data sensitif/isi nilai akademik di log; context minimal (user_id, id entitas, kelas exception, message).
- Verifikasi: 14 test / 151 assertions PASS (capturing PSR-3 logger, transaction + rollback, skrip sementara dihapus); jalur sukses & render berulang = 0 log; perilaku aplikasi tidak berubah; HTTP smoke OK; `php artisan test` 2 passed.
- P3 seluruhnya selesai (A3 — 2B.17, A4 — 2B.18).
- Sumber: `LAPORAN_LOGGING_MINIMAL_2B_18.md`.

### 2B.17 — Optimasi N+1 `daftarRapor` — **LULUS (10 November 2026)**
- A3 dari hasil penutupan UAT: `RaporSiswaService::daftarRapor()` tidak lagi linear — per rapor dulu ≈ 7 query (41 query untuk 6 pasangan) → sekarang konstan ≈ 9–10 query; hasil data identik dengan implementasi lama (diverifikasi terhadap implementasi lama inline); snapshot KKTP, urutan, dan otorisasi tidak berubah; 8/8 test sementara PASS (rollback), suite `php artisan test` 2 passed.
- File diubah: `app/Services/RaporSiswaService.php` saja.
- Sumber: `LAPORAN_OPTIMASI_N_PLUS_1_DAFTAR_RAPOR_2B_17.md`.

### 2B.16 — Hardening Route Beranda & Jadwal Guru (F-2) — **SELESAI (10 November 2026)**
- P1 F-2 disetujui dan dikerjakan: `/berandaguru` → `check.level:guru`, `/jadwalguru` → `check.level:guru,superadmin` kembali efektif (ditambahkan ke `$explicitUrls` di `routes/web.php`; loop dinamis tidak lagi menimpa route eksplisit). Component-level guard dipertahankan; guru tanpa assignment menu tidak lagi 404; siswa/wali/guest ditolak sesuai kebijakan; superadmin hanya `/jadwalguru`.
- P2 akun admin diputuskan pemilik proyek: **tidak perlu seeder**; superadmin cukup untuk operasional.
- 7 test / 15 assertions PASS (temporary, transaksi + rollback, dihapus setelah dipakai); regression route dinamis utuh; `php artisan test` 2 passed.
- Sumber: `LAPORAN_HARDENING_ROUTE_GURU_2B_16.md`.

### 2B.15 — Pemulihan Modul Docs + Hardening Akses
- **P0 dari 2B.14 diselesaikan**: tabel `docscat`/`docs` dibuat (migration baru, 2 file), fungsi Docs dipulihkan penuh (landing, list, detail, kategori, create/edit dokumen, upload PDF), role-restriction berlapis (route → component → view + FK RESTRICT).
- View composer `docs_sidebar` dibenahi; tidak lagi 500.
- 106 assertions PASS (rollback, temporary dihapus); rollback migration teruji.
- Sumber: `LAPORAN_IMPLEMENTASI_PEMULIHAN_DOCS_2B_15.md`.

---

## Sedang berjalan

- **2B.12** — LULUS; penutupan formal (UAT browser) menunggu persetujuan pemilik.
- **2B.11** — implementasi selesai; penutupan formal (UAT browser) menunggu persetujuan pemilik.

---

## Direncanakan (membutuhkan persetujuan terpisah)

### Pekerjaan tersisa dari hasil UAT (`LAPORAN_UAT_OTOMATIS_MENYELURUH_2B_14.md`)
- Perbaikan docs scaffold dormant (LOV unused) — opsional, menunggu persetujuan.

P1 F-2, P2 F-4 (akun admin), P3 (A3 N+1 `daftarRapor` — 2B.17; A4 logging minimal komponen — 2B.18), dan P0 artefak backup (2B.20–2B.21) telah diselesaikan/diputuskan; sisanya menunggu keputusan pemilik (lihat `LAPORAN_FINAL_PRODUCTION_READINESS.md` §8); lihat bagian Selesai.

---

## Membutuhkan keputusan pengguna

1. **G6 — Lingkup rekap lintas kelas**: rekap hanya menghitung kelas aktif TA aktif; hari-hari di kelas sebelumnya (siswa pindah mid-semester) tidak dihitung. Apakah diinginkan rekap penuh semester lintas kelas?
2. **G5 — Fallback KKTP**: `nilai_akhir.nilai_kktp` adalah snapshot finalisasi; bila null tetap ditampilkan `-`. Apakah diinginkan fallback ke `kktp_mapel.nilai_minimal`? (Tidak direkomendasikan tanpa keputusan eksplisit.)
3. **Kebijakan ekspor**: apakah export lain (mis. ketidakhadiran) diperlukan selain rekap?
4. ~~**P2 akun admin**~~ — **Diputuskan (2B.16)**: superadmin cukup untuk operasional; tidak perlu seeder akun `admin`. Bila kelak diperlukan, akun dapat dibuat melalui mekanisme yang sudah tersedia.
5. ~~**P1 (F-2)**~~ — **Selesai (2B.16)**: `/berandaguru` & `/jadwalguru` dikembalikan ke `check.level`; lihat `LAPORAN_HARDENING_ROUTE_GURU_2B_16.md`.

---

## Aturan pembaruan roadmap

- Roadmap diperbarui hanya setelah persetujuan pemilik proyek.
- Tahap baru tidak ditambahkan tanpa persetujuan eksplisit.
- Dokumen `AGENTS.md` dan `desain.md` tidak diubah untuk memasukkan roadmap.
