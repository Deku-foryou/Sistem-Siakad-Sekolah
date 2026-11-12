<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Halaman /wali-beranda — status akun wali.
 *
 * Ruang lingkup 2B.7.3D: HANYA dashboard status akun.
 * - Tidak menampilkan data siswa, akademik, nilai, rapor, absensi, jadwal.
 * - Tidak melakukan query ke tabel siswa/walimurid — relasi belum ada pada tahap ini.
 */
class WaliBerandaComponent extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()?->level === 'wali', 403);
        abort_unless(auth()->user()?->status_akun === 'aktif', 403, 'Akun tidak aktif.');
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.wali-beranda-component', [
            'user' => $user,
        ]);
    }
}
