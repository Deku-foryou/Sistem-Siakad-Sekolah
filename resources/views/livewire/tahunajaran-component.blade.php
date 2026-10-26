@section('title')
Data Tahun Ajaran
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">

        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Tahun Ajaran</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Kelola data tahun ajaran.
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-tahunajaran-modal')"><i
                            class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div class="flex-grow-1" style="max-width: 450px;">
                        <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #e9ecef;">
                            <span class="input-group-text bg-white border-0 ps-3">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-0 ps-2 py-2" placeholder="Cari..."
                                style="font-size: 14px; box-shadow: none;" wire:model.live.debounce.300ms="search">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-2 pb-0 border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" style="width:100%;white-space:nowrap">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 3%">No</th>
                                        <th style="width: 140px;text-align: center">Action</th>
                                        <th>Nama</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($tahunajarans->count() > 0)
                                        @foreach ($tahunajarans as $key => $datas)
                                            <tr class="px-4">
                                                <td class="text-center">{{ $tahunajarans->firstItem() + $key }}</td>
                                                <td style="min-width: 120px;text-align: center">
                                                    <button class="btn btn-sm btn-success"
                                                        wire:click="aktifkan('{{ $datas->idtahunajaran }}')">
                                                        Aktifkan
                                                    </button>
                                                    <button class="btn btn-sm btn-warning text-dark"
                                                        wire:click="editTahunajaran('{{ $datas->idtahunajaran }}')"><i
                                                            class="fa-regular fa-pen-to-square"></i></button>
                                                    <button class="btn btn-sm btn-danger text-white"
                                                        wire:click="deleteConfirmationTahunajaran('{{ $datas->idtahunajaran}}')"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </td>
                                                <td>{{ $datas->nama }}</td>
                                                <td>
                                                    @if ($datas->aktif)
                                                        <span class="badge bg-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-secondary">Nonaktif</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" style="text-align: center;"><small>Data Tidak ditemukan</small>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $tahunajarans->firstItem() }} to {{ $tahunajarans->lastItem() }} of {{
                            $tahunajarans->total() }} results
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-6 d-flex justify-content-end align-items-center">
                        {{ $tahunajarans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addTahunajaranModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Tahun Ajaran</h5>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeTahunajaran">
                        <div class="form-group row mb-3">
                            <label for="nama" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Nama (YYYY/YYYY)</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="nama" class="form-control" wire:model="nama">
                                @error('nama')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                        <span wire:loading.remove>
                            Simpan
                        </span>
                    </button>
                    <button type="button" class="btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirm --}}
    <div wire:ignore.self class="modal fade" id="deleteTahunajaranModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Confirmation</h5>
                </div>
                <div class="modal-body pt-4 pb-4">
                    <h6>Are you sure? You want to delete this data ?</h6>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteTahunajaran()">Ya</button>
                    <button type="button" class="btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div wire:ignore.self class="modal fade" id="editTahunajaranModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tahun Ajaran</h5>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateTahunajaran">
                        <div class="form-group row mb-3">
                            <label for="nama" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Nama (YYYY/YYYY)</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="nama" class="form-control" wire:model="nama">
                                @error('nama')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Tunggu...</span>
                                <span wire:loading.remove>
                                    Simpan Perubahan
                                </span>
                            </button>
                            <button type="button" class="btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                                wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('close-tahunajaran-modal', event => {
        $('#addTahunajaranModal').modal('hide');
        $('#editTahunajaranModal').modal('hide');
        $('#deleteTahunajaranModal').modal('hide');
    });

    window.addEventListener('show-add-tahunajaran-modal', event => {
        $('#addTahunajaranModal').modal('show');
    });

    window.addEventListener('show-edit-tahunajaran-modal', event => {
        $('#editTahunajaranModal').modal('show');
    });

    window.addEventListener('show-delete-confirmation-tahunajaran-modal', event => {
        $('#deleteTahunajaranModal').modal('show');
    });
</script>
