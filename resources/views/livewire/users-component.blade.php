@section('title')
    Data Users
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">

        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="mb-3 mb-lg-0">
                    <p class='p-0 m-0 h3 fw-bold'>Pengaturan Pengguna</p>
                    <div class="mt-2" style="color: #868686"> <i
                            class="fa-regular fa-circle-question me-1 text-danger"></i>Setting

                        role untuk
                        menentukan akses
                        terhadap
                        menu apa saja yang
                        akan
                        dilakukan seorang pengguna/user.</div>
                </div>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-users-modal')"><i
                            class="fa-solid fa-plus me-2 "></i> Tambah Data Users</button>
                </div>
            </div>
        </div>


        <!-- Loading Spinner -->
        <div wire:loading wire:target="selectedArticlecat,  exportToExcel, exportToPdf" class="loading-spinner">
            <div class="loader"></div>
        </div>
        <!-- Loading Spinner -->


        <!-- Action Toolbar Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                    <!-- Search Box -->
                    <div class="flex-grow-1" style="max-width: 450px;">
                        <div class="input-group"
                            style="border-radius: 10px; overflow: hidden; border: 1px solid #e9ecef;">
                            <span class="input-group-text bg-white border-0 ps-3">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-0 ps-2 py-2" placeholder="Cari..."
                                style="font-size: 14px; box-shadow: none;" wire:model.live.debounce.300ms="search">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Tombol Export Excel -->
                        <button class="btn btn-success text-white d-flex align-items-center gap-2 px-3"
                            style="border-radius: 10px; font-size: 13px; font-weight: 600;" wire:click="exportToExcel">
                            <i class="fa-solid fa-download"></i>
                            <span>Excel</span>
                        </button>
                        <!-- Tombol Export PDF -->
                        <button class="btn btn-danger text-white d-flex align-items-center gap-2 px-3"
                            style="border-radius: 10px; font-size: 13px; font-weight: 600;" wire:click="exportToPdf">
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
                                        <th style="width: 15%;text-align: center">Action</th>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($userss->count() > 0)
                                        @foreach ($userss as $datas)
                                            <tr class="px-4">
                                                <td style="width: 15%;text-align: center">
                                                    {{-- <a class="btn btn-sm text-white bg-success"
                                                        href="/model_has_roles/{{ $datas->id }}"><i
                                                            class="fa-solid fa-person-circle-question"></i></i></a> --}}
                                                    <a class="btn btn-sm text-white bg-success"
                                                        href="/usermenu/{{ $datas->id }}"><i class="fa-solid fa-wrench"></i></a>
                                                    <button class="btn btn-sm btn-warning text-dark"
                                                        wire:click="editUsers('{{ $datas->id }}')"><i
                                                            class="fa-regular fa-pen-to-square"></i></button>
                                                    <button class="btn btn-sm btn-danger text-white"
                                                        wire:click="deleteConfirmationUsers('{{ $datas->id}}')"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </td>
                                                <td>{{ $datas->username }}</td>
                                                <td>{{ $datas->name }}</td>
                                                <td>{{ $datas->email }}</td>
                                                <td>{{ $datas->level }} </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="7" style="text-align: center;"><small>Data Tidak ditemukan</small>
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
                            Showing {{ $userss->firstItem() }} to {{ $userss->lastItem() }} of {{ $userss->total() }}
                            results
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
                        {{ $userss->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" wire:model="usersName">
        <button type="button" class="btn btn-primary" id="btnusersModalLovAdd">
            LOV
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" wire:model="usersName">
        <button type="button" class="btn btn-primary" id="btnusersModalLovEdit">
            LOV
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addUsersModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Users</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeUsers">


                        <div class="form-group row mb-3">
                            <label for="username" class="col-12 col-lg-3 fw-bold text-lg-end">Username <span
                                    class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="username" class="form-control" wire:model="username">
                                @error('username')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="name" class="col-12 col-lg-3 fw-bold text-lg-end">Name <span
                                    class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="name" class="form-control" wire:model="name">
                                @error('name')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="email" class="col-12 col-lg-3 fw-bold text-lg-end">Email</label>
                            <div class="col-9">
                                <input type="text" id="email" class="form-control" wire:model="email">
                                @error('email')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="level"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">level</label>
                            <div class="col-12 col-lg-9">

                                <select name="level" class="form-select" wire:model="level">
                                    <option value="" selected>Pilih Akses Level Menu</option>
                                    <option value="superadmin">Superadmin</option>
                                    <option value="guru">Guru</option>
                                    <option value="siswa">Siswa</option>
                                </select>

                                @error('level')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>


                        <div class="form-group row mb-3">
                            <label for="password" class="col-12 col-lg-3 fw-bold text-lg-end">Password <span
                                    class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="password" class="form-control" wire:model="password">
                                @error('password')
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
                            Tambah Users
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

    <div wire:ignore.self class="modal fade" id="deleteUsersModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteUsers()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editUsersModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Users</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateUsers">


                        <div class="form-group row mb-3">
                            <label for="username" class="col-12 col-lg-3 fw-bold text-lg-end">Username <span
                                    class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="username" class="form-control" wire:model="username">
                                @error('username')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="name" class="col-12 col-lg-3 fw-bold text-lg-end">Name <span
                                    class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="name" class="form-control" wire:model="name">
                                @error('name')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="email" class="col-12 col-lg-3 fw-bold text-lg-end">Email</label>
                            <div class="col-9">
                                <input type="text" id="email" class="form-control" wire:model="email">
                                @error('email')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="level"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">level</label>
                            <div class="col-12 col-lg-9">

                                <select name="level" class="form-select" wire:model="level">
                                    <option value="" selected>Pilih Akses Level Menu</option>
                                    <option value="superadmin">Superadmin</option>
                                    <option value="guru">Guru</option>
                                    <option value="siswa">Siswa</option>
                                </select>

                                @error('level')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>



                        <div class="form-group row mb-3">
                            <label for="password" class="col-12 col-lg-3 fw-bold text-lg-end">Password <span
                                    class="text-danger">*</span></label>
                            <div class="col-9">
                                <input type="text" id="password" class="form-control" wire:model="password">
                                <div class="text-secondary mt-2" style="font-size: 10px"><i
                                        class="fa-solid fa-circle-exclamation me-1" style="font-size: 9px"></i> Jika
                                    Menggubah Data, Wajib
                                    Memasukkan Password Kembali!</div>
                                @error('password')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>



                        <div class="modal-footer">
                            <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                                <span wire:loading.remove>
                                    Update Users
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

    {{-- @include('livewire.users-lov') --}}


</div>



<script>
    window.addEventListener('close-users-modal', event => {
        $('#addUsersModal').modal('hide');
        $('#editUsersModal').modal('hide');
        $('#deleteUsersModal').modal('hide');
    });


    // copy dan aktifkan jika ingin menggunakan LOV  

    //       window.addEventListener('close-modal-lov', event =>{ 
    //          $('#usersModal').modal('hide'); 
    //     }); 


    // $(document).ready(function(){ 
    //      $('#btnusersModalLovAdd').click(function(){ 
    //       $('#usersModal').modal('show'); 
    //    }); 
    //  }); 


    // $(document).ready(function(){ 
    //      $('#btnusersModalLovEdit').click(function(){ 
    //       $('#usersModal').modal('show'); 
    //    }); 
    //  }); 



    window.addEventListener('show-add-users-modal', event => {
        $('#addUsersModal').modal('show');
        $('#addUsersModal').on('shown.bs.modal', function () {
            $("#name").focus();
        });
    });


    window.addEventListener('show-edit-users-modal', event => {
        $('#editUsersModal').modal('show');
    });


    window.addEventListener('show-delete-confirmation-users-modal', event => {
        $('#deleteUsersModal').modal('show');
    });

</script>