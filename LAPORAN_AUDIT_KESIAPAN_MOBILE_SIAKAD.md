# Laporan Audit Kesiapan Backend SIAKAD Mobile (Fase 0)

Tanggal audit: 16 November 2026
Ruang lingkup: repository SIAKAD (Laravel 12 + Livewire 3) — audit **read-only**
Target: menilai kesiapan backend melayani aplikasi Flutter untuk role `siswa`, `guru`, `wali`
Acuan: `AGENTS.md`, source code aktual, `routes/web.php`, 89 migrasi, 67 model, 70 komponen Livewire, service layer, middleware, konfigurasi.

> **Pernyataan integritas audit**
> - Tidak ada file aplikasi yang dibuat/diubah/dihapus; tidak ada migration/seeder dijalankan; `.env` tidak dibaca; tidak ada operasi Git yang mengubah repository.
> - Verifikasi behavior dilakukan dengan membaca kode dan menjalankan `php artisan route:list` (110 entri) pada sesi audit sebelumnya; tidak ada UAT ulang.
> - Setiap temuan diberi status **READY / PARTIAL / NOT READY / NOT VERIFIED**. Fakta dipisahkan dari asumsi/rekomendasi.

---

## 1. Executive Summary

Backend SIAKAD saat ini adalah **aplikasi monolith web (Livewire full-page)** — bukan API. Seluruh alur data untuk siswa, guru, dan wali tersedia dan sudah diverifikasi melalui UAT internal (lihat `ROADMAP.md`), tetapi disajikan sebagai HTML via Livewire, bukan JSON. Tidak ada satu pun endpoint API fungsional kecuali contoh bawaan (`GET /api/user`).

Kabar baik: aturan bisnis sudah terkonsentrasi di lapisan **service** (`app/Services`), terpisah dari UI Livewire — sehingga logika dapat digunakan ulang oleh API tanpa digandakan. Sanctum sudah terpasang di dependency dan model `User` sudah memakai `HasApiTokens`, tetapi **belum ada satu pun pembuatan token**, tidak ada login/logout API, dan tidak ada route API selain contoh tersebut.

Kesiapan per area (ringkas):

| Area | Status | Catatan singkat |
|---|---|---|
| A. Authentication & API readiness | NOT READY | Web session + captcha; Sanctum terpasang & belum dipakai; tidak ada token auth, tidak ada login/refresh/revoke API |
| B. Role & authorization | PARTIAL | Model otorisasi solid di web (`check.level` + `menu.access` + `abort_unless` + scoping), **belum ada padanan JSON/API**; risiko IDOR rendah tapi pola sebagian masih inline di komponen |
| C. Business logic reusability | READY (adaptasi kecil) | Service layer lengkap & terpisah dari UI; perlu pembungkus API (resources/validasi), bukan penulisan ulang aturan |
| D. Data relationships | READY | Relasi jelas (users↔siswa/guru, wali↔siswa, keuangan ledger); eager loading sudah dipakai selektif; perlu konvensi scope API |
| E. Finance API readiness | PARTIAL | Seluruh invariant (ledger, verifikasi server-side, idempotensi, reversal) ada di `PaymentService`/`KoreksiKeuanganService`; belum ada endpoint JSON, belum ada bukti pembayaran online via mobile, belum ada kebijakan "client tidak dipercaya" untuk kalkulasi |
| F. Website transition | PARTIAL | Memetakan mudah; route/menu lama tidak boleh dinonaktifkan sekarang; logika web masih diperlukan admin/superadmin |
| G. Infrastructure | PARTIAL | Rate limiting API 60/menit terdaftar; CORS `*` terlalu longgar; tidak ada API versioning, response convention, push notification, atau antrian |

**Kesimpulan singkat:** backend **siap dijadikan fondasi API** (logika inti reusable dan terjaga), tetapi lapisan API (auth token, middleware, validasi, resources, response convention, rate limit, hardening) **belum ada dan harus dibangun**. Estimasi kompleksitas bukan di logika bisnis, melainkan pada fondasi API + pengujian end-to-end dengan Flutter. Tidak ada kebocoran data yang ditemukan pada audit membaca kode, namun seluruh kesimpulan keamanan API bersifat **NOT VERIFIED** karena API belum ada untuk diuji.

---

## 2. Kondisi Arsitektur Saat Ini

- **Stack:** PHP ^8.2, Laravel ^12.0, Livewire ^3.7 (full-page components), MySQL (`templatedb` di Laragon), Bootstrap/CoreUI offline.
- **Pola:** monolith; `routes/web.php` berisi route eksplisit (grup middleware) **+ route dinamis** dari tabel `appmenunew` (URL menu → `App\Livewire\{Ucfirst}Component`). Route eksplisit utama terdaftar di grup `auth` + `cek.akun` + `force.password`? Tidak seluruhnya — lihat temuan B-03.
- **Kernel:** `app/Http/Kernel.php` — middleware global minimal (TrustProxies, CORS, Maintenance, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull); group `web` dan `api` standar.
- **Service layer:** `app/Services/` — absensi, penilaian, keuangan (`Services/Keuangan/*`), pembayaran (`Services/Payments/*`), import (`Services/Import/*`), akademik, dan `KonteksAkademikService` (sumber tunggal TA/semester aktif).
- **Support:** `app/Support/` — `KonteksAkademik`, `Lov`, `Hari`, `NamaFile`, `BerandaUrl`, `HelpCenter`.
- **Otorisasi:** custom level-based (`users.level`: `superadmin|admin|guru|siswa|wali`; `walikelas` belum diimplementasikan — ditolak `LoginBlockService`). Middleware: `check.level:...` → `abort(404)`; `menu.access` → cek `usermenu` → `abort(404)`; komponen memproteksi diri dengan `abort_unless`.
- **Notifikasi:** custom kanal database (`NotifikasiService`, tabel `notifikasi`/`notifikasi_penerima`), bell via `NotificationBellComponent` (guru/siswa/wali, 403 selainnya).
- **API terpasang:** `routes/api.php` (HANYA contoh `GET /api/user` di belakang `auth:sanctum`); `RouteServiceProvider` mendaftarkan prefix `api` + `RateLimiter::for('api')` 60/menit per user/IP; `config/cors.php` → paths `api/*`, `sanctum/csrf-cookie`, `allowed_origins: ['*']`, `supports_credentials: false`.
- **Sanctum:** terpasang (`laravel/sanctum` di composer.lock), `config/sanctum.php` ada (stateful domains dari env, guard `web`, `expiration => null`), model `User` memakai `HasApiTokens`, **tidak ada satu pun `createToken()` di codebase**.
- **Events:** `EventServiceProvider`/`BroadcastServiceProvider` standar; tidak ada broadcasting/realtime (polling/refresh manual).
- **Log:** `spatie/laravel-activitylog` (tabel `activity_log`) untuk aksi tertentu; `opcodesio/log-viewer` (dibatasi Gate `viewLogViewer` → superadmin, fail-closed).

---

## 3. Hasil Audit per Area

Legenda: **READY** = siap dipakai apa adanya; **PARTIAL** = ada fondasi, perlu penyesuaian; **NOT READY** = belum ada; **NOT VERIFIED** = belum dapat diuji (biasanya karena fiturnya belum ada).

### A. Authentication & API Readiness

