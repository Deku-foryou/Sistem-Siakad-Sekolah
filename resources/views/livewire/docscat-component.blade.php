@section('title')
    Data Kategori Dokumentasi
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Kategori Dokumentasi</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                        mengelola data Kategori Dokumentasi, termasuk menambahkan atau mengubah informasi.
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-docscat-modal')"><i
                            class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>


        {{-- Loader --}}
        <div wire:loading wire:target="exportToExcel, exportToPdf, search, tglstart, tglend" class="loading-spinner">
            <div class="loader"></div>
        </div>
        {{-- Loader --}}



        <div class="row">
            <div class="col-md-12">

                <div class="card mb-2 pb-0 border-0">
                    <div class="card-body p-0">



                        <div class="table-responsive">
                            <table class="table " style="width:100%;white-space:nowrap;">
                                <thead class="text-black" style="background: #ECECEC !important">
                                    <tr>
                                        <th class="text-center" style="width: 3%">No</th>
                                        <th style="width: 100px;text-align: center">Action</th>
                                        <th>Kategori Dokumentasi</th>
                                        <th>Urut</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($docscats->count() > 0)
                                        @foreach ($docscats as $key => $datas)
                                            <tr class="px-4">
                                                <td class="text-center">{{ $docscats->firstItem() + $key }} </td>
                                                <td style="min-width: 90px;text-align: center">
                                                    <button class="btn btn-sm btn-warning text-dark"
                                                        wire:click="editDocscat('{{ $datas->iddocscat }}')"><i
                                                            class="fa-regular fa-pen-to-square"></i></button>
                                                    <button class="btn btn-sm btn-danger text-white"
                                                        wire:click="deleteConfirmationDocscat('{{ $datas->iddocscat }}')"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                </td>
                                                <td>{{ $datas->docscat }}</td>
                                                <td>{{ $datas->urut }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" style="text-align: center;"><small>Data Tidak
                                                    ditemukan</small></td>
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
                            Showing {{ $docscats->firstItem() }} to {{ $docscats->lastItem() }}
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="10000">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-6 d-flex justify-content-end align-items-center">
                        {{ $docscats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addDocscatModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Docscat</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeDocscat">

                        <input type="text" id="iddocscat" class="form-control d-none" wire:model="iddocscat">

                        <div class="form-group row mb-3">
                            <label for="docscat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">docscat</label>
                            <div class="col-12 col-lg-9">
                                <textarea type="text" id="docscat" class="form-control" wire:model="docscat" placeholder="Masukkan docscat"> </textarea>
                                @error('docscat')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="urut"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">urut</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="urut" class="form-control" wire:model="urut"
                                    placeholder="Masukkan urut">
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

    <div wire:ignore.self class="modal fade" id="deleteDocscatModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteDocscat()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal"
                        aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editDocscatModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Docscat</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateDocscat">

                        <input type="text" id="iddocscat" class="form-control d-none" wire:model="iddocscat">

                        <div class="form-group row mb-3">
                            <label for="docscat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">docscat</label>
                            <div class="col-12 col-lg-9">
                                <textarea type="text" id="docscat" class="form-control" wire:model="docscat" placeholder="Masukkan docscat"> </textarea>
                                @error('docscat')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="urut"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">urut</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="urut" class="form-control" wire:model="urut"
                                    placeholder="Masukkan urut">
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
                            <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal"
                                aria-label="Close" wire:click="close()">Close</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>


    </div>

    {{--     @include('livewire.docscat-lov') --}}


</div>



<script>
    window.addEventListener('close-docscat-modal', event => {
        $('#addDocscatModal').modal('hide');
        $('#editDocscatModal').modal('hide');
        $('#deleteDocscatModal').modal('hide');
    });


    // copy dan aktifkan jika ingin menggunakan LOV  

    //       window.addEventListener('close-modal-lov', event =>{ 
    //          $('#docscatModal').modal('hide'); 
    //     }); 


    // $(document).ready(function(){ 
    //      $('#btndocscatModalLovAdd').click(function(){ 
    //       $('#docscatModal').modal('show'); 
    //    }); 
    //  }); 


    // $(document).ready(function(){ 
    //      $('#btndocscatModalLovEdit').click(function(){ 
    //       $('#docscatModal').modal('show'); 
    //    }); 
    //  }); 


    $('#addDocscatModal').on('shown.bs.modal', function() {
        $("id input form pertama").focus();
    });


    window.addEventListener('show-add-docscat-modal', event => {
        $('#addDocscatModal').modal('show');
    });


    window.addEventListener('show-edit-docscat-modal', event => {
        $('#editDocscatModal').modal('show');
    });


    window.addEventListener('show-delete-confirmation-docscat-modal', event => {
        $('#deleteDocscatModal').modal('show');
    });
</script>
