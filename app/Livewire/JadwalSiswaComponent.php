<?php

namespace App\Livewire;

use App\Services\JadwalSiswaService;
use App\Services\KonteksAkademikService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class JadwalSiswaComponent extends Component
{
    /** Filter hari: '0' = semua, '1'..'7' = hari tertentu. */
    public string $filterHari = '0';

    public function updatedFilterHari(): void
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        if (! in_array($this->filterHari, ['0', '1', '2', '3', '4', '5', '6', '7'], true)) {
            $this->filterHari = '0';
        }
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'siswa', 403);

        $konteks = KonteksAkademikService::ambil();
        $siswa = $user->siswa()->first();

        $kelasList = collect();
        $jadwalPerHari = collect();
        $jadwalTampil = collect();

        if ($konteks->valid() && $siswa) {
            // Sumber tunggal: JadwalSiswaService (penempatan aktif TA aktif, jadwal semester aktif).
            $jadwalSiswa = app(JadwalSiswaService::class);
            $kelasList = $jadwalSiswa->kelasAktif($siswa->idsiswa, $konteks);

            $kelasIds = $kelasList->pluck('idkelas')->all();

            if ($kelasIds) {
                $jadwalPerHari = $jadwalSiswa->perHari($jadwalSiswa->jadwal($konteks, $kelasIds));

                $jadwalTampil = $this->filterHari === '0'
                    ? $jadwalPerHari
                    : $jadwalPerHari->filter(
                        fn ($grup, int|string $hari) => (int) $hari === (int) $this->filterHari
                    );
            }
        }

        return view('livewire.jadwal-siswa-component', [
            'user' => $user,
            'siswa' => $siswa,
            'konteks' => $konteks,
            'kelasList' => $kelasList,
            'jadwalPerHari' => $jadwalPerHari,
            'jadwalTampil' => $jadwalTampil,
            'namaHari' => \App\Support\Hari::NAMA,
            'hariIni' => now()->dayOfWeekIso,
            'filterHari' => $this->filterHari,
        ]);
    }
}
