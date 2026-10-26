<?php

namespace App\Livewire;

use App\Models\Semester;
use App\Models\Tahunajaran;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SemesterComponent extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    use WithPagination;

    public $idsemester;

    public $tahunajaran_idtahunajaran;

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

    public function storeSemester()
    {
        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'nama' => 'required',
        ]);

        $exists = Semester::where('tahunajaran_idtahunajaran', $this->tahunajaran_idtahunajaran)
            ->where('nama', $this->nama)->exists();
        if ($exists) {
            $this->alert('error', 'Semester sudah ada untuk tahun ajaran ini');

            return;
        }

        $semester = new Semester;
        $semester->tahunajaran_idtahunajaran = $this->tahunajaran_idtahunajaran;
        $semester->nama = $this->nama;
        $semester->aktif = false;
        $semester->save();
        $this->reset(['idsemester', 'tahunajaran_idtahunajaran', 'nama', 'aktif']);

        $this->alert('success', 'Semester berhasil ditambahkan');
        $this->dispatch('close-semester-modal');
    }

    public function close()
    {
        $this->reset(['idsemester', 'tahunajaran_idtahunajaran', 'nama', 'aktif']);
        $this->resetValidation();
        $this->dispatch('close-semester-modal');
    }

    public function editSemester($id)
    {
        $semester = Semester::where('idsemester', $id)->first();
        $this->idsemester = $semester->idsemester;
        $this->tahunajaran_idtahunajaran = $semester->tahunajaran_idtahunajaran;
        $this->nama = $semester->nama;
        $this->aktif = $semester->aktif;
        $this->dispatch('show-edit-semester-modal');
    }

    public function updateSemester()
    {
        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'nama' => 'required',
        ]);

        $semester = Semester::where('idsemester', $this->idsemester)->first();
        $semester->tahunajaran_idtahunajaran = $this->tahunajaran_idtahunajaran;
        $semester->nama = $this->nama;
        $semester->save();

        $this->reset(['idsemester', 'tahunajaran_idtahunajaran', 'nama', 'aktif']);
        $this->alert('success', 'Semester berhasil diperbarui');
        $this->dispatch('close-semester-modal');
    }

    public function deleteConfirmationSemester($id)
    {
        $this->idsemester = $id;
        $this->dispatch('show-delete-confirmation-semester-modal');
    }

    public function deleteSemester()
    {
        $semester = Semester::where('idsemester', $this->idsemester)->first();
        try {
            $semester->delete();
            $this->alert('success', 'Semester berhasil dihapus');
        } catch (\Throwable $th) {
            $this->alert('error', 'Data tidak dapat dihapus');
        }
        $this->dispatch('close-semester-modal');
        $this->idsemester = '';
    }

    public function aktifkan($id)
    {
        Semester::aktifkan($id);
        $this->alert('success', 'Semester diaktifkan');
    }

    public function render()
    {
        $semesters = Semester::with('tahunajaran')
            ->latest()
            ->search($this->search)
            ->paginate($this->perPage);

        $tahunajarans = Tahunajaran::orderBy('nama')->get();

        return view('livewire.semester-component', [
            'semesters' => $semesters,
            'tahunajarans' => $tahunajarans,
        ]);
    }
}
