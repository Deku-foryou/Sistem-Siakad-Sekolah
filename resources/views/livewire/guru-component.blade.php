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
                        <th>NIP</th><th>Nama</th><th>JK</th><th>Status</th><th style="width: 150px; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gurus as $g)
                        <tr>
                            <td>{{ $g->nip }}</td>
                            <td>{{ $g->nama }}</td>
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
                        <div class="mb-2"><label>NIP</label><input class="form-control" wire:model="nip">@error('nip')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama">@error('nama')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir">@error('tanggal_lahir')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Telepon</label><input class="form-control" wire:model="telepon"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
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
                        <div class="mb-2"><label>NIP</label><input class="form-control" wire:model="nip">@error('nip')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir"></div>
                        <div class="mb-2"><label>Telepon</label><input class="form-control" wire:model="telepon"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
                <div class="modal-body">
                    @if ($detailGuru)
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold mb-2" style="width: 64px; height: 64px; font-size: 20px;">
                                {{ collect(preg_split('/\s+/', trim((string) $detailGuru->nama)))->filter()->take(2)->map(fn ($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))->implode('') ?: '-' }}
                            </div>
                            <h5 class="mb-0">{{ $detailGuru->nama ?: '-' }}</h5>
                            <div class="text-muted">{{ $detailGuru->nip ?: '-' }}</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-bold">Biodata</h6>
                                <dl class="row mb-0">
                                    <dt class="col-5">NIP</dt><dd class="col-7">{{ $detailGuru->nip ?: '-' }}</dd>
                                    <dt class="col-5">Nama</dt><dd class="col-7">{{ $detailGuru->nama ?: '-' }}</dd>
                                    <dt class="col-5">Jenis Kelamin</dt><dd class="col-7">{{ $detailGuru->jenis_kelamin === 'L' ? 'Laki-laki' : ($detailGuru->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</dd>
                                    <dt class="col-5">Tempat Lahir</dt><dd class="col-7">{{ $detailGuru->tempat_lahir ?: '-' }}</dd>
                                    <dt class="col-5">Tanggal Lahir</dt><dd class="col-7">{{ $detailGuru->tanggal_lahir ? $detailGuru->tanggal_lahir->locale('id')->translatedFormat('d F Y') : '-' }}</dd>
                                    <dt class="col-5">Telepon</dt><dd class="col-7">{{ $detailGuru->telepon ?: '-' }}</dd>
                                    <dt class="col-5">Alamat</dt><dd class="col-7">{{ $detailGuru->alamat ?: '-' }}</dd>
                                    <dt class="col-5">Status</dt><dd class="col-7">{{ $detailGuru->status ?: '-' }}</dd>
                                </dl>
                            </div>
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-bold">Akun</h6>
                                <dl class="row mb-3">
                                    <dt class="col-5">Username</dt><dd class="col-7">{{ $detailGuru->user?->username ?: '-' }}</dd>
                                    <dt class="col-5">Status Akun</dt><dd class="col-7">{{ $detailGuru->user ? ($detailGuru->user->wajib_ganti_password ? 'Belum ganti password' : 'Sudah diganti') : '-' }}</dd>
                                </dl>

                                <h6 class="fw-bold">Wali Kelas</h6>
                                @if ($detailGuru->kelas->isEmpty())
                                    <p class="text-muted">Tidak menjadi wali kelas</p>
                                @else
                                    <ul class="list-group list-group-flush mb-3">
                                        @foreach ($detailGuru->kelas as $kelasWali)
                                            <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                                                <span>{{ $kelasWali->nama ?: '-' }} ({{ $kelasWali->tingkat ?: '-' }}) · {{ $kelasWali->tahunajaran?->nama ?: '-' }}</span>
                                                @if ($tahunAjaranAktif && (int) $kelasWali->tahunajaran_idtahunajaran === (int) $tahunAjaranAktif->idtahunajaran)
                                                    <span class="badge bg-primary">Aktif</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <h6 class="fw-bold">Mengajar</h6>
                                <p class="text-muted mb-0">Segera tersedia</p>
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
