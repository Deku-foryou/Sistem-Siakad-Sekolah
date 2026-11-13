@section('title')
Data Paket Menu
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Paket Menu</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                        mengelola data Paket Menu, termasuk menambahkan atau mengubah informasi.
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-rolecat-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data" aria-label="Tambah data"><i
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
                            <table class="table   table-bordered table-striped " style="width:100%;white-space:nowrap">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 3%">No</th>
                                        <th style="width: 100px;text-align: center">Action</th>
                                        {{-- <th>Idrolecat</th> --}}
                                        <th>Paket Menu</th>
                                        <th>Menu</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($rolecats->count() > 0)
                                    @foreach ($rolecats as $key => $datas)
                                    <tr class="px-4">
                                        <td class="text-center">{{ $rolecats->firstItem() + $key }} </td>
                                        <td style="min-width: 90px;text-align: center">
                                            <a class="btn btn-sm text-white bg-success"
                                                href="/role/{{ $datas->idrolecat }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Kelola / atur modul" aria-label="Kelola / atur modul"><i
                                                    class="fa-solid fa-wrench"></i></a>
                                            <button class="btn btn-sm btn-warning text-dark"
                                                wire:click="editRolecat('{{ $datas->idrolecat }}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data" aria-label="Ubah data"><i
                                                    class="fa-regular fa-pen-to-square"></i></button>
                                            <button class="btn btn-sm btn-danger text-white"
                                                wire:click="deleteConfirmationRolecat('{{ $datas->idrolecat}}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)" aria-label="Hapus data (ada konfirmasi)"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                        {{-- <td>{{ $datas->idrolecat }}</td> --}}
                                        <td>{{ $datas->rolecat }}</td>

                                        <td>

                                            @php
                                            $appMenuCount = App\Models\Appmenunew::count();
                                            $role = App\Models\Role::with(['rolecat',
                                            'appmenunew'])->where('rolecat_idrolecat', $datas->idrolecat)->get();

                                            @endphp


                                            @if ($role->isEmpty())
                                            <div class="text-danger" style="font-size: 11px">Belum ada Roles yang
                                                dipilih</div>
                                            @else


                                            @foreach ($role as $items)

                                            @if ($role->count() == $appMenuCount)
                                            <div class=" badge fw-medium"
                                                style="font-size: 11px; background: #E5FFF1;border:1px solid #a5e7c3;color:#0d743a;">
                                                --
                                                Semua Menu
                                                --</div>
                                            @break
                                            @else
                                            <div class="d-inline-flex badge fw-medium"
                                                style='background: #f6e5ff;color:#9435dc;border:1px solid #d8a4f5;'>{{
                                                $items->appmenunew->appmenunew }}</div>
                                            @endif





                                            @endforeach
                                            @endif


                                        </td>


                                    </tr>
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan="3" style="text-align: center;"><small>Data Tidak ditemukan</small>
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
                            Showing {{ $rolecats->firstItem() }} to {{ $rolecats->lastItem() }} of {{
                            $rolecats->total() }} results
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="{{ $rolecats->total() }}">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-6 d-flex justify-content-end align-items-center">
                        {{ $rolecats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>





    {{-- Gunakan button berikut dan inclue berikut untuk menggunakan LOV --}}

    {{-- Button LOV modal --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="rolecatName" wire:model="rolecatName" readonly>
        <button type="button" class="btn btn-primary" id="btnrolecatModalLovAdd" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari" aria-label="Cari">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}


    {{-- Button LOV modal untuk Edit --}}
    {{-- <div class="d-flex justify-content-between align-items-center gap-1">
        <input class="form-control" id="rolecatName" wire:model="rolecatName" readonly>
        <button type="button" class="btn btn-primary" id="btnrolecatModalLovEdit" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari" aria-label="Cari">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div> --}}




    <!-- Tambah Modal-->
    <div wire:ignore.self class="modal fade" id="addrolecatModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Menambah Data rolecat</h5>
                </div>
                <div class="modal-body">



                    <form wire:submit.prevent="storeRolecat">
                        {{-- <div class="form-group row mb-3">
                            <label for="idrolecat" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">idrolecat</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="idrolecat" class="form-control" wire:model="idrolecat">
                                @error('idrolecat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="form-group row mb-3">
                            <label for="rolecat" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Nama Paket
                                Menu</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="rolecat" class="form-control" wire:model="rolecat">
                                @error('rolecat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                        <span wire:loading.remove>
                            Simpan
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

    <div wire:ignore.self class="modal fade" id="deleterolecatModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteRolecat()" data-bs-toggle="tooltip" data-bs-placement="top" title="Ya, lanjutkan">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}

    <div wire:ignore.self class="modal fade" id="editrolecatModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit rolecat</h5>
                </div>
                <div class="modal-body">

                    <form wire:submit.prevent="updateRolecat">
                        {{-- <div class="form-group row mb-3">
                            <label for="idrolecat" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">idrolecat</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="idrolecat" class="form-control" wire:model="idrolecat">
                                @error('idrolecat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="form-group row mb-3">
                            <label for="rolecat" class="col-12 col-lg-3 fw-bold text-lg-end mb-2 mb-lg-0 label">Nama Paket
                                Menu</label>
                            <div class="col-12 col-lg-9">
                                <input type="text" id="rolecat" class="form-control" wire:model="rolecat">
                                @error('rolecat')
                                <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn  btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Tunggu...</span>
                                <span wire:loading.remove>
                                    Simpan Perubahan
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
    window.addEventListener('close-rolecat-modal', event =>{ 
            $('#addrolecatModal').modal('hide'); 
            $('#editrolecatModal').modal('hide'); 
            $('#deleterolecatModal').modal('hide'); 
        }); 
 
 
       $('#addrolecatModal').on('shown.bs.modal', function () { 
            $("id input form pertama").focus(); 
        }); 
 
 
        window.addEventListener('show-add-rolecat-modal', event =>{ 
            $('#addrolecatModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-edit-rolecat-modal', event =>{ 
            $('#editrolecatModal').modal('show'); 
        }); 
 
 
        window.addEventListener('show-delete-confirmation-rolecat-modal', event =>{ 
            $('#deleterolecatModal').modal('show'); 
        }); 
 
</script>