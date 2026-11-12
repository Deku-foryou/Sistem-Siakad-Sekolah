<?php

namespace App\Livewire;

use App\Services\KonteksAkademikService;
use App\Services\RaporWaliKelasService;
use App\Support\NamaFile;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Halaman wali kelas: daftar siswa kelas tanggung jawab, status finalisasi,
 * nilai final, rekap kehadiran, catatan rapor, detail rapor, dan PDF.
 *
 * Nilai akademik selalu dari `nilai_akhir` via RaporWaliKelasService;
 * service tidak menghitung ulang. PDF resmi wajib final.
 */
class WaliKelasComponent extends Component
{
    public $kelasId = '';

    public $siswaId = '';

    public $catatan = '';

    public $galat = '';

    public $sukses = '';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);
    }

    public function updatedKelasId(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $this->siswaId = '';
        $this->catatan = '';
        $this->galat = '';
        $this->sukses = '';
    }

    public function updatedSiswaId(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $this->galat = '';
        $this->sukses = '';

        $this->muatCatatan();
    }

    public function muatCatatan(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        if ($this->kelasId === '' || $this->kelasId === null || $this->siswaId === '' || $this->siswaId === null) {
            $this->catatan = '';

            return;
        }

        try {
            $user = auth()->user();
            // Pastikan siswa memang pada kelas yang dipilih (juga memvalidasi ownership).
            RaporWaliKelasService::raporDetail($user, (int) $this->kelasId, (int) $this->siswaId);
            $row = RaporWaliKelasService::catatan($user, (int) $this->kelasId, (int) $this->siswaId);
            $this->catatan = $row?->catatan ?? '';
        } catch (DomainException $exception) {
            Log::warning('wali-kelas: muat catatan gagal', [
                'user_id' => (int) auth()->id(),
                'kelas_id' => (int) $this->kelasId,
                'siswa_id' => (int) $this->siswaId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->catatan = '';
            $this->galat = $exception->getMessage();
            $this->siswaId = '';
        }
    }

    public function simpanCatatan(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $this->galat = '';
        $this->sukses = '';

        try {
            $row = RaporWaliKelasService::simpanCatatan(auth()->user(), (int) $this->kelasId, (int) $this->siswaId, $this->catatan);
            $this->catatan = $row?->catatan ?? '';
            $this->sukses = $row ? 'Catatan rapor berhasil disimpan.' : 'Catatan rapor berhasil dihapus.';
        } catch (DomainException $exception) {
            Log::warning('wali-kelas: simpan catatan gagal', [
                'user_id' => (int) auth()->id(),
                'kelas_id' => (int) $this->kelasId,
                'siswa_id' => (int) $this->siswaId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->galat = $exception->getMessage();
        }
    }

    public function unduhPdf()
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        try {
            $data = RaporWaliKelasService::raporDetail(auth()->user(), (int) $this->kelasId, (int) $this->siswaId, true);
        } catch (DomainException $exception) {
            Log::warning('wali-kelas: gagal menyiapkan PDF', [
                'user_id' => (int) auth()->id(),
                'kelas_id' => (int) $this->kelasId,
                'siswa_id' => (int) $this->siswaId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->galat = $exception->getMessage();

            return null;
        }

        $pdf = Pdf::loadView('pdf.rapor', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false);

        $nisSegmen = NamaFile::aman((string) ($data['siswa']->nis ?: $data['siswa']->idsiswa), 'siswa');
        $namaFile = 'rapor-'.$nisSegmen.'-'.(int) $this->kelasId.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $namaFile);
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, ['guru', 'superadmin'], true), 403);

        $konteks = KonteksAkademikService::ambil();
        $user = auth()->user();
        $pesanGalat = $this->galat;
        $daftarKelas = collect();
        $daftarSiswa = collect();
        $status = null;
        $detail = null;

        if ($konteks->valid()) {
            $daftarKelas = RaporWaliKelasService::daftarKelas($user);
        }

        if ($konteks->valid() && $this->kelasId !== '' && $this->kelasId !== null) {
            try {
                $daftarSiswa = RaporWaliKelasService::daftarSiswa($user, (int) $this->kelasId);
                $status = RaporWaliKelasService::statusFinalisasi($user, (int) $this->kelasId);
            } catch (DomainException $exception) {
                $pesanGalat = $exception->getMessage();
                $this->kelasId = '';
            }
        }

        if ($konteks->valid() && $this->kelasId !== '' && $this->kelasId !== null && $this->siswaId !== '' && $this->siswaId !== null) {
            try {
                $detail = RaporWaliKelasService::raporDetail($user, (int) $this->kelasId, (int) $this->siswaId);
            } catch (DomainException $exception) {
                $pesanGalat = $exception->getMessage();
                $this->siswaId = '';
            }
        }

        return view('livewire.wali-kelas-component', [
            'konteks' => $konteks,
            'daftarKelas' => $daftarKelas,
            'daftarSiswa' => $daftarSiswa,
            'status' => $status,
            'detail' => $detail,
            'pesanGalat' => $pesanGalat,
        ]);
    }
}
