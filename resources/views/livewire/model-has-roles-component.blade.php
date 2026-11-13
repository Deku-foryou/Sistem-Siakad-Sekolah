@section('title')
Data Model has Roles
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="mb-3 mb-lg-0">
                    <p class='p-0 m-0 h3 fw-bold'>Pengaturan Pengguna has Role</p>
                    <div class="mt-2" style="color: #868686"> <i
                            class="fa-regular fa-circle-question me-1 text-danger"></i>
                        Tentukan role apa saja yang boleh diakses oleh masing - masing pengguna yang ada.</div>
                </div>
                <div>
                    {{-- <button class="btn btn-primary" wire:click="$dispatch('show-add-model_has_roles-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data"><i
                            class="fa-solid fa-plus me-2 "></i> Tambah Data Model_has_roles</button> --}}
                    <a class='btn btn-primary' href="/users">
                        <i class="fa-solid fa-arrow-left-long me-1"></i>
                        Kembali</a>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-lg-5">
                <form wire:submit.prevent='storeModel_has_roles'>

                    <div class="col-12 mb-3">
                        <button type='submit' class='btn btn-primary btn-sm' wire:loading.attr='disabled' data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                            <span wire:loading>
                                <span class='spinner-border spinner-border-sm'></span> Loading...</span>
                            <span wire:loading.remove>
                                <i class="fa-regular fa-floppy-disk me-2"></i> Simpan
                            </span>
                        </button>
                    </div>

                    <div class="mb-2 fw-medium">Module Role <span class="text-danger">*</span></div>

                    <div class="row">
                        <div class="col-12 mb-3 px-2">
                            <div class="border p-2 fw-medium rounded-top" style="background: #eff5fd"><i
                                    class="fa-solid fa-hashtag me-1" class="fw-normal" style="font-size: 10px"></i>
                                Roles

                            </div>
                            <div class="border p-2 rounded-bottom" style="margin-top: -1px">


                                @foreach ($roles as $datas)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="{{ $datas->id }}"
                                        wire:model.lazy='role_id' id="role_{{ $datas->id }}" @if(in_array($datas->id,
                                    $role_id))
                                    checked @endif>


                                    <label class="form-check-label" for="role_{{ $datas->id }}">
                                        {{ $datas->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div class="col-lg-7">
                <div class="py-2 mb-2">
                    <div class="d-lg-flex justify-content-between align-items-center">
                        <div>
                            <input type="text" class="form-control mb-1 mb-lg-0 " placeholder="Search"
                                wire:model.live.debounce.300ms="search">
                        </div>

                    </div>
                </div>
                <div class="card mb-2 pb-0 border-0">
                    <div class="card-body p-0">

                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($model_has_roles ->count() > 0)
                                    @foreach ($model_has_roles as $datas)
                                    <tr class='px-4'>
                                        <td>{{ $datas->users->name }}</td>
                                        <td>{{ $datas->roles->name }}</td>


                                    </tr>
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan='5' style='text-align: center;'><small>No Model_has_roles
                                                Found</small></td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="model_has_rolesName" wire:model="model_has_rolesName" readonly>
        <button type="button" class="btn btn-primary" id="btnmodel_has_rolesModalLovAdd" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari" aria-label="Cari">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="model_has_rolesName" wire:model="model_has_rolesName" readonly>
        <button type="button" class="btn btn-primary" id="btnmodel_has_rolesModalLovEdit" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari" aria-label="Cari">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addModel_has_rolesModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Model_has_roles</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeModel_has_roles">
                        <div class="form-group row mb-3">
                            <label for="role_id" class="col-3 fw-bold">role_id</label>
                            <div class="col-9">
                                <input type="text" id="role_id" class="form-control" wire:model="role_id">
                                @error('role_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="model_type" class="col-3 fw-bold">model_type</label>
                            <div class="col-9">
                                <input type="text" id="model_type" class="form-control" wire:model="model_type">
                                @error('model_type')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="model_id" class="col-3 fw-bold">model_id</label>
                            <div class="col-9">
                                <input type="text" id="model_id" class="form-control" wire:model="model_id">
                                @error('model_id')
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
                            Tambah Model_has_roles
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

    <div wire:ignore.self class="modal fade" id="deleteModel_has_rolesModal" tabindex="-1" data-bs-backdrop="static"
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
                        wire:click="deleteModel_has_roles()" data-bs-toggle="tooltip" data-bs-placement="top" title="Ya, lanjutkan">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editModel_has_rolesModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Model_has_roles</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateModel_has_roles">
                        <div class="form-group row mb-3">
                            <label for="role_id" class="col-3 fw-bold">role_id</label>
                            <div class="col-9">
                                <input type="text" id="role_id" class="form-control" wire:model="role_id">
                                @error('role_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="model_type" class="col-3 fw-bold">model_type</label>
                            <div class="col-9">
                                <input type="text" id="model_type" class="form-control" wire:model="model_type">
                                @error('model_type')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="model_id" class="col-3 fw-bold">model_id</label>
                            <div class="col-9">
                                <input type="text" id="model_id" class="form-control" wire:model="model_id">
                                @error('model_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                                <span wire:loading.remove>
                                    Update Model_has_roles
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
    window.addEventListener('close-model_has_roles-modal', event =>{ 
            $('#addModel_has_rolesModal').modal('hide'); 
            $('#editModel_has_rolesModal').modal('hide'); 
            $('#deleteModel_has_rolesModal').modal('hide'); 
        }); 
 
 
       $('#addModel_has_rolesModal').on('shown.bs.modal', function () { 
            $("id input form pertama").focus(); 
        }); 
 
 
        window.addEventListener('show-add-model_has_roles-modal', event =>{ 
            $('#addModel_has_rolesModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-edit-model_has_roles-modal', event =>{ 
            $('#editModel_has_rolesModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-delete-confirmation-model_has_roles-modal', event =>{ 
            $('#deleteModel_has_rolesModal').modal('show'); 
        }); 
 
</script>