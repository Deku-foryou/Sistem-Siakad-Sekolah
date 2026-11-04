@section('title', 'Jadwal Kelas')

<div class="card card-success">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="card-title mb-0">
                @if ($modeLihat && $kelasTerpilih)
                    Jadwal Kelas {{ $kelasTerpilih->nama }}
                @else
                    Jadwal Kelas
                @endif
            </h3>
            @if ($modeLihat)
                <div class="small text-body-secondary mt-1">
                    @if ($semesterTerpilih)
                        Tahun Ajaran {{ $semesterTerpilih->tahunajaran->nama ?? '-' }} &middot;
                        Semester {{ $semesterTerpilih->nama }}
                        @unless ($semesterTerpilih->aktif)
                            &middot; <span class="badge bg-secondary">Bukan semester aktif</span>
                        @endunless
                    @else
                        Semester belum tersedia
                    @endif
                </div>
            @endif
        </div>
        @if ($modeLihat)
            <a href="{{ route('kelas') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Kembali ke Kelas
            </a>
        @endif
    </div>
    <div class="card-body">
        @unless ($modeLihat)
            <p>Atur mapel dan guru pada jam pelajaran tiap kelas.</p>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Semester</label>
                    <select wire:model.live="semester" class="form-control">
                        @foreach($semesters as $s) <option value="{{$s->idsemester}}">{{$s->nama}}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Jurusan</label>
                    <select wire:model.live="filterJurusan" class="form-control">
                        <option value="">Pilih</option>
                        @foreach($jurusans as $j) <option value="{{$j->idjurusan}}">{{$j->nama}}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Kelas</label>
                    <select wire:model.live="filterKelas" class="form-control">
                        <option value="">Pilih</option>
                        @foreach($kelases as $k) <option value="{{$k->idkelas}}">{{$k->nama}}</option> @endforeach
                    </select>
                </div>
            </div>
        @endunless

        @if ($filterKelas && ! $kelasTerpilih)
            <div class="alert alert-warning mb-0">
                Kelas tidak ditemukan atau tidak cocok dengan tahun ajaran semester ini.
                @if ($modeLihat)
                    <a href="{{ route('kelas') }}">Kembali ke halaman Kelas</a>.
                @endif
            </div>
        @elseif ($kelasTerpilih && ! $semesterTerpilih)
            <div class="alert alert-warning mb-0">
                Belum ada semester yang dapat dipakai untuk menampilkan jadwal kelas ini.
                @if ($modeLihat)
                    Lengkapi data semester terlebih dahulu.
                @endif
            </div>
        @elseif ($kelasTerpilih)
            @if ($jumlahBarisJam === 0)
                <div class="alert alert-warning mb-0">
                    Kelas {{ $kelasTerpilih->nama }} belum punya jam pelajaran. Atur di menu
                    <a href="{{ route('jampelajaran') }}">Jam Pelajaran</a>.
                </div>
            @elseif ($modeLihat && $jadwalData->isEmpty())
                <div class="detail-card p-4 text-center">
                    <i class="fa-regular fa-calendar-xmark fs-2 text-secondary mb-2" aria-hidden="true"></i>
                    <p class="fw-semibold mb-1">Belum ada jadwal</p>
                    <p class="text-body-secondary mb-0">
                        Kelas {{ $kelasTerpilih->nama }} belum memiliki jadwal pada Semester
                        {{ $semesterTerpilih->nama }} (Tahun Ajaran
                        {{ $semesterTerpilih->tahunajaran->nama ?? '-' }}).
                        Jadwal dapat diatur melalui menu
                        <a href="{{ route('jadwalkelas') }}">Jadwal Kelas</a>.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Jam Ke</th>
                                @foreach($hari as $hariId => $namaHari)
                                    <th>{{ $namaHari }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for($urutan = 0; $urutan < $jumlahBarisJam; $urutan++)
                                <tr>
                                    <td>Jam ke-{{ $urutan + 1 }}</td>
                                    @foreach($hari as $hariId => $namaHari)
                                        @php
                                            $j = $jamPerHari->get($hariId, collect())->get($urutan);
                                        @endphp
                                        <td>
                                        @if($j)
                                            @php
                                                $jdw = $jadwalData->where('jamhari_idjamhari', $j->idjamhari)->first();
                                            @endphp
                                            @if($j->jenis == 'istirahat')
                                                <div class="bg-secondary p-2 text-center text-white">
                                                    Istirahat<br>
                                                    <small>{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                </div>
                                            @elseif($jdw)
                                                @if($modeLihat)
                                                    <div class="border rounded p-2 text-center">
                                                        <strong>{{$jdw->mapel_nama}}</strong><br>
                                                        <small>{{$jdw->guru_nama}} ({{$jdw->kode_guru}})</small><br>
                                                        <small>{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                    </div>
                                                @else
                                                    <button wire:click="edit({{$jdw->idjadwalkelas}}, {{$j->idjamhari}})" class="btn btn-sm btn-primary btn-block">
                                                        <strong>{{$jdw->mapel_nama}}</strong><br>
                                                        <small>{{$jdw->guru_nama}} ({{$jdw->kode_guru}})</small><br>
                                                        <small>{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small>
                                                    </button>
                                                @endif
                                            @elseif($modeLihat)
                                                <div class="text-center text-body-secondary">-</div>
                                            @else
                                                <button wire:click="edit(0, {{$j->idjamhari}})" class="btn btn-sm btn-outline-success btn-block">
                                                    {{ $j->jenis === 'pelajaran' ? 'Jam ke-'.$j->jam_ke : ucfirst($j->jenis) }}<br>
                                                    <small>{{ substr($j->mulai, 0, 5) }}-{{ substr($j->selesai, 0, 5) }}</small><br>
                                                    <strong>+</strong>
                                                </button>
                                            @endif
                                        @else
                                            -
                                        @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>

    @unless ($modeLihat)
        {{-- Modal --}}
        <div wire:ignore.self class="modal fade" id="jadwalkelasModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h4 class="modal-title">Atur Jadwal</h4></div>
                    <div class="modal-body">
                        <label for="jadwalkelas-mapel">Mata Pelajaran</label>
                        <select id="jadwalkelas-mapel" wire:model.live="mapel_id" class="form-control">
                            <option value="">Pilih Mata Pelajaran</option>
                            @foreach($mapels as $mapel)
                                <option value="{{ $mapel->idmapel }}">{{ $mapel->kode }} - {{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        @error('mapel_id') <span class="text-danger">{{ $message }}</span> @enderror

                        <label for="jadwalkelas-guru" class="mt-2">Guru Pengampu</label>
                        <select id="jadwalkelas-guru" wire:model="guru_id" class="form-control">
                            <option value="">Pilih Guru</option>
                            @foreach($gurus as $guru)
                                <option value="{{ $guru->idguru }}">
                                    {{ $guru->nama }}{{ $guru->kode_guru ? ' ('.$guru->kode_guru.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('guru_id') <span class="text-danger">{{ $message }}</span> @enderror

                        @if($mapels->isEmpty())
                            <div class="alert alert-warning mt-3 mb-0">Belum ada data mata pelajaran.</div>
                        @elseif($mapel_id && $gurus->isEmpty())
                            <div class="alert alert-warning mt-3 mb-0">
                                Belum ada guru aktif yang terhubung ke mata pelajaran ini. Atur mata pelajaran guru di data Guru terlebih dahulu.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
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
