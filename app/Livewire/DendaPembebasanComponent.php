<?php

namespace App\Livewire;

use App\Models\Denda;
use App\Models\DendaPengajuan;
use App\Models\Tagihan;
use App\Services\Keuangan\DendaPembebasanService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pembebasan Denda — admin mengajukan, superadmin menyetujui/menolak.
 *
 * - Guru/siswa/wali tidak boleh mengakses (backend abort).
 * - Efek finansial hanya melalui proses persetujuan superadmin (reversal/waiver tercatat).
 * - Denda yang dibebaskan tidak pernah tercatat sebagai pembayaran.
 * - Admin tidak dapat menyetujui pengajuannya sendiri (backend).
 */
class DendaPembebasanComponent extends Component
{
    use LivewireAlert, WithPagination;

    public string $tab = 'aktif';

    public string $filterStatus = '';

    public string $search = '';

    public int $selectedTagihanId = 0;

    public string $alasanPengajuan = '';

    public int $selectedPengajuanId = 0;

    public string $alasanPenolakan = '';

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    private function assertAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    private function assertSuperadmin(): void
    {
        abort_unless(Auth::user()?->level === 'superadmin', 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function pilihTagihan(int $id): void
    {
        $this->assertAdmin();
        $this->selectedTagihanId = $id;
        $this->alasanPengajuan = '';
    }

    public function ajukan(): void
    {
        $this->assertAdmin();
        try {
            $tagihan = Tagihan::findOrFail($this->selectedTagihanId);
            DendaPembebasanService::ajukanPembebasanDenda($tagihan, $this->alasanPengajuan, Auth::user());
            $this->alert('success', 'Pengajuan pembebasan denda terkirim. Menunggu keputusan superadmin.');
            $this->alasanPengajuan = '';
            $this->selectedTagihanId = 0;
            $this->dispatch('close-modal-ajukan');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function setujui(int $id): void
    {
        $this->assertSuperadmin();
        try {
            $p = DendaPengajuan::findOrFail($id);
            DendaPembebasanService::setujuiPembebasanDenda($p, Auth::user());
            $this->alert('success', 'Pembebasan denda disetujui; denda dibebaskan; tagihan dikurangi; tercatat sebagai adjustment.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function tolak(): void
    {
        $this->assertSuperadmin();
        if (trim($this->alasanPenolakan) === '') {
            $this->alert('error', 'Alasan penolakan wajib diisi.');

            return;
        }
        try {
            $p = DendaPengajuan::findOrFail($this->selectedPengajuanId);
            DendaPembebasanService::tolakPembebasanDenda($p, $this->alasanPenolakan, Auth::user());
            $this->alert('success', 'Pengajuan pembebasan denda ditolak.');
            $this->alasanPenolakan = '';
            $this->selectedPengajuanId = 0;
            $this->dispatch('close-modal-tolak');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function pilihTolak(int $id): void
    {
        $this->assertSuperadmin();
        $this->selectedPengajuanId = $id;
        $this->alasanPenolakan = '';
    }

    public function render(): View
    {
        $this->assertAdmin();

        $dendaAktif = collect();
        if ($this->tab === 'aktif') {
            $dendaAktif = DendaPembebasanService::tagihanDenganDendaBerlaku($this->search !== '' ? $this->search : null)
                ->paginate(12);
        }

        $pengajuanQuery = DendaPengajuan::query()->with(['tagihan.siswa', 'denda', 'pengaju', 'pemroses']);
        if ($this->filterStatus !== '') {
            $pengajuanQuery->where('status', $this->filterStatus);
        }
        if ($this->tab === 'pengajuan' && $this->search !== '') {
            $pengajuanQuery->whereHas('tagihan.siswa', fn ($sq) => $sq->where('nama', 'like', '%'.$this->search.'%')->orWhere('nis', 'like', '%'.$this->search.'%'));
        }
        $pengajuan = $this->tab === 'aktif' ? collect() : $pengajuanQuery->latest('id')->paginate(15);

        $selected = $this->selectedTagihanId ? Tagihan::with(['siswa', 'denda'])->find($this->selectedTagihanId) : null;

        return view('livewire.denda-pembebasan-component', [
            'dendaAktif' => $dendaAktif,
            'pengajuan' => $pengajuan,
            'selected' => $selected,
        ]);
    }
}
