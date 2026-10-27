<?php

namespace App\Livewire;

use App\Models\Polajam;
use App\Services\PolajamService;
use App\Support\Hari;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PolajamComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $idpolajam;

    public $nama = '';

    public $hari = [];

    public $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }

    public function updatingSearch(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->persist(null);
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $pola = Polajam::with('hari')->findOrFail($id);
        $this->idpolajam = $pola->idpolajam;
        $this->nama = $pola->nama;
        $this->hari = $pola->hari->pluck('hari')->map(fn ($day) => (string) $day)->all();
        $this->dispatch('show-edit-polajam-modal');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->persist((int) $this->idpolajam);
    }

    public function deleteConfirmation(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idpolajam = $id;
        $this->dispatch('show-delete-polajam-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            PolajamService::hapus((int) $this->idpolajam);
            $this->alert('success', 'Pola jam berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idpolajam', 'nama', 'hari']);
        $this->resetValidation();
        $this->dispatch('close-polajam-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $polas = Polajam::with(['hari', 'jamPelajaran'])
            ->when($this->search !== '', fn ($q) => $q->where('nama', 'like', '%'.$this->search.'%'))
            ->orderBy('nama')
            ->get();

        return view('livewire.polajam-component', [
            'polas' => $polas,
            'hariOptions' => Hari::NAMA,
        ]);
    }

    private function persist(?int $id): void
    {
        try {
            PolajamService::simpan($id, (string) $this->nama, (array) $this->hari);
            $this->alert('success', $id ? 'Pola jam berhasil diperbarui.' : 'Pola jam berhasil ditambahkan.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }
}
