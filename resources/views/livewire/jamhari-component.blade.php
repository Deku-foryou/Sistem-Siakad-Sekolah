@section('title', 'Jam Pelajaran')

<div>
<div class="container-fluid mb-5 px-3 px-lg-4 py-3">
    @include('assets.akademik')


    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-clock me-2 text-success" aria-hidden="true"></i>Jam Pelajaran</h1>
            <p class="sa-sub">Atur jam pelajaran dan istirahat per hari untuk kelas aktif.</p>
        </div>
        <button class="btn btn-primary" wire:click="openAdd" @if(!$filterKelas) disabled @endif data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Jam
        </button>
    </div>

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
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
                        <div class="border rounded px-3 py-2" style="background: var(--sa-green-faint); border-color: var(--sa-border) !important;">
                            <div class="sa-hint">Kelas terpilih</div>
                            <strong>{{ $kelasTerpilih->nama }}</strong>
                            <span class="sa-cell-muted">&middot; Tingkat {{ $kelasTerpilih->tingkat }}</span>
                        </div>
                    @else
                        <div class="sa-hint pb-2">Pilih kelas untuk melihat dan mengatur jam pelajaran.</div>
                    @endif
                </div>
            </div>
    </div>

    {{-- Tab hari --}}
    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto">
        @foreach($hariNama as $h => $nama)
            <li class="nav-item">
                <button class="nav-link {{ $hari == $h ? 'active' : '' }}" wire:click="$set('hari', {{ $h }})" @if(!$filterKelas) disabled @endif data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab" aria-label="Pindah tab">
                    {{ $nama }} <span class="sa-count ms-1">{{ $jumlah[$h] ?? '-' }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="row g-3">
        {{-- Tabel jam --}}
        <div class="col-12 col-xl-8">
            <div class="sa-card h-100">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-solid fa-table-rows" aria-hidden="true"></i>Jam hari {{ $hariNama[$hari] }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="table sa-table table-bordered mb-0">
                        <thead>
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
                                    <td class="text-center sa-cell-muted">{{ $index + 1 }}</td>
                                    <td><span class="sa-badge {{ $j->jenis == 'pelajaran' ? 'sa-hadir' : 'sa-netral' }}">{{ ucfirst($j->jenis) }}</span></td>
                                    <td class="text-center">{{ $j->jam_ke ?? '-' }}</td>
                                    <td>{{ substr($j->mulai, 0, 5) }}</td>
                                    <td>{{ substr($j->selesai, 0, 5) }}</td>
                                    <td>{{ (strtotime($j->selesai) - strtotime($j->mulai)) / 60 }} m</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="edit({{ $j->idjamhari }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="deleteConfirm({{ $j->idjamhari }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="sa-empty">
                                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                            @if(!$filterKelas)
                                                <p class="sa-empty-title">Belum ada kelas dipilih</p>
                                                <p class="sa-empty-text">Pilih jurusan dan kelas aktif di atas untuk menampilkan jam pelajaran.</p>
                                            @else
                                                <p class="sa-empty-title">Belum ada jam untuk hari {{ $hariNama[$hari] }}</p>
                                                <p class="sa-empty-text">Klik tombol Tambah Jam untuk menambahkan jam pelajaran atau istirahat.</p>
                                            @endif
                                        </div>
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
            <div class="sa-card h-100">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-regular fa-copy" aria-hidden="true"></i>Salin jam hari {{ $hariNama[$hari] }}</h2>
                </div>
                <div class="sa-card-body">
                    <p class="sa-hint">Gandakan seluruh jam hari {{ $hariNama[$hari] }} ke hari lain pada kelas yang sama. Hari yang sudah memiliki jam akan ditolak.</p>
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
                    <button class="btn btn-outline-primary w-100" wire:click="salinHari" wire:loading.attr="disabled" @if(!$filterKelas || !$hariTujuan) disabled @endif data-bs-toggle="tooltip" data-bs-placement="top" title="Salin">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                <button type="submit" class="btn btn-success" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Simpan</button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                <button type="submit" class="btn btn-success" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Simpan</button>
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
            <button class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)">Hapus</button>
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
