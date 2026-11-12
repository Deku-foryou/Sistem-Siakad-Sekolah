<?php

namespace App\Livewire;

use App\Models\RekonsiliasiHasil;
use App\Services\Keuangan\RekonsiliasiKeuanganService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rekonsiliasi Keuangan — admin & superadmin (read-only; tidak ada perbaikan otomatis).
 *
 * Menampilkan ringkasan pemeriksaan, temuan per kategori, riwayat hasil,
 * dan kolom status. Tidak ada tombol yang mengubah/menghapus transaksi keuangan.
 */
class RekonsiliasiKeuanganComponent extends Component
{
    use LivewireAlert, WithPagination;

    public string $filterPeriode = '';

    public string $filterKategori = '';

    public string $filterStatus = '';

    public string $tab = 'temuan';

    public int $selectedHasilId = 0;

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);

        $terakhir = RekonsiliasiHasil::query()->latest('id')->first();
        $this->selectedHasilId = $terakhir ? (int) $terakhir->id : 0;
    }

    private function assertAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function updatingFilterPeriode(): void
    {
        $this->resetPage();
    }

    public function updatingFilterKategori(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function jalankan(): void
    {
        $this->assertAdmin();

        $periode = trim($this->filterPeriode);
        if ($periode !== '' && ! preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $this->alert('error', 'Periode filter harus berformat YYYY-MM (mis. 2028-03) atau kosong.');

            return;
        }

        $hasil = RekonsiliasiKeuanganService::jalankanPemeriksaan($periode === '' ? null : $periode, Auth::user());
        $this->selectedHasilId = (int) $hasil->id;
        $this->tab = 'temuan';
        $this->alert('success', "Pemeriksaan rekonsiliasi selesai: {$hasil->jumlah_temuan} temuan ({$hasil->jumlah_kritis} kritis).");
    }

    public function pilihHasil(int $id): void
    {
        $this->assertAdmin();

        $hasil = RekonsiliasiHasil::findOrFail($id);
        $this->selectedHasilId = (int) $hasil->id;
        $this->tab = 'temuan';
        $this->resetPage();
    }

    public function render(): View
    {
        $this->assertAdmin();

        $hasilList = RekonsiliasiHasil::query()
            ->with('pembuat')
            ->when($this->filterPeriode !== '' && preg_match('/^\d{4}-\d{2}$/', $this->filterPeriode), fn ($q, $v) => $q->where('periode', $v))
            ->latest('id')
            ->limit(25)
            ->get();

        $hasilDipilih = $this->selectedHasilId > 0 ? RekonsiliasiHasil::with('pembuat')->find($this->selectedHasilId) : null;

        $temuanQuery = $hasilDipilih
            ? $hasilDipilih->temuan()->with(['siswa', 'tagihan'])
            : collect();

        if ($hasilDipilih) {
            if ($this->filterKategori !== '') {
                $temuanQuery->where('kategori', $this->filterKategori);
            }
            if ($this->filterStatus !== '') {
                $temuanQuery->where('status', $this->filterStatus);
            }
        }

        $temuanRows = $hasilDipilih ? $temuanQuery->orderByDesc('id')->paginate(25) : collect();

        $ringkasan = $hasilDipilih ? (array) ($hasilDipilih->ringkasan ?? []) : [];
        $kategoriAda = array_keys(RekonsiliasiKeuanganService::LABEL_KATEGORI);

        return view('livewire.rekonsiliasi-keuangan-component', [
            'hasilList' => $hasilList,
            'hasil' => $hasilDipilih,
            'temuanRows' => $temuanRows,
            'ringkasan' => $ringkasan,
            'kategoriAda' => $kategoriAda,
            'kategoriLabel' => RekonsiliasiKeuanganService::LABEL_KATEGORI,
            'terpotong' => $hasilDipilih ? RekonsiliasiKeuanganService::terpotongPada($hasilDipilih) : [],
            'totalDitemukan' => $hasilDipilih ? RekonsiliasiKeuanganService::totalDitemukanPada($hasilDipilih) : 0,
            'perKategori' => $hasilDipilih ? RekonsiliasiKeuanganService::perKategoriPada($hasilDipilih) : [],
        ]);
    }
}
