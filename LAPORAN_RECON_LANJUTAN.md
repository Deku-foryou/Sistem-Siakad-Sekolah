# LAPORAN RECON LANJUTAN: GURU ↔ MAPEL & DAMPAK PENGHAPUSAN JADWAL LAMA

## A. FOREIGN KEYS
### FK Yang Dimiliki (Milik Tabel Terkait)
- `jadwal`: `pengampu_idpengampu` -> `pengampu.idpengampu`
- `pengampu`: `semester_idsemester` -> `semester.idsemester`, `kelas_idkelas` -> `kelas.idkelas`, `mapel_idmapel` -> `mapel.idmapel`, `guru_idguru` -> `guru.idguru`
- `jampelajaran`: `polajam_idpolajam` -> `polajam.idpolajam`
- `polajamhari`: `polajam_idpolajam` -> `polajam.idpolajam`
- `publikasijadwal`: `semester_idsemester` -> `semester.idsemester`, `kelas_idkelas` -> `kelas.idkelas`

### FK Yang Merujuk (Tabel Lain Merujuk ke Tabel Terkait)
- `jadwal.pengampu_idpengampu`
- `pengampu` (dirujuk oleh `jadwal`)
- `jampelajaran` (tidak dirujuk FK lain)
- `polajam` (dirujuk oleh `jampelajaran`, `polajamhari`)
- `polajamhari` (tidak dirujuk FK lain)
- `publikasijadwal` (tidak dirujuk FK lain)

## B. INVENTARISASI PENGGUNAAN (SELECT-STRING)
(Laporan ini berdasarkan hasil grep/select-string di codebase)
- `Pengampu`: Digunakan di `App\Livewire\JadwalComponent`, `App\Models\Jadwal`, `App\Services\JadwalService`.
- `Jadwal`: Digunakan di `App\Livewire\JadwalComponent`, `App\Models\Jadwal`, `App\Models\Publikasijadwal`.
- `Jampelajaran`: Digunakan di `App\Livewire\JampelajaranComponent`, `App\Models\Jampelajaran`.
- `Polajam`: Digunakan di `App\Livewire\PolajamComponent`, `App\Models\Polajam`, `App\Services\JamHariService`.
- `Publikasi`: Digunakan di `App\Models\Publikasijadwal`, `App\Livewire\BuktiPublikasiComponent`.

## C. DAMPAK PENGHAPUSAN
Jika tabel-tabel ini (`jadwal`, `pengampu`, `jampelajaran`, `polajam`, `polajamhari`, `publikasijadwal`) dihapus kelak, maka:
- Komponen `JadwalComponent`, `JampelajaranComponent`, `PolajamComponent`, `BuktiPublikasiComponent` akan rusak/tidak berfungsi.
- Service `JadwalService`, `JamHariService`, `PolajamService` akan kehilangan fungsi.
- Data yang bergantung pada tabel-tabel ini di *Beranda siswa/guru* (jika ada) akan gagal dimuat.
- Perlu migrasi data (bukan penghapusan data) jika ingin mempertahankan data jadwal lama ke struktur baru.
