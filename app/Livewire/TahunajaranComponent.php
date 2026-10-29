<?php

namespace App\Livewire;

use App\Models\Tahunajaran;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TahunajaranComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $idtahunajaran;

    public $nama;

    public $aktif;

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

    public function storeTahunajaran()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            $this->validate([
                'nama' => 'required|unique:tahunajaran,nama',
            ]);
        } catch (\Throwable $th) {
            $this->alert('error', 'Validasi gagal: '.$th->getMessage());

            return;
        }

        if (! preg_match('/^\d{4}\/\d{4}$/', $this->nama)) {
            $this->alert('error', 'Format tahun ajaran harus YYYY/YYYY');

            return;
        }
        [$a, $b] = explode('/', $this->nama);
        if ((int) $b !== (int) $a + 1) {
            $this->alert('error', 'Tahun kedua harus tahun pertama + 1');

            return;
        }

        $tahun = new Tahunajaran;
        $tahun->nama = $this->nama;
        $tahun->aktif = false;
        $tahun->save();
        $this->reset();

        $this->alert('success', 'Tahun ajaran berhasil ditambahkan');
        $this->dispatch('close-tahunajaran-modal');
    }

    public function close()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatch('close-tahunajaran-modal');
    }

    public function editTahunajaran($id)
    {
        $tahun = Tahunajaran::where('idtahunajaran', $id)->first();
        $this->idtahunajaran = $tahun->idtahunajaran;
        $this->nama = $tahun->nama;
        $this->aktif = $tahun->aktif;
        $this->dispatch('show-edit-tahunajaran-modal');
    }

    public function updateTahunajaran()
    {
        $this->validate([
            'nama' => 'required|unique:tahunajaran,nama,'.$this->idtahunajaran.',idtahunajaran',
        ]);

        if (! preg_match('/^\d{4}\/\d{4}$/', $this->nama)) {
            $this->alert('error', 'Format tahun ajaran harus YYYY/YYYY');

            return;
        }
        [$a, $b] = explode('/', $this->nama);
        if ((int) $b !== (int) $a + 1) {
            $this->alert('error', 'Tahun kedua harus tahun pertama + 1');

            return;
        }

        $tahun = Tahunajaran::where('idtahunajaran', $this->idtahunajaran)->first();
        $tahun->nama = $this->nama;
        $tahun->save();

        $this->reset();
        $this->alert('success', 'Tahun ajaran berhasil diperbarui');
        $this->dispatch('close-tahunajaran-modal');
    }

    public function deleteConfirmationTahunajaran($id)
    {
        $this->idtahunajaran = $id;
        $this->dispatch('show-delete-confirmation-tahunajaran-modal');
    }

    public function deleteTahunajaran()
    {
        $tahun = Tahunajaran::where('idtahunajaran', $this->idtahunajaran)->first();
        if ($tahun->semesters()->count() > 0) {
            $this->alert('error', 'Tahun ajaran memiliki semester, tidak dapat dihapus');
            $this->dispatch('close-tahunajaran-modal');
            $this->idtahunajaran = '';

            return;
        }
        try {
            $tahun->delete();
            $this->alert('success', 'Tahun ajaran berhasil dihapus');
        } catch (\Throwable $th) {
            $this->alert('error', 'Data tidak dapat dihapus');
        }
        $this->dispatch('close-tahunajaran-modal');
        $this->idtahunajaran = '';
    }

    public function aktifkan($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Tahunajaran::aktifkan($id);
            $this->resetPage();
            $this->alert('success', 'Tahun ajaran diaktifkan');
        } catch (\Throwable $th) {
            $this->alert('error', 'Gagal mengaktifkan tahun ajaran: '.$th->getMessage());
        }
    }

    public function nonaktifkan($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Tahunajaran::nonaktifkan($id);
            $this->resetPage();
            $this->alert('success', 'Tahun ajaran dinonaktifkan');
        } catch (\Throwable $th) {
            $this->alert('error', 'Gagal menonaktifkan tahun ajaran: '.$th->getMessage());
        }
    }

    public function render()
    {
        $tahunajaran = Tahunajaran::latest()
            ->search($this->search)
            ->paginate($this->perPage);

        return view('livewire.tahunajaran-component', [
            'tahunajarans' => $tahunajaran,
        ]);
    }
}
