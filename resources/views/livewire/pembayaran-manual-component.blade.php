<div>
@section('title', 'Pembayaran Manual')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-hand-holding-dollar me-2 text-success" aria-hidden="true"></i>Pembayaran Manual</h1>
            <p class="sa-sub">Pencatatan pembayaran tunai/transfer/manual, verifikasi, dan kuitansi.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" wire:click="$dispatch('show-catat-modal')"><i class="fa-solid fa-plus me-1"></i>Catat Pembayaran</button>
            @if($pendingCount > 0)
                <span class="sa-chip"><i class="fa-solid fa-clock me-1"></i>{{ $pendingCount }} menunggu verifikasi</span>
            @endif
        </div>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Nama/NIS siswa">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tab</label>
                <select class="form-select" wire:model.live="tab">
                    <option value="pending">Menunggu Verifikasi</option>
                    <option value="history">Riwayat</option>
                </select>
            </div>
            @if($tab === 'history')
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua</option>
                    <option value="success">Disetujui</option>
                    <option value="failed">Ditolak</option>
                </select>
            </div>
            @endif
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-list" aria-hidden="true"></i>{{ $tab === 'pending' ? 'Pembayaran Menunggu Verifikasi' : 'Riwayat Pembayaran Manual' }}</h2>
            <span class="sa-count">{{ $rows->total() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Tagihan</th>
                        <th class="text-end">Nominal</th>
                        <th>Metode</th>
                        <th>Pembayar</th>
                        <th>Status</th>
                        <th>Dicatat Oleh</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->tanggal_pembayaran?->format('d/m/Y') ?? $row->created_at?->format('d/m/Y') }}</td>
                            <td>{{ $row->siswa?->nama }}<br><small class="text-muted">NIS {{ $row->siswa?->nis }}</small></td>
                            <td>{{ strtoupper($row->tagihan?->jenis) }} {{ $row->tagihan?->periode }}</td>
                            <td class="text-end">Rp {{ number_format($row->nominal_dibayar, 0, ',', '.') }}</td>
                            <td>{{ strtoupper($row->metode) }}</td>
                            <td>{{ $row->nama_pembayar ?? $row->payer_nama ?? '-' }}</td>
                            <td>
                                @if($row->status === 'success') <span class="sa-badge sa-hadir">Disetujui</span>
                                @elseif($row->status === 'pending' || $row->status === 'review') <span class="sa-badge sa-netral">Menunggu</span>
                                @elseif($row->status === 'failed') <span class="sa-badge sa-alpha">Ditolak</span>
                                @else <span class="sa-badge">{{ $row->status }}</span>
                                @endif
                            </td>
                            <td>{{ $row->inputer?->name ?? '-' }}</td>
                            <td class="text-center text-nowrap">
                                @if($row->status === 'pending' || $row->status === 'review')
                                    @if($row->bukti_path)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('pembayaran.bukti', $row->idpembayaran) }}" target="_blank"><i class="fa-solid fa-file-image"></i></a>
                                    @endif
                                    <button class="btn btn-sm btn-success" wire:click="setujui({{ $row->idpembayaran }})" wire:confirm="Setujui pembayaran ini?"><i class="fa-solid fa-check"></i> Setujui</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="pilihVerifikasi({{ $row->idpembayaran }})" data-bs-toggle="modal" data-bs-target="#modalTolak"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                @elseif($row->status === 'success')
                                    @if($row->kuitansi)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('pembayaran.kuitansi', $row->idpembayaran) }}" target="_blank"><i class="fa-solid fa-receipt"></i> Kuitansi</a>
                                    @endif
                                @elseif($row->status === 'failed')
                                    <span class="text-muted small">{{ Str::limit($row->alasan_penolakan ?? $row->alasan_gagal, 40) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Tidak ada data</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">{{ $rows->links() }}</div>
    </div>
</div>

{{-- Modal Catat --}}
<div wire:ignore.self class="modal fade" id="modalCatat" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-plus me-2"></i>Catat Pembayaran Manual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Siswa</label>
                        <select class="form-select" wire:model.live="siswaId" wire:change="pilihSiswa">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach($siswas as $s)
                                <option value="{{ $s->idsiswa }}">{{ $s->nama }} (NIS {{ $s->nis }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tagihan</label>
                        <select class="form-select" wire:model="tagihanId">
                            <option value="">-- Pilih Tagihan --</option>
                            @foreach($tagihanOptions as $t)
                                <option value="{{ $t->idtagihan }}">{{ strtoupper($t->jenis) }} {{ $t->periode }} - Sisa: Rp {{ number_format(\App\Services\Payments\PaymentService::sisaTagihan($t), 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nominal (Rp)</label>
                        <input type="number" class="form-control" wire:model="nominal" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Metode</label>
                        <select class="form-select" wire:model="metode">
                            <option value="tunai">Tunai</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="va">VA</option>
                            <option value="qris">QRIS</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Pembayaran</label>
                        <input type="date" class="form-control" wire:model="tanggal_pembayaran">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Pihak Pembayar</label>
                        <input type="text" class="form-control" wire:model="nama_pembayar">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Referensi/No. Transaksi (opsional)</label>
                        <input type="text" class="form-control" wire:model="referensi">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Bukti Pembayaran (opsional, jpg/png/pdf, max 2MB)</label>
                        <input type="file" class="form-control" wire:model="bukti">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea class="form-control" rows="2" wire:model="catatan"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetForm">Batal</button>
                <button class="btn btn-primary" wire:click="catat" wire:loading.attr="disabled">Simpan (Status: Menunggu Verifikasi)</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tolak --}}
<div wire:ignore.self class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-xmark me-2 text-danger"></i>Tolak Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Alasan Penolakan (wajib)</label>
                <textarea class="form-control" rows="3" wire:model="alasanPenolakan"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger" wire:click="tolak({{ $selectedPembayaranId }})">Tolak Pembayaran</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('show-catat-modal', function(){ $('#modalCatat').modal('show'); });
    window.addEventListener('close-modal-catat', function(){ $('#modalCatat').modal('hide'); });
    window.addEventListener('close-modal-verifikasi', function(){ });
    window.addEventListener('close-modal-tolak', function(){ $('#modalTolak').modal('hide'); });
</script>
</div>
