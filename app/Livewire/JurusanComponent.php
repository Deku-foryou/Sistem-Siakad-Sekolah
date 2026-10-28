<?php

namespace App\Livewire;

use App\Models\Jurusan;
use Illuminate\Support\Rule;
use App\Livewire\Concerns\HandlesImport;
use App\Livewire\Concerns\HandlesImport as HandleImportTrait;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class JurusanComponent extends Component
{
    use HandlesImport;
    use LivewireAlert;
    use WithPagination;

    public $idjurusan;

    public $kode;

    public $nama;

    public $deskripsi;

    public $aktif = true;

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

    public function storeJurusan()
    {
        $this->validate([
            'kode' => ['required', Rule::unique('jurusan', 'kode')],
            'nama' => 'required',
            'deskripsi' => 'nullable',
            'aktif' => 'boolean',
        ]);

        $jurusan = Jurusan::create([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'aktif' => $this->aktif,
        ]);

        $this->reset(['idjurusan', 'kode', 'nama', 'deskripsi', 'aktif']);
        $this->alert('success', 'Jurusan berhasil ditambahkan');
        $this->dispatch('close-jurusan-modal');
    }

    public function close()
    {
        $this->reset(['idjurusan', 'kode', 'nama', 'deskripsi', 'aktif']);
        $this->aktif = true;
        $this->resetValidation();
        $this->dispatch('close-jurusan-modal');
    }

    public function editJurusan($id)
    {
        $jurusan = Jurusan::where('idjurusan', $id)->first();
        $this->idjurusan = $jurusan->idjurusan;
        $this->kode = $jurusan->kode;
        $this->nama = $jurusan->nama;
        $this->deskripsi = $jurusan->deskripsi;
        $this->aktif = $jurusan->aktif;
        $this->dispatch('show-edit-jurusan-modal');
    }

    public function updateJurusan()
    {
        $this->validate([
            'kode' => 'required|unique:jurusan,kode,'.$this->idjurusan.',idjurusan',
            'nama' => 'required',
            'deskripsi' => 'nullable',
            'aktif' => 'boolean',
        ]);

        $jurusan = Jurusan::where('idjurusan', $this->idjurusan)->first();
        $jurusan->kode = $this->kode;
        $jurusan->nama = $this->nama;
        $jurusan->deskripsi = $this->deskripsi;
        $jurusan->aktif = $this->aktif;
        $jurusan->save();

        $this->reset(['idjurusan', 'kode', 'nama', 'deskripsi', 'aktif']);
        $this->aktif = true;
        $this->alert('success', 'Jurusan berhasil diperbarui');
        $this->dispatch('close-jurusan-modal');
    }

    public function deleteConfirmationJurusan($id)
    {
        $this->idjurusan = $id;
        $this->dispatch('show-delete-confirmation-jurusan-modal');
    }

    public function deleteJurusan()
    {
        $jurusan = Jurusan::where('idjurusan', $this->idjurusan)->first();
        if ($jurusan->kelas()->count() > 0) {
            $this->alert('error', 'Jurusan masih digunakan oleh kelas, tidak dapat dihapus');
            $this->dispatch('close-jurusan-modal');
            $this->idjurusan = '';

            return;
        }
        try {
            $jurusan->delete();
            $this->alert('success', 'Jurusan berhasil dihapus');
        } catch (\Throwable $th) {
            $this->alert('error', 'Data tidak dapat dihapus');
        }
        $this->dispatch('close-jurusan-modal');
        $this->idjurusan = '';
    }

    public function render()
    {
        $jurusan = Jurusan::latest()
            ->when($this->search, fn ($q) => $q->where('kode', 'like', '%'.$this->search.'%')
                ->orWhere('nama', 'like', '%'.$this->search.'%'))
            ->paginate($this->perPage);

        return view('livewire.jurusan-component', [
            'jurusans' => $jurusan,
        ]);
    }
}