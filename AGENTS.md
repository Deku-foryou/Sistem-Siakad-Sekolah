AGENTS.md — SIAKAD Laravel 12 + Livewire 3

# ================================================
HOW TO INVESTIGATE

Read highest-value sources first:
- Root manifest: `composer.json`, `.editorconfig`
- Config files: `config/*.php`
- Migration and seeder files: `database/migrations/`, `database/seeders/`
- Routes: `routes/web.php` — the entrypoint for all web routes
- AppServiceProvider.php — especially view composers (sidebar, docs_sidebar)
- database/seeders/DatabaseSeeder.php — seeder execution order

Architecture flow:
routes/web.php → dynamic route generation from appmenunew table → AppServiceProvider::boot() → view composers → database queries

# ================================================
KEY COMMANDS

- php artisan migrate --pretend        — Dry run: show what would migrate
- php artisan migrate                  — Run pending migrations (SAFE for production)
- php artisan migrate:status           — Show which migrations have run
- php artisan test                     — Run PHPUnit tests
- php artisan route:list               — List all registered routes
- php vendor/bin/pint <file>           — Format single file (Pint formatter)

# ================================================
CRITICAL: DUAL SCHEDULING SYSTEMS

⚠️  Two independent systems exist:

**SYSTEM 1: Jadwal (legacy, active)**
- Table: jadwal (idjadwal, pengampu_idpengampu, hari, jam_ke, status)
- Component: JadwalComponent
- Service: JadwalService
- Features: CRUD, conflict detection, publikasi, snapshot, tarik publikasi
- Status: Production system, used by existing schedules
- Grid: Generates hari + jam_ke options from Jampelajaran (global config)

**SYSTEM 2: JadwalKelas (new)**
- Table: jadwalkelas (idjadwalkelas, semester_idsemester, jamhari_idjamhari, mapel_idmapel, guru_idguru)
- Component: JadwalKelasComponent
- Service: JadwalKelasService
- Features: CRUD, conflict detection (no publikasi yet)
- Status: Development/testing system
- Grid: Generates hari + jam from Jamhari (per-class config, kelas_id field)

**Key difference:** Jadwal is global (semester-wide), JadwalKelas is per-class.
Do NOT mix APIs or import between these systems.

# ================================================
MIGRATION & SEEDER NOTES

**Usermenu table:**
- Created by migration 2026_10_28_140832_create_usermenu_table.php
- Key fields: idusermenu (PK, not auto-increment), appmenunew_idappmenunew (FK), user_iduser (FK)
- Constraint: RESTRICT on delete
- Used by: sidebar view composer in AppServiceProvider

**Seeder execution order (DatabaseSeeder.php):**
1. PaketDefaultSeeder — rolecat (Siswa, Guru)
2. AkademikMenuSeeder — Akademik category + menus + usermenu entries
3. BerandaMenuSeeder — Beranda category + menus
4. DataIndukMenuSeeder — Data Induk menus
5. IkonMenuSeeder — Icon updates for appmenunew
6. JadwalMenuSeeder — Jadwal category (Jam Pelajaran, Pengampu, Jadwal)
7. PolajamCleanupSeeder — Cleanup old Pola Jam menu (if exists)

**After all seeders:** DatabaseSeeder creates admin user (admin@sekolah.id / rahasia123 / superadmin) and assigns all 15 menus.

**Cleanup seeder pattern:**
Use JampelajaranCleanupSeeder and PolajamCleanupSeeder as templates when removing deprecated menus:
```php
$menu = Appmenunew::where('url', 'menu_url')->first();
if ($menu) {
    DB::table('usermenu')->where('appmenunew_idappmenunew', $menu->idappmenunew)->delete();
    DB::table('role')->where('appmenunew_idappmenunew', $menu->idappmenunew)->delete();
    $menu->delete();
}
```
Always run cleanup seeder AFTER the seeder that creates menus (so old data is cleaned if re-seeding).

# ================================================
PROJECT STRUCTURE

**Migrations (key):**
- 2026_10_26: users, appmenunew, tahunajaran, semester, guru, siswa, mapel, kelas, kelassiswa
- 2026_10_27: polajam, polajamhari, jampelajaran, pengampu, jadwal, publikasijadwal
- 2026_10_28: rolecat, appmenucat, role, usermenu, status columns
- 2026_11_06: jamhari (per-class jam config)
- 2026_11_07: jadwalkelas, kode_guru

**Livewire components (major):**
- JadwalComponent — UI for legacy schedule management
- JadwalKelasComponent — UI for new per-class schedule management
- JamhariComponent — UI for Jamhari (jam pelajaran) configuration
- All admin components require: `abort_unless(auth()->user()?->level === 'superadmin', 403)` at start of public methods

