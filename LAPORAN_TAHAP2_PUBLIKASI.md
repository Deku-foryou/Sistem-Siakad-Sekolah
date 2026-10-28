# Laporan Tahap 2 Publikasi

## File diubah
- migration add status to jadwal
- app/Models/Jadwal.php
- app/Services/JadwalService.php
- app/Livewire/JadwalComponent.php
- resources/views/livewire/jadwal-component.blade.php

## Migration
- status enum draft/published default draft. Data lama menjadi draft.

## Workflow
- Draft dapat diedit/dihapus; published diblokir sampai tarik publikasi.
- Publikasi per semester+kelas dengan validasi konflik guru/siswa.
- Tarik publikasi mengubah status draft.

## Hasil uji
- view:cache OK, php -l OK, Pint OK.
- Pengujian browser belum dilakukan.
