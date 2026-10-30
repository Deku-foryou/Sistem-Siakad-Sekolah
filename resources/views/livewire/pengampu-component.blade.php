@section('title', 'Pengampu')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="fw-bold mb-1">Pengampu</h3><span class="text-muted">Tetapkan guru mata pelajaran pada kelas dan semester.</span></div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-pengampu-modal')"><i class="fa-solid fa-plus"></i></button>
    </div>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body row g-2">
        <div class="col-md-6"><label class="form-label">Semester</label><select class="form-select" wire:model.live="filterSemester">
            <option value="">Pilih semester</option>@foreach ($semesters as $semester)
                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} · {{ $semester->nama }}</option>
            @endforeach
        </select></div>
        <div class="col-md-6"><label class="form-label">Kelas</label><select class="form-select" wire:model.live="filterKelas">
            <option value="">Semua kelas</option>@foreach ($kelasOptions as $kelas)
                <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }}) · {{ $kelas->jenis === 'induk' ? 'Induk' : 'Mapel pilihan' }}</option>
            @endforeach
        </select></div>
    </div></div>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Tahun Ajaran / Semester</th><th>Kelas</th><th>Mata Pelajaran</th><th>Guru</th><th>Jadwal Terisi</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr><td>{{ $row->semester->tahunajaran->nama ?? '-' }} · {{ $row->semester->nama }}</td>
                <td>{{ $row->kelas->nama }} ({{ $row->kelas->tingkat }})</td><td>{{ $row->mapel->nama }}</td>
                <td>{{ $row->guru->nama }}</td><td>{{ $row->jadwal_count }}</td>
                <td class="text-nowrap"><button class="btn btn-sm btn-warning" wire:click="edit({{ $row->idpengampu }})"><i class="fa-regular fa-pen-to-square"></i></button>
                    <button class="btn btn-sm btn-danger" wire:click="deleteConfirmation({{ $row->idpengampu }})"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        @empty
            <tr><td colspan="6" class="text-center">Belum ada data pengampu. Tambahkan semester, kelas, mata pelajaran, dan guru terlebih dahulu.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div wire:ignore.self class="modal fade" id="addPengampuModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Pengampu</h5></div><form wire:submit.prevent="store"><div class="modal-body">
            <label>Semester</label><select class="form-select mb-2" wire:model="semester_idsemester"><option value="">Pilih semester</option>@foreach ($semesters as $semester)
                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} · {{ $semester->nama }}</option>@endforeach</select>
            <label>Kelas</label><select class="form-select mb-2" wire:model="kelas_idkelas"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $kelas)
                <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>@endforeach</select>
            <label>Mata Pelajaran</label><select class="form-select mb-2" wire:model.live="mapel_idmapel"><option value="">Pilih mata pelajaran</option>@foreach ($mapels as $mapel)
                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>@endforeach</select>
            <label>Guru Aktif</label><select class="form-select" wire:model="guru_idguru"><option value="">Pilih guru</option>@foreach ($gurus as $guru)
                <option value="{{ $guru->idguru }}">{{ $guru->nama }} ({{ $guru->nip }})</option>@endforeach</select>
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div></form>
    </div></div></div>
    <div wire:ignore.self class="modal fade" id="editPengampuModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Pengampu</h5></div><form wire:submit.prevent="update"><div class="modal-body">
            <label>Semester</label><select class="form-select mb-2" wire:model="semester_idsemester"><option value="">Pilih semester</option>@foreach ($semesters as $semester)
                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} · {{ $semester->nama }}</option>@endforeach</select>
            <label>Kelas</label><select class="form-select mb-2" wire:model="kelas_idkelas"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $kelas)
                <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>@endforeach</select>
            <label>Mata Pelajaran</label><select class="form-select mb-2" wire:model.live="mapel_idmapel"><option value="">Pilih mata pelajaran</option>@foreach ($mapels as $mapel)
                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>@endforeach</select>
            <label>Guru Aktif</label><select class="form-select" wire:model="guru_idguru"><option value="">Pilih guru</option>@foreach ($gurus as $guru)
                <option value="{{ $guru->idguru }}">{{ $guru->nama }} ({{ $guru->nip }})</option>@endforeach</select>
        </div><div class="modal-footer"><button class="btn btn-primary">Simpan Perubahan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div></form>
    </div></div></div>
    <div wire:ignore.self class="modal fade" id="deletePengampuModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Hapus Pengampu</h5></div><div class="modal-body">Hapus data pengampu ini?</div>
        <div class="modal-footer"><button class="btn btn-danger" wire:click="delete">Hapus</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button></div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-pengampu-modal', () => { $('#addPengampuModal, #editPengampuModal, #deletePengampuModal').modal('hide'); });
    window.addEventListener('show-add-pengampu-modal', () => $('#addPengampuModal').modal('show'));
    window.addEventListener('show-edit-pengampu-modal', () => $('#editPengampuModal').modal('show'));
    window.addEventListener('show-delete-pengampu-modal', () => $('#deletePengampuModal').modal('show'));
</script>
