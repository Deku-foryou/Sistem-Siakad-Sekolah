@section('title')
Data Kelola Menu
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Kelola Menu</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                        mengelola data Kelola Menu, termasuk menambahkan atau mengubah informasi.
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-appmenu-modal')"><i
                            class="fa-solid fa-plus "></i></button>
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
                            <table class="table   table-bordered table-striped " style="width:100%;white-space:nowrap">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 3%">No</th>
                                        <th style="width: 100px;text-align: center">Action</th>
                                        {{-- <th>Idappmenunew</th> --}}
                                        <th class="text-center" style="width: 40px">Icon</th>
                                        <th>Menu</th>
                                        <th>Url (Nama Di Databases)</th>
                                        <th>Urut</th>
                                        <th>Kategori Menu</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($appmenus->count() > 0)
                                    @foreach ($appmenus as $key => $datas)
                                    <tr class="px-4">
                                        <td class="text-center">{{ $appmenus->firstItem() + $key }} </td>
                                        <td style="min-width: 90px;text-align: center">
                                            <button class="btn btn-sm btn-warning text-dark"
                                                wire:click="editAppmenu('{{ $datas->idappmenunew }}')"><i
                                                    class="fa-regular fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-danger text-white"
                                                wire:click="deleteConfirmationAppmenu('{{ $datas->idappmenunew}}')"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                        {{-- <td>{{ $datas->idappmenunew }}</td> --}}
                                        <td class="text-center">
                                            <img src="{{ asset('asset_offline/icon/' . $datas->icon) }}"
                                                class="icon_page">
                                        </td>
                                        <td>{{ $datas->appmenunew }}</td>
                                        <td>{{ $datas->url }}</td>
                                        <td>{{ $datas->urut }}</td>
                                        <td>{{ $datas->appmenucat->appmenucat ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan="6" style="text-align: center;"><small>Data Tidak ditemukan</small>
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
                            Showing {{ $appmenus->firstItem() }} to {{ $appmenus->lastItem() }} of {{ $appmenus->total()
                            }} results
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="{{ $appmenus->total() }}">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-6 d-flex justify-content-end align-items-center">
                        {{ $appmenus->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="appmenuName" wire:model="appmenuName" readonly>
        <button type="button" class="btn btn-primary" id="btnappmenuModalLovAdd">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="appmenuName" wire:model="appmenuName" readonly>
        <button type="button" class="btn btn-primary" id="btnappmenuModalLovEdit">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addAppmenuModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Kelola Menu</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeAppmenu">
                        {{-- <div class="form-group row mb-3">
                            <label for="idappmenunew"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">idappmenunew</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="idappmenunew" class="form-control" wire:model="idappmenunew">
                                @error('idappmenunew')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="form-group row mb-3">
                            <label for="appmenunew" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Menu</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="appmenunew" class="form-control" wire:model="appmenunew">
                                @error('appmenunew')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="url" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Url </label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="url" class="form-control" wire:model="url">
                                <span class="text-danger">*Wajib Sesuai
                                    Nama Databases.</span>
                                @error('url')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>


                        <div class="form-group row mb-3">
                            <label for="icon" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Icon</label>
                            <div class="col-12 col-lg-9">


                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <input type="text" id="icon" class="form-control" wire:model="icon"
                                        placeholder="Contoh : <i class=fa-regular .....">
                                    <button type="button" class="btn btn-primary" id="btniconModalLovAdd">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>

                                {{-- <span class="text-danger">*Code copy dari Font Awesome.</span> --}}
                                @error('icon')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>



                        <div class="form-group row mb-3">
                            <label for="urut" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Urut</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="urut" class="form-control" wire:model="urut">
                                @error('urut')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="appmenucat_idappmenucat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Kategori Menu</label>
                            <div class="col-12 col-lg-9">

                                <select name="appmenucat_idappmenucat" class="form-select"
                                    wire:model="appmenucat_idappmenucat">
                                    <option value="">Pilih Kategori</option>
                                    {!! $appmenucatOption !!}
                                </select>

                                @error('appmenucat_idappmenucat')
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
                            Simpan
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

    <div wire:ignore.self class="modal fade" id="deleteAppmenuModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteAppmenu()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editAppmenuModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kelola Menu</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateAppmenu">
                        {{-- <div class="form-group row mb-3">
                            <label for="idappmenunew"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">idappmenunew</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="idappmenunew" class="form-control" wire:model="idappmenunew">
                                @error('idappmenunew')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="form-group row mb-3">
                            <label for="appmenunew" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Menu</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="appmenunew" class="form-control" wire:model="appmenunew">
                                @error('appmenunew')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="url" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Url</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="url" class="form-control" wire:model="url">
                                <span class="text-danger">*Wajib Sesuai
                                    Nama Databases.</span>
                                @error('url')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="icon" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Icon</label>
                            <div class="col-12 col-lg-9">


                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <input type="text" id="icon" class="form-control" wire:model="icon"
                                        placeholder="Contoh : <i class=fa-regular .....">
                                    <button type="button" class="btn btn-primary" id="btniconModalLovEdit">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>

                                {{-- <span class="text-danger">*Code copy dari Font Awesome.</span> --}}
                                @error('icon')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>



                        <div class="form-group row mb-3">
                            <label for="urut" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Urut</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="urut" class="form-control" wire:model="urut">
                                @error('urut')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="appmenucat_idappmenucat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Kategori Menu</label>
                            <div class="col-12 col-lg-9">
                                <select name="appmenucat_idappmenucat" class="form-select"
                                    wire:model="appmenucat_idappmenucat">
                                    <option value="">Pilih Kategori</option>
                                    {!! $appmenucatOption !!}
                                </select>
                                @error('appmenucat_idappmenucat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Tunggu...</span>
                                <span wire:loading.remove>
                                    Simpan Perubahan
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

    {{-- @include('livewire.appmenu-lov') --}}
    @include('livewire.icon-lov')


</div>



<script>
    window.addEventListener('close-appmenu-modal', event =>{ 
            $('#addAppmenuModal').modal('hide'); 
            $('#editAppmenuModal').modal('hide'); 
            $('#deleteAppmenuModal').modal('hide'); 
        }); 
 
 
  // copy dan aktifkan jika ingin menggunakan LOV  
 
  //       window.addEventListener('close-modal-lov', event =>{ 
   //          $('#appmenuModal').modal('hide'); 
   //     }); 
 
 
  // $(document).ready(function(){ 
   //      $('#btnappmenuModalLovAdd').click(function(){ 
   //       $('#appmenuModal').modal('show'); 
    //    }); 
  //  }); 
 
 
  // $(document).ready(function(){ 
   //      $('#btnappmenuModalLovEdit').click(function(){ 
   //       $('#appmenuModal').modal('show'); 
    //    }); 
  //  }); 


//   icon LOV -----------------------------------------------------------
    window.addEventListener('close-modal-lov', event =>{
        $('#iconModal').modal('hide');
    });

    $(document).ready(function(){
        $('#btniconModalLovAdd').click(function(){
            $('#iconModal').modal('show');
        });
    });

    $(document).ready(function(){
        $('#btniconModalLovEdit').click(function(){
            $('#iconModal').modal('show');
        });
    });
//  icon LOV -----------------------------------------------------------
 
 
       $('#addAppmenuModal').on('shown.bs.modal', function () { 
            $("id input form pertama").focus(); 
        }); 
 
 
        window.addEventListener('show-add-appmenu-modal', event =>{ 
            $('#addAppmenuModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-edit-appmenu-modal', event =>{ 
            $('#editAppmenuModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-delete-confirmation-appmenu-modal', event =>{ 
            $('#deleteAppmenuModal').modal('show'); 
        }); 
 
</script>