| # | Temuan | Status | Bukti | Dampak mobile | Rekomendasi |
|---|---|---|---|---|---|
| A-01 | Login web: `POST /login` (`LoginController@authenticate`) dengan username + password + **captcha** (`mews/captcha`, `refresh-captcha`); blokir akun via `LoginBlockService::cekBolehLogin()` (status data induk per level); sukses → redirect sesuai level (`BerandaUrl::untuk`) atau `/setting` bila wajib ganti password | READY (web) / NOT READY (API) | `LoginController.php`, `LoginBlockService.php`, `BerandaUrl.php`, `routes/web.php` baris 82–93 | Tidak ada jalur login untuk Flutter; captcha + session tidak cocok untuk mobile | API login khusus: `POST /api/v1/auth/login` (tanpa captcha web; gunakan rate limit + optional hCaptcha/Cloudflare Turnstile di mobile jika kelak diperlukan); terbitkan Sanctum token |
| A-02 | Sanctum terpasang & model siap, tetapi belum ada token issuance/revocation | PARTIAL | `composer.lock` (laravel/sanctum), `config/sanctum.php` (expiration `null`, guard `web`), `app/Models/User.php` (`HasApiTokens`), `routes/api.php` (hanya `/api/user` contoh) | Flutter tidak dapat autentikasi saat ini | Bangun alur: login → `createToken('mobile', [ability])` → simpan token per perangkat; endpoint `logout`, `logout-all`, token pruning; set `expiration` wajar (mis. 30–90 hari) atau refresh token pattern |
| A-03 | Tidak ada refresh token / expiry: `sanctum.expiration = null` | PARTIAL | `config/sanctum.php` | Token bocor = akses permanen sampai dicabut manual | Tetapkan expiry + kemampuan revoke per-device; pertimbangkan refresh ringan (rotasi token saat login ulang) |
| A-04 | Tidak ada halaman/fitur daftar device/token untuk user | NOT READY | Tidak ada referensi `personal_access_tokens` management di codebase; route `/api/user` tidak pernah dipanggil dari mana pun (frontend web) | UX keamanan minim; user tidak bisa mencabut sesi mobile | Tambahkan `token_name` + `last_used_at`; UI sederhana "Perangkat saya" (minimal untuk superadmin/siswa) atau endpoint revoke-all |
| A-05 | Ganti password: wajib di web (`ForcePasswordChange` + kolom `users.wajib_ganti_password`); setelah sukses reset flag | READY (web) / NOT READY (mobile) | `ForcePasswordChange.php`, `AuthController@update` (mewajibkan lastPassword untuk ganti password), kolom `wajib_ganti_password` | Mobile harus menghormati flag wajib ganti password | Saat login API, jika `wajib_ganti_password === true` arahkan mobile ke screen ganti password (block endpoint lain sampai selesai) — padanan middleware `force.password` untuk API |
| A-06 | Akun nonaktif: `CekAkunAktif` (`cek.akun`) — guru/siswa harus `status=aktif` di data induk; admin/wali via `users.status_akun=aktif`; logout + invalidate session | READY (web) / NOT READY (API) | `CekAkunAktif.php`, `LoginBlockService.php` | Token tidak otomatis mati saat akun dinonaktifkan | Padanan middleware API (`cek.akun.api`) yang menolak token bila `cekBolehLogin()` false + revoke token terkait |
| A-07 | Logout web: `POST /logout` (`LoginController@logout`) | READY (web) / NOT READY (API) | `routes/web.php` (group `auth`), `LoginController` | — | `POST /api/v1/auth/logout` (revoke current token), `POST /api/v1/auth/logout-all` |
| A-08 | Tidak ada throttle pada `POST /login` (captcha menjadi satu-satunya pertahanan brute force selain dB existence) | PARTIAL | `routes/web.php` baris 91 (login tanpa `throttle:`), `mews/captcha` | Mobile login harus dilindungi throttle ketat (mis. 5/menit/IP+identifier) | `throttle:5,1` pada login API + log percobaan gagal (sudah ada pola `Log::` di LoginController? — verifikasi lebih lanjut di luar lingkup audit) |
| A-09 | Password hashing: bcrypt/argon2 via `Hash`; `password` kolom tidak pernah dikirim ke view; login memakai `Hash::check` | READY | `AuthController`, `config/hashing.php` | — | Pertahankan; jangan pernah ekspos hash di API (gunakan `UserResource` tanpa `password`) |
| A-10 | Migrasi Sanctum (`personal_access_tokens`) | READY | tabel `personal_access_tokens` tersedia? **NOT VERIFIED** — tidak ada migrasi Sanctum di `database/migrations` (89 migrasi dicek: tidak ada `create_personal_access_tokens_table`) | Login API akan gagal (`table not found`) jika tabel belum dimigrasi | Tambah migrasi baru `create_personal_access_tokens_table` (aditif) **sebagai bagian fase implementasi** — dengan persetujuan |
| A-11 | `stateful` domains untuk Sanctum (SPA) vs token mobile | PARTIAL | `config/sanctum.php` (env `SANCTUM_STATEFUL_DOMAINS`, default localhost) | Tidak relevan untuk Flutter (token Bearer), tapi berpotensi membingungkan penerapan | Dokumentasikan: mobile pakai Bearer token; web pakai session; jangan aktifkan stateful cookie di mobile |

### B. Role & Authorization

| # | Temuan | Status | Bukti | Dampak mobile | Rekomendasi |
|---|---|---|---|---|---|
| B-01 | Model otorisasi solid di web: grup `check.level:...` (abort 404) + `menu.access` (abort 404) + `abort_unless` di metode publik komponen + scoping kepemilikan di query/service | READY (pola) / NOT READY (API) | `CheckUserLevel.php`, `MenuAccessMiddleware.php`, `routes/web.php` (grup level), komponen e.g. `Bell`, `BayarTagihanComponent`, `RefundDokumenDownloadController` | Pola tidak dapat dipakai langsung di JSON; harus ada middleware API + policy setara | Buat middleware API `EnsureLevel` (setara `check.level`, JSON 403) dan policy/gate per resource; jangan `menu.access` untuk API (ganti dengan ability/ability token) |
| B-02 | Siswa: hanya data miliknya — guard "pastikan subjek" dipakai konsisten (`RaporSiswaService::pastikanSiswa(User $user): Siswa`; `BayarTagihanComponent` memvalidasi `$siswa->idsiswa === $tagihan->siswa_idsiswa`; histori absensi scoped; pembayaran `payment_attempt.siswa_idsiswa` terikat tagihan) | READY (di web) | `RaporSiswaService.php` (method `pastikanSiswa`, `daftarRapor`, `detail`, `rekapKehadiran`), `BayarTagihanComponent.php::selectSiswa/bayar/periksaStatus`, `PaymentService::buatAttempt` (lock tagihan + validasi sisa) | API mudah diamankan bila mengikuti pola yang sama | API wajib menerima "subjek dari token" (`auth()->user()->siswa`), bukan `siswa_id` dari body/query |
| B-03 | Guru: akses kelas dibatasi ke wali kelas (`getKelasTersedia`, `checkGuruAccess`, `getSiswaAktif(..., User $user ...)`) dan jadwal mengajar; validasi konteks (`validateContext`) | READY (di web) | `AbsensiHarianService.php` (method signatures terverifikasi), `JadwalGuruComponent`, `Kelas` (`guru_idguru`), `gurumapel`, `penugasan_mengajar` | API guru cukup ikut scoping ini | Ulangi scoping di setiap endpoint guru (jangan lift-and-shift tanpa scoping); tambahkan test per role |
| B-04 | Wali: hanya siswa relasi verified & aktif — dipakai di semua titik akses (RefundDokumen: `status_hubungan = 'verified'` + `tanggal_berhenti IS NULL`; `BayarTagihanComponent` memilih siswa relasi; `WaliAkademikComponent` scoping serupa) | READY (di web) | `RefundDokumenDownloadController` (blok wali), `WalimuridService`, `BayarTagihanComponent` | API wali aman bila pola sama | Query scope/relasi dipakai ulang; jangan pernah percaya `siswa_id` dari input wali |
| B-05 | Admin/superadmin tidak untuk mobile (keputusan tetap di website) | N/A (by design) | — | — | Route API hanya untuk siswa/guru/wali; role web eksplisit ditolak (403) dengan pesan jelas |
| B-06 | Tidak ada Policy/Gate untuk model selain `viewLogViewer`; tidak ada direktori `app/Policies` | PARTIAL | `app/Policies` tidak ada; `AuthServiceProvider` hanya Gate log viewer | Logic otorisasi hidup di service/komponen (imperatif), bukan terpusat → sulit di-audit & di-uji | Pada API fase, pertimbangkan Policy per resource untuk endpoint sensitif (absensi guru, nilai) |
| B-07 | IDOR: tidak ditemukan endpoint yang menerima ID subjek dari klien tanpa scoping pada komponen yang diaudit (`BayarTagihanComponent`, `RaporSiswaService`, `RefundDokumenDownloadController`, `HistoriAbsensiSiswaComponent`); semua scoping berbasis user login | READY (spot-check) / NOT VERIFIED (menyeluruh) | lihat B-02..B-04; spot-check terbatas | Risiko terbesar adalah implementasi API baru yang salah, bukan kode lama | Setiap endpoint API wajib review "siapa yang boleh melihat apa" + UAT role matrix |
| B-08 | `walikelas` tidak diimplementasikan; ditolak default | N/A | `LoginBlockService` | — | Dokumentasikan; jangan expose level ini di API |
| B-09 | `menu.access` mengandalkan nama route (`appmenunew.url`) sebagai kunci otorisasi — rapuh untuk API (tidak ada route name untuk resource JSON) | PARTIAL | `MenuAccessMiddleware.php` | API tidak bisa memakai `menu.access` | Ganti dengan Sanctum abilities / middleware level di API; biarkan `menu.access` khusus web |
| B-10 | Beberapa komponen meng-**abort** dengan halaman 404 web; di API harus jadi 403/404 JSON | PARTIAL | Pola `abort_unless` di banyak komponen | Kontrak error API harus didefinisikan | Response convention: 401 unauthenticated, 403 unauthorized (akun aktif/role), 404 not found, 422 validasi, 429 throttle, 500 generic |

### C. Business Logic Reusability

Seluruh modul diaudit terhadap prinsip "logika di service, UI di Livewire". Hasil: **mayoritas reusable dengan adaptasi pembungkus (DTO/Resource/Form Request), tanpa mengubah aturan bisnis**.

