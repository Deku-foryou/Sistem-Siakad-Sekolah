@section('title', 'KKTP Mata Pelajaran')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')
    {{-- Header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-bullseye me-2 text-success" aria-hidden="true"></i>KKTP Mata Pelajaran</h1>
            <p class="sa-sub">Atur nilai minimal ketuntasan per semester dan mata pelajaran.</p>
        </div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-kktp-modal')">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah KKTP
        </button>
    </div>

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <label for="kktp-semester" class="form-label">Semester</label>
                <select id="kktp-semester" class="form-select" wire:model.live="filterSemester">
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Daftar KKTP</h2>
            <span class="sa-count">{{ $rows->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>TA / Semester</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-center" style="width: 140px;">Nilai Minimal</th>
                        <th>Deskripsi</th>
                        <th class="text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><strong>{{ $row->semester->tahunajaran->nama ?? '-' }}</strong> &middot; {{ $row->semester->nama }}</td>
                            <td>{{ $row->mapel->nama }}</td>
                            <td class="text-center fw-semibold">{{ number_format((float) $row->nilai_minimal, 2, ',', '.') }}</td>
                            <td>{{ $row->deskripsi ?: '-' }}</td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $row->idkktp_mapel }})" aria-label="Edit KKTP {{ $row->mapel->nama }}"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="deleteConfirmation({{ $row->idkktp_mapel }})" aria-label="Hapus KKTP {{ $row->mapel->nama }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="sa-empty"><i class="fa-solid fa-bullseye" aria-hidden="true"></i><p class="sa-empty-title">Belum ada KKTP</p><p class="sa-empty-text">Tambahkan KKTP untuk semester yang dipilih.</p></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="addKktpModal" tabindex="-1" aria-labelledby="addKktpTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addKktpTitle"><i class="fa-solid fa-bullseye me-2" aria-hidden="true"></i>Tambah KKTP</h5></div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <label class="form-label" for="add-kktp-semester">Semester</label>
                        <select id="add-kktp-semester" class="form-select mb-3" wire:model="semester_idsemester">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="add-kktp-mapel">Mata Pelajaran</label>
                        <select id="add-kktp-mapel" class="form-select mb-3" wire:model="mapel_idmapel">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="add-kktp-minimal">Nilai Minimal</label>
                        <input id="add-kktp-minimal" type="number" min="0" max="100" step="0.01" class="form-control mb-3" inputmode="decimal" wire:model="nilai_minimal" placeholder="0 - 100">
                        <label class="form-label" for="add-kktp-deskripsi">Deskripsi (opsional)</label>
                        <textarea id="add-kktp-deskripsi" class="form-control" rows="3" wire:model="deskripsi"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div wire:ignore.self class="modal fade" id="editKktpModal" tabindex="-1" aria-labelledby="editKktpTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="editKktpTitle"><i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Edit KKTP</h5></div>
                <form wire:submit.prevent="update">
                    <div class="modal-body">
                        <label class="form-label" for="edit-kktp-semester">Semester</label>
                        <select id="edit-kktp-semester" class="form-select mb-3" wire:model="semester_idsemester">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="edit-kktp-mapel">Mata Pelajaran</label>
                        <select id="edit-kktp-mapel" class="form-select mb-3" wire:model="mapel_idmapel">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="edit-kktp-minimal">Nilai Minimal</label>
                        <input id="edit-kktp-minimal" type="number" min="0" max="100" step="0.01" class="form-control mb-3" inputmode="decimal" wire:model="nilai_minimal" placeholder="0 - 100">
                        <label class="form-label" for="edit-kktp-deskripsi">Deskripsi (opsional)</label>
                        <textarea id="edit-kktp-deskripsi" class="form-control" rows="3" wire:model="deskripsi"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Hapus --}}
    <div wire:ignore.self class="modal fade" id="deleteKktpModal" tabindex="-1" aria-labelledby="deleteKktpTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="deleteKktpTitle"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Hapus KKTP</h5></div>
                <div class="modal-body">Hapus KKTP ini?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button>
                    <button class="btn btn-danger" wire:click="delete"><i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i>Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.addEventListener('close-kktp-modal', () => { $('#addKktpModal, #editKktpModal, #deleteKktpModal').modal('hide'); });
    window.addEventListener('show-add-kktp-modal', () => $('#addKktpModal').modal('show'));
    window.addEventListener('show-edit-kktp-modal', () => $('#editKktpModal').modal('show'));
    window.addEventListener('show-delete-kktp-modal', () => $('#deleteKktpModal').modal('show'));
</script>
@endscript
