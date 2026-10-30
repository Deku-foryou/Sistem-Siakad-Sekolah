<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengampu;
use App\Models\Semester;
use App\Services\PengampuService;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PengampuComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $idpengampu;

    public $filterSemester = '';

    public $filterKelas = '';

    public $semester_idsemester = '';

    public $kelas_idkelas = '';

    public $mapel_idmapel = '';

    public $guru_idguru = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->filterSemester = Semester::where('aktif', true)->value('idsemester')
            ?? Semester::query()->orderByDesc('idsemester')->value('idsemester')
            ?? '';
        $this->semester_idsemester = $this->filterSemester;
    }

    public function updatedFilterSemester(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->filterKelas = '';
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->persist(null);
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $row = Pengampu::findOrFail($id);
        $this->idpengampu = $row->idpengampu;
        $this->semester_idsemester = $row->semester_idsemester;
        $this->kelas_idkelas = $row->kelas_idkelas;
        $this->mapel_idmapel = $row->mapel_idmapel;
        $this->guru_idguru = $row->guru_idguru;
        $this->dispatch('show-edit-pengampu-modal');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->persist((int) $this->idpengampu);
    }

    public function deleteConfirmation(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idpengampu = $id;
        $this->dispatch('show-delete-pengampu-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            PengampuService::hapus((int) $this->idpengampu);
            $this->alert('success', 'Pengampu berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idpengampu', 'kelas_idkelas', 'mapel_idmapel', 'guru_idguru']);
        $this->semester_idsemester = $this->filterSemester;
        $this->resetValidation();
        $this->dispatch('close-pengampu-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $semesters = Semester::with('tahunajaran')->orderByDesc('idsemester')->get();
        $kelas = Kelas::query()
            ->when($this->filterSemester, fn ($q) => $q->whereHas('tahunajaran.semesters', fn ($s) => $s->where('idsemester', $this->filterSemester)))
            ->orderBy('nama')
            ->get();
        $rows = Pengampu::with(['semester.tahunajaran', 'kelas', 'mapel', 'guru'])
            ->withCount('jadwal')
            ->when($this->filterSemester, fn ($q) => $q->where('semester_idsemester', $this->filterSemester))
            ->when($this->filterKelas, fn ($q) => $q->where('kelas_idkelas', $this->filterKelas))
            ->orderBy('kelas_idkelas')
            ->get();
        
        $gurus = $this->mapel_idmapel 
            ? Guru::where('status', 'aktif')
                ->whereHas('mapel', fn($q) => $q->where('mapel.idmapel', $this->mapel_idmapel))
                ->orderBy('nama')->get()
            : Guru::where('status', 'aktif')->orderBy('nama')->get();

        return view('livewire.pengampu-component', [
            'semesters' => $semesters,
            'kelasOptions' => $kelas,
            'mapels' => Mapel::orderBy('nama')->get(),
            'gurus' => $gurus,
            'rows' => $rows,
        ]);
    }

    private function persist(?int $id): void
    {
        try {
            PengampuService::simpan(
                $id,
                (int) $this->semester_idsemester,
                (int) $this->kelas_idkelas,
                (int) $this->mapel_idmapel,
                (int) $this->guru_idguru
            );
            $this->alert('success', $id ? 'Pengampu berhasil diperbarui.' : 'Pengampu berhasil ditambahkan.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }
}
