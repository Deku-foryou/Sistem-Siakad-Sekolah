<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PenugasanMengajar;
use App\Models\Semester;
use App\Services\KonteksAkademikService;
use App\Services\PenugasanMengajarService;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PenugasanMengajarComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $idpenugasan_mengajar;

    public $filterSemester = '';

    public $filterKelas = '';

    public $semester_idsemester = '';

    public $kelas_idkelas = '';

    public $mapel_idmapel = '';

    public $guru_idguru = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->filterSemester = KonteksAkademikService::ambil()->semester?->idsemester
            ?? Semester::where('aktif', true)->value('idsemester')
            ?? Semester::query()->orderByDesc('idsemester')->value('idsemester')
            ?? '';
        $this->semester_idsemester = $this->filterSemester;
    }

    public function updatedFilterSemester(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->filterKelas = '';
    }

    public function updatedSemesterIdsemester(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->kelas_idkelas = '';
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->persist(null);
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $row = PenugasanMengajar::findOrFail($id);
        $this->idpenugasan_mengajar = $row->idpenugasan_mengajar;
        $this->semester_idsemester = $row->semester_idsemester;
        $this->kelas_idkelas = $row->kelas_idkelas;
        $this->mapel_idmapel = $row->mapel_idmapel;
        $this->guru_idguru = $row->guru_idguru;
        $this->dispatch('show-edit-penugasan-modal');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->persist((int) $this->idpenugasan_mengajar);
    }

    public function deleteConfirmation(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->idpenugasan_mengajar = $id;
        $this->dispatch('show-delete-penugasan-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            PenugasanMengajarService::hapus((int) $this->idpenugasan_mengajar);
            $this->alert('success', 'Penugasan mengajar berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->reset(['idpenugasan_mengajar', 'kelas_idkelas', 'mapel_idmapel', 'guru_idguru']);
        $this->semester_idsemester = $this->filterSemester;
        $this->resetValidation();
        $this->dispatch('close-penugasan-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $semesters = Semester::with('tahunajaran')->orderByDesc('idsemester')->get();
        $kelas = Kelas::query()
            ->when(
                $this->filterSemester,
                fn ($query) => $query->whereHas(
                    'tahunajaran.semesters',
                    fn ($semester) => $semester->where('idsemester', $this->filterSemester)
                )
            )
            ->orderBy('nama')
            ->get();

        $kelasForm = Kelas::query()
            ->when(
                $this->semester_idsemester !== '',
                fn ($query) => $query->whereHas(
                    'tahunajaran.semesters',
                    fn ($semester) => $semester->where('idsemester', $this->semester_idsemester)
                )
            )
            ->orderBy('nama')
            ->get();

        $rows = PenugasanMengajar::with(['semester.tahunajaran', 'kelas', 'mapel', 'guru'])
            ->when($this->filterSemester, fn ($query) => $query->where('semester_idsemester', $this->filterSemester))
            ->when($this->filterKelas, fn ($query) => $query->where('kelas_idkelas', $this->filterKelas))
            ->orderBy('kelas_idkelas')
            ->orderBy('mapel_idmapel')
            ->get();

        return view('livewire.penugasan-mengajar-component', [
            'semesters' => $semesters,
            'kelasOptions' => $kelas,
            'kelasFormOptions' => $kelasForm,
            'mapels' => Mapel::orderBy('nama')->get(),
            'gurus' => Guru::where('status', 'aktif')->orderBy('nama')->get(),
            'rows' => $rows,
        ]);
    }

    private function persist(?int $id): void
    {
        try {
            PenugasanMengajarService::simpan(
                $id,
                (int) $this->semester_idsemester,
                (int) $this->kelas_idkelas,
                (int) $this->mapel_idmapel,
                (int) $this->guru_idguru
            );
            $this->alert('success', $id
                ? 'Penugasan mengajar berhasil diperbarui.'
                : 'Penugasan mengajar berhasil ditambahkan.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }
}
