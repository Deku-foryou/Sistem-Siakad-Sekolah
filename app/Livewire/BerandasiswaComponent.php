<?php

namespace App\Livewire;

use App\Services\JadwalSiswaService;
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
            // Sumber tunggal: JadwalSiswaService (penempatan aktif TA aktif, jadwal semester aktif).
            $jadwalSiswa = app(JadwalSiswaService::class);
            $kelasList = $jadwalSiswa->kelasAktif($siswa->idsiswa, $konteks);

            $kelasIds = $kelasList->pluck('idkelas')->all();

            if ($kelasIds) {
                $hariIni = now()->dayOfWeekIso;
                $jadwalHariIni = $jadwalSiswa->urutkan(
                    $jadwalSiswa->jadwal($konteks, $kelasIds, $hariIni)
                );
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
