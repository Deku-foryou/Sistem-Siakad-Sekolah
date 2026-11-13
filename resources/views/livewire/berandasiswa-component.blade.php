@section('title')
Beranda Siswa
@endsection

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Beranda Siswa</h1>
            @if ($konteks->valid())
                <p class="text-body-secondary mb-0">
                    Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot;
                    Semester {{ $konteks->semesterNama() }}
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
        <div class="card border shadow-sm">
            <div class="card-body">Akun ini belum terhubung ke data siswa. Hubungi admin sekolah.</div>
        </div>
    @else
        <div class="row g-3">
            <div class="col-12 col-lg-8">
                @php
                    $inisial = collect(preg_split('/\s+/', trim((string) $siswa->nama)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))
                        ->implode('');
                    $statusSiswa = match ($siswa->status) {
                        'aktif' => 'Aktif',
                        'lulus' => 'Lulus',
                        'pindah' => 'Pindah',
                        'keluar' => 'Keluar',
                        default => $siswa->status ?: '-',
                    };
                @endphp
                <section class="detail-card h-100">
                    <h2 class="detail-card-title mb-0">Biodata</h2>
                    <div class="p-3">
                        <div class="d-flex align-items-center gap-3 pb-3 mb-2 border-bottom">
                            <div class="detail-avatar rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" aria-hidden="true">
                                {{ $inisial ?: '-' }}
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold fs-5 text-break">{{ $siswa->nama ?: '-' }}</div>
                                <div class="detail-identity-number">NISN: {{ $siswa->nisn ?: '-' }} · NIS: {{ $siswa->nis ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless detail-table mb-0">
                                <tbody>
                                    <tr>
                                        <th scope="row">Tahun Ajaran</th>
                                        <td>{{ $konteks->tahunAjaranNama() ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Semester</th>
                                        <td>{{ $konteks->semesterNama() ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Kelas</th>
                                        <td>
                                            @if ($kelasList->isEmpty())
                                                Belum ditempatkan di kelas aktif
                                            @else
                                                {{ $kelasList->pluck('nama')->implode(', ') }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Jurusan</th>
                                        <td>{{ $kelasList->map(fn ($kelas) => $kelas->jurusan?->nama)->filter()->unique()->implode(', ') ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Wali Kelas</th>
                                        <td>{{ $kelasList->map(fn ($kelas) => $kelas->wali?->nama)->filter()->unique()->implode(', ') ?: '-' }}</td>
                                    </tr>
                                    <tr><th scope="row">Tahun Masuk</th><td>{{ $siswa->tahun_masuk ?: '-' }}</td></tr>
                                    <tr>
                                        <th scope="row">Status</th>
                                        <td>
                                            <span class="badge {{ $siswa->status === 'aktif' ? 'bg-success' : ($siswa->status === 'lulus' ? 'bg-info text-dark' : ($siswa->status === 'pindah' ? 'bg-secondary' : 'bg-danger')) }}">
                                                {{ $statusSiswa }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-4">
                <section class="detail-card h-100">
                    <h2 class="detail-card-title mb-0">Status Akun</h2>
                    <div class="p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless detail-table mb-3">
                                <tbody>
                                    <tr><th scope="row">Username</th><td>{{ $akun['username'] }}</td></tr>
                                    <tr>
                                        <th scope="row">Status Akun</th>
                                        <td>{{ $akun['wajibGantiPassword'] ? 'Belum ganti password' : 'Sudah ganti password' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <a class="btn btn-success btn-sm" href="/setting" data-bs-toggle="tooltip" data-bs-placement="top" title="Ganti password">Ganti password</a>
                    </div>
                </section>
            </div>

            <div class="col-12">
                <section class="detail-card h-100">
                    <h2 class="detail-card-title mb-0">Jadwal hari ini ({{ $namaHariIni }})</h2>
                    <div class="p-3">
                        @if ($jadwalHariIni->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Jam Ke</th>
                                            <th style="width: 130px;">Waktu</th>
                                            <th>Kelas</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Guru</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jadwalHariIni as $item)
                                            <tr>
                                                <td>{{ $item->jamhari->jam_ke ?? '-' }}</td>
                                                <td class="text-nowrap">{{ substr((string) $item->jamhari->mulai, 0, 5) }}–{{ substr((string) $item->jamhari->selesai, 0, 5) }}</td>
                                                <td class="text-nowrap">{{ $item->jamhari->kelas?->nama ?? '-' }}</td>
                                                <td>{{ $item->mapel?->nama ?? '-' }}</td>
                                                <td>{{ $item->guru?->nama ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 text-end">
                                <a class="btn btn-outline-success btn-sm" href="/jadwalsiswa" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail">Lihat semua jadwal</a>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fa-regular fa-calendar-xmark fs-2 text-secondary mb-2" aria-hidden="true"></i>
                                <p class="fw-semibold mb-1">Tidak ada jadwal hari ini</p>
                                <p class="text-body-secondary mb-3">Hari {{ $namaHariIni }} tidak ada mata pelajaran untuk kelas Anda pada semester {{ $konteks->semesterNama() }}.</p>
                                <a class="btn btn-outline-success btn-sm" href="/jadwalsiswa" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat detail">Lihat jadwal mingguan</a>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>
