<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <h3 class="fw-bold">Jam Pelajaran</h3>
        <p class="text-muted">Atur jam pelajaran per hari. Jam berlaku sama untuk semua kelas pada hari itu.</p>

        <div class="nav nav-tabs mb-3">
            @foreach($hariNama as $h => $nama)
                <button class="nav-link {{ $hari == $h ? 'active' : '' }}" wire:click="$set('hari', {{ $h }})" @if(!$filterKelas) disabled @endif>
                    {{ $nama }} <span class="badge bg-secondary">{{ $jumlah[$h] ?? '-' }}</span>
                </button>
            @endforeach
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filterJurusan">
                    <option value="">Pilih Jurusan</option>
                    @foreach($jurusans as $j) <option value="{{ $j->idjurusan }}">{{ $j->nama }}</option> @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filterKelas">
                    <option value="">Pilih Kelas</option>
                    @foreach($kelases as $k) <option value="{{ $k->idkelas }}">{{ $k->nama }} ({{ $k->tingkat }})</option> @endforeach
                </select>
            </div>
        </div>

        <button class="btn btn-success mb-3" wire:click="$dispatch('show-add-jamhari-modal')" @if(!$filterKelas) disabled @endif>Tambah Jam</button>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No</th><th>Jenis</th><th>Jam Ke</th><th>Mulai</th><th>Selesai</th><th>Durasi</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jam as $index => $j)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><span class="badge {{ $j->jenis == 'pelajaran' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($j->jenis) }}</span></td>
                        <td>{{ $j->jam_ke ?? '-' }}</td>
                        <td>{{ substr($j->mulai, 0, 5) }}</td>
                        <td>{{ substr($j->selesai, 0, 5) }}</td>
                        <td>{{ (strtotime($j->selesai) - strtotime($j->mulai)) / 60 }} m</td>
                        <td>
                            <button class="btn btn-sm btn-warning" wire:click="edit({{ $j->idjamhari }})">Edit</button>
                            <button class="btn btn-sm btn-danger" wire:click="deleteConfirm({{ $j->idjamhari }})">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Belum ada jam untuk hari {{ $hariNama[$hari] }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modals Tambah/Edit/Delete --}}
    <!-- Add Modal -->
    <div wire:ignore.self class="modal fade" id="addJamhariModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Jam</h5></div>
        <form wire:submit.prevent="store">
            <div class="modal-body">
                @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>@endif
                <select class="form-control" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
                @if($jenis == 'pelajaran')<input type="number" class="form-control" wire:model="jam_ke" placeholder="Jam Ke">@endif
                <input type="time" class="form-control" wire:model="mulai">
                <input type="time" class="form-control" wire:model="selesai">
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Menyimpan...</button></div>
        </form>
        </div></div>
    </div>

    <!-- Edit Modal -->
    <div wire:ignore.self class="modal fade" id="editJamhariModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Jam</h5></div>
        <form wire:submit.prevent="update">
            <div class="modal-body">
                @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>@endif
                <select class="form-control" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
                @if($jenis == 'pelajaran')<input type="number" class="form-control" wire:model="jam_ke" placeholder="Jam Ke">@endif
                <input type="time" class="form-control" wire:model="mulai">
                <input type="time" class="form-control" wire:model="selesai">
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Menyimpan...</button></div>
        </form>
        </div></div>
    </div>

    <!-- Delete Modal -->
    <div wire:ignore.self class="modal fade" id="deleteJamhariModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Hapus Jam</h5></div>
            <div class="modal-body">Yakin hapus jam ini?</div>
            <div class="modal-footer"><button class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled">Hapus</button></div>
        </div></div>
    </div>
</div>

<script>
    window.addEventListener('show-add-jamhari-modal', () => { $('#addJamhariModal').modal('show'); });
    window.addEventListener('close-jamhari-modal', () => { $('#addJamhariModal').modal('hide'); $('#editJamhariModal').modal('hide'); $('#deleteJamhariModal').modal('hide'); });
    window.addEventListener('show-edit-jamhari-modal', () => { $('#editJamhariModal').modal('show'); });
    window.addEventListener('show-delete-jamhari-modal', () => { $('#deleteJamhariModal').modal('show'); });
</script>
