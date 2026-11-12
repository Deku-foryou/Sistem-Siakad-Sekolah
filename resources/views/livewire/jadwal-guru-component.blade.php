@section('title', 'Jadwal Mengajar')

<div class="container-fluid px-3 px-lg-4 py-3">
    @include('assets.akademik')

    {{-- Header halaman --}}
    <div class="sa-head">
        <div>
            <h1 class="sa-title"><i class="fa-solid fa-chalkboard-user me-2 text-success" aria-hidden="true"></i>Jadwal Mengajar</h1>
            @if ($konteks->valid())
                <p class="sa-sub">Tahun Ajaran {{ $konteks->tahunAjaranNama() }} &middot; Semester {{ $konteks->semesterNama() }}</p>
            @endif
        </div>
        <span class="sa-chip"><i class="fa-solid {{ $user?->level === 'superadmin' ? 'fa-user-shield' : 'fa-user-tie' }}" aria-hidden="true"></i>{{ $user?->level === 'superadmin' ? 'Superadmin' : 'Guru' }}</span>
    </div>

    @if (! $konteks->valid())
        <div class="alert alert-warning" role="alert">
            <div class="fw-semibold"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Konteks akademik belum valid</div>
            <div class="mt-1">Belum ada tahun ajaran/semester aktif atau semester tidak cocok dengan tahun ajaran. Jadwal belum dapat ditampilkan sampai konteks diperbaiki.</div>
        </div>
    @elseif ($user?->level === 'guru' && ! $guru)
        <div class="alert alert-warning" role="alert">Akun guru ini belum terhubung ke data guru. Hubungi administrator.</div>
    @else
        @if ($jadwalPerHari->isEmpty())
            <div class="sa-card">
                <div class="sa-card-body sa-empty">
                    <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                    <p class="sa-empty-title">Belum ada jadwal mengajar</p>
                    <p class="sa-empty-text">
                        @if ($user?->level === 'superadmin')
                            Belum ada jadwal yang terdaftar untuk semester aktif.
                        @else
                            Anda belum memiliki jadwal mengajar pada semester aktif.
                        @endif
                    </p>
                </div>
            </div>
        @else
            {{-- Ringkasan total --}}
            @php $totalSesi = $jadwalPerHari->sum(fn ($items) => $items->count()); @endphp
            <div class="sa-stats" aria-label="Ringkasan jadwal">
                <span class="sa-stat"><i class="fa-solid fa-calendar-days text-secondary" aria-hidden="true"></i>Hari mengajar <b>{{ $jadwalPerHari->count() }}</b></span>
                <span class="sa-stat"><i class="fa-solid fa-clock text-secondary" aria-hidden="true"></i>Total sesi <b>{{ $totalSesi }}</b></span>
            </div>

            @foreach ($jadwalPerHari as $hari => $items)
                <section class="sa-card mb-3" aria-label="Jadwal hari {{ $namaHari[$hari] ?? $hari }}">
                    <div class="sa-card-head">
                        <h2 class="sa-card-title"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i>{{ $namaHari[$hari] ?? 'Hari '.$hari }}</h2>
                        <span class="sa-count">{{ $items->count() }} sesi</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table sa-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Waktu</th>
                                    <th>Kelas</th>
                                    <th>Mata Pelajaran</th>
                                    @if ($user?->level === 'superadmin')
                                        <th>Guru</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $jadwal)
                                    <tr>
                                        <td class="sa-cell-muted text-nowrap">{{ substr((string) $jadwal->jamhari->mulai, 0, 5) }}&ndash;{{ substr((string) $jadwal->jamhari->selesai, 0, 5) }}</td>
                                        <td class="fw-semibold">{{ $jadwal->jamhari->kelas?->nama ?? '-' }}</td>
                                        <td>{{ $jadwal->mapel?->nama ?? '-' }}</td>
                                        @if ($user?->level === 'superadmin')
                                            <td>{{ $jadwal->guru?->nama ?? '-' }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        @endif
    @endif
</div>
