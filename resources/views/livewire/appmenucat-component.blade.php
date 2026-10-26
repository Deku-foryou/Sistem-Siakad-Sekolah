@section('title')
Data Kategori Menu
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Kategori Menu</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                        mengelola data Kategori Menu, termasuk menambahkan atau mengubah informasi.
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-appmenucat-modal')"><i
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
                                        <th>Kategori</th>
                                        <th>Urut</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($appmenucats->count() > 0)
                                    @foreach ($appmenucats as $key => $datas)
                                    <tr class="px-4">
                                        <td class="text-center">{{ $appmenucats->firstItem() + $key }} </td>
                                        <td style="min-width: 90px;text-align: center">
                                            <button class="btn btn-sm btn-warning text-dark"
                                                wire:click="editAppmenucat('{{ $datas->idappmenucat }}')"><i
                                                    class="fa-regular fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-danger text-white"
                                                wire:click="deleteConfirmationAppmenucat('{{ $datas->idappmenucat}}')"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                        <td>{{ $datas->appmenucat }}</td>
                                        <td>{{ $datas->urut }}</td>
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
                            Showing {{ $appmenucats->firstItem() }} to {{ $appmenucats->lastItem() }} of {{
                            $appmenucats->total() }} results
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
                        {{ $appmenucats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="appmenucatName" wire:model="appmenucatName" readonly>
        <button type="button" class="btn btn-primary" id="btnappmenucatModalLovAdd">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="appmenucatName" wire:model="appmenucatName" readonly>
        <button type="button" class="btn btn-primary" id="btnappmenucatModalLovEdit">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addAppmenucatModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Kategori Menu</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeAppmenucat">
                        {{-- <div class="form-group row mb-3">
                            <label for="idappmenucat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">idappmenucat</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="idappmenucat" class="form-control" wire:model="idappmenucat">
                                @error('idappmenucat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="form-group row mb-3">
                            <label for="appmenucat" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Kategori
                                Menu</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="appmenucat" class="form-control" wire:model="appmenucat">
                                @error('appmenucat')
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

    <div wire:ignore.self class="modal fade" id="deleteAppmenucatModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteAppmenucat()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editAppmenucatModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori Menu</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateAppmenucat">
                        {{-- <div class="form-group row mb-3">
                            <label for="idappmenucat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">idappmenucat</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="idappmenucat" class="form-control" wire:model="idappmenucat">
                                @error('idappmenucat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="form-group row mb-3">
                            <label for="appmenucat" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Kategori
                                Menu</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="appmenucat" class="form-control" wire:model="appmenucat">
                                @error('appmenucat')
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

    {{-- @include('livewire.appmenucat-lov') --}}


</div>



<script>
    window.addEventListener('close-appmenucat-modal', event =>{ 
            $('#addAppmenucatModal').modal('hide'); 
            $('#editAppmenucatModal').modal('hide'); 
            $('#deleteAppmenucatModal').modal('hide'); 
        }); 
 
 
  // copy dan aktifkan jika ingin menggunakan LOV  
 
  //       window.addEventListener('close-modal-lov', event =>{ 
   //          $('#appmenucatModal').modal('hide'); 
   //     }); 
 
 
  // $(document).ready(function(){ 
   //      $('#btnappmenucatModalLovAdd').click(function(){ 
   //       $('#appmenucatModal').modal('show'); 
    //    }); 
  //  }); 
 
 
  // $(document).ready(function(){ 
   //      $('#btnappmenucatModalLovEdit').click(function(){ 
   //       $('#appmenucatModal').modal('show'); 
    //    }); 
  //  }); 
 
 
       $('#addAppmenucatModal').on('shown.bs.modal', function () { 
            $("id input form pertama").focus(); 
        }); 
 
 
        window.addEventListener('show-add-appmenucat-modal', event =>{ 
            $('#addAppmenucatModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-edit-appmenucat-modal', event =>{ 
            $('#editAppmenucatModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-delete-confirmation-appmenucat-modal', event =>{ 
            $('#deleteAppmenucatModal').modal('show'); 
        }); 
 
</script>