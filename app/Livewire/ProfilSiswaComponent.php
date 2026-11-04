<?php

namespace App\Livewire;

use App\Models\Kelassiswa;
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

        if ($siswa && $konteks->valid()) {
            // Penempatan aktif pada tahun ajaran aktif saja.
            $kelasList = Kelassiswa::query()
                ->with(['kelas.jurusan', 'kelas.wali'])
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('status', 'aktif')
                ->whereHas('kelas', fn ($query) => $query->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran))
                ->get()
                ->pluck('kelas')
                ->filter()
                ->sortBy('nama')
                ->values();
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
