<?php

namespace App\Livewire;

use App\Models\Siswa;
use App\Services\WalimuridService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Portal self-service wali: daftar relasi milik sendiri, ajukan/ajukan ulang relasi,
 * dan P5b (permintaan pemutusan). Hanya akun wali aktif.
 */
class WaliRelasiComponent extends Component
{
    use WithPagination;

    public $search = '';

    public $perpage = 10;

    // Form pengajuan baru
    public $siswaId = '';

    public $hubungan = 'wali';

    public $alasanPengajuan = '';

    // Form pengajuan ulang
    public $relasiAsalId = null;

    public $hubunganUlang = 'wali';

    public $alasanUlang = '';

    // Form permintaan putus (P5b)
    public $targetRelasiPutusId = null;

    public $alasanPutus = '';

    public $galat = '';

    public $sukses = '';

    public function boot(): void
    {
        WalimuridService::pastikanWaliAktif(Auth::user());
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'perpage'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $wali = Auth::user();
        $relasi = WalimuridService::relasiUntukWali($wali)
            ->with('permintaanPutus')
            ->paginate($this->perpage);

        $hasilCari = collect();
        if (trim($this->search) !== '') {
            $hasilCari = WalimuridService::cariSiswaRelevan($wali, $this->search, 20);
        }

        $siswaTerpilih = $this->siswaId !== ''
            ? Siswa::query()->whereKey((int) $this->siswaId)->first()
            : null;

        return view('livewire.wali-relasi-component', [
            'relasi' => $relasi,
            'hasilCari' => $hasilCari,
            'siswaTerpilih' => $siswaTerpilih,
        ]);
    }

    public function gantiSiswa(): void
    {
        $this->reset('siswaId', 'hubungan', 'alasanPengajuan');
    }

    public function pilihSiswa(int $siswaId): void
    {
        $this->siswaId = (string) $siswaId;
        $this->search = '';
    }

    public function ajukan(): void
    {
        $this->galat = '';
        $this->sukses = '';
        if ($this->siswaId === '') {
            $this->galat = 'Pilih siswa terlebih dahulu.';

            return;
        }
        try {
            WalimuridService::ajukanRelasi(
                Auth::user(),
                (int) $this->siswaId,
                $this->hubungan,
                $this->alasanPengajuan !== '' ? $this->alasanPengajuan : null
            );
            $this->sukses = 'Pengajuan relasi berhasil dikirim dan menunggu verifikasi.';
            $this->reset('siswaId', 'hubungan', 'alasanPengajuan', 'search');
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->galat = $e->getMessage();
        }
    }

    public function bukaUlang(int $relasiId): void
    {
        $this->relasiAsalId = $relasiId;
        $this->hubunganUlang = 'wali';
        $this->alasanUlang = '';
        $this->galat = '';
        $this->sukses = '';
    }

    public function ajukanUlang(): void
    {
        $this->galat = '';
        $this->sukses = '';
        if ($this->relasiAsalId === null) {
            return;
        }
        try {
            WalimuridService::ajukanUlangRelasi(
                Auth::user(),
                (int) $this->relasiAsalId,
                $this->hubunganUlang,
                $this->alasanUlang !== '' ? $this->alasanUlang : null
            );
            $this->sukses = 'Pengajuan ulang berhasil dikirim.';
            $this->reset('relasiAsalId', 'hubunganUlang', 'alasanUlang');
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->galat = $e->getMessage();
        }
    }

    public function batalUlang(): void
    {
        $this->reset('relasiAsalId', 'hubunganUlang', 'alasanUlang');
    }

    public function bukaPutus(int $relasiId): void
    {
        $this->targetRelasiPutusId = $relasiId;
        $this->alasanPutus = '';
        $this->galat = '';
        $this->sukses = '';
    }

    public function ajukanPutus(): void
    {
        $this->galat = '';
        $this->sukses = '';
        if ($this->targetRelasiPutusId === null) {
            return;
        }
        if (trim($this->alasanPutus) === '') {
            $this->addError('alasanPutus', 'Alasan wajib diisi.');

            return;
        }
        try {
            WalimuridService::buatPermintaanPutus(
                Auth::user(),
                (int) $this->targetRelasiPutusId,
                $this->alasanPutus
            );
            $this->sukses = 'Permintaan pemutusan dikirim dan menunggu keputusan pengelola.';
            $this->reset('targetRelasiPutusId', 'alasanPutus');
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->galat = $e->getMessage();
        }
    }

    public function batalPutus(): void
    {
        $this->reset('targetRelasiPutusId', 'alasanPutus');
    }
}
