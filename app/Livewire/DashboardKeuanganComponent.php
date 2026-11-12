<?php

namespace App\Livewire;

use App\Models\Siswa;
use App\Models\Tagihan;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class DashboardKeuanganComponent extends Component
{
    use LivewireAlert {
        alert as protected;
    }

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);

        $totalSiswaAktif = Siswa::where('status', 'aktif')->count();

        $tagihanTerbit = Tagihan::where('status', '!=', 'dibatalkan')->count();
        $tagihanLunas = Tagihan::where('status', 'lunas')->count();
        $tagihanBelumLunas = Tagihan::whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])->count();
        $tagihanDibebaskan = Tagihan::where('status', 'dibebaskan')->count();
        $tagihanDibayarSebagian = Tagihan::where('status', 'dibayar_sebagian')->count();

        $totalNominal = Tagihan::where('status', '!=', 'dibatalkan')->sum('total_tagihan');
        $totalTerbayar = Tagihan::where('status', 'lunas')->sum('total_tagihan');

        $tagihanPerKelas = Tagihan::with('kelas')
            ->where('status', '!=', 'dibatalkan')
            ->selectRaw('kelas_idkelas, kelas_nama, SUM(CASE WHEN status = \'dibayar_sebagian\' THEN total_tagihan * 0.5 ELSE total_tagihan END) as estimasi, SUM(total_tagihan) as total')
            ->groupBy('kelas_idkelas', 'kelas_nama')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'kelas' => $row->kelas_nama ?? $row->kelas?->nama ?? '-',
                'total' => (int) $row->total,
                'estimasi' => (int) $row->estimasi,
            ]);

        return view('livewire.dashboard-keuangan-component', [
            'totalSiswaAktif' => $totalSiswaAktif,
            'tagihanTerbit' => $tagihanTerbit,
            'tagihanLunas' => $tagihanLunas,
            'tagihanBelumLunas' => $tagihanBelumLunas,
            'tagihanDibebaskan' => $tagihanDibebaskan,
            'tagihanDibayarSebagian' => $tagihanDibayarSebagian,
            'totalNominal' => (int) $totalNominal,
            'totalTerbayar' => (int) $totalTerbayar,
            'tagihanPerKelas' => $tagihanPerKelas,
        ]);
    }
}
