# Laporan UI Detail Siswa dan Guru

## Perubahan

- Modal Detail Siswa dan Guru memakai partial bersama untuk header identitas dan kartu label-nilai.
- Tata letak memakai grid responsif Bootstrap: dua kolom mulai ukuran `lg`, satu kolom pada layar kecil.
- Detail siswa menampilkan biodata, akun, kelas aktif, serta riwayat dalam tabel; detail guru menampilkan biodata, akun, wali kelas, dan bagian Mengajar.
- CSS tambahan hanya menyentuh kelas `.detail-*` di `resources/views/assets/style.blade.php` (67 baris aturan, baris 66–132). Warna tema memakai hijau template `#0e7d6a`; kelas tabel/badge yang digunakan: `table table-sm table-borderless`, `table-responsive`, `badge bg-success`, `bg-info text-dark`, `bg-secondary`, dan `bg-danger`.
- Status akun diringkas sebagai “Terdaftar” atau “-”; markup detail tidak memuat nama kolom kredensial.

## Verifikasi

| Pemeriksaan | Perintah | Output |
|---|---|---|
| V1 — cakupan perubahan | `git status --short` | `M resources/views/assets/style.blade.php`; `M resources/views/livewire/guru-component.blade.php`; `M resources/views/livewire/siswa-component.blade.php`; `?? resources/views/livewire/partials/detail-card.blade.php`; `?? resources/views/livewire/partials/detail-header.blade.php`. Tidak ada komponen PHP, route, atau migration pada daftar perubahan. |
| V1 — diff stat | `git --no-pager diff --stat` | `resources/views/assets/style.blade.php \| 69 +++++++++`; `resources/views/livewire/guru-component.blade.php \| 90 +++++------`; `resources/views/livewire/siswa-component.blade.php \| 167 +++++++++++----------`; `3 files changed, 206 insertions(+), 120 deletions(-)`. Perintah ini belum memasukkan partial baru yang berstatus untracked. |
| V2 — kompilasi Blade | `php artisan view:clear`; `php artisan view:cache`; `php artisan view:clear` | `INFO Compiled views cleared successfully.`; `INFO Blade templates cached successfully.`; `INFO Compiled views cleared successfully.` |
| V3 — teks kredensial | `Select-String` pada kedua view dan dua partial, pola `password\|hash\|remember_token` | `No matches for password|hash|remember_token` |
| Tambahan — whitespace | `git --no-pager diff --check` | `git diff --check: exit 0` |

### Belum diuji

- Belum diuji: tampilan modal siswa dan guru di browser, grid dua kolom pada layar lebar dan satu kolom pada HP, kontras teks, badge, dan tabel riwayat.
