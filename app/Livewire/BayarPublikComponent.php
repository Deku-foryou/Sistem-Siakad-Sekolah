<?php

namespace App\Livewire;

use App\Models\PaymentAttempt;
use App\Services\Payments\MidtransGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\View\View;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Pembayaran publik (pihak ketiga) — tanpa akun, tanpa data akademik siswa.
 *
 * Halaman ini hanya menampilkan: jenis tagihan, periode, dan nominal.
 * Tidak ada nama siswa, NIS, kelas, atau data akademik lain.
 * Tautan berlaku sebagai capability (pihak yang memegang tautan boleh membayar).
 */
class BayarPublikComponent extends Component
{
    use LivewireAlert;

    public string $token = '';

    public function mount(string $token): void
    {
        abort_if(strlen($token) < 32 || ! ctype_alnum($token), 404);

        $attempt = PaymentAttempt::query()->where('public_token', $token)->first();

        abort_if($attempt === null, 404);

        $this->token = $token;
    }

    public function bayar(): void
    {
        $attempt = $this->attempt();
        abort_if($attempt === null, 404);

        abort_unless(in_array($attempt->status, ['created', 'pending'], true), 403);

        $gateway = app(MidtransGateway::class);

        if (! $gateway->isConfigured()) {
            $this->alert('error', 'Layanan pembayaran online belum tersedia. Hubungi admin sekolah.');

            return;
        }

        $this->dispatch('snap-pay', token: $attempt->snap_token, clientKey: $gateway->clientKey());
    }

    public function periksaStatus(): void
    {
        $attempt = $this->attempt();
        abort_if($attempt === null, 404);

        $gateway = app(MidtransGateway::class);

        if (! $gateway->isConfigured()) {
            $this->alert('error', 'Layanan pembayaran online belum tersedia.');

            return;
        }

        $state = PaymentService::sinkronStatusAttempt($attempt, $gateway);

        match ($state) {
            'success' => $this->alert('success', 'Pembayaran berhasil. Terima kasih.'),
            'pending' => $this->alert('info', 'Pembayaran masih menunggu (pending). Selesaikan sesuai instruksi pembayaran Anda.'),
            'failed' => $this->alert('error', 'Pembayaran gagal.'),
            'expired' => $this->alert('warning', 'Pembayaran kedaluwarsa. Minta tautan pembayaran baru dari pihak sekolah.'),
            'duplicate' => $this->alert('info', 'Pembayaran sudah terkonfirmasi sebelumnya.'),
            default => $this->alert('error', 'Status transaksi belum dapat dipastikan. Coba lagi beberapa saat.'),
        };
    }

    private function attempt(): ?PaymentAttempt
    {
        return PaymentAttempt::query()->where('public_token', $this->token)->first();
    }

    public function render(): View
    {
        $attempt = $this->attempt();
        abort_if($attempt === null, 404);

        $gateway = app(MidtransGateway::class);

        $kedaluwarsa = $attempt->expired_pada !== null && now()->greaterThan($attempt->expired_pada);

        return view('livewire.bayar-publik-component', [
            'attempt' => $attempt,
            'gatewayConfigured' => $gateway->isConfigured(),
            'snapJsUrl' => $gateway->snapJsUrl(),
            'clientKey' => $gateway->clientKey(),
            'kedaluwarsa' => $kedaluwarsa,
        ])->layout('layout.bayarpublik');
    }
}
