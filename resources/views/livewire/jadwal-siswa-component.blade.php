@section('title', 'Jadwal Pelajaran')

<div class="container-fluid px-3 px-lg-4 py-3">
<style>
    .jadwal-ringkasan { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
    .jadwal-ringkasan-icon { width: 56px; height: 56px; border-radius: 12px; background: #e8fbee; color: #0e7d6a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
    .jadwal-ringkasan-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }
    .jadwal-ringkasan-nama { font-weight: 700; color: #111827; font-size: 15px; line-height: 1.35; }
    .jadwal-ringkasan-detail { font-size: 14px; line-height: 1.5; margin-top: 4px; word-break: break-word; }
    .jadwal-hari-card { border-radius: 12px; overflow: hidden; }
    .jadwal-hari-ini { border: 2px solid #198754; }
    .jadwal-sesi { padding: 12px 16px; border-top: 1px solid #eee; }
    .jadwal-sesi:first-child { border-top: 0; }
    .jadwal-waktu { min-width: 92px; }
    .jadwal-waktu-jam { font-weight: 700; color: #111827; font-size: 14px; white-space: nowrap; }
    .jadwal-waktu-ke { font-size: 12px; color: #6b7280; }
    .jadwal-mapel { font-weight: 700; color: #111827; font-size: 14px; line-height: 1.4; word-break: break-word; }
    .jadwal-kelas { font-size: 13px; }
</style>

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
        @php
            $jadwalHariIni = $jadwalPerHari->get((int) $hariIni);
        @endphp

        {{-- Ringkasan hari ini --}}
        <section class="jadwal-ringkasan mb-3" aria-label="Ringkasan jadwal hari ini">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="jadwal-ringkasan-icon" aria-hidden="true">
                    <i class="fa-regular fa-calendar-days"></i>
                </div>
                <div class="min-w-0 flex-grow-1">
                    <div class="jadwal-ringkasan-label">Hari Ini</div>
                    <div class="jadwal-ringkasan-nama">{{ $namaHari[(int) $hariIni] ?? '' }} &middot; {{ now()->format('d/m/Y') }}</div>
                    @if ($jadwalHariIni && $jadwalHariIni->isNotEmpty())
                        <div class="jadwal-ringkasan-detail">
                            @foreach ($jadwalHariIni as $item)
                                <div>
                                    <span class="fw-semibold">{{ $item->mapel?->nama ?? '-' }}</span>
                                    <span class="text-body-secondary">({{ $item->jamhari->kelas?->nama ?? '-' }} &middot; {{ substr((string) $item->jamhari->mulai, 0, 5) }}–{{ substr((string) $item->jamhari->selesai, 0, 5) }} &middot; {{ $item->guru?->nama ?? '-' }})</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="jadwal-ringkasan-detail text-body-secondary">Tidak ada jadwal pelajaran hari ini</div>
                    @endif
                </div>
            </div>
        </section>

        @foreach ($jadwalPerHari as $hari => $items)
            @php $isHariIni = (int) $hari === (int) $hariIni; @endphp
            <div class="card jadwal-hari-card shadow-sm mb-3 {{ $isHariIni ? 'jadwal-hari-ini' : '' }}">
                <div class="card-header d-flex align-items-center justify-content-between py-2 {{ $isHariIni ? 'bg-success-subtle' : 'bg-white' }}">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <h2 class="h6 fw-bold mb-0">{{ $namaHari[$hari] ?? 'Hari '.$hari }}</h2>
                        @if ($isHariIni)
                            <span class="badge bg-success">Hari Ini</span>
                        @endif
                    </div>
                    <span class="badge bg-light text-dark">{{ $items->count() }} sesi</span>
                </div>
                <div class="card-body p-0">
                    @foreach ($items as $jadwal)
                        <div class="jadwal-sesi d-flex align-items-center gap-3 flex-wrap">
                            <div class="jadwal-waktu flex-shrink-0">
                                <div class="jadwal-waktu-jam">{{ substr((string) $jadwal->jamhari->mulai, 0, 5) }}–{{ substr((string) $jadwal->jamhari->selesai, 0, 5) }}</div>
                                @if ($jadwal->jamhari->jam_ke)
                                    <div class="jadwal-waktu-ke">Jam ke-{{ $jadwal->jamhari->jam_ke }}</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="jadwal-mapel">{{ $jadwal->mapel?->nama ?? '-' }}</div>
                                <div class="jadwal-kelas text-body-secondary"><i class="fa-solid fa-chalkboard-user me-1" aria-hidden="true"></i>{{ $jadwal->guru?->nama ?? '-' }}</div>
                            </div>
                            <span class="badge bg-light text-dark">{{ $jadwal->jamhari->kelas?->nama ?? '-' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
