<?php

namespace App\Livewire;

use App\Models\PaymentAttempt;
use App\Models\Pembayaran;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Transaksi Pembayaran — operasional superadmin & admin (read-only).
 *
 * Menampilkan:
 * - "Percobaan & Ledger Online": payment attempt beserta ledger yang tercatat.
 * - "Penggunaan Saldo Siswa": riwayat penggunaan saldo oleh siswa/wali (provider='saldo').
 * Tidak ada aksi mutasi; verifikasi hanya lewat webhook/API Midtrans dan verifikasi manual berwenang.
 * Guru: tidak boleh mengakses (backend abort).
 */
class TransaksiPembayaranComponent extends Component
{
    use LivewireAlert, WithPagination;

    public string $filterStatus = '';

    public string $search = '';

    public string $tab = 'attempt';

    public string $filterPeriode = '';

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPeriode(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterStatus = '';
        $this->filterPeriode = '';
        $this->search = '';
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);

        $rows = collect();
        $saldoRows = collect();
        $stats = [];

        $periode = trim($this->filterPeriode);

        if ($this->tab === 'saldo') {
            $saldoQuery = Pembayaran::query()
                ->with(['siswa', 'tagihan', 'inputer', 'verifikator', 'alokasi'])
                ->where('provider', 'saldo')
                ->when($this->search !== '', function ($q) {
                    $q->whereHas('siswa', function ($sq) {
                        $sq->where('nama', 'like', '%'.$this->search.'%')
                            ->orWhere('nis', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($periode !== '', function ($q) use ($periode) {
                    $q->whereHas('tagihan', fn ($tq) => $tq->where('periode', 'like', $periode.'%'));
                });

            $stats = [
                'total' => (clone $saldoQuery)->count(),
                'nominal' => (int) (clone $saldoQuery)->sum('nominal_dibayar'),
                'siswa' => (clone $saldoQuery)->distinct('siswa_idsiswa')->count('siswa_idsiswa'),
            ];

            $saldoRows = $saldoQuery
                ->latest('idpembayaran')
                ->paginate(25);
        } else {
            // Stat cards: mengikuti filter pencarian + periode (tanpa filter status,
            // supaya ringkasan tetap berarti); tabel: ikut filter status juga.
            $statsBase = PaymentAttempt::query()
                ->when($this->search !== '', function ($q) {
                    // JANGAN pakai argumen ke-2 closure (nilainya kondisi boolean, bukan string pencarian).
                    $q->where(function ($qq) {
                        $qq->where('order_id', 'like', '%'.$this->search.'%')
                            ->orWhereHas('siswa', fn ($sq) => $sq->where('nama', 'like', '%'.$this->search.'%'))
                            ->orWhereHas('tagihan', fn ($tq) => $tq->where('periode', 'like', '%'.$this->search.'%'));
                    });
                })
                ->when($periode !== '', function ($q) use ($periode) {
                    $q->whereHas('tagihan', fn ($tq) => $tq->where('periode', 'like', $periode.'%'));
                });

            $attemptBase = (clone $statsBase)->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus));

            $stats = [
                'total' => (clone $statsBase)->count(),
                'success' => (clone $statsBase)->where('status', 'success')->count(),
                'pending' => (clone $statsBase)->whereIn('status', ['created', 'pending'])->count(),
                'failed' => (clone $statsBase)->whereIn('status', ['failed', 'expired'])->count(),
                'verified' => (int) Pembayaran::query()->where('status', 'success')->where('provider', '!=', 'saldo')->sum('nominal_dibayar'),
            ];

            $rows = (clone $attemptBase)
                ->with(['siswa', 'tagihan', 'pembayaran.alokasi'])
                ->latest('idpayment_attempt')
                ->paginate(25);
        }

        $periodeOptions = \App\Models\Tagihan::query()
            ->whereNotNull('periode')
            ->select('periode')
            ->distinct()
            ->orderByDesc('periode')
            ->limit(24)
            ->pluck('periode');

        return view('livewire.transaksi-pembayaran-component', [
            'rows' => $rows,
            'saldoRows' => $saldoRows,
            'stats' => $stats,
            'periodeOptions' => $periodeOptions,
        ]);
    }
}
