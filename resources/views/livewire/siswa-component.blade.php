@section('title')
Data Siswa
@endsection

<div>
    <div class="container-fluid mb-5 px-4" style="margin-top: 20px">
        <div class="container-fluid px-0 p-3 rounded-top">
            <div class="d-lg-flex justify-content-between align-items-center mb-lg-1">
                <div class="mb-3 mb-lg-0"><p class="p-0 m-0 h3 fw-bold ">Siswa</p></div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" wire:click="bukaImport('siswa')" data-bs-toggle="tooltip" data-bs-placement="top" title="Impor data"><i class="fa-solid fa-upload me-1"></i>Import</button>
                    <button class="btn btn-outline-primary" wire:click="$dispatch('show-sync-siswa-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Buka formulir"><i class="fa-solid fa-arrows-rotate me-1"></i>Terapkan paket menu ke semua siswa</button>
                    <button class="btn btn-primary" wire:click="$dispatch('show-add-siswa-modal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data" aria-label="Tambah data"><i class="fa-solid fa-plus "></i></button>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3"><input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Nama</th><th>Kelas</th><th>Rombel</th><th>NISN</th><th>Status</th><th>JK</th><th style="width: 150px; white-space: nowrap;">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($siswas as $s)
                        @php
                            $kelas = $s->kelassiswa->first()?->kelas;
                        @endphp
                        <tr>
                            <td>{{ $s->nama }}</td>
                            <td>{{ $kelas ? $kelas->nama . ' (' . $kelas->tingkat . ')' : '-' }}</td>
                            <td>{{ $kelas ? $kelas->rombel : '-' }}</td>
                            <td>{{ $s->nisn }}</td>
                            <td>{{ $s->status }}</td>
                            <td>{{ $s->jenis_kelamin }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-info text-white" wire:click="detailSiswa({{ $s->idsiswa }})" title="Detail" aria-label="Detail" data-bs-toggle="tooltip" data-bs-placement="top"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning" wire:click="editSiswa({{ $s->idsiswa }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data" aria-label="Ubah data"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button class="btn btn-sm btn-danger" wire:click="deleteConfirmationSiswa({{ $s->idsiswa }})" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)" aria-label="Hapus data (ada konfirmasi)"><i class="fa-solid fa-trash-can"></i></button>
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
                    <form wire:submit="storeSiswa">
                        <div class="mb-2"><label>NISN</label><input class="form-control" wire:model="nisn">@error('nisn')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>NIS</label><input class="form-control" wire:model="nis"></div>
                        <div class="mb-2"><label>NIK</label><input class="form-control" wire:model="nik"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama">@error('nama')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir">@error('tanggal_lahir')<span class="text-danger">{{ $message }}</span>@enderror</div>
                        <div class="mb-2"><label>Agama</label><input class="form-control" wire:model="agama"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Telepon Siswa</label><input class="form-control" wire:model="telepon_siswa"></div>
                        <div class="mb-2"><label>Email Siswa</label><input type="email" class="form-control" wire:model="email"></div>
                        <div class="mb-2"><label>Nama Ayah</label><input class="form-control" wire:model="nama_ayah"></div>
                        <div class="mb-2"><label>Nama Ibu</label><input class="form-control" wire:model="nama_ibu"></div>
                        <div class="mb-2"><label>Telepon Ortu</label><input class="form-control" wire:model="telepon_ortu"></div>
                        <div class="mb-2"><label>Tahun Masuk</label><input class="form-control" wire:model="tahun_masuk"></div>
                        <div class="mb-2"><label>Asal Sekolah</label><input class="form-control" wire:model="asal_sekolah"></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="lulus">lulus</option><option value="pindah">pindah</option><option value="keluar">keluar</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
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
                    <form wire:submit="updateSiswa">
                        <div class="mb-2"><label>NISN</label><input class="form-control" wire:model="nisn"></div>
                        <div class="mb-2"><label>NIS</label><input class="form-control" wire:model="nis"></div>
                        <div class="mb-2"><label>NIK</label><input class="form-control" wire:model="nik"></div>
                        <div class="mb-2"><label>Nama</label><input class="form-control" wire:model="nama"></div>
                        <div class="mb-2"><label>JK</label><select class="form-select" wire:model="jenis_kelamin"><option value="L">L</option><option value="P">P</option></select></div>
                        <div class="mb-2"><label>Tempat Lahir</label><input class="form-control" wire:model="tempat_lahir"></div>
                        <div class="mb-2"><label>Tanggal Lahir</label><input type="date" class="form-control" wire:model="tanggal_lahir"></div>
                        <div class="mb-2"><label>Agama</label><input class="form-control" wire:model="agama"></div>
                        <div class="mb-2"><label>Alamat</label><textarea class="form-control" wire:model="alamat"></textarea></div>
                        <div class="mb-2"><label>Telepon Siswa</label><input class="form-control" wire:model="telepon_siswa"></div>
                        <div class="mb-2"><label>Email Siswa</label><input type="email" class="form-control" wire:model="email"></div>
                        <div class="mb-2"><label>Nama Ayah</label><input class="form-control" wire:model="nama_ayah"></div>
                        <div class="mb-2"><label>Nama Ibu</label><input class="form-control" wire:model="nama_ibu"></div>
                        <div class="mb-2"><label>Telepon Ortu</label><input class="form-control" wire:model="telepon_ortu"></div>
                        <div class="mb-2"><label>Tahun Masuk</label><input class="form-control" wire:model="tahun_masuk"></div>
                        <div class="mb-2"><label>Asal Sekolah</label><input class="form-control" wire:model="asal_sekolah"></div>
                        <div class="mb-2"><label>Status</label><select class="form-select" wire:model="status"><option value="aktif">aktif</option><option value="lulus">lulus</option><option value="pindah">pindah</option><option value="keluar">keluar</option></select></div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Simpan Perubahan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
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
                <div class="modal-body detail-modal">
                    @if ($detailSiswa)
                        @php
                            $siswaStatus = match ($detailSiswa->status) {
                                'aktif' => 'Aktif',
                                'lulus' => 'Lulus',
                                'pindah' => 'Pindah',
                                'keluar' => 'Keluar',
                                default => $detailSiswa->status ?: '-',
                            };
                            $biodataRows = [
                                'NISN' => $detailSiswa->nisn ?: '-',
                                'NIS' => $detailSiswa->nis ?: '-',
                                'Jenis Kelamin' => $detailSiswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($detailSiswa->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                                'Tempat Lahir' => $detailSiswa->tempat_lahir ?: '-',
                                'Tanggal Lahir' => $detailSiswa->tanggal_lahir ? $detailSiswa->tanggal_lahir->locale('id')->translatedFormat('d F Y') : '-',
                                'Agama' => $detailSiswa->agama ? mb_convert_case($detailSiswa->agama, MB_CASE_TITLE, 'UTF-8') : '-',
                                'Alamat' => $detailSiswa->alamat ?: '-',
                                'NIK' => $detailSiswa->nik ?: '-',
                                'Telepon Siswa' => $detailSiswa->telepon_siswa ?: '-',
                                'Email Siswa' => $detailSiswa->email ?: '-',
                                'Asal Sekolah' => $detailSiswa->asal_sekolah ?: '-',
                                'Nama Ayah' => $detailSiswa->nama_ayah ?: '-',
                                'Nama Ibu' => $detailSiswa->nama_ibu ?: '-',
                                'Telepon Ortu' => $detailSiswa->telepon_ortu ?: '-',
                                'Tahun Masuk' => $detailSiswa->tahun_masuk ?: '-',
                                'Status' => $siswaStatus,
                            ];
                            $akunRows = [
                                'Username' => $detailSiswa->user?->username ?: '-',
                                'Status Akun' => $detailSiswa->user ? 'Terdaftar' : '-',
                            ];
                            if (! $tahunAjaranAktif) {
                                $kelasAktifRows = ['Informasi' => 'Belum ada tahun ajaran aktif'];
                            } elseif (! $kelasIndukAktif && $kelasPilihanAktif->isEmpty()) {
                                $kelasAktifRows = ['Informasi' => 'Belum ditempatkan di kelas'];
                            } else {
                                $kelasAktifRows = [
                                    'Tahun Ajaran' => $tahunAjaranAktif->nama ?: '-',
                                    'Tingkat' => $kelasIndukAktif?->tingkat ?: '-',
                                    'Jurusan' => $kelasIndukAktif?->jurusan?->nama ?: '-',
                                    'Rombel' => $kelasIndukAktif?->rombel ?: '-',
                                    'Nama Kelas' => $kelasIndukAktif?->nama ?: '-',
                                    'Wali Kelas' => $kelasIndukAktif?->wali?->nama ?: '-',
                                    'Mapel Pilihan' => $kelasPilihanAktif->isNotEmpty()
                                        ? $kelasPilihanAktif->map(fn ($kelasPilihan) => $kelasPilihan->nama.' ('.$kelasPilihan->tingkat.')')->all()
                                        : '-',
                                ];
                            }
                        @endphp

                        @include('livewire.partials.detail-header', [
                            'name' => $detailSiswa->nama,
                            'numberLabel' => 'NISN',
                            'number' => $detailSiswa->nisn,
                            'status' => $detailSiswa->status,
                        ])

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                @include('livewire.partials.detail-card', ['title' => 'Biodata', 'rows' => $biodataRows])
                            </div>
                            <div class="col-12 col-lg-6 d-flex flex-column gap-3">
                                @include('livewire.partials.detail-card', ['title' => 'Akun', 'rows' => $akunRows])
                                @include('livewire.partials.detail-card', ['title' => 'Kelas Tahun Ajaran Aktif', 'rows' => $kelasAktifRows])
                            </div>
                            <div class="col-12">
                                <section class="detail-card">
                                    <h6 class="detail-card-title mb-0">Riwayat Kelas</h6>
                                    <div class="p-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless detail-history-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Tahun Ajaran</th>
                                                        <th scope="col">Kelas</th>
                                                        <th scope="col">Jenis</th>
                                                        <th scope="col">Wali</th>
                                                        <th scope="col">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($riwayatKelas as $penempatan)
                                                        @php
                                                            $kelasRiwayat = $penempatan->kelas;
                                                            $riwayatAktif = $tahunAjaranAktif
                                                                && (int) $kelasRiwayat?->tahunajaran_idtahunajaran === (int) $tahunAjaranAktif->idtahunajaran;
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $kelasRiwayat?->tahunajaran?->nama ?: '-' }}</td>
                                                            <td>{{ $kelasRiwayat?->nama ? $kelasRiwayat->nama.' ('.$kelasRiwayat->tingkat.')' : '-' }}</td>
                                                            <td>{{ $kelasRiwayat?->jenis === 'induk' ? 'Induk' : ($kelasRiwayat?->jenis === 'mapel_pilihan' ? 'Mapel pilihan' : '-') }}</td>
                                                            <td>{{ $kelasRiwayat?->wali?->nama ?: '-' }}</td>
                                                            <td>@if ($riwayatAktif)<span class="badge bg-success">Aktif</span>@endif</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="5">Belum ada riwayat</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Data siswa tidak tersedia.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="tutupDetailSiswa" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button>
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
                    <button class="btn btn-danger" wire:click="deleteSiswa()" data-bs-toggle="tooltip" data-bs-placement="top" title="Ya, lanjutkan">Ya</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close()" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Close</button>
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
                    <button class="btn btn-primary" wire:click="sinkronkanPaket" data-bs-toggle="tooltip" data-bs-placement="top" title="Sinkronkan data/paket">Terapkan paket</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
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