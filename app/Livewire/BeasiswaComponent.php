<?php

namespace App\Livewire;

use App\Models\Beasiswa;
use App\Models\BeasiswaSiswa;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class BeasiswaComponent extends Component
{
    use LivewireAlert {
        alert as protected;
    }

    public $idbeasiswa;

    public $nama = '';

    public $jenis = 'nominal';

    public $nilai = '';

    public $berlaku_dari = '';

    public $berlaku_sampai = '';

    public $alasan = '';

    public $status = 'aktif';

    public $filterStatus = 'aktif';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->berlaku_dari = date('Y-m-d');
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $beasiswas = Beasiswa::with('penerima')
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('idbeasiswa')
            ->get();

        return view('livewire.beasiswa-component', [
            'beasiswas' => $beasiswas,
        ]);
    }

    public function store(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $validated = $this->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:nominal,persen,pembebasan',
            'nilai' => 'required|integer|min:0|max:100000000',
            'berlaku_dari' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'alasan' => 'nullable|string|max:500',
        ]);

        if ($validated['jenis'] === 'persen' && ((int) $validated['nilai'] < 1 || (int) $validated['nilai'] > 100)) {
            $this->addError('nilai', 'Nilai persen harus antara 1 dan 100.');

            return;
        }

        DB::transaction(function () use ($validated) {
            Beasiswa::create([
                'nama' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'nilai' => (int) $validated['nilai'],
                'berlaku_dari' => $validated['berlaku_dari'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'alasan' => $validated['alasan'] ?? null,
                'status' => 'aktif',
                'dibuat_oleh' => auth()->id(),
            ]);
        });

        $this->resetForm();
        $this->alert('success', 'Beasiswa berhasil ditambahkan.');
    }

    public function edit(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $row = Beasiswa::findOrFail($id);
        $this->idbeasiswa = $row->idbeasiswa;
        $this->nama = $row->nama;
        $this->jenis = $row->jenis;
        $this->nilai = (string) $row->nilai;
        $this->berlaku_dari = $row->berlaku_dari->format('Y-m-d');
        $this->berlaku_sampai = $row->berlaku_sampai ? $row->berlaku_sampai->format('Y-m-d') : '';
        $this->alasan = $row->alasan ?? '';
        $this->dispatch('show-edit-beasiswa-modal');
    }

    public function update(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $validated = $this->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:nominal,persen,pembebasan',
            'nilai' => 'required|integer|min:0|max:100000000',
            'berlaku_dari' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'alasan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $row = Beasiswa::findOrFail($this->idbeasiswa);
            $row->update([
                'nama' => $validated['nama'],
                'jenis' => $validated['jenis'],
                'nilai' => (int) $validated['nilai'],
                'berlaku_dari' => $validated['berlaku_dari'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'alasan' => $validated['alasan'] ?? null,
            ]);
        });

        $this->resetForm();
        $this->alert('success', 'Beasiswa berhasil diperbarui.');
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $row = Beasiswa::findOrFail($id);
        $row->status = $row->status === 'aktif' ? 'nonaktif' : 'aktif';
        $row->save();

        $this->alert('success', 'Status beasiswa diperbarui.');
    }

    public function assignToSiswa(int $beasiswaId, int $siswaId): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        DB::transaction(function () use ($beasiswaId, $siswaId) {
            BeasiswaSiswa::firstOrCreate(
                ['beasiswa_idbeasiswa' => $beasiswaId, 'siswa_idsiswa' => $siswaId],
                [
                    'berlaku_dari' => now()->format('Y-m-d'),
                    'berlaku_sampai' => null,
                    'status' => 'aktif',
                    'dibuat_oleh' => auth()->id(),
                ]
            );
        });

        $this->alert('success', 'Siswa berhasil ditambahkan ke beasiswa.');
    }

    public function revokeFromSiswa(int $beasiswaId, int $siswaId): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        DB::transaction(function () use ($beasiswaId, $siswaId) {
            $row = BeasiswaSiswa::where('beasiswa_idbeasiswa', $beasiswaId)
                ->where('siswa_idsiswa', $siswaId)
                ->first();

            if ($row) {
                $row->status = 'berhenti';
                $row->berlaku_sampai = now()->format('Y-m-d');
                $row->save();
            }
        });

        $this->alert('success', 'Siswa berhasil dicabut dari beasiswa.');
    }

    public function close(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->resetForm();
        $this->dispatch('close-beasiswa-modal');
    }

    public function resetForm(): void
    {
        $this->idbeasiswa = null;
        $this->nama = '';
        $this->jenis = 'nominal';
        $this->nilai = '';
        $this->berlaku_dari = now()->format('Y-m-d');
        $this->berlaku_sampai = '';
        $this->alasan = '';
        $this->status = 'aktif';
        $this->dispatch('close-beasiswa-modal');
    }
}
