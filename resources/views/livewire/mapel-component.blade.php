@section('title')
Data Mapel
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Mapel</p></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" wire:click="bukaImport('mapel')" data-bs-toggle="tooltip" data-bs-placement="top" title="Impor data"><i class="fa-solid fa-upload me-1"></i>Import</button>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-mapel-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data" aria-label="Tambah data"><i class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3"><input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kelompok</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($mapels as $m)
                        <tr>
                            <td>{{ $m->kode }}</td>
                            <td>{{ $m->nama }}</td>
                            <td>{{ $m->kelompok }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" wire:click="editMapel({{ $m->idmapel }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data" aria-label="Ubah data"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationMapel({{ $m->idmapel }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)" aria-label="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $mapels->links() }}
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addMapelModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Mapel</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeMapel">
                        <div class="mb-2"><label>Kode</label><input class="form-control" wire:model="kode">@error('kode')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>Kelompok</label><select class="form-select" wire:model="kelompok"><option value="wajib">wajib</option><option value="pilihan">pilihan</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editMapelModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Mapel</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateMapel">
                        <div class="mb-2"><label>Kode</label><input class="form-control" wire:model="kode"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>Kelompok</label><select class="form-select" wire:model="kelompok"><option value="wajib">wajib</option><option value="pilihan">pilihan</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Simpan Perubahan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteMapelModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Delete Confirmation</h5></div>
                <div class="modal-body pt-4 pb-4"><h6>Are you sure? You want to delete this data ?</h6></div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteMapel()" data-bs-toggle="tooltip" data-bs-placement="top" title="Ya, lanjutkan">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.partials.import-modal')
</div>

<script>
    window.addEventListener('close-mapel-modal', event => { $('#addMapelModal').modal('hide'); $('#editMapelModal').modal('hide'); $('#deleteMapelModal').modal('hide'); });
    window.addEventListener('show-add-mapel-modal', event => { $('#addMapelModal').modal('show'); });
    window.addEventListener('show-edit-mapel-modal', event => { $('#editMapelModal').modal('show'); });
    window.addEventListener('show-delete-confirmation-mapel-modal', event => { $('#deleteMapelModal').modal('show'); });
</script>
