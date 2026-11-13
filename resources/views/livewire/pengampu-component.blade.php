@section('title', 'Pengampu')

<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-user-tie me-2 text-success" aria-hidden="true"></i>Pengampu</h1>
            <p class="sa-sub">Tetapkan guru mata pelajaran pada kelas dan semester.</p>
        </div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-pengampu-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Pengampu
        </button>
    </div>

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <label for="pengampu-semester" class="form-label">Semester</label>
                <select id="pengampu-semester" class="form-select" wire:model.live="filterSemester">
                    <option value="">Semua semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="pengampu-kelas" class="form-label">Kelas</label>
                <select id="pengampu-kelas" class="form-select" wire:model.live="filterKelas">
                    <option value="">Semua kelas</option>
                    @foreach ($kelasOptions as $kelas)
                        <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }}) &middot; {{ $kelas->jenis === 'induk' ? 'Induk' : 'Mapel pilihan' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-table-list" aria-hidden="true"></i>Daftar Pengampu</h2>
            <span class="sa-count">{{ $rows->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table table-striped mb-0">
                <thead>
                    <tr>
                        <th>TA / Semester</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru</th>
                        <th class="text-center">Jadwal Terisi</th>
                        <th class="text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><strong>{{ $row->semester->tahunajaran->nama ?? '-' }}</strong> &middot; {{ $row->semester->nama }}</td>
                        <td>{{ $row->kelas->nama }} <span class="sa-cell-muted">({{ $row->kelas->tingkat }})</span></td>
                        <td>{{ $row->mapel->nama }}</td>
                        <td>{{ $row->guru->nama }}</td>
                        <td class="text-center">
                            <span class="sa-badge {{ $row->jadwal_count > 0 ? 'sa-hadir' : 'sa-netral' }}">{{ $row->jadwal_count }} sesi</span>
                        </td>
                        <td class="text-center text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $row->idpengampu }})" aria-label="Ubah pengampu {{ $row->guru->nama }} - {{ $row->mapel->nama }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                            <button class="btn btn-sm btn-outline-danger" wire:click="deleteConfirmation({{ $row->idpengampu }})" aria-label="Hapus pengampu {{ $row->guru->nama }} - {{ $row->mapel->nama }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="sa-empty"><i class="fa-solid fa-user-slash" aria-hidden="true"></i><p class="sa-empty-title">Belum ada data pengampu</p><p class="sa-empty-text">Tambahkan semester, kelas, mata pelajaran, dan guru terlebih dahulu, lalu tetapkan pengampu.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="addPengampuModal" tabindex="-1" aria-labelledby="addPengampuTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="addPengampuTitle"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Tambah Pengampu</h5></div>
            <form wire:submit.prevent="store"><div class="modal-body">
                <label for="add-semester" class="form-label">Semester</label>
                <select id="add-semester" class="form-select mb-3" wire:model="semester_idsemester"><option value="">Pilih semester</option>@foreach ($semesters as $semester)
                    <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>@endforeach</select>
                <label for="add-kelas" class="form-label">Kelas</label>
                <select id="add-kelas" class="form-select mb-3" wire:model="kelas_idkelas"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $kelas)
                    <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>@endforeach</select>
                <label for="add-mapel" class="form-label">Mata Pelajaran</label>
                <select id="add-mapel" class="form-select mb-3" wire:model.live="mapel_idmapel"><option value="">Pilih mata pelajaran</option>@foreach ($mapels as $mapel)
                    <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>@endforeach</select>
                <label for="add-guru" class="form-label">Guru Aktif</label>
                <select id="add-guru" class="form-select" wire:model="guru_idguru"><option value="">Pilih guru</option>@foreach ($gurus as $guru)
                    <option value="{{ $guru->idguru }}">{{ $guru->nama }} ({{ $guru->nip }})</option>@endforeach</select>
            </div><div class="modal-footer"><button class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button></div></form>
        </div></div>
    </div>

    {{-- Modal Edit --}}
    <div wire:ignore.self class="modal fade" id="editPengampuModal" tabindex="-1" aria-labelledby="editPengampuTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="editPengampuTitle"><i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Edit Pengampu</h5></div>
            <form wire:submit.prevent="update"><div class="modal-body">
                <label for="edit-semester" class="form-label">Semester</label>
                <select id="edit-semester" class="form-select mb-3" wire:model="semester_idsemester"><option value="">Pilih semester</option>@foreach ($semesters as $semester)
                    <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>@endforeach</select>
                <label for="edit-kelas" class="form-label">Kelas</label>
                <select id="edit-kelas" class="form-select mb-3" wire:model="kelas_idkelas"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $kelas)
                    <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>@endforeach</select>
                <label for="edit-mapel" class="form-label">Mata Pelajaran</label>
                <select id="edit-mapel" class="form-select mb-3" wire:model.live="mapel_idmapel"><option value="">Pilih mata pelajaran</option>@foreach ($mapels as $mapel)
                    <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>@endforeach</select>
                <label for="edit-guru" class="form-label">Guru Aktif</label>
                <select id="edit-guru" class="form-select" wire:model="guru_idguru"><option value="">Pilih guru</option>@foreach ($gurus as $guru)
                    <option value="{{ $guru->idguru }}">{{ $guru->nama }} ({{ $guru->nip }})</option>@endforeach</select>
            </div><div class="modal-footer"><button class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Perubahan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button></div></form>
        </div></div>
    </div>

    {{-- Modal Hapus --}}
    <div wire:ignore.self class="modal fade" id="deletePengampuModal" tabindex="-1" aria-labelledby="deletePengampuTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="deletePengampuTitle"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Hapus Pengampu</h5></div>
            <div class="modal-body">Hapus data pengampu ini? Tindakan ini tidak menghapus data jadwal yang sudah dibuat.</div>
            <div class="modal-footer"><button class="btn btn-danger" wire:click="delete" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i>Hapus</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button></div>
        </div></div>
    </div>
</div>
<script>
    window.addEventListener('close-pengampu-modal', () => { $('#addPengampuModal, #editPengampuModal, #deletePengampuModal').modal('hide'); });
    window.addEventListener('show-add-pengampu-modal', () => $('#addPengampuModal').modal('show'));
    window.addEventListener('show-edit-pengampu-modal', () => $('#editPengampuModal').modal('show'));
    window.addEventListener('show-delete-pengampu-modal', () => $('#deletePengampuModal').modal('show'));
</script>
