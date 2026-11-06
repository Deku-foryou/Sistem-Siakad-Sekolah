<?php

namespace App\Livewire;

use App\Services\RaporSiswaService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Livewire\Component;

/**
 * Portal rapor siswa (read-only).
 *
 * - Hanya level siswa; identitas selalu dari `User::siswa()` (akun login),
 *   tidak ada ID siswa dari frontend.
 * - Hanya rapor `final` yang ditampilkan; nilai dari `nilai_akhir` snapshot.
 * - PDF memakai template `pdf/rapor` yang sama dengan wali kelas.
 */
class RaporSiswaComponent extends Component
{
    /** Kunci rapor terpilih: "<semesterId>.<kelasId>" (bukan ID siswa). */
    public $raporKunci = '';

    public $galat = '';

    public $sukses = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);
    }

    public function updatedRaporKunci(): void
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $this->galat = '';
        $this->sukses = '';
    }

    public function unduhPdf()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        try {
            [$semesterId, $kelasId] = $this->raporTerpilih();
            $data = RaporSiswaService::detail(auth()->user(), $semesterId, $kelasId);
        } catch (DomainException $exception) {
            $this->galat = $exception->getMessage();

            return null;
        }

        $pdf = Pdf::loadView('pdf.rapor', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false);

        $namaFile = 'rapor-'.($data['siswa']->nis ?: $data['siswa']->idsiswa).'-'.$kelasId.'-'.$semesterId.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $namaFile);
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'siswa', 403);

        $user = auth()->user();
        $pesanGalat = $this->galat;
        $daftarRapor = collect();
        $detail = null;
        $siswa = null;

        try {
            $siswa = RaporSiswaService::pastikanSiswa($user);
            $daftarRapor = RaporSiswaService::daftarRapor($user);
        } catch (DomainException $exception) {
            $pesanGalat = $exception->getMessage();
        }

        if ($this->raporKunci !== '' && $this->raporKunci !== null) {
            try {
                [$semesterId, $kelasId] = $this->raporTerpilih();
                $detail = RaporSiswaService::detail($user, $semesterId, $kelasId);
            } catch (DomainException $exception) {
                $pesanGalat = $exception->getMessage();
                $this->raporKunci = '';
            }
        }

        return view('livewire.rapor-siswa-component', [
            'siswa' => $siswa,
            'daftarRapor' => $daftarRapor,
            'detail' => $detail,
            'pesanGalat' => $pesanGalat,
        ]);
    }

    /**
     * @return array{0: int, 1: int} [semesterId, kelasId]
     */
    private function raporTerpilih(): array
    {
        $bagian = explode('.', trim((string) $this->raporKunci));

        if (count($bagian) !== 2 || ! ctype_digit($bagian[0]) || ! ctype_digit($bagian[1])) {
            throw new DomainException('Rapor yang dipilih tidak valid.');
        }

        return [(int) $bagian[0], (int) $bagian[1]];
    }
}
