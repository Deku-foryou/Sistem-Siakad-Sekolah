<?php

namespace App\Livewire;

use App\Models\PaymentAttempt;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Payments\MidtransGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Bayar Tagihan — portal siswa & wali.
 *
 * - Siswa: hanya tagihan miliknya sendiri.
 * - Wali: hanya tagihan milik siswa dengan relasi verified + aktif (tanggal_berhenti NULL).
 * - IDOR-safe: setiap aksi memverifikasi kepemilikan server-side, tidak hanya menyembunyikan UI.
 * - Guru/level lain: tidak boleh mengakses halaman ini (backend abort).
 */
class BayarTagihanComponent extends Component
{
    use LivewireAlert;

    public string $siswaId = '';

    /** Tautan publik yang sedang ditampilkan (untuk dibagikan pihak ketiga). */
    public string $tautanPublik = '';

    public string $jumlahSaldo = '';

    public array $tagihanSaldoDipilih = [];

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['siswa', 'wali'], true), 403);

        // Pihak wali: butuh relasi verified + aktif minimal satu; siswa: siswa terhubung sendiri.
        $daftar = $this->resolvedSiswas();

        if ($daftar->isNotEmpty()) {
            $this->siswaId = (string) $daftar->first()->idsiswa;
        }
    }

    /** Daftar siswa yang boleh dilihat/dibayar oleh aktor saat ini (IDOR-safe). */
    private function resolvedSiswas()
    {
        $aktor = Auth::user();

        if ($aktor->level === 'siswa') {
            $siswa = $aktor->siswa()->first();

            return collect($siswa ? [$siswa] : []);
        }

        // Wali: relasi verified + aktif saja
        return Siswa::query()
            ->where('status', 'aktif')
            ->whereHas('walimurid', function ($q) use ($aktor) {
                $q->where('user_iduser', $aktor->id)
                    ->where('status_hubungan', 'verified')
                    ->whereNull('tanggal_berhenti');
            })
            ->orderBy('nama')
            ->get();
    }

    private function resolveSiswaAktif(?string $siswaIdRaw): ?Siswa
    {
        if ($siswaIdRaw === null || $siswaIdRaw === '') {
            return null;
        }

        $id = (int) $siswaIdRaw;

        $siswa = $this->resolvedSiswas()->firstWhere('idsiswa', $id);

        return $siswa;
    }

    private function guardSiswaSilakan(string $siswaIdRaw): Siswa
    {
        $siswa = $this->resolveSiswaAktif($siswaIdRaw);
        abort_unless($siswa !== null, 403);

        return $siswa;
    }

    public function selectSiswa(string $siswaId): void
    {
        abort_unless(in_array(Auth::user()?->level, ['siswa', 'wali'], true), 403);

        $this->guardSiswaSilakan($siswaId);
        $this->siswaId = (string) (int) $siswaId;
        $this->reset(['tautanPublik']);
    }

    /**
     * Buat payment attempt (Snap) untuk satu tagihan milik siswa terpilih.
     */
    public function bayar(int $tagihanId): void
    {
        abort_unless(in_array(Auth::user()?->level, ['siswa', 'wali'], true), 403);

        $siswa = $this->guardSiswaSilakan($this->siswaId);
        abort_unless($siswa->status === 'aktif', 403);

        /** @var Tagihan|null $tagihan */
        $tagihan = Tagihan::query()->find($tagihanId);
        abort_unless($tagihan !== null && (int) $tagihan->siswa_idsiswa === (int) $siswa->idsiswa, 403);

        $gateway = app(MidtransGateway::class);

        if (! $gateway->isConfigured()) {
            $this->alert('error', 'Layanan pembayaran online belum dikonfigurasi. Hubungi admin sekolah.');

            return;
        }

        try {
            $attempt = PaymentService::buatAttempt(
                $tagihan,
                Auth::user()->level === 'wali' ? PaymentService::PAYER_WALI : PaymentService::PAYER_SISWA,
                Auth::user(),
                null,
                $gateway,
            );
        } catch (\Throwable $e) {
            report($e);
            $this->alert('error', 'Gagal membuat transaksi pembayaran: '.$e->getMessage());

            return;
        }

        $this->dispatch('snap-pay', token: $attempt->snap_token, clientKey: $gateway->clientKey());
    }

    /**
     * Periksa status satu attempt ke server-side (bukan dari browser).
     */
    public function periksaStatus(int $attemptId): void
    {
        abort_unless(in_array(Auth::user()?->level, ['siswa', 'wali'], true), 403);

        $siswa = $this->guardSiswaSilakan($this->siswaId);

        $attempt = PaymentAttempt::query()
            ->where('idpayment_attempt', $attemptId)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->first();

        abort_unless($attempt !== null, 403);

        $gateway = app(MidtransGateway::class);

        if (! $gateway->isConfigured()) {
            $this->alert('error', 'Layanan pembayaran online belum dikonfigurasi.');

            return;
        }

        $state = PaymentService::sinkronStatusAttempt($attempt, $gateway);

        match ($state) {
            'success' => $this->alert('success', 'Pembayaran berhasil. Tagihan telah teralokasi.'),
            'pending' => $this->alert('info', 'Pembayaran masih menunggu (pending). Selesaikan sesuai instruksi pembayaran.'),
            'failed' => $this->alert('error', 'Pembayaran gagal.'),
            'expired' => $this->alert('warning', 'Pembayaran kedaluwarsa.'),
            'duplicate' => $this->alert('info', 'Pembayaran sudah terkonfirmasi sebelumnya.'),
            default => $this->alert('error', 'Status transaksi belum dapat dipastikan. Coba lagi beberapa saat.'),
        };
    }

    /**
     * Tampilkan tautan publik (pihak ketiga) untuk satu attempt.
     */
    public function tampilkanTautan(int $attemptId): void
    {
        abort_unless(in_array(Auth::user()?->level, ['siswa', 'wali'], true), 403);

        $siswa = $this->guardSiswaSilakan($this->siswaId);

        $attempt = PaymentAttempt::query()
            ->where('idpayment_attempt', $attemptId)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->whereIn('status', ['created', 'pending'])
            ->first();

        abort_unless($attempt !== null && $attempt->public_token, 403);

        $this->tautanPublik = url('/bayar-publik/'.$attempt->public_token);
        $this->dispatch('show-tautan-modal');
    }

    /**
     * Gunakan saldo siswa terpilih untuk membayar tagihan (alokasi otomatis).
     * Otorisasi: siswa milik sendiri / wali relasi verified + aktif. Nominal dihitung server-side.
     */
    public function gunakanSaldo(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['siswa', 'wali'], true), 403);

        $siswa = $this->guardSiswaSilakan($this->siswaId);
        abort_unless($siswa->status === 'aktif', 403);

        $jumlah = (int) $this->jumlahSaldo;
        if ($jumlah < 1) {
            $this->alert('error', 'Jumlah penggunaan saldo tidak valid.');

            return;
        }

        $saldoTersedia = PaymentService::hitungSaldoTersedia($siswa->idsiswa);
        if ($jumlah > $saldoTersedia) {
            $this->alert('error', 'Saldo tidak mencukupi (tersedia Rp '.number_format($saldoTersedia, 0, ',', '.').').');

            return;
        }

        try {
            PaymentService::gunakanSaldo($siswa, $jumlah, Auth::user());
            $this->alert('success', 'Saldo berhasil digunakan untuk membayar tagihan.');
            $this->reset(['jumlahSaldo', 'tagihanSaldoDipilih']);
        } catch (\Throwable $e) {
            report($e);
            $this->alert('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        // Wali tanpa relasi verified + aktif: render halaman dengan pesan, bukan 403.
        $siswa = $this->resolveSiswaAktif($this->siswaId);
        $gateway = app(MidtransGateway::class);

        $siswas = $this->resolvedSiswas();

        $tagihanRows = collect();
        $attempts = collect();
        $pembayaranManual = collect();
        $refundDokumen = collect();

        $saldoTersedia = 0;
        $riwayatSaldo = collect();

        if ($siswa !== null) {
            $tagihanRows = Tagihan::query()
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])
                ->orderBy('tanggal_jatuh_tempo')
                ->get()
                ->map(function (Tagihan $tagihan) {
                    $terpakai = PaymentService::terpakaiTagihan($tagihan);
                    $sisa = max(0, (int) $tagihan->total_tagihan - $terpakai);

                    return [
                        'tagihan' => $tagihan,
                        'terpakai' => $terpakai,
                        'sisa' => $sisa,
                    ];
                });

            $attempts = PaymentAttempt::query()
                ->with('tagihan')
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->latest('idpayment_attempt')
                ->limit(10)
                ->get();

            $pembayaranManual = \App\Models\Pembayaran::query()
                ->with(['tagihan', 'kuitansi'])
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('provider', 'manual')
                ->latest('idpembayaran')
                ->limit(10)
                ->get();

            $saldoTersedia = PaymentService::hitungSaldoTersedia($siswa->idsiswa);
            $riwayatSaldo = \App\Models\Pembayaran::query()
                ->with('tagihan')
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('provider', 'saldo')
                ->latest('idpembayaran')
                ->limit(10)
                ->get();

            // Dokumen refund (PDF) milik siswa ini
            $refundDokumen = \App\Models\RefundDokumen::query()
                ->with(['refund.pembayaran.tagihan'])
                ->whereHas('refund.pembayaran', fn ($q) => $q->where('siswa_idsiswa', $siswa->idsiswa))
                ->latest('id')
                ->get();
        }

        return view('livewire.bayar-tagihan-component', [
            'siswa' => $siswa,
            'siswas' => $siswas,
            'tagihanRows' => $tagihanRows,
            'attempts' => $attempts,
            'pembayaranManual' => $pembayaranManual,
            'saldoTersedia' => $saldoTersedia,
            'riwayatSaldo' => $riwayatSaldo,
            'refundDokumen' => $refundDokumen,
            'gatewayConfigured' => $gateway->isConfigured(),
            'snapJsUrl' => $gateway->snapJsUrl(),
            'clientKey' => $gateway->clientKey(),
        ]);
    }
}
