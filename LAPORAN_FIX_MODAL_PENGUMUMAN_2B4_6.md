# Laporan Perbaikan Modal Detail Pengumuman

## Akar masalah

- Modal detail dan `.modal-backdrop` dibuat manual oleh Blade, sedangkan Bootstrap juga membuat dan mengelola backdrop sendiri. Kedua mekanisme ini dapat meninggalkan backdrop yang tidak terpasang lagi ke lifecycle modal.
- Modal detail berada di dalam `@if($detail)`. Saat `tutupDetail()` mengosongkan `detailId`, Livewire dapat menghapus elemen modal ketika proses penutupan Bootstrap masih berlangsung. Listener penutupan sebelumnya kemudian mencari tombol di elemen yang sudah hilang; Bootstrap tidak mendapat kesempatan menyelesaikan pembersihan modal dan backdrop.
- Handler pembukaan menggunakan `new bootstrap.Modal(...)` di setiap kali modal dibuka, sehingga instance tidak digunakan ulang.

## File yang diubah

- `resources/views/livewire/pengumuman-component.blade.php`
- `app/Livewire/PengumumanComponent.php`

## Solusi

- Modal detail sekarang selalu berada di DOM, dengan `wire:ignore.self`, sehingga Livewire dapat memperbarui isinya tanpa menghapus elemen modal ketika Bootstrap menjalankan transisi.
- Bootstrap menjadi satu-satunya pemilik backdrop. Backdrop manual dihapus.
- Modal memakai `getOrCreateInstance()` dan event Livewire terdaftar dalam `@script`, menghindari pembuatan instance baru dan pemasangan listener pada setiap render.
- Tombol Tutup, ikon X, klik backdrop, serta Escape menggunakan lifecycle Bootstrap. Event `hidden.bs.modal` menyinkronkan state detail ke Livewire setelah transisi selesai.
- Setelah modal tersembunyi, class `modal-open` dan inline `overflow`/`padding-right` dipulihkan. Bila modal lain masih terbuka, class dan nilai style yang diperlukan untuk modal tersebut dipertahankan. Tidak ada penghapusan massal backdrop.
- Tampilan modal dirapikan dengan hierarki judul, badge berwarna, informasi dalam grid responsif, target terkait, dan footer yang konsisten.
- Tidak ada perubahan struktur data atau aturan bisnis.

## Hasil pengujian aktual

| Pemeriksaan | Hasil |
|---|---|
| `php -l app/Livewire/PengumumanComponent.php` | LULUS — `No syntax errors detected` |
| `php vendor/bin/pint --test app/Livewire/PengumumanComponent.php` | LULUS — Pint melaporkan file lolos |
| `php artisan view:cache` | LULUS — Blade templates berhasil dicache |
| `php artisan view:clear` | LULUS — compiled views berhasil dibersihkan |
| `php artisan route:list --path=pengumuman --except-vendor` | LULUS — route `/pengumuman` terdaftar ke `PengumumanComponent` |
| `php artisan test --testsuite=Unit` | LULUS — 1 tes lulus |
| `php artisan test` | GAGAL sebagian — 1 tes unit lulus; tes contoh feature `ExampleTest` gagal karena mengharapkan `/` berstatus 200, aktual 404 (`tests/Feature/ExampleTest.php:17`) |
| Uji browser dan interaksi modal | BELUM DIUJI — akses `/pengumuman` dialihkan ke `/login`, yang meminta autentikasi dan CAPTCHA |

### Pemeriksaan UI yang belum dapat diukur

Karena sesi browser tidak terautentikasi, skenario berikut **Belum diuji**: menutup dengan tombol Tutup, menutup dengan ikon X, buka/tutup minimal lima kali, klik sidebar/tombol Tambah/filter dan scroll setelah penutupan, pemeriksaan backdrop secara visual, serta membuka modal setelah update Livewire pada halaman terautentikasi.
