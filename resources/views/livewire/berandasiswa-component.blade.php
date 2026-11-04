@section('title')
Beranda Siswa
@endsection

<div class="container-fluid px-3 px-lg-4 py-4">
    <h1 class="h3 fw-bold mb-4">Beranda Siswa</h1>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            @if (! $siswa)
                <div class="card border shadow-sm">
                    <div class="card-body">Data siswa belum terhubung. Hubungi admin sekolah.</div>
                </div>
            @else
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
                        <table class="table table-sm table-borderless detail-table mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row">Kelas Induk</th>
                                    <td>
                                        @if (! $tahunAjaranAktif)
                                            Belum ada tahun ajaran aktif
                                        @elseif (! $kelasIndukAktif)
                                            Belum ditempatkan di kelas
                                        @else
                                            {{ $kelasIndukAktif->nama ?: '-' }} ({{ $kelasIndukAktif->tingkat ?: '-' }}) · Wali: {{ $kelasIndukAktif->wali?->nama ?: '-' }}
                                        @endif
                                    </td>
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
                </section>
            @endif
        </div>

        <div class="col-12 col-lg-4">
            <section class="detail-card h-100">
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
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <section class="detail-card h-100">
                <h2 class="detail-card-title mb-0">Jadwal hari ini ({{ $namaHariIni }})</h2>
                <div class="p-3">
                    @if(count($jadwalHariIni) > 0)
                        <table class="table table-sm table-borderless detail-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Jam</th>
                                    <th scope="col">Mapel</th>
                                    <th scope="col">Guru</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jadwalHariIni as $item)
                                    <tr>
                                        <td>{{ $item['jam_ke'] }}</td>
                                        <td>{{ $item['mapel'] ?? '-' }}</td>
                                        <td>{{ $item['guru'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-body-secondary">Tidak ada jadwal hari ini.</div>
                    @endif
                </div>
            </section>
        </div>

        @foreach (['Absensi hari ini', 'Tagihan SPP'] as $placeholder)
            <div class="col-12 col-md-6 col-lg-4">
                <section class="detail-card h-100">
                    <h2 class="detail-card-title mb-0">{{ $placeholder }}</h2>
                    <div class="p-3 text-body-secondary">Segera tersedia</div>
                </section>
            </div>
        @endforeach
    </div>
</div>
