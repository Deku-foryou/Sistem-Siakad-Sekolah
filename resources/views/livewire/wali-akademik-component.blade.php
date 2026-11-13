@section('title', 'Akses Akademik Wali')

<div>
    <div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

        <div class="sa-head">
            <div>
                <h1 class="sa-title"><i class="fa-solid fa-graduation-cap me-2 text-success" aria-hidden="true"></i>Akses Akademik Wali</h1>
                <p class="sa-sub">Read-only. Data akademik siswa dari relasi wali yang terverifikasi.</p>
            </div>
        </div>

        @if ($konteks->valid())
            <div class="small text-muted mb-3">
                <i class="fa-regular fa-calendar me-1"></i>Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot; Semester {{ $konteks->semesterNama() }}
            </div>
        @else
            <div class="alert alert-warning small mb-3">Tahun ajaran atau semester aktif belum dikonfigurasi. Data akademik tidak tersedia.</div>
        @endif

        @if ($relasiList->isEmpty())
            <div class="alert alert-warning">Belum ada relasi wali-siswa yang terverifikasi. Akses akademik belum tersedia.</div>
        @else
            @if ($gagalMuat)
                <div class="alert alert-danger">
                    Gagal memuat data akademik siswa. Silakan muat ulang halaman.
                    @if (config('app.debug'))
                        <div class="small text-body-secondary mt-2">Keterangan teknis tercatat di log aplikasi.</div>
                    @endif
                </div>
            @endif

            <div class="mb-3">
                <label for="wali-siswa-select" class="form-label">Pilih siswa</label>
                <select id="wali-siswa-select" class="form-select" wire:change="pilihSiswa($event.target.value)">
                    <option value="">-- pilih --</option>
                    @foreach ($relasiList as $r)
                        <option value="{{ $r->siswa_idsiswa }}" {{ (string)$r->siswa_idsiswa === (string)$siswaId ? 'selected' : '' }}>
                            {{ $r->siswa->nama ?? '-' }} ({{ $r->siswa->nis ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($siswaId !== '')
                <ul class="nav nav-tabs mb-3 flex-wrap">
                    <li class="nav-item"><button class="nav-link {{ $tab === 'nilai' ? 'active' : '' }}" wire:click="gantiTab('nilai')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Nilai / Rapor</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'rekap' ? 'active' : '' }}" wire:click="gantiTab('rekap')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Rekap Kehadiran</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'ketidakhadiran' ? 'active' : '' }}" wire:click="gantiTab('ketidakhadiran')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Ketidakhadiran</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'catatan' ? 'active' : '' }}" wire:click="gantiTab('catatan')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Catatan Wali Kelas</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'perkembangan' ? 'active' : '' }}" wire:click="gantiTab('perkembangan')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Perkembangan Akademik</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'absensi_harian' ? 'active' : '' }}" wire:click="gantiTab('absensi_harian')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Absensi Harian</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'absensi_perjam' ? 'active' : '' }}" wire:click="gantiTab('absensi_perjam')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Absensi Per Jam</button></li>
                    <li class="nav-item"><button class="nav-link {{ $tab === 'jadwal' ? 'active' : '' }}" wire:click="gantiTab('jadwal')" data-bs-toggle="tooltip" data-bs-placement="top" title="Pindah tab">Jadwal</button></li>
                </ul>

                @if ($tab === 'nilai')
                    @forelse ($dataNilai as $d)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white py-2 fw-semibold">
                                {{ $d['kelas']->nama ?? '-' }} — {{ $d['semester']->nama ?? '-' }} ({{ $d['semester']->tahunajaran->nama ?? '' }})
                            </div>
                            <div class="card-body p-0">
                                <table class="table sa-table table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Mapel</th><th class="text-center">Nilai Akhir</th><th class="text-center">KKTP</th></tr></thead>
                                    <tbody>
                                        @foreach ($d['nilai'] as $n)
                                            <tr>
                                                <td>{{ $n->mapel->nama ?? '-' }}</td>
                                                <td class="text-center">{{ number_format((float)$n->nilai_akhir, 2) }}</td>
                                                <td class="text-center">{{ $n->nilai_kktp !== null ? number_format((float)$n->nilai_kktp, 2) : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-body-secondary">Belum ada rapor final yang tersedia.</p>
                    @endforelse

                @elseif ($tab === 'rekap')
                    @if ($dataRekap === null)
                        <p class="text-body-secondary">Konteks akademik tidak valid atau belum tersedia.</p>
                    @else
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h2 class="h6 fw-bold mb-0">Rekap Kehadiran Semester Aktif</h2>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="unduhRekap" wire:loading.attr="disabled" wire:target="unduhRekap" data-bs-toggle="tooltip" data-bs-placement="top" title="Ekspor CSV">
                                <span wire:loading.remove wire:target="unduhRekap"><i class="fa-solid fa-file-csv me-1"></i>Unduh CSV</span>
                                <span wire:loading wire:target="unduhRekap"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyiapkan&hellip;</span>
                            </button>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="display-6 fw-bold text-success">{{ $dataRekap['hadir'] }}</div><div class="small text-body-secondary">Hadir</div></div></div>
                            <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="display-6 fw-bold text-warning">{{ $dataRekap['sakit'] }}</div><div class="small text-body-secondary">Sakit</div></div></div>
                            <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="display-6 fw-bold text-info">{{ $dataRekap['izin'] }}</div><div class="small text-body-secondary">Izin</div></div></div>
                            <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="display-6 fw-bold text-danger">{{ $dataRekap['alpha'] }}</div><div class="small text-body-secondary">Alpha</div></div></div>
                        </div>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="small text-body-secondary">Total hari</div>
                                    <div class="h5 fw-bold mb-0">{{ $dataRekap['total'] }}</div>
                                </div>
                                <div>
                                    <div class="small text-body-secondary">Tahun Ajaran / Semester</div>
                                    <div class="h6 fw-semibold mb-0">{{ $konteks->tahunAjaranNama() ?? '-' }} / {{ $konteks->semesterNama() ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="small text-body-secondary">Persentase kehadiran</div>
                                    <div class="h4 fw-bold mb-0 text-primary">{{ $dataRekap['persen'] }}</div>
                                </div>
                            </div>
                        </div>
                        <p class="text-body-secondary small mt-3 mb-0">Rekap dihitung dari absensi harian (utama) yang dilengkapi absensi per jam untuk tanggal yang tidak memiliki absensi harian. Periode dibatasi mulai dari tanggal relasi wali aktif.</p>
                    @endif

                @elseif ($tab === 'ketidakhadiran')
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.live="filterStatus">
                                <option value="semua">Semua (selain hadir)</option>
                                <option value="sakit">Sakit</option>
                                <option value="izin">Izin</option>
                                <option value="alpha">Alpha</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari tanggal</label>
                            <input type="date" class="form-control" wire:model.live="filterDari">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai tanggal</label>
                            <input type="date" class="form-control" wire:model.live="filterSampai">
                        </div>
                    </div>

                    <h6 class="fw-semibold">Histori Absen Harian</h6>
                    <div class="table-responsive mb-3">
                        <table class="table sa-table table-bordered">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Kelas</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($dataKetidakhadiranHarian ?? [] as $a)
                                    <tr>
                                        <td>{{ $a->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $a->kelas?->nama ?? '-' }}</td>
                                        <td class="text-center">
                                            @php($st = strtolower((string) $a->status))
                                            @if ($st === 'hadir') <span class="sa-badge sa-hadir">Hadir</span>
                                            @elseif ($st === 'sakit') <span class="sa-badge sa-sakit">Sakit</span>
                                            @elseif ($st === 'izin') <span class="sa-badge sa-izin">Izin</span>
                                            @elseif ($st === 'alpha') <span class="sa-badge sa-alpha">Alpha</span>
                                            @else <span class="sa-badge sa-netral">{{ $a->status }}</span> @endif
                                        </td>
                                        <td>{{ $a->catatan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-body-secondary">Tidak ada ketidakhadiran harian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if ($dataKetidakhadiranHarian) {{ $dataKetidakhadiranHarian->links() }} @endif
                    </div>

                    <h6 class="fw-semibold">Histori Absen Per Jam</h6>
                    <div class="table-responsive">
                        <table class="table sa-table table-bordered">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($dataKetidakhadiranPerjam ?? [] as $a)
                                    <tr>
                                        <td>{{ $a->tanggal && strtotime((string) $a->tanggal) ? date('d/m/Y', strtotime((string) $a->tanggal)) : '-' }}</td>
                                        <td>{{ $a->jam_ke ?? '-' }}</td>
                                        <td>{{ $a->kelas_nama ?? '-' }}</td>
                                        <td>{{ $a->mapel_nama ?? '-' }}</td>
                                        <td class="text-center">
                                            @php($st = strtolower((string) $a->status))
                                            @if ($st === 'hadir') <span class="sa-badge sa-hadir">Hadir</span>
                                            @elseif ($st === 'sakit') <span class="sa-badge sa-sakit">Sakit</span>
                                            @elseif ($st === 'izin') <span class="sa-badge sa-izin">Izin</span>
                                            @elseif ($st === 'alpha') <span class="sa-badge sa-alpha">Alpha</span>
                                            @else <span class="sa-badge sa-netral">{{ $a->status }}</span> @endif
                                        </td>
                                        <td>{{ $a->keterangan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-body-secondary">Tidak ada ketidakhadiran per jam.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if ($dataKetidakhadiranPerjam) {{ $dataKetidakhadiranPerjam->links() }} @endif
                    </div>

                @elseif ($tab === 'catatan')
                    @if ($dataCatatan === null)
                        <div class="alert alert-info">Belum ada catatan dari wali kelas untuk semester ini.</div>
                    @else
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 fw-semibold">Catatan Wali Kelas</div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Guru</dt>
                                    <dd class="col-sm-9">{{ $dataCatatan->guru->nama ?? '-' }}</dd>
                                    <dt class="col-sm-3">Catatan</dt>
                                    <dd class="col-sm-9">{{ $dataCatatan->catatan }}</dd>
                                </dl>
                            </div>
                        </div>
                    @endif

                @elseif ($tab === 'perkembangan')
                    @if (($dataPerkembangan['jumlah_mapel'] ?? 0) === 0)
                        <div class="alert alert-info">Belum ada data nilai yang tersedia untuk semester ini.</div>
                    @else
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-4"><div class="card border-0 shadow-sm text-center py-3"><div class="small text-body-secondary">Jumlah Mapel</div><div class="h4 fw-bold mb-0">{{ $dataPerkembangan['jumlah_mapel'] }}</div></div></div>
                            <div class="col-6 col-md-4"><div class="card border-0 shadow-sm text-center py-3"><div class="small text-body-secondary">Rata-rata Nilai Akhir</div><div class="h4 fw-bold mb-0">{{ number_format((float) ($dataPerkembangan['rata_rata'] ?? 0), 2, ',', '.') }}</div></div></div>
                            <div class="col-6 col-md-4"><div class="card border-0 shadow-sm text-center py-3"><div class="small text-body-secondary">Di Bawah KKTP</div><div class="h4 fw-bold mb-0 {{ ($dataPerkembangan['jumlah_di_bawah_kktp'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ $dataPerkembangan['jumlah_di_bawah_kktp'] }}</div></div></div>
                        </div>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 fw-semibold">Nilai Akhir per Mapel</div>
                            <div class="card-body p-0">
                                <table class="table sa-table table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Mapel</th><th class="text-center">Nilai Akhir</th><th class="text-center">KKTP</th><th class="text-center">Status</th></tr></thead>
                                    <tbody>
                                        @foreach ($dataPerkembangan['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['mapel'] }}</td>
                                                <td class="text-center">{{ number_format((float) $row['nilai'], 2, ',', '.') }}</td>
                                                <td class="text-center">{{ $row['kktp'] !== null ? number_format((float) $row['kktp'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-center">@if ($row['di_bawah']) <span class="badge bg-danger">Di Bawah</span> @else <span class="badge bg-success">Tercapai</span> @endif</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="text-body-secondary small mt-3 mb-0">Ringkasan menggunakan snapshot nilai_akhir pada semester/tahun ajaran aktif. KKTP dari snapshot nilai_akhir; bila kosong, gunakan KKTP mapel dari sumber lain.</p>
                    @endif

                @elseif ($tab === 'absensi_harian')
                    <div class="table-responsive">
                        <table class="table sa-table table-bordered">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($dataAbsensiHarian as $a)
                                    <tr>
                                        <td>{{ $a->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="text-center">
                                            @php($st = strtolower((string) ($a->status ?? '')))
                                            @if ($st === 'hadir') <span class="sa-badge sa-hadir">Hadir</span>
                                            @elseif ($st === 'sakit') <span class="sa-badge sa-sakit">Sakit</span>
                                            @elseif ($st === 'izin') <span class="sa-badge sa-izin">Izin</span>
                                            @elseif ($st === 'alpha') <span class="sa-badge sa-alpha">Alpha</span>
                                            @else <span class="sa-badge sa-netral">{{ $a->status ?? '-' }}</span> @endif
                                        </td>
                                        <td>{{ $a->catatan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-body-secondary">Tidak ada data absensi harian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                @elseif ($tab === 'absensi_perjam')
                    <div class="table-responsive">
                        <table class="table sa-table table-bordered">
                            <thead class="table-light"><tr><th>Pertemuan</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($dataAbsensiPerjam as $a)
                                    <tr>
                                        <td>{{ $a->jadwal_pertemuan_idjadwal_pertemuan ?? '-' }}</td>
                                        <td class="text-center">
                                            @php($st = strtolower((string) ($a->status ?? '')))
                                            @if ($st === 'hadir') <span class="sa-badge sa-hadir">Hadir</span>
                                            @elseif ($st === 'sakit') <span class="sa-badge sa-sakit">Sakit</span>
                                            @elseif ($st === 'izin') <span class="sa-badge sa-izin">Izin</span>
                                            @elseif ($st === 'alpha') <span class="sa-badge sa-alpha">Alpha</span>
                                            @else <span class="sa-badge sa-netral">{{ $a->status ?? '-' }}</span> @endif
                                        </td>
                                        <td>{{ $a->keterangan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-body-secondary">Tidak ada data absensi per jam.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                @elseif ($tab === 'jadwal')
                    @forelse ($dataJadwal as $hari => $items)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white py-2 fw-semibold">Hari {{ $hari }}</div>
                            <div class="card-body p-0">
                                <table class="table sa-table table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Jam</th><th>Mapel</th><th>Guru</th></tr></thead>
                                    <tbody>
                                        @foreach ($items as $j)
                                            <tr>
                                                <td>{{ $j->jamhari->mulai ?? '-' }} - {{ $j->jamhari->selesai ?? '-' }}</td>
                                                <td>{{ $j->mapel->nama ?? '-' }}</td>
                                                <td>{{ $j->guru->nama ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">
                            @if (! $konteks->valid())
                                Tahun ajaran atau semester aktif belum valid, sehingga jadwal tidak dapat ditampilkan.
                            @else
                                Belum ada jadwal yang dipenuhi untuk siswa ini pada semester aktif. Pastikan jadwal pelajaran sudah diisi sesuai tahun ajaran dan semester aktif.
                            @endif
                        </div>
                    @endforelse
                @endif
            @endif
        @endif
    </div>
</div>