| Modul | Reusable? | Bukti (service/method) | Pekerjaan yang dibutuhkan untuk API |
|---|---|---|---|
| Profil & akun (pengguna) | YA | `AuthController@update` (nama/email/password + wajib ganti password), `SiswaService`, `GuruService` | Endpoint `GET/PATCH /api/v1/me` + `POST /api/v1/me/password`; ekstrak validasi ke Form Request; **tanpa** foto kalau mobile belum butuh (scope fase) |
| Jadwal & kalender akademik | YA | `JadwalSiswaService::kelasAktif(int $siswaId, KonteksAkademik $konteks)`, `jadwal(KonteksAkademik, array $kelasIds, ?int $hari)`, `urutkan`, `perHari`; `KonteksAkademikService::ambil()` | Endpoint jadwal siswa/guru; perlu `JadwalGuruService`-style wrapper? Guru: logika query ada di komponen (`JadwalGuruComponent`) — ekstraksi kecil diperlukan (lihat G-05) |
| Absensi harian & per jam (guru input + siswa histori) | YA | `AbsensiHarianService::getKelasTersedia/getSiswaAktif/validateContext/checkGuruAccess/saveAbsensiMassal/editAbsensi/getAbsensiKelasHari`; `AbsensiPerjamService`; `HistoriAbsensiSiswaComponent` (logika rekap inline) | Ekstrak rekap histori siswa ke service (`RekapAbsensiService`) agar dipakai web & API tanpa duplikasi; endpoint baca untuk siswa, endpoint tulis untuk guru |
| Penilaian & rapor | YA | `NilaiSiswaService`, `FinalisasiNilaiService`, `KoreksiNilaiService`, `RaporSiswaService` (`pastikanSiswa/daftarRapor/detail/rekapKehadiran`), `RaporWaliKelasService` | Endpoint rapor siswa (read-only); input nilai guru (`SimpanNilaiService`?) — `InputNilaiComponent` logikanya sebagian inline; ekstraksi terarah |
| Pengumuman & baca | SEBAGIAN | `PengumumanService::buat/ubah/publish/...` (draf→publish; snapshot penerima); `PengumumanSiswaComponent` baca + `PengumumanBaca` | Endpoint list/detail/mark-read untuk siswa/guru/wali; publish tetap web-only (superadmin) |
| Notifikasi | YA | `NotifikasiService::buat/list/hitungBelumDibaca/tandaiDibaca(*)` (snapshot penerima; idempotent) | Endpoint list + unread count + mark read; polling sederhana di Flutter; tidak ada push (lihat G-08) |
| Portal wali | YA (scoping wajib) | `WalimuridService` (relasi, verifikasi, putus), `WaliAkademikComponent` (read-only), `WaliAntrianVerifikasiComponent` (admin) | Endpoint wali: children list (relasi verified-aktif), akademik read-only anak, tagihan anak; **verifikasi/aktivasi tetap web-only** |
| Pembayaran & keuangan | YA | `PaymentService::buatAttempt/sisaTagihan/terpakaiTagihan/hitungSaldoTersedia/saldoSiswa/sinkronStatusAttempt/verifikasiPembayaranManual/tolakPembayaranManual/gunakanSaldo/riwayatPenggunaanSaldo`; `TagihanService`; `KuitansiService`; `RefundDokumenService`; `RekonsiliasiKeuanganService` (read-only); `DendaPembebasanService`; `KoreksiKeuanganService` | Endpoint tagihan/saldo/riwayat/permintaan bayar/sinkron status/penggunaan saldo; POST pembayaran membuat attempt (bukan menerima kartu); bukti/kuitansi/dokumen via download controller (pola otorisasi server-side dipertahankan) |
| Data master & penempatan | SEBAGIAN | `SiswaService`, `KelasService`, `PenempatanService` | Tidak diperlukan untuk app siswa/guru/wali (admin tetap website) |

**Aturan bisnis yang TIDAK BOLEH diubah** (konfirmasi): seluruh invariant ledger (`kunci tagihan + cek sisa`, alokasi maksimal sisa, saldo sebagai alokasi `jenis='saldo'` dengan lawan provider `saldo`), verifikasi server-side Midtrans, idempotensi webhook/settlement, reversal (`reversal-refund`/`reversal-koreksi`) dan provider pengganti `koreksi`, self-approval ditolak, `lockForUpdate` pada baris kritis.

### D. Data Relationship

| # | Relasi / aspek | Status | Bukti | Risiko & catatan |
|---|---|---|---|---|
| D-01 | `users` → `siswa` (hasOne via `user_iduser`, unique) & `users` → `guru` (hasOne via `user_iduser`) | READY | `User::siswa()/guru()`; migrasi `create_siswa_table`, `create_guru_table` | `siswa` **tidak wajib** punya `user` (FK dari siswa ke users, unique) — akun siswa dibuat terpisah dari data siswa; API harus menangani "siswa tanpa akun" (tidak bisa login) |
| D-02 | Wali ↔ siswa: many-to-many via `walimurid` (status_hubungan: pending/verified/putus?, `tanggal_berhenti` nullable); histori immutable (`walimurid_history`, `waliakun_history` + trigger DB) | READY | `walimurid` migration, `WalimuridService`, `RefundDokumenDownloadController` (pola query) | Query standar "relasi verified & aktif": `where('status_hubungan','verified')->whereNull('tanggal_berhenti')` — harus distandarisasi menjadi scope/relasi (`Walimurid::aktif()`) |
| D-03 | Ambiguitas status string: nilai `status_hubungan` diperiksa via string literal di berbagai tempat (`'verified'`) — tidak ada enum PHP terpusat | PARTIAL | beberapa file memakai literal `'verified'` | Rawan drift; pusatkan konstanta/relasi untuk API (tanpa mengubah data) |
| D-04 | Guru ↔ mapel/kelas: `gurumapel`, `penugasan_mengajar`, `kelas.guru_idguru` (wali kelas); rombel via `kelassiswa` per semester | READY | migrasi terkait, `GuruService`, `PenugasanMengajarService` | Dua jalur (wali kelas vs mapel) menentukan scope — gunakan KEDUANYA pada endpoint guru (union), bukan salah satu |
| D-05 | Siswa → tagihan/pembayaran: `tagihan.siswa_idsiswa`, `pembayaran.siswa_idsiswa`, `payment_attempt.siswa_idsiswa`, `pembayaran_alokasi.siswa_idsiswa` | READY | migrasi `create_tagihan_table`, `create_pembayaran_table`, `create_payment_attempt_table`, `create_pembayaran_alokasi_table` | Semua query keuangan wajib filter `siswa_idsiswa` milik subjek; `payment_attempt.public_token` aman dibagikan (opaque) — tetap jangan pernah kirim `idpayment_attempt` internal |
| D-06 | Wali → tagihan anak: via `walimurid` (bukan FK langsung) — selalu join relasi verified-aktif | READY | `RefundDokumenDownloadController` (pola), `BayarTagihanComponent::selectSiswa` | Jangan expose tagihan anak tanpa cek relasi; pertimbangkan view `v_wali_siswa_aktif` untuk efisiensi (butuh migrasi — keputusan pemilik) |
| D-07 | `jadwal_pertemuan` (snapshot publikasi) → absensi per jam | READY | `jadwal_pertemuan` migration, `AbsensiPerjamService` | Mobile membaca snapshot yang sama — konsisten dengan yang dipublikasikan |
| D-08 | `keuangan` lainnya: `tarif`, `tarif_riwayat`, `beasiswa`, `beasiswa_siswa`, `denda`, `kuitansi`, `refund_*`, `koreksi_pengajuan`, `rekonsiliasi_hasil/temuan` | READY | migrasi `2026_11_12_*` | Konsisten; API hanya expose agregat/riwayat milik sendiri + dokumen dengan otorisasi server-side |
| D-09 | Eager loading: komponen memakai `with([...])` selektif; belum ada konvensi API Resource | PARTIAL | contoh: `RekonsiliasiKeuanganComponent`, `RaporSiswaService::daftarRapor` | Saat API dibangun, N+1 perlu dikontrol (Resource + `whenLoaded`), bukan dibiarkan |
| D-10 | `notififikasi_penerima` snapshot penerima — histori tidak berubah saat user dihapus (RESTRICT?) | READY/PARTIAL | migrasi `create_notifikasi_penerima_table` (2026_11_10) | Data histori tetap konsisten; API list hanya milik penerima login |
| D-11 | Potensi kebocoran via ID mentah: semua tabel memakai integer auto-increment (mis. `idsiswa`, `idpembayaran`) | PARTIAL | seluruh migrasi | Risiko enumeration; mitigasi: selalu scoping by-owner (sudah pola standar) + jangan expose `id` internal bila tidak perlu (pakai UUID? — **jangan diubah sekarang**; cukup scoping) |

### E. Finance API Readiness

