@section('title', 'Jam Pelajaran')

<div>
<div class="container-fluid mb-5 px-3 px-md-4" style="margin-top: 20px">

    {{-- Header halaman --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="fw-bold mb-1">Jam Pelajaran</h3>
            <p class="text-body-secondary mb-0">Atur jam pelajaran dan istirahat per hari untuk kelas aktif.</p>
        </div>
        <button class="btn btn-success" wire:click="openAdd" @if(!$filterKelas) disabled @endif>
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Jam
        </button>
    </div>

    {{-- Filter --}}
    <div class="card border mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="jamhari-jurusan" class="form-label">Jurusan</label>
                    <select id="jamhari-jurusan" class="form-select" wire:model.live="filterJurusan">
                        <option value="">Semua jurusan</option>
                        <option value="none">Kelas tanpa jurusan</option>
                        @foreach($jurusans as $j)
                            <option value="{{ $j->idjurusan }}">{{ $j->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="jamhari-kelas" class="form-label">Kelas</label>
                    <select id="jamhari-kelas" class="form-select" wire:model.live="filterKelas">
                        <option value="">Pilih kelas aktif</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->idkelas }}">{{ $k->nama }} ({{ $k->tingkat }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    @if($kelasTerpilih)
                        <div class="border rounded px-3 py-2 bg-light">
                            <div class="small text-body-secondary">Kelas terpilih</div>
                            <strong>{{ $kelasTerpilih->nama }}</strong>
                            <span class="text-body-secondary">&middot; Tingkat {{ $kelasTerpilih->tingkat }}</span>
                        </div>
                    @else
                        <div class="text-body-secondary small pb-2">Pilih kelas untuk melihat dan mengatur jam pelajaran.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tab hari --}}
    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto">
        @foreach($hariNama as $h => $nama)
            <li class="nav-item">
                <button class="nav-link {{ $hari == $h ? 'active' : '' }}" wire:click="$set('hari', {{ $h }})" @if(!$filterKelas) disabled @endif>
                    {{ $nama }} <span class="badge bg-secondary ms-1">{{ $jumlah[$h] ?? '-' }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="row g-3">
        {{-- Tabel jam --}}
        <div class="col-12 col-xl-8">
            <div class="card border h-100">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                    <h6 class="mb-0 fw-semibold">Jam hari {{ $hariNama[$hari] }}</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 48px;">No</th>
                                <th>Jenis</th>
                                <th class="text-center">Jam Ke</th>
                                <th>Mulai</th>
                                <th>Selesai</th>
                                <th>Durasi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jam as $index => $j)
                                <tr>
                                    <td class="text-center text-body-secondary">{{ $index + 1 }}</td>
                                    <td><span class="badge {{ $j->jenis == 'pelajaran' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($j->jenis) }}</span></td>
                                    <td class="text-center">{{ $j->jam_ke ?? '-' }}</td>
                                    <td>{{ substr($j->mulai, 0, 5) }}</td>
                                    <td>{{ substr($j->selesai, 0, 5) }}</td>
                                    <td>{{ (strtotime($j->selesai) - strtotime($j->mulai)) / 60 }} m</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="edit({{ $j->idjamhari }})">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="deleteConfirm({{ $j->idjamhari }})">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fa-regular fa-clock fs-3 d-block mb-2 text-body-secondary" aria-hidden="true"></i>
                                        @if(!$filterKelas)
                                            <div class="fw-semibold">Belum ada kelas dipilih</div>
                                            <div class="text-body-secondary small">Pilih jurusan dan kelas aktif di atas untuk menampilkan jam pelajaran.</div>
                                        @else
                                            <div class="fw-semibold">Belum ada jam untuk hari {{ $hariNama[$hari] }}</div>
                                            <div class="text-body-secondary small">Klik tombol Tambah Jam untuk menambahkan jam pelajaran atau istirahat.</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Salin hari --}}
        <div class="col-12 col-xl-4">
            <div class="card border h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 fw-semibold">Salin jam hari {{ $hariNama[$hari] }}</h6>
                </div>
                <div class="card-body">
                    <p class="text-body-secondary small">Gandakan seluruh jam hari {{ $hariNama[$hari] }} ke hari lain pada kelas yang sama. Hari yang sudah memiliki jam akan ditolak.</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($hariNama as $h => $nama)
                            @if($hari != $h)
                                <label class="form-check border rounded px-2 py-1 m-0">
                                    <input class="form-check-input" type="checkbox" value="{{ $h }}" wire:model="hariTujuan" @if(!$filterKelas) disabled @endif>
                                    <span class="form-check-label">{{ $nama }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <button class="btn btn-outline-primary w-100" wire:click="salinHari" wire:loading.attr="disabled" @if(!$filterKelas || !$hariTujuan) disabled @endif>
                        <i class="fa-regular fa-copy me-1" aria-hidden="true"></i>Salin ke hari terpilih
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals Tambah/Edit/Delete --}}
<!-- Add Modal -->
<div wire:ignore.self class="modal fade" id="addJamhariModal" tabindex="-1" aria-labelledby="addJamhariModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="addJamhariModalLabel">Tambah Jam</h5></div>
        <form wire:submit.prevent="store">
            <div class="modal-body">
                @if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>@endif
                <div class="mb-2">
                    <label for="add-jamhari-jenis" class="form-label">Jenis</label>
                    <select id="add-jamhari-jenis" class="form-select" wire:model.live="jenis">
                        <option value="pelajaran">Pelajaran</option>
                        <option value="istirahat">Istirahat</option>
                    </select>
                </div>
                @if($jenis == 'pelajaran')
                    <div class="mb-2">
                        <label for="add-jamhari-ke" class="form-label">Jam Ke</label>
                        <input id="add-jamhari-ke" type="number" class="form-control" wire:model="jam_ke" placeholder="Jam Ke" min="1">
                    </div>
                @endif
                <div class="row g-2">
                    <div class="col">
                        <label for="add-jamhari-mulai" class="form-label">Mulai</label>
                        <input id="add-jamhari-mulai" type="time" class="form-control" wire:model="mulai">
                    </div>
                    <div class="col">
                        <label for="add-jamhari-selesai" class="form-label">Selesai</label>
                        <input id="add-jamhari-selesai" type="time" class="form-control" wire:model="selesai">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success" wire:loading.attr="disabled">Simpan</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div wire:ignore.self class="modal fade" id="editJamhariModal" tabindex="-1" aria-labelledby="editJamhariModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="editJamhariModalLabel">Edit Jam</h5></div>
        <form wire:submit.prevent="update">
            <div class="modal-body">
                @if($errors->any())<div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>@endif
                <div class="mb-2">
                    <label for="edit-jamhari-jenis" class="form-label">Jenis</label>
                    <select id="edit-jamhari-jenis" class="form-select" wire:model.live="jenis">
                        <option value="pelajaran">Pelajaran</option>
                        <option value="istirahat">Istirahat</option>
                    </select>
                </div>
                @if($jenis == 'pelajaran')
                    <div class="mb-2">
                        <label for="edit-jamhari-ke" class="form-label">Jam Ke</label>
                        <input id="edit-jamhari-ke" type="number" class="form-control" wire:model="jam_ke" placeholder="Jam Ke" min="1">
                    </div>
                @endif
                <div class="row g-2">
                    <div class="col">
                        <label for="edit-jamhari-mulai" class="form-label">Mulai</label>
                        <input id="edit-jamhari-mulai" type="time" class="form-control" wire:model="mulai">
                    </div>
                    <div class="col">
                        <label for="edit-jamhari-selesai" class="form-label">Selesai</label>
                        <input id="edit-jamhari-selesai" type="time" class="form-control" wire:model="selesai">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success" wire:loading.attr="disabled">Simpan</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Delete Modal -->
<div wire:ignore.self class="modal fade" id="deleteJamhariModal" tabindex="-1" aria-labelledby="deleteJamhariModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="deleteJamhariModalLabel">Hapus Jam</h5></div>
        <div class="modal-body">
            <p class="mb-0">Yakin menghapus jam ini? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled">Hapus</button>
        </div>
    </div></div>
</div>

<script>
    window.addEventListener('show-add-jamhari-modal', () => { $('#addJamhariModal').modal('show'); });
    window.addEventListener('close-jamhari-modal', () => { $('#addJamhariModal').modal('hide'); $('#editJamhariModal').modal('hide'); $('#deleteJamhariModal').modal('hide'); });
    window.addEventListener('show-edit-jamhari-modal', () => { $('#editJamhariModal').modal('show'); });
    window.addEventListener('show-delete-jamhari-modal', () => { $('#deleteJamhariModal').modal('show'); });
</script>
</div>
