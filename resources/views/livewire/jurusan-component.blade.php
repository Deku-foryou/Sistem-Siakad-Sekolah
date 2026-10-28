@section('title')
Data Jurusan
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Jurusan</p></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" wire:click="bukaImport('jurusan')"><i class="fa-solid fa-upload me-1"></i>Import</button>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-jurusan-modal')"><i class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Kode</th><th>Nama</th><th>Deskripsi</th><th>Aktif</th><th style="width: 150px; white-space: nowrap;">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($jurusans as $j)
                        <tr>
                            <td>{{ $j->kode }}</td>
                            <td>{{ $j->nama }}</td>
                            <td>{{ $j->deskripsi ?: '-' }}</td>
                            <td>{{ $j->aktif ? 'Ya' : 'Tidak' }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" wire:click="editJurusan({{ $j->idjurusan }})"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationJurusan({{ $j->idjurusan }})"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $jurusans->links() }}
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addJurusanModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Jurusan</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeJurusan">
                        <div class="mb-2"><label>Kode</label><input class="form-control" wire:model="kode"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>Deskripsi</label><textarea class="form-control" wire:model="deskripsi"></textarea></div>
                        <div class="mb-2"><label>Status Aktif</label>
                            <select class="form-select" wire:model="aktif">
                                <option value="true">Aktif</option>
                                <option value="false">Nonaktif</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editJurusanModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Jurusan</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateJurusan">
                        <div class="mb-2"><label>Kode</label><input class="form-control" wire:model="kode"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>Deskripsi</label><textarea class="form-control" wire:model="deskripsi"></textarea></div>
                        <div class="mb-2"><label>Status Aktif</label>
                            <select class="form-select" wire:model="aktif">
                                <option value="true">Aktif</option>
                                <option value="false">Nonaktif</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteJurusanModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Delete Confirmation</h5></div>
                <div class="modal-body pt-4 pb-4"><h6>Are you sure? You want to delete this data ?</h6></div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteJurusan()">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.partials.import-modal')
</div>

<script>
    window.addEventListener('close-jurusan-modal', event => { $('#addJurusanModal').modal('hide'); $('#editJurusanModal').modal('hide'); $('#deleteJurusanModal').modal('hide'); });
    window.addEventListener('show-add-jurusan-modal', event => { $('#addJurusanModal').modal('show'); });
    window.addEventListener('show-edit-jurusan-modal', event => { $('#editJurusanModal').modal('show'); });
    window.addEventListener('show-delete-confirmation-jurusan-modal', event => { $('#deleteJurusanModal').modal('show'); });
</script>