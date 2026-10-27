@section('title', 'Pola Jam')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold">Pola Jam</h3>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-polajam-modal')"><i class="fa-solid fa-plus"></i></button>
    </div>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <input class="form-control" placeholder="Cari pola..." wire:model.live.debounce.300ms="search">
    </div></div>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Nama Pola</th><th>Hari</th><th>Jumlah Jam</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse ($polas as $pola)
            <tr>
                <td>{{ $pola->nama }}</td>
                <td>{{ $pola->hari->pluck('hari')->map(fn ($day) => $hariOptions[$day] ?? '-')->join(', ') }}</td>
                <td>{{ $pola->jamPelajaran->where('jenis', 'pelajaran')->count() }}</td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="{{ url('/jampelajaran') }}?pola={{ $pola->idpolajam }}">Atur Jam</a>
                    <button class="btn btn-sm btn-warning" wire:click="edit({{ $pola->idpolajam }})"><i class="fa-regular fa-pen-to-square"></i></button>
                    <button class="btn btn-sm btn-danger" wire:click="deleteConfirmation({{ $pola->idpolajam }})"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">Belum ada pola jam. Tambahkan pola untuk mulai mengatur jam pelajaran.</td></tr>
        @endforelse
        </tbody>
    </table></div>

    <div wire:ignore.self class="modal fade" id="addPolajamModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Pola Jam</h5></div>
        <form wire:submit.prevent="store"><div class="modal-body">
            <label class="form-label">Nama Pola</label><input class="form-control mb-3" wire:model="nama">
            <label class="form-label">Hari</label>
            @foreach ($hariOptions as $nomor => $hariNama)
                <label class="form-check me-3 d-inline-block"><input class="form-check-input" type="checkbox" value="{{ $nomor }}" wire:model="hari"><span class="form-check-label">{{ $hariNama }}</span></label>
            @endforeach
        </div><div class="modal-footer">
            <button class="btn btn-primary" type="submit">Simpan</button><button class="btn btn-secondary" type="button" data-bs-dismiss="modal" wire:click="close">Tutup</button>
        </div></form>
    </div></div></div>
    <div wire:ignore.self class="modal fade" id="editPolajamModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Pola Jam</h5></div>
        <form wire:submit.prevent="update"><div class="modal-body">
            <label class="form-label">Nama Pola</label><input class="form-control mb-3" wire:model="nama">
            <label class="form-label">Hari</label>
            @foreach ($hariOptions as $nomor => $hariNama)
                <label class="form-check me-3 d-inline-block"><input class="form-check-input" type="checkbox" value="{{ $nomor }}" wire:model="hari"><span class="form-check-label">{{ $hariNama }}</span></label>
            @endforeach
        </div><div class="modal-footer">
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button><button class="btn btn-secondary" type="button" data-bs-dismiss="modal" wire:click="close">Tutup</button>
        </div></form>
    </div></div></div>
    <div wire:ignore.self class="modal fade" id="deletePolajamModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Hapus Pola Jam</h5></div><div class="modal-body">Hapus pola jam ini?</div>
        <div class="modal-footer"><button class="btn btn-danger" wire:click="delete">Hapus</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button></div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-polajam-modal', () => { $('#addPolajamModal, #editPolajamModal, #deletePolajamModal').modal('hide'); });
    window.addEventListener('show-add-polajam-modal', () => $('#addPolajamModal').modal('show'));
    window.addEventListener('show-edit-polajam-modal', () => $('#editPolajamModal').modal('show'));
    window.addEventListener('show-delete-polajam-modal', () => $('#deletePolajamModal').modal('show'));
</script>
