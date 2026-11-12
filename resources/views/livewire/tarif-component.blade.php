@section('title', 'Tarif')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-tags me-2 text-success" aria-hidden="true"></i>Tarif</h1>
            <p class="sa-sub">Atur besaran tarif per jenis: global, per kelas, dan per individu.</p>
        </div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-tarif-modal')">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Tarif
        </button>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label for="tarif-filter-jenis" class="form-label">Jenis</label>
                <select id="tarif-filter-jenis" class="form-select" wire:model.live="filterJenis">
                    <option value="">Semua</option>
                    <option value="global">Global</option>
                    <option value="kelas">Kelas</option>
                    <option value="individu">Individu</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="tarif-filter-status" class="form-label">Status</label>
                <select id="tarif-filter-status" class="form-select" wire:model.live="filterStatus">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="">Semua</option>
                </select>
            </div>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-table-list" aria-hidden="true"></i>Daftar Tarif</h2>
            <span class="sa-count">{{ $tarifs->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Nama</th>
                        <th class="text-end">Nominal</th>
                        <th>Berlaku</th>
                        <th>Cakupan</th>
                        <th>Status</th>
                        <th class="text-center" style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tarifs as $row)
                        <tr>
                            <td>@if ($row->jenis === 'global') Global @elseif ($row->jenis === 'kelas') Kelas @else Individu @endif</td>
                            <td>{{ $row->nama }}</td>
                            <td class="text-end">Rp {{ number_format($row->nominal, 0, ',', '.') }}</td>
                            <td>{{ $row->berlaku_dari?->format('d/m/Y') }} s.d {{ $row->berlaku_sampai ? $row->berlaku_sampai->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if ($row->jenis === 'kelas') {{ $row->kelas?->nama ?? '-' }}
                                @elseif ($row->jenis === 'individu') {{ $row->siswa?->nama ?? '-' }}
                                @else Semua siswa @endif
                            </td>
                            <td><span class="sa-badge {{ $row->status === 'aktif' ? 'sa-hadir' : 'sa-netral' }}">{{ ucfirst($row->status) }}</span></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $row->idtarif }})" aria-label="Edit tarif {{ $row->nama }}"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="toggleStatus({{ $row->idtarif }})" aria-label="Toggle status tarif {{ $row->nama }}"><i class="fa-solid fa-toggle-{{ $row->status === 'aktif' ? 'on' : 'off' }}" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="sa-empty"><i class="fa-solid fa-tag" aria-hidden="true"></i><p class="sa-empty-title">Belum ada tarif</p><p class="sa-empty-text">Tambahkan tarif global atau per kelas terlebih dahulu.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="addTarifModal" tabindex="-1" aria-labelledby="addTarifTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addTarifTitle"><i class="fa-solid fa-tag me-2" aria-hidden="true"></i>Tambah Tarif</h5></div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <label class="form-label" for="add-tarif-jenis">Jenis</label>
                        <select id="add-tarif-jenis" class="form-select mb-3" wire:model.live="jenis">
                            <option value="global">Global</option>
                            <option value="kelas">Kelas</option>
                            <option value="individu">Individu</option>
                        </select>
                        @if ($jenis === 'kelas')
                            <label class="form-label" for="add-tarif-kelas">Kelas</label>
                            <select id="add-tarif-kelas" class="form-select mb-3" wire:model="kelas_idkelas"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $k)<option value="{{ $k->idkelas }}">{{ $k->nama }} ({{ $k->tingkat }})</option>@endforeach</select>
                        @endif
                        @if ($jenis === 'individu')
                            <label class="form-label" for="add-tarif-siswa">Siswa</label>
                            <select id="add-tarif-siswa" class="form-select mb-3" wire:model="siswa_idsiswa"><option value="">Pilih siswa</option>@foreach ($siswaOptions as $s)<option value="{{ $s->idsiswa }}">{{ $s->nama }} &middot; {{ $s->nis ?? '-' }}</option>@endforeach</select>
                        @endif
                        <label class="form-label" for="add-tarif-nama">Nama</label>
                        <input id="add-tarif-nama" type="text" class="form-control mb-3" wire:model="nama" placeholder="Contoh: SPP Bulanan">
                        <label class="form-label" for="add-tarif-nominal">Nominal (Rp)</label>
                        <input id="add-tarif-nominal" type="number" min="1" class="form-control mb-3" wire:model="nominal" placeholder="0">
                        <div class="row g-2"><div class="col-md-6"><label class="form-label" for="add-tarif-dari">Berlaku Dari</label><input id="add-tarif-dari" type="date" class="form-control" wire:model="berlaku_dari"></div><div class="col-md-6"><label class="form-label" for="add-tarif-sampai">Berlaku Sampai</label><input id="add-tarif-sampai" type="date" class="form-control" wire:model="berlaku_sampai"></div></div>
                        <label class="form-label mt-2" for="add-tarif-alasan">Alasan (opsional)</label>
                        <textarea id="add-tarif-alasan" class="form-control" rows="2" wire:model="alasan"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div wire:ignore.self class="modal fade" id="editTarifModal" tabindex="-1" aria-labelledby="editTarifTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="editTarifTitle"><i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Edit Tarif</h5></div>
                <form wire:submit.prevent="update">
                    <div class="modal-body">
                        <label class="form-label" for="edit-tarif-jenis">Jenis</label>
                        <select id="edit-tarif-jenis" class="form-select mb-3" wire:model.live="jenis"><option value="global">Global</option><option value="kelas">Kelas</option><option value="individu">Individu</option></select>
                        @if ($jenis === 'kelas')<label class="form-label" for="edit-tarif-kelas">Kelas</label><select id="edit-tarif-kelas" class="form-select mb-3" wire:model="kelas_idkelas"><option value="">Pilih kelas</option>@foreach ($kelasOptions as $k)<option value="{{ $k->idkelas }}">{{ $k->nama }} ({{ $k->tingkat }})</option>@endforeach</select>@endif
                        @if ($jenis === 'individu')<label class="form-label" for="edit-tarif-siswa">Siswa</label><select id="edit-tarif-siswa" class="form-select mb-3" wire:model="siswa_idsiswa"><option value="">Pilih siswa</option>@foreach ($siswaOptions as $s)<option value="{{ $s->idsiswa }}">{{ $s->nama }} &middot; {{ $s->nis ?? '-' }}</option>@endforeach</select>@endif
                        <label class="form-label" for="edit-tarif-nama">Nama</label>
                        <input id="edit-tarif-nama" type="text" class="form-control mb-3" wire:model="nama">
                        <label class="form-label" for="edit-tarif-nominal">Nominal (Rp)</label>
                        <input id="edit-tarif-nominal" type="number" min="1" class="form-control mb-3" wire:model="nominal">
                        <div class="row g-2"><div class="col-md-6"><label class="form-label" for="edit-tarif-dari">Berlaku Dari</label><input id="edit-tarif-dari" type="date" class="form-control" wire:model="berlaku_dari"></div><div class="col-md-6"><label class="form-label" for="edit-tarif-sampai">Berlaku Sampai</label><input id="edit-tarif-sampai" type="date" class="form-control" wire:model="berlaku_sampai"></div></div>
                        <label class="form-label mt-2" for="edit-tarif-alasan">Alasan (opsional)</label>
                        <textarea id="edit-tarif-alasan" class="form-control" rows="2" wire:model="alasan"></textarea>
                        <label class="form-label mt-2" for="edit-tarif-status">Status</label>
                        <select id="edit-tarif-status" class="form-select" wire:model="status"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.addEventListener('close-tarif-modal', () => { $('#addTarifModal, #editTarifModal').modal('hide'); });
    window.addEventListener('show-add-tarif-modal', () => $('#addTarifModal').modal('show'));
    window.addEventListener('show-edit-tarif-modal', () => $('#editTarifModal').modal('show'));
</script>
@endscript
