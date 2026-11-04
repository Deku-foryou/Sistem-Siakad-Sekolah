@section('title', 'Histori Absensi')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Histori Absensi</h1>
            <p class="text-body-secondary mb-0">
                Catatan kehadiran @if ($siswa) {{ $siswa->nama }} @else Anda @endif per pertemuan.
                Hanya menampilkan pertemuan yang sudah diisi absensinya.
            </p>
        </div>
        <span class="badge bg-success px-3 py-2">Siswa</span>
    </div>

    @if (! $siswa)
        <div class="alert alert-warning" role="alert">
            Akun ini belum terhubung ke data siswa. Histori absensi belum dapat ditampilkan. Hubungi administrator.
        </div>
    @else
        <!-- Filter -->
        <div class="card card-body shadow-sm mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="tanggal-dari" class="form-label mb-1 small fw-semibold">Dari tanggal</label>
                    <input type="date" id="tanggal-dari" wire:model.live="tanggalDari" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="tanggal-sampai" class="form-label mb-1 small fw-semibold">Sampai tanggal</label>
                    <input type="date" id="tanggal-sampai" wire:model.live="tanggalSampai" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="filter-status" class="form-label mb-1 small fw-semibold">Status</label>
                    <select id="filter-status" wire:model.live="status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        <option value="hadir">Hadir</option>
                        <option value="sakit">Sakit</option>
                        <option value="izin">Izin</option>
                        <option value="alpha">Alpha</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilter">Reset filter</button>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-light text-dark border px-3 py-2">Total {{ $ringkasan['total'] }}</span>
            <span class="badge bg-success px-3 py-2">Hadir {{ $ringkasan['hadir'] }}</span>
            <span class="badge bg-warning text-dark px-3 py-2">Sakit {{ $ringkasan['sakit'] }}</span>
            <span class="badge bg-info text-dark px-3 py-2">Izin {{ $ringkasan['izin'] }}</span>
            <span class="badge bg-danger px-3 py-2">Alpha {{ $ringkasan['alpha'] }}</span>
        </div>

        <!-- Tabel -->
        @if ($absensi && $absensi->total() > 0)
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 110px;">Tanggal</th>
                                <th>Hari</th>
                                <th class="text-center">Jam Ke</th>
                                <th>Waktu</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru</th>
                                <th class="text-center">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($absensi as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                                    <td>{{ $namaHari[(int) $row->hari] ?? '-' }}</td>
                                    <td class="text-center">{{ $row->jam_ke ?? '-' }}</td>
                                    <td>{{ $row->waktu_mulai }}&ndash;{{ $row->waktu_selesai }}</td>
                                    <td>{{ $row->kelas_nama }}</td>
                                    <td>{{ $row->mapel_nama }}</td>
                                    <td>{{ $row->guru_nama }}</td>
                                    <td class="text-center">
                                        @if ($row->status === 'hadir')
                                            <span class="badge bg-success">Hadir</span>
                                        @elseif ($row->status === 'sakit')
                                            <span class="badge bg-warning text-dark">Sakit</span>
                                        @elseif ($row->status === 'izin')
                                            <span class="badge bg-info text-dark">Izin</span>
                                        @else
                                            <span class="badge bg-danger">Alpha</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->keterangan ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Menampilkan {{ $absensi->firstItem() }}&ndash;{{ $absensi->lastItem() }} dari {{ $absensi->total() }} catatan</small>
                    <div>{{ $absensi->links() }}</div>
                </div>
            </div>
        @else
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fa-regular fa-clipboard fs-2 text-secondary mb-2" aria-hidden="true"></i>
                    <p class="fw-semibold mb-1">Belum ada catatan absensi</p>
                    <p class="text-body-secondary mb-0">
                        @if ($tanggalDari || $tanggalSampai || $status !== '')
                            Tidak ada catatan dengan filter yang dipilih. Coba ubah atau reset filter.
                        @else
                            Belum ada pertemuan yang diisi absensinya untuk Anda.
                        @endif
                    </p>
                    @if ($tanggalDari || $tanggalSampai || $status !== '')
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-3" wire:click="resetFilter">Reset filter</button>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