| # | Temuan | Status | Bukti | Dampak mobile | Rekomendasi |
|---|---|---|---|---|---|
| E-01 | Tagihan: baca milik sendiri (siswa/wali) — `BayarTagihanComponent::render` menghitung sisa lewat `PaymentService::terpakaiTagihan` & `sisaTagihan`; status: `belum_dibayar/dibayar_sebagian/lunas/dibebaskan/dibatalkan` | READY (logika) / PARTIAL (API) | `BayarTagihanComponent.php`, `PaymentService::sisaTagihan/terpakaiTagihan`, migrasi `tagihan` | Endpoint `GET /tagihan` siap dibangun di atas service yang ada | Reuse `sisaTagihan/terpakaiTagihan` — **jangan** hitung ulang di Flutter |
| E-02 | Riwayat pembayaran: `payment_attempt` (online) + `pembayaran` (ledger; `provider` midtrans/manual/saldo/koreksi) + `riwayatPenggunaanSaldo` | READY (logika) | `PaymentService::riwayatPenggunaanSaldo`, model `Pembayaran`, `PembayaranAlokasi` | Endpoint riwayat + detail + status attempt | Satu endpoint agregat (union attempt+ledger) atau dua endpoint terpisah; putuskan di Fase 4 |
| E-03 | Instruksi pembayaran online: `buatAttempt()` (sisa dari lock tagihan; order_id unik; Snap token; expiry 24h; attempt `created`) | READY (logika) | `PaymentService::buatAttempt`, `MidtransGateway::createSnapTransaction` | Mobile menerima Snap token + `redirect_url`; tanpa menyerahkan logika harga ke klien | Flutter tidak pernah menentukan nominal; `gross_amount` dari server |
| E-04 | Status transaksi: webhook (`POST /webhook/midtrans`) + polling `GET /api/v1/.../status` via `sinkronStatusAttempt()` + perintah `pembayaran:cek-status` | READY (logika) / PARTIAL (akses) | `MidtransWebhookController`, `PaymentService::sinkronStatusAttempt`, `CekStatusPembayaran`, `BayarTagihanComponent::periksaStatus` | Mobile dapat polling status dengan aman | Endpoint `GET /api/v1/pembayaran/attempts/{public_token}/status` (batasi ke pemilik attempt); gunakan `public_token`, bukan ID internal |
| E-05 | Bukti pembayaran manual: unduh PDF kuitansi & bukti (otorisasi server-side; checksum refund) — download controllers | READY (otorisasi) / NOT READY (mobile) | `PembayaranManualDownloadController` (`kuitansi`, `bukti`), `RefundDokumenDownloadController` (`dokumen`, checksum + realpath guard), route `pembayaran/kuitansi/{id}`, `pembayaran/bukti/{id}`, `refund/dokumen/{id}` | Mobile butuh URL unduh + header auth | Reuse controller pola sama untuk API (token Sanctum + cek kepemilikan); **jangan** expose path file |
| E-06 | Refund & dokumen refund: pengajuan admin → approval superadmin → reversal; dokumen PDF terproteksi | READY (logika+guard) / NOT READY (mobile) | `RefundKoreksiComponent`, `KoreksiKeuanganService`, `RefundDokumenService`, `RefundDokumenDownloadController` | Mobile siswa/wali cukup **melihat status refund** + unduh dokumen miliknya | Endpoint status refund milik sendiri + download terkontrol; pengajuan refund tetap web-only (admin/superadmin) |
| E-07 | Verifikasi server-side Midtrans (Get Status API + signature SHA512 + cross-check nominal + whitelist payload) & **tidak ada** kredensial di kode | READY | `MidtransGateway.php` (seluruh file), `PaymentService::prosesNotifikasi/settle` | Fondasi kuat untuk mobile | Pertahankan apa adanya; API pembayaran hanya bagi-bagi instruksi, tidak pernah menerima "konfirmasi dari klien" |
| E-08 | Idempotensi: attempt `success` tidak diproses ulang; ledger max 1 per attempt; alokasi diproteksi; perintah scheduler idempotent; webhook → `duplicate` | READY | `PaymentService::settle` (komentar + kode), `spp:terbit`, `tagihan:pengingat` | Mobile retry aman (mis. tap dua kali) | Tetap idempotent di API (mis. `riwayatPenggunaanSaldo`/attempt create hanya dari lock server) |
| E-09 | **Yang TIDAK BOLEH dipercaya ke Flutter:** nominal tagihan/saldo, sisa, status pembayaran, keputusan "lunas", verifikasi webhook, perhitungan beasiswa/diskon/denda, redirect pembayaran sukses dari klien, ID attempt internal, session token web, `public_token` tanpa scoping | — | — | — | Flutter hanya menampilkan + membayar (Snap); seluruh keputusan di server |
| E-10 | Midtrans **belum teruji end-to-end** (kredensial belum ada) | NOT VERIFIED (kritis) | `ROADMAP.md` 2B.31/2B.35; `config/services.php` (kosong = gateway nonaktif); `Http::fake()` dalam uji | Alur pembayaran mobile bergantung pada gateway yang belum terbukti | Jangan bangun Fase 4 sebelum UAT sandbox Midtrans lulus; pertimbangkan manual-only untuk MVP mobile bila gateway belum siap |
| E-11 | Ledger/riwayat bersifat immutable; koreksi via reversal — mobile **read-only** untuk riwayat | READY | `PaymentService` (settle hanya menambah), reversal `reversal-refund/koreksi` | — | Mobile tidak mengubah transaksi; refund hanya melalui alur web admin/superadmin |
| E-12 | Kuitansi manual (non-Midtrans): `KuitansiService` — pembayaran manual terverifikasi dapat membuat kuitansi | READY (logika) | `KuitansiService` | Mobile unduh kuitansi (otorisasi seperti bukti) | Reuse otorisasi download |
| E-13 | `payment_attempt.public_token` sudah disiapkan untuk akses publik (BayarPublik) | READY | `bayar-publik/{token}` route, `BayarTagihanComponent::tampilkanTautan`, migration `add_attempt_and_payer_to_pembayaran`/`create_payment_attempt` | Cocok untuk deep-link hasil pembayaran | Gunakan `public_token` sebagai identifier publik di API, bukan ID numerik |

### F. Website Transition

| # | Temuan | Status | Bukti | Dampak | Rekomendasi |
|---|---|---|---|---|---|
| F-01 | Route/menu yang kelak tidak dipakai siswa/guru/wali via website: `berandasiswa`, `jadwalsiswa`, `absensi-siswa`, `profil-siswa`, `pengumuman-siswa`, `raporsiswa`, `bayartagihan`, `notifikasi` (siswa), `berandaguru`, `jadwalguru`, `inputnilai`, `kktpmapel`, `komponennilai`, `finalisasirapor`, `koreksinilai`, `raporwalikelas`, `penugasanmengajar`, `notifikasi` (guru), `wali-*`, `bayartagihan` (wali) | PARTIAL (teridentifikasi) | `routes/web.php` (eksplisit + loop dinamis `appmenunew`), seeder menu per paket | Risiko: mematikan route terlalu cepat memutus akses yang masih dipakai | Jangan nonaktifkan apa pun sekarang; siswa/guru/wali tetap bisa akses web paralel selama transisi; deprecation bertahap setelah mobile stabil |
| F-02 | Komponen Livewire yang logikanya tetap diperlukan (dipindahkan/diekstrak ke service): profil/akun, jadwal, absensi, rapor, pengumuman, notifikasi, keuangan | PARTIAL | lihat C | Logika tidak hilang, pindah lapisan | Refactor terarah per fase (bukan big-bang), dengan test regresi |
| F-03 | Fitur yang WAJIB ada di API sebelum website dikurangi: semua read siswa/wali/guru + bayar tagihan (siswa/wali) + notifikasi + ganti password | PARTIAL (daftar) | bagian C & E | Tanpa API lengkap, pengurangan web melumpuhkan pengguna | Checklist "mobile feature parity" per role sebelum menonaktifkan menu web |
| F-04 | Risiko jika route lama langsung dinonaktifkan: kehilangan akses wali (belum tentu punya aplikasi siap), wali tidak bisa membayar, absensi guru berhenti, nilai tidak terisi, rapor terblokir | — | — | — | Pertahankan web untuk semua role; mobile memperluas, bukan menggantikan, sampai terbukti stabil |
| F-05 | Admin & superadmin tetap di web (keputusan): keuangan kompleks (rekonsiliasi, refund, verifikasi manual) tetap web-only | N/A (keputusan) | bagian 2 & 3.E | — | Dokumentasikan di README/kebijakan; jangan expose endpoint admin |
| F-06 | Docs (`/docs*`, `welcome-docs`) dan dashboard internal tetap web | N/A | `DocsComponent` dll. | — | Tidak disentuh mobile |
| F-07 | Perbedaan sifat data: beranda siswa menampilkan ringkasan keuangan (tagihan) — bila kelak website siswa dimatikan, semua fungsi ini harus ada di API | PARTIAL | `BerandasiswaComponent` | — | Tambahkan endpoint `GET /api/v1/siswa/beranda` sebagai agregat (bukan replikasi query 4x) |
| F-08 | Menu dinamis (`appmenunew`/`usermenu`) dipakai untuk sidebar web — mobile tidak butuh; jangan dipaksa cocok | N/A | `AppServiceProvider` view composer, `MenuAccessMiddleware` | Mobile pakai kemampuan (abilities) sendiri, bukan menu | Buat matriks kemampuan mobile terpisah dari `usermenu` |

