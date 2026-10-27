<?php

namespace App\Livewire;

use App\Models\Jampelajaran;
use App\Models\Polajam;
use App\Services\JampelajaranService;
use DomainException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Url;
use Livewire\Component;

class JampelajaranComponent extends Component
{
    use LivewireAlert {
        alert as protected;
        confirm as protected;
        flash as protected;
    }

    public $idjampelajaran;

    #[Url(as: 'pola')]
    public $polajam_idpolajam = '';

    public $jenis = 'pelajaran';

    public $jam_ke = '';

    public $mulai = '';

    public $selesai = '';

    public $keterangan = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->polajam_idpolajam = $this->polajam_idpolajam
            ?: (Polajam::query()->value('idpolajam') ?? '');
        if ($this->polajam_idpolajam) {
            $this->setDefaultTimes();
        }
    }

    public function updatedJenis(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        if ($this->jenis === 'istirahat') {
            $this->jam_ke = '';
        }
    }

    public function updatedPolajamIdpolajam(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->setDefaultTimes();
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idjampelajaran = null;
        $this->jenis = 'pelajaran';
        $this->keterangan = '';
        $this->setDefaultTimes();
        $this->dispatch('show-add-jampelajaran-modal');
    }

    public function store(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->persist(null);
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $row = Jampelajaran::findOrFail($id);
        $this->idjampelajaran = $row->idjampelajaran;
        $this->polajam_idpolajam = $row->polajam_idpolajam;
        $this->jenis = $row->jenis;
        $this->jam_ke = $row->jam_ke ?? '';
        $this->mulai = substr($row->mulai, 0, 5);
        $this->selesai = substr($row->selesai, 0, 5);
        $this->keterangan = $row->keterangan ?? '';
        $this->dispatch('show-edit-jampelajaran-modal');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->persist((int) $this->idjampelajaran);
    }

    public function deleteConfirmation(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idjampelajaran = $id;
        $this->dispatch('show-delete-jampelajaran-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JampelajaranService::hapus((int) $this->idjampelajaran);
            $this->alert('success', 'Jam berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idjampelajaran', 'jenis', 'jam_ke', 'mulai', 'selesai', 'keterangan']);
        $this->jenis = 'pelajaran';
        $this->resetValidation();
        $this->dispatch('close-jampelajaran-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $polas = Polajam::orderBy('nama')->get();
        $rows = Jampelajaran::with('pola')
            ->when($this->polajam_idpolajam, fn ($q) => $q->where('polajam_idpolajam', $this->polajam_idpolajam))
            ->orderBy('mulai')
            ->get();

        return view('livewire.jampelajaran-component', [
            'polas' => $polas,
            'rows' => $rows,
        ]);
    }

    private function persist(?int $id): void
    {
        try {
            JampelajaranService::simpan(
                $id,
                (int) $this->polajam_idpolajam,
                (string) $this->jenis,
                $this->jam_ke,
                (string) $this->mulai,
                (string) $this->selesai,
                (string) $this->keterangan
            );
            $this->alert('success', $id ? 'Jam berhasil diperbarui.' : 'Jam berhasil ditambahkan.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    private function setDefaultTimes(): void
    {
        if (! $this->polajam_idpolajam) {
            return;
        }
        $last = Jampelajaran::where('polajam_idpolajam', $this->polajam_idpolajam)
            ->orderByDesc('selesai')
            ->first();
        $nextJam = (int) Jampelajaran::where('polajam_idpolajam', $this->polajam_idpolajam)
            ->where('jenis', 'pelajaran')
            ->max('jam_ke') + 1;
        $this->jam_ke = (string) $nextJam;
        $this->mulai = $last ? substr($last->selesai, 0, 5) : '';
        $this->selesai = $last ? date('H:i', strtotime($this->mulai.' +45 minutes')) : '';
    }
}
