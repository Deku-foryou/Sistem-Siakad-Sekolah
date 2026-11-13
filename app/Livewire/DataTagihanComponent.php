<?php

namespace App\Livewire;

use App\Livewire\Concerns\LovSiswa;
use App\Models\Kelas;
use App\Models\Tagihan;
use App\Services\Keuangan\TagihanService;
use App\Services\LovService;
use App\Support\Lov;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class DataTagihanComponent extends Component
{
    use LivewireAlert {
        alert as protected;
    }
    use LovSiswa, WithPagination;

    public $filterStatus = '';

    public $filterJenis = '';

    public $filterPeriode = '';

    public $search = '';

    public $nama = '';

    public $jenis = 'spp';

    public $periode = '';

    public $nominal = '';

    public $jatuhTempo = '';

    public $scope = 'semua';

    public $scopeId = '';

    // siswaId & siswaLabel disediakan trait LovSiswa (mode single)
    public string $siswaId = '';

    public $alasan = '';

    public $detailId = null;

    public function boot(): void
    {
        // Livewire 3: dipanggil pada SETIAP request — otorisasi tidak pernah terlewati.
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function updatedScope(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        // Perubahan cakupan membersihkan pilihan lama (kelas/siswa) agar tidak tertinggal state usang.
        $this->scopeId = '';
        $this->siswaId = '';
        $this->siswaLabel = '';
        $this->reset('lovSearch', 'lovHasil');
    }

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->periode = now()->format('Y-m');
        $this->jatuhTempo = now()->addDays(10)->format('Y-m-d');
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $rows = Tagihan::with(['siswa', 'kelas'])
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filterJenis, fn ($q, $v) => $q->where('jenis', $v))
            ->when($this->filterPeriode, fn ($q, $v) => $q->where('periode', $v))
            ->when($this->search, fn ($q, $v) => $q->whereHas('siswa', fn ($sq) => $sq->where('nama', 'like', "%{$v}%")->orWhere('nis', 'like', "%{$v}%")))
            ->orderByDesc('tanggal_terbit')
            ->orderByDesc('idtagihan')
            ->paginate(25);

        $kelas = Kelas::orderBy('nama')->get();

        return view('livewire.data-tagihan-component', [
            'rows' => $rows,
            'kelasOptions' => $kelas,
        ]);
    }

    public function terbit(string $mode): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        if ($mode === 'spp') {
            $result = TagihanService::terbitSppBulanan($this->periode, auth()->user());
            $tanpaTarif = (int) ($result['tanpa_tarif'] ?? 0);
            $pesan = "Tagihan SPP periode {$this->periode} selesai: {$result['dibuat']} dibuat, {$result['dilewati']} dilewati, {$result['dibebaskan']} dibebaskan";
            if ($tanpaTarif > 0) {
                $pesan .= ", {$tanpaTarif} tanpa tarif (dilewati — tidak dibuatkan tagihan Rp 0; terbitkan ulang setelah tarif diperbaiki)";
            }
            $this->alert($tanpaTarif > 0 ? 'warning' : 'success', $pesan.'.');

            return;
        }

        $validated = $this->validate([
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:ujian,seragam,buku,pendaftaran,insidental,lainnya',
            'periode' => 'required|string|max:30',
            'nominal' => 'required|integer|min:1000',
            'jatuhTempo' => 'required|date|after_or_equal:today',
            'scope' => 'required|in:semua,kelas,siswa',
            'scopeId' => $this->scope === 'kelas' ? 'required|integer|exists:kelas,idkelas' : 'nullable|integer|exists:kelas,idkelas',
            'siswaId' => $this->scope === 'siswa' ? 'required|integer' : 'nullable',
            'alasan' => 'nullable|string|max:500',
        ]);

        // Validasi ulang registry (aktif) bila cakupan siswa — defense in depth.
        if ($validated['scope'] === 'siswa') {
            $siswa = LovService::selected(Lov::KEY_SISWA, $validated['siswaId'] ?? null);
            if ($siswa === null) {
                $this->addError('siswaId', 'Siswa tidak ditemukan atau tidak aktif.');

                return;
            }
        }

        $scopeIdFinal = $validated['scope'] === 'siswa' ? (int) $validated['siswaId'] : ($validated['scopeId'] ? (int) $validated['scopeId'] : null);

        DB::transaction(function () use ($validated, $scopeIdFinal) {
            TagihanService::terbitInsidental(
                $validated['nama'],
                (int) $validated['nominal'],
                $validated['jatuhTempo'],
                $validated['periode'],
                $validated['scope'],
                $scopeIdFinal,
                auth()->user(),
                $validated['jenis'],
            );
        });

        $this->resetForm();
        $this->alert('success', 'Tagihan insidental berhasil diterbitkan.');
    }

    public function hapus(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $tagihan = Tagihan::findOrFail($id);

        if ($tagihan->status !== 'belum_dibayar') {
            $this->alert('error', 'Hanya tagihan belum dibayar yang dapat dibatalkan.');

            return;
        }

        DB::transaction(function () use ($tagihan) {
            TagihanService::batalkan($tagihan, auth()->user(), 'Dibatalkan dari UI');
        });

        $this->alert('success', 'Tagihan berhasil dibatalkan.');
    }

    public function bebaskan(int $id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $tagihan = Tagihan::findOrFail($id);

        DB::transaction(function () use ($tagihan) {
            $tagihan->status = 'dibebaskan';
            $tagihan->save();

            \App\Models\TagihanAdjustment::create([
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'jenis' => 'pembebasan',
                'nominal_lama' => $tagihan->total_tagihan,
                'nominal_baru' => 0,
                'alasan' => 'Pembebasan penuh oleh superadmin',
                'oleh' => auth()->id(),
            ]);
        });

        $this->alert('success', 'Tagihan berhasil dibebaskan.');
    }

    public function lihatDetail(int $id): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->detailId = $id;
        $this->dispatch('show-detail-tagihan-modal');
    }

    public function resetForm(): void
    {
        $this->reset(['nama', 'jenis', 'scopeId', 'alasan']);
        $this->jenis = 'spp';
        $this->scope = 'semua';
        $this->siswaId = '';
        $this->siswaLabel = '';
        $this->reset('lovSearch', 'lovHasil');
        $this->periode = now()->format('Y-m');
        $this->jatuhTempo = now()->addDays(10)->format('Y-m-d');
        $this->dispatch('close-tagihan-modal');
    }

    public function close(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
        $this->dispatch('close-tagihan-modal');
    }
}
