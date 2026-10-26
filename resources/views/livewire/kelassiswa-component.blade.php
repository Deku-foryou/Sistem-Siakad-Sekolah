@section('title')
Penempatan Siswa
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Penempatan Siswa</p></div>
                <div>
                    <button class="btn btn-success" wire:click="bukaImport('penempatan')"><i class="fa-solid fa-upload me-1"></i>Import</button>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label>Tahun Ajaran</label>
                        <select class="form-select" wire:model.live="tahunajaran_id">
                            <option value="">Pilih</option>
                            @foreach ($tahunajarans as $t)<option value="{{ $t->idtahunajaran }}">{{ $t->nama }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Kelas</label>
                        <select class="form-select" wire:model.live="kelas_id">
                            <option value="">Pilih</option>
                            @foreach ($kelasList as $k)<option value="{{ $k->idkelas }}">{{ $k->nama }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        @if ($kelas_id)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <h6>Tambah Siswa</h6>
                    <input type="text" class="form-control mb-2" placeholder="Cari nama/NISN..." wire:model.live.debounce.300ms="search">
                    <select class="form-select mb-2" multiple wire:model="siswa_ids" size="8">
                        @foreach ($siswaAktif as $s)
                            <option value="{{ $s->idsiswa }}">{{ $s->nama }} ({{ $s->nisn }})</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" wire:click="tambahSiswa">Tambahkan</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Nama</th><th>NISN</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @if ($members)
                            @foreach ($members as $m)
                                <tr>
                                    <td>{{ $m->siswa->nama ?? '-' }}</td>
                                    <td>{{ $m->siswa->nisn ?? '-' }}</td>
                                    <td><button class="btn btn-sm btn-danger" wire:click="keluarkan({{ $m->idkelassiswa }})">Keluarkan</button></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                @if ($members) {{ $members->links() }} @endif
            </div>
        @endif
    </div>
    @include('livewire.partials.import-modal')
</div>
