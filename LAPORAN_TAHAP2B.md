# Verifikasi bersih Tahap 2B

## 1. Konteks dan batasan

- Pemeriksaan dijalankan pada worktree aktif. Output Git yang dibaca menunjukkan branch `feature/data-induk`, bukan `feature/import`; atas arahan pengguna, perubahan dilanjutkan tanpa mengganti branch atau menulis ke Git.
- Tidak membaca atau mengubah `.env`, tidak menjalankan migration, tidak menghapus data, dan tidak mengerjakan Import Bagian C/D.
- Data uji login dijalankan di dalam `DB::beginTransaction()` dan selalu di-rollback dalam `finally`. Skrip verifikasi sementara dihapus setelah digunakan.

## 2. Diagnosis awal baca-saja

Output:

```text
users=3
siswa=0
guru=0
kelassiswa=0
rolecat=0
usermenu=16
LEVEL_COUNTS
superadmin=2
siswa=1
```

Kondisi berhenti (`siswa > 0` atau `users > 3`) tidak terpenuhi; proses dilanjutkan. Tidak ada daftar siswa karena jumlah siswa nol.

Perintah awal Tinker tidak dapat diparse akibat quoting PowerShell. Pesan error persis:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected T_NS_SEPARATOR on line 1
```

Percobaan Tinker kedua juga tidak dapat diparse:

```text
Psy\Exception\ParseErrorException
PHP Parse error: Syntax error, unexpected '.' on line 1
```

Script `count_data.php` yang sudah ada juga gagal karena memanggil `Kernel::call()` dengan Closure. Pesan error:

```text
TypeError
Symfony\Component\Console\Input\StringInput::__construct(): Argument #1 ($input) must be of type string, Closure given, called in C:\project-reyhan\template web\vendor\laravel\framework\src\Illuminate\Console\Application.php on line 190
```

Diagnosis berhasil diulang dengan script PHP baca-saja sementara; script tersebut dihapus setelah verifikasi.

## 3. Pencarian salah eja dan diagnosis login

Output `Select-String` sebelum perbaikan:

```text
app\Services\GuruService.php:54:        if (! in_array($jk, ['L', 'P', 'LAKI-LAKI', 'PERUPEMAN', 'PEREMPUAN'])) {
app\Services\SiswaService.php:53:        if (! in_array($jk, ['L', 'P', 'LAKI-LAKI', 'PERUPEMAN', 'PEREMPUAN'])) {
```

Pencarian ulang pada `app`, `database`, `routes`, `resources`, dan `config` setelah perbaikan:

```text
NO_MATCHES
```

Sebelum perubahan, `LoginBlockService` tidak dipanggil; `LoginController::authenticate()` memiliki pengecekan status inline yang terpisah. Kini `LoginController::authenticate()` memanggil `LoginBlockService::cekBolehLogin()` sebagai satu-satunya mekanisme pengecekan blokir. Service meng-query relasi `siswa()`/`guru()` untuk status aktif, bukan membaca atribut `users` atau relasi yang mungkin sudah tercache. Superadmin tetap selalu boleh login; level lain yang bukan siswa/guru mempertahankan perilaku sebelumnya.

Output `SHOW COLUMNS FROM users`:

```text
id|bigint unsigned|NO|PRI|NULL
username|varchar(30)|YES|UNI|NULL
name|varchar(255)|NO||NULL
email|varchar(255)|YES|UNI|NULL
level|varchar(255)|YES||NULL
wajib_ganti_password|tinyint(1)|NO||0
email_verified_at|timestamp|YES||NULL
password|varchar(255)|NO||NULL
remember_token|varchar(100)|YES||NULL
created_at|timestamp|YES||NULL
updated_at|timestamp|YES||NULL
```

Kolom `status` tidak ada di `users`; status berada di tabel `siswa` dan `guru`.

## 4. Normalisasi jenis kelamin

Ditambahkan satu fungsi bersama `App\Support\JenisKelamin::normalisasi(?string): string`, digunakan oleh `SiswaService` dan `GuruService`. Normalisasi memakai `trim()` dan `mb_strtoupper()`, hanya menerima `L`, `P`, `LAKI-LAKI`, `LAKI LAKI`, dan `PEREMPUAN`; nilai lain termasuk `null` melempar `InvalidArgumentException`.

Output uji:

```text
input|hasil|error
L|L|-
P|P|-
laki-laki|L|-
Laki-laki|L|-
LAKI-LAKI|L|-
PEREMPUAN|P|-
Perempuan|P|-
perempuan|P|-
" P "|P|-
X|-| Jenis kelamin tidak valid.
""|-| Jenis kelamin tidak valid.
null|-| Jenis kelamin tidak valid.
LAKI LAKI|L|-
```

## 5. Uji blokir login dengan rollback

Semua data fixture dibuat setelah `DB::beginTransaction()` dan `DB::rollBack()` dipanggil dalam `finally`. Uji tambahan mengubah status di database ketika relasi siswa pada model sudah termuat; hasil `TIDAK` membuktikan pengecekan memakai query segar.

Output uji:

```text
login_case|hasil
siswa aktif|BOLEH
siswa lulus|TIDAK
siswa pindah|TIDAK
siswa keluar|TIDAK
guru aktif|BOLEH
guru nonaktif|TIDAK
superadmin|BOLEH
siswa tanpa baris siswa|TIDAK
siswa status DB berubah saat relasi cache aktif|TIDAK
TRANSACTION_ROLLED_BACK
```

Siswa aktif dan guru aktif boleh login.

## 6. Pint

Perintah:

```text
php vendor/bin/pint app\Services app\Livewire app\Http\Controllers app\Support config\siakad.php database\seeders
```

Output lengkap:

```text
.✓✓✓✓✓✓✓✓✓✓✓✓✓✓✓✓✓.✓✓.✓✓✓✓✓✓✓.✓.✓....✓✓✓✓✓✓...✓✓.

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel
    FIXED   .......................................................................... 49 files, 36 style issues fixed
  ✓ app\Http\Controllers\Model_has_rolesController.php class_attributes_separation, blank_line_after_opening_tag, con…
  ✓ app\Http\Controllers\PermissionsController.php class_attributes_separation, array_indentation, blank_line_after_o…
  ✓ app\Http\Controllers\Role_has_permissionsController.php class_attributes_separation, blank_line_after_opening_tag…
  ✓ app\Http\Controllers\RolesController.php class_attributes_separation, array_indentation, blank_line_after_opening…
  ✓ app\Http\Controllers\UsersController.php class_attributes_separation, array_indentation, blank_line_after_opening…
  ✓ app\Livewire\ActivitylogComponent.php class_attributes_separation, concat_space, ordered_traits, trailing_comma_i…
  ✓ app\Livewire\AppmenucatComponent.php single_class_element_per_statement, class_attributes_separation, new_with_pa…
  ✓ app\Livewire\AppmenunewComponent.php single_class_element_per_statement, class_attributes_separation, new_with_pa…
  ✓ app\Livewire\DashboardComponent.php                                 no_extra_blank_lines, single_blank_line_at_eof
  ✓ app\Livewire\DocsComponent.php single_class_element_per_statement, class_attributes_separation, no_empty_statemen…
  ✓ app\Livewire\DocsContentComponent.php                        class_attributes_separation, single_blank_line_at_eof
  ✓ app\Livewire\DocsCreateComponent.php single_class_element_per_statement, class_attributes_separation, new_with_pa…
  ✓ app\Livewire\DocsEditComponent.php single_class_element_per_statement, class_attributes_separation, concat_space,…
  ✓ app\Livewire\DocscatComponent.php single_class_element_per_statement, class_attributes_separation, no_empty_state…
  ✓ app\Livewire\GuruComponent.php concat_space, unary_operator_spaces, no_unused_imports, not_operator_with_successo…
  ✓ app\Livewire\KelasComponent.php concat_space, unary_operator_spaces, not_operator_with_successor_space, blank_lin…
  ✓ app\Livewire\KelassiswaComponent.php concat_space, unary_operator_spaces, not_operator_with_successor_space, blan…
  ✓ app\Livewire\MapelComponent.php concat_space, unary_operator_spaces, not_operator_with_successor_space, blank_lin…
  ✓ app\Livewire\ModelHasRolesComponent.php class_attributes_separation, method_chaining_indentation, ordered_traits,…
  ✓ app\Livewire\PermissionsComponent.php single_class_element_per_statement, class_attributes_separation, new_with_p…
  ✓ app\Livewire\RoleComponent.php single_class_element_per_statement, class_attributes_separation, array_indentation…
  ✓ app\Livewire\RoleHasPermissionsComponent.php single_class_element_per_statement, class_attributes_separation, new…
  ✓ app\Livewire\RolecatComponent.php single_class_element_per_statement, class_attributes_separation, new_with_paren…
  ✓ app\Livewire\RolesComponent.php single_class_element_per_statement, class_attributes_separation, new_with_parenth…
  ✓ app\Livewire\SiswaComponent.php concat_space, unary_operator_spaces, no_unused_imports, not_operator_with_success…
  ✓ app\Livewire\UsermenuComponent.php single_class_element_per_statement, class_attributes_separation, array_indenta…
  ✓ app\Services\GuruService.php new_with_parentheses, unary_operator_spaces, not_operator_with_successor_space, blan…
  ✓ app\Services\KelasService.php new_with_parentheses, unary_operator_spaces, no_unused_imports, not_operator_with_s…
  ✓ app\Services\LoginBlockService.php                                                        single_blank_line_at_eof
  ✓ app\Services\MapelService.php new_with_parentheses, lambda_not_used_import, method_argument_space, unary_operator…
  ✓ app\Services\PaketMenuService.php concat_space, unary_operator_spaces, no_unused_imports, not_operator_with_succe…
  ✓ app\Services\PenempatanService.php new_with_parentheses, unary_operator_spaces, no_unused_imports, not_operator_w…
  ✓ app\Services\SiswaService.php new_with_parentheses, unary_operator_spaces, not_operator_with_successor_space, bla…
  ✓ app\Support\JenisKelamin.php                                                                           line_ending
  ✓ database\seeders\PaketDefaultSeeder.php                                                   single_blank_line_at_eof
  ✓ database\seeders\RakSeeder.php single_space_around_construct, braces_position, no_unused_imports, binary_operator…
```

## 7. Hitungan akhir

Output sesudah semua uji dan rollback:

```text
users=3
siswa=0
guru=0
kelassiswa=0
rolecat=0
usermenu=16
LEVEL_COUNTS
superadmin=2
siswa=1
```

Hitungan `users`, `siswa`, `guru`, dan `usermenu` sama dengan diagnosis awal. Tidak ada data uji yang tertinggal.
