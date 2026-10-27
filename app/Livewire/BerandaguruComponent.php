<?php

namespace App\Livewire;

use App\Models\Kelas;
use App\Models\Tahunajaran;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BerandaguruComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'guru', 403);

        $guru = $user->guru()
            ->select(['idguru', 'user_iduser', 'nip', 'nama', 'status'])
            ->first();

        $tahunAjaranAktif = null;
        $kelasWali = collect();

        if ($guru) {
            $tahunAjaranAktif = Tahunajaran::query()
                ->where('aktif', true)
                ->value('idtahunajaran');

            if ($tahunAjaranAktif) {
                $kelasWali = Kelas::query()
                    ->select([
                        'idkelas', 'tahunajaran_idtahunajaran', 'nama',
                        'tingkat', 'jenis', 'guru_idguru',
                    ])
                    ->where('tahunajaran_idtahunajaran', $tahunAjaranAktif)
                    ->where('guru_idguru', $guru->idguru)
                    ->where('jenis', 'induk')
                    ->with([
                        'kelassiswa' => fn ($query) => $query->select([
                            'idkelassiswa', 'kelas_idkelas', 'siswa_idsiswa',
                        ]),
                        'kelassiswa.siswa' => fn ($query) => $query->select([
                            'idsiswa', 'nisn', 'nama', 'jenis_kelamin',
                        ]),
                    ])
                    ->get();
            }
        }

        return view('livewire.berandaguru-component', [
            'guru' => $guru,
            'akun' => [
                'username' => $user->username ?: '-',
                'wajibGantiPassword' => (bool) $user->wajib_ganti_password,
            ],
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'kelasWali' => $kelasWali,
        ]);
    }
}
