<div>
@section('title', 'Refund & Koreksi')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-money-bill-transfer me-2 text-success" aria-hidden="true"></i>Refund &amp; Koreksi Keuangan</h1>
            <p class="sa-sub">Pengajuan refund/koreksi oleh admin; persetujuan dan pemrosesan oleh superadmin. Histori asli tidak diubah — efek keuangan via reversal alokasi.</p>
        </div>
        <span class="sa-chip"><i class="fa-solid fa-shield-halved me-1"></i>Operasional</span>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tampilan</label>
                <select class="form-select" wire:model.live="tab">
                    <option value="refund">Pengajuan Refund</option>
                    <option value="koreksi">Pengajuan Koreksi</option>
                    <option value="riwayat">Riwayat</option>
                </select>
            </div>
            @if($tab === 'refund' || $tab === 'koreksi')
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua</option>
                    <option value="pending">Menunggu</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="terproses">Terproses</option>
                    <option value="gagal">Gagal</option>
                </select>
            </div>
            @if($tab === 'koreksi')
            <div class="col-md-2">
                <label class="form-label">Jenis</label>
                <select class="form-select" wire:model.live="filterJenis">
                    <option value="">Semua</option>
                    <option value="nominal">Nominal</option>
                    <option value="alokasi">Alokasi</option>
                    <option value="pencatatan_manual">Pencatatan Manual</option>
                    <option value="hubungan_tagihan">Hubungan Tagihan</option>
                </select>
            </div>
            @endif
            @endif
            <div class="col-md-4">
                <label class="form-label">Cari pembayaran (nama/NIS siswa)</label>
                <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Nama atau NIS">
            </div>
        </div>
    </div>

    <div class="sa-card mb-3">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-receipt" aria-hidden="true"></i>Pilih Pembayaran untuk Diajukan</h2></div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Siswa</th><th>Tagihan</th><th class="text-end">Nominal</th><th>Metode</th><th class="text-end">Dapat Refund</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($pembayaranRows as $p)
                        @php($refundable = \App\Services\Keuangan\KoreksiKeuanganService::refundable($p))
                        <tr>
                            <td>{{ $p->siswa?->nama }}<br><small class="text-muted">NIS {{ $p->siswa?->nis }}</small></td>
                            <td>{{ $p->tagihan ? strtoupper($p->tagihan->jenis).' '.$p->tagihan->periode : '-' }}</td>
                            <td class="text-end">Rp {{ number_format((int) $p->nominal_dibayar, 0, ',', '.') }}</td>
                            <td>{{ strtoupper($p->metode) }}</td>
                            <td class="text-end">Rp {{ number_format((int) $refundable, 0, ',', '.') }}</td>
                            <td class="text-center text-nowrap">
                                @if($refundable > 0)
                                    <button class="btn btn-sm btn-outline-success" wire:click="pilihPembayaran({{ $p->idpembayaran }}); $dispatch('show-modal-refund')" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Bayar">Refund</button>
                                    <button class="btn btn-sm btn-outline-primary" wire:click="pilihPembayaran({{ $p->idpembayaran }}); $dispatch('show-modal-koreksi')" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Bayar">Koreksi</button>
                                @else
                                    <small class="text-muted">Habis direfund</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Tidak ada pembayaran</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">{{ $pembayaranRows->links() }}</div>
    </div>

    @if($tab === 'refund')
    <div class="sa-card">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Pengajuan Refund</h2><span class="sa-count">{{ $refundRows->total() ?? 0 }} pengajuan</span></div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Siswa</th><th class="text-end">Nominal</th><th>Metode</th><th>Status</th><th>Pengaju</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($refundRows as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $r->pembayaran?->siswa?->nama }}</td>
                            <td class="text-end">Rp {{ number_format((int) $r->nominal_refund, 0, ',', '.') }}</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $r->metode)) }}</td>
                            <td>
                                @if($r->status === 'pending') <span class="sa-badge sa-netral">Menunggu</span>
                                @elseif($r->status === 'disetujui') <span class="sa-badge sa-netral">Disetujui</span>
                                @elseif($r->status === 'terproses') <span class="sa-badge sa-hadir">Terproses {{ $r->nomor_dokumen }}</span>
                                    @if($r->dokumen)
                                        <a class="btn btn-sm btn-outline-success ms-1" href="{{ route('refund.dokumen', $r->dokumen->id) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor PDF"><i class="fa-solid fa-file-pdf"></i> Unduh PDF</a>
                                    @elseif(auth()->user()?->level === 'superadmin')
                                        <button class="btn btn-sm btn-outline-warning ms-1" wire:click="generatePdfRefund({{ $r->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor PDF"><i class="fa-solid fa-file-circle-plus"></i> Generate PDF</button>
                                    @endif
                                @elseif($r->status === 'ditolak') <span class="sa-badge sa-alpha">Ditolak</span>
                                @else <span class="sa-badge sa-alpha">Gagal</span>
                                @endif
                            </td>
                            <td>{{ $r->pengaju?->name ?? '-' }}</td>
                            <td class="text-center text-nowrap">
                                @if(auth()->user()?->level === 'superadmin')
                                    @if($r->status === 'pending')
                                        <button class="btn btn-sm btn-success" wire:click="setujuiRefund({{ $r->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Setujui"><i class="fa-solid fa-check"></i> Setujui</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="pilihTolakRefund({{ $r->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Tolak"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                    @elseif($r->status === 'disetujui')
                                        <button class="btn btn-sm btn-primary" wire:click="pilihProsesRefund({{ $r->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Proses"><i class="fa-solid fa-hand-holding-dollar"></i> Proses</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Tidak ada pengajuan refund</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($refundRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<div class="sa-card-foot">{{ $refundRows->links() }}</div>@endif
    </div>
    @elseif($tab === 'koreksi')
    <div class="sa-card">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Pengajuan Koreksi</h2><span class="sa-count">{{ $koreksiRows->total() ?? 0 }} pengajuan</span></div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Siswa</th><th>Jenis</th><th class="text-end">Nominal Usulan</th><th>Status</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($koreksiRows as $k)
                        <tr>
                            <td>{{ $k->id }}</td>
                            <td>{{ $k->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $k->pembayaran?->siswa?->nama }}</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $k->jenis)) }}</td>
                            <td class="text-end">{{ $k->nominal_usulan !== null ? 'Rp '.number_format((int) $k->nominal_usulan, 0, ',', '.') : '-' }}</td>
                            <td>
                                @if($k->status === 'pending') <span class="sa-badge sa-netral">Menunggu</span>
                                @elseif($k->status === 'disetujui') <span class="sa-badge sa-netral">Disetujui</span>
                                @elseif($k->status === 'terproses') <span class="sa-badge sa-hadir">Terproses</span>
                                @elseif($k->status === 'ditolak') <span class="sa-badge sa-alpha">Ditolak</span>
                                @else <span class="sa-badge sa-alpha">Gagal</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                @if(auth()->user()?->level === 'superadmin')
                                    @if($k->status === 'pending')
                                        <button class="btn btn-sm btn-success" wire:click="setujuiKoreksi({{ $k->id }}); $dispatch('close-modal-koreksi')" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Setujui"><i class="fa-solid fa-check"></i> Setujui &amp; Terapkan</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="pilihTolakKoreksi({{ $k->id }})" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Tolak"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="sa-empty"><i class="fa-solid fa-inbox"></i><p class="sa-empty-title">Tidak ada pengajuan koreksi</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($koreksiRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<div class="sa-card-foot">{{ $koreksiRows->links() }}</div>@endif
    </div>
    @else
    <div class="sa-card">
        <div class="sa-card-head"><h2 class="sa-card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>Riwayat</h2></div>
        <p class="p-3 text-muted mb-0">Riwayat refund &amp; koreksi tersimpan dengan status pengajuan di tab masing-masing. Histori pembayaran asli tidak berubah.</p>
    </div>
    @endif
</div>

{{-- Modal Ajukan Refund --}}
<div wire:ignore.self class="modal fade" id="modalRefund" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Ajukan Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Pembayaran #{{ $selectedPembayaranId }}</p>
                <label class="form-label">Nominal refund (Rp)</label>
                <input type="number" class="form-control mb-2" wire:model="nominalRefund" min="1">
                <label class="form-label">Alasan</label>
                <textarea class="form-control mb-2" rows="2" wire:model="alasanRefund"></textarea>
                <label class="form-label">Metode</label>
                <select class="form-select mb-2" wire:model="metodeRefund">
                    <option value="transfer_manual">Transfer manual (sekolah)</option>
                    <option value="tunai">Tunai</option>
                    <option value="lainnya">Lainnya</option>
                    <option value="provider_api">API Provider (belum tersedia)</option>
                </select>
                <label class="form-label">Referensi (opsional)</label>
                <input type="text" class="form-control" wire:model="referensiRefund" placeholder="No. transfer / bukti / dll">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetForm" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                <button class="btn btn-primary" wire:click="ajukanRefund" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Ajukan">Ajukan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ajukan Koreksi --}}
