@section('title')
Beranda Guru
@endsection

<div class="container-fluid px-3 px-lg-4 py-4">
    <h1 class="h3 fw-bold mb-4">Beranda Guru</h1>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            @if (! $guru)
                <div class="card border shadow-sm">
                    <div class="card-body">Data guru belum terhubung. Hubungi admin sekolah.</div>
                </div>
            @else
                @php
                    $inisial = collect(preg_split('/\s+/', trim((string) $guru->nama)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))
                        ->implode('');
                    $statusGuru = match ($guru->status) {
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                        default => $guru->status ?: '-',
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
                                <div class="fw-bold fs-5 text-break">{{ $guru->nama ?: '-' }}</div>
                                <div class="detail-identity-number">NIP: {{ $guru->nip ?: '-' }}</div>
                            </div>
                        </div>
                        <table class="table table-sm table-borderless detail-table mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row">Status</th>
                                    <td>
                                        <span class="badge {{ $guru->status === 'aktif' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $statusGuru }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        <div class="col-12 col-lg-8 d-flex flex-column gap-3">
            <section class="detail-card">
                <h2 class="detail-card-title mb-0">Status Akun</h2>
                <div class="p-3">
                    <table class="table table-sm table-borderless detail-table mb-3">
                        <tbody>
                            <tr><th scope="row">Username</th><td>{{ $akun['username'] }}</td></tr>
                            <tr>
                                <th scope="row">Status Akun</th>
                                <td>{{ $akun['wajibGantiPassword'] ? 'Belum ganti password' : 'Sudah ganti password' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <a class="btn btn-success btn-sm" href="/setting">Ganti password</a>
                </div>
            </section>

            <section class="detail-card">
                <h2 class="detail-card-title mb-0">Wali Kelas</h2>
                <div class="p-3">
                    @if (! $tahunAjaranAktif)
                        <p class="mb-0">Belum ada tahun ajaran aktif</p>
                    @elseif ($kelasWali->isEmpty())
                        <p class="mb-0">Anda tidak menjadi wali kelas</p>
                    @else
                        @foreach ($kelasWali as $kelas)
                            <div class="{{ ! $loop->last ? 'mb-4' : '' }}">
                                <h3 class="h6 fw-bold mb-2">
                                    {{ $kelas->nama ?: '-' }} ({{ $kelas->tingkat ?: '-' }})
                                    <span class="text-body-secondary fw-normal">· {{ $kelas->kelassiswa->count() }} siswa</span>
                                </h3>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped detail-history-table mb-0">
                                        <thead>
                                            <tr><th scope="col">NISN</th><th scope="col">Nama</th><th scope="col">JK</th></tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($kelas->kelassiswa as $penempatan)
                                                <tr>
                                                    <td>{{ $penempatan->siswa?->nisn ?: '-' }}</td>
                                                    <td>{{ $penempatan->siswa?->nama ?: '-' }}</td>
                                                    <td>{{ $penempatan->siswa?->jenis_kelamin ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3">Belum ada siswa di kelas ini</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </section>
        </div>

        @foreach (['Jadwal mengajar hari ini', 'Izin menunggu persetujuan'] as $placeholder)
            <div class="col-12 col-md-6">
                <section class="detail-card h-100">
                    <h2 class="detail-card-title mb-0">{{ $placeholder }}</h2>
                    <div class="p-3 text-body-secondary">Segera tersedia</div>
                </section>
            </div>
        @endforeach
    </div>
</div>
