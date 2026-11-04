<?php

namespace App\Livewire;

use App\Models\Pengumuman;
use App\Services\PengumumanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman pengumuman siswa (read-only).
 *
 * - Daftar: pengumuman terbit, waktu_terbit <= now, periode berlaku aktif, snapshot ada.
 * - Riwayat: pengumuman arsip atau terbit kedaluwarsa, waktu_terbit <= now, berlaku_dari tercapai, snapshot ada.
 * - Detail: snapshot wajib ada + syarat waktu/status terpenuhi; tandai dibaca idempoten.
 * - Identitas selalu dari auth()->user()->siswa; tidak pernah dari input.
 */
class PengumumanSiswaComponent extends Component
{
    use WithPagination;

    public string $tab = 'aktif';

    public ?int $detailId = null;

    public function mount($id = null)
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        if ($id !== null) {
            $this->detailId = (int) $id;
        }
    }

    public function gantiTab(string $tab): void
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        if (! in_array($tab, ['aktif', 'riwayat'], true)) {
            $tab = 'aktif';
        }

        $this->tab = $tab;
        $this->resetPage('aktif');
        $this->resetPage('riwayat');
    }

    public function bukaDetail(int $id)
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $this->detailId = $id;
    }

    public function tutupDetail()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $this->detailId = null;
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $user = Auth::user();
        $siswa = $user->siswa()->first();

        $daftarAktif = null;
        $riwayat = null;
        $detail = null;
        $baca = null;

        if ($siswa) {
            $service = app(PengumumanService::class);

            $daftarAktif = $service->daftarAktif($siswa->idsiswa);
            $riwayat = $service->riwayat($siswa->idsiswa);

            if ($this->detailId) {
                $detail = $service->detailUntukSiswa($siswa->idsiswa, $this->detailId);
                $baca = $service->tandaiSudahDibaca($siswa->idsiswa, $this->detailId);
            }
        }

        // Belum dibaca untuk daftar aktif (badge).
        $belumDibaca = 0;
        if ($siswa && $daftarAktif) {
            foreach ($daftarAktif->items() as $item) {
                $row = $item->baca->firstWhere('siswa_idsiswa', $siswa->idsiswa);
                if ($row && $row->dibaca_pada === null) {
                    $belumDibaca++;
                }
            }
        }

        return view('livewire.pengumuman-siswa-component', [
            'user' => $user,
            'siswa' => $siswa,
            'tab' => $this->tab,
            'daftarAktif' => $daftarAktif,
            'riwayat' => $riwayat,
            'detail' => $detail,
            'baca' => $baca,
            'belumDibaca' => $belumDibaca,
            'detailId' => $this->detailId,
        ]);
    }
}
