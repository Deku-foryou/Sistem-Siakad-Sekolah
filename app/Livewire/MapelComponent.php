<?php

namespace App\Livewire;

use App\Models\Mapel;
use App\Services\MapelService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class MapelComponent extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $idmapel;

    public $kode;

    public $nama;

    public $kelompok = 'wajib';

    public $search = '';

    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        $this->resetPage();
    }

    public function storeMapel()
    {
        $this->validate([
            'kode' => 'required|unique:mapel,kode',
            'nama' => 'required',
            'kelompok' => 'required|in:wajib,pilihan',
        ]);

        $data = [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kelompok' => $this->kelompok,
        ];

        $result = MapelService::buat($data);

        if (! $result['success']) {
            $this->alert('error', 'Gagal menambah mapel: '.implode(', ', array_values($result['errors'] ?? [])));

            return;
        }

        $this->reset(['idmapel', 'kode', 'nama', 'kelompok']);
        $this->alert('success', 'Mapel berhasil ditambahkan');
        $this->dispatch('close-mapel-modal');
    }

    public function close()
    {
        $this->reset(['idmapel', 'kode', 'nama', 'kelompok']);
        $this->kelompok = 'wajib';
        $this->resetValidation();
        $this->dispatch('close-mapel-modal');
    }

    public function editMapel($id)
    {
        $mapel = Mapel::where('idmapel', $id)->first();
        $this->idmapel = $mapel->idmapel;
        $this->kode = $mapel->kode;
        $this->nama = $mapel->nama;
        $this->kelompok = $mapel->kelompok;
        $this->dispatch('show-edit-mapel-modal');
    }

    public function updateMapel()
    {
        $this->validate([
            'kode' => 'required|unique:mapel,kode,'.$this->idmapel.',idmapel',
            'nama' => 'required',
            'kelompok' => 'required|in:wajib,pilihan',
        ]);

        $mapel = Mapel::where('idmapel', $this->idmapel)->first();
        $mapel->kode = $this->kode;
        $mapel->nama = $this->nama;
        $mapel->kelompok = $this->kelompok;
        $mapel->save();

        $this->reset(['idmapel', 'kode', 'nama', 'kelompok']);
        $this->kelompok = 'wajib';
        $this->alert('success', 'Mapel berhasil diperbarui');
        $this->dispatch('close-mapel-modal');
    }

    public function deleteConfirmationMapel($id)
    {
        $this->idmapel = $id;
        $this->dispatch('show-delete-confirmation-mapel-modal');
    }

    public function deleteMapel()
    {
        $mapel = Mapel::where('idmapel', $this->idmapel)->first();
        try {
            $mapel->delete();
            $this->alert('success', 'Mapel berhasil dihapus');
        } catch (\Throwable $th) {
            $this->alert('error', 'Data tidak dapat dihapus');
        }
        $this->dispatch('close-mapel-modal');
        $this->idmapel = '';
    }

    public function render()
    {
        $mapel = Mapel::latest()
            ->search($this->search)
            ->paginate($this->perPage);

        return view('livewire.mapel-component', [
            'mapels' => $mapel,
        ]);
    }
}
