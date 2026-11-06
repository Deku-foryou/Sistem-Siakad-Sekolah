<?php

namespace App\Livewire;

use App\Services\KonteksAkademikService;
use App\Services\NilaiSiswaService;
use DomainException;
use Livewire\Component;

/**
 * Input nilai guru.
 *
 * Sumber otorisasi tunggal: `penugasan_mengajar` melalui NilaiSiswaService.
 * Seluruh operasi publik memeriksa level guru, dan service memeriksa ulang
 * kepemilikan penugasan, kelas siswa, serta komponen nilai.
 */
class InputNilaiComponent extends Component
{
    public $penugasanId = '';

    /** @var array<int|string, array<int|string, mixed>> */
    public $nilai = [];

    public $galat = '';

    public $sukses = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        try {
            $daftar = NilaiSiswaService::daftarPenugasan(auth()->user());

            if ($daftar->count() === 1) {
                $this->penugasanId = (string) $daftar->first()->idpenugasan_mengajar;
                $this->muat();
            }
        } catch (DomainException $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    public function updatedPenugasanId(): void
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $this->galat = '';
        $this->sukses = '';
        $this->resetErrorBag();
        $this->muat();
    }

    /**
     * Muat nilai tersimpan ke matriks input.
     */
    public function muat(): void
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $this->nilai = [];

        if ($this->penugasanId === '' || $this->penugasanId === null) {
            return;
        }

        try {
            $penugasan = NilaiSiswaService::penugasan(auth()->user(), (int) $this->penugasanId);

            foreach (NilaiSiswaService::rekap($penugasan) as $siswaId => $baris) {
                foreach ($baris['nilai'] as $komponenId => $nilai) {
                    $this->nilai[$siswaId][$komponenId] = $nilai === null ? '' : (string) $nilai;
                }
            }
        } catch (DomainException $exception) {
            $this->penugasanId = '';
            $this->nilai = [];
            $this->galat = $exception->getMessage();
        }
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $this->galat = '';
        $this->sukses = '';

        try {
            $jumlah = NilaiSiswaService::simpanBanyak(
                auth()->user(),
                (int) $this->penugasanId,
                is_array($this->nilai) ? $this->nilai : []
            );

            $this->sukses = $jumlah > 0
                ? $jumlah.' nilai berhasil disimpan.'
                : 'Tidak ada nilai yang diisi, sehingga tidak ada yang disimpan.';

            $this->muat();
        } catch (DomainException $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'guru', 403);

        $konteks = KonteksAkademikService::ambil();
        $pesanGalat = $this->galat;
        $daftarPenugasan = collect();
        $penugasan = null;
        $rekap = collect();
        $komponen = collect();
        $konfigurasiValid = false;
        $terkunci = false;

        if ($konteks->valid()) {
            try {
                $daftarPenugasan = NilaiSiswaService::daftarPenugasan(auth()->user());
            } catch (DomainException $exception) {
                $pesanGalat = $exception->getMessage();
            }
        }

        if ($konteks->valid() && $this->penugasanId !== '' && $this->penugasanId !== null) {
            try {
                $penugasan = NilaiSiswaService::penugasan(auth()->user(), (int) $this->penugasanId);
                $komponen = NilaiSiswaService::komponenAktif($penugasan);
                $konfigurasiValid = NilaiSiswaService::konfigurasiValid($penugasan);
                $terkunci = NilaiSiswaService::terkunci($penugasan);
                $rekap = NilaiSiswaService::rekap($penugasan);
            } catch (DomainException $exception) {
                $pesanGalat = $exception->getMessage();
                $penugasan = null;
                $komponen = collect();
                $rekap = collect();
            }
        }

        $totalBobotAktif = round(
            (float) $komponen->sum(fn ($item) => (float) $item->bobot),
            2
        );

        return view('livewire.input-nilai-component', [
            'konteks' => $konteks,
            'pesanGalat' => $pesanGalat,
            'daftarPenugasan' => $daftarPenugasan,
            'penugasan' => $penugasan,
            'rekap' => $rekap,
            'komponen' => $komponen,
            'konfigurasiValid' => $konfigurasiValid,
            'terkunci' => $terkunci,
            'totalBobotAktif' => $totalBobotAktif,
        ]);
    }
}
