<?php

namespace App\Livewire;

use App\Models\JadwalKelas;
use App\Models\Kelassiswa;
use App\Services\KonteksAkademikService;
use App\Support\Hari;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Beranda Siswa (read-only).
 *
 * - Konteks akademik (tahun ajaran & semester) selalu dari KonteksAkademikService::ambil().
 * - Jadwal hari ini berasal dari jadwalkelas + jamhari, sama seperti /jadwalsiswa.
 * - Tidak membaca tabel jadwal legacy (jadwal / publikasijadwal).
 */
class BerandasiswaComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'siswa', 403);

        $konteks = KonteksAkademikService::ambil();
        $siswa = $user->siswa()
            ->select([
                'idsiswa', 'user_iduser', 'nisn', 'nis', 'nama',
                'tahun_masuk', 'status',
            ])
            ->first();

        $kelasList = collect();
        $jadwalHariIni = collect();
        $namaHariIni = Hari::nama(now()->dayOfWeekIso);

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

            $kelasIds = $kelasList->pluck('idkelas')->all();

            if ($kelasIds) {
                $hariIni = now()->dayOfWeekIso;
                $jadwalHariIni = JadwalKelas::query()
                    ->with(['jamhari.kelas', 'mapel', 'guru'])
                    ->where('semester_idsemester', $konteks->semester->idsemester)
                    ->whereHas('jamhari', fn ($query) => $query
                        ->whereIn('kelas_id', $kelasIds)
                        ->where('hari', $hariIni))
                    ->get()
                    ->sortBy(fn ($item) => [
                        (int) ($item->jamhari->jam_ke ?? 999),
                        (string) ($item->jamhari->mulai ?? ''),
                    ])
                    ->values();
            }
        }

        return view('livewire.berandasiswa-component', [
            'siswa' => $siswa,
            'akun' => [
                'username' => $user->username ?: '-',
                'wajibGantiPassword' => (bool) $user->wajib_ganti_password,
            ],
            'konteks' => $konteks,
            'kelasList' => $kelasList,
            'jadwalHariIni' => $jadwalHariIni,
            'namaHariIni' => $namaHariIni,
        ]);
    }
}
