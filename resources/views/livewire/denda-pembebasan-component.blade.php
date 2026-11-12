<div>
@section('title', 'Pembebasan Denda')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-hand-holding-dollar me-2 text-success" aria-hidden="true"></i>Pembebasan Denda</h1>
            <p class="sa-sub">Pengajuan pembebasan denda oleh admin; persetujuan/penolakan oleh superadmin. Denda yang dibebaskan tidak dicatat sebagai pembayaran; tagihan dikurangi sebesar denda yang dibebaskan.</p>
        </div>
        <span class="sa-chip"><i class="fa-solid fa-shield-halved me-1"></i>Operasional</span>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tampilan</label>
                <select class="form-select" wire:model.live="tab">
                    <option value="aktif">Tagihan dengan Denda Berlaku</option>
                    <option value="pengajuan">Pengajuan Pembebasan</option>
                </select>
            </div>
            @if($tab === 'pengajuan')
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua</option>
                    <option value="pending">Menunggu</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="terproses">Terproses</option>
                    <option value="gagal">Gagal</option>
                </select>
            </div>
            @endif
            <div class="col-md-4">
                <label class="form-label">Cari siswa (nama/NIS)</label>
                <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Nama atau NIS">
            </div>
        </div>
    </div>

    @if($tab === 'aktif')
    <div class="sa-card">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i>Tagihan dengan Denda Berlaku</h2><span class="sa-count">{{ $dendaAktif->total() }} tagihan</span></div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Siswa</th><th>Tagihan</th><th class="text-end">Total (incl. denda)</th><th class="text-end">Denda</th><th class="text-end">Terpakai</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($dendaAktif as $t)
                        @php($sisaDenda = \App\Services\Keuangan\DendaPembebasanService::nominalDapatDibebaskan($t->denda, $t))
                        <tr>
                            <td>{{ $t->siswa?->nama }}<br><small class="text-muted">NIS {{ $t->siswa?->nis }}</small></td>
                            <td>{{ strtoupper($t->jenis) }} {{ $t->periode }}</td>
                            <td class="text-end">Rp {{ number_format($t->total_tagihan, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($t->denda?->nominal_denda ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format(\App\Services\Payments\PaymentService::terpakaiTagihan($t), 0, ',', '.') }}</td>
                            <td class="text-center text-nowrap">
                                @if($sisaDenda > 0)
                                    <button class="btn btn-sm btn-outline-success" wire:click="pilihTagihan({{ $t->idtagihan }}); $dispatch('show-modal-ajukan')" type="button">Ajukan Pembebasan</button>
                                @else
                                    <small class="text-muted">Denda tertutupi</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Tidak ada tagihan dengan denda berlaku</p><p class="sa-empty-text">Denda hanya berlaku untuk jenis ujian dan hanya jatuh tempo yang dilewati (sesuai konfigurasi denda).</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($dendaAktif instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<div class="sa-card-foot">{{ $dendaAktif->links() }}</div>@endif
    </div>
    @else
    <div class="sa-card">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Pengajuan Pembebasan Denda</h2><span class="sa-count">{{ $pengajuan->total() }} pengajuan</span></div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Siswa</th><th>Tagihan</th><th class="text-end">Denda</th><th>Status</th><th>Pengaju</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($pengajuan as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $p->tagihan?->siswa?->nama }}<br><small class="text-muted">NIS {{ $p->tagihan?->siswa?->nis }}</small></td>
                            <td>{{ strtoupper($p->tagihan?->jenis ?? '-') }} {{ $p->tagihan?->periode }}</td>
                            <td class="text-end">Rp {{ number_format($p->nominal_denda, 0, ',', '.') }}</td>
                            <td>
                                @if($p->status === 'pending') <span class="sa-badge sa-netral">Menunggu</span>
                                @elseif($p->status === 'terproses') <span class="sa-badge sa-hadir">Terproses</span>
                                @elseif($p->status === 'ditolak') <span class="sa-badge sa-alpha">Ditolak</span>
                                @else <span class="sa-badge sa-alpha">Gagal</span>
                                @endif
                            </td>
                            <td>{{ $p->pengaju?->name ?? '-' }}</td>
                            <td class="text-center text-nowrap">
                                @if(auth()->user()?->level === 'superadmin' && $p->status === 'pending')
                                    <button class="btn btn-sm btn-success" wire:click="setujui({{ $p->id }})" type="button"><i class="fa-solid fa-check"></i> Setujui</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="pilihTolak({{ $p->id }}); $dispatch('show-modal-tolak')" type="button"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                @endif
                            </td>
                        </tr>
                        @if($p->status === 'terproses' && $p->catatan_proses)
                            <tr><td colspan="8" class="small text-muted py-1">Berlaku: {{ $p->catatan_proses }}</td></tr>
                        @elseif($p->status === 'ditolak' && $p->alasan_ditolak)
                            <tr><td colspan="8" class="small text-muted py-1">Ditolak: {{ $p->alasan_ditolak }}</td></tr>
                        @endif
                    @empty
                        <tr><td colspan="8"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Belum ada pengajuan</p><p class="sa-empty-text">Admin mengajukan lewat tab "Tagihan dengan Denda Berlaku".</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pengajuan instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<div class="sa-card-foot">{{ $pengajuan->links() }}</div>@endif
    </div>
    @endif
</div>

{{-- Modal Ajukan Pembebasan Denda --}}
<div wire:ignore.self class="modal fade" id="modalAjukan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Ajukan Pembebasan Denda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @if($selected)
                    <p class="small text-muted">Tagihan: <strong>{{ strtoupper($selected->jenis) }} {{ $selected->periode }}</strong> — Siswa: {{ $selected->siswa?->nama }}</p>
                    <p class="small">Total tagihan: <strong>Rp {{ number_format($selected->total_tagihan, 0, ',', '.') }}</strong><br>Denda: <strong>Rp {{ number_format($selected->denda?->nominal_denda ?? 0, 0, ',', '.') }}</strong><br>Denda dapat dibebaskan (belum tertutupi): <strong>Rp {{ number_format(\App\Services\Keuangan\DendaPembebasanService::nominalDapatDibebaskan($selected->denda, $selected), 0, ',', '.') }}</strong></p>
                @endif
                <label class="form-label">Alasan pembebasan (wajib)</label>
                <textarea class="form-control" rows="3" wire:model="alasanPengajuan" placeholder="Contoh: Siswa sakit berkepanjangan; disetujui wali & guru; kebijakan sekolah..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" wire:click="ajukan()" wire:loading.attr="disabled">Ajukan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tolak --}}
<div wire:ignore.self class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-danger"><i class="fa-solid fa-xmark me-2"></i>Tolak Pembebasan Denda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Alasan penolakan (wajib)</label>
                <textarea class="form-control" rows="3" wire:model="alasanPenolakan"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger" wire:click="tolak()">Tolak</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('show-modal-ajukan', function () { $('#modalAjukan').modal('show'); });
    window.addEventListener('close-modal-ajukan', function () { $('#modalAjukan').modal('hide'); });
    window.addEventListener('show-modal-tolak', function () { $('#modalTolak').modal('show'); });
    window.addEventListener('close-modal-tolak', function () { $('#modalTolak').modal('hide'); });
</script>
</div>
