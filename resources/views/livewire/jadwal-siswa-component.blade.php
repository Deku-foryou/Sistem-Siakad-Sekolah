@section('title', 'Jadwal Pelajaran')

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Jadwal Pelajaran</h1>
            @if ($konteks->valid())
                <p class="text-body-secondary mb-0">
                    Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot;
                    Semester {{ $konteks->semesterNama() }}
                    @if ($kelasList->count() > 0)
                        &middot; {{ $kelasList->pluck('nama')->implode(', ') }}
                    @endif
                </p>
            @endif
        </div>
        <span class="badge bg-success px-3 py-2">Siswa</span>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold">
                <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                Konteks akademik belum valid
            </div>
            <div class="mt-1">
                Belum ada tahun ajaran/semester aktif atau semester tidak cocok dengan tahun ajaran. Jadwal belum dapat ditampilkan sampai konteks diperbaiki.
            </div>
        </div>
    @elseif (! $siswa)
        <div class="alert alert-warning" role="alert">
            Akun ini belum terhubung ke data siswa. Hubungi administrator.
        </div>
    @elseif ($kelasList->isEmpty())
        <div class="detail-card p-4 text-center">
            <i class="fa-regular fa-calendar-xmark fs-2 text-secondary mb-2" aria-hidden="true"></i>
            <p class="fw-semibold mb-1">Belum memiliki kelas aktif</p>
            <p class="text-body-secondary mb-0">Anda belum terdaftar aktif pada kelas mana pun di tahun ajaran {{ $konteks->tahunAjaranNama() }}. Silakan hubungi administrasi sekolah.</p>
        </div>
    @elseif ($jadwalPerHari->isEmpty())
        <div class="detail-card p-4 text-center">
            <i class="fa-regular fa-calendar-xmark fs-2 text-secondary mb-2" aria-hidden="true"></i>
            <p class="fw-semibold mb-1">Belum ada jadwal</p>
            <p class="text-body-secondary mb-0">Kelas {{ $kelasList->pluck('nama')->implode(', ') }} belum memiliki jadwal pada semester {{ $konteks->semesterNama() }}.</p>
        </div>
    @else
        @foreach ($jadwalPerHari as $hari => $items)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                    <h2 class="h6 fw-bold mb-0">{{ $namaHari[$hari] ?? 'Hari '.$hari }}</h2>
                    <span class="badge bg-light text-dark">{{ $items->count() }} sesi</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 110px;">Waktu</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $jadwal)
                                <tr>
                                    <td class="text-nowrap">{{ substr((string) $jadwal->jamhari->mulai, 0, 5) }}–{{ substr((string) $jadwal->jamhari->selesai, 0, 5) }}</td>
                                    <td class="text-nowrap">{{ $jadwal->jamhari->kelas?->nama ?? '-' }}</td>
                                    <td>{{ $jadwal->mapel?->nama ?? '-' }}</td>
                                    <td>{{ $jadwal->guru?->nama ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
