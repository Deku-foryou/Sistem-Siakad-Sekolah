@section('title', 'Jadwal')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="fw-bold mb-1">Jadwal Pelajaran</h3><span class="text-muted">Pilih sel untuk menambah atau mengganti pengampu.</span></div>
    </div>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body row g-2">
        <div class="col-md-6"><label class="form-label">Semester</label><select class="form-select" wire:model.live="filterSemester">
            <option value="">Pilih semester</option>@foreach ($semesters as $semester)
                <option value="{{ $semester->idsemester }}">{{ $semester->tahunajaran->nama ?? '-' }} · {{ $semester->nama }}</option>@endforeach
        </select></div>
        <div class="col-md-6"><label class="form-label">Kelas</label><select class="form-select" wire:model.live="filterKelas">
            <option value="">Pilih kelas</option>@foreach ($kelasOptions as $kelas)
                <option value="{{ $kelas->idkelas }}">{{ $kelas->nama }} ({{ $kelas->tingkat }})</option>@endforeach
        </select></div>
    </div></div>
    @if (! $filterSemester)
        <div class="alert alert-info">Belum ada semester. Buat tahun ajaran dan semester terlebih dahulu.</div>
    @elseif ($periods === [] || $mappings->isEmpty())
        <div class="alert alert-info">Belum ada pola jam atau jam pelajaran yang dapat ditampilkan. <a href="{{ url('/polajam') }}">Atur pola jam</a> dan <a href="{{ url('/jampelajaran') }}">jam pelajaran</a>.</div>
    @elseif (! $filterKelas)
        <div class="alert alert-info">Belum ada kelas pada tahun ajaran semester ini.</div>
    @else
        <div class="table-responsive"><table class="table table-bordered align-middle text-center">
            <thead class="table-success"><tr><th>Jam</th>
                @foreach ($hariOptions as $day => $namaHari)
                    <th>{{ $namaHari }}
                        @php($mapping = $mappings->get($day))
                        @if ($mapping)
                            @foreach ($mapping->pola->jamPelajaran->where('jenis', 'istirahat')->sortBy('mulai') as $break)
                                <div><small class="fw-normal">Istirahat {{ substr($break->mulai, 0, 5) }}–{{ substr($break->selesai, 0, 5) }}</small></div>
                            @endforeach
                        @endif
                    </th>
                @endforeach
            </tr></thead>
            <tbody>
            @foreach ($periods as $period)
                <tr><th class="text-nowrap">Ke-{{ $period['jam_ke'] }}</th>
                    @foreach ($hariOptions as $day => $namaHari)
                        @php($mapping = $mappings->get($day))
                        @php($hasPeriod = $mapping && $mapping->pola->jamPelajaran->contains(fn ($item) => $item->jenis === 'pelajaran' && (int) $item->jam_ke === $period['jam_ke']))
                        @php($cell = $jadwals->get($day.'|'.$period['jam_ke']))
                        <td class="p-1">
                            @if ($hasPeriod)
                                <button class="btn btn-sm w-100 {{ $cell ? 'btn-success' : 'btn-outline-secondary' }}"
                                    wire:click="bukaSlot({{ $day }}, {{ $period['jam_ke'] }})">
                                    <small>{{ $times[$day.'|'.$period['jam_ke']] ?? '' }}</small><br>
                                    @if ($cell)
                                        <strong>{{ $cell->pengampu->mapel->nama }}</strong><br>
                                        <small>{{ $cell->pengampu->guru->nama }}</small>
                                    @else
                                        <span>+</span>
                                    @endif
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    <div wire:ignore.self class="modal fade" id="jadwalModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">{{ $idjadwal ? 'Ubah Jadwal' : 'Tambah Jadwal' }}</h5></div>
        <div class="modal-body">
            <p class="mb-2"><strong>{{ $hariOptions[$hari] ?? '-' }} · Jam ke-{{ $jam_ke }}</strong></p>
            <label class="form-label">Pengampu</label><select class="form-select" wire:model="pengampu_idpengampu">
                <option value="">Pilih mata pelajaran dan guru</option>
                @foreach ($pengampus as $pengampu)
                    <option value="{{ $pengampu->idpengampu }}">{{ $pengampu->mapel->nama }} · {{ $pengampu->guru->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="modal-footer">
            @if ($idjadwal)<button class="btn btn-danger me-auto" wire:click="hapus" wire:confirm="Hapus jadwal ini?">Hapus Jadwal</button>@endif
            <button class="btn btn-primary" wire:click="simpan">Simpan</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
        </div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-jadwal-modal', () => $('#jadwalModal').modal('hide'));
    window.addEventListener('show-jadwal-modal', () => $('#jadwalModal').modal('show'));
</script>
