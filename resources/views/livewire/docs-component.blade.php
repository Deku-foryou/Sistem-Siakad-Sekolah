@section('title')
    Data Buat Dokumentasi
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Buat Dokumentasi</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                        mengelola data Buat Dokumentasi, termasuk menambahkan atau mengubah informasi.
                    </div>
                </div>
                @if (in_array(auth()->user()?->level, ['superadmin', 'admin'], true))
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-docs-modal')"><i
                            class="fa-solid fa-plus "></i></button>
                </div>
                @endif
            </div>
        </div>


        {{-- Loader --}}
        <div wire:loading wire:target="exportToExcel, exportToPdf, search, tglstart, tglend" class="loading-spinner">
            <div class="loader"></div>
        </div>
        {{-- Loader --}}


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
                    @if (in_array(auth()->user()?->level, ['superadmin', 'admin'], true))
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
                    @endif
                </div>

            </div>
        </div>
    </div>
    <div class="row">
            <div class="col-md-12">

                <div class="card mb-2 pb-0 border-0">
                    <div class="card-body p-0">



                        <div class="table-responsive">
                            <table class="table " style="width:100%;white-space:nowrap">
                                <thead class="text-black" style="background: #ECECEC !important">

                                    <tr>
                                        <th class="text-center" style="width: 3%">No</th>
                                        <th style="width: 100px;text-align: center">Action</th>
                                        <th>Judul</th>
                                        <th>Url / Konten</th>
                                        <th>Urut</th>
                                        <th>Kategori Dokumentasi</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($docss->count() > 0)
                                        @foreach ($docss as $key => $datas)
                                            <tr class="px-4">
                                                <td class="text-center">{{ $docss->firstItem() + $key }} </td>
                                                <td style="min-width: 90px;text-align: center">
                                                    @if (in_array(auth()->user()?->level, ['superadmin', 'admin'], true))
                                                    <button class="btn btn-sm btn-warning text-dark"
                                                        wire:click="editDocs('{{ $datas->iddocs }}')"><i
                                                            class="fa-regular fa-pen-to-square"></i></button>
                                                    <button class="btn btn-sm btn-danger text-white"
                                                        wire:click="deleteConfirmationDocs('{{ $datas->iddocs }}')"><i
                                                            class="fa-solid fa-trash-can"></i></button>
                                                    @endif
                                                </td>
                                                <td>{{ $datas->title }}</td>
                                                <td>
                                                    <a href="{{ asset('storage/tutorial-website/' . $datas->content) }}"
                                                        target="_blank">{{ $datas->content }}</a>
                                                </td>
                                                <td>{{ $datas->urut }}</td>
                                                <td>{{ $datas->docscat->docscat ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" style="text-align: center;"><small>Data Tidak
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
                            Showing {{ $docss->firstItem() }} to {{ $docss->lastItem() }}
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
                        {{ $docss->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addDocsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data Docs</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeDocs">

                        <input type="text" id="iddocs" class="form-control d-none" wire:model="iddocs">

                        <div class="form-group row mb-3">
                            <label for="title"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Judul</label>
                            <div class="col-12 col-lg-9">
                                <textarea type="text" id="title" class="form-control" wire:model="title" placeholder="Masukkan title"> </textarea>
                                @error('title')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="content"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">content</label>
                            <div class="col-12 col-lg-9">
                                <input type="file" id="content" class="form-control" wire:model="content"
                                    placeholder="Masukkan content">
                                @error('content')
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

                        <div class="form-group row mb-3">
                            <label for="docscat_iddocscat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Kategori
                                Dokumentasi</label>
                            <div class="col-12 col-lg-9">
                                <select class="form-select" id="docscat_iddocscat" name="docscat_iddocscat"
                                    wire:model="docscat_iddocscat" placeholder="Masukkan docscat_iddocscat">
                                    <option value="" selected>Pilih Kategori Dokumentasi</option>
                                    {!! $docscatOption !!}
                                </select>
                                @error('docscat_iddocscat')
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

    <div wire:ignore.self class="modal fade" id="deleteDocsModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteDocs()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal"
                        aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editDocsModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Docs</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateDocs">

                        <input type="text" id="iddocs" class="form-control d-none" wire:model="iddocs">

                        <div class="form-group row mb-3">
                            <label for="title"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Judul</label>
                            <div class="col-12 col-lg-9">
                                <textarea type="text" id="title" class="form-control" wire:model="title" placeholder="Masukkan title"> </textarea>
                                @error('title')
                                    <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="content"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">content</label>
                            <div class="col-12 col-lg-9">
                                <input type="file" id="content" class="form-control" wire:model="content"
                                    placeholder="Masukkan content">
                                @error('content')
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

                        <div class="form-group row mb-3">
                            <label for="docscat_iddocscat"
                                class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Kategori
                                Dokumentasi</label>
                            <div class="col-12 col-lg-9">
                                <select class="form-select" id="docscat_iddocscat" name="docscat_iddocscat"
                                    wire:model="docscat_iddocscat" placeholder="Masukkan docscat_iddocscat">
                                    <option value="" selected>Pilih Kategori Dokumentasi</option>
                                    {!! $docscatOption !!}
                                </select>
                                @error('docscat_iddocscat')
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

    {{--     @include('livewire.docs-lov') --}}


</div>



<script>
    window.addEventListener('close-docs-modal', event => {
        $('#addDocsModal').modal('hide');
        $('#editDocsModal').modal('hide');
        $('#deleteDocsModal').modal('hide');
    });


    // copy dan aktifkan jika ingin menggunakan LOV  

    //       window.addEventListener('close-modal-lov', event =>{ 
    //          $('#docsModal').modal('hide'); 
    //     }); 


    // $(document).ready(function(){ 
    //      $('#btndocsModalLovAdd').click(function(){ 
    //       $('#docsModal').modal('show'); 
    //    }); 
    //  }); 


    // $(document).ready(function(){ 
    //      $('#btndocsModalLovEdit').click(function(){ 
    //       $('#docsModal').modal('show'); 
    //    }); 
    //  }); 


    $('#addDocsModal').on('shown.bs.modal', function() {
        $("id input form pertama").focus();
    });


    window.addEventListener('show-add-docs-modal', event => {
        $('#addDocsModal').modal('show');
    });


    window.addEventListener('show-edit-docs-modal', event => {
        $('#editDocsModal').modal('show');
    });


    window.addEventListener('show-delete-confirmation-docs-modal', event => {
        $('#deleteDocsModal').modal('show');
    });
</script>