### G. Infrastructure & Operational Readiness

| # | Temuan | Status | Bukti | Dampak mobile | Rekomendasi |
|---|---|---|---|---|---|
| G-01 | **CORS** longgar: `api/*` → `allowed_origins: ['*']`, `allowed_headers: ['*']`, `supports_credentials: false` | PARTIAL | `config/cors.php` | Mobile native tidak terpengaruh CORS, tetapi jika kelak ada web mobile/preview, `*` salah; mengizinkan origin mana pun | Persempit ke origin resmi (aplikasi web admin, bila perlu) atau nonaktifkan CORS untuk API token; jangan `*` di produksi |
| G-02 | **API versioning**: tidak ada | NOT READY | `routes/api.php`, tidak ada prefix `/v1` | Sulit evolusi tanpa memutus klien | Prefix `/api/v1/...` sejak hari pertama; resource transform menjaga kontrak |
| G-03 | **Response convention**: tidak ada (HTML error pages, `abort()` dengan view) | NOT READY | `CheckUserLevel` (abort 404), `Handler.php` | Kontrak tidak jelas untuk Flutter | Konvensi JSON: `{data}`, `{message}`, `{errors}`; kode HTTP standar; `error.code` stabil; dokumentasi di OpenAPI/static docs |
| G-04 | **Validation**: validasi hidup di komponen Livewire (`validate([...])`) dan sebagian di service; belum ada Form Request API | PARTIAL | contoh: `PembayaranManualComponent` (`'bukti' => 'nullable\|file\|mimes:jpg,jpeg,png,pdf\|max:2048'`), `InputNilaiComponent`, dll. | Duplikasi validasi bila API ditulis manual | Ekstrak validasi ke Form Request per endpoint (reusable oleh web bila mau) |
| G-05 | Sebagian kecil logika masih tertanam di komponen (mis. `JadwalGuruComponent` membaca service `urutkan/perHari`, tetapi query daftar kelas guru ada di komponen) — perlu ekstraksi ringan | PARTIAL | `JadwalGuruComponent`, `JadwalSiswaService` | API guru butuh service serupa | Ekstrak `JadwalGuruService`/`RekapAbsensiService` terarah |
| G-06 | **Rate limiting**: group `api` → `throttle:api` → 60/menit per user/IP (`RouteServiceProvider`); tidak ada limiter khusus login (web) | PARTIAL | `RouteServiceProvider`, `Kernel.php` (`api` group, alias `throttle`) | 60/menit cukup untuk polling wajar; login perlu lebih ketat; endpoint sensitif (buat attempt) perlu limiter tersendiri | Definisikan `RateLimiter::for('login')` (5/menit), `for('pembayaran')` (10/menit), `for('heavy')` opsional; jaga `api` 60/menit |
| G-07 | **Logging & audit trail**: `spatie/activitylog` aktif pada aktivitas tertentu; `Log::` di service (rekonsiliasi, saldo, webhook); log-viewer dibatasi Gate superadmin | READY (web) / PARTIAL (API) | `activity_log` migration, `AuthServiceProvider` (Gate `viewLogViewer`), `Log::` di `RekonsiliasiKeuanganService`, `PaymentService` | API perlu mencatat aksi keuangan/keamanan (login, ganti password, bayar) | Gunakan activity log di endpoint sensitif; jangan log data sensitif (password, token, PAN) |
| G-08 | **Push notification**: tidak ada (FCM/APNs belum diintegrasikan; notifikasi kanal DB saja, polling/refresh manual) | NOT READY | `NotifikasiService` (kanal database), tidak ada `laravel-notification-channels/fcm` di composer.lock | Fitur realtime mobile terbatas | Fase lanjutan: FCM via HTTP v1 (tanpa package atau dgn package — keputusan dependency) + `devices` token storage; untuk MVP: polling + local notification |
| G-09 | **Queue & jobs**: `QUEUE_CONNECTION=sync` default; tidak ada job khusus (hanya scheduler commands) | PARTIAL | `.env.example` (`QUEUE_CONNECTION=sync`), `config/queue.php` | Pengiriman notifikasi/push bila nanti ditambah harus async | Fase 2+: pertimbangkan `database` queue + worker untuk push; tidak wajib untuk MVP polling |
| G-10 | **Scheduler**: `Kernel.php` — `spp:terbit` (bulanan), `tagihan:pengingat` (07:00), `pembayaran:cek-status` (5 menit), backup nonaktif default | READY (kode) / NOT VERIFIED (server) | `Console/Kernel.php` | Mobile tidak perlu tahu; tetapi `pembayaran:cek-status` mengamankan status pembayaran mobile | Aktifkan cron/Task Scheduler di server produksi + pantau `schedule:list` |
| G-11 | **File upload & akses privat**: bukti pembayaran manual dipindah ke `storage_path('app/private/pembayaran/bukti')` (di luar disk publik); dokumen refund di `storage/app/private` + realpath guard + checksum; **disk `local` config maps ke `storage/app/public`** (tidak tertutup sepenuhnya — hati-hati saat menambah disk); `public/storage` symlink ada; foto siswa/guru di disk publik | PARTIAL | `config/filesystems.php` (disk `local` → `storage/app/public`), `PembayaranManualComponent` (rename manual ke `private/`), `RefundDokumenDownloadController` (realpath+checksum) | Foto profil via API aman (public); bukti/kuitansi harus lewat endpoint ber-otorisasi (bukan URL langsung); **jangan** mengekspose `storage/app/public` untuk berkas privat baru | Definisikan disk `private` eksplisit (`root = storage/app/private`); semua download mobile via controller terkontrol; foto publik OK |
| G-12 | **Error handling**: `Handler.php` standar; tidak ada JSON render khusus API (belum perlu karena belum ada API) | PARTIAL | `app/Exceptions/Handler.php` | — | Konvensi error API (G-03) + render khusus `api/*` (accept JSON) di fase implementasi |
| G-13 | **Deployment mobile/API**: tidak ada konfigurasi API-specific (nginx contoh, env, healthcheck); webhook Midtrans sudah ada (`webhook/midtrans`) | PARTIAL | `routes/web.php` (baris 292–295), tidak ada dokumen deployment API terverifikasi (`PROSEDUR_DEPLOYMENT_SCHEDULER_SIAKAD.md` tidak ada di repo — lihat README §17) | Deploy dual (web+API) harus dipikirkan (satu app, satu domain, prefix `/api`) | Dokumentasikan: satu Laravel app melayani web (`/`) + API (`/api/*`); HTTPS wajib; `webhook/midtrans` whitelist IP Midtrans opsional di nginx |
| G-14 | **DB**: `utf8mb4`, engine InnoDB (default), FK RESTRICT, PK `id{tabel}` — stabil | READY | konfigurasi `config/database.php`, migrasi | — | Tidak ada perubahan untuk API |
| G-15 | **Personal access tokens table**: tidak ada di migrasi | NOT READY | daftar 89 migrasi (tidak ada `personal_access_tokens`) | Sanctum token tidak bisa disimpan | Tambah `create_personal_access_tokens_table` (migrasi aditif) di fase implementasi |
| G-16 | **Observability API**: tidak ada request ID/ correlation untuk mobile | NOT READY | — | Debugging sulit | Tambah header `X-Request-Id` pada respons API + log konteks (fase G) |

---

## 4. Inventaris Service & Business Logic yang Reusable

Daftar ini adalah aset utama untuk API. "Adaptasi" berarti pembungkus (Form Request + API Resource + mapping), bukan penulisan ulang aturan.

