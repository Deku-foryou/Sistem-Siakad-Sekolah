<?php

namespace App\Livewire;

use App\Models\Kelas;
use App\Models\Tahunajaran;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BerandasiswaComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'siswa', 403);

        $siswa = $user->siswa()
            ->select([
                'idsiswa', 'user_iduser', 'nisn', 'nis', 'nama',
                'tahun_masuk', 'status',
            ])
            ->first();

        $tahunAjaranAktif = null;
        $kelasIndukAktif = null;

        if ($siswa) {
            $tahunAjaranAktif = Tahunajaran::query()
                ->where('aktif', true)
                ->value('idtahunajaran');

            if ($tahunAjaranAktif) {
                $kelasIndukAktif = Kelas::query()
                    ->select([
                        'idkelas', 'tahunajaran_idtahunajaran', 'nama',
                        'tingkat', 'jenis', 'guru_idguru',
                    ])
                    ->with('wali:idguru,nama')
                    ->where('tahunajaran_idtahunajaran', $tahunAjaranAktif)
                    ->where('jenis', 'induk')
                    ->whereHas('kelassiswa', fn ($query) => $query->where('siswa_idsiswa', $siswa->idsiswa))
                    ->first();
            }
        }

        return view('livewire.berandasiswa-component', [
            'siswa' => $siswa,
            'akun' => [
                'username' => $user->username ?: '-',
                'wajibGantiPassword' => (bool) $user->wajib_ganti_password,
            ],
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'kelasIndukAktif' => $kelasIndukAktif,
        ]);
    }
}
