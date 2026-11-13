<div>@php
    $rolePanduan = [
        ['level' => 'superadmin', 'label' => 'Superadmin', 'desc' => 'Kelola sekolah secara menyeluruh', 'icon' => 'fa-user-shield', 'slug' => 'dashboard-utama'],
        ['level' => 'admin', 'label' => 'Admin', 'desc' => 'Operasional & antrian verifikasi wali', 'icon' => 'fa-user-gear', 'slug' => 'akun-wali-relasi'],
        ['level' => 'guru', 'label' => 'Guru', 'desc' => 'Mengajar, absensi, input nilai & rapor', 'icon' => 'fa-chalkboard-user', 'slug' => 'input-nilai'],
        ['level' => 'siswa', 'label' => 'Siswa', 'desc' => 'Jadwal, absensi, rapor & pengumuman', 'icon' => 'fa-user-graduate', 'slug' => 'rapor'],
        ['level' => 'wali', 'label' => 'Wali Murid', 'desc' => 'Pantau akademik & keuangan anak', 'icon' => 'fa-people-roof', 'slug' => 'wali-akademik-keuangan'],
    ];
@endphp

{{-- ============ TOGGLER mobile (khusus pusat dokumentasi) ============ --}}
<div class="container px-4 pt-4 d-block d-md-none docs-az-toggler">
    <button class="header-toggler d-flex justify-content-start align-items-center" type="button" wire:click="toggleSidebar">
        <i class="fa-solid fa-bars me-3"></i> Halo, {{ Auth::user()->name }}
    </button>
</div>

