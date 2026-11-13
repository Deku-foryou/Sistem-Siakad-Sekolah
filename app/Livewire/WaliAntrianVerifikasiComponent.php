<?php

namespace App\Livewire;

use App\Models\Walimurid;
use App\Services\WalimuridService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Antrean Verifikasi Wali–Siswa (2B.7.5B).
 * Hanya superadmin & admin sekolah yang memegang menu `wali-antrian-verifikasi`.
 */
class WaliAntrianVerifikasiComponent extends Component
{
    use WithPagination;

    public $search = '';

    public $perpage = 10;

    public $tab = 'pending'; // 'pending' | 'aktif' | 'putus'

    // Aksi per-baris
    public $targetId = null;

    public $aksi = null; // 'verifikasi' | 'tolak' | 'putus'

    // Form verifikasi
    public $tanggalMulai = '';

    public $alasanPeriode = '';

    public $catatan = '';

    // Form penolakan / pemutusan
    public $alasan = '';

    // Permintaan putus (P5b) - admin
    public $targetPermintaanId = null;

    public $aksiPutus = null; // 'setujui' | 'tolak'

    public $catatanKeputusan = '';

    protected $rules = [];

    public function boot(): void
    {
        WalimuridService::pastikanBerwenangVerifikasi(Auth::user());
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'perpage', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        if ($this->tab === 'aktif') {
            $query = WalimuridService::relasiAktif($this->search);
        } elseif ($this->tab === 'putus') {
            $query = WalimuridService::permintaanPutusMenunggu();
            if (trim($this->search) !== '') {
                $q = $this->search;
                // filter not applied to permintaan query beyond base; keep simple
            }
            $items = $query->paginate($this->perpage);

            return view('livewire.wali-antrian-verifikasi-component', ['items' => $items]);
        } else {
            $query = WalimuridService::antreanPending($this->search);
        }
        $items = $query->paginate($this->perpage);

        return view('livewire.wali-antrian-verifikasi-component', [
            'items' => $items,
        ]);
    }

    public function bukaAksi(int $targetId, string $aksi): void
    {
        abort_unless(in_array($aksi, ['verifikasi', 'tolak', 'putus'], true), 404);

        $relasi = Walimurid::findOrFail($targetId);
        // pastikan wewenang server-side (W1-W3)
        WalimuridService::pastikanBerwenangVerifikasi(Auth::user(), $relasi);

        $this->targetId = $targetId;
        $this->aksi = $aksi;
        $this->resetForm();
    }

    public function batalAksi(): void
    {
        $this->reset('targetId', 'aksi', 'tanggalMulai', 'alasanPeriode', 'catatan', 'alasan');
        $this->resetValidation();
    }

    // ---- Permintaan putus (P5b) admin processing ----
    public function bukaSetujuiPutus(int $permintaanId): void
    {
        $this->targetPermintaanId = $permintaanId;
        $this->aksiPutus = 'setujui';
        $this->catatanKeputusan = '';
        $this->galat = '';
        $this->sukses = '';
    }

    public function bukaTolakPutus(int $permintaanId): void
    {
        $this->targetPermintaanId = $permintaanId;
        $this->aksiPutus = 'tolak';
        $this->catatanKeputusan = '';
        $this->galat = '';
        $this->sukses = '';
    }

    public function batalPutusPermintaan(): void
    {
        $this->reset('targetPermintaanId', 'aksiPutus', 'catatanKeputusan');
        $this->resetValidation();
    }

    public function simpanKeputusanPutus(): void
    {
        $aktor = Auth::user();
        try {
            if ($this->aksiPutus === 'setujui') {
                WalimuridService::setujuiPermintaanPutus($aktor, (int) $this->targetPermintaanId, $this->catatanKeputusan !== '' ? $this->catatanKeputusan : null);
                $this->sukses = 'Permintaan pemutusan disetujui; relasi diakhiri.';
            } else {
                if (trim($this->catatanKeputusan) === '') {
                    $this->addError('catatanKeputusan', 'Catatan penolakan wajib diisi.');

                    return;
                }
                WalimuridService::tolakPermintaanPutus($aktor, (int) $this->targetPermintaanId, $this->catatanKeputusan);
                $this->sukses = 'Permintaan pemutusan ditolak.';
            }
        } catch (\Throwable $e) {
            $this->galat = $e->getMessage();

            return;
        }
        $this->reset('targetPermintaanId', 'aksiPutus', 'catatanKeputusan');
        $this->resetValidation();
        $this->resetPage();
    }

    public function simpanAksi(): void
    {
        $aktor = Auth::user();
        $relasi = Walimurid::findOrFail($this->targetId);

        try {
            switch ($this->aksi) {
                case 'verifikasi':
                    if ($this->tanggalMulai === '') {
                        $this->tanggalMulai = now()->toDateString();
                    }
                    WalimuridService::verifikasiRelasi(
                        $aktor,
                        $relasi->idwalimurid,
                        $this->tanggalMulai,
                        $this->alasanPeriode !== '' ? $this->alasanPeriode : null,
                        $this->catatan !== '' ? $this->catatan : null
                    );
                    $this->dispatch('close-aksi-modal');
                    break;

                case 'tolak':
                    if (trim($this->alasan) === '') {
                        $this->addError('alasan', 'Alasan penolakan wajib diisi.');

                        return;
                    }
                    WalimuridService::tolakRelasi($aktor, $relasi->idwalimurid, $this->alasan);
                    $this->dispatch('close-aksi-modal');
                    break;

                case 'putus':
                    if (trim($this->alasan) === '') {
                        $this->addError('alasan', 'Alasan pemutusan wajib diisi.');

                        return;
                    }
                    WalimuridService::putusRelasiLangsung($aktor, $relasi->idwalimurid, $this->alasan);
                    $this->dispatch('close-aksi-modal');
                    break;

                default:
                    abort(404);
            }
        } catch (\InvalidArgumentException|\DomainException $e) {
            $this->addError('aksi', $e->getMessage());

            return;
        }

        $this->reset('targetId', 'aksi', 'tanggalMulai', 'alasanPeriode', 'catatan', 'alasan');
        $this->resetValidation();
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->tanggalMulai = now()->toDateString();
        $this->alasanPeriode = '';
        $this->catatan = '';
        $this->alasan = '';
        $this->resetValidation();
    }
}
