@section('title', 'Jadwal Kelas')

<div class="container-fluid mb-5 px-3 px-md-4" style="margin-top: 20px">

    {{-- Header halaman --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="fw-bold mb-1">
                @if ($modeLihat && $kelasTerpilih)
                    Jadwal Kelas {{ $kelasTerpilih->nama }}
                @else
                    Jadwal Kelas
                @endif
            </h3>
            <p class="text-body-secondary mb-0">
                @if ($modeLihat)
                    @if ($semesterTerpilih)
                        Tahun Ajaran {{ $semesterTerpilih->tahunajaran->nama ?? '-' }} &middot;
                        Semester {{ $semesterTerpilih->nama }}
                        @unless ($semesterTerpilih->aktif)
                            &middot; <span class="badge bg-secondary">Bukan semester aktif</span>
                        @endunless
                    @else
                        Semester belum tersedia
                    @endif
                @else
                    Atur mapel dan guru pada jam pelajaran tiap kelas.
                @endif
            </p>
        </div>
        @if ($modeLihat)
            <a href="{{ route('kelas') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Kembali ke Kelas
            </a>
        @endif
    </div>

    @unless ($modeLihat)
        {{-- Filter --}}
        <div class="card border mb-4">
            <div class="card-body">
                <p class="text-body-secondary small mb-3">Pilih semester, jurusan, lalu kelas. Setelah kelas dipilih, grid jadwal akan ditampilkan di bawah.</p>
                <div class="row g-3 align-items-end">
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
                            <div class="border rounded px-3 py-2 bg-light">
                                <div class="small text-body-secondary">Kelas terpilih</div>
                                <strong>{{ $kelasTerpilih->nama }}</strong>
                                <div class="small text-body-secondary">{{ $semesterTerpilih->nama }} &middot; Tahun Ajaran {{ $semesterTerpilih->tahunajaran->nama ?? '-' }}</div>
                            </div>
                        @else
                            <div class="text-body-secondary small pb-2">Belum ada kelas dipilih.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endunless

    @if ($filterKelas && ! $kelasTerpilih)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
            Kelas tidak ditemukan atau tidak cocok dengan tahun ajaran semester ini.
            @if ($modeLihat)
                <a href="{{ route('kelas') }}">Kembali ke halaman Kelas</a>.
            @endif
        </div>
    @elseif ($kelasTerpilih && ! $semesterTerpilih)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
            Belum ada semester yang dapat dipakai untuk menampilkan jadwal kelas ini.
            @if ($modeLihat)
                Lengkapi data semester terlebih dahulu.
            @endif
        </div>
    @elseif (! $kelasTerpilih)
        {{-- Empty state sebelum kelas dipilih (mode normal) --}}
        <div class="card border">
            <div class="card-body text-center py-5">
                <i class="fa-regular fa-calendar-days fs-1 text-body-secondary mb-3 d-block" aria-hidden="true"></i>
                <h5 class="fw-semibold">Belum ada kelas dipilih</h5>
                <p class="text-body-secondary mb-0">Pilih semester, jurusan, dan kelas di atas untuk menampilkan grid jadwal.</p>
            </div>
        </div>
    @elseif ($kelasTerpilih)
        @if ($jumlahBarisJam === 0)
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>
                Kelas {{ $kelasTerpilih->nama }} belum punya jam pelajaran. Atur di menu
                <a href="{{ route('jamhari') }}">Jam Pelajaran</a>.
            </div>
        @elseif ($modeLihat && $jadwalData->isEmpty())
            <div class="card border">
                <div class="card-body text-center py-5">
                    <i class="fa-regular fa-calendar-xmark fs-1 text-body-secondary mb-3 d-block" aria-hidden="true"></i>
                    <h5 class="fw-semibold">Belum ada jadwal</h5>
                    <p class="text-body-secondary mb-0">
                        Kelas {{ $kelasTerpilih->nama }} belum memiliki jadwal pada Semester
                        {{ $semesterTerpilih->nama }} (Tahun Ajaran
                        {{ $semesterTerpilih->tahunajaran->nama ?? '-' }}).
                        Jadwal dapat diatur melalui menu
                        <a href="{{ route('jadwalkelas') }}">Jadwal Kelas</a>.
                    </p>
                </div>
            </div>
        @else
            {{-- Grid jadwal --}}
            <div class="card border">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                    <h6 class="mb-0 fw-semibold">Grid Jadwal &middot; Kelas {{ $kelasTerpilih->nama }}</h6>
                    <span class="badge bg-light text-dark border">{{ $jumlahBarisJam }} baris jam</span>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead class="table-light">
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
                                            <td class="p-1" style="min-width: 150px;">
                                            @if($j)
                                                @php
                                                    $jdw = $jadwalData->where('jamhari_idjamhari', $j->idjamhari)->first();
                                                @endphp
                                                @if($j->jenis == 'istirahat')
                                                    <div class="bg-light border rounded p-2">
                                                        <small class="fw-semibold text-body-secondary d-block">Istirahat</small>
                                                        <small class="text-body-secondary">{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                    </div>
                                                @elseif($jdw)
                                                    @if($modeLihat)
                                                        <div class="border rounded p-2 h-100">
                                                            <strong class="d-block text-truncate" title="{{ $jdw->mapel_nama }}">{{$jdw->mapel_nama}}</strong>
                                                            <small class="d-block text-body-secondary">{{$jdw->guru_nama}} ({{$jdw->kode_guru}})</small>
                                                            <small class="text-body-secondary">{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                        </div>
                                                    @else
                                                        <button wire:click="edit({{$jdw->idjadwalkelas}}, {{$j->idjamhari}})" class="btn btn-sm btn-primary w-100">
                                                            <strong class="d-block">{{$jdw->mapel_nama}}</strong>
                                                            <small class="d-block fw-normal">{{$jdw->guru_nama}} ({{$jdw->kode_guru}})</small>
                                                            <small class="fw-normal">{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                        </button>
                                                    @endif
                                                @elseif($modeLihat)
                                                    <div class="text-center text-body-secondary py-2">-</div>
                                                @else
                                                    <button wire:click="edit(0, {{$j->idjamhari}})" class="btn btn-sm btn-outline-success w-100">
                                                        {{ $j->jenis === 'pelajaran' ? 'Jam ke-'.$j->jam_ke : ucfirst($j->jenis) }}<br>
                                                        <small>{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small><br>
                                                        <strong>+</strong>
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-body-secondary">-</span>
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h4 class="modal-title" id="jadwalkelasModalLabel">Atur Jadwal</h4></div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="jadwalkelas-mapel" class="form-label">Mata Pelajaran</label>
                            <select id="jadwalkelas-mapel" wire:model.live="mapel_id" class="form-select">
                                <option value="">Pilih Mata Pelajaran</option>
                                @foreach($mapels as $mapel)
                                    <option value="{{ $mapel->idmapel }}">{{ $mapel->kode }} - {{ $mapel->nama }}</option>
                                @endforeach
                            </select>
                            @error('mapel_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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
                            @error('guru_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button wire:click="store" class="btn btn-success">Simpan</button>
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
