<?php

namespace App\Livewire;

use App\Exceptions\FinalisasiException;
use App\Services\FinalisasiNilaiService;
use App\Services\KonteksAkademikService;
use DomainException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Halaman finalisasi rapor tingkat kelas (semester aktif).
 *
 * Akses: wali kelas (melalui relasi `kelas.guru_idguru`) atau superadmin.
 * Otorisasi ditegakkan di service dan method publik Livewire, bukan hanya
 * penyembunyian tombol.
 */
class FinalisasiRaporComponent extends Component
{
    public $kelasId = '';

    public $galat = '';

    public $sukses = '';

    /** @var array<int, string> */
    public $daftarKesalahan = [];

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);
    }

    public function updatedKelasId(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $this->galat = '';
        $this->sukses = '';
        $this->daftarKesalahan = [];
    }

    public function periksa(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $this->galat = '';
        $this->sukses = '';
        $this->daftarKesalahan = [];

        try {
            $this->daftarKesalahan = FinalisasiNilaiService::periksa(auth()->user(), (int) $this->kelasId)['errors'];
            $this->sukses = $this->daftarKesalahan === []
                ? 'Pemeriksaan selesai. Rapor kelas ini siap difinalisasi.'
                : null;
        } catch (DomainException $exception) {
            Log::warning('finalisasi-rapor: periksa gagal', [
                'user_id' => (int) auth()->id(),
                'kelas_id' => (int) $this->kelasId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->galat = $exception->getMessage();
        }
    }

    public function finalisasi(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $this->galat = '';
        $this->sukses = '';
        $this->daftarKesalahan = [];

        try {
            $row = FinalisasiNilaiService::finalisasi(auth()->user(), (int) $this->kelasId);

            $this->sukses = 'Rapor berhasil difinalisasi pada '.$row->difinalisasi_pada?->format('d/m/Y H:i').'.';
        } catch (FinalisasiException $exception) {
            Log::warning('finalisasi-rapor: finalisasi ditolak', [
                'user_id' => (int) auth()->id(),
                'kelas_id' => (int) $this->kelasId,
                'exception' => get_class($exception),
                'jumlah_kesalahan' => count($exception->daftarKesalahan),
                'kesalahan' => $exception->daftarKesalahan,
            ]);
            $this->daftarKesalahan = $exception->daftarKesalahan;
            $this->galat = 'Finalisasi dibatalkan. Perbaiki seluruh poin berikut.';
        } catch (DomainException $exception) {
            Log::warning('finalisasi-rapor: finalisasi gagal', [
                'user_id' => (int) auth()->id(),
                'kelas_id' => (int) $this->kelasId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->galat = $exception->getMessage();
        }
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $konteks = KonteksAkademikService::ambil();
        $user = auth()->user();
        $pesanGalat = $this->galat;
        $daftarKelas = collect();
        $kelas = null;
        $hasilPeriksa = null;
        $status = null;

        if ($konteks->valid()) {
            $daftarKelas = FinalisasiNilaiService::daftarKelas($user);
        }

        if ($konteks->valid() && $this->kelasId !== '' && $this->kelasId !== null) {
            try {
                $hasilPeriksa = FinalisasiNilaiService::periksa($user, (int) $this->kelasId);
                $kelas = $hasilPeriksa['kelas'];
                $status = FinalisasiNilaiService::status((int) $this->kelasId);
            } catch (DomainException $exception) {
                $pesanGalat = $exception->getMessage();
                $this->kelasId = '';
            }
        }

        return view('livewire.finalisasi-rapor-component', [
            'konteks' => $konteks,
            'daftarKelas' => $daftarKelas,
            'kelas' => $kelas,
            'hasilPeriksa' => $hasilPeriksa,
            'status' => $status,
            'pesanGalat' => $pesanGalat,
            'levelLengkap' => $user->level,
        ]);
    }
}
