@section('title', 'Data Tagihan')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-file-invoice me-2 text-success" aria-hidden="true"></i>Data Tagihan</h1>
            <p class="sa-sub">Daftar tagihan siswa, penerbitan dan pembatalan tagihan.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" wire:click="terbit('spp')" wire:loading.attr="disabled" wire:target="terbit" data-bs-toggle="tooltip" data-bs-placement="top" title="Terbitkan">
                <i class="fa-solid fa-calendar-days me-1" aria-hidden="true"></i> Terbitkan SPP {!! $periode !!}
            </button>
            <button class="btn btn-primary" wire:click="$dispatch('show-add-tagihan-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
                <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tagihan Insidental
            </button>
        </div>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-3"><label for="tg-search" class="form-label">Cari Siswa / NIS</label><input id="tg-search" type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Nama atau NIS"></div>
            <div class="col-md-3"><label for="tg-filter-status" class="form-label">Status</label><select id="tg-filter-status" class="form-select" wire:model.live="filterStatus"><option value="">Semua</option><option value="belum_dibayar">Belum Dibayar</option><option value="dibayar_sebagian">Dibayar Sebagian</option><option value="lunas">Lunas</option><option value="dibebaskan">Dibebaskan</option><option value="dibatalkan">Dibatalkan</option></select></div>
            <div class="col-md-3"><label for="tg-filter-jenis" class="form-label">Jenis</label><select id="tg-filter-jenis" class="form-select" wire:model.live="filterJenis"><option value="">Semua</option><option value="spp">SPP</option><option value="ujian">Ujian</option><option value="seragam">Seragam</option><option value="buku">Buku</option><option value="pendaftaran">Pendaftaran</option><option value="insidental">Insidental</option></select></div>
            <div class="col-md-3"><label for="tg-filter-periode" class="form-label">Periode</label><input id="tg-filter-periode" type="text" class="form-control" wire:model.live="filterPeriode" placeholder="YYYY-MM"></div>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-table" aria-hidden="true"></i>Daftar Tagihan</h2>
            <span class="sa-count">{{ $rows->total() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr><th>Siswa</th><th>Jenis</th><th>Periode</th><th class="text-end">Total</th><th>Status</th><th>Jatuh Tempo</th><th class="text-center" style="width:150px;">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><div class="fw-semibold">{{ $row->siswa?->nama }}</div><div class="text-body-secondary small">NIS {{ $row->siswa?->nis ?? '-' }}</div></td>
                            <td>{{ ucfirst($row->jenis) }} @if($row->nama) / {{ $row->nama }} @endif</td>
                            <td>{{ $row->periode }}</td>
                            <td class="text-end">Rp {{ number_format((int) $row->total_tagihan, 0, ',', '.') }}</td>
                            <td>
                                @if ($row->status === 'lunas') <span class="sa-badge sa-hadir">Lunas</span>
                                @elseif ($row->status === 'dibayar_sebagian') <span class="sa-badge sa-netral">Dibayar Sebagian</span>
                                @elseif ($row->status === 'dibebaskan') <span class="sa-badge sa-netral">Dibebaskan</span>
                                @elseif ($row->status === 'dibatalkan') <span class="sa-badge sa-alpha">Dibatalkan</span>
                                @else <span class="sa-badge sa-netral">Belum Dibayar</span> @endif
                            </td>
                            <td>{{ $row->tanggal_jatuh_tempo?->format('d/m/Y') }}</td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="lihatDetail({{ $row->idtagihan }})" aria-label="Lihat detail" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                @if ($row->status === 'belum_dibayar')
                                    <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $row->idtagihan }})" aria-label="Batalkan tagihan" data-bs-toggle="tooltip" data-bs-placement="top" title="Batalkan tagihan"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>
                                    @if (auth()->user()?->level === 'superadmin')
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="bebaskan({{ $row->idtagihan }})" aria-label="Bebaskan tagihan" data-bs-toggle="tooltip" data-bs-placement="top" title="Bebaskan tagihan"><i class="fa-solid fa-hand-holding" aria-hidden="true"></i></button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="sa-empty"><i class="fa-solid fa-file-circle-xmark" aria-hidden="true"></i><p class="sa-empty-title">Belum ada tagihan</p><p class="sa-empty-text">Klik "Terbitkan SPP" atau "Tagihan Insidental" untuk memulai.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card-foot">{{ $rows->links() }}</div>
    </div>

    {{-- Modal Insidental --}}
    <div wire:ignore.self class="modal fade" id="addTagihanModal" tabindex="-1" aria-labelledby="addTagihanTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addTagihanTitle"><i class="fa-solid fa-file-circle-plus me-2" aria-hidden="true"></i>Tagihan Insidental</h5></div>
                <form wire:submit.prevent="terbit('insidental')">
                    <div class="modal-body">
                        <label class="form-label" for="add-tg-nama">Nama Tagihan</label>
                        <input id="add-tg-nama" type="text" class="form-control mb-3" wire:model="nama" placeholder="Contoh: Ujian Akhir Semester">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label" for="add-tg-jenis">Jenis</label><select id="add-tg-jenis" class="form-select" wire:model="jenis"><option value="ujian">Ujian</option><option value="seragam">Seragam</option><option value="buku">Buku</option><option value="pendaftaran">Pendaftaran</option><option value="insidental">Insidental</option><option value="lainnya">Lainnya</option></select></div>
                            <div class="col-md-6"><label class="form-label" for="add-tg-periode">Periode</label><input id="add-tg-periode" type="text" class="form-control" wire:model="periode" placeholder="YYYY-MM"></div>
                        </div>
                        <div class="row g-2 mt-2"><div class="col-md-6"><label class="form-label" for="add-tg-nominal">Nominal (Rp)</label><input id="add-tg-nominal" type="number" min="1000" class="form-control" wire:model="nominal" placeholder="0"></div><div class="col-md-6"><label class="form-label" for="add-tg-jatuh-tempo">Jatuh Tempo</label><input id="add-tg-jatuh-tempo" type="date" class="form-control" wire:model="jatuhTempo"></div></div>
                        <label class="form-label mt-2" for="add-tg-scope">Cakupan</label>
                        <select id="add-tg-scope" class="form-select mb-3" wire:model.live="scope"><option value="semua">Semua siswa aktif</option><option value="kelas">Per kelas</option><option value="siswa">Per siswa</option></select>
                        @if ($scope === 'kelas')
                            <label class="form-label" for="add-tg-kelas">Kelas</label>
                            <select id="add-tg-kelas" class="form-select mb-3" wire:model="scopeId"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $k)<option value="{{ $k->idkelas }}">{{ $k->nama }} ({{ $k->tingkat }})</option>@endforeach</select>
                        @elseif ($scope === 'siswa')
                            <label class="form-label" for="lov-siswa-search">Siswa</label>
                            @include('livewire.partials.lov-siswa', ['lovMode' => 'single', 'siswaLabel' => $siswaLabel, 'siswaTerpilih' => []])
                            @error('siswaId') <div class="invalid-feedback d-block mb-3" role="alert">{{ $message }}</div> @enderror
                        @endif
                        <label class="form-label" for="add-tg-alasan">Alasan (opsional)</label>
                        <textarea id="add-tg-alasan" class="form-control" rows="2" wire:model="alasan"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button><button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="terbit" data-bs-toggle="tooltip" data-bs-placement="top" title="Terbitkan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Terbitkan</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Detail --}}
    <div wire:ignore.self class="modal fade" id="detailTagihanModal" tabindex="-1" aria-labelledby="detailTagihanTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="detailTagihanTitle"><i class="fa-solid fa-file-lines me-2" aria-hidden="true"></i>Detail Tagihan</h5></div>
                @if ($detailId)
                    @php $t = \App\Models\Tagihan::with('siswa')->find($detailId); @endphp
                    @if ($t)
                        <div class="modal-body">
                            <div class="sa-hint mb-2">Siswa: <strong>{{ $t->siswa?->nama }}</strong> (NIS {{ $t->siswa?->nis ?? '-' }}) &middot; Kelas: {{ $t->kelas_nama ?? '-' }}</div>
                            <div class="row g-2">
                                <div class="col-md-6"><div class="sa-hint">Jenis</div><div>{{ ucfirst($t->jenis) }} @if($t->nama) / {{ $t->nama }} @endif</div></div>
                                <div class="col-md-6"><div class="sa-hint">Periode</div><div>{{ $t->periode }}</div></div>
                                <div class="col-md-6"><div class="sa-hint">Nominal</div><div>Rp {{ number_format((int) $t->nominal, 0, ',', '.') }}</div></div>
                                <div class="col-md-6"><div class="sa-hint">Diskon</div><div>Rp {{ number_format((int) $t->diskon_nominal, 0, ',', '.') }} ({{ $t->diskon_persen }}%)</div></div>
                                <div class="col-md-6"><div class="sa-hint">Total</div><div class="fw-semibold">Rp {{ number_format((int) $t->total_tagihan, 0, ',', '.') }}</div></div>
                                <div class="col-md-6"><div class="sa-hint">Status</div><div>{{ $t->label_status }}</div></div>
                                <div class="col-md-6"><div class="sa-hint">Terbit</div><div>{{ $t->tanggal_terbit?->format('d/m/Y') }}</div></div>
                                <div class="col-md-6"><div class="sa-hint">Jatuh Tempo</div><div>{{ $t->tanggal_jatuh_tempo?->format('d/m/Y') }}</div></div>
                                <div class="col-md-6"><div class="sa-hint">Tarif</div><div>{{ $t->tarif_nama ?? '-' }} ({{ $t->tarif_jenis ?? '-' }})</div></div>
                                <div class="col-md-6"><div class="sa-hint">Denda ID</div><div>{{ $t->denda_id ?? '-' }}</div></div>
                            </div>
                        </div>
                    @endif
                @endif
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="$set('detailId', null)" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button></div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.addEventListener('close-tagihan-modal', () => { $('#addTagihanModal, #detailTagihanModal').modal('hide'); });
    window.addEventListener('show-add-tagihan-modal', () => $('#addTagihanModal').modal('show'));
    window.addEventListener('show-detail-tagihan-modal', () => $('#detailTagihanModal').modal('show'));
</script>
@endscript