**Models worth knowing:**
- Jadwal, Jampelajaran, Polajam, Polajamhari (legacy system) — do NOT use in new code
- JadwalKelas, Jamhari (new system) — preferred for new features
- Pengampu — connects semester+kelas+mapel+guru, used by both systems

**Sidebar rendering:**
View composer in AppServiceProvider queries: `Usermenu::with(['appmenunew', 'user'])->where('user_iduser', auth()->id())`
Renders to `assets.sidebar` view. Menu order is `appmenunew.urut` ascending.
If menu missing from sidebar: check usermenu table has entry + appmenunew url is in route list.

# ================================================
COMMON PITFALLS

1. **Routes are partially dynamic**
   - routes/web.php lines 92-102: Loop generates routes for all appmenunew.url values NOT in $explicitUrls
   - Explicit routes (like /jadwalkelas, /polajam) are hardcoded before this loop
   - If a menu route won't load: check if URL is in appmenunew + route loop includes it + component exists

2. **Grid/Hari generation depends on correct source**
   - JadwalComponent grid uses: distinct jam_ke from Jampelajaran (global) + hari from existing Jadwal records
   - JadwalKelasComponent grid uses: Jamhari.where('kelas_id', ...) for per-class hari/jam
   - Mixing sources will cause empty grid or validation errors

3. **Jampelajaran vs Jamhari confusion**
   - Jampelajaran: global jam config (used by Jadwal system), tied to polajam_idpolajam
   - Jamhari: per-class jam config (used by JadwalKelas system), tied to kelas_id
   - Do NOT query Jampelajaran for JadwalKelas validation or vice versa

4. **Publikasi only for Jadwal**
   - JadwalService has publikasikan() and tarikPublikasi()
   - JadwalKelasService does NOT have publikasi (future feature)
   - Publikasijadwal table stores snapshots of Jadwal records only

5. **Menu cleanup requires seeder**
   - Seeder JadwalMenuSeeder only CREATES menus in code
   - To REMOVE menus from database: must create cleanup seeder (see pattern above)
   - Seeders do NOT auto-sync database with code changes

6. **Schema::hasTable() checks in routes/web.php**
   - routes/web.php runs BEFORE migrations during bootstrap
   - appMenuUrls array uses: `if (Schema::hasTable('appmenunew')) { ... }`
   - This prevents "table not found" error on fresh install

# ================================================
VERIFICATION STEPS

After changes to Jadwal, JadwalKelas, or menu systems:

1. `php artisan migrate:status` — all migrations [1] Ran
2. `php artisan route:list` — check /jadwal, /jadwalkelas, /jampelajaran routes present
3. `php artisan test` — run test suite (at least unit tests should pass)
4. Check no Polajam references in JadwalService/JadwalComponent (except in comments)
5. Check no Jamhari references in Jadwal system
6. Verify menu cleanup: `SELECT * FROM appmenunew WHERE url = 'menu_url'` should be empty after cleanup seeder

# ================================================
ENV / CONFIG

- Database: templatedb (MySQL)
- DB_USERNAME: root, DB_PASSWORD: (empty)
- App namespace: App
- Auth: Custom level-based (superadmin, Guru, Siswa) — NOT Spatie
- Formatter: Laravel Pint (use `php vendor/bin/pint <file>` on changed files only)
- View composers: sidebar, docs_sidebar (run on every request, keep queries efficient)

# ================================================
ATURAN KERJA (WAJIB)

- **Jangan membaca, menampilkan, atau mengedit .env.**

- **Dilarang: migrate:fresh, migrate:reset, db:wipe.** Migrations hanya aditif. Jalankan `php artisan migrate --pretend` lebih dulu.

- **Jangan menambah package Composer tanpa izin.**

- **Jangan membuat file tes permanen di tests/.** Skrip uji ditaruh di storage/app/tmp-uji, dijalankan dalam DB::beginTransaction() + rollBack() di finally, lalu dihapus.

- **Git hanya baca** (status, diff, log, show). Jangan add/commit/push/checkout/reset/stash/clean.

- **Shell PowerShell, path berspasi:** Mulai dengan `Set-Location -LiteralPath "C:\project-reyhan\template web"`. Panggil `php ...` dan `php vendor/bin/pint <file-spesifik>`.

- **Jangan menulis hasil atau PASS yang tidak berasal dari output aktual.** Jika tidak bisa diukur, tulis "TIDAK BISA DIUKUR: <alasan>". Browser = "Belum diuji".

- **Konvensi:**
  - Tabel: lowercase tanpa plural, PK id{tabel}, FK {tabel}_id{pk}, ON DELETE RESTRICT
  - Livewire: Component PascalCase, view kebab-case
  - Admin components: semua method publik diawali `abort_unless(auth()->user()?->level === 'superadmin', 403)`
  - Pesan/label: Bahasa Indonesia
  - Aturan bisnis: di Service class
  - Menu cleanup: gunakan seeder pattern, selalu run AFTER create seeder