<div wire:ignore.self class="modal fade" id="modalKoreksi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Ajukan Koreksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Pembayaran #{{ $selectedPembayaranId }}</p>
                <label class="form-label">Jenis Koreksi</label>
                <select class="form-select mb-2" wire:model.live="jenisKoreksi">
                    <option value="nominal">Nominal pembayaran</option>
                    <option value="alokasi">Alokasi pembayaran</option>
                    <option value="pencatatan_manual">Pencatatan manual</option>
                    <option value="hubungan_tagihan">Hubungan dengan tagihan lain</option>
                </select>
                <label class="form-label">Alasan</label>
                <textarea class="form-control mb-2" rows="2" wire:model="alasanKoreksi"></textarea>
                @if($jenisKoreksi === 'nominal')
                <label class="form-label">Nominal usulan (baru)</label>
                <input type="number" class="form-control mb-2" wire:model="nominalUsulan" min="1">
                @elseif($jenisKoreksi === 'pencatatan_manual')
                <label class="form-label">Metode usulan</label>
                <select class="form-select mb-2" wire:model="metodeUsulan"><option>tunai</option><option>transfer</option><option>va</option><option>qris</option><option>ewallet</option></select>
                <label class="form-label">Nama pembayar usulan</label>
                <input type="text" class="form-control mb-2" wire:model="namaPembayarUsulan">
                <label class="form-label">Tanggal pembayaran usulan</label>
                <input type="date" class="form-control mb-2" wire:model="tanggalPembayaranUsulan">
                @elseif($jenisKoreksi === 'hubungan_tagihan')
                <label class="form-label">Tagihan pengganti</label>
                <select class="form-select mb-2" wire:model="tagihanPenggantiId">
                    <option value="">-- Pilih tagihan pengganti --</option>
                    @foreach($tagihanOptions as $t)
                        <option value="{{ $t->idtagihan }}">{{ strtoupper($t->jenis) }} {{ $t->periode }} — Rp {{ number_format((int) $t->total_tagihan, 0, ',', '.') }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetForm" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                <button class="btn btn-primary" wire:click="ajukanKoreksi" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Ajukan">Ajukan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Penolakan --}}
