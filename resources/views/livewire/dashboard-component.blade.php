@section('title', 'Dashboard')

@php
    $jam = (int) $waktu->format('H');
    $greet = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    $peran = $isSuperadmin ? 'Administrator' : 'Admin Sekolah';
@endphp

<div class="container-fluid px-3 py-3 sd-page">
    @include('assets.akademik')

    <style>
        /* ============ Dashboard Admin & Superadmin — Enterprise-grade minimal ============ */
        .sd-page { --sd-teal: var(--sa-green); --sd-teal-dark: var(--sa-green-dark); --sd-faint: var(--sa-green-faint); --sd-ink: var(--sa-ink); --sd-muted: var(--sa-muted); --sd-border: var(--sa-border); --sd-shadow: 0 1px 2px rgba(16,24,40,.05); }

        /* ---------- Header (kompak) ---------- */
        .sd-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .35rem 1.5rem; margin-bottom: .75rem; }
        .sd-greet { font-size: 1.05rem; font-weight: 800; letter-spacing: -.01em; color: var(--sd-ink); margin: 0; line-height: 1.25; }
        .sd-head-sub { font-size: .75rem; color: var(--sd-muted); margin: .1rem 0 0; }
        .sd-head-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
        .sd-bell { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 1.9rem; height: 1.9rem; border-radius: 999px; background: #fff; border: 1px solid var(--sd-border); color: var(--sd-muted); text-decoration: none; }
        .sd-bell:hover { color: var(--sd-teal-dark); border-color: var(--sd-teal); }
        .sd-bell-badge { position: absolute; top: -.28rem; right: -.28rem; background: #dc2626; color: #fff; font-size: .6rem; font-weight: 800; border-radius: 999px; min-width: 1rem; height: 1rem; display: inline-flex; align-items: center; justify-content: center; padding: 0 .26rem; line-height: 1; }

        /* ---------- KPI (4 kartu seragam) ---------- */
        .sd-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin-bottom: .75rem; }
        .sd-kpi { display: flex; align-items: center; gap: .6rem; background: #fff; border: 1px solid var(--sd-border); border-radius: var(--sa-radius); box-shadow: var(--sd-shadow); padding: .55rem .75rem; text-decoration: none; color: inherit; transition: border-color .15s; }
        .sd-kpi:hover { border-color: var(--sd-teal); }
        .sd-kpi-icon { width: 2.1rem; height: 2.1rem; border-radius: .5rem; background: var(--sd-faint); color: var(--sd-teal-dark); display: inline-flex; align-items: center; justify-content: center; font-size: .95rem; flex: 0 0 auto; }
        .sd-kpi-value { display: block; font-size: 1.15rem; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; color: var(--sd-ink); }
        .sd-kpi-label { display: block; font-size: .62rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--sd-muted); }

        /* ---------- Panel generik ---------- */
        .sd-panel { height: 100%; }
        .sd-panel .sa-card-head { padding: .5rem .85rem; background: #fff; border-bottom: 1px solid var(--sd-border); }
        .sd-panel .sa-card-title { font-size: .78rem; font-weight: 800; color: var(--sd-ink); letter-spacing: .02em; }
        .sd-panel .sa-card-title i { color: var(--sd-teal); }
        .sd-panel .sa-card-body { padding: .55rem .85rem .75rem; }
        .sd-head-link { font-size: .72rem; font-weight: 700; color: var(--sd-teal-dark); text-decoration: none; display: inline-flex; align-items: center; gap: .3rem; }
        .sd-head-link:hover { text-decoration: underline; }
        .sd-count-badge { background: var(--sd-teal-dark); color: #fff; font-size: .66rem; font-weight: 800; border-radius: 999px; padding: .12rem .5rem; }

        /* ---------- Butuh Perhatian ---------- */
        .sd-todo-item { display: flex; align-items: center; gap: .55rem; padding: .3rem .35rem; border-bottom: 1px solid #eef0f3; border-radius: .4rem; text-decoration: none; color: inherit; }
        .sd-todo-item:last-child { border-bottom: 0; }
        .sd-todo-item:hover { background: #f7f8fa; }
        .sd-todo-icon { width: 1.6rem; height: 1.6rem; border-radius: .4rem; background: var(--sd-faint); color: var(--sd-teal-dark); display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex: 0 0 auto; }
        .sd-todo-text { flex: 1; min-width: 0; font-size: .78rem; font-weight: 600; line-height: 1.3; }
        .sd-todo-text small { display: block; font-size: .65rem; color: var(--sd-muted); font-weight: 500; }
        .sd-todo-count { font-weight: 800; font-variant-numeric: tabular-nums; font-size: .88rem; color: var(--sd-ink); }
        .sd-todo-empty { text-align: center; padding: .9rem .5rem; }
        .sd-todo-empty i { font-size: 1.3rem; color: #10b981; }
        .sd-todo-empty b { display: block; font-size: .8rem; color: var(--sd-ink); margin: .15rem 0 0; }
        .sd-todo-empty small { font-size: .68rem; color: var(--sd-muted); }

        /* ---------- Ringkasan Keuangan ---------- */
        .sd-fin-item { display: flex; justify-content: space-between; align-items: baseline; gap: .75rem; padding: .42rem .1rem; border-bottom: 1px dashed #eef0f3; }
        .sd-fin-item:last-child { border-bottom: 0; }
        .sd-fin-label { font-size: .72rem; color: var(--sd-muted); font-weight: 600; }
        .sd-fin-value { font-size: .82rem; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--sd-ink); white-space: nowrap; }
        .sd-fin-ok { color: #0b6e4f; }
        .sd-fin-danger { color: #b91c1c; }
        .sd-fin-note { font-size: .64rem; color: var(--sd-muted); margin: .4rem 0 0; }

        /* ---------- Donut charts ---------- */
        .sd-donut-wrap { display: flex; align-items: center; gap: .75rem; }
        .sd-donut-chart { width: 120px; height: 120px; flex: 0 0 120px; position: relative; }
        .sd-donut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; }
        .sd-donut-center b { display: block; font-size: 1.05rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; color: var(--sd-ink); }
        .sd-donut-center span { display: block; font-size: .52rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--sd-muted); margin-top: .15rem; }
        .sd-legend { list-style: none; margin: 0; padding: 0; flex: 1; min-width: 0; }
        .sd-legend li { display: flex; align-items: center; gap: .4rem; font-size: .7rem; padding: .17rem 0; }
        .sd-dot { width: .5rem; height: .5rem; border-radius: 999px; flex: 0 0 auto; }
        .sd-legend-val { margin-left: auto; font-weight: 700; color: var(--sd-ink); font-variant-numeric: tabular-nums; }
        .sd-legend-pct { width: 2.2rem; text-align: right; color: var(--sd-muted); font-size: .65rem; font-variant-numeric: tabular-nums; }

        /* ---------- Aktivitas & Pengumuman ---------- */
        .sd-activity { list-style: none; margin: 0; padding: 0; }
        .sd-activity li { display: flex; align-items: flex-start; gap: .5rem; padding: .3rem 0; border-bottom: 1px dashed #eef0f3; font-size: .75rem; }
        .sd-activity li:last-child { border-bottom: 0; }
        .sd-act-icon { width: 1.45rem; height: 1.45rem; border-radius: .35rem; background: #f3f4f6; color: #4b5563; display: inline-flex; align-items: center; justify-content: center; font-size: .66rem; flex: 0 0 auto; margin-top: .1rem; }
        .sd-act-main { flex: 1; min-width: 0; line-height: 1.35; }
        .sd-act-sub { color: var(--sd-muted); font-size: .64rem; }

        .sd-pa-item { display: flex; gap: .5rem; align-items: flex-start; padding: .3rem .2rem; text-decoration: none; color: inherit; border-radius: .4rem; border-bottom: 1px solid #eef0f3; }
        .sd-pa-item:last-child { border-bottom: 0; }
        .sd-pa-item:hover { background: #f7f8fa; }
        .sd-pa-badge { flex: 0 0 auto; font-size: .58rem; font-weight: 800; padding: .18rem .45rem; border-radius: 999px; letter-spacing: .04em; margin-top: .1rem; }
        .sd-pa-badge.terbit { background: #e7f6ec; color: #0b6e4f; }
        .sd-pa-badge.draf { background: #eef0f3; color: #6b7280; }
        .sd-pa-badge.arsip { background: #fdecec; color: #b91c1c; }
        .sd-pa-title { font-size: .75rem; font-weight: 700; line-height: 1.3; }
        .sd-pa-meta { font-size: .62rem; color: var(--sd-muted); margin-top: .08rem; }

        /* ---------- Aksi cepat ---------- */
        .sd-quick { display: flex; flex-wrap: wrap; gap: .4rem; }

        @media (max-width: 1199.98px) {
            .sd-kpis { grid-template-columns: repeat(4, 1fr); gap: .5rem; }
        }
        @media (max-width: 991.98px) {
            .sd-kpis { grid-template-columns: repeat(2, 1fr); }
            .sd-donut-wrap { flex-wrap: wrap; justify-content: center; }
            .sd-legend { flex: 1 0 100%; }
        }
        @media (max-width: 575.98px) {
            .sd-greet { font-size: .92rem; }
            .sd-kpis { grid-template-columns: repeat(2, 1fr); gap: .4rem; }
            .sd-kpi { padding: .45rem .55rem; gap: .45rem; }
            .sd-kpi-value { font-size: 1rem; }
            .sd-donut-chart { width: 110px; height: 110px; flex-basis: 110px; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>

    {{-- ========== 1. HEADER ========== --}}
    <div class="sd-head">
        <div>
            <h1 class="sd-greet">{{ $greet }}, {{ $pengguna?->name }}</h1>
            <p class="sd-head-sub">{{ $peran }} · {{ $waktu->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="sd-head-meta">
            <span class="sa-chip"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>Tahun Ajaran <b>{{ $konteks->tahunAjaranNama() ?: '—' }}</b></span>
            <span class="sa-chip"><i class="fa-regular fa-calendar" aria-hidden="true"></i>Semester <b>{{ $konteks->semesterNama() ?: '—' }}</b></span>
            @if ($notifikasiUnread > 0)
                <a href="/notifikasi" class="sd-bell" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $notifikasiUnread }} notifikasi belum dibaca" aria-label="{{ $notifikasiUnread }} notifikasi belum dibaca">
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    <span class="sd-bell-badge">{{ $notifikasiUnread }}</span>
                </a>
            @endif
        </div>
    </div>

    @if ($konteks->punyaPeringatan())
        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-2" role="alert">
            <i class="fa-solid fa-triangle-exclamation mt-1" aria-hidden="true"></i>
            <div class="small">
                <strong>Konteks akademik belum konsisten.</strong>
                <ul class="mb-1 ps-3">
                    @foreach ($konteks->pesanPeringatan() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
                <a href="/tahunajaran" class="alert-link">Kelola Tahun Ajaran →</a>
            </div>
        </div>
    @endif

    {{-- ========== 2. KPI ========== --}}
    <div class="sd-kpis">
        <a href="/siswa" class="sd-kpi" data-bs-toggle="tooltip" data-bs-placement="top" title="Kelola siswa">
            <span class="sd-kpi-icon"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
            <span><span class="sd-kpi-value">{{ number_format($statistik['siswa_aktif'], 0, ',', '.') }}</span><span class="sd-kpi-label">Siswa Aktif</span></span>
        </a>
        <a href="/guru" class="sd-kpi" data-bs-toggle="tooltip" data-bs-placement="top" title="Kelola guru">
            <span class="sd-kpi-icon"><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i></span>
            <span><span class="sd-kpi-value">{{ number_format($statistik['guru_aktif'], 0, ',', '.') }}</span><span class="sd-kpi-label">Guru Aktif</span></span>
        </a>
        <a href="/kelas" class="sd-kpi" data-bs-toggle="tooltip" data-bs-placement="top" title="Kelola kelas">
            <span class="sd-kpi-icon"><i class="fa-solid fa-school" aria-hidden="true"></i></span>
            <span><span class="sd-kpi-value">{{ $statistik['kelas'] !== null ? number_format($statistik['kelas'], 0, ',', '.') : '—' }}</span><span class="sd-kpi-label">Kelas Aktif</span></span>
        </a>
        <a href="/mapel" class="sd-kpi" data-bs-toggle="tooltip" data-bs-placement="top" title="Kelola mata pelajaran">
            <span class="sd-kpi-icon"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
            <span><span class="sd-kpi-value">{{ number_format($statistik['mapel'], 0, ',', '.') }}</span><span class="sd-kpi-label">Mata Pelajaran</span></span>
        </a>
    </div>

    {{-- ========== 3. OPERASIONAL + KEUANGAN ========== --}}
    <div class="row g-2 mb-2">
        <div class="col-12 col-lg-6">
            <section class="sa-card sd-panel" aria-label="Butuh perhatian">
                <div class="sa-card-head">
                    <h3 class="sa-card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i>Butuh Perhatian</h3>
                    @if (count($antrean) > 0)
                        <span class="sd-count-badge">{{ array_sum(array_column($antrean, 'count')) }}</span>
                    @endif
                </div>
                <div class="sa-card-body">
                    @forelse ($antrean as $item)
                        <a href="{{ $item['url'] }}" class="sd-todo-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Buka {{ $item['label'] }}">
                            <span class="sd-todo-icon"><i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i></span>
                            <span class="sd-todo-text">{{ $item['label'] }}<small>Menunggu tindakan</small></span>
                            <span class="sd-todo-count">{{ $item['count'] }}</span>
                        </a>
                    @empty
                        <div class="sd-todo-empty">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            <b>Semua beres</b>
                            <small>Tidak ada pengajuan menunggu tindakan Anda.</small>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-6">
            @if ($lihatKeuangan && $keuangan)
                <section class="sa-card sd-panel" aria-label="Ringkasan keuangan">
                    <div class="sa-card-head">
                        <h3 class="sa-card-title"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>Ringkasan Keuangan</h3>
                        <a href="/dashboardkeuangan" class="sd-head-link" data-bs-toggle="tooltip" data-bs-placement="top" title="Buka dashboard keuangan">Detail <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="sa-card-body">
                        <div class="sd-fin-item">
                            <span class="sd-fin-label">Pembayaran masuk bulan ini</span>
                            <span class="sd-fin-value sd-fin-ok">Rp {{ number_format($keuangan['terbayar_bulan_ini'], 0, ',', '.') }}</span>
                        </div>
                        <div class="sd-fin-item">
                            <span class="sd-fin-label">Tagihan terbuka</span>
                            <span class="sd-fin-value">{{ $keuangan['open_count'] }} tagihan · Rp {{ number_format($keuangan['open_total'], 0, ',', '.') }}</span>
                        </div>
                        <div class="sd-fin-item">
                            <span class="sd-fin-label">Total tunggakan</span>
                            <span class="sd-fin-value sd-fin-danger">Rp {{ number_format($keuangan['tunggakan'], 0, ',', '.') }}</span>
                        </div>
                        <p class="sd-fin-note">Tagihan terbuka = belum dibayar + dibayar sebagian · tunggakan = sisa belum dialokasikan.</p>
                    </div>
                </section>
            @else
                <section class="sa-card sd-panel" aria-label="Ringkasan keuangan">
                    <div class="sa-card-head"><h3 class="sa-card-title"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>Ringkasan Keuangan</h3></div>
                    <div class="sa-card-body"><div class="sd-todo-empty"><i class="fa-solid fa-lock text-secondary" aria-hidden="true"></i><b>Tidak dapat diakses</b><small>Ringkasan keuangan hanya untuk peran yang berhak.</small></div></div>
                </section>
            @endif
        </div>
    </div>

    {{-- ========== 4. VISUALISASI ========== --}}
    <div class="row g-2 mb-2">
        {{-- Komposisi Pengguna --}}
        <div class="col-12 col-lg-6">
            <section class="sa-card sd-panel" aria-label="Komposisi pengguna">
                <div class="sa-card-head">
                    <h3 class="sa-card-title"><i class="fa-solid fa-user-group" aria-hidden="true"></i>Komposisi Pengguna</h3>
                    <span class="sa-count">{{ number_format($komposisiPengguna['total'], 0, ',', '.') }} akun</span>
                </div>
                <div class="sa-card-body">
                    @if ($komposisiPengguna['total'] > 0)
                        @php
                            $penggunaCfg = [
                                'siswa' => ['Siswa', '#0e7d6a'],
                                'guru' => ['Guru', '#2563eb'],
                                'wali' => ['Wali', '#d97706'],
                                'admin' => ['Admin / Superadmin', '#64748b'],
                            ];
                            $penggunaTotal = max(1, $komposisiPengguna['total']);
                        @endphp
                        <div class="sd-donut-wrap">
                            <div class="sd-donut-chart">
                                <div id="sd-donut-pengguna" style="width:120px;height:120px"></div>
                                <div class="sd-donut-center"><b>{{ number_format($komposisiPengguna['total'], 0, ',', '.') }}</b><span>Akun</span></div>
                            </div>
                            <ul class="sd-legend">
                                @foreach ($penggunaCfg as $k => [$label, $color])
                                    <li><span class="sd-dot" style="background: {{ $color }}"></span>{{ $label }}<span class="sd-legend-val">{{ number_format($komposisiPengguna[$k], 0, ',', '.') }}</span><span class="sd-legend-pct">{{ number_format($komposisiPengguna[$k] / $penggunaTotal * 100, 0) }}%</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="sd-todo-empty"><i class="fa-solid fa-users-slash" aria-hidden="true"></i><b>Belum ada data pengguna</b><small>Komposisi tampil setelah akun terdaftar.</small></div>
                    @endif
                </div>
            </section>
        </div>

        {{-- Status Tagihan --}}
        <div class="col-12 col-lg-6">
            <section class="sa-card sd-panel" aria-label="Status tagihan">
                <div class="sa-card-head">
                    <h3 class="sa-card-title"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>Status Tagihan</h3>
                    <span class="sa-count">{{ number_format($statusTagihan['total'], 0, ',', '.') }} aktif</span>
                </div>
                <div class="sa-card-body">
                    @if ($statusTagihan['total'] > 0)
                        @php
                            $tagCfg = [
                                'lunas' => ['Lunas', '#0e7d6a'],
                                'dibayar_sebagian' => ['Dibayar Sebagian', '#d97706'],
                                'belum_dibayar' => ['Belum Dibayar', '#dc2626'],
                                'dibebaskan' => ['Dibebaskan', '#64748b'],
                            ];
                            $tagTotal = max(1, $statusTagihan['total']);
                        @endphp
                        <div class="sd-donut-wrap">
                            <div class="sd-donut-chart">
                                <div id="sd-donut-tagihan" style="width:120px;height:120px"></div>
                                <div class="sd-donut-center"><b>{{ number_format($statusTagihan['total'], 0, ',', '.') }}</b><span>Tagihan</span></div>
                            </div>
                            <ul class="sd-legend">
                                @foreach ($tagCfg as $k => [$label, $color])
                                    <li><span class="sd-dot" style="background: {{ $color }}"></span>{{ $label }}<span class="sd-legend-val">{{ number_format($statusTagihan[$k], 0, ',', '.') }}</span><span class="sd-legend-pct">{{ number_format($statusTagihan[$k] / $tagTotal * 100, 0) }}%</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="sd-todo-empty"><i class="fa-solid fa-file-circle-xmark" aria-hidden="true"></i><b>Belum ada tagihan</b><small>Status tagihan tampil setelah periode tagihan diterbitkan.</small></div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- ========== 5. AKTIVITAS & PENGUMUMAN ========== --}}
    <div class="row g-2 mb-2">
        @if ($isSuperadmin)
            <div class="col-12 col-lg-6">
                <section class="sa-card sd-panel" aria-label="Aktivitas terbaru">
                    <div class="sa-card-head">
                        <h3 class="sa-card-title"><i class="fa-solid fa-bolt" aria-hidden="true"></i>Aktivitas Terbaru</h3>
                        <span class="sa-count">{{ count($aktivitas) }} terakhir</span>
                    </div>
                    <div class="sa-card-body">
                        @if (count($aktivitas) > 0)
                            <ul class="sd-activity">
                                @foreach ($aktivitas as $a)
                                    <li>
                                        <span class="sd-act-icon"><i class="fa-solid {{ $a['icon'] }}" aria-hidden="true"></i></span>
                                        <span class="sd-act-main"><strong>{{ $a['actor'] }}</strong> — {{ $a['subject'] }} {{ $a['verb'] }}<br><span class="sd-act-sub">{{ $a['at'] }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="sd-todo-empty"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><b>Belum ada aktivitas terekam</b><small>Catatan tampil setelah ada perubahan data.</small></div>
                        @endif
                    </div>
                </section>
            </div>
        @endif

        <div class="{{ $isSuperadmin ? 'col-12 col-lg-6' : 'col-12' }}">
            <section class="sa-card sd-panel" aria-label="Pengumuman terbaru">
                <div class="sa-card-head">
                    <h3 class="sa-card-title"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i>Pengumuman Terbaru</h3>
                    @if ($isSuperadmin)
                        <a href="/pengumuman" class="sd-head-link" data-bs-toggle="tooltip" data-bs-placement="top" title="Kelola pengumuman">Semua <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
                <div class="sa-card-body">
                    @if (count($pengumuman) > 0)
                        @foreach ($pengumuman as $p)
                            @php $badge = match ($p['status']) { 'terbit' => 'terbit', 'draf' => 'draf', default => 'arsip' }; @endphp
                            <div class="sd-pa-item">
                                <span class="sd-pa-badge {{ $badge }}">{{ strtoupper($p['status']) }}</span>
                                <span class="flex-grow-1">
                                    <span class="sd-pa-title">{{ $p['judul'] }}</span>
                                    <span class="sd-pa-meta d-block">{{ \Illuminate\Support\Str::limit($p['ringkasan'], 90) }} · {{ \Carbon\Carbon::parse($p['at'])->locale('id')->translatedFormat('d M Y') }}</span>
                                </span>
                            </div>
                        @endforeach
                    @else
                        <div class="sd-todo-empty"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i><b>Belum ada pengumuman</b><small>Pengumuman tampil setelah diterbitkan.</small></div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- ========== 6. AKSI CEPAT ========== --}}
    @if (count($aksiCepat) > 0)
        <section class="sa-card sd-panel" aria-label="Aksi cepat">
            <div class="sa-card-head">
                <h3 class="sa-card-title"><i class="fa-solid fa-bolt" aria-hidden="true"></i>Aksi Cepat</h3>
            </div>
            <div class="sa-card-body">
                <div class="sd-quick">
                    @foreach ($aksiCepat as $a)
                        <a href="{{ $a['url'] }}" class="btn btn-outline-success btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $a['label'] }}">
                            <i class="fa-solid {{ $a['icon'] }} me-1" aria-hidden="true"></i>{{ $a['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @script
    <script>
        (function () {
            const pengguna = @js($komposisiPengguna);
            const tagihan = @js($statusTagihan);

            const render = () => {
                if (typeof Highcharts === 'undefined') return;

                const donut = (id, title, data, unit) => {
                    const el = document.getElementById(id);
                    if (!el || el.__rendered) return;
                    el.__rendered = true;
                    Highcharts.chart(id, {
                        chart: { type: 'pie', height: 120, width: 120, backgroundColor: 'transparent', margin: 0, spacing: [0, 0, 0, 0] },
                        title: { text: null }, exporting: { enabled: false }, navigation: { buttonOptions: { enabled: false } },
                        tooltip: { pointFormat: '<b>{point.percentage:.0f}%</b> ({y} ' + unit + ')' },
                        legend: { enabled: false },
                        credits: { enabled: false },
                        series: [{
                            name: title,
                            dataLabels: { enabled: false },
                            innerSize: '64%',
                            data: data,
                        }],
                    });
                };

                if (pengguna.total > 0) {
                    donut('sd-donut-pengguna', 'Pengguna', [
                        { name: 'Siswa', y: pengguna.siswa, color: '#0e7d6a' },
                        { name: 'Guru', y: pengguna.guru, color: '#2563eb' },
                        { name: 'Wali', y: pengguna.wali, color: '#d97706' },
                        { name: 'Admin / Superadmin', y: pengguna.admin, color: '#64748b' },
                    ], 'akun');
                }
                if (tagihan.total > 0) {
                    donut('sd-donut-tagihan', 'Tagihan', [
                        { name: 'Lunas', y: tagihan.lunas, color: '#0e7d6a' },
                        { name: 'Dibayar Sebagian', y: tagihan.dibayar_sebagian, color: '#d97706' },
                        { name: 'Belum Dibayar', y: tagihan.belum_dibayar, color: '#dc2626' },
                        { name: 'Dibebaskan', y: tagihan.dibebaskan, color: '#64748b' },
                    ], 'tagihan');
                }
            };

            document.addEventListener('DOMContentLoaded', render);
            if (window.Livewire) {
                try { Livewire.hook('morphed', render); } catch (e) {}
            }
            render();
        })();
    </script>
    @endscript
</div>
