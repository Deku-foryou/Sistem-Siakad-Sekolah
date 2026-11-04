@section('title', 'Dashboard Superadmin')

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Superadmin</h1>
            <p class="text-body-secondary mb-0">Pusat informasi akademik SIAKAD.</p>
        </div>
        <span class="badge bg-success px-3 py-2">
            <i class="fa-solid fa-user-shield me-1" aria-hidden="true"></i> Superadmin
        </span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <section class="detail-card h-100">
                <div class="p-3">
                    <div class="text-body-secondary small">Total Siswa Aktif</div>
                    <div class="fs-3 fw-bold">{{ $statistik['siswa_aktif'] }}</div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <section class="detail-card h-100">
                <div class="p-3">
                    <div class="text-body-secondary small">Total Guru Aktif</div>
                    <div class="fs-3 fw-bold">{{ $statistik['guru_aktif'] }}</div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <section class="detail-card h-100">
                <div class="p-3">
                    <div class="text-body-secondary small">Total Kelas (Thn. Aktif)</div>
                    @if ($konteks->valid())
                        <div class="fs-3 fw-bold">{{ $statistik['kelas'] }}</div>
                    @else
                        <div class="fs-6 fw-semibold text-warning">Konteks tidak valid</div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <section class="detail-card h-100">
                <div class="p-3">
                    <div class="text-body-secondary small">Total Mata Pelajaran</div>
                    <div class="fs-3 fw-bold">{{ $statistik['mapel'] }}</div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <section class="detail-card h-100">
                <h2 class="detail-card-title mb-0">Identitas Pengguna</h2>
                <div class="p-3">
                    <div class="fw-bold fs-5 text-break">{{ $pengguna->name ?: '-' }}</div>
                    <div class="detail-identity-number mb-3">{{ $pengguna->username ? '@'.$pengguna->username : '-' }}</div>
                    <table class="table table-sm table-borderless detail-table mb-0">
                        <tbody>
                            <tr>
                                <th scope="row">Username</th>
                                <td>{{ $pengguna->username ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Email</th>
                                <td class="text-break">{{ $pengguna->email ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Level Akses</th>
                                <td><span class="badge bg-success">Superadmin</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <section class="detail-card h-100">
                <h2 class="detail-card-title mb-0">Tahun Ajaran</h2>
                <div class="p-3 d-flex flex-column h-100">
                    <div class="text-body-secondary small mb-1">Tahun ajaran aktif</div>
                    @if ($konteks->tahunAjaran)
                        <div class="fs-4 fw-bold text-break">{{ $konteks->tahunAjaran->nama }}</div>
                        <div><span class="badge bg-success">Aktif</span></div>
                    @else
                        <div class="fs-5 fw-semibold text-body-secondary">Belum ada tahun ajaran aktif</div>
                        <div><span class="badge bg-secondary">Tidak ada</span></div>
                    @endif
                    <a href="/tahunajaran" class="small mt-3 text-decoration-none">
                        Kelola tahun ajaran <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <section class="detail-card h-100">
                <h2 class="detail-card-title mb-0">Semester</h2>
                <div class="p-3 d-flex flex-column h-100">
                    <div class="text-body-secondary small mb-1">Semester aktif</div>
                    @if ($konteks->semester)
                        <div class="fs-4 fw-bold text-break">Semester {{ $konteks->semester->nama }}</div>
                        <div class="detail-identity-number">
                            Tahun ajaran: {{ $konteks->semesterTahunAjaranNama() ?: '-' }}
                        </div>
                    @else
                        <div class="fs-5 fw-semibold text-body-secondary">Belum ada semester aktif</div>
                    @endif
                    <a href="/tahunajaran" class="small mt-3 text-decoration-none">
                        Kelola tahun ajaran dan semester <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            @if ($konteks->punyaPeringatan())
                <div class="alert alert-warning" role="alert">
                    <div class="fw-semibold mb-2">
                        <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                        Konteks akademik belum valid
                    </div>
                    <ul class="mb-3 ps-3">
                        @foreach ($konteks->pesanPeringatan() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="/tahunajaran" class="btn btn-sm btn-outline-success">Kelola Tahun Ajaran</a>
                        <a href="/tahunajaran" class="btn btn-sm btn-outline-success">Kelola Tahun Ajaran & Semester</a>
                    </div>
                </div>
            @else
                <div class="alert alert-success" role="status">
                    <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
                    Konteks akademik aktif dan konsisten:
                    tahun ajaran {{ $konteks->tahunAjaranNama() }},
                    semester {{ $konteks->semesterNama() }}.
                </div>
            @endif
        </div>

        <div class="col-12">
            <section class="detail-card">
                <h2 class="detail-card-title mb-0">Ringkasan Akademik</h2>
                <div class="p-4 text-center">
                    <i class="fa-solid fa-chart-simple fs-3 text-secondary mb-2" aria-hidden="true"></i>
                    @if ($konteks->valid())
                        <p class="fw-semibold mb-1">Statistik akademik belum tersedia</p>
                        <p class="text-body-secondary mb-0">
                            Ringkasan siswa, guru, kelas, jadwal, dan absensi akan ditambahkan pada tahap pengembangan berikutnya.
                        </p>
                    @else
                        <p class="fw-semibold mb-1">Ringkasan akademik belum dapat ditampilkan</p>
                        <p class="text-body-secondary mb-0">
                            Lengkapi dan aktifkan konteks tahun ajaran serta semester terlebih dahulu.
                        </p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
