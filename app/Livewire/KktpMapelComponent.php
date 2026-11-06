<?php

namespace App\Livewire;

use App\Models\KktpMapel;
use App\Models\Mapel;
use App\Models\Semester;
use App\Services\KktpMapelService;
use App\Services\KonteksAkademikService;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KktpMapelComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $idkktp_mapel;

    public $filterSemester = '';

    public $semester_idsemester = '';

    public $mapel_idmapel = '';

    public $nilai_minimal = '';

    public $deskripsi = '';

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

        $this->semester_idsemester = $this->filterSemester;
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->persist(null);
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $row = KktpMapel::findOrFail($id);
        $this->idkktp_mapel = $row->idkktp_mapel;
        $this->semester_idsemester = $row->semester_idsemester;
        $this->mapel_idmapel = $row->mapel_idmapel;
        $this->nilai_minimal = $row->nilai_minimal;
        $this->deskripsi = $row->deskripsi;
        $this->dispatch('show-edit-kktp-modal');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->persist((int) $this->idkktp_mapel);
    }

    public function deleteConfirmation(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->idkktp_mapel = $id;
        $this->dispatch('show-delete-kktp-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        try {
            KktpMapelService::hapus((int) $this->idkktp_mapel);
            $this->alert('success', 'KKTP berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->reset(['idkktp_mapel', 'mapel_idmapel', 'nilai_minimal', 'deskripsi']);
        $this->semester_idsemester = $this->filterSemester;
        $this->resetValidation();
        $this->dispatch('close-kktp-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $rows = KktpMapel::with(['semester.tahunajaran', 'mapel'])
            ->when($this->filterSemester, fn ($query) => $query->where('semester_idsemester', $this->filterSemester))
            ->orderBy('mapel_idmapel')
            ->get();

        return view('livewire.kktp-mapel-component', [
            'semesters' => Semester::with('tahunajaran')->orderByDesc('idsemester')->get(),
            'mapels' => Mapel::orderBy('nama')->get(),
            'rows' => $rows,
        ]);
    }

    private function persist(?int $id): void
    {
        try {
            KktpMapelService::simpan(
                $id,
                (int) $this->semester_idsemester,
                (int) $this->mapel_idmapel,
                $this->nilai_minimal,
                $this->deskripsi
            );
            $this->alert('success', $id
                ? 'KKTP berhasil diperbarui.'
                : 'KKTP berhasil ditambahkan.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }
}
