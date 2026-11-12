@section('title', 'Beasiswa')
<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-hands-holding-child me-2 text-success" aria-hidden="true"></i>Beasiswa</h1>
            <p class="sa-sub">Kelola beasiswa sekolah: nominal, persen, dan pembebasan penuh.</p>
        </div>
        <button class="btn btn-primary" wire:click="$dispatch('show-add-beasiswa-modal')">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Beasiswa
        </button>
    </div>

    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label for="beasiswa-filter-status" class="form-label">Filter Status</label>
                <select id="beasiswa-filter-status" class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-head">
            <h2 class="sa-card-title"><i class="fa-solid fa-list-ul" aria-hidden="true"></i>Daftar Beasiswa</h2>
            <span class="sa-count">{{ $beasiswas->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th class="text-end">Nilai</th>
                        <th>Berlaku</th>
                        <th>Status</th>
                        <th>Penerima</th>
                        <th class="text-center" style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($beasiswas as $row)
                        <tr>
                            <td><strong>{{ $row->nama }}</strong></td>
                            <td>
                                @if ($row->jenis === 'nominal') Nominal
                                @elseif ($row->jenis === 'persen') Persen ({{ $row->nilai }}%)
                                @else Pembebasan Penuh @endif
                            </td>
                            <td class="text-end">
                                @if ($row->jenis === 'nominal') Rp {{ number_format($row->nilai, 0, ',', '.') }}
                                @elseif ($row->jenis === 'persen') {{ $row->nilai }}%
                                @else Rp 0 @endif
                            </td>
                            <td>{{ $row->berlaku_dari?->format('d/m/Y') }} s.d {{ $row->berlaku_sampai ? $row->berlaku_sampai->format('d/m/Y') : '-' }}</td>
                            <td>
                                <span class="sa-badge {{ $row->status === 'aktif' ? 'sa-hadir' : 'sa-netral' }}">{{ ucfirst($row->status) }}</span>
                            </td>
                            <td>{{ $row->penerima->where('status', 'aktif')->count() }} penerima</td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $row->idbeasiswa }})" aria-label="Edit beasiswa {{ $row->nama }}"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="toggleStatus({{ $row->idbeasiswa }})" aria-label="Toggle status {{ $row->nama }}"><i class="fa-solid fa-toggle-{{ $row->status === 'aktif' ? 'on' : 'off' }}" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><div class="sa-empty"><i class="fa-solid fa-hands-holding-circle" aria-hidden="true"></i><p class="sa-empty-title">Belum ada beasiswa</p><p class="sa-empty-text">Tambahkan beasiswa untuk siswa yang berhak.</p></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="addBeasiswaModal" tabindex="-1" aria-labelledby="addBeasiswaTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addBeasiswaTitle"><i class="fa-solid fa-hands-holding-child me-2" aria-hidden="true"></i>Tambah Beasiswa</h5></div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <label class="form-label" for="add-bea-nama">Nama</label>
                        <input id="add-bea-nama" type="text" class="form-control mb-3" wire:model="nama" placeholder="Contoh: Beasiswa Prestasi">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="add-bea-jenis">Jenis</label>
                                <select id="add-bea-jenis" class="form-select" wire:model.live="jenis">
                                    <option value="nominal">Nominal (Rp)</option>
                                    <option value="persen">Persen (%)</option>
                                    <option value="pembebasan">Pembebasan Penuh</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                @if ($jenis === 'persen')
                                    <label class="form-label" for="add-bea-nilai-persen">Persen (1-100)</label>
                                    <input id="add-bea-nilai-persen" type="number" min="1" max="100" class="form-control" wire:model="nilai" placeholder="1 - 100">
                                @else
                                    <label class="form-label" for="add-bea-nilai-nominal">Nilai (Rp)</label>
                                    <input id="add-bea-nilai-nominal" type="number" min="0" class="form-control" wire:model="nilai" placeholder="0">
                                @endif
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6"><label class="form-label" for="add-bea-dari">Berlaku Dari</label><input id="add-bea-dari" type="date" class="form-control" wire:model="berlaku_dari"></div>
                            <div class="col-md-6"><label class="form-label" for="add-bea-sampai">Berlaku Sampai</label><input id="add-bea-sampai" type="date" class="form-control" wire:model="berlaku_sampai"></div>
                        </div>
                        <label class="form-label mt-2" for="add-bea-alasan">Alasan (opsional)</label>
                        <textarea id="add-bea-alasan" class="form-control" rows="2" wire:model="alasan"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div wire:ignore.self class="modal fade" id="editBeasiswaModal" tabindex="-1" aria-labelledby="editBeasiswaTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="editBeasiswaTitle"><i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Edit Beasiswa</h5></div>
                <form wire:submit.prevent="update">
                    <div class="modal-body">
                        <label class="form-label" for="edit-bea-nama">Nama</label>
                        <input id="edit-bea-nama" type="text" class="form-control mb-3" wire:model="nama">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label" for="edit-bea-jenis">Jenis</label><select id="edit-bea-jenis" class="form-select" wire:model.live="jenis"><option value="nominal">Nominal (Rp)</option><option value="persen">Persen (%)</option><option value="pembebasan">Pembebasan Penuh</option></select></div>
                            <div class="col-md-6">@if ($jenis === 'persen')<label class="form-label" for="edit-bea-nilai-persen">Persen (1-100)</label><input id="edit-bea-nilai-persen" type="number" min="1" max="100" class="form-control" wire:model="nilai">@else<label class="form-label" for="edit-bea-nilai-nominal">Nilai (Rp)</label><input id="edit-bea-nilai-nominal" type="number" min="0" class="form-control" wire:model="nilai">@endif</div>
                        </div>
                        <div class="row g-2 mt-2"><div class="col-md-6"><label class="form-label" for="edit-bea-dari">Berlaku Dari</label><input id="edit-bea-dari" type="date" class="form-control" wire:model="berlaku_dari"></div><div class="col-md-6"><label class="form-label" for="edit-bea-sampai">Berlaku Sampai</label><input id="edit-bea-sampai" type="date" class="form-control" wire:model="berlaku_sampai"></div></div>
                        <label class="form-label mt-2" for="edit-bea-alasan">Alasan (opsional)</label>
                        <textarea id="edit-bea-alasan" class="form-control" rows="2" wire:model="alasan"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.addEventListener('close-beasiswa-modal', () => { $('#addBeasiswaModal, #editBeasiswaModal').modal('hide'); });
    window.addEventListener('show-add-beasiswa-modal', () => $('#addBeasiswaModal').modal('show'));
    window.addEventListener('show-edit-beasiswa-modal', () => $('#editBeasiswaModal').modal('show'));
</script>
@endscript
