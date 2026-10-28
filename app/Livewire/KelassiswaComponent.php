<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesImport;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Siswa;
use App\Models\Tahunajaran;
use App\Services\PenempatanService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class KelassiswaComponent extends Component
{
    use HandlesImport;
    use LivewireAlert;
    use WithPagination;

    public $tahunajaran_id;

    public $kelas_id;

    public $siswa_ids = [];

    public $search = '';

    public $perPage = 10;

    public function mount()
    {
        $aktif = Tahunajaran::aktif();
        $this->tahunajaran_id = $aktif?->idtahunajaran;
    }

    public function updatedTahunajaranId()
    {
        $this->kelas_id = null;
        $this->resetPage();
    }

    public function updatedKelasId()
    {
        $this->resetPage();
    }

    public function tambahSiswa()
    {
        if (! $this->kelas_id || empty($this->siswa_ids)) {
            $this->alert('error', 'Pilih kelas dan siswa');

            return;
        }

        $kelas = Kelas::find($this->kelas_id);
        if (! $kelas) {
            $this->alert('error', 'Kelas tidak ditemukan');

            return;
        }

        foreach ($this->siswa_ids as $sid) {
            $siswa = Siswa::find($sid);
            if (! $siswa) {
                $this->alert('error', 'Siswa tidak ditemukan: '.$sid);

                continue;
            }

            $result = PenempatanService::tambah($kelas, $siswa);

            if (! $result['success']) {
                $this->alert('error', 'Gagal penempatan: '.implode(', ', array_values($result['errors'] ?? [])));

                continue;
            }

            $this->alert('success', 'Siswa berhasil dipasok ke kelas');
        }

        $this->siswa_ids = [];
    }

    public function keluarkan($idkelassiswa)
    {
        $row = Kelassiswa::find($idkelassiswa);
        if ($row) {
            $row->status = 'pindah';
            $row->save();
            $this->alert('success', 'Siswa dikeluarkan dari kelas');
        }
    }

    public function render()
    {
        $tahunajarans = Tahunajaran::orderBy('nama')->get();
        $kelasList = $this->tahunajaran_id
            ? Kelas::where('tahunajaran_idtahunajaran', $this->tahunajaran_id)->orderBy('nama')->get()
            : collect();

        $members = null;
        if ($this->kelas_id) {
            $members = Kelassiswa::with('siswa')
                ->where('kelas_idkelas', $this->kelas_id)
                ->paginate($this->perPage);
        }

        // siswa aktif yang belum di kelas ini (untuk multi-pilih)
        $siswaAktif = Siswa::where('status', 'aktif')
            ->when($this->search, function ($q) {
                $q->where('nama', 'like', '%'.$this->search.'%')
                    ->orWhere('nisn', 'like', '%'.$this->search.'%');
            })
            ->orderBy('nama')
            ->limit(50)
            ->get();

        return view('livewire.kelassiswa-component', [
            'tahunajarans' => $tahunajarans,
            'kelasList' => $kelasList,
            'members' => $members,
            'siswaAktif' => $siswaAktif,
        ]);
    }
}
