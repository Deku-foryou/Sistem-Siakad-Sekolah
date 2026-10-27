@section('title')
Data Siswa
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Siswa</p></div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" wire:click="bukaImport('siswa')"><i class="fa-solid fa-upload me-1"></i>Import</button>
                    <button class="btn btn-outline-primary" wire:click="$dispatch('show-sync-siswa-modal')"><i class="fa-solid fa-arrows-rotate me-1"></i>Terapkan paket menu ke semua siswa</button>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-siswa-modal')"><i class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3"><input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>NISN</th><th>Nama</th><th>JK</th><th>Status</th><th style="width: 150px; white-space: nowrap;">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($siswas as $s)
                        <tr>
                            <td>{{ $s->nisn }}</td>
                            <td>{{ $s->nama }}</td>
                            <td>{{ $s->jenis_kelamin }}</td>
                            <td>{{ $s->status }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-info text-white" wire:click="detailSiswa({{ $s->idsiswa }})" title="Detail" aria-label="Detail"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning" wire:click="editSiswa({{ $s->idsiswa }})"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationSiswa({{ $s->idsiswa }})"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $siswas->links() }}
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="addSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Tambah Siswa</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="storeSiswa">
                        <div class="mb-2"><label>NISN</label><input class="form-control" wire:model="nisn">@error('nisn')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>NIS</label><input class="form-control" wire:model="nis"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama">@error('nama')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir">@error('tanggal_lahir')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Agama</label><input class="form-control" wire:model="agama"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Nama Ayah</label><input class="form-control" wire:model="nama_ayah"></div>
                        <div class="mb-2"><label>Nama Ibu</label><input class="form-control" wire:model="nama_ibu"></div>
                        <div class="mb-2"><label>Telepon Ortu</label><input class="form-control" wire:model="telepon_ortu"></div>
                        <div class="mb-2"><label>Tahun Masuk</label><input class="form-control" wire:model="tahun_masuk"></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="lulus">lulus</option><option value="pindah">pindah</option><option value="keluar">keluar</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="editSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Siswa</h5></div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateSiswa">
                        <div class="mb-2"><label>NISN</label><input class="form-control" wire:model="nisn"></div>
                        <div class="mb-2"><label>NIS</label><input class="form-control" wire:model="nis"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir"></div>
                        <div class="mb-2"><label>Agama</label><input class="form-control" wire:model="agama"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Nama Ayah</label><input class="form-control" wire:model="nama_ayah"></div>
                        <div class="mb-2"><label>Nama Ibu</label><input class="form-control" wire:model="nama_ibu"></div>
                        <div class="mb-2"><label>Telepon Ortu</label><input class="form-control" wire:model="telepon_ortu"></div>
                        <div class="mb-2"><label>Tahun Masuk</label><input class="form-control" wire:model="tahun_masuk"></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="lulus">lulus</option><option value="pindah">pindah</option><option value="keluar">keluar</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="detailSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="detailSiswaTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailSiswaTitle">Detail Siswa</h5>
                </div>
                <div class="modal-body">
                    @if ($detailSiswa)
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold mb-2" style="width: 64px; height: 64px; font-size: 20px;">
                                {{ collect(preg_split('/\s+/', trim((string) $detailSiswa->nama)))->filter()->take(2)->map(fn ($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))->implode('') ?: '-' }}
                            </div>
                            <h5 class="mb-0">{{ $detailSiswa->nama ?: '-' }}</h5>
                            <div class="text-muted">{{ $detailSiswa->nisn ?: '-' }}</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-bold">Biodata</h6>
                                <dl class="row mb-0">
                                    <dt class="col-5">NISN</dt><dd class="col-7">{{ $detailSiswa->nisn ?: '-' }}</dd>
                                    <dt class="col-5">NIS</dt><dd class="col-7">{{ $detailSiswa->nis ?: '-' }}</dd>
                                    <dt class="col-5">Jenis Kelamin</dt><dd class="col-7">{{ $detailSiswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($detailSiswa->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</dd>
                                    <dt class="col-5">Tempat Lahir</dt><dd class="col-7">{{ $detailSiswa->tempat_lahir ?: '-' }}</dd>
                                    <dt class="col-5">Tanggal Lahir</dt><dd class="col-7">{{ $detailSiswa->tanggal_lahir ? $detailSiswa->tanggal_lahir->locale('id')->translatedFormat('d F Y') : '-' }}</dd>
                                    <dt class="col-5">Agama</dt><dd class="col-7">{{ $detailSiswa->agama ?: '-' }}</dd>
                                    <dt class="col-5">Alamat</dt><dd class="col-7">{{ $detailSiswa->alamat ?: '-' }}</dd>
                                    <dt class="col-5">Nama Ayah</dt><dd class="col-7">{{ $detailSiswa->nama_ayah ?: '-' }}</dd>
                                    <dt class="col-5">Nama Ibu</dt><dd class="col-7">{{ $detailSiswa->nama_ibu ?: '-' }}</dd>
                                    <dt class="col-5">Telepon Ortu</dt><dd class="col-7">{{ $detailSiswa->telepon_ortu ?: '-' }}</dd>
                                    <dt class="col-5">Tahun Masuk</dt><dd class="col-7">{{ $detailSiswa->tahun_masuk ?: '-' }}</dd>
                                    <dt class="col-5">Status</dt><dd class="col-7">{{ $detailSiswa->status ?: '-' }}</dd>
                                </dl>
                            </div>
                            <div class="col-12 col-lg-6">
                                <h6 class="fw-bold">Akun</h6>
                                <dl class="row mb-3">
                                    <dt class="col-5">Username</dt><dd class="col-7">{{ $detailSiswa->user?->username ?: '-' }}</dd>
                                    <dt class="col-5">Status Akun</dt><dd class="col-7">{{ $detailSiswa->user ? ($detailSiswa->user->wajib_ganti_password ? 'Belum ganti password' : 'Sudah diganti') : '-' }}</dd>
                                </dl>

                                <h6 class="fw-bold">Kelas Tahun Ajaran Aktif</h6>
                                @if (! $tahunAjaranAktif)
                                    <p class="text-muted mb-3">Belum ada tahun ajaran aktif</p>
                                @elseif (! $kelasIndukAktif && $kelasPilihanAktif->isEmpty())
                                    <p class="text-muted mb-3">Belum ditempatkan di kelas</p>
                                @else
                                    <dl class="row mb-3">
                                        <dt class="col-5">Kelas Induk</dt>
                                        <dd class="col-7">
                                            {{ $kelasIndukAktif ? $kelasIndukAktif->nama.' ('.$kelasIndukAktif->tingkat.')' : '-' }}
                                            @if ($kelasIndukAktif)
                                                <div class="small text-muted">Wali: {{ $kelasIndukAktif->wali?->nama ?: '-' }}</div>
                                            @endif
                                        </dd>
                                        <dt class="col-5">Mapel Pilihan</dt>
                                        <dd class="col-7">
                                            @forelse ($kelasPilihanAktif as $kelasPilihan)
                                                <div>{{ $kelasPilihan->nama }} ({{ $kelasPilihan->tingkat }})</div>
                                            @empty
                                                -
                                            @endforelse
                                        </dd>
                                    </dl>
                                @endif

                                <h6 class="fw-bold">Riwayat Kelas</h6>
                                @if ($riwayatKelas->isEmpty())
                                    <p class="text-muted mb-0">Belum ditempatkan di kelas</p>
                                @else
                                    <ul class="list-group list-group-flush">
                                        @foreach ($riwayatKelas as $penempatan)
                                            <li class="list-group-item px-0">
                                                <div class="d-flex justify-content-between gap-2">
                                                    <span>{{ $penempatan->kelas?->nama ?: '-' }} · {{ $penempatan->kelas?->jenis === 'induk' ? 'Induk' : ($penempatan->kelas?->jenis === 'mapel_pilihan' ? 'Mapel pilihan' : '-') }}</span>
                                                    @if ($tahunAjaranAktif && (int) $penempatan->kelas?->tahunajaran_idtahunajaran === (int) $tahunAjaranAktif->idtahunajaran)
                                                        <span class="badge bg-primary">Aktif</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">
                                                    Tahun Ajaran: {{ $penempatan->kelas?->tahunajaran?->nama ?: '-' }}
                                                    · Wali: {{ $penempatan->kelas?->wali?->nama ?: '-' }}
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Data siswa tidak tersedia.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="tutupDetailSiswa">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="deleteSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Delete Confirmation</h5></div>
                <div class="modal-body pt-4 pb-4"><h6>Are you sure? You want to delete this data ?</h6></div>
                <div class="modal-footer">
                    <button class="btn btn-danger" wire:click="deleteSiswa()">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="syncSiswaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="syncSiswaTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="syncSiswaTitle">Terapkan paket menu Siswa</h5></div>
                <div class="modal-body">Paket menu Siswa akan diterapkan ke semua akun siswa. Menu yang sudah ada, termasuk menu manual, tidak akan dihapus.</div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="sinkronkanPaket">Terapkan paket</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.partials.import-modal')
</div>

<script>
    window.addEventListener('close-siswa-modal', event => { $('#addSiswaModal').modal('hide'); $('#editSiswaModal').modal('hide'); $('#deleteSiswaModal').modal('hide'); });
    window.addEventListener('show-add-siswa-modal', event => { $('#addSiswaModal').modal('show'); });
    window.addEventListener('show-edit-siswa-modal', event => { $('#editSiswaModal').modal('show'); });
    window.addEventListener('show-detail-siswa-modal', event => { $('#detailSiswaModal').modal('show'); });
    window.addEventListener('close-detail-siswa-modal', event => { $('#detailSiswaModal').modal('hide'); });
    window.addEventListener('show-delete-confirmation-siswa-modal', event => { $('#deleteSiswaModal').modal('show'); });
    window.addEventListener('show-sync-siswa-modal', () => { bootstrap.Modal.getOrCreateInstance(document.getElementById('syncSiswaModal')).show(); });
    window.addEventListener('close-sync-siswa-modal', () => { bootstrap.Modal.getOrCreateInstance(document.getElementById('syncSiswaModal')).hide(); });
</script>
