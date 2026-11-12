<div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Antrean Verifikasi Wali–Siswa</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button class="nav-link {{ $tab === 'pending' ? 'active' : '' }}" wire:click="$set('tab','pending')">Antrean Pending</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $tab === 'aktif' ? 'active' : '' }}" wire:click="$set('tab','aktif')">Relasi Aktif (P5a)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $tab === 'putus' ? 'active' : '' }}" wire:click="$set('tab','putus')">Permintaan Putus (P5b)</button>
                </li>
            </ul>
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Cari wali / siswa / NIS" wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="perpage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            @error('aksi')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="table-responsive">
                @if ($tab === 'putus')
                    <table class="table table-striped table-hover">
                        <thead><tr><th>#</th><th>Peminta</th><th>Siswa</th><th>Alasan</th><th>Diajukan</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse ($items as $p)
                                <tr>
                                    <td>{{ $p->idpermintaan_putus }}</td>
                                    <td>{{ $p->peminta->name ?? $p->peminta->username ?? '-' }}</td>
                                    <td>{{ $p->relasi->siswa->nama ?? '-' }}</td>
                                    <td>{{ $p->alasan }}</td>
                                    <td>{{ $p->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-success" wire:click="bukaSetujuiPutus({{ $p->idpermintaan_putus }})">Setujui</button>
                                        <button class="btn btn-sm btn-danger" wire:click="bukaTolakPutus({{ $p->idpermintaan_putus }})">Tolak</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Tidak ada permintaan putus menunggu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Wali (pemilik)</th>
                                <th>Siswa</th>
                                <th>NIS</th>
                                <th>Hubungan</th>
                                <th>Diajukan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($items as $r)
                            <tr>
                                <td>{{ $r->idwalimurid }}</td>
                                <td>{{ $r->pemilik->name ?? $r->pemilik->username ?? '-' }}<br><small class="text-muted">{{ $r->pemilik->username ?? '' }}</small></td>
                                <td>{{ $r->siswa->nama ?? '-' }}</td>
                                <td>{{ $r->siswa->nis ?? '-' }}</td>
                                <td>{{ $r->hubungan }}</td>
                                <td>{{ $r->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td>
                                    @if ($tab === 'pending')
                                        <button class="btn btn-sm btn-success" wire:click="bukaAksi({{ $r->idwalimurid }}, 'verifikasi')">Verifikasi</button>
                                        <button class="btn btn-sm btn-danger" wire:click="bukaAksi({{ $r->idwalimurid }}, 'tolak')">Tolak</button>
                                    @else
                                        <button class="btn btn-sm btn-warning" wire:click="bukaAksi({{ $r->idwalimurid }}, 'putus')">Putus</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada pengajuan pending.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @endif
            </div>

            {{ $items->links() }}
        </div>
    </div>

    {{-- Modal aksi --}}
    @if ($targetId)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            @if ($aksi === 'verifikasi') Verifikasi Relasi
                            @elseif ($aksi === 'tolak') Tolak Pengajuan
                            @elseif ($aksi === 'putus') Putus Relasi
                            @endif
                        </h5>
                        <button type="button" class="btn-close" wire:click="batalAksi"></button>
                    </div>
                    <div class="modal-body">
                        @if ($aksi === 'verifikasi')
                            <div class="mb-3">
                                <label class="form-label">Tanggal mulai akses</label>
                                <input type="date" class="form-control" wire:model="tanggalMulai">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan perubahan periode (opsional)</label>
                                <textarea class="form-control" wire:model="alasanPeriode"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan (opsional)</label>
                                <textarea class="form-control" wire:model="catatan"></textarea>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label">Alasan (wajib)</label>
                                <textarea class="form-control" wire:model="alasan"></textarea>
                                @error('alasan') <span class="text-danger" style="font-size:11.5px;">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="batalAksi">Batal</button>
                        <button type="button" class="btn btn-primary" wire:click="simpanAksi">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
