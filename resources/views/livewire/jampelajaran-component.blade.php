@section('title', 'Jam Pelajaran')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="fw-bold mb-1">Jam Pelajaran</h3><span class="text-muted">Atur rentang jam dan istirahat per hari.</span></div>
        <div class="d-flex gap-2">
            @if ($pola && $rows->isNotEmpty())
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#salinJamModal"><i class="fa-solid fa-copy"></i> Salin ke hari lain</button>
            @endif
            <button class="btn btn-primary" wire:click="bukaTambah"><i class="fa-solid fa-plus"></i></button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <label class="form-label">Hari</label>
        <select class="form-select" wire:model.live="hari">
            @foreach ($hariOptions as $no => $nama)
                <option value="{{ $no }}">{{ $nama }}</option>
            @endforeach
        </select>
        @if ($pola)
            <div class="mt-2 text-muted small">
                Jam hari ini berasal dari pola '{{ $pola->nama }}'
                @if ($bersama)
                    (juga dipakai: {{ collect($pengguna)->filter(fn($h) => $h !== (int)$hari)->map(fn($h) => \App\Support\Hari::nama($h) ?? $h)->implode(', ') }})
                @endif
            </div>
        @endif
    </div></div>

    @if (! $pola || $rows->isEmpty())
        <div class="card border-0 shadow-sm mb-3"><div class="card-body text-center">
            <p class="mb-3">Belum ada jam untuk hari {{ \App\Support\Hari::nama((int)$hari) }}.</p>
            <button class="btn btn-primary me-2" wire:click="bukaTambah">Buat jam hari ini</button>
            @if (count($hariLainDenganJam) > 0)
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#salinJamModal">Salin dari hari lain</button>
            @endif
        </div></div>
    @else
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Urutan/Waktu</th><th>Jenis</th><th>Jam Ke</th><th>Mulai</th><th>Selesai</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $loop->iteration }}</td><td>{{ ucfirst($row->jenis) }}</td><td>{{ $row->jam_ke ?? '-' }}</td>
                    <td>{{ substr($row->mulai, 0, 5) }}</td><td>{{ substr($row->selesai, 0, 5) }}</td><td>{{ $row->keterangan ?? '-' }}</td>
                    <td class="text-nowrap"><button class="btn btn-sm btn-warning" wire:click="edit({{ $row->idjampelajaran }})"><i class="fa-regular fa-pen-to-square"></i></button>
                        <button class="btn btn-sm btn-danger" wire:click="deleteConfirmation({{ $row->idjampelajaran }})"><i class="fa-solid fa-trash-can"></i></button></td></tr>
            @empty
                <tr><td colspan="7" class="text-center">Belum ada jam pada hari ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    @endif

    <div wire:ignore.self class="modal fade" id="addJamPelajaranModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Jam</h5></div><form wire:submit.prevent="store"><div class="modal-body">
            <label>Jenis</label><select class="form-select mb-2" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
            @if ($jenis === 'pelajaran')<label>Jam Ke</label><input type="number" min="1" class="form-control mb-2" wire:model="jam_ke">@endif
            <label>Mulai</label><input type="time" class="form-control mb-2" wire:model="mulai">
            <label>Selesai</label><input type="time" class="form-control mb-2" wire:model="selesai">
            <label class="mt-2">Keterangan</label><input type="text" maxlength="100" class="form-control mb-2" wire:model="keterangan">
            @if ($bersama)
                <label class="mt-2">Berlaku untuk</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="hari_ini" wire:model="cakupan" id="cakupanHari"><label class="form-check-label" for="cakupanHari">Hanya {{ \App\Support\Hari::nama((int)$hari) }}</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="semua" wire:model="cakupan" id="cakupanSemua"><label class="form-check-label" for="cakupanSemua">Semua hari yang memakai jam ini ({{ collect($pengguna)->map(fn($h)=>\App\Support\Hari::nama($h))->implode(', ') }})</label></div>
            @endif
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div></form>
    </div></div></div>

    <div wire:ignore.self class="modal fade" id="editJamPelajaranModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Jam</h5></div><form wire:submit.prevent="update"><div class="modal-body">
            <label>Jenis</label><select class="form-select mb-2" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
            @if ($jenis === 'pelajaran')<label>Jam Ke</label><input type="number" min="1" class="form-control mb-2" wire:model="jam_ke">@endif
            <label>Mulai</label><input type="time" class="form-control mb-2" wire:model="mulai">
            <label>Selesai</label><input type="time" class="form-control mb-2" wire:model="selesai">
            <label class="mt-2">Keterangan</label><input type="text" maxlength="100" class="form-control mb-2" wire:model="keterangan">
            @if ($bersama)
                <label class="mt-2">Berlaku untuk</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="hari_ini" wire:model="cakupan" id="cakupanHariEdit"><label class="form-check-label" for="cakupanHariEdit">Hanya {{ \App\Support\Hari::nama((int)$hari) }}</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="semua" wire:model="cakupan" id="cakupanSemuaEdit"><label class="form-check-label" for="cakupanSemuaEdit">Semua hari yang memakai jam ini ({{ collect($pengguna)->map(fn($h)=>\App\Support\Hari::nama($h))->implode(', ') }})</label></div>
            @endif
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan Perubahan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div></form>
    </div></div></div>

    <div wire:ignore.self class="modal fade" id="deleteJamPelajaranModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Hapus Jam</h5></div><div class="modal-body">
            Hapus baris jam ini?
            @if ($bersama)
                <div class="mt-2">
                    <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="hari_ini" wire:model="cakupan" id="cakupanHariDel"><label class="form-check-label" for="cakupanHariDel">Hanya {{ \App\Support\Hari::nama((int)$hari) }}</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="semua" wire:model="cakupan" id="cakupanSemuaDel"><label class="form-check-label" for="cakupanSemuaDel">Semua hari yang memakai jam ini</label></div>
                </div>
            @endif
        </div>
        <div class="modal-footer"><button class="btn btn-danger" wire:click="delete">Hapus</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button></div>
    </div></div></div>

    <div wire:ignore.self class="modal fade" id="salinJamModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Salin ke hari lain</h5></div><div class="modal-body">
            <p class="text-warning">Jam hari tujuan akan diganti dengan jam {{ \App\Support\Hari::nama((int)$hari) }}.</p>
            @foreach (\App\Support\Hari::NAMA as $no => $nama)
                @if ($no !== (int)$hari)
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="{{ $no }}" wire:model="targetHari" id="target{{ $no }}"><label class="form-check-label" for="target{{ $no }}">{{ $nama }}</label></div>
                @endif
            @endforeach
        </div><div class="modal-footer"><button class="btn btn-primary" wire:click="salin">Salin</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-jampelajaran-modal', () => { $('#addJamPelajaranModal, #editJamPelajaranModal, #deleteJamPelajaranModal').modal('hide'); });
    window.addEventListener('show-add-jampelajaran-modal', () => $('#addJamPelajaranModal').modal('show'));
    window.addEventListener('show-edit-jampelajaran-modal', () => $('#editJamPelajaranModal').modal('show'));
    window.addEventListener('show-delete-jampelajaran-modal', () => $('#deleteJamPelajaranModal').modal('show'));
    window.addEventListener('close-salin-modal', () => $('#salinJamModal').modal('hide'));
</script>
