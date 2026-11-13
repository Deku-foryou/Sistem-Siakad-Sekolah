@section('title', 'Penugasan Mengajar')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')
    {{-- Header --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-user-tie me-2 text-success" aria-hidden="true"></i>Penugasan Mengajar</h1>
            <p class="sa-sub">Tetapkan guru penanggung jawab mata pelajaran per kelas dan semester.</p>
        </div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-penugasan-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Penugasan
        </button>
    </div>

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <label for="penugasan-semester" class="form-label">Semester</label>
                <select id="penugasan-semester" class="form-select" wire:model.live="filterSemester">
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="penugasan-kelas" class="form-label">Kelas</label>
                <select id="penugasan-kelas" class="form-select" wire:model.live="filterKelas">
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
            <h2 class="sa-card-title"><i class="fa-solid fa-table-list" aria-hidden="true"></i>Daftar Penugasan</h2>
            <span class="sa-count">{{ $rows->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>TA / Semester</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru</th>
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
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $row->idpenugasan_mengajar }})" aria-label="Edit penugasan {{ $row->guru->nama }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="deleteConfirmation({{ $row->idpenugasan_mengajar }})" aria-label="Hapus penugasan {{ $row->guru->nama }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="sa-empty"><i class="fa-solid fa-user-slash" aria-hidden="true"></i><p class="sa-empty-title">Belum ada penugasan</p><p class="sa-empty-text">Tambahkan semester, kelas, mata pelajaran, dan guru terlebih dahulu, lalu tetapkan penugasan.</p></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="addPenugasanModal" tabindex="-1" aria-labelledby="addPenugasanTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addPenugasanTitle"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Tambah Penugasan Mengajar</h5></div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <label class="form-label" for="add-penugasan-semester">Semester</label>
                        <select id="add-penugasan-semester" class="form-select mb-3" wire:model.live="semester_idsemester">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="add-penugasan-kelas">Kelas</label>
                        <select id="add-penugasan-kelas" class="form-select mb-3" wire:model="kelas_idkelas">
                            <option value="">Pilih kelas</option>
                            @foreach ($kelasFormOptions as $kelas)
                                <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="add-penugasan-mapel">Mata Pelajaran</label>
                        <select id="add-penugasan-mapel" class="form-select mb-3" wire:model="mapel_idmapel">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="add-penugasan-guru">Guru Aktif</label>
                        <select id="add-penugasan-guru" class="form-select" wire:model="guru_idguru">
                            <option value="">Pilih guru</option>
                            @foreach ($gurus as $guru)
                                <option value="{{ $guru->idguru }}">{{ $guru->nama }} ({{ $guru->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button>
                        <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div wire:ignore.self class="modal fade" id="editPenugasanModal" tabindex="-1" aria-labelledby="editPenugasanTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="editPenugasanTitle"><i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Edit Penugasan Mengajar</h5></div>
                <form wire:submit.prevent="update">
                    <div class="modal-body">
                        <label class="form-label" for="edit-penugasan-semester">Semester</label>
                        <select id="edit-penugasan-semester" class="form-select mb-3" wire:model.live="semester_idsemester">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="edit-penugasan-kelas">Kelas</label>
                        <select id="edit-penugasan-kelas" class="form-select mb-3" wire:model="kelas_idkelas">
                            <option value="">Pilih kelas</option>
                            @foreach ($kelasFormOptions as $kelas)
                                <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="edit-penugasan-mapel">Mata Pelajaran</label>
                        <select id="edit-penugasan-mapel" class="form-select mb-3" wire:model="mapel_idmapel">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        <label class="form-label" for="edit-penugasan-guru">Guru Aktif</label>
                        <select id="edit-penugasan-guru" class="form-select" wire:model="guru_idguru">
                            <option value="">Pilih guru</option>
                            @foreach ($gurus as $guru)
                                <option value="{{ $guru->idguru }}">{{ $guru->nama }} ({{ $guru->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button>
                        <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Hapus --}}
    <div wire:ignore.self class="modal fade" id="deletePenugasanModal" tabindex="-1" aria-labelledby="deletePenugasanTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="deletePenugasanTitle"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Hapus Penugasan Mengajar</h5></div>
                <div class="modal-body">Hapus penugasan mengajar ini?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                    <button class="btn btn-danger" wire:click="delete" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i>Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.addEventListener('close-penugasan-modal', () => { $('#addPenugasanModal, #editPenugasanModal, #deletePenugasanModal').modal('hide'); });
    window.addEventListener('show-add-penugasan-modal', () => $('#addPenugasanModal').modal('show'));
    window.addEventListener('show-edit-penugasan-modal', () => $('#editPenugasanModal').modal('show'));
    window.addEventListener('show-delete-penugasan-modal', () => $('#deletePenugasanModal').modal('show'));
</script>
@endscript