| Service / kelas | Method kunci (terverifikasi di source) | Catatan reuse |
|---|---|---|
| `KonteksAkademikService` (`app/Services/KonteksAkademikService.php`), `Support\KonteksAkademik` | `ambil()` → TA/semester aktif | Wajib dipanggil lebih dulu oleh semua endpoint akademik |
| `JadwalSiswaService` (`app/Services/JadwalSiswaService.php`) | `kelasAktif(int $siswaId, KonteksAkademik $konteks)`, `jadwal(KonteksAkademik, array $kelasIds, ?int $hari = null)`, `urutkan(Collection)`, `perHari(Collection)` | Beasiswa siswa; guru: query serupa ada di komponen — **ekstraksi ringan disarankan** (F-02) |
| `AbsensiHarianService` (`app/Services/AbsensiHarianService.php`) | `getKelasTersedia`, `getSiswaAktif`, `validateContext`, `checkGuruAccess`, `checkEditDeadline`, `checkDuplicate`, `saveAbsensiMassal`, `editAbsensi`, `getAbsensiKelasHari`, `authorizeRecordForEdit` | Guru (tulis) + siswa (baca via `HistoriAbsensiSiswaComponent` — ekstraksi kecil) |
| `AbsensiPerjamService` | jadwal pertemuan + isi absensi per jam | Perlu method publik untuk baca histori siswa (baca `jadwal_pertemuan` + `absensi_perjam`) |
| `RaporSiswaService` (`app/Services/RaporSiswaService.php`) | `pastikanSiswa(User)`, `daftarRapor(User)`, `detail(User, int $semesterId, int $kelasId)`, `rekapKehadiran(int $kelasId, int $siswaId, int $semesterId)` | Inti portal siswa read-only |
| `RaporWaliKelasService` | daftar kelas/siswa untuk finalisasi rapor | Guru (wali kelas) |
| `NilaiSiswaService`, `FinalisasiNilaiService`, `KoreksiNilaiService`, `KomponenNilaiService`, `KktpMapelService`, `PenugasanMengajarService` | CRUD/alur nilai & KKTP | Endpoint guru untuk input nilai (dengan scoping mapel yang diampu) |
| `NotifikasiService` (`app/Services/NotifikasiService.php`) | `buat`, `daftar`, `hitungBelumDibaca`, `tandaiDibaca`, `tandaiSemuaDibaca` | API notifikasi semua role mobile |
| `PaymentService` (`app/Services/Payments/PaymentService.php`) | `buatAttempt`, `sisaTagihan`, `terpakaiTagihan`, `hitungSaldoTersedia`, `saldoSiswa`, `sinkronStatusAttempt`, `verifikasiPembayaranManual`, `tolakPembayaranManual`, `gunakanSaldo`, `riwayatPenggunaanSaldo` | Inti keuangan siswa/wali; **jangan di-expose langsung**; bungkus dengan otorisasi + rate limit |
| `MidtransGateway` (`app/Services/Payments/`) | `isConfigured`, `snapJsUrl`, `createSnapTransaction`, `verifySignature`, `getTransactionStatus` | Hanya dipakai server-side; mobile tidak pernah pegang server_key |
| `TagihanService`, `TarifService`, `DiskonService`, `DendaPembebasanService`, `KuitansiService` | daftar/penerbitan/beasiswa/denda/kuitansi | Kebanyakan admin-only (web); siswa/wali hanya baca milik sendiri |
| `KoreksiKeuanganService`, `RefundDokumenService`, `RekonsiliasiKeuanganService` | alur koreksi/refund/rekonsiliasi (superadmin) | **Jangan** expose ke mobile (web-only) |
| `WalimuridService` (`app/Services/WalimuridService.php`) | relasi & verifikasi wali | Wali endpoint (baca); verifikasi tetap web |
| `SiswaService`, `GuruService`, `KelasService`, `PenempatanService`, `SiswaService` | data master | Admin web-only; tidak untuk mobile |
| `BerandaUrl` (`app/Support/BerandaUrl.php`) | mapping level → URL | Tidak perlu di API (mobile handle sendiri) |

**Catatan ekstraksi yang disarankan (tanpa mengubah bisnis):**
1. `RekapAbsensiService` — gabung logika histori/rekap dari `HistoriAbsensiSiswaComponent`.
2. `JadwalGuruService` — pindahkan query dari `JadwalGuruComponent`.
3. `BerandaSiswaService` — ringkasan beranda (jadwal hari ini, pengumuman, tagihan) agar tidak query 4× di komponen/API.

---

## 5. Inventaris Endpoint API yang Direkomendasikan

Prefix `/api/v1`. Semua endpoint: `auth:sanctum` + `check.level.api` (baru) kecuali login & webhook. Admin/superadmin **tidak** ada endpoint.

### 5.1 Auth (publik & login)
| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/v1/auth/login` | POST | Body: `username`, `password`, `device_name`. Respons: `token`, `user{...}`, `wajib_ganti_password` |
| `/api/v1/auth/logout` | POST | Revoke token login ini |
| `/api/v1/auth/logout-all` | POST | Revoke semua token user |
| `/api/v1/auth/me` | GET | Profil singkat + flag wajib ganti password |
| `/api/v1/auth/password` | POST | Ganti password (wajib `current_password`; min 8; tidak sama dengan lama; reset `wajib_ganti_password=false`) |

### 5.2 Siswa
| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/v1/siswa/beranda` | GET | Agregat: jadwal hari ini, pengumuman terbaru, tagihan ringkas, badge notifikasi |
| `/api/v1/siswa/jadwal?tanggal=YYYY-MM-DD` | GET | Jadwal hari tertentu / minggu berjalan |
| `/api/v1/siswa/jadwal/hari-ini` | GET | Shortcut |
| `/api/v1/siswa/absensi/harian?semester=` | GET | Rekap harian (TA/semester aktif default) |
| `/api/v1/siswa/absensi/periode?dari=&sampai=` | GET | Daftar absensi harian rentang |
| `/api/v1/siswa/absensi/perjam` | GET | Riwayat per jam (jadwal_pertemuan) |
| `/api/v1/siswa/nilai` | GET | Rekap nilai + KKTP / komponen per semester |
| `/api/v1/siswa/rapor` | GET | Daftar rapor tersedia |
| `/api/v1/siswa/rapor/{semesterId}` | GET | Detail rapor (terkunci hanya setelah finalisasi) |
| `/api/v1/siswa/profil` | GET/PATCH | Baca profil; update name/email (batasi) — foto opsional fase lanjut |
| `/api/v1/siswa/pengumuman` | GET | List pengumuman (target termasuk siswa) |
| `/api/v1/siswa/pengumuman/{id}/baca` | POST | Tandai baca (idempotent) |
| `/api/v1/siswa/tagihan` | GET | Daftar tagihan + status + sisa |
| `/api/v1/siswa/tagihan/{id}` | GET | Detail (scoping milik sendiri) |
| `/api/v1/siswa/pembayaran/attempts` | POST | Buat attempt (Snap token) → body: `tagihan_id`, `payer_jenis` (siswa/wali) |
| `/api/v1/siswa/pembayaran/attempts/{public_token}/status` | GET | Status attempt (polling); scoping pemilik |
| `/api/v1/siswa/pembayaran/riwayat` | GET | Riwayat pembayaran (ledger + attempt) |
| `/api/v1/siswa/saldo` | GET | Saldo tersedia |
| `/api/v1/siswa/saldo/gunakan` | POST | Bayar tagihan pakai saldo (reuse `gunakanSaldo`) |
| `/api/v1/siswa/kuitansi/{id}` | GET | Unduh kuitansi (otorisasi) |
| `/api/v1/pengumuman{...}` | GET | — (lihat pengumuman siswa) |

### 5.3 Wali
| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/v1/wali/children` | GET | Daftar siswa relasi verified-aktif |
| `/api/v1/wali/children/{siswaId}/beranda` | GET | Ringkasan anak (jadwal, tagihan, rekap absensi) |
| `/api/v1/wali/children/{siswaId}/jadwal` | GET | Jadwal anak |
| `/api/v1/wali/children/{siswaId}/absensi` | GET | Rekap absensi anak |
| `/api/v1/wali/children/{siswaId}/nilai` & `/rapor` | GET | Read-only |
| `/api/v1/wali/children/{siswaId}/tagihan` | GET | Tagihan anak |
| `/api/v1/wali/children/{siswaId}/bayar` | POST | Bayar tagihan anak (`payer_jenis=wali`) |
| `/api/v1/wali/children/{siswaId}/saldo` | GET | Saldo anak |
| `/api/v1/wali/profil` | GET/PATCH | Profil wali; relasi & verifikasi **tidak** diekspos |

### 5.4 Guru
| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/v1/guru/beranda` | GET | Jadwal mengajar hari ini, kelas wali, pengumuman, notifikasi count |
| `/api/v1/guru/jadwal` | GET | Jadwal mengajar (filter hari) |
| `/api/v1/guru/kelas` | GET | Kelas wali + mapel/kelas yang diajar (`gurumapel`/`penugasan_mengajar`) |
| `/api/v1/guru/kelas/{kelasId}/siswa` | GET | Siswa aktif (scoping wali kelas/mapel) |
| `/api/v1/guru/absensi/{kelasId}?tanggal=` | GET | Isi absensi tersimpan |
| `/api/v1/guru/absensi` | POST | Simpan absensi massal (scoping + deadline `checkEditDeadline`) |
| `/api/v1/guru/nilai/komponen?mapel=` | GET | Komponen nilai tersedia |
| `/api/v1/guru/nilai` | POST | Simpan nilai (scoping mapel yang diampu) |
| `/api/v1/guru/pengumuman` | GET | List pengumuman (target guru/semua) |
| `/api/v1/guru/profil` | GET/PATCH | Profil + ganti password |

