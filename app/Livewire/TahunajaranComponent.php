<?php

namespace App\Livewire;

use App\Models\Semester;
use App\Models\Tahunajaran;
use App\Services\KonteksAkademikService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class TahunajaranComponent extends Component
{
    use LivewireAlert;
    use WithPagination;

    // Tahun Ajaran Properties
    public $idtahunajaran;

    public $nama;

    public $aktif;

    // Semester Properties
    public $idsemester;

    public $semester_tahunajaran_id;

    public $semester_nama;

    public $semester_aktif;

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

    public function storeTahunajaran()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->validate([
            'nama' => 'required|unique:tahunajaran,nama',
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

        try {
            $tahun = new Tahunajaran;
            $tahun->nama = $this->nama;
            $tahun->aktif = false;
            $tahun->save();
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Tahun ajaran dengan nama tersebut sudah ada atau tidak dapat disimpan.');

            return;
        }

        $this->reset(['idtahunajaran', 'nama', 'aktif']);

        $this->alert('success', 'Tahun ajaran berhasil ditambahkan');
        $this->dispatch('close-tahunajaran-modal');
    }

    public function close()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idtahunajaran', 'nama', 'aktif', 'idsemester', 'semester_tahunajaran_id', 'semester_nama', 'semester_aktif']);
        $this->resetValidation();
        $this->dispatch('close-tahunajaran-modal');
        $this->dispatch('close-semester-modal');
    }

    public function editTahunajaran($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $tahun = Tahunajaran::query()->findOrFail($id);
        $this->idtahunajaran = $tahun->idtahunajaran;
        $this->nama = $tahun->nama;
        $this->aktif = $tahun->aktif;
        $this->dispatch('show-edit-tahunajaran-modal');
    }

    public function updateTahunajaran()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

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

        $tahun = Tahunajaran::query()->findOrFail($this->idtahunajaran);
        $tahun->nama = $this->nama;
        try {
            $tahun->save();
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Tahun ajaran dengan nama tersebut sudah ada atau tidak dapat diperbarui.');

            return;
        }

        $this->reset(['idtahunajaran', 'nama', 'aktif']);
        $this->alert('success', 'Tahun ajaran berhasil diperbarui');
        $this->dispatch('close-tahunajaran-modal');
    }

    public function deleteConfirmationTahunajaran($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->idtahunajaran = $id;
        $this->dispatch('show-delete-confirmation-tahunajaran-modal');
    }

    public function deleteTahunajaran()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            DB::transaction(function () {
                $tahun = Tahunajaran::query()->lockForUpdate()->findOrFail($this->idtahunajaran);

                if ($tahun->aktif || $tahun->semesters()->where('aktif', true)->exists()) {
                    throw new \DomainException('Tahun ajaran dengan status aktif tidak dapat dihapus.');
                }

                if ($tahun->semesters()->exists()) {
                    throw new \DomainException('Hapus seluruh semester pada tahun ajaran ini terlebih dahulu.');
                }

                $tahun->delete();
            });
            $this->alert('success', 'Tahun ajaran berhasil dihapus');
        } catch (\DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Tahun ajaran tidak dapat dihapus karena masih memiliki data terkait.');
        }
        $this->dispatch('close-tahunajaran-modal');
        $this->idtahunajaran = '';
    }

    // Semester Methods
    public function addSemester($tahunajaranId)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idsemester', 'semester_nama', 'semester_aktif']);
        $this->semester_tahunajaran_id = Tahunajaran::query()->findOrFail($tahunajaranId)->idtahunajaran;
        $this->dispatch('show-add-semester-modal');
    }

    public function storeSemester()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->validate([
            'semester_tahunajaran_id' => 'required|integer|exists:tahunajaran,idtahunajaran',
            'semester_nama' => 'required|in:Ganjil,Genap',
        ]);

        try {
            $semester = new Semester;
            $semester->tahunajaran_idtahunajaran = $this->semester_tahunajaran_id;
            $semester->nama = $this->semester_nama;
            $semester->aktif = false;
            $semester->save();
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Semester tersebut sudah tersedia atau tidak dapat disimpan.');

            return;
        }

        $this->reset(['idsemester', 'semester_tahunajaran_id', 'semester_nama', 'semester_aktif']);
        $this->alert('success', 'Semester berhasil ditambahkan');
        $this->dispatch('close-semester-modal');
    }

    public function editSemester($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $semester = Semester::query()->findOrFail($id);
        $this->idsemester = $semester->idsemester;
        $this->semester_tahunajaran_id = $semester->tahunajaran_idtahunajaran;
        $this->semester_nama = $semester->nama;
        $this->semester_aktif = $semester->aktif;
        $this->dispatch('show-edit-semester-modal');
    }

    public function updateSemester()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->validate([
            'semester_tahunajaran_id' => 'required|integer|exists:tahunajaran,idtahunajaran',
            'semester_nama' => 'required|in:Ganjil,Genap',
        ]);

        try {
            DB::transaction(function () {
                $semester = Semester::query()->lockForUpdate()->findOrFail($this->idsemester);

                // Semester aktif tidak boleh dipindahkan ke tahun ajaran lain.
                $parentBerubah = (int) $semester->tahunajaran_idtahunajaran !== (int) $this->semester_tahunajaran_id;
                if ($semester->aktif && $parentBerubah) {
                    throw new DomainException('Semester aktif tidak dapat dipindahkan ke tahun ajaran lain.');
                }

                $duplicate = Semester::query()
                    ->where('tahunajaran_idtahunajaran', $this->semester_tahunajaran_id)
                    ->where('nama', $this->semester_nama)
                    ->where('idsemester', '!=', $semester->idsemester)
                    ->exists();
                if ($duplicate) {
                    throw new DomainException('Semester sudah ada untuk tahun ajaran tersebut.');
                }

                $semester->tahunajaran_idtahunajaran = $this->semester_tahunajaran_id;
                $semester->nama = $this->semester_nama;
                $semester->save();
            });
        } catch (DomainException $exception) {
            $this->addError('semester_nama', $exception->getMessage());

            return;
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Semester tidak dapat diperbarui karena data terkait atau kombinasi semester sudah digunakan.');

            return;
        }

        $this->reset(['idsemester', 'semester_tahunajaran_id', 'semester_nama', 'semester_aktif']);
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

        try {
            DB::transaction(function () {
                $semester = Semester::query()->lockForUpdate()->findOrFail($this->idsemester);

                if ($semester->aktif) {
                    throw new \DomainException('Semester aktif tidak dapat dihapus.');
                }

                $semester->delete();
            });
            $this->alert('success', 'Semester berhasil dihapus');
        } catch (\DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Semester tidak dapat dihapus karena masih digunakan oleh data akademik.');
        }
        $this->dispatch('close-semester-modal');
        $this->idsemester = '';
    }

    public function aktifkanSemester($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Semester::aktifkan($id);
            $this->alert('success', 'Semester dan tahun ajaran induknya berhasil diaktifkan.');
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        } catch (\RuntimeException $exception) {
            report($exception);
            $this->alert('error', 'Konteks akademik gagal diaktifkan. Periksa data semester dan tahun ajaran, lalu coba lagi.');
        }
    }

    public function nonaktifkanSemester($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Semester::nonaktifkan($id);
            $this->alert('warning', 'Semester dinonaktifkan. Aktifkan semester lain untuk memulihkan konteks akademik.');
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Semester tidak dapat dinonaktifkan karena terjadi masalah pada data terkait.');
        }
    }

    public function nonaktifkan($id)
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            Tahunajaran::nonaktifkan($id);
            $this->alert('warning', 'Tahun ajaran dinonaktifkan.');
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        } catch (QueryException $exception) {
            report($exception);
            $this->alert('error', 'Tahun ajaran tidak dapat dinonaktifkan karena terjadi masalah pada data terkait.');
        }
    }

    public function render()
    {
        $tahunajaran = Tahunajaran::with(['semesters' => function ($query) {
            $query->orderBy('nama');
        }])
            ->orderByDesc('nama')
            ->search($this->search)
            ->paginate($this->perPage);

        return view('livewire.tahunajaran-component', [
            'tahunajarans' => $tahunajaran,
            'konteks' => KonteksAkademikService::ambil(),
            'semuaTahunajaran' => Tahunajaran::query()->orderByDesc('nama')->get(),
        ]);
    }
}
