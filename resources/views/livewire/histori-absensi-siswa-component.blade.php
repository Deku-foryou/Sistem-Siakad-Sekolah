@section('title', 'Histori Absensi')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-clock-rotate-left me-2 text-success" aria-hidden="true"></i>Histori Absensi</h1>
            <p class="sa-sub">
                Catatan kehadiran @if ($siswa) {{ $siswa->nama }} @else Anda @endif.
                Hanya menampilkan absensi yang sudah diisi.
            </p>
        </div>
        <span class="sa-chip"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Siswa</span>
    </div>

    @if ($siswa)
        {{-- Rekap kehadiran semester & tahun ajaran aktif --}}
        <section class="sa-card mb-3" aria-label="Rekap kehadiran semester aktif">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Rekap Kehadiran</h2>
                        <p class="text-body-secondary small mb-0">
                            Semester {{ $konteks->semesterNama() ?? '-' }} &middot;
                            Tahun Ajaran {{ $konteks->tahunAjaranNama() ?? '-' }} &middot;
                            Tidak terpengaruh filter di bawah
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" wire:click="unduhRekap" wire:loading.attr="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor CSV">
                        <i class="fa-solid fa-file-csv me-1" aria-hidden="true"></i>Unduh Rekap (CSV)
                    </button>
                </div>
                <div class="row g-2">
                    @foreach ([['Harian', $rekapHarian, $persenHarian], ['Per Jam', $rekapPerJam, $persenPerJam]] as [$label, $r, $persen])
                        <div class="col-md-6">
                            <div class="border rounded p-2 h-100 bg-white">
                                <div class="small text-body-secondary mb-1">{{ $label }}</div>
                                <div class="d-flex flex-wrap gap-2 mb-1">
                                    <span class="sa-badge sa-hadir">Hadir {{ $r['hadir'] }}</span>
                                    <span class="sa-badge sa-sakit">Sakit {{ $r['sakit'] }}</span>
                                    <span class="sa-badge sa-izin">Izin {{ $r['izin'] }}</span>
                                    <span class="sa-badge sa-alpha">Alpha {{ $r['alpha'] }}</span>
                                </div>
                                <div class="small text-body-secondary">
                                    <span class="fw-semibold text-body">{{ $r['total'] }}</span> catatan &middot;
                                    Kehadiran <span class="fw-semibold text-body">{{ $persen }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (! $siswa)
        <div class="alert alert-warning" role="alert">
            Akun ini belum terhubung ke data siswa. Histori absensi belum dapat ditampilkan. Hubungi administrator.
        </div>
    @else
        <ul class="nav nav-tabs mb-3" role="tablist" aria-label="Jenis histori absensi">
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === 'harian' ? 'true' : 'false' }}"
                    class="nav-link {{ $tab === 'harian' ? 'active' : '' }}"
                    wire:click="gantiTab('harian')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Absensi Harian</button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === 'per_jam' ? 'true' : 'false' }}"
                    class="nav-link {{ $tab === 'per_jam' ? 'active' : '' }}"
                    wire:click="gantiTab('per_jam')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Absensi Per Jam</button>
            </li>
        </ul>

        @if ($tab === 'harian')
            {{-- ===================== TAB HARIAN ===================== --}}
            <div class="sa-card sa-filter mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="tanggal-dari-harian" class="form-label mb-1 small fw-semibold">Dari tanggal</label>
                        <input type="date" id="tanggal-dari-harian" wire:model.live="tanggalDariHarian" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="tanggal-sampai-harian" class="form-label mb-1 small fw-semibold">Sampai tanggal</label>
                        <input type="date" id="tanggal-sampai-harian" wire:model.live="tanggalSampaiHarian" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="filter-status-harian" class="form-label mb-1 small fw-semibold">Status</label>
                        <select id="filter-status-harian" wire:model.live="statusHarian" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            <option value="hadir">Hadir</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilterHarian" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset filter">Reset filter</button>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="sa-stat">Total {{ $ringkasanHarian['total'] }}</span>
                <span class="sa-stat">Hadir {{ $ringkasanHarian['hadir'] }}</span>
                <span class="sa-stat">Sakit {{ $ringkasanHarian['sakit'] }}</span>
                <span class="sa-stat">Izin {{ $ringkasanHarian['izin'] }}</span>
                <span class="sa-stat">Alpha {{ $ringkasanHarian['alpha'] }}</span>
            </div>

            @if ($absensiHarian && $absensiHarian->total() > 0)
                <div class="sa-card">
                    <div class="table-responsive">
                        <table class="table sa-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 110px;">Tanggal</th>
                                    <th>Hari</th>
                                    <th>Kelas</th>
                                    <th class="text-center">Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($absensiHarian as $row)
                                    <tr>
                                        <td>{{ $row->tanggal?->format('d/m/Y') }}</td>
                                        <td>{{ $namaHari[(int) $row->tanggal?->dayOfWeekIso] ?? '-' }}</td>
                                        <td>{{ $row->kelas?->nama ?? '-' }}</td>
                                        <td class="text-center">
                                            @if ($row->status === 'hadir')
                                                <span class="sa-badge sa-hadir">Hadir</span>
                                            @elseif ($row->status === 'sakit')
                                                <span class="sa-badge sa-sakit">Sakit</span>
                                            @elseif ($row->status === 'izin')
                                                <span class="sa-badge sa-izin">Izin</span>
                                            @else
                                                <span class="sa-badge sa-alpha">Alpha</span>
                                            @endif
                                        </td>
                                        <td>{{ $row->catatan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Menampilkan {{ $absensiHarian->firstItem() }}–{{ $absensiHarian->lastItem() }} dari {{ $absensiHarian->total() }} catatan</small>
                        <div>{{ $absensiHarian->links() }}</div>
                    </div>
                </div>
            @else
                <div class="sa-card">
                    <div class="card-body text-center py-5">
                        <i class="fa-regular fa-clipboard fs-2 text-secondary mb-2" aria-hidden="true"></i>
                        <p class="fw-semibold mb-1">Belum ada catatan absensi harian</p>
                        <p class="text-body-secondary mb-0">
                            @if ($tanggalDariHarian || $tanggalSampaiHarian || $statusHarian !== '')
                                Tidak ada catatan dengan filter yang dipilih. Coba ubah atau reset filter.
                            @else
                                Wali kelas belum mengisi absensi harian untuk Anda pada semester ini.
                            @endif
                        </p>
                        @if ($tanggalDariHarian || $tanggalSampaiHarian || $statusHarian !== '')
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-3" wire:click="resetFilterHarian" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset filter">Reset filter</button>
                        @endif
                    </div>
                </div>
            @endif
        @else
            {{-- ===================== TAB PER JAM ===================== --}}
            <div class="sa-card sa-filter mb-3">
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
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilter" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset filter">Reset filter</button>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="sa-stat">Total {{ $ringkasanPerJam['total'] }}</span>
                <span class="sa-stat">Hadir {{ $ringkasanPerJam['hadir'] }}</span>
                <span class="sa-stat">Sakit {{ $ringkasanPerJam['sakit'] }}</span>
                <span class="sa-stat">Izin {{ $ringkasanPerJam['izin'] }}</span>
                <span class="sa-stat">Alpha {{ $ringkasanPerJam['alpha'] }}</span>
            </div>

            @if ($absensiPerJam && $absensiPerJam->total() > 0)
                <div class="sa-card">
                    <div class="table-responsive">
                        <table class="table sa-table mb-0">
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
                                @foreach ($absensiPerJam as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                                        <td>{{ $namaHari[(int) $row->hari] ?? '-' }}</td>
                                        <td class="text-center">{{ $row->jam_ke ?? '-' }}</td>
                                        <td>{{ $row->waktu_mulai }}–{{ $row->waktu_selesai }}</td>
                                        <td>{{ $row->kelas_nama }}</td>
                                        <td>{{ $row->mapel_nama }}</td>
                                        <td>{{ $row->guru_nama }}</td>
                                        <td class="text-center">
                                            @if ($row->status === 'hadir')
                                                <span class="sa-badge sa-hadir">Hadir</span>
                                            @elseif ($row->status === 'sakit')
                                                <span class="sa-badge sa-sakit">Sakit</span>
                                            @elseif ($row->status === 'izin')
                                                <span class="sa-badge sa-izin">Izin</span>
                                            @else
                                                <span class="sa-badge sa-alpha">Alpha</span>
                                            @endif
                                        </td>
                                        <td>{{ $row->keterangan ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Menampilkan {{ $absensiPerJam->firstItem() }}–{{ $absensiPerJam->lastItem() }} dari {{ $absensiPerJam->total() }} catatan</small>
                        <div>{{ $absensiPerJam->links() }}</div>
                    </div>
                </div>
            @else
                <div class="sa-card">
                    <div class="card-body text-center py-5">
                        <i class="fa-regular fa-clipboard fs-2 text-secondary mb-2" aria-hidden="true"></i>
                        <p class="fw-semibold mb-1">Belum ada catatan absensi per jam</p>
                        <p class="text-body-secondary mb-0">
                            @if ($tanggalDari || $tanggalSampai || $status !== '')
                                Tidak ada catatan dengan filter yang dipilih. Coba ubah atau reset filter.
                            @else
                                Belum ada pertemuan yang diisi absensinya untuk Anda.
                            @endif
                        </p>
                        @if ($tanggalDari || $tanggalSampai || $status !== '')
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-3" wire:click="resetFilter" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset filter">Reset filter</button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    @endif
</div>
