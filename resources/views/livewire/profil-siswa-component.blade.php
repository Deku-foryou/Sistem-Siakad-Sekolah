@section('title', 'Profil Saya')

<div class="container-fluid px-3 px-lg-4 py-4">
    <style>
        .profil-summary { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        .profil-avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .profil-avatar-fallback { background: #0e7d6a; color: #fff; font-weight: 700; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; }
        .profil-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; height: 100%; }
        .profil-stat-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .profil-stat-value { font-weight: 700; color: #111827; font-size: 15px; line-height: 1.35; word-break: break-word; }
        .profil-field { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; height: 100%; }
        .profil-field-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .profil-field-value { font-weight: 600; color: #111827; font-size: 14px; line-height: 1.45; word-break: break-word; }
        .profil-empty { color: #9ca3af; font-weight: 400; }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Profil Saya</h1>
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
                Belum ada tahun ajaran/semester aktif atau semester tidak cocok dengan tahun ajaran. Informasi akademik belum dapat ditampilkan sampai konteks diperbaiki.
            </div>
        </div>
    @elseif (! $siswa)
        <div class="alert alert-warning" role="alert">
            Akun ini belum terhubung ke data siswa. Hubungi admin sekolah.
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
                default => $siswa->status ?: 'Belum diisi',
            };
            $statusBadge = match ($siswa->status) {
                'aktif' => 'bg-success',
                'lulus' => 'bg-info text-dark',
                'pindah' => 'bg-secondary',
                default => 'bg-danger',
            };
            $nikTersamarkan = $siswa->nik
                ? '***-****-****-' . substr($siswa->nik, -4)
                : 'Belum diisi';
        @endphp

        {{-- A. Header Profil --}}
        <section class="profil-summary mb-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                @if ($fotoUrl)
                    <img src="{{ $fotoUrl }}" alt="Foto {{ $siswa->nama }}" class="profil-avatar">
                @else
                    <div class="profil-avatar profil-avatar-fallback" aria-hidden="true">{{ $inisial ?: '-' }}</div>
                @endif
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold fs-5 text-break">{{ $siswa->nama ?: 'Belum diisi' }}</div>
                    <div class="text-body-secondary">
                        NIS: {{ $siswa->nis ?: 'Belum diisi' }} &middot; NISN: {{ $siswa->nisn ?: 'Belum diisi' }}
                    </div>
                </div>
                <span class="badge {{ $statusBadge }} px-3 py-2">{{ $statusSiswa }}</span>
            </div>
        </section>

        {{-- B. Ringkasan Akademik --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="profil-stat-card">
                    <div class="profil-stat-label"><i class="fa-regular fa-calendar-check me-1" aria-hidden="true"></i>Tahun Ajaran</div>
                    <div class="profil-stat-value">{{ $konteks->tahunAjaranNama() ?: 'Belum diisi' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="profil-stat-card">
                    <div class="profil-stat-label"><i class="fa-regular fa-bookmark me-1" aria-hidden="true"></i>Semester</div>
                    <div class="profil-stat-value">{{ $konteks->semesterNama() ?: 'Belum diisi' }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="profil-stat-card">
                    <div class="profil-stat-label"><i class="fa-solid fa-school me-1" aria-hidden="true"></i>Kelas</div>
                    <div class="profil-stat-value">{{ $kelasList->pluck('nama')->implode(', ') ?: 'Belum ditempatkan di kelas aktif' }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="profil-stat-card">
                    <div class="profil-stat-label"><i class="fa-solid fa-book-open me-1" aria-hidden="true"></i>Jurusan</div>
                    <div class="profil-stat-value">{{ $kelasList->map(fn ($k) => $k->jurusan?->nama)->filter()->unique()->implode(', ') ?: 'Belum diisi' }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="profil-stat-card">
                    <div class="profil-stat-label"><i class="fa-solid fa-person-chalkboard me-1" aria-hidden="true"></i>Wali Kelas</div>
                    <div class="profil-stat-value">{{ $kelasList->map(fn ($k) => $k->wali?->nama)->filter()->unique()->implode(', ') ?: 'Belum ditentukan' }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- C. Informasi Pribadi --}}
            <div class="col-12 col-lg-6">
                <h2 class="h6 fw-bold mb-2">Informasi Pribadi</h2>
                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">NIK</div>
                            <div class="profil-field-value {{ $siswa->nik ? '' : 'profil-empty' }}">{{ $nikTersamarkan }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Tempat Lahir</div>
                            <div class="profil-field-value {{ $siswa->tempat_lahir ? '' : 'profil-empty' }}">{{ $siswa->tempat_lahir ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Tanggal Lahir</div>
                            <div class="profil-field-value {{ $siswa->tanggal_lahir ? '' : 'profil-empty' }}">{{ $siswa->tanggal_lahir?->format('d/m/Y') ?? 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Jenis Kelamin</div>
                            <div class="profil-field-value {{ $siswa->jenis_kelamin ? '' : 'profil-empty' }}">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : 'Belum diisi') }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Agama</div>
                            <div class="profil-field-value {{ $siswa->agama ? '' : 'profil-empty' }}">{{ $siswa->agama ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Tahun Masuk</div>
                            <div class="profil-field-value {{ $siswa->tahun_masuk ? '' : 'profil-empty' }}">{{ $siswa->tahun_masuk ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="profil-field">
                            <div class="profil-field-label">Asal Sekolah</div>
                            <div class="profil-field-value {{ $siswa->asal_sekolah ? '' : 'profil-empty' }}">{{ $siswa->asal_sekolah ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- D. Kontak & Keluarga --}}
            <div class="col-12 col-lg-6">
                <h2 class="h6 fw-bold mb-2">Kontak &amp; Keluarga</h2>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="profil-field">
                            <div class="profil-field-label">Alamat</div>
                            <div class="profil-field-value {{ $siswa->alamat ? '' : 'profil-empty' }}">{{ $siswa->alamat ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Telepon Siswa</div>
                            <div class="profil-field-value {{ $siswa->telepon_siswa ? '' : 'profil-empty' }}">{{ $siswa->telepon_siswa ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Email Siswa</div>
                            <div class="profil-field-value {{ $siswa->email ? '' : 'profil-empty' }}">{{ $siswa->email ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    @if (! ($siswa->nama_ayah || $siswa->telepon_ayah || $siswa->nama_ibu || $siswa->telepon_ibu || $siswa->nama_wali || $siswa->telepon_wali || $siswa->telepon_ortu))
                        <div class="col-12">
                            <div class="alert alert-warning py-2 mb-0 small" role="status">
                                <i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i>
                                Kontak orang tua/wali belum tersedia. Hubungi admin sekolah untuk melengkapinya.
                            </div>
                        </div>
                    @endif
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Ayah</div>
                            <div class="profil-field-value">
                                <div>{{ $siswa->nama_ayah ?: 'Belum diisi' }}</div>
                                <div class="text-body-secondary small">
                                    @if ($siswa->telepon_ayah)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siswa->telepon_ayah) }}" class="text-decoration-none">{{ $siswa->telepon_ayah }}</a>
                                    @else
                                        Belum diisi
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Ibu</div>
                            <div class="profil-field-value">
                                <div>{{ $siswa->nama_ibu ?: 'Belum diisi' }}</div>
                                <div class="text-body-secondary small">
                                    @if ($siswa->telepon_ibu)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siswa->telepon_ibu) }}" class="text-decoration-none">{{ $siswa->telepon_ibu }}</a>
                                    @else
                                        Belum diisi
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="profil-field">
                            <div class="profil-field-label">Wali Murid</div>
                            <div class="profil-field-value">
                                <div>{{ $siswa->nama_wali ?: 'Belum diisi' }}</div>
                                <div class="text-body-secondary small">
                                    @if ($siswa->telepon_wali)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siswa->telepon_wali) }}" class="text-decoration-none">{{ $siswa->telepon_wali }}</a>
                                    @else
                                        Belum diisi
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($siswa->telepon_ortu)
                        <div class="col-12 col-sm-6">
                            <div class="profil-field">
                                <div class="profil-field-label">Telepon Orang Tua/Wali</div>
                                <div class="profil-field-value">
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siswa->telepon_ortu) }}" class="text-decoration-none">{{ $siswa->telepon_ortu }}</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- E. Informasi Akun --}}
            <div class="col-12">
                <h2 class="h6 fw-bold mb-2">Informasi Akun</h2>
                <div class="profil-field d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1 min-w-0">
                        <div class="profil-field-label">Username</div>
                        <div class="profil-field-value">{{ $akun['username'] }}</div>
                    </div>
                    <div>
                        <div class="profil-field-label">Status Password</div>
                        <div class="profil-field-value {{ $akun['wajibGantiPassword'] ? 'text-warning' : '' }}">{{ $akun['wajibGantiPassword'] ? 'Belum diganti' : 'Sudah diganti' }}</div>
                    </div>
                    <a class="btn btn-outline-success btn-sm" href="/setting">Pengaturan Akun</a>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3 mb-0" role="note">
            <i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>
            Halaman ini bersifat baca saja. Jika ada data yang belum benar atau belum terisi, silakan hubungi admin sekolah untuk pengajuan perubahan.
        </div>
    @endif
</div>
