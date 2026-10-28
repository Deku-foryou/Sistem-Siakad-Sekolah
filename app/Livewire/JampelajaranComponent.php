<?php

namespace App\Livewire;

use App\Models\Jampelajaran;
use App\Models\Polajamhari;
use App\Services\JamHariService;
use App\Support\Hari;
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

    #[Url(as: 'hari')]
    public $hari = 1;

    public $idjampelajaran = null;

    public $cakupan = 'hari_ini';

    public $targetHari = [];

    public $jenis = 'pelajaran';

    public $jam_ke = '';

    public $mulai = '';

    public $selesai = '';

    public $keterangan = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $first = Polajamhari::query()->min('hari');
        $this->hari = $first ? (int) $first : 1;
        $this->setDefaultTimes();
    }

    public function updatedHari(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->cakupan = 'hari_ini';
        $this->setDefaultTimes();
    }

    public function updatedJenis(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        if ($this->jenis === 'istirahat') {
            $this->jam_ke = '';
        }
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->idjampelajaran = null;
        $this->jenis = 'pelajaran';
        $this->keterangan = '';
        $this->cakupan = 'hari_ini';
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
        $this->jenis = $row->jenis;
        $this->jam_ke = $row->jam_ke ?? '';
        $this->mulai = substr($row->mulai, 0, 5);
        $this->selesai = substr($row->selesai, 0, 5);
        $this->keterangan = $row->keterangan ?? '';
        $this->cakupan = 'hari_ini';
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
        $this->cakupan = 'hari_ini';
        $this->dispatch('show-delete-jampelajaran-modal');
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            JamHariService::hapusJam((int) $this->hari, (int) $this->idjampelajaran, (string) $this->cakupan);
            $this->alert('success', 'Jam berhasil dihapus.');
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function salin(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        try {
            $ringkasan = JamHariService::salinKeHari((int) $this->hari, (array) $this->targetHari);
            $pesan = [];
            foreach ($ringkasan as $item) {
                $namaHari = Hari::nama((int) $item['hari']);
                if ($item['status'] === 'berhasil') {
                    $pesan[] = $namaHari.': berhasil';
                } else {
                    $pesan[] = $namaHari.': ditolak ('.($item['alasan'] ?? '-').')';
                }
            }
            $this->alert('success', implode('; ', $pesan));
            $this->targetHari = [];
            $this->dispatch('close-salin-modal');
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    public function close(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->reset(['idjampelajaran', 'jenis', 'jam_ke', 'mulai', 'selesai', 'keterangan', 'cakupan', 'targetHari']);
        $this->jenis = 'pelajaran';
        $this->cakupan = 'hari_ini';
        $this->resetValidation();
        $this->dispatch('close-jampelajaran-modal');
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $pola = JamHariService::polaUntukHari((int) $this->hari);
        $rows = $pola ? $pola->jamPelajaran()->orderBy('mulai')->get() : collect();
        $pengguna = $pola ? JamHariService::hariPengguna($pola) : [];
        $bersama = count($pengguna) > 1;
        $hariLainDenganJam = [];
        foreach (Hari::NAMA as $no => $nama) {
            if ($no === (int) $this->hari) {
                continue;
            }
            $p = JamHariService::polaUntukHari($no);
            if ($p && $p->jamPelajaran()->exists()) {
                $hariLainDenganJam[] = $no;
            }
        }

        return view('livewire.jampelajaran-component', [
            'pola' => $pola,
            'rows' => $rows,
            'pengguna' => $pengguna,
            'bersama' => $bersama,
            'hariLainDenganJam' => $hariLainDenganJam,
            'hariOptions' => Hari::NAMA,
        ]);
    }

    private function persist(?int $id): void
    {
        try {
            $data = [
                'jenis' => $this->jenis,
                'jam_ke' => $this->jam_ke,
                'mulai' => $this->mulai,
                'selesai' => $this->selesai,
                'keterangan' => $this->keterangan,
            ];
            if ($id === null) {
                JamHariService::tambahJam((int) $this->hari, $data, (string) $this->cakupan);
                $this->alert('success', 'Jam berhasil ditambahkan.');
            } else {
                JamHariService::ubahJam((int) $this->hari, $id, $data, (string) $this->cakupan);
                $this->alert('success', 'Jam berhasil diperbarui.');
            }
            $this->close();
        } catch (DomainException $exception) {
            $this->alert('error', $exception->getMessage());
        }
    }

    private function setDefaultTimes(): void
    {
        $pola = JamHariService::polaUntukHari((int) $this->hari);
        if (! $pola) {
            $this->jam_ke = '1';
            $this->mulai = '';
            $this->selesai = '';

            return;
        }
        $last = $pola->jamPelajaran()->orderByDesc('selesai')->first();
        $nextJam = (int) $pola->jamPelajaran()->where('jenis', 'pelajaran')->max('jam_ke') + 1;
        $this->jam_ke = (string) ($nextJam < 1 ? 1 : $nextJam);
        $this->mulai = $last ? substr($last->selesai, 0, 5) : '';
        $this->selesai = '';
    }
}
