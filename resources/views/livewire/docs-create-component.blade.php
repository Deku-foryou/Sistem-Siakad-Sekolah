@section('title')
    Create Data Article
@endsection
@push('css')
    <style>
        .note-editable {
            background-color: #f5f5f5;
        }
    </style>
@endpush
<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0">
                    <p class="p-0 m-0 h3 fw-bold ">Buat Dokumentasi</p>
                    <div class="mt-2" style="color: #868686">
                        <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                        menambah data Article.
                    </div>

                </div>
                <div>
                    <a class="btn btn-primary" href="/docs" style="background: #1464dc"><i
                            class="fa-solid fa-house"></i></a>
                </div>
            </div>
        </div>
        <div class="container-fluid px-0">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form wire:submit.prevent="storeDocs">
                                <div class="form-group">
                                    <label for="title" class=" fw-bold mb-2 mb-lg-0">Judul</label>
                                    <div class="mt-2 mb-2">
                                        <input type=" text" id="title" class="form-control" wire:model="title">
                                        @error('title')
                                            <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="docscat_iddocscat" class=" fw-bold mb-2 mb-lg-0">Kategori</label>
                                    <div class="mt-2 mb-2">
                                        <select id="docscat_iddocscat" class="form-select" wire:model="docscat_iddocscat">
                                            <option value="">-- Pilih Kategori --</option>
                                            @foreach ($kategori as $item)
                                                <option value="{{ $item->iddocscat }}">{{ $item->docscat }}</option>
                                            @endforeach
                                        </select>
                                        @error('docscat_iddocscat')
                                            <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="urut" class=" fw-bold mb-2 mb-lg-0">Urutan</label>
                                    <div class="mt-2 mb-2">
                                        <input type="number" min="1" id="urut" class="form-control" wire:model="urut">
                                        @error('urut')
                                            <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mb-2">
                                    <label for="content" class=" fw-bold mb-2 mb-lg-0">Isi (Dokumentasi)</label>
                                    <div class="mt-2">
                                        {{-- <div wire:ignore>
                                            <textarea class=" form-control" id="content" wire:model="content"></textarea>
                                        </div> --}}
                                        <div>
                                            <input type="file" class=" form-control" id="content"
                                                wire:model="content" accept="application/pdf,.pdf">
                                        </div>
                                        @error('content')
                                            <span class="text-danger" style="font-size: 11.5px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>


                        </div>
                        <div class="card-footer">
                            <button type="submit" id="my-submit" class="btn"
                                style="background: #1464dc; color:white" wire:loading.attr="disabled">
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm"></span> Loading...</span>
                                <span wire:loading.remove>
                                    Simpan
                                </span>
                            </button>
                            <a href="/docs" class=" btn-secondary btn">Close</a>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <script>
        $(function() {
            $('#content').summernote({
                tabsize: 2,
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['fontsize', ['fontsize']], // Menambahkan ukuran font
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                fontsize: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '22', '24', '28', '32', '36',
                    '48'
                ],
                fontsizeUnit: 'px', // Menentukan satuan ukuran font (px, pt, em)
                callbacks: {
                    onChange: function(contents, $editable) {
                        @this.set('content', contents);
                        $('.note-editable').css('background-color', '#f5f5f5');
                    }
                }
            });
        })
    </script> --}}
