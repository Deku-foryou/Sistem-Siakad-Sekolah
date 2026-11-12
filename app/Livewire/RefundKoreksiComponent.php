<?php

namespace App\Livewire;

use App\Models\KoreksiPengajuan;
use App\Models\Pembayaran;
use App\Models\RefundPengajuan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Keuangan\KoreksiKeuanganService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Refund & Koreksi Keuangan — admin mengajukan, superadmin menyetujui/menolak + memproses.
 *
 * - Guru/siswa/wali tidak boleh mengakses (backend abort).
 * - Histori asli tidak bisa diubah; efek finansial hanya via reversal alokasi.
 * - Admin tidak dapat menyetujui pengajuannya sendiri (backend).
 */
class RefundKoreksiComponent extends Component
{
    use LivewireAlert, WithPagination;

    public string $tab = 'refund';

    public string $filterStatus = '';

    public string $search = '';

    public string $filterJenis = '';

    // Form refund
    public int $selectedPembayaranId = 0;

    public string $nominalRefund = '';

    public string $alasanRefund = '';

    public string $metodeRefund = 'transfer_manual';

    public string $referensiRefund = '';

    // Form koreksi
    public string $jenisKoreksi = 'nominal';

    public string $alasanKoreksi = '';

    public string $nominalUsulan = '';

    public string $metodeUsulan = 'tunai';

    public string $namaPembayarUsulan = '';

    public string $tanggalPembayaranUsulan = '';

    public string $tagihanPenggantiId = '';

    // Proses
    public int $selectedPengajuanId = 0;

    public string $alasanPenolakan = '';

    public string $referensiProses = '';

    public string $catatanProses = '';