<div class="docs-az">

    {{-- ============ SIDEBAR (abu-abu muda, seperti desain lama) ============ --}}
    @if($sidebarTerbuka)
        <div class="docs-az-backdrop" wire:click="toggleSidebar" aria-hidden="true"></div>
    @endif

    <aside class="docs-az-sidebar {{ $sidebarTerbuka ? 'terbuka' : '' }}" id="sidebar" aria-label="Navigasi dokumentasi">
        <div class="padding_sidebar">
            <div class="sidebar-brand d-flex d-md-flex fw-medium justify-content-start px-lg-4 my-3">
                <div class="d-block">
                    <div class="nav-title fw-bold mx-2 text-black" style="font-size: 15px; line-height: 1.25;">
                        Dokumentasi<br>Aplikasi
                    </div>
                </div>
            </div>

            <a href="{{ \App\Support\BerandaUrl::untuk(auth()->user()) }}" class="btn btn-outline-secondary text-black mb-3 w-100"
               data-bs-toggle="tooltip" data-bs-placement="top" title="Kembali" style="font-size:.82rem">
                <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>

            <li class="nav-title fw-medium" style="margin-top: 7px; margin-left: 8px; font-size: 14px !important; font-weight: 700 !important;">
                Dokumentasi
            </li>

            <ul class="sidebar-nav p-2" data-coreui="navigation">
                @foreach($kategori as $slug => $meta)
                    @php
                        $daftar = $artikelPerKategori[$slug] ?? [];
                        $terbuka = in_array($slug, $expanded, true);
                    @endphp
                    <li class="nav-group {{ $terbuka ? 'show' : '' }}">
                        <button type="button" class="nav-link nav-group-toggle docs-az-cat {{ $terbuka ? 'show' : '' }}"
                                wire:click="toggleKategori('{{ $slug }}')" aria-expanded="{{ $terbuka ? 'true' : 'false' }}" aria-controls="cat-{{ $slug }}"
                                title="{{ $terbuka ? 'Tutup' : 'Buka' }} kategori {{ $meta['label'] }}">
                            <i class="fa-solid {{ $meta['icon'] }} me-2 docs-az-cat-icon" aria-hidden="true"></i>
                            <span class="docs-az-cat-label">{{ $meta['label'] }}</span>
                        </button>
                        @if($terbuka && count($daftar))
                            <ul class="nav-group-items" id="cat-{{ $slug }}">
                                @foreach($daftar as $a)
                                    <li class="nav-item">
                                        <button type="button"
                                                class="nav-link sidebar_items_text docs-az-link {{ $aktif && $aktif['slug'] === $a['slug'] ? 'active' : '' }}"
                                                aria-current="{{ $aktif && $aktif['slug'] === $a['slug'] ? 'page' : 'false' }}"
                                                wire:click="pilihArtikel('{{ $a['slug'] }}')">{{ $a['title'] }}</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>

            <p class="small text-secondary px-3 mt-2 mb-4" style="font-size:.7rem">
                <i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Khusus pengguna login.
            </p>
        </div>
    </aside>

    {{-- ============ KONTEN UTAMA (putih) ============ --}}
    <main class="docs-az-content">

        {{-- ===== MODE: HASIL PENCARIAN ===== --}}
        @if($q !== '')
            <nav aria-label="breadcrumb" class="mb-2"><ol class="breadcrumb"><li class="breadcrumb-item"><button type="button" class="btn btn-link p-0 border-0 align-baseline text-decoration-none" style="color:#0d6efd" wire:click="kembaliKeBeranda">Dokumentasi</button></li><li class="breadcrumb-item active" aria-current="page">Hasil pencarian</li></ol></nav>

            <h1 class="h3 fw-bold text-body-emphasis mb-1">Hasil pencarian: “{{ $q }}”</h1>
            <p class="text-secondary mb-2">{{ count($hasilCari) }} artikel cocok (judul, ringkasan, kategori &amp; kata kunci).</p>

            <form wire:submit="" role="search" class="mb-3">
                <label class="visually-hidden" for="q-cari">Cari artikel dokumentasi</label>
                <div class="input-group" style="max-width: 560px">
                    <input id="q-cari" type="search" class="form-control" wire:model.live.debounce.300ms="q" placeholder="Ketik kata kunci…" autocomplete="off">
                    <button class="btn btn-success" type="submit" aria-label="Cari"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </div>
            </form>

            @if(count($hasilCari))
                <div class="row g-3">
                    @foreach($hasilCari as $r)
                        <div class="col-12">
                            <button type="button" class="card border shadow-sm w-100 text-start docs-az-result px-3 py-2" wire:click="pilihArtikel('{{ $r['slug'] }}')">
                                <span class="small fw-bold text-uppercase" style="color:#198754; letter-spacing:.06em; font-size:.68rem">{{ $kategori[$r['cat']]['label'] ?? $r['cat'] }}</span>
                                <span class="fw-bold text-body-emphasis" style="font-size:.95rem">{{ $r['title'] }}</span>
                                <span class="text-secondary" style="font-size:.8rem">{{ $r['desc'] }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="card border border-dashed text-center py-5">
                    <div class="card-body">
                        <i class="fa-regular fa-face-frown-open fa-2x text-secondary mb-2" aria-hidden="true"></i>
                        <h2 class="h6 fw-bold mb-1">Tidak ada artikel yang cocok</h2>
                        <p class="text-secondary small mb-2">Coba kata kunci yang lebih umum, misalnya: <em>login</em>, <em>tagihan</em>, <em>rapor</em>, <em>jadwal</em>, <em>wali</em>, <em>KKTP</em>.</p>
                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="kembaliKeBeranda">Kembali ke beranda dokumentasi</button>
                    </div>
                </div>
            @endif

        {{-- ===== MODE: ARTIKEL ===== --}}
        @elseif($aktif)
            <nav aria-label="breadcrumb" class="docs-breadcrumb mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><button type="button" class="btn btn-link p-0 border-0 align-baseline text-decoration-none" style="color:#0d6efd" wire:click="kembaliKeBeranda">Dokumentasi</button></li>
                    <li class="breadcrumb-item">{{ $kategori[$aktif['cat']]['label'] ?? $aktif['cat'] }}</li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $aktif['title'] }}</li>
                </ol>
            </nav>

            <div class="row g-3">
                <div class="col-lg-9">
                    <article>
                        @include('docs.artikel.' . $aktif['slug'])
                    </article>

                    <p class="text-muted small mt-3 mb-2" style="font-size:.72rem">
                        <i class="fa-regular fa-clock me-1" aria-hidden="true"></i>±{{ $aktif['minutes'] }} menit baca
                        <span class="mx-2">·</span>Terakhir diperbarui {{ \Illuminate\Support\Carbon::parse($aktif['updated'])->translatedFormat('d F Y') }}
                    </p>

                    <div class="d-flex justify-content-between gap-2 mt-2 docs-prevnext">
                        @if($prevNext['prev'])
                            <button type="button" class="btn btn-outline-success text-start" wire:click="pilihArtikel('{{ $prevNext['prev']['slug'] }}')">
                                <span class="d-block text-secondary" style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em"><i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Artikel sebelumnya</span>
                                <span class="fw-semibold">{{ $prevNext['prev']['title'] }}</span>
                            </button>
                        @else
                            <span></span>
                        @endif
                        @if($prevNext['next'])
                            <button type="button" class="btn btn-outline-success text-end ms-auto" wire:click="pilihArtikel('{{ $prevNext['next']['slug'] }}')">
                                <span class="d-block text-secondary" style="font-size:.68rem; text-transform:uppercase; letter-spacing:.06em">Artikel berikutnya<i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i></span>
                                <span class="fw-semibold">{{ $prevNext['next']['title'] }}</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- TOC (kanan, seperti desain dokumentasi lama) --}}
                <aside class="col-lg-3 d-none d-xl-block docs-toc" aria-label="Daftar isi artikel">
                    <div class="card border shadow-sm">
                        <div class="card-body py-3">
                            <div class="small fw-bold text-uppercase text-secondary mb-2" style="letter-spacing:.1em; font-size:.68rem">
                                <i class="fa-solid fa-list-ul me-1" aria-hidden="true"></i>Daftar Isi
                            </div>
                            <nav class="docs-toc-nav" aria-label="Daftar isi">
                                @foreach($aktif['toc'] as $anchor => $label)
                                    <a class="docs-toc-link" href="#{{ $anchor }}">{{ $label }}</a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                </aside>
            </div>

        {{-- ===== MODE: BERANDA DOKUMENTASI ===== --}}
        @else
            {{-- Landing ala desain lama: judul + deskripsi + ilustrasi --}}
            <div class="row p-2 p-lg-4 pb-0 pe-lg-0 pt-lg-0 align-items-center mb-4">
                <div class="col-lg-7 p-3 p-lg-4 pt-lg-0">
                    <h1 class="display-5 fw-bold lh-1 text-body-emphasis mb-3">Dokumentasi<br>Aplikasi</h1>
                    <p class="lead text-secondary">Selamat datang di pusat dokumentasi resmi aplikasi ini. Di sini kamu dapat menemukan informasi yang komprehensif tentang berbagai <code>fitur</code>, <code>fungsi</code>, dan cara <code>penggunaan</code> aplikasi ini.
                        <br><br>
                        Dokumentasi ini bertujuan membantu pengguna memahami dan memanfaatkan aplikasi secara optimal — dari login pertama hingga pelaporan keuangan.
                        Ingin kembali ke halaman <a href="{{ \App\Support\BerandaUrl::untuk(auth()->user()) }}">dashboard</a>.
                    </p>

                    <form wire:submit="" role="search" class="docs-az-searchrow">
                        <label class="visually-hidden" for="q-hero">Cari artikel dokumentasi</label>
                        <input id="q-hero" type="search" class="form-control form-control-lg" wire:model.live.debounce.300ms="q" placeholder="Cari: login, tagihan, rapor, KKTP, wali…" autocomplete="off">
                        <button class="btn btn-success btn-lg" type="submit" aria-label="Cari"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                    </form>
                    <div class="small text-muted mt-2">
                        <i class="fa-solid fa-book me-1" aria-hidden="true"></i>{{ $jumlahArtikel }} artikel
                        <span class="mx-1">·</span><i class="fa-solid fa-layer-group me-1" aria-hidden="true"></i>{{ count($kategori) }} kategori
                        <span class="mx-1">·</span><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Khusus pengguna login
                    </div>
                </div>
                <div class="col-lg-4 offset-lg-1 p-0 overflow-hidden d-none d-lg-block">
                    <img class="rounded-lg-3" src="{{ asset('img/docs.png') }}" alt="Ilustrasi dokumentasi aplikasi" width="720">
                </div>
            </div>

            {{-- Kategori --}}
            <h2 class="h5 fw-bold text-body-emphasis border-bottom pb-2 mb-3"><i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>Kategori Dokumentasi</h2>
            <div class="row g-3 mb-4">
                @foreach($kategori as $slug => $meta)
                    @php $pertama = $artikelPerKategori[$slug][0] ?? null; @endphp
                    <div class="col-md-6 col-xl-4">
                        <button type="button" class="card border shadow-sm h-100 w-100 text-start docs-az-catcard" wire:click="{{ $pertama ? "pilihArtikel('{$pertama['slug']}')" : "toggleKategori('{$slug}')" }}">
                            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                                <span class="docs-az-cat-icon"><i class="fa-solid {{ $meta['icon'] }}" aria-hidden="true"></i></span>
                                <span>
                                    <span class="d-block fw-bold text-body-emphasis" style="font-size:.86rem; line-height:1.3">{{ $meta['label'] }}</span>
                                    <span class="d-block text-secondary" style="font-size:.72rem">{{ count($artikelPerKategori[$slug] ?? []) }} artikel</span>
                                </span>
                            </div>
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Panduan per peran --}}
            <h2 class="h5 fw-bold text-body-emphasis border-bottom pb-2 mb-3"><i class="fa-solid fa-route me-2" aria-hidden="true"></i>Panduan Berdasarkan Peran</h2>
            <div class="row g-3 mb-4">
                @foreach($rolePanduan as $rp)
                    @php $kartuTerlihat = isset($terlihat[$rp['slug']]); @endphp
                    <div class="col-6 col-md-4 col-xl-2">
                        <button type="button" class="card border shadow-sm h-100 w-100 text-center docs-az-rolecard {{ $kartuTerlihat ? '' : 'docs-az-rolecard-alt' }}"
                                wire:click="pilihArtikel('{{ $kartuTerlihat ? $rp['slug'] : 'peran' }}')"
                                title="{{ $kartuTerlihat ? 'Buka panduan '.$rp['label'] : 'Artikel khusus '.$rp['label'].' — baca ringkasan peran di sini' }}">
                            <div class="card-body py-3 px-2">
                                <span class="docs-az-rolecard-icon"><i class="fa-solid {{ $rp['icon'] }}" aria-hidden="true"></i></span>
                                <span class="d-block fw-bold text-body-emphasis" style="font-size:.8rem">{{ $rp['label'] }}</span>
                                <span class="d-none d-xl-block text-secondary mx-auto" style="font-size:.66rem; line-height:1.45; max-width: 22ch">{{ $rp['desc'] }}</span>
                            </div>
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Populer & terbaru --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h2 class="h5 fw-bold text-body-emphasis border-bottom pb-2 mb-2"><i class="fa-solid fa-fire me-2 text-warning" aria-hidden="true"></i>Artikel Populer</h2>
                    <div class="list-group list-group-flush border rounded">
                        @foreach($populer as $p)
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-baseline gap-2" wire:click="pilihArtikel('{{ $p['slug'] }}')">
                                <span class="fw-semibold text-body-emphasis" style="font-size:.86rem">{{ $p['title'] }}</span>
                                <span class="text-secondary small text-nowrap">{{ $kategori[$p['cat']]['label'] ?? '' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-6">
                    <h2 class="h5 fw-bold text-body-emphasis border-bottom pb-2 mb-2"><i class="fa-solid fa-clock-rotate-left me-2 text-secondary" aria-hidden="true"></i>Baru Diperbarui</h2>
                    <div class="list-group list-group-flush border rounded">
                        @foreach($terbaru as $t)
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-baseline gap-2" wire:click="pilihArtikel('{{ $t['slug'] }}')">
                                <span class="fw-semibold text-body-emphasis" style="font-size:.86rem">{{ $t['title'] }}</span>
                                <span class="text-secondary small text-nowrap">{{ \Illuminate\Support\Carbon::parse($t['updated'])->translatedFormat('d M Y') }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Akses cepat --}}
            <h2 class="h5 fw-bold text-body-emphasis border-bottom pb-2 mb-3"><i class="fa-solid fa-bolt me-2" aria-hidden="true"></i>Akses Cepat</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach([['slug'=>'tahun-ajaran-semester','label'=>'Tahun Ajaran & Semester','icon'=>'fa-calendar-days'],['slug'=>'input-nilai','label'=>'Input & Koreksi Nilai','icon'=>'fa-pen-ruler'],['slug'=>'troubleshooting','label'=>'Masalah Umum & Solusi','icon'=>'fa-screwdriver-wrench'],['slug'=>'pusat-bantuan','label'=>'Cara Melapor Masalah','icon'=>'fa-circle-question']] as $aksi)
                    <button type="button" class="btn btn-outline-success" wire:click="pilihArtikel('{{ $aksi['slug'] }}')">
                        <i class="fa-solid {{ $aksi['icon'] }} me-1" aria-hidden="true"></i>{{ $aksi['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </main>
</div>

{{-- ============ GAYA (tampilan lama: sidebar abu-abu, konten putih) ============ --}}
<style>
    .docs-az { display: flex; align-items: stretch; min-height: 100vh; background: #fff; }

    /* Sidebar abu-abu muda ala desain lama */
    .docs-az-sidebar {
        width: 288px; flex: 0 0 288px;
        background: #ececec;
        border-right: 1px solid #dedede;
        position: sticky; top: 0; height: 100vh; overflow-y: auto;
        padding: 4px 8px 16px;
    }
    .docs-az-sidebar .sidebar-brand { background: #ececec; }
    .docs-az-sidebar .nav-title { color: #272727 !important; font-weight: 800 !important; }
    .docs-az-sidebar .sidebar-nav { padding-left: 0; }

    .docs-az-cat {
        display: flex; align-items: center; gap: .45rem;
        width: 100%; text-align: left;
        background: transparent; border: 0;
        color: #272727; font-weight: 700; font-size: .86rem;
        padding: .42rem .55rem; border-radius: 10px;
    }
    .docs-az-cat, .docs-az-cat.show, .docs-az-cat:focus { color: #272727 !important; }
    .docs-az-cat:hover { background: #e0e0e0; color: #272727 !important; }
    .docs-az-cat.show { background: #e2e2e2; }
    .docs-az-cat::after { content: ""; margin-left: auto; border: .3em solid transparent; border-top-color: #495057; transition: transform .15s; }
    .docs-az-cat.show::after { transform: rotate(180deg); }
    .docs-az-cat-icon { color: #2778f1; width: 1.1rem; text-align: center; }
    .docs-az-cat-label { white-space: normal; overflow-wrap: break-word; line-height: 1.35; }

    .docs-az-sidebar .nav-group { border-radius: 12px; }
    .docs-az-sidebar .nav-group.show { background: transparent; }
    .docs-az-sidebar .nav-group-items { list-style: none; margin: .1rem 0 .2rem 1.6rem; padding: 0; border-left: 1px solid #d3d3d3; background: transparent; }
    .docs-az-link {
        display: block; width: 100%; text-align: left;
        background: transparent; border: 0;
        color: #2778f1 !important; font-size: .82rem; line-height: 1.4; font-weight: 500;
        padding: .34rem .65rem; border-radius: 0 10px 10px 0;
        text-decoration: none; border-left: 2px solid transparent; margin-left: -1px;
        white-space: normal !important; overflow: visible !important; text-overflow: clip !important; word-wrap: break-word;
    }
    .docs-az-sidebar .nav-link.docs-az-link {
        white-space: normal !important; overflow: visible !important; text-overflow: clip !important; word-wrap: break-word !important;
    }
    .docs-az-link:hover { background: #e0e0e0; text-decoration: none; color: #1b5fd0 !important; }
    .docs-az-link.active { background: #99b1ef4b; font-weight: 600; color: #292929 !important; border-radius: 10px; }

    /* Konten */
    .docs-az-content { flex: 1 1 auto; min-width: 0; padding: 1.25rem 1.5rem 2.5rem; background: #fff; }

    /* Kotak pencarian landing */
    .docs-az-searchrow { display: flex; max-width: 560px; margin-top: .5rem; }
    .docs-az-searchrow input { border-radius: .375rem 0 0 .375rem; }
    .docs-az-searchrow button { border-radius: 0 .375rem .375rem 0; }

    /* Kartu kategori */
    .docs-az-catcard { transition: box-shadow .15s ease, transform .15s ease; }
    .docs-az-catcard:hover { box-shadow: 0 .35rem .8rem rgba(0,0,0,.12) !important; transform: translateY(-2px); }
    .docs-az-cat-icon { width: 38px; height: 38px; border-radius: 10px; background: #e8f0fe; color: #2778f1; display: grid; place-items: center; font-size: .95rem; flex: 0 0 auto; }

    /* Kartu panduan per peran */
    .docs-az-rolecard { transition: box-shadow .15s ease, transform .15s ease; }
    .docs-az-rolecard:hover { box-shadow: 0 .35rem .8rem rgba(0,0,0,.12) !important; transform: translateY(-2px); }
    .docs-az-rolecard-icon { font-size: 1.5rem; color: #198754; display: block; margin-bottom: .25rem; }
    .docs-az-rolecard-alt { border-style: dashed !important; opacity: .8; }

    /* Hasil pencarian */
    .docs-az-result { cursor: pointer; transition: box-shadow .15s ease, transform .15s ease; }
    .docs-az-result:hover { box-shadow: 0 .35rem .8rem rgba(0,0,0,.12) !important; transform: translateY(-2px); }

    /* TOC kanan */
    .docs-toc { position: sticky; top: 1rem; align-self: start; }
    .docs-toc-nav { display: flex; flex-direction: column; }
    .docs-toc-link { font-size: .8rem; color: #495057; text-decoration: none; padding: .22rem .4rem; border-radius: .25rem; line-height: 1.4; }
    .docs-toc-link:hover { background: #f1f3f5; color: #0d6efd; }

    .docs-prevnext .btn { max-width: 48%; }

    /* Backdrop mobile */
    .docs-az-backdrop { position: fixed; inset: 0; z-index: 1035; background: rgba(0,0,0,.45); }

    @media (max-width: 991.98px) {
        .docs-az { display: block; }
        .docs-az-sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; z-index: 1045;
            width: 84vw; max-width: 320px; height: 100vh;
            transform: translateX(-105%); transition: transform .25s ease;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.25);
        }
        .docs-az-sidebar.terbuka { transform: translateX(0); }
        .docs-az-content { padding: 1rem .9rem 2rem; }
        .docs-prevnext .btn { max-width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>

</div>