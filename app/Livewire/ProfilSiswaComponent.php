<?php

namespace App\Livewire;

use App\Services\JadwalSiswaService;
use App\Services\KonteksAkademikService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Profil Siswa (read-only).
 *
 * - Identitas selalu dari auth()->user()->siswa; tidak pernah dari parameter URL/input.
 * - Tidak ada method untuk mengubah data (update, delete, upload, dll.).
 * - NIK ditampilkan tersamarkan (hanya 4 digit terakhir).
 */
class ProfilSiswaComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'siswa', 403);

        $konteks = KonteksAkademikService::ambil();
        $siswa = $user->siswa()->first();

        $kelasList = collect();

        if ($siswa) {
            // Sumber tunggal penempatan aktif: JadwalSiswaService (kelassiswa aktif, TA aktif).
            $kelasList = app(JadwalSiswaService::class)->kelasAktif($siswa->idsiswa, $konteks);
        }

        $fotoUrl = null;
        if ($siswa && $siswa->foto && Storage::disk('public')->exists($siswa->foto)) {
            $fotoUrl = Storage::disk('public')->url($siswa->foto);
        }

        return view('livewire.profil-siswa-component', [
            'siswa' => $siswa,
            'akun' => [
                'username' => $user->username ?: '-',
                'wajibGantiPassword' => (bool) $user->wajib_ganti_password,
            ],
            'konteks' => $konteks,
            'kelasList' => $kelasList,
            'fotoUrl' => $fotoUrl,
        ]);
    }
}
