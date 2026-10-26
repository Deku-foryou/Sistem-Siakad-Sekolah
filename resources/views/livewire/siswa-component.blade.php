@section('title')
Data Siswa
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Siswa</p></div>
                <div><button class="btn btn-primary" wire:click="$dispatch('show-add-siswa-modal')"><i class="fa-solid fa-plus "></i></button></div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3"><input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>NISN</th><th>Nama</th><th>JK</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($siswas as $s)
                        <tr>
                            <td>{{ $s->nisn }}</td>
                            <td>{{ $s->nama }}</td>
                            <td>{{ $s->jenis_kelamin }}</td>
                            <td>{{ $s->status }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" wire:click="editSiswa({{ $s->idsiswa }})"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationSiswa({{ $s->idsiswa }})"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $siswas->links() }}
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Siswa</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeSiswa">
                        <div class="mb-2"><label>NISN</label><input class="form-control" wire:model="nisn">@error('nisn')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>NIS</label><input class="form-control" wire:model="nis"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama">@error('nama')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir">@error('tanggal_lahir')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Agama</label><input class="form-control" wire:model="agama"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Nama Ayah</label><input class="form-control" wire:model="nama_ayah"></div>
                        <div class="mb-2"><label>Nama Ibu</label><input class="form-control" wire:model="nama_ibu"></div>
                        <div class="mb-2"><label>Telepon Ortu</label><input class="form-control" wire:model="telepon_ortu"></div>
                        <div class="mb-2"><label>Tahun Masuk</label><input class="form-control" wire:model="tahun_masuk"></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="lulus">lulus</option><option value="pindah">pindah</option><option value="keluar">keluar</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Siswa</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateSiswa">
                        <div class="mb-2"><label>NISN</label><input class="form-control" wire:model="nisn"></div>
                        <div class="mb-2"><label>NIS</label><input class="form-control" wire:model="nis"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir"></div>
                        <div class="mb-2"><label>Agama</label><input class="form-control" wire:model="agama"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Nama Ayah</label><input class="form-control" wire:model="nama_ayah"></div>
                        <div class="mb-2"><label>Nama Ibu</label><input class="form-control" wire:model="nama_ibu"></div>
                        <div class="mb-2"><label>Telepon Ortu</label><input class="form-control" wire:model="telepon_ortu"></div>
                        <div class="mb-2"><label>Tahun Masuk</label><input class="form-control" wire:model="tahun_masuk"></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="lulus">lulus</option><option value="pindah">pindah</option><option value="keluar">keluar</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Delete Confirmation</h5></div>
                <div class="modal-body pt-4 pb-4"><h6>Are you sure? You want to delete this data ?</h6></div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteSiswa()">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('close-siswa-modal', event => { $('#addSiswaModal').modal('hide'); $('#editSiswaModal').modal('hide'); $('#deleteSiswaModal').modal('hide'); });
    window.addEventListener('show-add-siswa-modal', event => { $('#addSiswaModal').modal('show'); });
    window.addEventListener('show-edit-siswa-modal', event => { $('#editSiswaModal').modal('show'); });
    window.addEventListener('show-delete-confirmation-siswa-modal', event => { $('#deleteSiswaModal').modal('show'); });
</script>