<div wire:ignore.self class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-danger"><i class="fa-solid fa-xmark me-2"></i>Tolak</h5><button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <label class="form-label">Alasan penolakan (wajib)</label>
                <textarea class="form-control" rows="3" wire:model="alasanPenolakan"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                @if($tolakTipe === 'refund')
                    <button class="btn btn-danger" wire:click="tolakRefundTerpilih" data-bs-toggle="tooltip" data-bs-placement="top" title="Tolak">Tolak</button>
                @else
                    <button class="btn btn-danger" wire:click="tolakKoreksiTerpilih" data-bs-toggle="tooltip" data-bs-placement="top" title="Tolak">Tolak</button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal Proses Refund --}}
<div wire:ignore.self class="modal fade" id="modalProses" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Proses Refund (manual oleh sekolah)</h5><button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <label class="form-label">Referensi proses (wajib) — nomor transfer / nama penerima / tanggal</label>
                <input type="text" class="form-control mb-2" wire:model="referensiProses" placeholder="TRX-001 / tunai 11/11/2026 / dll">
                <label class="form-label">Catatan (opsional)</label>
                <textarea class="form-control" rows="2" wire:model="catatanProses"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                <button class="btn btn-primary" wire:click="prosesRefundTerpilih" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Proses">Tandai Terproses</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('show-modal-refund', function () { $('#modalRefund').modal('show'); });
    window.addEventListener('close-modal-refund', function () { $('#modalRefund').modal('hide'); });
    window.addEventListener('show-modal-koreksi', function () { $('#modalKoreksi').modal('show'); });
    window.addEventListener('close-modal-koreksi', function () { $('#modalKoreksi').modal('hide'); });
    window.addEventListener('show-modal-tolak', function () { $('#modalTolak').modal('show'); });
    window.addEventListener('close-modal-tolak', function () { $('#modalTolak').modal('hide'); });
    window.addEventListener('show-modal-proses', function () { $('#modalProses').modal('show'); });
    window.addEventListener('close-modal-proses', function () { $('#modalProses').modal('hide'); });
</script>
</div>
