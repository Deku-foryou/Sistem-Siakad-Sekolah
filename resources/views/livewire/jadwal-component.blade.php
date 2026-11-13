@section('title', 'Jadwal (Lama)')

<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')

    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-table-cells-large me-2 text-success" aria-hidden="true"></i>Jadwal Pelajaran <span class="sa-badge sa-netral align-middle">Sistem Lama</span></h1>
            <p class="sa-sub">Pilih sel untuk menambah atau mengganti mapel dan guru.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" wire:click="publikasikan" data-bs-toggle="tooltip" data-bs-placement="top" title="Publikasikan"><i class="fa-solid fa-cloud-arrow-up me-1" aria-hidden="true"></i>Publikasikan</button>
            <button class="btn btn-outline-warning" wire:click="tarikPublikasi" data-bs-toggle="tooltip" data-bs-placement="top" title="Publikasikan"><i class="fa-solid fa-cloud-arrow-down me-1" aria-hidden="true"></i>Tarik Publikasi</button>
        </div>
    </div>

    @if ($publikasiAktif)
        <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
            <span><i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i><strong>DIPUBLIKASIKAN</strong> &middot; Nomor: {{ $publikasiAktif->nomor }} &middot; Tanggal: {{ $publikasiAktif->published_at }}</span>
            <a class="btn btn-sm btn-primary" href="{{ url('/bukti-publikasi/'.$publikasiAktif->idpublikasijadwal) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail">Lihat Bukti</a>
        </div>
    @else
        <div class="alert alert-warning" role="status"><i class="fa-solid fa-file-pen me-2" aria-hidden="true"></i><strong>DRAFT</strong> &mdash; jadwal belum dipublikasikan.</div>
        @if ($publikasiTerakhir && $publikasiTerakhir->status === 'withdrawn')
            <div class="alert alert-secondary d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
                <span><i class="fa-solid fa-clock-rotate-left me-2" aria-hidden="true"></i>Publikasi sebelumnya ({{ $publikasiTerakhir->nomor }}) telah ditarik pada {{ $publikasiTerakhir->withdrawn_at }}.</span>
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/bukti-publikasi/'.$publikasiTerakhir->idpublikasijadwal) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail">Lihat Bukti</a>
            </div>
        @endif
    @endif

    {{-- Filter --}}
    <div class="sa-card sa-filter mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <label for="jadwal-semester" class="form-label">Semester</label>
                <select id="jadwal-semester" class="form-select" wire:model.live="filterSemester">
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} &middot; {{ $semester->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="jadwal-kelas" class="form-label">Kelas</label>
                <select id="jadwal-kelas" class="form-select" wire:model.live="filterKelas">
                    <option value="">Pilih kelas</option>
                    @foreach ($kelasOptions as $kelas)
                        <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if (! $filterSemester)
        <div class="alert alert-info"><i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>Belum ada semester. Buat tahun ajaran dan semester terlebih dahulu.</div>
    @elseif ($periods === [])
        <div class="alert alert-info"><i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>Belum ada pola jam atau jam pelajaran yang dapat ditampilkan. Atur dulu jam pelajaran per hari di menu <a href="{{ url('/jamhari') }}">Jam Pelajaran</a>.</div>
    @elseif (! $filterKelas)
        <div class="alert alert-info"><i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>Belum ada kelas pada tahun ajaran semester ini.</div>
    @else
        <div class="sa-card">
            <div class="sa-card-head">
                <h2 class="sa-card-title"><i class="fa-solid fa-table-cells" aria-hidden="true"></i>Grid Jadwal</h2>
                <span class="sa-count">{{ count($periods) }} baris jam</span>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="table-responsive">
                    <table class="table sa-table table-bordered align-middle text-center mb-0">
                        <thead><tr><th>Jam</th>
                            @foreach ($gridHariOptions as $day => $namaHari)
                                <th>{{ $namaHari }}
                                    @foreach ($istirahats as $break)
                                        <div><small class="fw-normal">Istirahat {{ substr($break->mulai, 0, 5) }}&ndash;{{ substr($break->selesai, 0, 5) }}</small></div>
                                    @endforeach
                                </th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                        @foreach ($periods as $period)
                            <tr><th class="text-nowrap">Ke-{{ $period['jam_ke'] }}</th>
                                @foreach ($gridHariOptions as $day => $namaHari)
                                    @php($cell = $jadwals->get($day.'|'.$period['jam_ke']))
                                    <td class="p-1">
                                        <button class="btn btn-sm w-100 {{ $cell ? 'btn-success' : 'btn-outline-secondary' }}"
                                            wire:click="bukaSlot({{ $day }}, {{ $period['jam_ke'] }})"
                                            aria-label="{{ $cell ? 'Ubah' : 'Tambah' }} jadwal hari {{ $namaHari }} jam ke-{{ $period['jam_ke'] }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Buka slot jadwal">
                                            <small>{{ $times[$day.'|'.$period['jam_ke']] ?? '' }}</small><br>
                                            @if ($cell)
                                                <strong>{{ $cell->pengampu->mapel->nama }}</strong><br>
                                                <small>{{ $cell->pengampu->guru->nama }}</small>
                                            @else
                                                <span><i class="fa-solid fa-plus" aria-hidden="true"></i></span>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    <div wire:ignore.self class="modal fade" id="jadwalModal" tabindex="-1" aria-labelledby="jadwalModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="jadwalModalLabel"><i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>{{ $idjadwal ? 'Ubah Jadwal' : 'Tambah Jadwal' }}</h5></div>
        <div class="modal-body">
            <p class="mb-2"><strong>{{ $hariOptions[$hari] ?? '-' }} &middot; Jam ke-{{ $jam_ke }}</strong></p>
            @if ($mapels->isEmpty())
                <div class="alert alert-info">Belum ada mapel. <a href="{{ url('/mapel') }}">Kelola Mapel</a>.</div>
            @else
                <label for="jadwal-mapel" class="form-label">Mapel</label>
                <select id="jadwal-mapel" class="form-select mb-3" wire:model.live="mapelId">
                    <option value="">Pilih mapel</option>
                    @foreach ($mapels as $mapel)
                        <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                    @endforeach
                </select>
            @endif
            @if ($activeGurus->isEmpty())
                <div class="alert alert-info">Belum ada guru aktif. <a href="{{ url('/guru') }}">Kelola Guru</a>.</div>
            @else
                <label for="jadwal-guru-select" class="form-label">Guru</label>
                <select id="jadwal-guru-select" class="form-select" wire:model="guruId" @disabled($selectedPengampu !== null)>
                    <option value="">Pilih guru</option>
                    @if ($selectedPengampu && ! $activeGurus->contains('idguru', $selectedPengampu->guru_idguru))
                        <option value="{{ $selectedPengampu->guru_idguru }}">{{ $selectedPengampu->guru->nama }}</option>
                    @endif
                    @foreach ($activeGurus as $guru)
                        <option value="{{ $guru->idguru }}">{{ $guru->nama }}</option>
                    @endforeach
                </select>
                @if ($selectedPengampu)
                    <small class="sa-hint">Pengampu terdaftar</small>
                @endif
            @endif
        </div>
        <div class="modal-footer">
            @if ($idjadwal)<button class="btn btn-danger me-auto" wire:click="hapus" wire:confirm="Hapus jadwal ini?" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus data (ada konfirmasi)">Hapus Jadwal</button>@endif
            <button class="btn btn-primary" wire:click="simpan" @disabled($mapels- data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">isEmpty() || $activeGurus->isEmpty() || ! $mapelId || ! $guruId)><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Tutup</button>
        </div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-jadwal-modal', () => $('#jadwalModal').modal('hide'));
    window.addEventListener('show-jadwal-modal', () => $('#jadwalModal').modal('show'));
</script>
