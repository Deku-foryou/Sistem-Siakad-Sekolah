@section('title', 'Pembayaran Tagihan')
<div class="bp-card">
    <h1 class="h4 fw-bold mb-1">Pembayaran Tagihan Sekolah</h1>
    <p class="text-body-secondary small mb-3">Anda membayar tagihan atas nama sekolah. Tidak diperlukan akun untuk membayar melalui tautan ini.</p>

    <div class="bp-row"><span class="text-body-secondary">Jenis</span><span class="fw-semibold">{{ $attempt->tagihan?->jenis === 'spp' ? 'SPP' : ucfirst($attempt->tagihan?->jenis ?? 'Tagihan') }}</span></div>
    <div class="bp-row"><span class="text-body-secondary">Periode</span><span class="fw-semibold">{{ $attempt->tagihan?->periode ?? '-' }}</span></div>
    <div class="bp-row"><span class="text-body-secondary">Nomor Transaksi</span><span class="fw-semibold small">{{ $attempt->order_id }}</span></div>
    <div class="bp-row"><span class="text-body-secondary">Status</span>
        <span class="fw-semibold">
            @if ($attempt->status === 'success') <span class="text-success"><i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Lunas</span>
            @elseif ($attempt->status === 'pending') <span class="text-warning-emphasis"><i class="fa-solid fa-hourglass-half me-1" aria-hidden="true"></i>Menunggu pembayaran</span>
            @elseif ($attempt->status === 'failed') <span class="text-danger"><i class="fa-solid fa-circle-xmark me-1" aria-hidden="true"></i>Gagal</span>
            @elseif ($attempt->status === 'expired' || $kedaluwarsa) <span class="text-danger"><i class="fa-solid fa-clock me-1" aria-hidden="true"></i>Kedaluwarsa</span>
            @else <span class="text-body-secondary">Dibuat</span> @endif
        </span>
    </div>
    <div class="bp-row align-items-center"><span class="text-body-secondary">Nominal</span><span class="bp-nominal">Rp {{ number_format((int) $attempt->nominal, 0, ',', '.') }}</span></div>

    @if ($attempt->status === 'success')
        <div class="alert alert-success mt-3 mb-0" role="status"><i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>Pembayaran sudah berhasil diterima. Terima kasih.</div>
    @else
        @if (! $gatewayConfigured)
            <div class="alert alert-warning mt-3 mb-0" role="alert"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Layanan pembayaran online belum tersedia. Hubungi admin sekolah.</div>
        @elseif ($attempt->expired_pada && now()->greaterThan($attempt->expired_pada))
            <div class="alert alert-danger mt-3 mb-0" role="alert"><i class="fa-solid fa-clock me-2" aria-hidden="true"></i>Transaksi ini sudah kedaluwarsa. Minta tautan pembayaran baru dari pihak sekolah.</div>
        @else
            <button class="btn btn-success btn-lg w-100 mt-3" wire:click="bayar" wire:loading.attr="disabled" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Bayar">
                <i class="fa-solid fa-credit-card me-2" aria-hidden="true"></i>Bayar Sekarang
            </button>
            <button class="btn btn-outline-secondary w-100 mt-2" wire:click="periksaStatus" wire:loading.attr="disabled" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Bayar">
                <i class="fa-solid fa-rotate me-2" aria-hidden="true"></i>Saya sudah membayar — Periksa Status
            </button>
        @endif
    @endif
</div>

@if ($gatewayConfigured && in_array($attempt->status, ['created', 'pending'], true))
    <script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('snap-pay', function ({ token, clientKey }) {
                if (typeof window.snap === 'undefined') {
                    alert('Skrip pembayaran belum dimuat. Muat ulang halaman lalu coba lagi.');
                    return;
                }
                window.snap.pay(token, {
                    onSuccess: function () { window.location.reload(); },
                    onPending: function () { window.location.reload(); },
                    onError: function (result) { alert('Pembayaran gagal: ' + (result && result.status_message ? result.status_message : 'silakan coba lagi.')); },
                    onClose: function () {}
                });
            });
        });
    </script>
@endif
