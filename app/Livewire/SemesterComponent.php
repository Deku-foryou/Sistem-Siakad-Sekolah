<?php

namespace App\Livewire;

use App\Models\Semester;
use App\Models\Tahunajaran;
use App\Services\KonteksAkademikService;
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
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function updatingPerpage()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->resetPage();
    }

    public function storeSemester()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            $this->validate([
                'tahunajaran_idtahunajaran' => 'required',
                'nama' => 'required',
            ]);
        } catch (\Throwable $th) {
            $this->alert('error', 'Validasi gagal: '.$th->getMessage());

            return;
        }

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
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idsemester', 'tahunajaran_idtahunajaran', 'nama', 'aktif']);
        $this->resetValidation();
        $this->dispatch('close-semester-modal');
    }

    public function editSemester($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $semester = Semester::where('idsemester', $id)->first();
        $this->idsemester = $semester->idsemester;
        $this->tahunajaran_idtahunajaran = $semester->tahunajaran_idtahunajaran;
        $this->nama = $semester->nama;
        $this->aktif = $semester->aktif;
        $this->dispatch('show-edit-semester-modal');
    }

    public function updateSemester()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->validate([
            'tahunajaran_idtahunajaran' => 'required',
            'nama' => 'required',
        ]);

        $semester = Semester::where('idsemester', $this->idsemester)->first();

        // Semester yang sedang aktif tidak boleh diganti tahun ajaran induknya.
        $parentBerubah = (int) $semester->tahunajaran_idtahunajaran !== (int) $this->tahunajaran_idtahunajaran;
        if ($semester->aktif && $parentBerubah) {
            $this->alert('error', 'Semester aktif tidak dapat diganti tahun ajaran induknya. Nonaktifkan semester terlebih dahulu.');

            return;
        }

        $semester->tahunajaran_idtahunajaran = $this->tahunajaran_idtahunajaran;
        $semester->nama = $this->nama;
        $semester->save();

        $this->reset(['idsemester', 'tahunajaran_idtahunajaran', 'nama', 'aktif']);
        $this->alert('success', 'Semester berhasil diperbarui');
        $this->dispatch('close-semester-modal');
    }

    public function deleteConfirmationSemester($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->idsemester = $id;
        $this->dispatch('show-delete-confirmation-semester-modal');
    }

    public function deleteSemester()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $semester = Semester::where('idsemester', $this->idsemester)->first();
        if ($semester->aktif) {
            $this->alert('error', 'Semester aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.');
            $this->dispatch('close-semester-modal');
            $this->idsemester = '';

            return;
        }
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
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Semester::aktifkan($id);
            $this->resetPage();
            $this->alert('success', 'Semester diaktifkan');
        } catch (\Throwable $th) {
            $this->alert('error', 'Gagal mengaktifkan semester: '.$th->getMessage());
        }
    }

    public function nonaktifkan($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Semester::nonaktifkan($id);
            $this->resetPage();
            $this->alert('warning', 'Semester dinonaktifkan. Tidak ada konteks akademik aktif sampai semester lain diaktifkan.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Gagal menonaktifkan semester: '.$th->getMessage());
        }
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
            'konteks' => KonteksAkademikService::ambil(),
        ]);
    }
}
