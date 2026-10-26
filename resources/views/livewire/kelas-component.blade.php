@section('title')
Data Kelas
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Kelas</p></div>
                <div><button class="btn btn-primary" wire:click="$dispatch('show-add-kelas-modal')"><i class="fa-solid fa-plus "></i></button></div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select" wire:model.live="filterTahunajaran">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach ($tahunajarans as $t)
                                <option value="{{ $t->idtahunajaran }}">{{ $t->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Tahun Ajaran</th><th>Nama</th><th>Tingkat</th><th>Jenis</th><th>Wali</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($kelases as $k)
                        <tr>
                            <td>{{ $k->tahunajaran->nama ?? '-' }}</td>
                            <td>{{ $k->nama }}</td>
                            <td>{{ $k->tingkat }}</td>
                            <td>{{ $k->jenis }}</td>
                            <td>{{ $k->wali->nama ?? '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" wire:click="editKelas({{ $k->idkelas }})"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationKelas({{ $k->idkelas }})"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $kelases->links() }}
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addKelasModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Kelas</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeKelas">
                        <div class="mb-2"><label>Tahun Ajaran</label>
                            <select class="form-select" wire:model="tahunajaran_idtahunajaran">
                                <option value="">Pilih</option>
                                @foreach ($tahunajarans as $t)<option value="{{ $t->idtahunajaran }}">{{ $t->nama }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>Tingkat</label><select class="form-select" wire:model="tingkat"><option value="10">10</option><option value="11">11</option><option value="12">12</option></select></div>
                        <div class="mb-2"><label>Jenis</label><select class="form-select" wire:model="jenis"><option value="induk">induk</option><option value="mapel_pilihan">mapel_pilihan</option></select></div>
                        <div class="mb-2"><label>Wali Kelas (hanya induk)</label>
                            <select class="form-select" wire:model="guru_idguru">
                                <option value="">-</option>
                                @foreach ($gurus as $g)<option value="{{ $g->idguru }}">{{ $g->nama }}</option>@endforeach
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

    <div wire:ignore.self class="modal fade" id="editKelasModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Kelas</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateKelas">
                        <div class="mb-2"><label>Tahun Ajaran</label>
                            <select class="form-select" wire:model="tahunajaran_idtahunajaran">
                                <option value="">Pilih</option>
                                @foreach ($tahunajarans as $t)<option value="{{ $t->idtahunajaran }}">{{ $t->nama }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>Tingkat</label><select class="form-select" wire:model="tingkat"><option value="10">10</option><option value="11">11</option><option value="12">12</option></select></div>
                        <div class="mb-2"><label>Jenis</label><select class="form-select" wire:model="jenis"><option value="induk">induk</option><option value="mapel_pilihan">mapel_pilihan</option></select></div>
                        <div class="mb-2"><label>Wali Kelas</label>
                            <select class="form-select" wire:model="guru_idguru">
                                <option value="">-</option>
                                @foreach ($gurus as $g)<option value="{{ $g->idguru }}">{{ $g->nama }}</option>@endforeach
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

    <div wire:ignore.self class="modal fade" id="deleteKelasModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Delete Confirmation</h5></div>
                <div class="modal-body pt-4 pb-4"><h6>Are you sure? You want to delete this data ?</h6></div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteKelas()">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('close-kelas-modal', event => { $('#addKelasModal').modal('hide'); $('#editKelasModal').modal('hide'); $('#deleteKelasModal').modal('hide'); });
    window.addEventListener('show-add-kelas-modal', event => { $('#addKelasModal').modal('show'); });
    window.addEventListener('show-edit-kelas-modal', event => { $('#editKelasModal').modal('show'); });
    window.addEventListener('show-delete-confirmation-kelas-modal', event => { $('#deleteKelasModal').modal('show'); });
</script>
