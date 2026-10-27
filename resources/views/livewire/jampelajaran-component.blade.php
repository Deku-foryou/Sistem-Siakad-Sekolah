@section('title', 'Jam Pelajaran')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="fw-bold mb-1">Jam Pelajaran</h3><span class="text-muted">Atur rentang jam dan istirahat dalam pola.</span></div>
        <button class="btn btn-primary" wire:click="bukaTambah" @disabled($polas->isEmpty())><i class="fa-solid fa-plus"></i></button>
    </div>
    @if ($polas->isEmpty())
        <div class="alert alert-info">Belum ada pola jam. <a href="{{ url('/polajam') }}">Buat pola jam terlebih dahulu.</a></div>
    @else
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <label class="form-label">Pola Jam</label>
            <select class="form-select" wire:model.live="polajam_idpolajam">
                @foreach ($polas as $pola)<option value="{{ $pola->idpolajam }}">{{ $pola->nama }}</option>@endforeach
            </select>
        </div></div>
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Urutan/Waktu</th><th>Jenis</th><th>Jam Ke</th><th>Mulai</th><th>Selesai</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $loop->iteration }}</td><td>{{ ucfirst($row->jenis) }}</td><td>{{ $row->jam_ke ?? '-' }}</td>
                    <td>{{ substr($row->mulai, 0, 5) }}</td><td>{{ substr($row->selesai, 0, 5) }}</td>
                    <td class="text-nowrap"><button class="btn btn-sm btn-warning" wire:click="edit({{ $row->idjampelajaran }})"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="btn btn-sm btn-danger" wire:click="deleteConfirmation({{ $row->idjampelajaran }})"><i class="fa-solid fa-trash-can"></i></button></td></tr>
            @empty
                <tr><td colspan="6" class="text-center">Belum ada jam pada pola ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    @endif
    <div wire:ignore.self class="modal fade" id="addJamPelajaranModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Jam</h5></div><form wire:submit.prevent="store"><div class="modal-body">
            <label>Pola</label><select class="form-select mb-2" wire:model="polajam_idpolajam">@foreach ($polas as $pola)<option value="{{ $pola->idpolajam }}">{{ $pola->nama }}</option>@endforeach</select>
            <label>Jenis</label><select class="form-select mb-2" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
            @if ($jenis === 'pelajaran')<label>Jam Ke</label><input type="number" min="1" class="form-control mb-2" wire:model="jam_ke">@endif
            <label>Mulai</label><input type="time" class="form-control mb-2" wire:model="mulai">
            <label>Selesai</label><input type="time" class="form-control" wire:model="selesai">
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div></form>
    </div></div></div>
    <div wire:ignore.self class="modal fade" id="editJamPelajaranModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Jam</h5></div><form wire:submit.prevent="update"><div class="modal-body">
            <label>Pola</label><select class="form-select mb-2" wire:model="polajam_idpolajam">@foreach ($polas as $pola)<option value="{{ $pola->idpolajam }}">{{ $pola->nama }}</option>@endforeach</select>
            <label>Jenis</label><select class="form-select mb-2" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
            @if ($jenis === 'pelajaran')<label>Jam Ke</label><input type="number" min="1" class="form-control mb-2" wire:model="jam_ke">@endif
            <label>Mulai</label><input type="time" class="form-control mb-2" wire:model="mulai">
            <label>Selesai</label><input type="time" class="form-control" wire:model="selesai">
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan Perubahan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div></form>
    </div></div></div>
    <div wire:ignore.self class="modal fade" id="deleteJamPelajaranModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Hapus Jam</h5></div><div class="modal-body">Hapus baris jam ini?</div>
        <div class="modal-footer"><button class="btn btn-danger" wire:click="delete">Hapus</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button></div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-jampelajaran-modal', () => { $('#addJamPelajaranModal, #editJamPelajaranModal, #deleteJamPelajaranModal').modal('hide'); });
    window.addEventListener('show-add-jampelajaran-modal', () => $('#addJamPelajaranModal').modal('show'));
    window.addEventListener('show-edit-jampelajaran-modal', () => $('#editJamPelajaranModal').modal('show'));
    window.addEventListener('show-delete-jampelajaran-modal', () => $('#deleteJamPelajaranModal').modal('show'));
</script>