### 5.5 Notifikasi (semua role mobile)
| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/v1/notifikasi` | GET | Daftar (paged), filter belum dibaca |
| `/api/v1/notifikasi/belum-dibaca` | GET | Count untuk badge |
| `/api/v1/notifikasi/{id}/baca` | POST | Tandai dibaca (idempotent, scoped) |
| `/api/v1/notifikasi/baca-semua` | POST | Tandai semua |

### 5.6 Webhook & utilitas
| Endpoint | Method | Keterangan |
|---|---|---|
| `/webhook/midtrans` (eksisting) | POST | Tetap di luar `/api/*`; tidak berubah; verifikasi server-side wajib |
| `/api/v1/health` | GET | Liveness (tanpa auth; tidak membocorkan versi sensitif) |

**Catatan:** endpoint yang mutasi (POST/PUT/DELETE) wajib idempotent-friendly; attempt create aman diulang karena lock server + unique `order_id` hanya dibuat server; saldo-gunakan aman diulang? `gunakanSaldo` mengurangi tagihan — **bukan** idempotent by default; klien harus disable tombol + server tetap lock; pertimbangkan header `Idempotency-Key` opsional (fase G-07).

---

## 6. Gap Analysis (kondisi sekarang vs kebutuhan mobile)

| # | Kebutuhan mobile | Kondisi sekarang | Gap | Prioritas |
|---|---|---|---|---|
| G1 | Auth token (Bearer) untuk Flutter | Session web; captcha; `HasApiTokens` terpasang tapi tidak dipakai | **Besar** — harus dibangun (login JSON, token issue/revoke, expiry) | P0 |
| G2 | Tabel `personal_access_tokens` | Tidak ada migrasi Sanctum | Migrasi baru (aditif) | P0 |
| G3 | Migrate middleware auth API + level gating + scoping | Web middleware ada; belum ada API equivalents | Inventarisasi ulang semua guard, buat middleware JSON (401/403) | P0 |
| G4 | Response convention JSON | HTML errors | Kontrak `{data}/{message}/{errors}` + kode HTTP | P0 |
| G5 | API versioning & prefix | Tidak ada | `/api/v1` | P0 |
| G6 | Endpoint auth lengkap (login/logout/me/password) | Tidak ada | Bangun | P0 |
| G7 | Endpoint siswa (jadwal/histori/nilai/rapor/beranda/tagihan/bayar/saldo/kuitansi) | Semua logika ada di service/komponen | Pembungkus resources + scoping; agregat beranda baru | P1 |
| G8 | Endpoint wali (children/readonly akademik/tagihan/bayar) | Logika ada (read-only) | Pembungkus + scoping relasi | P1 |
| G9 | Endpoint guru (beranda/jadwal/kelas/absensi/nilai) | Sebagian logika inline di komponen | Ekstraksi service kecil | P1 |
| G10 | Endpoint notifikasi (list/count/read) | Logika ada di `NotifikasiService` | Pembungkus saja | P1 |
| G11 | Upload/unduh file terkontrol (kuitansi, bukti) | Download controllers lengkap | Adaptasi untuk API (token + otorisasi sama); disk `private` eksplisit direkomendasikan | P1 |
| G12 | Push notification (FCM/APNs) | Tidak ada | Integrasi + storage device token (bisa MVP tanpa ini) | P2 |
| G13 | OpenAPI/dokumentasi API | Tidak ada | Minimal `API.md` + contoh curl; OpenAPI opsional | P2 |
| G14 | Rate limit login/pembayaran | 60/menit `api`, tidak ada limit khusus | `login` 5/menit; `pembayaran` 10/menit | P1 |
| G15 | CORS restrict | `*` | Persempit/hapus setelah mobile rilis | P1 |
| G16 | Feature parity siswa/guru/wali sebelum website dikurangi | Belum ada diukur | Checklist parity + UAT matrix | P2 |
| G17 | Monitoring/logging API | `activitylog` ada | Log pembayaran/login via API; request-id | P2 |
| G18 | Migrasi foto profil ke API upload | Upload web baru jadi | Fase lanjutan opsional | P3 |

---

## 7. Security Risks & Mitigasi

| # | Risiko | Tingkat | Status saat ini | Mitigasi (fase implementasi) |
|---|---|---|---|---|
| S-01 | Token Sanctum bocor → akses abadi (`expiration=null`) | Tinggi | Konfigurasi default | Set expiry 30–90 hari, rotasi saat login, endpoint revoke-all, `last_used_at` |
| S-02 | Tidak ada throttle login → brute force | Tinggi | Captcha web (tidak di API) | `throttle:5,1` pada login; log; pertimbangkan hCaptcha mobile jika captcha tak layak |
| S-03 | CORS `*` untuk API | Sedang | `config/cors.php` | Persempit ke domain resmi atau hapus untuk token-based API |
| S-04 | IDOR pada endpoint API baru (menerima `siswa_id` dari body) | Tinggi | Pola web aman; API belum ada | Subjek selalu dari token; Form Request hanya menerima ID resource; scoping di service |
| S-05 | Kebocoran ledger keuangan via endpoint detail tanpa scoping | Tinggi | Pola web aman | scoping pemilik untuk kuitansi/bukti/dokumen/refund (pola `RefundDokumenDownloadController`) |
| S-06 | Enumeration ID integer (semua tabel) | Sedang | PK auto-increment | Scoping by-owner (sudah); jangan expose ID internal bila tidak perlu; `public_token` untuk attempt |
| S-07 | Replay webhook / signature palsu | Rendah | Mitigasi kuat ada (signature + server-side Get Status + whitelist payload) | Pertahankan; mobile tidak pernah memicu jalur ini |
| S-08 | `menu.access` tidak cocok untuk API → kesalahan otorisasi | Sedang | Web-only | Jangan pakai `menu.access` di API; kemampuan eksplisit per endpoint |
| S-09 | Akun dinonaktifkan tapi token masih hidup | Sedang | Session hilang (web); token tidak | Middleware API `cek.akun` + revoke token pada penonaktifan |
| S-10 | Foto/data pribadi via `storage/app/public` ter-expose | Sedang | Foto siswa/guru publik by design | Hanya foto; dokumen & bukti tetap private; disk `private` eksplisit |
| S-11 | Activity log membocorkan data sensitif (password) | Rendah | Tidak terlihat di audit | Whitelist field; jangan log password/token/PAN |
| S-12 | Password lemah / reuse dari web | Sedang | Min 8 karakter; tidak cek kamus | Pertahankan min 8; pertimbangkan password compromise check di login (opsional) |
| S-13 | Penggunaan kredensial merchant Midtrans di mobile | Kritis | Tidak ada kredensial di kode | Flutter tidak pernah pegang server key; token Snap hanya sekali pakai |
| S-14 | Data wali bocor ke wali lain via `siswa_id` | Tinggi | Pola web aman (verified-aktif) | Sama: scoping relasi di setiap endpoint wali |
| S-15 | Error message membocorkan info (mis. "user tidak ditemukan" vs "password salah") | Rendah | `LoginController` generik | Konsisten: "Username atau password salah" |
| S-16 | `activity_log`/`log-viewer` diakses role non-superadmin | Rendah | Gate `viewLogViewer` fail-closed | Pertahankan |
| S-17 | Dependency rawan (queue sync, tidak ada async mail) | Rendah | `.env.example` | Push notification → queue; monitoring disk log |
| S-18 | Risiko operasional: `route:cache` tidak bisa (closure web) | Rendah | `routes/web.php` | API routes tanpa closure jika ingin `route:cache` nanti — buat controller terpisah |

---

## 8. Rekomendasi Arsitektur API

**Keputusan kunci yang direkomendasikan (butuh persetujuan pemilik):**

1. **Satu aplikasi Laravel**, satu deployment: web tetap di `/`, API di `/api/v1/*` (satu basis kode = satu sumber kebenaran aturan bisnis).
2. **Sanctum token (Bearer)** untuk Flutter; web tetap session. JANGAN pakai Sanctum stateful cookie untuk mobile.
3. **`personal_access_tokens`** via migrasi aditif; token name = `device_name`; abilities minimal (`siswa`, `guru`, `wali`) untuk defense-in-depth.
4. **Middleware API baru** (file baru, bukan mengubah yang ada): `auth:sanctum` (framework) + `api.level:siswa,guru,wali` (JSON 401/403; setara `check.level`) + `api.akun` (padanan `cek.akun`; tolak & cabut token bila nonaktif) + `api.force-password` (bila `wajib_ganti_password=true`, semua endpoint selain `/me` & `/password` → 403 `PASSWORD_CHANGE_REQUIRED`).
5. **Response convention**: `{"data": ...}` untuk sukses tunggal/koleksi (+ `meta` untuk paging), `{"message": "..."}` untuk aksi, `{"message": "...", "errors": {field: [..]}}` untuk 422, `{"message": "...", "code": "MACHINE_CODE"}` untuk 401/403/429. HTTP status semantik. Tidak ada stack trace ke klien (`APP_DEBUG=false` di produksi).
6. **Form Request** per endpoint (validasi terpusat) + **API Resources** (kontrak data; tidak membocorkan kolom internal — mis. `payment_attempt.idpayment_attempt`, `siswa_idsiswa`, timestamps internal kecuali yang bermakna).
7. **Versioning** `/api/v1` sejak awal; perubahan breaking → `/api/v2`, bukan edit v1.
8. **CORS**: hapus `*`; whitelist domain resmi atau biarkan kosong (token-based, tidak butuh CORS dari native app).
9. **Idempotensi**: endpoint yang mutasi keuangan menerima header `Idempotency-Key` (opsional; diverifikasi via cache) — keputusan Fase 4; minimal: disable tombol di klien + lock server.
10. **Rate limiting khusus**: `login` 5/menit/IP+username; `pembayaran-create` 10/menit; `api` 60/menit default.
11. **API tidak pernah mengembalikan**: password, remember token, hash, `public_token` selain untuk pemilik attempt, detail internal ledger selain milik sendiri.
12. **OpenAPI ringan**: minimal `docs/api.md` dengan contoh request/response per endpoint; Postman collection opsional.
13. **Tidak menambah dependency** tanpa persetujuan (mis. OpenAPI generator, FCM package) — gunakan HTTP facade untuk FCM terlebih dahulu.
14. **Dokumentasi per role**: matriks kemampuan (ability) siswa/guru/wali dalam README/docs API.

**Struktur yang disarankan (untuk fase implementasi):**
```
routes/api.php            → prefix /api/v1, group per role (middleware)
app/Http/Controllers/Api/V1/{Auth,Siswa,Guru,Wali,Notifikasi,Health}Controller.php
app/Http/Requests/Api/V1/...  (Form Requests)
app/Http/Resources/Api/V1/... (Resources)
app/Http/Middleware/ApiLevel.php  (BARU; tidak menyentuh middleware web)
```

---

## 9. Urutan Roadmap Implementasi

**Fase 0 (sekarang):** audit ini — selesai. Keputusan & persetujuan pemilik.

**Fase 1 — Fondasi API (P0)**
1. Migrasi aditif `personal_access_tokens`
2. `routes/api.php` baru dengan `/api/v1` + group per role
3. Middleware `ApiLevel` + `ApiAktif` + `ApiForcePassword` (file baru)
4. Response convention + error render JSON (bootstrap `Handler` via renderable untuk `api/*`)
5. Endpoint auth: login, logout, logout-all, me, password
6. Rate limiter `login` (5/menit) + `api` (60/menit; sudah ada)
7. Activity log untuk login/logout/password change via API
8. UAT: login per role, token revoke, akun nonaktif, wajib ganti password, throttle

**Fase 2 — Siswa read-only (P1)**
9. Ekstraksi ringkasan beranda (`BerandaSiswaService`)
10. Endpoint: beranda, jadwal (hari ini/minggu), absensi harian/perjam + rekap, nilai/rapor (read-only)
11. Ekstraksi `RekapAbsensiService` (dipindah dari komponen histori)

**Fase 3 — Pengumuman & notifikasi (P1)**
12. Endpoint notifikasi (list/count/baca/baca-semua) + pengumuman siswa (list/detail/baca)
13. UAT polling + badge

**Fase 4 — Keuangan siswa/wali (P1, syarat: UAT Midtrans sandbox lulus ATAU keputusan manual-only)**
14. Endpoint tagihan, detail, saldo, riwayat
15. Endpoint buat attempt (Snap token), cek status (polling), unduh kuitansi/bukti/dokumen (pola download controllers)
16. Endpoint gunakan saldo
17. UAT keuangan: idempotensi, retry, attempt vs tagihan status, saldo, wali bayar anak

**Fase 5 — Guru (P1)**
18. Ekstraksi `JadwalGuruService`
19. Endpoint: beranda guru, jadwal, kelas, absensi (baca/tulis massal), komponen nilai, simpan nilai
20. UAT scoping guru (tidak bisa isi kelas lain)

**Fase 6 — Wali (P1)**
21. Endpoint children + akademik read-only + tagihan + bayar
22. UAT wali: relasi pending tidak bisa akses; relasi putus tidak bisa akses; scoping beda anak

**Fase 7 — Hardening & transisi (P2)**
23. CORS restrict, OpenAPI/dokumentasi, rate limit tune, logging request-id
24. Push notification (opsional, keputusan FCM)
25. Checklist feature parity + keputusan pengurangan web siswa/guru/wali
26. Aktivasi scheduler di server + verifikasi `schedule:list`
27. Verifikasi full UAT mobile end-to-end (login → bayar → terima notifikasi)

---

## 10. Keputusan yang Membutuhkan Persetujuan

1. **Approval dependency baru?** Rekomendasi: tidak ada untuk Fase 1–6 (Sanctum sudah terpasang; FCM opsional di Fase 7 memakai HTTP facade). Jika OpenAPI generator diinginkan → butuh persetujuan.
2. **Migrasi aditif `personal_access_tokens`** — setuju? (wajib; tanpa ini Sanctum tidak berfungsi)
3. **Scope MVP mobile** — semua fitur (Fase 2–6) atau mulai siswa-only?
4. **Pembayaran online di mobile:** tunggu UAT sandbox Midtrans lulus (butuh kredensial merchant) ATAU rilis MVP dengan pembayaran **manual saja** (admin verifikasi)?
5. **Kebijakan token**: expiry (mis. 30/60/90 hari)? revoke-all tersedia? device limit?
6. **Website siswa/guru/wali**: tetap aktif penuh (paralel) selama transisi? Kapan mulai deprecation?
7. **Disk `private` baru** (`storage/app/private` eksplisit) untuk bukti/kuitansi — setuju? (perubahan aditif; tidak memindahkan file lama)
8. **Push notification**: masuk MVP atau Fase 7? FCM (butuh Google services config Flutter) atau cukup polling + in-app?
9. **Akses admin/superadmin dari mobile**: dikonfirmasi tetap web-only (bukan di mobile)?
10. **Siapa yang menyetujui kredensial Midtrans production** dan kapan UAT sandbox dijalankan?
11. **Perubahan `users` untuk device push tokens** (tabel baru `devices` — aditif) bila push disetujui.
12. **Branding/nama API** (title/response `X-App: SIAKAD-Mobile`) dan kepemilikan dokumentasi API.

---

## 11. Kesimpulan Kesiapan Backend

**VERDICT: Backend SIAKAD SIAP diekstrak menjadi API** — namun **belum siap dipakai**. Fondasi bisnis (service layer, ledger keuangan yang immutable, notifikasi, scoping akademik) sudah berada di lapisan yang benar dan dapat digunakan ulang. Yang tidak ada adalah lapisan API itu sendiri.

Ringkasan kesiapan per area:

- **A. Auth/API readiness:** NOT READY — web session + captcha saja; Sanctum terpasang tanpa migrasi token; tidak ada endpoint auth.
- **B. Role & authorization:** PARTIAL — pola solid (level + `menu.access` + scoping) tetapi web-only; butuh padanan JSON + scoping ketat per endpoint.
- **C. Business logic reusability:** READY dengan catatan — service layer matang; ekstraksi kecil (rekap absensi, jadwal guru, beranda siswa).
- **D. Data relationships:** READY — FK/RESTRICT, relasi jelas, snapshot jadwal, scoping keuangan; konvensi scope API perlu distandarisasi.
- **E. Finance API readiness:** PARTIAL — seluruh aturan ledger/verifikasi/idempotensi tersedia di server; Midtrans belum teruji (blocked kredensial); Flutter harus "bodoh" (tidak menghitung apa pun).
- **F. Website transition:** PARTIAL — pemetaan jelas; jangan nonaktifkan web siswa/guru/wali sekarang; butuh checklist feature parity.
- **G. Infrastructure:** PARTIAL — throttle 60/menit ada; CORS `*` harus dipersempit; disk `private` belum eksplisit; tidak ada push/queue; scheduler perlu aktivasi di server.

**Yang bisa langsung dikerjakan tanpa keputusan tambahan:** Fase 1 (fondasi) dan Fase 2/3 (read-only siswa + notifikasi).
**Yang butuh keputusan:** Fase 4 (pembayaran — tergantung status Midtrans), Fase 7 (push, deprecation web).

**Skor kesiapan backend (subyektif, berdasar temuan):** fondasi bisnis 8/10; kesiapan API 2/10; kesiapan mobile production (end-to-end) 1/10. Dengan Fase 1–3 selesai, kesiapan API naik ke ~5/10; Fase 4–6 → ~7–8/10; sisanya adalah QA/UAT dan keputusan bisnis.

**Pernyataan penutup audit:** seluruh temuan berasal dari inspeksi source code (tanpa menjalankan aplikasi); tidak ada klaim pengujian yang tidak dilakukan; repository tidak berubah.

---

### Lampiran — Inventaris "NOT VERIFIED" (harus dibuktikan di fase implementasi)

1. Migrasi Sanctum (`personal_access_tokens`) belum ada di daftar 89 migrasi.
2. Tidak ada `createToken()` di seluruh codebase.
3. Tidak ada endpoint login untuk Flutter (verifikasi manual `route:list` pada sesi ini: 110 route, semuanya web kecuali `webhook/midtrans` dan contoh `/api/user`).
4. Midtrans belum teruji end-to-end (tidak ada kredensial; `Http::fake()` saja).
5. Performa query portal pada data produksi besar.
6. Scheduler belum terverifikasi aktif di server deployment (konfigurasi kode ≠ cron terpasang).
7. `storage/app/private` belum menjadi disk Laravel resmi (akses via path langsung di controller).
8. Feature parity siswa/guru/wali vs kebutuhan Flutter belum diukur.
