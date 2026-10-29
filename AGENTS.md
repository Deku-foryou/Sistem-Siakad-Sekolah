AGENTS.md — SIAKAD Laravel 12 + Livewire 3

# ================================================
HOW TO INVESTIGATE

Read highest-value sources first:

- Root manifest (composer.json) and config files
- Migration and seeder files in database/migrations/ and database/seeders/
- routes/web.php — the entrypoint for all web routes
- AppServiceProvider.php — especially view composers that query DB at boot
- database/seeds/ — all seeder files and their call order in DatabaseSeeder.php

If unclear, inspect representative code files rather than random leaf files. The flow is:
routes/web.php → AppServiceProvider::boot() → view composers → database queries.

# ================================================
KEY COMMANDS

- php artisan migrate:fresh --seed     — FULL reset + rebuild (drops all tables)
- php artisan migrate:status           — Show which migrations have run
- php artisan migrate:reset            — Roll back all migrations (keeps data)
- php artisan migrate:refresh          — Re-run all migrations without dropping data
- php artisan tinker                   — Interactive PHP shell
- php artisan route:list               — List all registered routes
- php artisan config:cache             — Re-cache after config changes

# ================================================
MIGRATION &amp; SEEDER NOTES

CRITICAL: The usermenu table has NO create migration by default.

- If usermenu table is missing, sidebar/composer queries fail with "Table doesn't exist"
- Fix: create migration with idusermenu (integer, not auto-increment), appmenunew_idappmenunew FK, user_iduser FK
- See: database/migrations/2026_10_28_140832_create_usermenu_table.php

Seeders are called in this order from DatabaseSeeder.php:

1. PaketDefaultSeeder
2. AkademikMenuSeeder
3. BerandaMenuSeeder
4. DataIndukMenuSeeder
5. IkonMenuSeeder
6. JadwalMenuSeeder

After all seeders, DatabaseSeeder creates the admin user ([admin@sekolah.id](mailto:admin@sekolah.id) / rahasia123 / superadmin).

If usermenu table is empty after migrate:fresh --seed, check:

- DatabaseSeeder assigns menus to the admin user via Usermenu::firstOrCreate()
- All 15 appmenunew IDs must be assigned (1..15)

# ================================================
PROJECT STRUCTURE

Database migrations (key ones):

- 2026_10_26_000000_create_users_table.php        — users table
- 2026_10_26_000000_create_appmenunew_table.php  — application menus
- 2026_10_26_000001_add_siakad_fields_to_users_table.php  — SIAKAD fields on users
- 2026_10_28_140832_create_usermenu_table.php    — USER-MENU LINKING TABLE (critical!)
- 2026_10_28_135304_create_rolecat_table.php    — role categories (Siswa, Guru)
- 2026_10_28_135531_create_role_table.php       — role-menu assignments

Seeders:

- PaketDefaultSeeder           — initializes rolecat Siswa/Guru
- AkademikMenuSeeder           — Akademik category + menus + usermenu entries
- BerandaMenuSeeder            — Beranda category + menus + role assignments
- DataIndukMenuSeeder          — Data Induk category menus
- IkonMenuSeeder               — Ikon category menus
- JadwalMenuSeeder             — Jadwal/Pengampu category menus

# ================================================
COMMON PITFALLS

1. routes/web.php top-level DB query
   - BEFORE: $appMenuUrls = \\App\\Models\\Appmenunew::query()-&gt;whereNotNull('url')-&gt;pluck('url')-&gt;unique()-&gt;values();
   - This runs during Laravel bootstrap, BEFORE migrations.
   - FIX: Wrap in Schema::hasTable('appmenunew') check, or move to seeder.
2. Missing usermenu table migration
   - Usermenu model exists but no create migration → table never created → sidebar empty
   - Fix: create migration with proper FK to appmenunew and users
3. Migration ordering issues
   - Some migrations reference columns/tables created in later migrations
   - Fix: rename migrations with proper timestamp ordering (e.g., 2026_10_31 vs 2026_10_28)
4. Admin account not creatable after fresh database
   - DatabaseSeeder must create admin user AFTER all migrations run
   - Password: rahasia123, level: superadmin, email: [admin@sekolah.id](mailto:admin@sekolah.id)

# ================================================
VERIFICATION STEPS

After php artisan migrate:fresh --seed, verify:

1. php artisan migrate:status — all migrations [1] Ran
2. php artisan route:list — usermenu/{id} route present
3. usermenu table has rows for user_id=1 (superadmin) with 15 appmenunew_id entries
4. App appears functional: login with [admin@sekolah.id](mailto:admin@sekolah.id) / rahasia123
5. Sidebar composer query returns menu data without "Table doesn't exist" error

# ================================================
ENV / CONFIG

- Database: templatedb (MySQL)
- .env: DB_DATABASE=templatedb, DB_USERNAME=root, DB_PASSWORD=(empty)
- App namespace: App
- Livewire 3, MewsCaptcha, Laravel Sanctum
- Custom level-based auth (not Spatie full permission)
- Config: config/siakad.php — paket_default.siswa = 'Siswa', paket_default.guru = 'Guru'



## Aturan kerja (wajib)

- Jangan membaca, menampilkan, atau mengedit .env.

- Dilarang: migrate:fresh, migrate:reset, db:wipe, drop table/kolom, menghapus data nyata, RefreshDatabase/DatabaseMigrations. Migration hanya aditif; jalankan `migrate --pretend` lebih dulu.

- Jangan menambah package composer tanpa izin. Jangan membuat file tes permanen di tests/; skrip uji ditaruh di storage/app/tmp-uji, dijalankan dalam DB::beginTransaction() + rollBack() di finally, lalu dihapus.

- Git hanya baca (status, diff, log, show); jangan add/commit/checkout/reset/stash/clean.

- Shell PowerShell, path project berspasi: mulai dengan Set-Location -LiteralPath "C:\\project-reyhan\\template web". Panggil `php ...` dan `php vendor/bin/pint <file-spesifik>`; Pint hanya pada file yang diubah. Untuk tinker, pakai skrip PHP terpisah, bukan --execute dengan kutip.

- Dilarang menulis hasil, angka, atau PASS yang tidak berasal dari output perintah yang benar-benar dijalankan. Jika tidak bisa diukur tulis "TIDAK BISA DIUKUR: &lt;alasan&gt;". Yang butuh browser tulis "Belum diuji".

- Jangan memasukkan teks proses berpikir, catatan internal, atau kalimat seperti "spec says..." ke dalam file kode.

- Konvensi: tabel lowercase tanpa plural, PK id{tabel}, FK {tabel}\_id{pk}, FK ON DELETE RESTRICT, komponen Livewire NamaComponent + view kebab-case, aturan bisnis di Service, semua method publik komponen admin diawali abort\_unless(auth()-&gt;user()?-&gt;level === 'superadmin', 403). Pesan dan label Bahasa Indonesia.