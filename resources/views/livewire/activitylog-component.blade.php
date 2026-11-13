<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">

        <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
            <div class="mb-3 mb-lg-0">
                <p class="p-0 m-0 h3 fw-bold ">Activity Logs</p>
                <div class="mt-2" style="color: #868686">
                    <i class="fa-regular fa-circle-question me-1 text-danger"></i>Menu ini digunakan untuk
                    melihat aktivitas pengguna dalam aplikasi, seperti membuat, mengubah, atau menghapus data.
                </div>
            </div>
        </div>



        {{-- filter --}}
        <div class="py-2 mb-2">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="d-lg-flex align-items-center gap-2">
                    <div>
                        <input type="date" class="form-control rounded-5" wire:model.live.debounce.500ms="tglstart">
                    </div>
                    <div>
                        <input type="date" class="form-control rounded-5" wire:model.live.debounce.500ms="tglend">
                    </div>
                    <div>
                        <select class="form-select rounded-5" wire:model.live.debounce.500ms="selectedUser">
                            <option value="" selected> Semua User </option>
                            @foreach ($getUser as $user)
                                <option value="{{ $user->user_id }}">{{ $user->user_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select class="form-select rounded-5" wire:model.live.debounce.500ms="selectedAction">
                            <option value="" selected> Semua Aksi </option>
                            @foreach ($getAction as $action)
                                <option value="{{ $action->description }}">{{ $action->description }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select class="form-select rounded-5" wire:model.live.debounce.500ms="selectedMenu">
                            <option value="">Semua Menu</option>

                            @foreach ($getMenu as $menu)
                                <option value="{{ $menu->value }}">
                                    {{ $menu->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select class="form-select rounded-5" wire:model.live.debounce.500ms="selectedBulan">
                            <option value="">Pilih Bulan</option>

                            @foreach ($dataBulan as $bulan => $bulan_name)
                                <option value="{{ $bulan }}">
                                    {{ $bulan_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>  


                    {{-- hapus bedasarkan bulan --}}
                    <div>
                        @if($selectedBulan != '')
                        <button type="button" class="btn btn-danger rounded-5 text-white fw-bold" wire:click="deleteLog('{{ $selectedBulan }}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)">Hapus Log</button>
                        @endif
                    </div>  


                </div>
            </div>
        </div>
        {{-- filter --}}


        {{-- table --}}
        <div class="table-responsive">

            <table class="table table-sm table-hover">

                <thead class="bg-white text-dark">
                    <tr>
                        <th class="fw-bold text-center fw-bold">No</th>
                        <th class="fw-bold">Waktu</th>
                        <th class="fw-bold">User</th>
                        <th class="fw-bold">Menu</th>
                        <th class="fw-bold">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($logs as $key => $log)
                        @php

                            $model = $log->subject_type ? class_basename($log->subject_type) : '-';

                            $props = json_decode($log->properties, true);

                            $attributes = $props['attributes'] ?? [];
                            $old = $props['old'] ?? [];

                            $borderColor = match ($log->event) {
                                'created' => 'green',
                                'updated' => 'orange',
                                default => 'red',
                            };

                        @endphp

                        <tr style="cursor:pointer" wire:click="toggle({{ $log->id }})">
                            <td class="text-center fw-bold">
                                {{ $key + 1 }}
                            </td>
                            <td>
                                {{ date('d M Y H:i:s', strtotime($log->created_at)) }}
                            </td>

                            <td class="fw-bold">
                                {{ $log->user_name ?? 'System' }}
                            </td>

                            <td>
                                {{ $model }}
                            </td>

                            <td>

                                @if ($log->event == 'created')
                                    <span class="rounded-5 badge bg-success"
                                        style="font-size: 10px">{{ ucfirst($log->event) }}</span>
                                @elseif($log->event == 'updated')
                                    <span class="rounded-5 badge bg-warning text-white"
                                        style="font-size: 10px">{{ ucfirst($log->event) }}</span>
                                @elseif($log->event == 'deleted')
                                    <span class="rounded-5 badge bg-danger"
                                        style="font-size: 10px">{{ ucfirst($log->event) }}</span>
                                @endif

                            </td>

                        </tr>


                        {{-- DETAIL --}}
                        @if ($openRow == $log->id)
                            <tr>
                                <td colspan="5"
                                    style="background: #f7f7f7; border-left: 2px solid {{ $borderColor }}; padding: 15px;">

                                    <div class="p-3">

                                        <div class="fw-bold mb-3">
                                            Detail Aktivitas
                                        </div>

                                        <div class="small">

                                            {{-- CREATED --}}
                                            @if ($log->event == 'created')
                                                @forelse ($attributes as $key => $value)
                                                    <div class="d-flex justify-content-between py-1 border-bottom">

                                                        <span class="text-black">
                                                            {{ $key }}
                                                        </span>

                                                        <span class="text-success fw-semibold">
                                                            {{ is_array($value) ? json_encode($value) : $value }}
                                                        </span>

                                                    </div>
                                                @empty
                                                    <div class="text-muted">Tidak ada data</div>
                                                @endforelse
                                            @endif


                                            {{-- UPDATED --}}
                                            @if ($log->event == 'updated')
                                                @forelse ($attributes as $key => $value)
                                                    <div class="d-flex justify-content-between py-1 border-bottom">

                                                        <span class="text-black">
                                                            {{ $key }}
                                                        </span>

                                                        <span>

                                                            <span class="text-danger text-decoration-line-through">
                                                                {{ is_array($old[$key] ?? null) ? json_encode($old[$key]) : $old[$key] ?? '-' }}
                                                            </span>

                                                            <span class="mx-1 text-black">→</span>

                                                            <span class="text-success fw-semibold">
                                                                {{ is_array($value) ? json_encode($value) : $value }}
                                                            </span>

                                                        </span>

                                                    </div>
                                                @empty
                                                    <div class="text-muted">Tidak ada perubahan</div>
                                                @endforelse
                                            @endif


                                            {{-- DELETED --}}
                                            @if ($log->event == 'deleted')
                                                @forelse ($old as $key => $value)
                                                    <div class="d-flex justify-content-between py-1 border-bottom">

                                                        <span class="text-black">
                                                            {{ $key }}
                                                        </span>

                                                        <span class="text-danger fw-semibold">
                                                            {{ is_array($value) ? json_encode($value) : $value }}
                                                        </span>

                                                    </div>
                                                @empty
                                                    <div class="text-muted">Tidak ada data</div>
                                                @endforelse
                                            @endif

                                        </div>

                                    </div>

                                </td>
                            </tr>
                        @endif
                        {{-- END DETAIL --}}
                    @endforeach



                </tbody>

            </table>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
        {{-- table --}}
    </div>
</div>
