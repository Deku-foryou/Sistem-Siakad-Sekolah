<div class="container">
    <div class="row">
        <div class="d-flex align-items-center justify-content-between gap-3">

            <!-- Dropdown for items per page -->
            <select wire:change="updatePerPage($event.target.value, '{{ $keyPagination }}')"
                class="form-select form-select-sm w-auto me-2">
                <option value="10" @if (($perPage ?? 10) == 10) selected @endif>10</option>
                <option value="20" @if (($perPage ?? 10) == 20) selected @endif>20</option>
                <option value="50" @if (($perPage ?? 10) == 50) selected @endif>50</option>
                <option value="100" @if (($perPage ?? 10) == 100) selected @endif>100</option>
                <option value="{{ $dataPagination['pagination']['total'] ?? 0 }}"
                    @if (($perPage ?? 10) == ($dataPagination['pagination']['total'] ?? 0)) selected @endif>
                    All
                </option>
            </select>

            <!-- Display Items Info -->
            <div class="text-end mt-2 fw-light d-lg-block d-none" style="font-size: 0.8rem">
                Displaying
                {{ (($dataPagination['pagination']['current_page'] ?? 1) - 1) *
                    ($dataPagination['pagination']['per_page'] ?? 10) +
                    1 }}
                to
                {{ min(
                    ($dataPagination['pagination']['current_page'] ?? 1) * ($dataPagination['pagination']['per_page'] ?? 10),
                    $dataPagination['pagination']['total'] ?? 0,
                ) }}
                of
                {{ $dataPagination['pagination']['total'] ?? 0 }} items
            </div>

            <!-- Pagination Controls -->
            <div class="d-flex align-items-center">
                <!-- First Page -->
                <button wire:click="changePage('first', '{{ $keyPagination }}')" class="btn btn-sm border me-1"
                    @if (($dataPagination['pagination']['current_page'] ?? 1) == 1) disabled @endif>
                    &#171;
                </button>

                <!-- Previous Page -->
                <button wire:click="changePage('previous', '{{ $keyPagination }}')" class="btn btn-sm border me-1"
                    @if (($dataPagination['pagination']['current_page'] ?? 1) == 1) disabled @endif>
                    &#8249;
                </button>

                <!-- Page Input -->
                <div class="input-group input-group-sm me-1 px-3">
                    <input type="number" class="form-control"
                        wire:change="updatePage($event.target.value, '{{ $keyPagination }}')"
                        value="{{ min(
                            max(1, $dataPagination['pagination']['current_page'] ?? 1),
                            $dataPagination['pagination']['last_page'] ?? 1,
                        ) }}"
                        min="1" max="{{ $dataPagination['pagination']['last_page'] ?? 1 }}">
                    <span class="input-group-text">/ {{ $dataPagination['pagination']['last_page'] ?? 1 }}</span>
                </div>

                <!-- Next Page -->
                <button wire:click="changePage('next', '{{ $keyPagination }}')" class="btn btn-sm border me-1"
                    @if (($dataPagination['pagination']['current_page'] ?? 1) == ($dataPagination['pagination']['last_page'] ?? 1)) disabled @endif>
                    &#8250;
                </button>

                <!-- Last Page -->
                <button wire:click="changePage('last', '{{ $keyPagination }}')" class="btn btn-sm border me-1"
                    @if (($dataPagination['pagination']['current_page'] ?? 1) == ($dataPagination['pagination']['last_page'] ?? 1)) disabled @endif>
                    &#187;
                </button>

                <!-- Reload Button -->
                <button wire:click="reloadPage('{{ $keyPagination }}')" class="btn btn-sm border">
                    &#x21bb;
                </button>
            </div>
        </div>
    </div>
</div>
