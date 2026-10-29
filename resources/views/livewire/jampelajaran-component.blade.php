@section('title')
Jam Pelajaran
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Jam Pelajaran</p>
                    <p class="text-muted">Hari: {{ \App\Support\Hari::nama((int)$hari) }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" wire:click="bukaTambah">Tambah Jam</button>
                </div>
            </div>
        </div>
        
        @if (! $pola || $rows->isEmpty())
            <div class="card border-0 shadow-sm mb-3"><div class="card-body text-center">
                <p class="mb-3">Belum ada jam untuk hari {{ \App\Support\Hari::nama((int)$hari) }}.</p>
                <button class="btn btn-primary" wire:click="bukaTambah">Buat jam hari ini</button>
            </div></div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Urutan</th><th>Jenis</th><th>Jam Ke</th><th>Mulai</th><th>Selesai</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->urutan }}</td>
                                <td>{{ $row->jenis }}</td>
                                <td>{{ $row->jam_ke }}</td>
                                <td>{{ substr($row->mulai, 0, 5) }}</td>
                                <td>{{ substr($row->selesai, 0, 5) }}</td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-warning" wire:click="edit({{ $row->idjampelajaran }})"><i class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn btn-sm btn-danger" wire:click="deleteConfirmation({{ $row->idjampelajaran }})"><i class="fa-solid fa-trash-can"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">Belum ada jampelajaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Modal Tambah --}}
    <div wire:ignore.self class="modal fade" id="addJamPelajaranModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Tambah Jam</h5></div>
            <form wire:submit.prevent="store">
                <div class="modal-body">
                    <label>Jenis</label><select class="form-select mb-2" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
                    @if ($jenis === 'pelajaran')<label>Jam Ke</label><input type="number" min="1" class="form-control mb-2" wire:model="jam_ke">@endif
                    <label>Mulai</label><input type="time" class="form-control mb-2" wire:model="mulai">
                    <label>Selesai</label><input type="time" class="form-control mb-2" wire:model="selesai">
                    @if ($bersama)
                        <label class="mt-2">Berlaku untuk</label>
                        <div class="form-check"><input class="form-check-input" type="radio" name="cakupan" value="hari_ini" wire:model="cakupan"><label class="form-check-label">Hanya {{ \App\Support\Hari::nama((int)$hari) }}</label></div>
                        <div class="form-check"><input class="form-check-input" type=\"radio\" name=\"cakupan\" value=\"semua\" wire:model=\"cakupan\"><label class=\"form-check-label\">Semua hari yang memakai jam ini</label></div>
                    @endif
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Simpan</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button></div>
            </form>
        </div></div>
    </div>

    {{-- Modal Edit --}}
    <div wire:ignore.self class="modal fade" id="editJamPelajaranModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Jam</h5></div>
            <form wire:submit.prevent="update">
                <div class="modal-body">
                    <label>Jenis</label><select class="form-select mb-2" wire:model.live="jenis"><option value="pelajaran">Pelajaran</option><option value="istirahat">Istirahat</option></select>
                    @if ($jenis === 'pelajaran')<label>Jam Ke</label><input type="number" min=\"1\" class=\"form-control mb-2\" wire:model=\"jam_ke\">@endif
                    <label>Mulai</label><input type="time" class=\"form-control mb-2\" wire:model=\"mulai\">
                    <label>Selesai</label><input type=\"time\" class=\"form-control mb-2\" wire:model=\"selesai\">
                    @if ($bersama)
                        <label class="mt-2">Berlaku untuk</label>
                        <div class="form-check"><input class=\"form-check-input\" type=\"radio\" name=\"cakupan\" value=\"hari_ini\" wire:model=\"cakupan\"><label class=\"form-check-label\">Hanya {{ \App\Support\Hari::nama((int)$hari) }}</label></div>
                        <div class=\"form-check\"><input class=\"form-check-input\" type=\"radio\" name=\"cakupan\" value=\"semua\" wire:model=\"cakupan\"><label class=\"form-check-label\">Semua hari yang memakai jam ini</label></div>
                    @endif
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Simpan Perubahan</button><button type="button" class="btn btn-secondary" data-bs-dismiss=\"modal" wire:click="close">Tutup</button></div>
            </form>
        </div></div>
    </div>

    {{-- Modal Delete --}}
    <div wire:ignore.self class="modal fade" id="deleteJamPelajaranModal" tabindex="-1">
        <div class=\"modal-dialog modal-dialog-centered\"><div class=\"modal-content\">
            <div class=\"modal-header\"><h5 class=\"modal-title\">Konfirmasi Hapus</h5></div>
            <div class=\"modal-body\">Yakin ingin menghapus jam ini?</div>
            <div class=\"modal-footer\"><button class=\"btn btn-danger\" wire:click=\"delete\">Ya, Hapus</button><button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Batal</button></div>
        </div></div>
    </div>
</div>

<script>
    window.addEventListener('show-add-jampelajaran-modal', () => { $('#addJamPelajaranModal').modal('show'); });
    window.addEventListener('show-edit-jampelajaran-modal', () => { $('#editJamPelajaranModal').modal('show'); });
    window.addEventListener('show-delete-jampelajaran-modal', () => { $('#deleteJamPelajaranModal').modal('show'); });
    window.addEventListener('close-jampelajaran-modal', () => { 
        $('#addJamPelajaranModal').modal('hide'); 
        $('#editJamPelajaranModal').modal('hide'); 
        $('#deleteJamPelajaranModal').modal('hide'); 
    });
</script>