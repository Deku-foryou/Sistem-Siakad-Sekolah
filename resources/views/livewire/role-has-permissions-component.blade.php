@section('title')
Data Role has permissions
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="mb-3 mb-lg-0">
                    <p class='p-0 m-0 h3 fw-bold'>Pengaturan Role Has Permission</p>
                    <div class="mt-2" style="color: #868686"> <i class="fa-regular fa-circle-question me-1"></i>
                        Tentukan permission apa saja yang boleh diakses oleh masing - masing role yang ada.</div>
                </div>
                <div>
                    <a class='btn btn-primary' href="/roles">
                        <i class="fa-solid fa-arrow-left-long me-1"></i>
                        Kembali</a>
                    {{-- <button class="btn btn-primary"
                        wire:click="$dispatch('show-add-role_has_permissions-modal')"><i
                            class="fa-solid fa-plus me-2 "></i> Tambah Data Role_has_permissions</button> --}}
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-lg-5">
                <form wire:submit.prevent='storeRole_has_permissions'>
                    <div class="col-12 mb-3">
                        <button type='submit' class='btn btn-primary btn-sm' wire:loading.attr='disabled'>
                            <span wire:loading>
                                <span class='spinner-border spinner-border-sm'></span> Loading...</span>
                            <span wire:loading.remove>
                                <i class="fa-regular fa-floppy-disk me-2"></i> Simpan
                            </span>
                        </button>
                        <button type='button' class='btn btn-success btn-sm' wire:click='selectAll'>

                            <i class="fa-solid fa-check me-2"></i>Pilih Semua
                        </button>
                    </div>

                    <div class="mb-2 fw-medium">Module Permissions <span class="text-danger">*</span></div>


                    <div class="row">
                        <div class="col-12 mb-3 px-2">
                            <div class="border p-2 fw-medium rounded-top" style="background: #eff5fd"><i
                                    class="fa-solid fa-hashtag me-1" class="fw-normal" style="font-size: 10px"></i>
                                Permisions
                            </div>
                            <div class="border p-2 rounded-bottom" style="margin-top: -1px">
                                @foreach ($permissions as $datas)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $datas->id }}"
                                        wire:model.lazy='permission_id' id="permission_{{ $datas->id }}"
                                        @if(in_array($datas->id,
                                    $permission_id)) checked @endif>


                                    <label class="form-check-label" for="permission_{{ $datas->id }}">
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
                                        <th>Role</th>
                                        <th>Permission</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($role_has_permissionss ->count() > 0)
                                    @foreach ($role_has_permissionss as $datas)
                                    <tr class='px-4'>
                                        <td>{{ $datas->roles->name }}</td>
                                        <td>{{ $datas->permissions->name }}</td>


                                    </tr>
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan='5' style='text-align: center;'><small>No Role_has_permissions
                                                Found</small>
                                        </td>
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
        <input class="form-control" id="role_has_permissionsName" wire:model="role_has_permissionsName" readonly>
        <button type="button" class="btn btn-primary" id="btnrole_has_permissionsModalLovAdd">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="role_has_permissionsName" wire:model="role_has_permissionsName" readonly>
        <button type="button" class="btn btn-primary" id="btnrole_has_permissionsModalLovEdit">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addRole_has_permissionsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Role_has_permissions</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeRole_has_permissions">
                        <div class="form-group row mb-3">
                            <label for="permission_id" class="col-3 fw-bold">permission_id</label>
                            <div class="col-9">
                                <input type="text" id="permission_id" class="form-control" wire:model="permission_id">
                                @error('permission_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="role_id" class="col-3 fw-bold">role_id</label>
                            <div class="col-9">
                                <input type="text" id="role_id" class="form-control" wire:model="role_id">
                                @error('role_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled">
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                        <span wire:loading.remove>
                            Tambah Role_has_permissions
                        </span>
                    </button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
                </form>
            </div>
        </div>
    </div>





    {{-- Delete Confirm --}}

    <div wire:ignore.self class="modal fade" id="deleteRole_has_permissionsModal" tabindex="-1"
        data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId"
        aria-hidden="true">

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
                        wire:click="deleteRole_has_permissions()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editRole_has_permissionsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role_has_permissions</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateRole_has_permissions">
                        <div class="form-group row mb-3">
                            <label for="permission_id" class="col-3 fw-bold">permission_id</label>
                            <div class="col-9">
                                <input type="text" id="permission_id" class="form-control" wire:model="permission_id">
                                @error('permission_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="role_id" class="col-3 fw-bold">role_id</label>
                            <div class="col-9">
                                <input type="text" id="role_id" class="form-control" wire:model="role_id">
                                @error('role_id')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                                <span wire:loading.remove>
                                    Update Role_has_permissions
                                </span>
                            </button>
                            <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                                wire:click="close()">Close</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>


    </div>

    {{-- @include('livewire.role_has_permissions-lov') --}}


</div>



<script>
    window.addEventListener('close-role_has_permissions-modal', event =>{ 
            $('#addRole_has_permissionsModal').modal('hide'); 
            $('#editRole_has_permissionsModal').modal('hide'); 
            $('#deleteRole_has_permissionsModal').modal('hide'); 
        }); 
 
 
  // copy dan aktifkan jika ingin menggunakan LOV  
 
  //       window.addEventListener('close-modal-lov', event =>{ 
   //          $('#role_has_permissionsModal').modal('hide'); 
   //     }); 
 
 
  // $(document).ready(function(){ 
   //      $('#btnrole_has_permissionsModalLovAdd').click(function(){ 
   //       $('#role_has_permissionsModal').modal('show'); 
    //    }); 
  //  }); 
 
 
  // $(document).ready(function(){ 
   //      $('#btnrole_has_permissionsModalLovEdit').click(function(){ 
   //       $('#role_has_permissionsModal').modal('show'); 
    //    }); 
  //  }); 
 
 
       $('#addRole_has_permissionsModal').on('shown.bs.modal', function () { 
            $("id input form pertama").focus(); 
        }); 
 
 
        window.addEventListener('show-add-role_has_permissions-modal', event =>{ 
            $('#addRole_has_permissionsModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-edit-role_has_permissions-modal', event =>{ 
            $('#editRole_has_permissionsModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-delete-confirmation-role_has_permissions-modal', event =>{ 
            $('#deleteRole_has_permissionsModal').modal('show'); 
        }); 
 
</script>