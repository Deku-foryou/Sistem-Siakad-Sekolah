@section('title', 'Jadwal')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="fw-bold mb-1">Jadwal Pelajaran</h3><span class="text-muted">Pilih sel untuk menambah atau mengganti mapel dan guru.</span></div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" wire:click="publikasikan">Publikasikan</button>
            <button class="btn btn-warning" wire:click="tarikPublikasi">Tarik Publikasi</button>
        </div>
    </div>
    @if ($publikasiAktif)
        <div class="alert alert-success">
            DIPUBLIKASIKAN | Nomor: {{ $publikasiAktif->nomor }} | Tanggal: {{ $publikasiAktif->published_at }}
            <a class="btn btn-sm btn-primary" href="{{ url('/bukti-publikasi/'.$publikasiAktif->idpublikasijadwal) }}">Lihat Bukti</a>
        </div>
    @else
        <div class="alert alert-warning">DRAFT</div>
        @if ($publikasiTerakhir && $publikasiTerakhir->status === 'withdrawn')
            <div class="alert alert-secondary">
                Publikasi sebelumnya ({{ $publikasiTerakhir->nomor }}) telah ditarik pada {{ $publikasiTerakhir->withdrawn_at }}.
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/bukti-publikasi/'.$publikasiTerakhir->idpublikasijadwal) }}">Lihat Bukti</a>
            </div>
        @endif
    @endif
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
    @elseif ($periods === [])
        <div class="alert alert-info">Belum ada pola jam atau jam pelajaran yang dapat ditampilkan. Atur dulu jam pelajaran per hari di menu Jam Pelajaran. <a href="{{ url("/jamhari") }}">Jam Pelajaran</a>.</div>
    @elseif (! $filterKelas)
        <div class="alert alert-info">Belum ada kelas pada tahun ajaran semester ini.</div>
    @else
        <div class="table-responsive"><table class="table table-bordered align-middle text-center">
            <thead class="table-success"><tr><th>Jam</th>
                @foreach ($gridHariOptions as $day => $namaHari)
                    <th>{{ $namaHari }}
                        @foreach ($istirahats as $break)
                                <div><small class="fw-normal">Istirahat {{ substr($break->mulai, 0, 5) }}–{{ substr($break->selesai, 0, 5) }}</small></div>
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
                                wire:click="bukaSlot({{ $day }}, {{ $period['jam_ke'] }})">
                                <small>{{ $times[$day.'|'.$period['jam_ke']] ?? '' }}</small><br>
                                @if ($cell)
                                    <strong>{{ $cell->pengampu->mapel->nama }}</strong><br>
                                    <small>{{ $cell->pengampu->guru->nama }}</small>
                                @else
                                    <span>+</span>
                                @endif
                            </button>
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
            @if ($mapels->isEmpty())
                <div class="alert alert-info">Belum ada mapel. <a href="{{ url('/mapel') }}">Kelola Mapel</a>.</div>
            @else
                <label class="form-label">Mapel</label>
                <select class="form-select mb-3" wire:model.live="mapelId">
                    <option value="">Pilih mapel</option>
                    @foreach ($mapels as $mapel)
                        <option value="{{ $mapel->idmapel }}">{{ $mapel->nama }}</option>
                    @endforeach
                </select>
            @endif
            @if ($activeGurus->isEmpty())
                <div class="alert alert-info">Belum ada guru aktif. <a href="{{ url('/guru') }}">Kelola Guru</a>.</div>
            @else
                <label class="form-label">Guru</label>
                <select class="form-select" wire:model="guruId" @disabled($selectedPengampu !== null)>
                    <option value="">Pilih guru</option>
                    @if ($selectedPengampu && ! $activeGurus->contains('idguru', $selectedPengampu->guru_idguru))
                        <option value="{{ $selectedPengampu->guru_idguru }}">{{ $selectedPengampu->guru->nama }}</option>
                    @endif
                    @foreach ($activeGurus as $guru)
                        <option value="{{ $guru->idguru }}">{{ $guru->nama }}</option>
                    @endforeach
                </select>
                @if ($selectedPengampu)
                    <small class="text-muted">Pengampu terdaftar</small>
                @endif
            @endif
        </div>
        <div class="modal-footer">
            @if ($idjadwal)<button class="btn btn-danger me-auto" wire:click="hapus" wire:confirm="Hapus jadwal ini?">Hapus Jadwal</button>@endif
            <button class="btn btn-primary" wire:click="simpan" @disabled($mapels->isEmpty() || $activeGurus->isEmpty() || ! $mapelId || ! $guruId)>Simpan</button><button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="close">Tutup</button>
        </div>
    </div></div></div>
</div>
<script>
    window.addEventListener('close-jadwal-modal', () => $('#jadwalModal').modal('hide'));
    window.addEventListener('show-jadwal-modal', () => $('#jadwalModal').modal('show'));
</script>
