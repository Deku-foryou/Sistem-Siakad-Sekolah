<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Tahunajaran;
use App\Services\KelasService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class KelasComponent extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $idkelas;

    public $tahunajaran_idtahunajaran;

    public $nama;

    public $tingkat = '10';

    public $jenis = 'induk';

    public $guru_idguru;

    public $filterTahunajaran;

    public $search = '';

    public $perPage = 10;

    public function mount()
    {
        $aktif = Tahunajaran::aktif();
        $this->filterTahunajaran = $aktif?->idtahunajaran;
        $this->tahunajaran_idtahunajaran = $aktif?->idtahunajaran;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function updatedFilterTahunajaran()
    {
        $this->resetPage();
    }

    public function storeKelas()
    {
        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'nama' => 'required',
            'tingkat' => 'required|in:10,11,12',
            'jenis' => 'required|in:induk,mapel_pilihan',
        ]);

        $data = [
            'nama' => $this->nama,
            'tingkat' => $this->tingkat,
            'jenis' => $this->jenis,
            'tahun_ajaran' => $this->tahunajaran_idtahunajaran,
            'nip_wali' => $this->guru_idguru,
        ];

        $result = KelasService::buat($data);

        if (! $result['success']) {
            $this->alert('error', 'Gagal menambah kelas: '.implode(', ', array_values($result['errors'] ?? [])));

            return;
        }

        $this->reset(['idkelas', 'tahunajaran_idtahunajaran', 'nama', 'tingkat', 'jenis', 'guru_idguru']);
        $this->alert('success', 'Kelas berhasil ditambahkan');
        $this->dispatch('close-kelas-modal');
    }

    public function close()
    {
        $this->reset(['idkelas', 'nama', 'guru_idguru']);
        $this->resetValidation();
        $this->dispatch('close-kelas-modal');
    }

    public function editKelas($id)
    {
        $kelas = Kelas::where('idkelas', $id)->first();
        $this->idkelas = $kelas->idkelas;
        $this->tahunajaran_idtahunajaran = $kelas->tahunajaran_idtahunajaran;
        $this->nama = $kelas->nama;
        $this->tingkat = $kelas->tingkat;
        $this->jenis = $kelas->jenis;
        $this->guru_idguru = $kelas->guru_idguru;
        $this->dispatch('show-edit-kelas-modal');
    }

    public function updateKelas()
    {
        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'nama' => 'required',
            'tingkat' => 'required|in:10,11,12',
            'jenis' => 'required|in:induk,mapel_pilihan',
        ]);

        if ($this->jenis !== 'induk') {
            $this->guru_idguru = null;
        }

        if ($this->guru_idguru && $this->jenis !== 'induk') {
            $this->alert('error', 'Wali kelas hanya untuk kelas induk');

            return;
        }

        if ($this->guru_idguru) {
            $exists = Kelas::where('tahunajaran_idtahunajaran', $this->tahunajaran_idtahunajaran)
                ->where('jenis', 'induk')
                ->where('guru_idguru', $this->guru_idguru)
                ->where('idkelas', '!=', $this->idkelas)
                ->exists();
            if ($exists) {
                $this->alert('error', 'Guru sudah menjadi wali kelas lain di tahun ajaran ini');

                return;
            }
        }

        $kelas = Kelas::where('idkelas', $this->idkelas)->first();
        $kelas->tahunajaran_idtahunajaran = $this->tahunajaran_idtahunajaran;
        $kelas->nama = $this->nama;
        $kelas->tingkat = $this->tingkat;
        $kelas->jenis = $this->jenis;
        $kelas->guru_idguru = $this->guru_idguru;
        $kelas->save();

        $this->reset(['idkelas', 'nama', 'guru_idguru']);
        $this->alert('success', 'Kelas berhasil diperbarui');
        $this->dispatch('close-kelas-modal');
    }

    public function deleteConfirmationKelas($id)
    {
        $this->idkelas = $id;
        $this->dispatch('show-delete-confirmation-kelas-modal');
    }

    public function deleteKelas()
    {
        $kelas = Kelas::where('idkelas', $this->idkelas)->first();
        if ($kelas->kelassiswa()->count() > 0) {
            $this->alert('error', 'Kelas memiliki anggota, tidak dapat dihapus');
            $this->dispatch('close-kelas-modal');
            $this->idkelas = '';

            return;
        }
        try {
            $kelas->delete();
            $this->alert('success', 'Kelas berhasil dihapus');
        } catch (\Throwable $th) {
            $this->alert('error', 'Data tidak dapat dihapus');
        }
        $this->dispatch('close-kelas-modal');
        $this->idkelas = '';
    }

    public function render()
    {
        $kelas = Kelas::with(['tahunajaran', 'wali'])
            ->when($this->filterTahunajaran, function ($q) {
                $q->where('tahunajaran_idtahunajaran', $this->filterTahunajaran);
            })
            ->latest()
            ->search($this->search)
            ->paginate($this->perPage);

        $tahunajarans = Tahunajaran::orderBy('nama')->get();
        $gurus = Guru::where('status', 'aktif')->orderBy('nama')->get();

        return view('livewire.kelas-component', [
            'kelases' => $kelas,
            'tahunajarans' => $tahunajarans,
            'gurus' => $gurus,
        ]);
    }
}
