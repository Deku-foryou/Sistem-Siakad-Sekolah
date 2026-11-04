<?php

namespace App\Livewire;

use App\Models\JadwalKelas;
use App\Models\Kelassiswa;
use App\Services\KonteksAkademikService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class JadwalSiswaComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->level === 'siswa', 403);

        $konteks = KonteksAkademikService::ambil();
        $siswa = $user->siswa()->first();

        $kelasList = collect();
        $jadwalPerHari = collect();

        if ($konteks->valid() && $siswa) {
            // Penempatan aktif pada tahun ajaran aktif saja.
            $kelasList = Kelassiswa::query()
                ->with('kelas')
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('status', 'aktif')
                ->whereHas('kelas', fn ($q) => $q->where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran))
                ->get()
                ->pluck('kelas')
                ->sortBy('nama')
                ->values();

            $kelasIds = $kelasList->pluck('idkelas')->all();

            if ($kelasIds) {
                $jadwalPerHari = $this->kelompokkanPerHari(
                    JadwalKelas::query()
                        ->with([
                            'semester',
                            'jamhari.kelas',
                            'mapel',
                            'guru',
                        ])
                        ->where('semester_idsemester', $konteks->semester->idsemester)
                        ->whereHas('jamhari', fn ($q) => $q->whereIn('kelas_id', $kelasIds))
                        ->get()
                );
            }
        }

        return view('livewire.jadwal-siswa-component', [
            'user' => $user,
            'siswa' => $siswa,
            'konteks' => $konteks,
            'kelasList' => $kelasList,
            'jadwalPerHari' => $jadwalPerHari,
            'namaHari' => \App\Support\Hari::NAMA,
        ]);
    }

    private function kelompokkanPerHari($jadwal)
    {
        // Urutkan berdasarkan hari, lalu jam_ke, lalu jam mulai.
        return $jadwal
            ->sortBy(fn ($j) => [
                (int) $j->jamhari->hari,
                (int) ($j->jamhari->jam_ke ?? 999),
                (string) $j->jamhari->mulai,
            ])
            ->groupBy(fn ($j) => (int) $j->jamhari->hari)
            ->sortKeys();
    }
}
