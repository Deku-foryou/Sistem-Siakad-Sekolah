<?php

namespace App\Livewire;

use App\Models\Kelas;
use App\Models\Publikasijadwal;
use App\Models\Semester;
use App\Models\Tahunajaran;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Support\Hari;

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
        $jadwalHariIni = [];
        $namaHariIni = Hari::nama(now()->dayOfWeekIso);

        if ($siswa) {
            $tahunAjaranAktif = Tahunajaran::query()
                ->where('aktif', true)
                ->value('idtahunajaran');

            if ($tahunAjaranAktif) {
                // Ambil semester aktif (misal yang terbaru untuk tahun ajaran aktif)
                $semesterAktifId = Semester::query()
                    ->where('tahunajaran_idtahunajaran', $tahunAjaranAktif)
                    ->orderByDesc('idsemester')
                    ->value('idsemester');

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

                if ($kelasIndukAktif && $semesterAktifId) {
                    $publikasi = Publikasijadwal::query()
                        ->where('semester_idsemester', $semesterAktifId)
                        ->where('kelas_idkelas', $kelasIndukAktif->idkelas)
                        ->where('status', 'published')
                        ->latest('idpublikasijadwal')
                        ->first();

                    if ($publikasi && is_array($publikasi->snapshot)) {
                        $hariIni = now()->dayOfWeekIso;
                        $jadwalHariIni = array_filter($publikasi->snapshot, fn ($item) => isset($item['hari']) && (int) $item['hari'] === $hariIni);
                        usort($jadwalHariIni, fn ($a, $b) => ($a['jam_ke'] ?? 0) <=> ($b['jam_ke'] ?? 0));
                    }
                }
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
            'jadwalHariIni' => $jadwalHariIni,
            'namaHariIni' => $namaHariIni,
        ]);
    }
}
