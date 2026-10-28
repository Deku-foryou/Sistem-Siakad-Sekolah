# Laporan Fix Hari

## Hari.php
Konstanta `NAMA`, method `nama(int): string`. Bug: view memakai `Hari::nama` sebagai konstanta. Diperbaiki menjadi `Hari::NAMA`.

## Perbaikan
- `resources/views/livewire/jampelajaran-component.blade.php` baris `@foreach (\App\Support\Hari::NAMA as $no => $nama)`.

## Render
View clear/cache sukses, php -l OK, Pint OK. Render komponen selesai tanpa exception.
