<?php

namespace App\Livewire;

use App\Services\BerandaSiswaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Beranda Siswa (read-only).
 *
 * - Konteks akademik (tahun ajaran & semester) selalu dari KonteksAkademikService::ambil() (via BerandaSiswaService).
 * - Jadwal hari ini berasal dari jadwalkelas + jamhari, sama seperti /jadwalsiswa.
 * - Tidak membaca tabel jadwal legacy (jadwal / publikasijadwal).
 * - Data beranda digabung oleh BerandaSiswaService (dipakai juga oleh API /api/v1/siswa/beranda).
 */
class BerandasiswaComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'siswa', 403);

        return view('livewire.berandasiswa-component', BerandaSiswaService::beranda($user));
    }
}
