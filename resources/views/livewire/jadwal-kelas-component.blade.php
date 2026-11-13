@section('title', 'Jadwal Kelas')

<div class="container-fluid px-3 px-lg-4 py-3 mb-5">
    @include('assets.akademik')


    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-table-cells me-2 text-success" aria-hidden="true"></i>
                @if ($modeLihat && $kelasTerpilih)
                    Jadwal Kelas {{ $kelasTerpilih->nama }}
                @else
                    Jadwal Kelas
                @endif
            </h1>
            @if ($modeLihat)
                @if ($semesterTerpilih)
                    <p class="sa-sub">
                        Tahun Ajaran {{ $semesterTerpilih->tahunajaran->nama ?? '-' }} &middot;
                        Semester {{ $semesterTerpilih->nama }}
                        @unless ($semesterTerpilih->aktif)
                            <span class="sa-badge sa-netral ms-1">Bukan semester aktif</span>
                        @endunless
                    </p>
                @else
                    <p class="sa-sub">Semester belum tersedia</p>
                @endif
            @else
                <p class="sa-sub">Atur mata pelajaran dan guru pada jam pelajaran tiap kelas.</p>
            @endif
        </div>
        @if ($modeLihat)
            <a href="{{ route('kelas') }}" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Kembali">
                <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Kembali ke Kelas
            </a>
        @endif
    </div>

    @unless ($modeLihat)
        {{-- Filter --}}
        <div class="sa-card sa-filter mb-3">
            <p class="sa-hint mb-2"><i class="fa-solid fa-filter me-1" aria-hidden="true"></i>Pilih semester, jurusan, lalu kelas. Setelah kelas dipilih, grid jadwal akan ditampilkan di bawah.</p>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="jadwalkelas-semester" class="form-label">Semester</label>
                    <select id="jadwalkelas-semester" wire:model.live="semester" class="form-select">
                        @foreach($semesters as $s) <option value="{{$s->idsemester}}">{{$s->nama}}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="jadwalkelas-jurusan" class="form-label">Jurusan</label>
                    <select id="jadwalkelas-jurusan" wire:model.live="filterJurusan" class="form-select">
                        <option value="">Pilih</option>
                        @foreach($jurusans as $j) <option value="{{$j->idjurusan}}">{{$j->nama}}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="jadwalkelas-kelas" class="form-label">Kelas</label>
                    <select id="jadwalkelas-kelas" wire:model.live="filterKelas" class="form-select">
                        <option value="">Pilih</option>
                        @foreach($kelases as $k) <option value="{{$k->idkelas}}">{{$k->nama}}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    @if($kelasTerpilih && $semesterTerpilih)
                        <div class="border rounded px-3 py-2" style="background: var(--sa-green-faint); border-color: var(--sa-border) !important;">
                            <div class="sa-hint">Kelas terpilih</div>
                            <strong>{{ $kelasTerpilih->nama }}</strong>
                            <div class="sa-hint">{{ $semesterTerpilih->nama }} &middot; TA {{ $semesterTerpilih->tahunajaran->nama ?? '-' }}</div>
                        </div>
                    @else
                        <div class="sa-hint pb-2">Belum ada kelas dipilih.</div>
                    @endif
                </div>
            </div>
        </div>
    @endunless

    @if ($filterKelas && ! $kelasTerpilih)
        <div class="alert alert-warning" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
            Kelas tidak ditemukan atau tidak cocok dengan tahun ajaran semester ini.
            @if ($modeLihat)
                <a href="{{ route('kelas') }}">Kembali ke halaman Kelas</a>.
            @endif
        </div>
    @elseif ($kelasTerpilih && ! $semesterTerpilih)
        <div class="alert alert-warning" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
            Belum ada semester yang dapat dipakai untuk menampilkan jadwal kelas ini.
            @if ($modeLihat)
                Lengkapi data semester terlebih dahulu.
            @endif
        </div>
    @elseif (! $kelasTerpilih)
        {{-- Empty state sebelum kelas dipilih --}}
        <div class="sa-card">
            <div class="sa-card-body sa-empty">
                <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                <p class="sa-empty-title">Belum ada kelas dipilih</p>
                <p class="sa-empty-text">Pilih semester, jurusan, dan kelas di atas untuk menampilkan grid jadwal.</p>
            </div>
        </div>
    @elseif ($kelasTerpilih)
        @if ($jumlahBarisJam === 0)
            <div class="alert alert-info" role="alert">
                <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>
                Kelas {{ $kelasTerpilih->nama }} belum punya jam pelajaran. Atur di menu <a href="{{ route('jamhari') }}">Jam Pelajaran</a>.
            </div>
        @elseif ($modeLihat && $jadwalData->isEmpty())
            <div class="sa-card">
                <div class="sa-card-body sa-empty">
                    <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                    <p class="sa-empty-title">Belum ada jadwal</p>
                    <p class="sa-empty-text">Kelas {{ $kelasTerpilih->nama }} belum memiliki jadwal pada Semester {{ $semesterTerpilih->nama }} (TA {{ $semesterTerpilih->tahunajaran->nama ?? '-' }}).</p>
                </div>
            </div>
        @else
            {{-- Grid jadwal --}}
            <div class="sa-card">
                <div class="sa-card-head">
                    <h2 class="sa-card-title"><i class="fa-solid fa-table-cells" aria-hidden="true"></i>Grid Jadwal &middot; {{ $kelasTerpilih->nama }}</h2>
                    <span class="sa-count">{{ $jumlahBarisJam }} baris jam</span>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                        <table class="table sa-table table-bordered align-middle text-center mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 96px;">Jam Ke</th>
                                    @foreach($hari as $hariId => $namaHari)
                                        <th class="text-nowrap">{{ $namaHari }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @for($urutan = 0; $urutan < $jumlahBarisJam; $urutan++)
                                    <tr>
                                        <th class="text-body-secondary fw-semibold text-nowrap">Jam ke-{{ $urutan + 1 }}</th>
                                        @foreach($hari as $hariId => $namaHari)
                                            @php
                                                $j = $jamPerHari->get($hariId, collect())->get($urutan);
                                            @endphp
                                            <td class="p-1" style="min-width: 148px;">
                                            @if($j)
                                                @php
                                                    $jdw = $jadwalData->where('jamhari_idjamhari', $j->idjamhari)->first();
                                                @endphp
                                                @if($j->jenis == 'istirahat')
                                                    <div class="sa-badge sa-netral w-100 justify-content-center py-2" style="border-radius:.55rem;">
                                                        <span><small class="fw-semibold d-block">Istirahat</small><small class="sa-cell-muted">{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small></span>
                                                    </div>
                                                @elseif($jdw)
                                                    @if($modeLihat)
                                                        <div class="border rounded p-2 h-100 text-start" style="border-color: var(--sa-border) !important;">
                                                            <strong class="d-block text-truncate" title="{{ $jdw->mapel_nama }}">{{ $jdw->mapel_nama }}</strong>
                                                            <small class="d-block text-body-secondary">{{ $jdw->guru_nama }} ({{ $jdw->kode_guru }})</small>
                                                            <small class="sa-cell-muted">{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                        </div>
                                                    @else
                                                        <button wire:click="edit({{$jdw->idjadwalkelas}}, {{$j->idjamhari}})" class="btn btn-sm btn-primary w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Ubah data" aria-label="Ubah data">
                                                            <strong class="d-block">{{ $jdw->mapel_nama }}</strong>
                                                            <small class="d-block fw-normal">{{ $jdw->guru_nama }} ({{ $jdw->kode_guru }})</small>
                                                            <small class="fw-normal">{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                        </button>
                                                    @endif
                                                @elseif($modeLihat)
                                                    <div class="sa-cell-muted py-2">-</div>
                                                @else
                                                    <button wire:click="edit(0, {{$j->idjamhari}})" class="btn btn-sm btn-outline-success w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah data" aria-label="Tambah data">
                                                        {{ $j->jenis === 'pelajaran' ? 'Jam ke-'.$j->jam_ke : ucfirst($j->jenis) }}<br>
                                                        <small>{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small><br>
                                                        <strong><i class="fa-solid fa-plus" aria-hidden="true"></i></strong>
                                                    </button>
                                                @endif
                                            @else
                                                <span class="sa-cell-muted">-</span>
                                            @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @unless ($modeLihat)
        {{-- Modal --}}
        <div wire:ignore.self class="modal fade" id="jadwalkelasModal" tabindex="-1" aria-labelledby="jadwalkelasModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h4 class="modal-title" id="jadwalkelasModalLabel"><i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Atur Jadwal</h4></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="jadwalkelas-mapel" class="form-label">Mata Pelajaran</label>
                            <select id="jadwalkelas-mapel" wire:model.live="mapel_id" class="form-select">
                                <option value="">Pilih Mata Pelajaran</option>
                                @foreach($mapels as $mapel)
                                    <option value="{{ $mapel->idmapel }}">{{ $mapel->kode }} - {{ $mapel->nama }}</option>
                                @endforeach
                            </select>
                            @error('mapel_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-2">
                            <label for="jadwalkelas-guru" class="form-label">Guru Pengampu</label>
                            <select id="jadwalkelas-guru" wire:model="guru_id" class="form-select">
                                <option value="">Pilih Guru</option>
                                @foreach($gurus as $guru)
                                    <option value="{{ $guru->idguru }}">
                                        {{ $guru->nama }}{{ $guru->kode_guru ? ' ('.$guru->kode_guru.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('guru_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>

                        @if($mapels->isEmpty())
                            <div class="alert alert-warning mb-0 py-2">Belum ada data mata pelajaran.</div>
                        @elseif($mapel_id && $gurus->isEmpty())
                            <div class="alert alert-warning mb-0 py-2">
                                Belum ada guru aktif yang terhubung ke mata pelajaran ini. Atur mata pelajaran guru di data Guru terlebih dahulu.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" data-bs-placement="top" title="Tutup">Batal</button>
                        <button wire:click="store" class="btn btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Simpan</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            window.addEventListener('show-jadwalkelas-modal', () => $('#jadwalkelasModal').modal('show'));
            window.addEventListener('close-jadwalkelas-modal', () => $('#jadwalkelasModal').modal('hide'));
        </script>
    @endunless
</div>
