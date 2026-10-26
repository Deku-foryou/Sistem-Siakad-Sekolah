<div wire:ignore.self class="modal fade" id="iconModal" aria-labelledby="iconModalLabel" aria-hidden="true"
    tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="iconModalLabel">List Icon</h1>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="searchIcons"> Search </label>
                    <input type="text" class="form-control mb-1 mb-lg-0 " placeholder="Search Icons"
                        wire:model.live.debounce.500ms="searchIcons">
                </div>
                <div>
                    <div class="row">
                        @foreach($dataIcons as $image)
                        <div class="col-lg-3 col-4 mb-3 ">
                            <div class="icon_box" style="cursor: pointer">
                                <div class="card px-3 py-2" wire:click="selectIcon('{{ basename($image) }}')">
                                    <div class="d-flex justify-content-start align-items-center gap-3">
                                        <div>
                                            <img src="{{ asset('asset_offline/icon/' . basename($image)) }}"
                                                class="icon_page">
                                        </div>
                                        <div class="text-secondary" style="font-size: 12px">
                                            {{ Str::limit(basename($image), 15) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    {{ $dataIcons->links() }}
                </div>
            </div>
        </div>
    </div>
</div>