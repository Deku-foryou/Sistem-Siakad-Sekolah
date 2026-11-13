@section('title', 'Jadwal Pelajaran')

{{-- Hanya tampil saat cetak --}}
<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="print-header" aria-hidden="true">
        <h1>Jadwal Pelajaran</h1>
        <p>
            {{ $siswa?->nama ?: '-' }}
            @if ($kelasList->isNotEmpty())
                &middot; {{ $kelasList->pluck('nama')->implode(', ') }}
            @endif
            &middot; Tahun Ajaran {{ $konteks->tahunAjaranNama() ?: '-' }}
            &middot; Semester {{ $konteks->semesterNama() ?: '-' }}
        </p>
    </div>
    @include('assets.akademik')
    <style>
        /* Ringkasan hari ini + kartu hari (cetak-aman) */
        .jadwal-ringkasan { background: #fff; border: 1px solid var(--sa-border); border-radius: var(--sa-radius); padding: 1rem 1.1rem; box-shadow: var(--sa-shadow); }
        .jadwal-ringkasan-icon { width: 52px; height: 52px; border-radius: .75rem; background: var(--sa-green-faint); color: var(--sa-green-dark); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
        .jadwal-ringkasan-label { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--sa-muted); }
        .jadwal-ringkasan-nama { font-weight: 800; color: var(--sa-ink); font-size: .95rem; }
        .jadwal-ringkasan-detail { font-size: .9rem; line-height: 1.55; margin-top: .35rem; word-break: break-word; }
        .jadwal-hari-card { margin-bottom: 1rem; }
        .jadwal-sesi { padding: .7rem 1rem; border-top: 1px solid var(--sa-border); }
        .jadwal-sesi:first-child { border-top: 0; }
        .jadwal-waktu { min-width: 92px; }
        .jadwal-waktu-jam { font-weight: 700; color: var(--sa-ink); font-size: .88rem; white-space: nowrap; }
        .jadwal-waktu-ke { font-size: .74rem; color: var(--sa-muted); }
        .jadwal-mapel { font-weight: 700; color: var(--sa-ink); font-size: .9rem; line-height: 1.4; }
        .jadwal-kelas { font-size: .82rem; }
        .print-header { display: none; }

        @media print {
            @page { size: A4; margin: 12mm; }

            body { background: #fff !important; }

            .wrapper { margin: 0 !important; padding: 0 !important; }

            #sidebar,
            header.header,
            .no-print,
            .border-top,
            .footer { display: none !important; }

            .container-fluid { padding: 0 !important; width: 100% !important; }

            .print-header { display: block !important; text-align: center; margin-bottom: 12px; }
            .print-header h1 { font-size: 16px; font-weight: 700; margin: 0 0 2px; }
            .print-header p { margin: 0; font-size: 11px; color: #333; }

            .jadwal-ringkasan { display: none !important; }
            .sa-card { box-shadow: none !important; border: 1px solid #bbb !important; border-radius: 0 !important; margin-bottom: 10px !important; page-break-inside: avoid; }
            .jadwal-hari-ini { outline: 2px solid #198754 !important; }
            .sa-card-head { background: #fff !important; border-radius: 0 !important; }
        }
    </style>

    {{-- Header halaman --}}
    <div class="sa-head no-print">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-calendar-days me-2 text-success" aria-hidden="true"></i>Jadwal Pelajaran</h1>
            @if ($konteks->valid())
                <p class="sa-sub">
                    TA {{ $konteks->tahunAjaranNama() }} &middot; Semester {{ $konteks->semesterNama() }}
                    @if ($kelasList->count() > 0)
                        &middot; {{ $kelasList->pluck('nama')->implode(', ') }}
                    @endif
                </p>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" data-bs-toggle="tooltip" data-bs-placement="top" title="Cetak">
                <i class="fa-solid fa-print me-1" aria-hidden="true"></i>Cetak Jadwal
            </button>
            <span class="sa-chip"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Siswa</span>
        </div>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Konteks akademik belum valid</div>
            <div class="mt-1">Belum ada tahun ajaran/semester aktif atau semester tidak cocok dengan tahun ajaran. Jadwal belum dapat ditampilkan sampai konteks diperbaiki.</div>
        </div>
    @elseif (! $siswa)
        <div class="alert alert-warning" role="alert">Akun ini belum terhubung ke data siswa. Hubungi administrator.</div>
    @elseif ($kelasList->isEmpty())
        <div class="sa-card">
            <div class="sa-card-body sa-empty">
                <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                <p class="sa-empty-title">Belum memiliki kelas aktif</p>
                <p class="sa-empty-text">Anda belum terdaftar aktif pada kelas mana pun di tahun ajaran {{ $konteks->tahunAjaranNama() }}. Silakan hubungi administrasi sekolah.</p>
            </div>
        </div>
    @else
        {{-- Navigasi hari --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print" role="group" aria-label="Filter hari" wire:loading.class="opacity-50">
            <span class="sa-hint fw-semibold me-1">Hari:</span>
            <button type="button" class="btn btn-sm {{ $filterHari === '0' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('filterHari', '0')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tampilkan semua">Semua</button>
            <button type="button" class="btn btn-sm {{ (string) $filterHari === (string) $hariIni ? 'btn-success' : 'btn-outline-success' }}" wire:click="$set('filterHari', '{{ $hariIni }}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Tampilkan hari ini">Hari Ini</button>
            @foreach ($namaHari as $h => $n)
                <button type="button" class="btn btn-sm {{ (string) $filterHari === (string) $h ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('filterHari', '{{ $h }}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Filter jadwal per hari" aria-label="Filter jadwal per hari">{{ $n }}</button>
            @endforeach
        </div>

        @if ($jadwalPerHari->isEmpty())
            <div class="sa-card">
                <div class="sa-card-body sa-empty">
                    <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                    <p class="sa-empty-title">Belum ada jadwal</p>
                    <p class="sa-empty-text">Kelas {{ $kelasList->pluck('nama')->implode(', ') }} belum memiliki jadwal pada semester {{ $konteks->semesterNama() }}.</p>
                </div>
            </div>
        @elseif ($jadwalTampil->isEmpty())
            <div class="sa-card">
                <div class="sa-card-body sa-empty">
                    <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                    <p class="sa-empty-title">Tidak ada jadwal</p>
                    <p class="sa-empty-text">Tidak ada jadwal pada hari {{ $namaHari[(int) $filterHari] ?? '' }} untuk kelas Anda pada semester {{ $konteks->semesterNama() }}.</p>
                </div>
            </div>
        @else
            @php
                $jadwalHariIni = $jadwalPerHari->get((int) $hariIni);
            @endphp

            {{-- Ringkasan hari ini --}}
            <section class="jadwal-ringkasan mb-3 no-print" aria-label="Ringkasan jadwal hari ini">
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
                                        <span class="text-body-secondary">({{ $item->jamhari->kelas?->nama ?? '-' }} &middot; {{ substr((string) $item->jamhari->mulai, 0, 5) }}&ndash;{{ substr((string) $item->jamhari->selesai, 0, 5) }} &middot; {{ $item->guru?->nama ?? '-' }})</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="jadwal-ringkasan-detail text-body-secondary">Tidak ada jadwal pelajaran hari ini</div>
                        @endif
                    </div>
                </div>
            </section>

            @foreach ($jadwalTampil as $hari => $items)
                @php $isHariIni = (int) $hari === (int) $hariIni; @endphp
                <section class="sa-card jadwal-hari-card {{ $isHariIni ? 'jadwal-hari-ini' : '' }}" aria-label="Jadwal hari {{ $namaHari[$hari] ?? $hari }}">
                    <div class="sa-card-head">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <h2 class="sa-card-title"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i>{{ $namaHari[$hari] ?? 'Hari '.$hari }}</h2>
                            @if ($isHariIni)
                                <span class="sa-badge sa-hadir">Hari Ini</span>
                            @endif
                        </div>
                        <span class="sa-count">{{ $items->count() }} sesi</span>
                    </div>
                    <div class="card-body p-0">
                        @foreach ($items as $jadwal)
                            <div class="jadwal-sesi d-flex align-items-center gap-3 flex-wrap">
                                <div class="jadwal-waktu flex-shrink-0">
                                    <div class="jadwal-waktu-jam">{{ substr((string) $jadwal->jamhari->mulai, 0, 5) }}&ndash;{{ substr((string) $jadwal->jamhari->selesai, 0, 5) }}</div>
                                    @if ($jadwal->jamhari->jam_ke)
                                        <div class="jadwal-waktu-ke">Jam ke-{{ $jadwal->jamhari->jam_ke }}</div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="jadwal-mapel">{{ $jadwal->mapel?->nama ?? '-' }}</div>
                                    <div class="jadwal-kelas text-body-secondary"><i class="fa-solid fa-chalkboard-user me-1" aria-hidden="true"></i>{{ $jadwal->guru?->nama ?? '-' }}</div>
                                </div>
                                <span class="sa-chip"><i class="fa-solid fa-school" aria-hidden="true"></i>{{ $jadwal->jamhari->kelas?->nama ?? '-' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    @endif
</div>
