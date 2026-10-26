@section('title')
Data Model has Roles
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="mb-3 mb-lg-0">
                    <p class='p-0 m-0 h3 fw-bold'>Pengaturan Grup Role {{ $rolecat->rolecat }}</p>
                    <div class="mt-2" style="color: #868686"> <i
                            class="fa-regular fa-circle-question me-1 text-danger"></i>
                        Tentukan role apa saja yang akan dibuatkan grub yang akan diakses oleh masing - masing pengguna
                        yang ada.</div>
                </div>
                <div>
                    {{-- <button class="btn btn-primary" wire:click="$dispatch('show-add-usermenus-modal')"><i
                            class="fa-solid fa-plus me-2 "></i> Tambah Data usermenus</button> --}}
                    <a class='btn btn-primary' href="/rolecat">
                        <i class="fa-solid fa-arrow-left-long me-1"></i>
                        Kembali</a>
                </div>
            </div>
        </div>


        {{-- @dump($role_id) --}}

        <div class="row">

            <div class="col-lg-7">
                <form wire:submit.prevent='tambahMenuRole'>

                    <div class="col-12 mb-3">
                        <button type='submit' class='btn btn-primary btn-sm' wire:loading.attr='disabled'>
                            <span wire:loading>
                                <span class='spinner-border spinner-border-sm'></span> Loading...</span>
                            <span wire:loading.remove>
                                <i class="fa-regular fa-floppy-disk me-2"></i> Simpan
                            </span>
                        </button>
                    </div>



                    <div class="mb-2 fw-medium">List Menu<span class="text-danger">*</span></div>

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
                                        wire:model.lazy='role_id' id="role_{{ $datas->idappmenunew }}">


                                    <label class="form-check-label" for="role_{{ $datas->idappmenunew }}">
                                        {{ $datas->appmenunew }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                </form>
            </div>

            <div class="col-lg-5">
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
                    <div class=" col-lg-12 d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{
                            $roles->total()
                            }} results
                        </div>
                        <div>
                            Per Page
                            <select name="form-control" wire:model.live='perPage'>
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="{{ $roles->total() }}">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 col-lg-12 d-flex justify-content-end align-items-center">
                        {{ $roles->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>






    {{-- Delete Confirm --}}

    <div wire:ignore.self class="modal fade" id="deleterolemenusModal" tabindex="-1" data-bs-backdrop="static"
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
                    <button class="btn  btn-danger" id="confirmDeleteButton" wire:click="deleteRolemenu()">Ya</button>
                    <button type="button" class=" btn-secondary btn" data-bs-dismiss="modal"
                        aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>




    {{-- @include('livewire.usermenus-lov') --}}


</div>



<script>
    window.addEventListener('close-usermenus-modal', event =>{ 
            $('#deleterolemenusModal').modal('hide'); 
        }); 

 
 
        window.addEventListener('show-delete-confirmation-rolemenus-modal', event =>{ 
            $('#deleterolemenusModal').modal('show'); 
        }); 
 
</script>