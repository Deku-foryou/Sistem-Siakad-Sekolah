<div wire:ignore.self class="modal fade" id="rolecatModal" aria-labelledby="rolecatModalLabel" aria-hidden="true"
    tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="rolecatModalLabel">rolecat Modal</h1>
            </div>
            <div class="modal-body">
                <div class="row pb-2">
                    <div class="col-6">
                        <div>Search</div>
                        <input type="text" class="form-control" placeholder="Cari data .."
                            wire:model.live.debounce.300ms="searchlov">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Action</th>
                                <th>Idrolecat</th>
                                <th>rolecat</th>

                            </tr>
                        </thead>
                        <tbody>
                            @if ($rolecats->count() > 0)
                            @foreach ($rolecats as $datas)
                            <tr class="px-4">
                                <td style="text-align: center;">
                                    <button class="btn btn-sm btn-danger text-white" type="button"
                                        wire:click="rolecatLOV({{ $datas->idrolecat }}, 'rolecat')"><i
                                            class="fa-solid fa-check"></i></button>
                                </td>
                                <td>{{ $datas->idrolecat }}</td>
                                <td>{{ $datas->rolecat }}</td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan=" 3 " style="text-align: center;"><small>Data Tidak di temukan</small></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class=" d-flex justify-content-between align-items-center">
                    <div>
                        Showing {{ $rolecats->firstItem() }} to {{ $rolecats->lastItem() }} of {{
                        $rolecats->total() }} results
                    </div>
                    <div class="mt-2">
                        {{ $rolecats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>