@section('title')
    Beranda Wali
@endsection

<div>
    <div class="container-fluid px-4" style="margin-top: 20px">
        <div class="row g-4">

            <!-- Identitas akun -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 class="h3 fw-bold mb-1">Selamat datang, {{ $user->name }}</h1>
                                <p class="text-muted mb-0">Portal Wali Murid — SIAKAD</p>
                            </div>
                            <span class="badge bg-success fs-6"><i class="fa-solid fa-circle-check me-1"></i>Aktif</span>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Username</div>
                                <div class="fw-semibold"><code>{{ $user->username }}</code></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Email</div>
                                <div class="fw-semibold">{{ $user->email ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Peran</div>
                                <div class="fw-semibold">Wali Murid</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Status akun</div>
                                <div class="fw-semibold">
                                    @if ($user->status_akun === 'aktif')
                                        <span class="text-success">Aktif</span>
                                    @else
                                        <span class="text-danger">Nonaktif</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <a href="/setting" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Ganti password">
                            <i class="fa-solid fa-key me-1"></i>Ganti password
                        </a>
                    </div>
                </div>
            </div>

            <!-- Informasi hubungan & portal akademik -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm border-start border-warning border-4" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark">
                            <i class="fa-solid fa-circle-exclamation text-warning me-2"></i>Belum ada hubungan siswa
                        </h2>
                        <p class="mb-2">Akun Anda belum terhubung dengan siswa mana pun, atau hubungan tersebut belum diverifikasi oleh pihak sekolah.</p>
                        <p class="mb-0">Fitur <strong>akademik — nilai, rapor, absensi, jadwal, catatan wali kelas</strong> — akan tersedia di sini <strong>setelah hubungan wali–siswa terverifikasi</strong>.</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold text-muted"><i class="fa-solid fa-shield-halved me-2"></i>Menjaga Akun Anda</h2>
                        <ul class="small mb-0 ps-3">
                            <li>Jangan membagikan password Anda kepada siapa pun, termasuk pihak sekolah.</li>
                            <li>Ganti password sementara segera setelah login pertama.</li>
                            <li>Hubungi admin sekolah bila Anda tidak merasa membuat akun ini.</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