    public string $tolakTipe = 'refund';

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    private function assertAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    private function assertSuperadmin(): void
    {
        abort_unless(Auth::user()?->level === 'superadmin', 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterJenis(): void
    {
        $this->resetPage();
    }

    public function pilihPembayaran(int $id): void
    {
        $this->assertAdmin();
        $this->selectedPembayaranId = $id;
        $this->nominalRefund = '';
        $this->alasanRefund = '';
        $this->metodeRefund = 'transfer_manual';
        $this->referensiRefund = '';
        $this->jenisKoreksi = 'nominal';
        $this->alasanKoreksi = '';
        $this->nominalUsulan = '';
        $this->metodeUsulan = 'tunai';
        $this->namaPembayarUsulan = '';
        $this->tanggalPembayaranUsulan = '';
        $this->tagihanPenggantiId = '';
    }

    public function ajukanRefund(): void
    {
        $this->assertAdmin();

        $pembayaran = Pembayaran::findOrFail($this->selectedPembayaranId);

        try {
            KoreksiKeuanganService::ajukanRefund(
                $pembayaran,
                (int) $this->nominalRefund,
                $this->alasanRefund,
                $this->metodeRefund,
                $this->referensiRefund ?: null,
                Auth::user(),
            );
            $this->alert('success', 'Pengajuan refund terkirim. Menunggu persetujuan superadmin.');
            $this->resetForm();
            $this->dispatch('close-modal-refund');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function ajukanKoreksi(): void
    {
        $this->assertAdmin();

        $pembayaran = Pembayaran::findOrFail($this->selectedPembayaranId);

        try {
            KoreksiKeuanganService::ajukanKoreksi(
                $pembayaran,
                $this->jenisKoreksi,
                $this->alasanKoreksi,
                [
                    'nominal_usulan' => $this->nominalUsulan !== '' ? (int) $this->nominalUsulan : null,
                    'metode_usulan' => $this->metodeUsulan ?: null,
                    'nama_pembayar_usulan' => $this->namaPembayarUsulan ?: null,
                    'tanggal_pembayaran_usulan' => $this->tanggalPembayaranUsulan ?: null,
                    'tagihan_pengganti_idtagihan' => $this->tagihanPenggantiId !== '' ? (int) $this->tagihanPenggantiId : null,
                ],
                Auth::user(),
            );
            $this->alert('success', 'Pengajuan koreksi terkirim. Menunggu persetujuan superadmin.');
            $this->resetForm();
            $this->dispatch('close-modal-koreksi');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function setujuiRefund(int $id): void
    {
        $this->assertSuperadmin();
        try {
            $r = RefundPengajuan::findOrFail($id);
            KoreksiKeuanganService::setujuiRefund($r, Auth::user());
            $this->alert('success', 'Refund disetujui. Proses refund dilakukan superadmin.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function tolakRefund(int $id): void
    {
        $this->assertSuperadmin();
        if (trim($this->alasanPenolakan) === '') {
            $this->alert('error', 'Alasan penolakan wajib diisi.');

            return;
        }
        try {
            $r = RefundPengajuan::findOrFail($id);
            KoreksiKeuanganService::tolakRefund($r, $this->alasanPenolakan, Auth::user());
            $this->alert('success', 'Refund ditolak.');
            $this->alasanPenolakan = '';
            $this->dispatch('close-modal-tolak');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function prosesRefund(int $id): void
    {
        $this->assertSuperadmin();
        if (trim($this->referensiProses) === '') {
            $this->alert('error', 'Referensi proses wajib diisi (nomor transfer/nama penerima/tanggal, dll).');

            return;
        }
        try {
            $r = RefundPengajuan::findOrFail($id);
            KoreksiKeuanganService::prosesRefundManual($r, $this->referensiProses, $this->catatanProses, Auth::user());
            $this->alert('success', 'Refund terproses; alokasi reversal tercatat; ledger asli tidak berubah.');
            $this->referensiProses = '';
            $this->catatanProses = '';
            $this->dispatch('close-modal-proses');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    // Tombol modal memakai aksi tanpa argumen: nilai $selectedPengajuanId
    // dibaca dari state komponen saat klik, bukan dari interpolated render awal.
    public function prosesRefundTerpilih(): void
    {
        $this->prosesRefund($this->selectedPengajuanId);
    }

    // Aksi tombol Tolak/Proses di baris pengajuan: hindari rantai $set/$dispatch
    // inline (evaluasi Alpine tidak menjamin $set terdefinisi di segmen berikutnya).
    public function pilihTolakRefund(int $id): void
    {
        $this->selectedPengajuanId = $id;
        $this->tolakTipe = 'refund';
        $this->dispatch('show-modal-tolak');
    }

    public function pilihTolakKoreksi(int $id): void
    {
        $this->selectedPengajuanId = $id;
        $this->tolakTipe = 'koreksi';
        $this->dispatch('show-modal-tolak');
    }

    public function pilihProsesRefund(int $id): void
    {
        $this->selectedPengajuanId = $id;
        $this->dispatch('show-modal-proses');
    }

    public function setujuiKoreksi(int $id): void
    {
        $this->assertSuperadmin();
        try {
            $k = KoreksiPengajuan::findOrFail($id);
            KoreksiKeuanganService::setujuiKoreksi($k, Auth::user());
            $this->alert('success', 'Koreksi diterapkan: reversal + replacement alokasi tercatat; histori asli tidak diubah.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function tolakKoreksi(int $id): void
    {
        $this->assertSuperadmin();
        if (trim($this->alasanPenolakan) === '') {
            $this->alert('error', 'Alasan penolakan wajib diisi.');

            return;
        }
        try {
            $k = KoreksiPengajuan::findOrFail($id);
            KoreksiKeuanganService::tolakKoreksi($k, $this->alasanPenolakan, Auth::user());
            $this->alert('success', 'Koreksi ditolak.');
            $this->alasanPenolakan = '';
            $this->dispatch('close-modal-tolak');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function generatePdfRefund(int $id): void
    {
        $this->assertSuperadmin();
        try {
            $refund = RefundPengajuan::findOrFail($id);
            $dokumen = \App\Services\Keuangan\RefundDokumenService::generateUlang($refund, Auth::user());
            if ($dokumen->wasRecentlyCreated) {
                $this->alert('success', 'PDF dokumen refund dibuat: '.$dokumen->nomor_dokumen);
            } else {
                $this->alert('success', 'PDF dokumen refund sudah ada: '.$dokumen->nomor_dokumen);
            }
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function tolakRefundTerpilih(): void
    {
        $this->tolakRefund($this->selectedPengajuanId);
    }

    public function tolakKoreksiTerpilih(): void
    {
        $this->tolakKoreksi($this->selectedPengajuanId);
    }

    public function resetForm(): void
    {
        $this->assertAdmin();
        $this->reset(['nominalRefund', 'alasanRefund', 'metodeRefund', 'referensiRefund',
            'jenisKoreksi', 'alasanKoreksi', 'nominalUsulan', 'metodeUsulan', 'namaPembayarUsulan',
            'tanggalPembayaranUsulan', 'tagihanPenggantiId', 'alasanPenolakan', 'referensiProses', 'catatanProses']);
        $this->metodeRefund = 'transfer_manual';
        $this->jenisKoreksi = 'nominal';
        $this->metodeUsulan = 'tunai';
    }

    public function render(): View
    {
        $this->assertAdmin();

        $pembayaranRows = Pembayaran::query()
            ->with(['siswa', 'tagihan'])
            ->where('status', 'success')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('siswa', fn ($sq) => $sq->where('nama', 'like', '%'.$this->search.'%')->orWhere('nis', 'like', '%'.$this->search.'%'));
            })
            ->latest('idpembayaran')
            ->paginate(10);

        $refundQuery = RefundPengajuan::query()->with(['pembayaran.siswa', 'pengaju', 'pemberiPersetujuan', 'pemroses', 'dokumen']);
        $rekuestIQuery = KoreksiPengajuan::query()->with(['pembayaran.siswa', 'pengaju', 'pemberiPersetujuan', 'pemroses', 'tagihanPengganti']);

        if ($this->tab === 'refund') {
            if ($this->filterStatus !== '') {
                $refundQuery->where('status', $this->filterStatus);
            }
            $refundRows = $refundQuery->latest('id')->paginate(15);
            $koreksiRows = collect();
        } elseif ($this->tab === 'koreksi') {
            $refundRows = collect();
            if ($this->filterStatus !== '') {
                $rekuestIQuery->where('status', $this->filterStatus);
            }
            if ($this->filterJenis !== '') {
                $rekuestIQuery->where('jenis', $this->filterJenis);
            }
            $koreksiRows = $rekuestIQuery->latest('id')->paginate(15);
        } else {
            $refundRows = collect();
            $koreksiRows = collect();
        }

        $tagihanOptions = collect();
        if ($this->selectedPembayaranId) {
            $p = Pembayaran::with('siswa')->find($this->selectedPembayaranId);
            if ($p) {
                $tagihanOptions = Tagihan::where('siswa_idsiswa', $p->siswa_idsiswa)
                    ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])
                    ->orderByDesc('tanggal_terbit')
                    ->get();
            }
        }

        return view('livewire.refund-koreksi-component', [
            'pembayaranRows' => $pembayaranRows,
            'refundRows' => $refundRows,
            'koreksiRows' => $koreksiRows,
            'tagihanOptions' => $tagihanOptions,
        ]);
    }
}
