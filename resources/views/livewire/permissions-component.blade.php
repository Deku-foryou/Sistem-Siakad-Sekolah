@section('title')
Data Permissions
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <p class="p-0 m-0 h3 fw-bold mb-3 mb-lg-0 ">Permissions</p>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-permissions-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data"><i
                            class="fa-solid fa-plus me-2 "></i> Tambah Data Permissions</button>
                </div>
            </div>
        </div>
        <!-- Action Toolbar Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                <!-- Search Box -->
                <div class="flex-grow-1" style="max-width: 450px;">
                    <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #e9ecef;">
                        <span class="input-group-text bg-white border-0 ps-3">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-0 ps-2 py-2"
                            placeholder="Cari..."
                            style="font-size: 14px; box-shadow: none;" wire:model.live.debounce.300ms="search">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Tombol Export Excel -->
                    <button class="btn btn-success text-white d-flex align-items-center gap-2 px-3"
                        style="border-radius: 10px; font-size: 13px; font-weight: 600;" wire:click="exportToExcel" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor Excel">
                        <i class="fa-solid fa-download"></i>
                        <span>Excel</span>
                    </button>
                    <!-- Tombol Export PDF -->
                    <button class="btn btn-danger text-white d-flex align-items-center gap-2 px-3"
                        style="border-radius: 10px; font-size: 13px; font-weight: 600;" wire:click="exportToPdf" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor PDF">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>PDF</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
            <div class="col-md-12">

                <div class="card mb-2 pb-0 border-0">
                    <div class="card-body p-0">



                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 8%;text-align: center">Action</th>
                                        <th>Name</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($permissionss->count() > 0)
                                    @foreach ($permissionss as $datas)
                                    <tr class="px-4">
                                        <td style="width: 8%;text-align: center">
                                            <button class="btn btn-sm btn-warning text-dark"
                                                wire:click="editPermissions('{{ $datas->id }}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data" aria-label="Ubah data"><i
                                                    class="fa-regular fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-danger text-white"
                                                wire:click="deleteConfirmationPermissions('{{ $datas->id}}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)" aria-label="Hapus data (ada konfirmasi)"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                        <td>{{ $datas->name }}</td>
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
                    <div class=" col-lg-6 d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $permissionss->firstItem() }} to {{ $permissionss->lastItem() }} of {{
                            $permissionss->total() }} results
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-6 d-flex justify-content-end align-items-center">
                        {{ $permissionss->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="permissionsName" wire:model="permissionsName" readonly>
        <button type="button" class="btn btn-primary" id="btnpermissionsModalLovAdd" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari" aria-label="Cari">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="permissionsName" wire:model="permissionsName" readonly>
        <button type="button" class="btn btn-primary" id="btnpermissionsModalLovEdit" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari" aria-label="Cari">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addPermissionsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Permissions</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storePermissions">


                        <div class="form-group row mb-3">
                            <label for="name" class="col-3 fw-bold">Name <span class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="name" class="form-control" wire:model="name">
                                @error('name')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>



                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                        <span wire:loading.remove>
                            Tambah Permissions
                        </span>
                    </button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>





    {{-- Delete Confirm --}}

    <div wire:ignore.self class="modal fade" id="deletePermissionsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered  " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Confirmation</h5>
                </div>
                <div class="modal-body pt-4 pb-4">
                    <h6>Are you sure? You want to delete this data ?</h6>
                </div>
                <div class="modal-footer">
                    <button class="btn  btn-danger" id="confirmDeleteButton"
                        wire:click="deletePermissions()" data-bs-toggle="tooltip" data-bs-placement="top" title="Ya, lanjutkan">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editPermissionsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Permissions</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updatePermissions">


                        <div class="form-group row mb-3">
                            <label for="name" class="col-3 fw-bold">Name <span class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="name" class="form-control" wire:model="name">
                                @error('name')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>



                        <div class="modal-footer">
                            <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                                <span wire:loading.remove>
                                    Update Permissions
                                </span>
                            </button>
                            <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                                wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>


    </div>
</div>



<script>
    window.addEventListener('close-permissions-modal', event =>{ 
            $('#addPermissionsModal').modal('hide'); 
            $('#editPermissionsModal').modal('hide'); 
            $('#deletePermissionsModal').modal('hide'); 
        }); 
 
 
       $('#addPermissionsModal').on('shown.bs.modal', function () { 
            $("#name").focus(); 
        }); 
 
 
        window.addEventListener('show-add-permissions-modal', event =>{ 
            $('#addPermissionsModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-edit-permissions-modal', event =>{ 
            $('#editPermissionsModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-delete-confirmation-permissions-modal', event =>{ 
            $('#deletePermissionsModal').modal('show'); 
        }); 
 
</script>