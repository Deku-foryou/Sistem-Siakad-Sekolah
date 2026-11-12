@section('title', 'Bayar Tagihan')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @if ($gatewayConfigured)
        <script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
    @endif
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('snap-pay', ({ token, clientKey }) => {
                if (typeof window.snap === 'undefined') {
                    alert('Skrip pembayaran belum dimuat. Muat ulang halaman lalu coba lagi.');
                    return;
                }
                window.snap.pay(token, {
                    onSuccess: function () { window.location.reload(); },
                    onPending: function () { window.location.reload(); },
                    onError: function (result) { alert('Pembayaran gagal: ' + (result && result.status_message ? result.status_message : 'silakan coba lagi.')); },
                    onClose: function () { /* dibiarkan; siswa dapat menekan "Periksa Status" */ }
                });
            });
        });
    </script>

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-credit-card me-2 text-success" aria-hidden="true"></i>Bayar Tagihan</h1>
            <p class="sa-sub">Pembayaran SPP &amp; tagihan lainnya secara online melalui Midtrans Snap.</p>
        </div>
        @if (auth()->user()?->level === 'wali')
            <span class="sa-chip"><i class="fa-solid fa-user-shield" aria-hidden="true"></i>Wali</span>
        @else
            <span class="sa-chip"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Siswa</span>
        @endif
    </div>

    @if (! $gatewayConfigured)
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Pembayaran online belum dikonfigurasi</div>
            <div class="mt-1">Layanan pembayaran Midtrans Snap belum tersedia di lingkungan ini (credential sandbox belum diatur). Silakan lakukan pembayaran melalui tata usaha atau minta tautan pembayaran dari pihak sekolah.</div>
        </div>
    @endif

    @if (! $siswa)
        <div class="sa-card">
            <div class="sa-empty">
                <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                <p class="sa-empty-title">Belum ada siswa yang terhubung</p>
                <p class="sa-empty-text">Akun wali ini belum memiliki relasi verified + aktif dengan siswa. Minta verifikasi hubungan melalui halaman <em>Relasi Wali</em>, lalu coba lagi.</p>
            </div>
        </div>
    @else
    @if ($siswas->count() > 1)
        <div class="sa-card sa-filter mb-3">
            <label for="bt-pilih-siswa" class="form-label">Pilih siswa</label>
            <select id="bt-pilih-siswa" class="form-select" wire:model.live="siswaId" wire:change="selectSiswa($event.target.value)">
                @foreach ($siswas as $s)
                    <option value="{{ $s->idsiswa }}" @selected((string) $s->idsiswa === $siswaId)>{{ $s->nama }} (NIS {{ $s->nis ?? '-' }})</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i>Tagihan yang Bisa Dibayar</h2>
            <span class="sa-count">{{ $tagihanRows->count() }} tagihan</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Jenis</th><th>Periode</th><th class="text-end">Total</th><th class="text-end">Terbayar</th><th class="text-end">Sisa</th><th>Jatuh Tempo</th><th class="text-center" style="width:120px;">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($tagihanRows as $row)
                        <tr>
                            <td>{{ $row['tagihan']->jenis === 'spp' ? 'SPP' : ucfirst($row['tagihan']->jenis) }}@if ($row['tagihan']->nama) &middot; {{ $row['tagihan']->nama }}@endif</td>
                            <td>{{ $row['tagihan']->periode }}</td>
                            <td class="text-end">Rp {{ number_format($row['tagihan']->total_tagihan, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['terpakai'], 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($row['sisa'], 0, ',', '.') }}</td>
                            <td>{{ $row['tagihan']->tanggal_jatuh_tempo?->format('d/m/Y') }}</td>
                            <td class="text-center text-nowrap">
                                @if ($row['sisa'] > 0)
                                    <button class="btn btn-sm btn-success" wire:click="bayar({{ $row['tagihan']->idtagihan }})" wire:loading.attr="disabled" aria-label="Bayar tagihan">
                                        <i class="fa-solid fa-credit-card me-1" aria-hidden="true"></i>Bayar
                                    </button>
                                @else
                                    <span class="sa-badge sa-hadir">Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="sa-empty"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p class="sa-empty-title">Tidak ada tagihan yang perlu dibayar</p><p class="sa-empty-text">Semua tagihan Anda sudah lunas atau belum ada tagihan yang terbit.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-wallet" aria-hidden="true"></i>Saldo Siswa</h2>
            <span class="sa-count">Tersedia: Rp {{ number_format($saldoTersedia, 0, ',', '.') }}</span>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="bt-saldo-jumlah" class="form-label">Nominal penggunaan saldo (Rp)</label>
                <input id="bt-saldo-jumlah" type="number" class="form-control" wire:model="jumlahSaldo" min="1" max="{{ $saldoTersedia }}" @disabled($saldoTersedia < 1) placeholder="0">
            </div>
            <div class="col-md-4">
                <button class="btn btn-success" wire:click="gunakanSaldo" wire:loading.attr="disabled" @disabled($saldoTersedia < 1) type="button">
                    <i class="fa-solid fa-hand-holding-dollar me-1" aria-hidden="true"></i>Gunakan Saldo
                </button>
                <p class="small text-body-secondary mt-1 mb-0">Dialokasikan otomatis: jatuh tempo terdekat lebih dahulu; jika sama, tanggal terbit tertua; pokok sebelum denda.</p>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2 justify-content-md-end">
                    <span class="sa-chip"><i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Penggunaan tercatat otomatis</span>
                </div>
            </div>
        </div>
        @if ($riwayatSaldo->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table sa-table mb-0">
                    <thead>
                        <tr><th>Tanggal</th><th>Tagihan</th><th class="text-end">Nominal</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayatSaldo as $rs)
                            <tr>
                                <td>{{ $rs->dibayar_pada?->format('d/m/Y H:i') ?? $rs->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $rs->tagihan?->jenis === 'spp' ? 'SPP' : ucfirst($rs->tagihan?->jenis ?? '-') }} {{ $rs->tagihan?->periode }}</td>
                                <td class="text-end">Rp {{ number_format($rs->nominal_dibayar, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="sa-card mb-3">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>Riwayat Percobaan &amp; Pembayaran</h2>
            <span class="sa-count">10 terakhir</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Order ID</th><th>Tagihan</th><th class="text-end">Nominal</th><th>Metode</th><th>Status</th><th>Dibuat</th><th class="text-center" style="width:190px;">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td><code class="small">{{ $attempt->order_id }}</code></td>
                            <td>{{ $attempt->tagihan?->jenis === 'spp' ? 'SPP' : ucfirst($attempt->tagihan?->jenis ?? '-') }} {{ $attempt->tagihan?->periode }}</td>
                            <td class="text-end">Rp {{ number_format($attempt->nominal, 0, ',', '.') }}</td>
                            <td>{{ $attempt->metode ? str_replace('_', ' ', strtoupper($attempt->metode)) : '-' }}</td>
                            <td>
                                @if ($attempt->status === 'success') <span class="sa-badge sa-hadir">Berhasil</span>
                                @elseif ($attempt->status === 'pending') <span class="sa-badge sa-netral">Menunggu</span>
                                @elseif ($attempt->status === 'created') <span class="sa-badge sa-netral">Dibuat</span>
                                @elseif ($attempt->status === 'failed') <span class="sa-badge sa-alpha">Gagal</span>
                                @elseif ($attempt->status === 'expired') <span class="sa-badge sa-alpha">Kedaluwarsa</span>
                                @else <span class="sa-badge sa-alpha">Dibatalkan</span> @endif
                            </td>
                            <td>{{ $attempt->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-center text-nowrap">
                                @if (in_array($attempt->status, ['created', 'pending'], true))
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="periksaStatus({{ $attempt->idpayment_attempt }})" aria-label="Periksa status transaksi"><i class="fa-solid fa-rotate" aria-hidden="true"></i><span class="d-none d-xl-inline ms-1">Cek Status</span></button>
                                    <button class="btn btn-sm btn-outline-primary" wire:click="tampilkanTautan({{ $attempt->idpayment_attempt }})" aria-label="Salin tautan pembayaran pihak ketiga"><i class="fa-solid fa-link" aria-hidden="true"></i><span class="d-none d-xl-inline ms-1">Tautan</span></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="sa-empty"><i class="fa-solid fa-inbox" aria-hidden="true"></i><p class="sa-empty-title">Belum ada percobaan pembayaran</p><p class="sa-empty-text">Riwayat transaksi online akan tampil di sini.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(isset($pembayaranManual) && $pembayaranManual->isNotEmpty())
    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i>Pembayaran Manual</h2>
            <span class="sa-count">{{ $pembayaranManual->count() }} terakhir</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Tanggal</th><th>Tagihan</th><th class="text-end">Nominal</th><th>Metode</th><th>Status</th><th class="text-center">Dokumen</th></tr>
                </thead>
                <tbody>
                    @foreach ($pembayaranManual as $pm)
                        <tr>
                            <td>{{ $pm->tanggal_pembayaran?->format('d/m/Y') ?? $pm->created_at?->format('d/m/Y') }}</td>
                            <td>{{ $pm->tagihan?->jenis === 'spp' ? 'SPP' : ucfirst($pm->tagihan?->jenis ?? '-') }} {{ $pm->tagihan?->periode }}</td>
                            <td class="text-end">Rp {{ number_format($pm->nominal_dibayar, 0, ',', '.') }}</td>
                            <td>{{ strtoupper($pm->metode) }}</td>
                            <td>
                                @if($pm->status === 'success') <span class="sa-badge sa-hadir">Disetujui</span>
                                @elseif($pm->status === 'pending' || $pm->status === 'review') <span class="sa-badge sa-netral">Menunggu</span>
                                @elseif($pm->status === 'failed') <span class="sa-badge sa-alpha">Ditolak</span>
                                @else <span class="sa-badge">{{ $pm->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($pm->bukti_path)
                                    <a class="btn btn-sm btn-outline-secondary me-1" href="{{ route('pembayaran.bukti', $pm->idpembayaran) }}" target="_blank"><i class="fa-solid fa-file-lines"></i> Bukti</a>
                                @endif
                                @if($pm->status === 'success' && $pm->kuitansi)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('pembayaran.kuitansi', $pm->idpembayaran) }}" target="_blank"><i class="fa-solid fa-receipt"></i> Lihat Kuitansi</a>
                                @elseif($pm->status === 'failed')
                                    <small class="text-muted">-</small>
                                @else
                                    <small class="text-muted">Menunggu</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(isset($refundDokumen) && $refundDokumen->isNotEmpty())
    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Dokumen Refund</h2>
            <span class="sa-count">{{ $refundDokumen->count() }} dokumen</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Tanggal</th><th>Refund</th><th>Tagihan</th><th class="text-end">Nominal</th><th class="text-center">Dokumen</th></tr>
                </thead>
                <tbody>
                    @foreach ($refundDokumen as $rd)
                        <tr>
                            <td>{{ $rd->created_at?->format('d/m/Y') }}</td>
                            <td>Refund #{{ $rd->refund_pengajuan_id }}<br><small class="text-muted">{{ $rd->nomor_dokumen }}</small></td>
                            <td>{{ $rd->refund?->pembayaran?->tagihan ? strtoupper($rd->refund->pembayaran->tagihan->jenis).' '.($rd->refund->pembayaran->tagihan->periode ?? '') : '-' }}</td>
                            <td class="text-end">Rp {{ number_format($rd->refund?->nominal_refund ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('refund.dokumen', $rd->id) }}" target="_blank"><i class="fa-solid fa-file-pdf"></i> Unduh PDF</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif

<div wire:ignore.self class="modal fade" id="modalTautan" tabindex="-1" aria-labelledby="modalTautanLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTautanLabel"><i class="fa-solid fa-link me-2" aria-hidden="true"></i>Tautan Pembayaran Pihak Ketiga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">Bagikan tautan ini kepada pihak yang ingin membantu membayarkan tagihan. Tautan hanya berlaku untuk satu transaksi ini dan tidak menampilkan data akademik siswa.</p>
                <label for="bt-tautan" class="form-label">Tautan pembayaran</label>
                <div class="input-group">
                    <input id="bt-tautan" type="text" class="form-control" readonly value="{{ $tautanPublik }}" onclick="this.select()">
                    <button type="button" class="btn btn-outline-primary" onclick="window.salinTautan()" aria-label="Salin tautan"><i class="fa-solid fa-copy" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.salinTautan = function () {
        var input = document.getElementById('bt-tautan');
        if (!input) { return; }
        input.select();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value);
        } else {
            document.execCommand('copy');
        }
    };
    window.addEventListener('show-tautan-modal', function () { $('#modalTautan').modal('show'); });
</script>
</div>