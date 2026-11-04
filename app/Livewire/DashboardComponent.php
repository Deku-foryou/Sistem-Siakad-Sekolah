<?php

namespace App\Livewire;

use App\Services\KonteksAkademikService;
use App\Support\KonteksAkademik;
use Livewire\Component;

class DashboardComponent extends Component
{
    public function mount(): void
    {
        $this->pastikanSuperadmin();
    }

    public function render()
    {
        // Pemeriksaan diulang di render() karena mount() hanya berjalan pada
        // pemuatan awal, sedangkan request Livewire lanjutan (update) tidak
        // melewati mount().
        $this->pastikanSuperadmin();

        $konteks = KonteksAkademikService::ambil();

        return view('livewire.dashboard-component', [
            'pengguna' => auth()->user(),
            'konteks' => $konteks,
            'statistik' => $this->ambilStatistik($konteks),
        ]);
    }

    private function ambilStatistik(KonteksAkademik $konteks): array
    {
        $statistik = [
            'siswa_aktif' => \App\Models\Siswa::where('status', 'aktif')->count(),
            'guru_aktif' => \App\Models\Guru::where('status', 'aktif')->count(),
            'mapel' => \App\Models\Mapel::count(),
            'kelas' => null,
        ];

        if ($konteks->valid()) {
            $statistik['kelas'] = \App\Models\Kelas::where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)->count();
        }

        return $statistik;
    }

    private function pastikanSuperadmin(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }
}
