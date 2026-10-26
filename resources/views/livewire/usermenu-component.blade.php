@section('title')
    Data Status Kepemilikan Menu User
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">

        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="mb-3 mb-lg-0">
                    <p class='p-0 m-0 h3 fw-bold'>Status Kepemilikan Menu User {{ $user->name ?? '-' }}</p>
                    <div class="mt-2" style="color: #868686"> <i
                            class="fa-regular fa-circle-question me-1 text-danger"></i>
                        Tentukan role apa saja yang boleh diakses oleh masing - masing pengguna yang ada.</div>
                </div>
                <div>
                    {{-- <button class="btn btn-primary" wire:click="$dispatch('show-add-usermenus-modal')"><i
                            class="fa-solid fa-plus me-2 "></i> Tambah Data usermenus</button> --}}
                    <a class='btn btn-primary' href="/users">
                        <i class="fa-solid fa-arrow-left-long me-1"></i>
                        Kembali</a>
                </div>
            </div>
        </div>


        {{-- @dump($menu_id) --}}

        <div class="row">

            <div class="col-lg-7">
                <form wire:submit.prevent='tambahMenuUser'>

                    <div class="col-12 mb-3">
                        <button type='submit' class='btn btn-primary btn-sm' wire:loading.attr='disabled'>
                            <span wire:loading>
                                <span class='spinner-border spinner-border-sm'></span> Loading...</span>
                            <span wire:loading.remove>
                                <i class="fa-regular fa-floppy-disk me-2"></i> Simpan
                            </span>
                        </button>
                    </div>




                    {{-- Tambahan Role --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-content-center">
                            <div class="card-title fw-bold">Pilih Paket Menu</div>

                            @if ($rolecat_id)
                                <div>
                                    <button class='btn btn-danger btn-sm text-white' wire:loading.attr='disabled'
                                        wire:click='hapusPaket()' wire:ignore.self type="button">
                                        <span wire:loading>
                                            <span class='spinner-border spinner-border-sm'></span> Loading...</span>
                                        <span wire:loading.remove>
                                            <i class="fa-regular fa-trash-can me-2"></i>Hapus Paket
                                        </span>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">

                            <div class="alert alert-primary" role="alert">
                                <i class="fa-regular fa-bell me-2"></i> Pilih paket menu yang ingin ditambahkan, dengan
                                mengceklis menu.
                            </div>


                            <div class="row">
                                @foreach ($rolecat as $datas)

                                    <div class="col-6 mb-3 px-2">
                                        <div class="border p-2 fw-bold rounded-top d-flex justify-content-between align-items-center"
                                            style="background: #eff5fd">
                                            <div>
                                                <i class="fa-solid fa-folder-tree me-1" style="font-size: 13px"></i>
                                                {{$datas->rolecat}}
                                            </div>


                                            <div>
                                                <input class="form-check-input" type="checkbox"
                                                    value="{{ $datas->idrolecat }}" wire:model.lazy='rolecat_id'
                                                    id="rolecat_{{ $datas->idrolecat }}">
                                            </div>
                                        </div>
                                        <div class="border p-2 rounded-bottom" style="margin-top: -1px">
                                            @foreach ($role->where('rolecat_idrolecat', $datas->idrolecat) as $items)
                                                <div class="form-check mt-1">


                                                    <i class="fa-regular fa-folder-closed me-2"></i>
                                                    <label class="form-check-label">
                                                        {{ $items->appmenunew->appmenunew }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                    {{-- Tambahan Role --}}



                    @if(Auth::user()->level == 'superadmin')
                        <div class="mb-2 fw-medium">List Menu<span class="text-danger">*</span></div>

                        <div class="alert alert-success" role="alert">
                            <i class="fa-regular fa-bell me-2"></i>Dan Pilih menu satuan yang ingin ditambahkan,
                            dengan
                            mengceklis menu.
                        </div>

                        <div class="row">


                            @foreach ($appMenuCat as $items)


                                <div class="col-6 mb-3 px-2">
                                    <div class="border p-2 fw-bold rounded-top" style="background: #eff5fd"><i
                                            class="fa-solid fa-hashtag me-1" class="fw-bold" style="font-size: 10px"></i>
                                        {{$items->appmenucat}}

                                    </div>
                                    <div class="border p-2 rounded-bottom" style="margin-top: -1px">

                                        @foreach ($appmenu->where('appmenucat_idappmenucat', $items->idappmenucat) as $datas)
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" value="{{ $datas->idappmenunew }}"
                                                    wire:model.lazy='menu_id' id="menu_{{ $datas->idappmenunew }}">


                                                <label class="form-check-label" for="menu_{{ $datas->idappmenunew }}">
                                                    {{ $datas->appmenunew }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    @endif
                </form>
            </div>

            <div class="col-lg-5">
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
                                        style="font-size: 14px; box-shadow: none;"
                                        wire:model.live.debounce.300ms="search">
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <!-- Tombol Export Excel -->
                                <button class="btn btn-success text-white d-flex align-items-center gap-2 px-3"
                                    style="border-radius: 10px; font-size: 13px; font-weight: 600;"
                                    wire:click="exportToExcel">
                                    <i class="fa-solid fa-download"></i>
                                    <span>Excel</span>
                                </button>
                                <!-- Tombol Export PDF -->
                                <button class="btn btn-danger text-white d-flex align-items-center gap-2 px-3"
                                    style="border-radius: 10px; font-size: 13px; font-weight: 600;"
                                    wire:click="exportToPdf">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <span>PDF</span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-2" role="alert">
                    <i class="fa-regular fa-bell me-2"></i>Menu yang dimiliki User {{ $user->name ?? '-' }}
                </div>

                <!-- Table Card -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle" style="width:100%; margin-bottom: 0;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%; text-align: center">Action</th>
                                        <th>Menu</th>
                                        <th>URL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($usermenus->count() > 0)
                                        @foreach ($usermenus as $datas)
                                            <tr>
                                                <td style="width: 25%; text-align: center">
                                                    <button class="btn btn-sm btn-danger text-white"
                                                        wire:click="deleteConfirmationUsermenu('{{ $datas->idusermenu }}')"
                                                        style="border-radius: 8px;"
                                                        type="button">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    @if($datas->appmenunew)
                                                        <span class="d-flex align-items-center">
                                                            <i class="{{ $datas->appmenunew->icon ?? 'fa-solid fa-link' }} me-2 text-primary" style="font-size: 14px;"></i>
                                                            <span>{{ $datas->appmenunew->appmenunew }}</span>
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <code>{{ $datas->appmenunew->url ?? '-' }}</code>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                <i class="fa-regular fa-folder-open d-block mb-2" style="font-size: 24px;"></i>
                                                <small>Data Tidak ditemukan</small>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination & Per Page -->
                <div class="row">
                    <div class=" col-lg-12 d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $usermenus->firstItem() }} to {{ $usermenus->lastItem() }} of {{
                                $usermenus->total()
                            }} results
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="{{ $usermenus->total() }}">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-12 d-flex justify-content-end align-items-center">
                        {{ $usermenus->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="usermenusName" wire:model="usermenusName" readonly>
        <button type="button" class="btn btn-primary" id="btnusermenusModalLovAdd">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="usermenusName" wire:model="usermenusName" readonly>
        <button type="button" class="btn btn-primary" id="btnusermenusModalLovEdit">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}









    {{-- Delete Confirm --}}
    <div wire:ignore.self class="modal fade" id="deleteusermenusModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteUsermenu()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal"
                        aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Delete Confirm --}}
    <div wire:ignore.self class="modal fade" id="deletehapuspaketModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered  " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Paket Confirmation</h5>
                </div>
                <div class="modal-body pt-4 pb-4">
                    <h6>Are you sure? You want to delete this data ?</h6>
                </div>
                <div class="modal-footer">
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deletePaketRole()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal"
                        aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>




    {{-- @include('livewire.usermenus-lov') --}}


</div>



<script>
    window.addEventListener('close-usermenus-modal', event => {
        $('#addusermenusModal').modal('hide');
        $('#editusermenusModal').modal('hide');
        $('#deleteusermenusModal').modal('hide');
        $('#deletehapuspaketModal').modal('hide');
    });


    // copy dan aktifkan jika ingin menggunakan LOV  

    //       window.addEventListener('close-modal-lov', event =>{ 
    //          $('#usermenusModal').modal('hide'); 
    //     }); 


    // $(document).ready(function(){ 
    //      $('#btnusermenusModalLovAdd').click(function(){ 
    //       $('#usermenusModal').modal('show'); 
    //    }); 
    //  }); 


    // $(document).ready(function(){ 
    //      $('#btnusermenusModalLovEdit').click(function(){ 
    //       $('#usermenusModal').modal('show'); 
    //    }); 
    //  }); 


    $('#addusermenusModal').on('shown.bs.modal', function () {
        $("id input form pertama").focus();
    });


    window.addEventListener('show-add-usermenus-modal', event => {
        $('#addusermenusModal').modal('show');
    });


    window.addEventListener('show-edit-usermenus-modal', event => {
        $('#editusermenusModal').modal('show');
    });


    window.addEventListener('show-delete-confirmation-usermenus-modal', event => {
        $('#deleteusermenusModal').modal('show');
    });

    window.addEventListener('show-delete-confirmation-hapuspaket-modal', event => {
        $('#deletehapuspaketModal').modal('show');
    });

</script>