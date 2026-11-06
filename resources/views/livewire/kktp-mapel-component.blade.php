@section('title', 'KKTP Mata Pelajaran')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-1">KKTP Mata Pelajaran</h3>
            <span class="text-muted">Atur nilai minimal ketuntasan per semester dan mata pelajaran.</span>
        </div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-kktp-modal')"><i class="fa-solid fa-plus"></i></button>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-6">
                <label class="form-label">Semester</label>
                <select class="form-select" wire:model.live="filterSemester">
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>Tahun Ajaran / Semester</th>
                    <th>Mata Pelajaran</th>
                    <th>Nilai Minimal</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->semester->tahunajaran->nama ?? '-' }} &middot; {{ $row->semester->nama }}</td>
                        <td>{{ $row->mapel->nama }}</td>
                        <td>{{ number_format((float) $row->nilai_minimal, 2, ',', '.') }}</td>
                        <td>{{ $row->deskripsi ?: '-' }}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-warning" wire:click="edit({{ $row->idkktp_mapel }})" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="btn btn-sm btn-danger" wire:click="deleteConfirmation({{ $row->idkktp_mapel }})" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Belum ada KKTP pada semester ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div wire:ignore.self class="modal fade" id="addKktpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah KKTP</h5></div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <label class="form-label">Semester</label>
                        <select class="form-select mb-2" wire:model="semester_idsemester">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label">Mata Pelajaran</label>
                        <select class="form-select mb-2" wire:model="mapel_idmapel">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label">Nilai Minimal</label>
                        <input type="number" min="0" max="100" step="0.01" class="form-control mb-2" wire:model="nilai_minimal">
                        <label class="form-label">Deskripsi (opsional)</label>
                        <textarea class="form-control" rows="3" wire:model="deskripsi"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editKktpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit KKTP</h5></div>
                <form wire:submit.prevent="update">
                    <div class="modal-body">
                        <label class="form-label">Semester</label>
                        <select class="form-select mb-2" wire:model="semester_idsemester">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label">Mata Pelajaran</label>
                        <select class="form-select mb-2" wire:model="mapel_idmapel">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label">Nilai Minimal</label>
                        <input type="number" min="0" max="100" step="0.01" class="form-control mb-2" wire:model="nilai_minimal">
                        <label class="form-label">Deskripsi (opsional)</label>
                        <textarea class="form-control" rows="3" wire:model="deskripsi"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Simpan Perubahan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteKktpModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Hapus KKTP</h5></div>
                <div class="modal-body">Hapus KKTP ini?</div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="delete">Hapus</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('close-kktp-modal', () => { $('#addKktpModal, #editKktpModal, #deleteKktpModal').modal('hide'); });
    window.addEventListener('show-add-kktp-modal', () => $('#addKktpModal').modal('show'));
    window.addEventListener('show-edit-kktp-modal', () => $('#editKktpModal').modal('show'));
    window.addEventListener('show-delete-kktp-modal', () => $('#deleteKktpModal').modal('show'));
</script>
