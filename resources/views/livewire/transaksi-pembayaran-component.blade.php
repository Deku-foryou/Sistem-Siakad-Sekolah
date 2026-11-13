@section('title', 'Transaksi Pembayaran')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <style>
        /* ---------- Ringkasan kartu ---------- */
        .tp-stats { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .6rem; margin: .75rem 0 1rem; }
        .tp-stats-saldo { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .tp-stat { display: flex; align-items: center; gap: .6rem; background: #fff; border: 1px solid var(--sa-border); border-radius: var(--sa-radius); box-shadow: var(--sa-shadow); padding: .65rem .85rem; min-width: 0; }
        .tp-stat-icon { width: 2.4rem; height: 2.4rem; border-radius: .6rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
        .tp-stat-total .tp-stat-icon { background: #eef7f3; color: var(--sa-green-dark); }
        .tp-stat-ok .tp-stat-icon { background: #e7f6ec; color: #0b6e4f; }
        .tp-stat-wait .tp-stat-icon { background: #fdf0d5; color: #92400e; }
        .tp-stat-fail .tp-stat-icon { background: #fdecec; color: #b91c1c; }
        .tp-stat-value { min-width: 0; }
        .tp-stat-value .tp-stat-label { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--sa-muted); line-height: 1.2; }
        .tp-stat-value .tp-stat-number { display: block; font-size: 1.15rem; font-weight: 800; color: var(--sa-ink); font-variant-numeric: tabular-nums; line-height: 1.3; margin-top: .15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tp-stat-value .tp-stat-note { display: block; font-size: .68rem; color: var(--sa-muted); margin-top: .05rem; }
        .tp-stat-wide { grid-column: span 2; background: linear-gradient(135deg, #0e7d6a 0%, #0b5f52 100%); border-color: transparent; }
        .tp-stat-wide .tp-stat-icon { background: rgba(255,255,255,.18); color: #fff; }
        .tp-stat-wide .tp-stat-label, .tp-stat-wide .tp-stat-note { color: rgba(255,255,255,.82); }
        .tp-stat-wide .tp-stat-number { color: #fff; }

        /* ---------- Legend attempt vs ledger ---------- */
        .tp-head-left { max-width: 64rem; min-width: 0; }
        .tp-legend { display: flex; flex-wrap: wrap; gap: .4rem 1.25rem; align-items: center; margin: .4rem 0 .75rem; font-size: .8rem; color: var(--sa-muted); }
        .tp-legend strong { color: var(--sa-ink); }
        .tp-dot { display: inline-flex; align-items: center; gap: .35rem; }
        .tp-dot::before { content: ""; width: .55rem; height: .55rem; border-radius: 999px; background: #d97706; }
        .tp-dot-ledger::before { background: var(--sa-green); }

        /* ---------- Filter ---------- */
        .tp-filter .form-label { font-size: .75rem; font-weight: 700; color: var(--sa-ink); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .03em; }
        .tp-reset { align-self: end; height: calc(1.5em + .9rem + 2px); }
        .tp-reset:hover { background: #fff; border-color: var(--sa-green); color: var(--sa-green-dark); }

        /* ---------- Tabel ---------- */
        .tp-table thead th { padding: .6rem .75rem; }
        .tp-table tbody td { padding: .6rem .75rem; }
        .tp-orderid { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem; color: var(--sa-ink); background: #f3f4f6; padding: .15rem .4rem; border-radius: .3rem; border: 1px solid var(--sa-border); white-space: nowrap; }
        .tp-mono-sub { font-size: .72rem; color: var(--sa-muted); }
        .tp-nominal { font-weight: 700; font-variant-numeric: tabular-nums; color: var(--sa-ink); white-space: nowrap; }
        .tp-metode { font-size: .78rem; font-weight: 600; color: var(--sa-muted); letter-spacing: .03em; white-space: nowrap; }
        .tp-status { min-width: 6.5rem; }
        .tp-ledger { margin-left: .35rem; }

        /* ---------- Empty state ---------- */
        .tp-empty { padding: 2.5rem 1rem 2.25rem; text-align: center; }
        .tp-empty-icon { width: 4.25rem; height: 4.25rem; border-radius: 999px; background: var(--sa-green-faint); color: var(--sa-green-dark); display: inline-flex; align-items: center; justify-content: center; font-size: 1.7rem; margin-bottom: .75rem; }
        .tp-empty-title { font-size: 1.05rem; font-weight: 700; color: var(--sa-ink); margin-bottom: .3rem; }
        .tp-empty-text { color: var(--sa-muted); font-size: .86rem; max-width: 32rem; margin: 0 auto .75rem; }
        .tp-empty-hint { display: inline-flex; flex-wrap: wrap; gap: .35rem; justify-content: center; }

        /* ---------- Responsif ---------- */
        @media (max-width: 1199.98px) {
            .tp-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .tp-stat-wide { grid-column: span 3; }
            .tp-stats-saldo { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .tp-head-left { max-width: 52rem; }
        }
        @media (max-width: 575.98px) {
            .tp-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tp-stat { padding: .55rem .65rem; gap: .45rem; }
            .tp-stat-icon { width: 2rem; height: 2rem; font-size: .9rem; }
            .tp-stat-wide { grid-column: span 2; }
            .tp-stats-saldo { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tp-table { font-size: .8rem; }
            .tp-table thead th { padding: .5rem .6rem; font-size: .66rem; }
            .tp-table tbody td { padding: .5rem .6rem; }
            .tp-hide-sm { display: none; }
        }
    </style>

    <div class="sa-head">
        <div class="tp-head-left">
            <h1 class="sa-title"><i class="fa-solid fa-receipt me-2 text-success" aria-hidden="true"></i>Transaksi Pembayaran</h1>
            <p class="sa-sub">Pemantauan percobaan pembayaran (payment attempt) dan pembayaran yang sudah terverifikasi (ledger). Halaman ini bersifat <strong>read-only</strong> — verifikasi hanya melalui webhook/API Midtrans atau verifikasi manual.</p>
            <div class="tp-legend">
                <span class="tp-dot"><span><strong>Percobaan</strong> — pembayaran dimulai, <em>belum</em> berarti uang diterima</span></span>
                <span class="tp-dot tp-dot-ledger"><span><strong>Ledger</strong> — uang masuk tercatat setelah webhook/verifikasi</span></span>
            </div>
        </div>
        <div class="text-end mt-1 mt-lg-0" style="margin-left:auto;">
            <span class="sa-chip"><i class="fa-solid fa-shield-halved me-1" aria-hidden="true"></i>Operasional — Superadmin &amp; Admin</span>
        </div>
    </div>

    @if ($tab === 'saldo')
    <div class="tp-stats tp-stats-saldo">
        <div class="tp-stat tp-stat-total">
            <span class="tp-stat-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Penggunaan Saldo</span>
                <span class="tp-stat-number">{{ number_format((int) ($stats['total'] ?? 0)) }}</span>
                <span class="tp-stat-note">transaksi tercatat</span>
            </span>
        </div>
        <div class="tp-stat">
            <span class="tp-stat-icon" style="background:#e8efff;color:#1d4ed8"><i class="fa-solid fa-rupiah-sign" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Nominal Terpakai</span>
                <span class="tp-stat-number">Rp {{ number_format((int) ($stats['nominal'] ?? 0), 0, ',', '.') }}</span>
                <span class="tp-stat-note">sesuai filter aktif</span>
            </span>
        </div>
        <div class="tp-stat">
            <span class="tp-stat-icon" style="background:#f3f0ff;color:#6d28d9"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Siswa Unik</span>
                <span class="tp-stat-number">{{ number_format((int) ($stats['siswa'] ?? 0)) }}</span>
                <span class="tp-stat-note">menggunakan saldo</span>
            </span>
        </div>
    </div>
    @else
    <div class="tp-stats">
        <div class="tp-stat tp-stat-total">
            <span class="tp-stat-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Total Percobaan</span>
                <span class="tp-stat-number">{{ number_format((int) ($stats['total'] ?? 0)) }}</span>
                <span class="tp-stat-note">payment attempt</span>
            </span>
        </div>
        <div class="tp-stat tp-stat-ok">
            <span class="tp-stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Berhasil</span>
                <span class="tp-stat-number">{{ number_format((int) ($stats['success'] ?? 0)) }}</span>
                <span class="tp-stat-note">pembayaran terverifikasi</span>
            </span>
        </div>
        <div class="tp-stat tp-stat-wait">
            <span class="tp-stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Menunggu</span>
                <span class="tp-stat-number">{{ number_format((int) ($stats['pending'] ?? 0)) }}</span>
                <span class="tp-stat-note">dibuat + pending</span>
            </span>
        </div>
        <div class="tp-stat tp-stat-fail">
            <span class="tp-stat-icon"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Gagal / Expired</span>
                <span class="tp-stat-number">{{ number_format((int) ($stats['failed'] ?? 0)) }}</span>
                <span class="tp-stat-note">gagal + expired</span>
            </span>
        </div>
        <div class="tp-stat tp-stat-wide">
            <span class="tp-stat-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
            <span class="tp-stat-value">
                <span class="tp-stat-label">Total Terverifikasi</span>
                <span class="tp-stat-number">Rp {{ number_format((int) ($stats['verified'] ?? 0), 0, ',', '.') }}</span>
                <span class="tp-stat-note">ledger masuk (seluruh waktu)</span>
            </span>
        </div>
    </div>
    @endif

    <div class="sa-card sa-filter tp-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-12">
                <label for="tp-tab" class="form-label">Jenis Tampilan</label>
                <select id="tp-tab" class="form-select" wire:model.live="tab">
                    <option value="attempt">Percobaan &amp; Ledger Online</option>
                    <option value="saldo">Penggunaan Saldo Siswa</option>
                </select>
            </div>
            <div class="col-md-3 col-12">
                <label for="tp-search" class="form-label">Cari</label>
                <input id="tp-search" type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="order ID, nama siswa, periode...">
            </div>
            <div class="col-md-2 col-6">
                <label for="tp-filter-periode" class="form-label">Periode</label>
                <select id="tp-filter-periode" class="form-select" wire:model.live="filterPeriode">
                    <option value="">Semua periode</option>
                    @foreach ($periodeOptions as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label for="tp-filter-status" class="form-label">Status</label>
                <select id="tp-filter-status" class="form-select" wire:model.live="filterStatus" @disabled($tab === 'saldo')>
                    <option value="">Semua</option>
                    <option value="created">Dibuat</option>
                    <option value="pending">Menunggu</option>
                    <option value="success">Berhasil</option>
                    <option value="failed">Gagal</option>
                    <option value="expired">Kedaluwarsa</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2 col-12">
                <button type="button" class="btn btn-outline-secondary tp-reset w-100" wire:click="resetFilters" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset filter"><i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i>Reset Filter</button>
            </div>
        </div>
    </div>

    @if ($tab === 'saldo')
    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-wallet" aria-hidden="true"></i>Riwayat Penggunaan Saldo Siswa</h2>
            <span class="sa-count">{{ $saldoRows->total() }} penggunaan</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table tp-table mb-0">
                <thead>
                    <tr><th>Tanggal</th><th>Siswa</th><th>Tagihan</th><th class="text-end">Nominal</th><th class="tp-hide-sm">Pembayar</th><th class="tp-hide-sm">Dicatat Oleh</th><th class="tp-hide-sm">Verifikator</th></tr>
                </thead>
                <tbody>
                    @forelse ($saldoRows as $row)
                        <tr>
                            <td><div class="fw-semibold">{{ $row->diverifikasi_pada?->format('d/m/Y') ?? $row->created_at?->format('d/m/Y') }}</div><div class="tp-mono-sub">{{ $row->diverifikasi_pada?->format('H:i') ?? $row->created_at?->format('H:i') }}</div></td>
                            <td><div class="fw-semibold">{{ $row->siswa?->nama ?? '-' }}</div><div class="tp-mono-sub">NIS {{ $row->siswa?->nis ?? '-' }}</div></td>
                            <td><div class="fw-semibold">{{ $row->tagihan?->jenis === 'spp' ? 'SPP' : ucfirst($row->tagihan?->jenis ?? '-') }}</div><div class="tp-mono-sub">{{ $row->tagihan?->periode }}</div></td>
                            <td class="text-end tp-nominal">Rp {{ number_format((int) $row->nominal_dibayar, 0, ',', '.') }}</td>
                            <td class="tp-hide-sm">{{ $row->nama_pembayar ?? $row->payer_nama ?? '-' }}</td>
                            <td class="tp-hide-sm">{{ $row->inputer?->name ?? '-' }}</td>
                            <td class="tp-hide-sm">{{ $row->verifikator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="tp-empty">
                                    <div class="tp-empty-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></div>
                                    <p class="tp-empty-title">Belum ada penggunaan saldo</p>
                                    <p class="tp-empty-text">Penggunaan saldo siswa/wali akan tampil di sini setelah siswa/wali memakai saldo untuk membayar tagihan. Saldo terpakai dicatat sebagai pembayaran dengan provider <code>saldo</code>, bukan uang masuk sekolah.</p>
                                    <div class="tp-empty-hint"><span class="sa-chip"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Nominal terpakai di atas hanya mengurangi sisa tagihan — bukan penerimaan baru</span></div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">{{ $saldoRows->links() }}</div>
    </div>
    @else
    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Daftar Transaksi</h2>
            <span class="sa-count">{{ $rows->total() }} transaksi</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table tp-table mb-0">
                <thead>
                    <tr><th>Order ID</th><th>Siswa</th><th>Tagihan</th><th class="text-end">Nominal</th><th class="tp-hide-sm">Metode</th><th class="tp-hide-sm">Pembayar</th><th>Status</th><th>Dibuat</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $ledger = $row->pembayaran->first(); @endphp
                        <tr>
                            <td><code class="tp-orderid">{{ $row->order_id }}</code></td>
                            <td><div class="fw-semibold">{{ $row->siswa?->nama ?? '-' }}</div><div class="tp-mono-sub">NIS {{ $row->siswa?->nis ?? '-' }}</div></td>
                            <td><div class="fw-semibold">{{ $row->tagihan?->jenis === 'spp' ? 'SPP' : ucfirst($row->tagihan?->jenis ?? '-') }}</div><div class="tp-mono-sub">{{ $row->tagihan?->periode }}</div></td>
                            <td class="text-end tp-nominal">Rp {{ number_format((int) $row->nominal, 0, ',', '.') }}</td>
                            <td class="tp-metode tp-hide-sm">{{ $row->metode ? str_replace('_', ' ', strtoupper($row->metode)) : '-' }}</td>
                            <td class="tp-hide-sm">{{ $row->payer_nama ?: ($row->payer_jenis === 'siswa' ? 'Siswa' : ucfirst($row->payer_jenis)) }}</td>
                            <td class="tp-status">
                                @if ($row->status === 'success') <span class="sa-badge sa-hadir"><i class="fa-solid fa-check me-1" aria-hidden="true"></i>Berhasil</span>
                                @elseif ($row->status === 'pending') <span class="sa-badge sa-sakit"><i class="fa-regular fa-clock me-1" aria-hidden="true"></i>Menunggu</span>
                                @elseif ($row->status === 'created') <span class="sa-badge sa-netral"><i class="fa-regular fa-hourglass-half me-1" aria-hidden="true"></i>Dibuat</span>
                                @elseif ($row->status === 'failed') <span class="sa-badge sa-alpha"><i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>Gagal</span>
                                @elseif ($row->status === 'expired') <span class="sa-badge sa-alpha"><i class="fa-solid fa-hourglass-end me-1" aria-hidden="true"></i>Kedaluwarsa</span>
                                @else <span class="sa-badge sa-alpha">Dibatalkan</span> @endif
                                @if ($ledger) <span class="sa-badge sa-netral tp-ledger" title="Uang masuk tercatat di ledger"><i class="fa-solid fa-book me-1" aria-hidden="true"></i>Ledger</span> @endif
                            </td>
                            <td><div class="fw-semibold">{{ $row->created_at?->format('d/m/Y') }}</div><div class="tp-mono-sub">{{ $row->created_at?->format('H:i') }}</div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="tp-empty">
                                    <div class="tp-empty-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></div>
                                    <p class="tp-empty-title">Belum ada transaksi</p>
                                    <p class="tp-empty-text">Transaksi akan tampil di sini setelah siswa/wali memulai pembayaran dari halaman <em>Bayar Tagihan</em>. Percobaan transaksi (payment attempt) <strong>belum tentu masuk ke ledger</strong> — pembayaran hanya dianggap berhasil dan dicatat sebagai uang masuk setelah webhook Midtrans mengonfirmasi status terverifikasi atau verifikasi manual diselesaikan.</p>
                                    <div class="tp-empty-hint">
                                        <span class="sa-chip"><i class="fa-solid fa-arrow-right me-1" aria-hidden="true"></i>Percobaan → <code>payment_attempt</code></span>
                                        <span class="sa-chip"><i class="fa-solid fa-arrow-right me-1" aria-hidden="true"></i>Terverifikasi → <code>pembayaran</code> (ledger)</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">{{ $rows->links() }}</div>
    </div>
    @endif
</div>
