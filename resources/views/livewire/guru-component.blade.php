@section('title')
Data Guru
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Guru</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" wire:click="bukaImport('guru')"><i class="fa-solid fa-upload me-1"></i>Import</button>
                    <button class="btn btn-outline-primary" wire:click="$dispatch('show-sync-guru-modal')"><i class="fa-solid fa-arrows-rotate me-1"></i>Terapkan paket menu ke semua guru</button>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-guru-modal')"><i class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>NIP</th><th>Nama</th><th>Mapel</th><th>JK</th><th>Status</th><th style="width: 150px; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gurus as $g)
                        <tr>
                            <td>{{ $g->nip }}</td>
                            <td>{{ $g->nama }}</td>
                            <td>
                                @foreach($g->mapel->take(3) as $m)
                                    <span class="badge bg-secondary me-1" title="{{ $m->nama }}">{{ $m->nama }}</span>
                                @endforeach
                                @if($g->mapel->count() > 3)
                                    <span class="badge bg-secondary">+{{ $g->mapel->count() - 3 }}</span>
                                @endif
                                @if($g->mapel->isEmpty()) - @endif
                            </td>
                            <td>{{ $g->jenis_kelamin }}</td>
                            <td>{{ $g->status }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-info text-white" wire:click="detailGuru({{ $g->idguru }})" title="Detail" aria-label="Detail"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning" wire:click="editGuru({{ $g->idguru }})"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationGuru({{ $g->idguru }})"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $gurus->links() }}
        </div>
    </div>

    <!-- Add Modal -->
    <div wire:ignore.self class="modal fade" id="addGuruModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Guru</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeGuru">
                        @if($errors->any())
                        <div class="alert alert-danger mb-3">
                            <strong>Ada kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="mb-2"><label>NIP</label><input class="form-control" wire:model="nip">@error('nip')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Kode Guru</label><input class="form-control" wire:model="kode_guru">@error('kode_guru')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama">@error('nama')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select>@error('jenis_kelamin')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir">@error('tempat_lahir')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir">@error('tanggal_lahir')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Telepon</label><input class="form-control" wire:model="telepon">@error('telepon')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea>@error('alamat')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option></select>@error('status')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2">
                            <label>Mapel yang diampu</label>
                            <input type="text" class="form-control mb-2" wire:model.live="mapelSearch" placeholder="Cari mapel...">
                            <div class="border p-2" style="max-height: 150px; overflow-y: auto;">
                                @forelse ($mapels as $m)
                                    <div class="form-check" @if($mapelSearch && !str_contains(strtolower($m->nama), strtolower($mapelSearch)) && !str_contains(strtolower($m->kode), strtolower($mapelSearch))) style="display:none;" @endif>
                                        <input class="form-check-input" type="checkbox" value="{{ $m->idmapel }}" wire:model="mapel_ids" id="add-mapel-{{ $m->idmapel }}">
                                        <label class="form-check-label" for="add-mapel-{{ $m->idmapel }}">
                                            {{ $m->kode }} - {{ $m->nama }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small">Belum ada mapel. Tambahkan di menu Mapel.</p>
                                @endforelse
                            </div>
                            @error('mapel_ids')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="storeGuru">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div wire:ignore.self class="modal fade" id="editGuruModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Guru</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateGuru">
                        @if($errors->any())
                        <div class="alert alert-danger mb-3">
                            <strong>Ada kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="mb-2"><label>NIP</label><input class="form-control" wire:model="nip">@error('nip')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Kode Guru</label><input class="form-control" wire:model="kode_guru">@error('kode_guru')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama">@error('nama')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select>@error('jenis_kelamin')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir">@error('tempat_lahir')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir">@error('tanggal_lahir')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Telepon</label><input class="form-control" wire:model="telepon">@error('telepon')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea>@error('alamat')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option></select>@error('status')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                        <div class="mb-2">
                            <label>Mapel yang diampu</label>
                            <input type="text" class="form-control mb-2" wire:model.live="mapelSearch" placeholder="Cari mapel...">
                            <div class="border p-2" style="max-height: 150px; overflow-y: auto;">
                                @forelse ($mapels as $m)
                                    <div class="form-check" @if($mapelSearch && !str_contains(strtolower($m->nama), strtolower($mapelSearch)) && !str_contains(strtolower($m->kode), strtolower($mapelSearch))) style="display:none;" @endif>
                                        <input class="form-check-input" type="checkbox" value="{{ $m->idmapel }}" wire:model="mapel_ids" id="edit-mapel-{{ $m->idmapel }}">
                                        <label class="form-check-label" for="edit-mapel-{{ $m->idmapel }}">
                                            {{ $m->kode }} - {{ $m->nama }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small">Belum ada mapel. Tambahkan di menu Mapel.</p>
                                @endforelse
                            </div>
                            @error('mapel_ids')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updateGuru">Simpan Perubahan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="detailGuruModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="detailGuruTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailGuruTitle">Detail Guru</h5>
                </div>
                <div class="modal-body detail-modal">
                    @if ($detailGuru)
                        @php
                            $guruStatus = match ($detailGuru->status) {
                                'aktif' => 'Aktif',
                                'nonaktif' => 'Nonaktif',
                                default => $detailGuru->status ?: '-',
                            };
                            $biodataRows = [
                                'NIP' => $detailGuru->nip ?: '-',
                                'Kode Guru' => $detailGuru->kode_guru ?: '-',
                                'Nama' => $detailGuru->nama ?: '-',
                                'Jenis Kelamin' => $detailGuru->jenis_kelamin === 'L' ? 'Laki-laki' : ($detailGuru->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                                'Tempat Lahir' => $detailGuru->tempat_lahir ?: '-',
                                'Tanggal Lahir' => $detailGuru->tanggal_lahir ? $detailGuru->tanggal_lahir->locale('id')->translatedFormat('d F Y') : '-',
                                'Telepon' => $detailGuru->telepon ?: '-',
                                'Alamat' => $detailGuru->alamat ?: '-',
                                'Status' => $guruStatus,
                            ];
                            $akunRows = [
                                'Username' => $detailGuru->user?->username ?: '-',
                                'Status Akun' => $detailGuru->user ? 'Terdaftar' : '-',
                            ];
                            $waliRows = $detailGuru->kelas->isEmpty()
                                ? ['Informasi' => 'Tidak menjadi wali kelas']
                                : $detailGuru->kelas->mapWithKeys(fn ($kelasWali) => [
                                    ($kelasWali->tahunajaran?->nama ?: '-').' · '.($kelasWali->nama ?: '-')
                                        .($tahunAjaranAktif && (int) $kelasWali->tahunajaran_idtahunajaran === (int) $tahunAjaranAktif->idtahunajaran ? ' · Aktif' : '')
                                        => '('.($kelasWali->tingkat ?: '-').') · Wali: '.($detailGuru->nama ?: '-'),
                                ])->all();
                        @endphp

                        @include('livewire.partials.detail-header', [
                            'name' => $detailGuru->nama,
                            'numberLabel' => 'NIP',
                            'number' => $detailGuru->nip,
                            'status' => $detailGuru->status,
                        ])

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                @include('livewire.partials.detail-card', ['title' => 'Biodata', 'rows' => $biodataRows])
                            </div>
                            <div class="col-12 col-lg-6 d-flex flex-column gap-3">
                                @include('livewire.partials.detail-card', ['title' => 'Akun', 'rows' => $akunRows])
                                @include('livewire.partials.detail-card', ['title' => 'Wali Kelas', 'rows' => $waliRows])
                            </div>
                            <div class="col-12">
                                @include('livewire.partials.detail-card', [
                                    'title' => 'Mengajar',
                                    'rows' => ['Mapel' => $detailGuru->mapel->isEmpty() ? '-' : $detailGuru->mapel->pluck('nama')->implode(', ')],
                                ])
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Data guru tidak tersedia.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="tutupDetailGuru">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div wire:ignore.self class="modal fade" id="deleteGuruModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Delete Confirmation</h5></div>
                <div class="modal-body pt-4 pb-4"><h6>Are you sure? You want to delete this data ?</h6></div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteGuru()">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="syncGuruModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="syncGuruTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="syncGuruTitle">Terapkan paket menu Guru</h5></div>
                <div class="modal-body">Paket menu Guru akan diterapkan ke semua akun guru. Menu yang sudah ada, termasuk menu manual, tidak akan dihapus.</div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="sinkronkanPaket">Terapkan paket</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.partials.import-modal')
</div>

<script>
    window.addEventListener('close-guru-modal', event => {
        $('#addGuruModal').modal('hide');
        $('#editGuruModal').modal('hide');
        $('#deleteGuruModal').modal('hide');
    });
    window.addEventListener('show-add-guru-modal', event => { $('#addGuruModal').modal('show'); });
    window.addEventListener('show-edit-guru-modal', event => { $('#editGuruModal').modal('show'); });
    window.addEventListener('show-detail-guru-modal', event => { $('#detailGuruModal').modal('show'); });
    window.addEventListener('close-detail-guru-modal', event => { $('#detailGuruModal').modal('hide'); });
    window.addEventListener('show-delete-confirmation-guru-modal', event => { $('#deleteGuruModal').modal('show'); });
    window.addEventListener('show-sync-guru-modal', () => { bootstrap.Modal.getOrCreateInstance(document.getElementById('syncGuruModal')).show(); });
    window.addEventListener('close-sync-guru-modal', () => { bootstrap.Modal.getOrCreateInstance(document.getElementById('syncGuruModal')).hide(); });
</script>
