<?php

namespace App\Livewire;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Tarif;
use App\Models\TarifRiwayat;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TarifComponent extends Component
{
    use LivewireAlert {
        alert as protected;
    }

    public $idtarif;

    public $jenis = 'global';

    public $nama = '';

    public $nominal = '';

    public $berlaku_dari = '';

    public $berlaku_sampai = '';

    public $kelas_idkelas = '';

    public $siswa_idsiswa = '';

    public $alasan = '';

    public $status = 'aktif';

    public $filterJenis = '';

    public $filterStatus = 'aktif';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->berlaku_dari = date('Y-m-d');
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $tarifs = Tarif::with(['kelas', 'siswa'])
            ->when($this->filterJenis, fn ($q, $v) => $q->where('jenis', $v))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('idtarif')
            ->get();

        $kelas = Kelas::orderBy('nama')->get();
        $siswa = Siswa::where('status', 'aktif')->orderBy('nama')->limit(500)->get();

        return view('livewire.tarif-component', [
            'tarifs' => $tarifs,
            'kelasOptions' => $kelas,
            'siswaOptions' => $siswa,
        ]);
    }

    public function updatedJenis(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        if ($this->jenis !== 'kelas') {
            $this->kelas_idkelas = '';
        }

        if ($this->jenis !== 'individu') {
            $this->siswa_idsiswa = '';
        }
    }

    public function store(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $validated = $this->validate([
            'jenis' => 'required|in:global,kelas,individu',
            'nama' => 'required|string|max:150',
            'nominal' => 'required|integer|min:1',
            'berlaku_dari' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'kelas_idkelas' => 'nullable|required_if:jenis,kelas|exists:kelas,idkelas',
            'siswa_idsiswa' => 'nullable|required_if:jenis,individu|exists:siswa,idsiswa',
            'alasan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $created = Tarif::create([
                'jenis' => $validated['jenis'],
                'nama' => $validated['nama'],
                'nominal' => (int) $validated['nominal'],
                'berlaku_dari' => $validated['berlaku_dari'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'status' => 'aktif',
                'kelas_idkelas' => $validated['kelas_idkelas'] ?? null,
                'siswa_idsiswa' => $validated['siswa_idsiswa'] ?? null,
                'alasan' => $validated['alasan'] ?? null,
                'dibuat_oleh' => auth()->id(),
            ]);

            TarifRiwayat::create([
                'tarif_idtarif' => $created->idtarif,
                'aksi' => 'dibuat',
                'nominal_baru' => $created->nominal,
                'nama_baru' => $created->nama,
                'berlaku_dari_baru' => $created->berlaku_dari,
                'berlaku_sampai_baru' => $created->berlaku_sampai,
                'status_baru' => 'aktif',
                'alasan' => $validated['alasan'] ?? null,
                'oleh' => auth()->id(),
            ]);
        });

        $this->resetForm();
        $this->alert('success', 'Tarif berhasil ditambahkan.');
    }

    public function edit(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $row = Tarif::findOrFail($id);
        $this->idtarif = $row->idtarif;
        $this->jenis = $row->jenis;
        $this->nama = $row->nama;
        $this->nominal = (string) $row->nominal;
        $this->berlaku_dari = $row->berlaku_dari->format('Y-m-d');
        $this->berlaku_sampai = $row->berlaku_sampai ? $row->berlaku_sampai->format('Y-m-d') : '';
        $this->kelas_idkelas = $row->kelas_idkelas ? (string) $row->kelas_idkelas : '';
        $this->siswa_idsiswa = $row->siswa_idsiswa ? (string) $row->siswa_idsiswa : '';
        $this->alasan = $row->alasan ?? '';
        $this->status = $row->status;
        $this->dispatch('show-edit-tarif-modal');
    }

    public function update(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $validated = $this->validate([
            'jenis' => 'required|in:global,kelas,individu',
            'nama' => 'required|string|max:150',
            'nominal' => 'required|integer|min:1',
            'berlaku_dari' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'kelas_idkelas' => 'nullable|required_if:jenis,kelas|exists:kelas,idkelas',
            'siswa_idsiswa' => 'nullable|required_if:jenis,individu|exists:siswa,idsiswa',
            'alasan' => 'nullable|string|max:500',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        DB::transaction(function () use ($validated) {
            $row = Tarif::findOrFail($this->idtarif);
            $lama = [
                'nominal' => $row->nominal,
                'nama' => $row->nama,
                'berlaku_dari' => $row->berlaku_dari,
                'berlaku_sampai' => $row->berlaku_sampai,
                'status' => $row->status,
            ];

            $row->update([
                'jenis' => $validated['jenis'],
                'nama' => $validated['nama'],
                'nominal' => (int) $validated['nominal'],
                'berlaku_dari' => $validated['berlaku_dari'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'kelas_idkelas' => $validated['kelas_idkelas'] ?? null,
                'siswa_idsiswa' => $validated['siswa_idsiswa'] ?? null,
                'alasan' => $validated['alasan'] ?? null,
                'status' => $validated['status'],
            ]);

            TarifRiwayat::create([
                'tarif_idtarif' => $row->idtarif,
                'aksi' => 'diubah',
                'nominal_lama' => $lama['nominal'],
                'nominal_baru' => $row->nominal,
                'nama_lama' => $lama['nama'],
                'nama_baru' => $row->nama,
                'berlaku_dari_lama' => $lama['berlaku_dari'],
                'berlaku_dari_baru' => $row->berlaku_dari,
                'berlaku_sampai_lama' => $lama['berlaku_sampai'],
                'berlaku_sampai_baru' => $row->berlaku_sampai,
                'status_lama' => $lama['status'],
                'status_baru' => $row->status,
                'alasan' => $validated['alasan'] ?? null,
                'oleh' => auth()->id(),
            ]);
        });

        $this->resetForm();
        $this->alert('success', 'Tarif berhasil diperbarui.');
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        DB::transaction(function () use ($id) {
            $row = Tarif::findOrFail($id);
            $lama = $row->status;
            $row->status = $row->status === 'aktif' ? 'nonaktif' : 'aktif';
            $row->save();

            TarifRiwayat::create([
                'tarif_idtarif' => $row->idtarif,
                'aksi' => 'dinonaktifkan',
                'status_lama' => $lama,
                'status_baru' => $row->status,
                'alasan' => 'Toggle status dari UI',
                'oleh' => auth()->id(),
            ]);
        });

        $this->alert('success', 'Status tarif diperbarui.');
    }

    public function close(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->resetForm();
        $this->dispatch('close-tarif-modal');
    }

    public function resetForm(): void
    {
        $this->idtarif = null;
        $this->jenis = 'global';
        $this->nama = '';
        $this->nominal = '';
        $this->berlaku_dari = now()->format('Y-m-d');
        $this->berlaku_sampai = '';
        $this->kelas_idkelas = '';
        $this->siswa_idsiswa = '';
        $this->alasan = '';
        $this->status = 'aktif';
        $this->dispatch('close-tarif-modal');
    }
}
