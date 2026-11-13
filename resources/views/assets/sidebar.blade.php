<style>
    .sidebar.bg-white .nav-link {
        color: #6b7280;
        font-weight: 500;
        border-radius: 8px;
        margin: 4px 12px;
        padding: 10px 16px;
    }

    .sidebar.bg-white .nav-link i {
        color: #6b7280 !important;
    }

    .sidebar.bg-white .nav-link:hover {
        background-color: #f9fafb;
        color: #111827;
    }

    .sidebar.bg-white .nav-link.active,
    .sidebar.bg-white .nav-item.active .nav-link {
        background-color: #e8fbee !important;
        color: #0e7d6a !important;
    }

    .sidebar.bg-white .nav-link.active i,
    .sidebar.bg-white .nav-item.active .nav-link i {
        color: #0e7d6a !important;
    }

    .sidebar.bg-white .nav-title {
        color: #0E7D6A;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 1.5rem;
        padding-left: 20px;
    }

    .padding_sidebar {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-height: 0;
    }

    .sidebar-menu {
        flex: 1;
        min-height: 0;
    }

    .icon_sidebar_fa {
        display: inline-flex;
        flex: 0 0 16px;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 17px;
        font-size: 17px;
        line-height: 17px;
    }

    /* ========== Accordion kelompok menu ========== */
    .docs-group {
        margin: 6px 4px 2px;
    }

    .docs-group-toggle {
        display: flex;
        align-items: center;
        width: 100%;
        background: transparent;
        border: 0;
        border-radius: 8px;
        padding: 10px 16px;
        margin: 0;
        color: #6b7280;
        font-weight: 500;
        font-size: .875rem;
        text-align: left;
        transition: background-color .15s ease, color .15s ease;
    }

    .docs-group-toggle:hover {
        background-color: #f9fafb;
        color: #111827;
    }

    .docs-group-toggle .docs-group-chevron {
        margin-left: auto;
        font-size: .72rem;
        transition: transform .2s ease;
        color: #9ca3af;
    }

    .docs-group.open .docs-group-chevron {
        transform: rotate(180deg);
    }

    .docs-group-toggle.has-active {
        color: #0e7d6a;
        font-weight: 700;
    }

    .docs-group-toggle.has-active i {
        color: #0e7d6a !important;
    }

    .docs-group-title {
        flex: 1;
        white-space: normal;
        overflow-wrap: break-word;
        text-align: left;
        line-height: 1.35;
    }

    .docs-group-badge {
        display: inline-flex;
        align-items: center;
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
        border-radius: 999px;
        padding: .12rem .42rem;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        margin-left: .4rem;
        flex: 0 0 auto;
    }

    .docs-group-items {
        list-style: none;
        margin: 2px 0 0 1.1rem;
        padding: 0;
        border-left: 1px solid #eef0f3;
        display: none;
    }

    .docs-group.open .docs-group-items {
        display: block;
    }

    .docs-group-items .nav-link {
        padding: 8px 14px;
        margin: 2px 6px 2px 10px;
        font-size: .855rem;
    }

    .sa-sidebar-scroll {
        scrollbar-width: thin;
        scrollbar-color: #d1d5db transparent;
    }

    @media (prefers-reduced-motion: reduce) {
        .docs-group-toggle, .docs-group-chevron { transition: none !important; }
    }
</style>

<div class="sidebar sidebar-fixed bg-white border-end" id="sidebar" style="border-right: 1px solid #f3f4f6;">
    <div class="padding_sidebar">
        <div class="sidebar-brand d-none d-md-flex justify-content-start px-lg-4 my-3 bg-white">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('ubhi.webp') }}" alt="SIAKAD Template" width="42" height="42" style="object-fit: contain; width:auto; height:42px; flex:0 0 auto;">
                <span class="lh-sm" style="white-space: nowrap;">
                    <span style="display:block; font-weight:800; font-size:1.05rem; color:#0b5f52; letter-spacing:.02em;">SIAKAD</span>
                    <span style="display:block; font-size:.68rem; font-weight:700; color:#6b7280; letter-spacing:.09em; text-transform:uppercase;">Template</span>
                </span>
            </div>
        </div>

        <div class="sidebar-menu sa-sidebar-scroll" data-simplebar>
        @php
            /* ===== Sumber menu & aturan tampilan — TIDAK mengubah authorization =====
               - Menu tetap berasal dari usermenu (di-join appmenunew, diurutkan urut),
                 dibaca oleh view composer [AppServiceProvider] -> $appMenu.
               - Sembunyikan menu tertentu untuk superadmin (perilaku yang sudah ada).
            */
            $level = Auth::user()?->level;
            $menuDisembunyikanSuperadmin = ['berandaguru', 'berandasiswa', 'jadwalsiswa', 'absensi-siswa', 'profil-siswa', 'pengumuman-siswa', 'jadwal', 'pengampu', 'inputnilai', 'raporsiswa', 'notifikasi'];
            $sembunyikanMenu = $level === 'superadmin';

            $ikonFallback = 'fa-regular fa-folder-open';
            $resolveIcon = function ($icon) use ($ikonFallback) {
                $icon = trim((string) $icon);
                if ($icon !== '' && (str_starts_with($icon, 'fa-') || str_starts_with($icon, 'fa '))) {
                    return $icon;
                }
                if ($icon !== '' && str_ends_with(strtolower($icon), '.svg')) {
                    return null; // SVG file icon
                }
                return $ikonFallback;
            };

            $rows = [];
            foreach ($appMenu as $datas) {
                $m = $datas->appmenunew;
                if (! $m) {
                    continue;
                }
                if ($m->url === 'semester') { // menu lama, redirect ke tahunajaran
                    continue;
                }
                if ($sembunyikanMenu && in_array($m->url, $menuDisembunyikanSuperadmin, true)) {
                    continue;
                }
                $rows[$m->url] = $datas;
            }

            // Tambahan khusus guru: Absensi Per Jam bukan baris usermenu, tetapi
            // route /absensi-perjam memang khusus level guru (check.level:guru).
            if ($level === 'guru' && ! isset($rows['absensi-perjam'])) {
                $rows['absensi-perjam'] = (object) ['appmenunew' => (object) [
                    'url' => 'absensi-perjam',
                    'appmenunew' => 'Absensi Per Jam',
                    'icon' => 'fa-solid fa-list-check',
                    'urut' => 15,
                    'appmenucat_idappmenucat' => 5,
                ]];
            }

            $isSuperadmin = $level === 'superadmin';
            $menuManagement = [
                ['url' => 'rolecat', 'label' => 'Paket Menu', 'icon' => 'fa-regular fa-folder-open'],
                ['url' => 'appmenucat', 'label' => 'Kategori Menu', 'icon' => 'fa-solid fa-folder-tree'],
                ['url' => 'appmenunew', 'label' => 'Kelola Menu', 'icon' => 'fa-regular fa-folder-closed'],
                ['url' => 'users', 'label' => 'Users', 'icon' => 'fa-solid fa-users-rectangle'],
            ];
            $manajemenItems = [];
            if ($isSuperadmin) {
                foreach ($menuManagement as $m) {
                    $manajemenItems[] = $m;
                }
            }
            if (isset($rows['setting'])) {
                $manajemenItems[] = ['url' => 'setting', 'label' => $rows['setting']->appmenunew->appmenunew, 'icon' => 'fa-solid fa-gear'];
            }

            $berandaRow = null;
            if ($isSuperadmin) {
                $berandaRow = ['url' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-house'];
            } else {
                foreach (['berandasiswa', 'berandaguru', 'wali-beranda'] as $kandidat) {
                    if (isset($rows[$kandidat])) {
                        $berandaRow = ['url' => '/' . $kandidat, 'label' => $rows[$kandidat]->appmenunew->appmenunew, 'icon' => 'fa-solid fa-house'];
                        break;
                    }
                }
            }

            $groups = [
                ['key' => 'akademik', 'label' => 'Akademik', 'icon' => 'fa-solid fa-graduation-cap', 'items' => [
                    'tahunajaran' => 'Tahun Ajaran',
                    'siswa' => 'Data Siswa',
                    'guru' => 'Data Guru',
                    'kelas' => 'Kelas',
                    'jurusan' => 'Jurusan',
                    'mapel' => 'Mata Pelajaran',
                    'kelassiswa' => 'Penempatan Siswa',
                    'penugasanmengajar' => 'Penugasan Mengajar',
                    'profil-siswa' => 'Profil Saya',
                ]],
                ['key' => 'jadwal', 'label' => 'Jadwal & Absensi', 'icon' => 'fa-solid fa-calendar-week', 'items' => [
                    'jamhari' => 'Jam Pelajaran',
                    'jadwalkelas' => 'Jadwal Kelas',
                    'jadwalguru' => 'Jadwal Guru',
                    'jadwalsiswa' => 'Jadwal Siswa',
                    'absensi-harian' => 'Absensi Harian',
                    'absensi-perjam' => 'Absensi Per Jam',
                    'absensi-siswa' => 'Histori Absensi',
                ]],
                ['key' => 'penilaian', 'label' => 'Penilaian & Rapor', 'icon' => 'fa-solid fa-pen-ruler', 'items' => [
                    'komponennilai' => 'Komponen Nilai',
                    'kktpmapel' => 'KKTP',
                    'inputnilai' => 'Input Nilai',
                    'koreksinilai' => 'Koreksi Nilai',
                    'finalisasirapor' => 'Finalisasi Rapor',
                    'raporwalikelas' => 'Rapor Wali Kelas',
                    'raporsiswa' => 'Rapor Siswa',
                ]],
                ['key' => 'wali', 'label' => 'Wali Murid', 'icon' => 'fa-solid fa-user-shield', 'items' => [
                    'wali-akun' => 'Kelola Akun Wali',
                    'wali-antrian-verifikasi' => 'Antrean Verifikasi',
                    'wali-relasi' => 'Relasi Wali',
                    'wali-akademik' => 'Akademik Wali',
                ]],
                ['key' => 'keuangan', 'label' => 'Keuangan', 'icon' => 'fa-solid fa-sack-dollar', 'items' => [
                    'dashboardkeuangan' => 'Dashboard Keuangan',
                    'datatagihan' => 'Data Tagihan',
                    'tarif' => 'Tarif',
                    'beasiswa' => 'Beasiswa',
                    'transaksipembayaran' => 'Transaksi Pembayaran',
                    'pembayaranmanual' => 'Pembayaran Manual',
                    'rekonsiliasikeuangan' => 'Rekonsiliasi',
                    'refundkoreksi' => 'Refund & Koreksi',
                    'pembebasandenda' => 'Pembebasan Denda',
                    'bayartagihan' => 'Bayar Tagihan',
                ]],
                ['key' => 'komunikasi', 'label' => 'Komunikasi', 'icon' => 'fa-solid fa-bullhorn', 'items' => [
                    'pengumuman' => 'Pengumuman',
                    'pengumuman-siswa' => 'Pengumuman',
                    'notifikasi' => 'Notifikasi',
                ]],
                ['key' => 'sistem', 'label' => 'Manajemen Sistem', 'icon' => 'fa-solid fa-gears', 'items' => []],
                ['key' => 'legacy', 'label' => 'Fitur Legacy', 'icon' => 'fa-solid fa-clock-rotate-left', 'badge' => 'Lama', 'items' => [
                    'pengampu' => 'Pengampu',
                    'jadwal' => 'Jadwal (Lama)',
                ]],
            ];

            // Manajemen Sistem: union item superadmin hardcode + menu "setting" per-user.
            foreach ($groups as &$g) {
                if ($g['key'] === 'sistem') {
                    foreach ($manajemenItems as $mi) {
                        $g['items'][$mi['url']] = $mi['label'];
                    }
                }
            }
            unset($g);

            $resolveGroupItem = function ($url) use ($rows, $resolveIcon) {
                if (! isset($rows[$url])) {
                    return null;
                }
                $m = $rows[$url]->appmenunew;
                return ['url' => '/' . $m->url, 'label' => $m->appmenunew, 'icon' => $resolveIcon($m->icon), 'svg' => ($m->icon && str_ends_with(strtolower(trim((string) $m->icon)), '.svg')) ? trim((string) $m->icon) : null];
            };

            $isDocsActive = request()->is('welcome-docs');
            $isDashboardActive = $berandaRow ? request()->is(ltrim($berandaRow['url'], '/')) : false;

            // Tentukan kelompok yang mengandung halaman aktif (untuk auto-open).
            $activeGroup = null;
            foreach ($groups as $g) {
                foreach (array_keys($g['items']) as $url) {
                    if (request()->is($url)) {
                        $activeGroup = $g['key'];
                        break 2;
                    }
                }
                // halaman khusus hardcoded/management juga dihitung
                if ($g['key'] === 'sistem') {
                    if (request()->is('users') || request()->is('rolecat') || request()->is('appmenucat') || request()->is('appmenunew') || request()->is('setting')) {
                        $activeGroup = 'sistem';
                    }
                }
            }
        @endphp

        <ul class="sidebar-nav p-0 mt-3">
            @if ($berandaRow)
                <li class="nav-item">
                    <a class="nav-link {{ $isDashboardActive ? 'active' : '' }}" href="{{ $berandaRow['url'] }}">
                        <i class="{{ $berandaRow['icon'] }} me-3"></i> {{ $berandaRow['label'] }}
                    </a>
                </li>
            @endif

            @foreach ($groups as $g)
                @php
                    // Kumpulkan item kelompok yang benar-benar dimiliki user (atau hardcode untuk superadmin).
                    $items = [];
                    foreach (array_keys($g['items']) as $url) {
                        if (in_array($url, ['users', 'rolecat', 'appmenucat', 'appmenunew'], true) && ! $isSuperadmin) {
                            continue;
                        }
                        if (in_array($url, ['users', 'rolecat', 'appmenucat', 'appmenunew'], true)) {
                            $m = collect($menuManagement)->firstWhere('url', $url);
                            $items[] = ['url' => '/' . $url, 'label' => $m['label'], 'icon' => $m['icon'], 'svg' => null];
                            continue;
                        }
                        $item = $resolveGroupItem($url);
                        if ($item) {
                            $items[] = $item;
                        }
                    }

                    // Ikon kelompok jika semua item custom
                    $groupHasActive = false;
                    foreach ($items as $i) {
                        if (request()->is(ltrim($i['url'], '/'))) {
                            $groupHasActive = true;
                            break;
                        }
                    }
                    $open = $activeGroup === $g['key'];
                @endphp

                @if (count($items) > 0)
                    <li class="nav-group docs-group {{ $open ? 'open' : '' }}" data-group="{{ $g['key'] }}">
                        <button type="button" class="docs-group-toggle {{ $groupHasActive ? 'has-active' : '' }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="group-{{ $g['key'] }}">
                            <i class="{{ $g['icon'] }} me-3 icon_sidebar_fa" aria-hidden="true"></i>
                            <span class="docs-group-title">{{ $g['label'] }}</span>
                            @if (! empty($g['badge']))
                                <span class="docs-group-badge">{{ $g['badge'] }}</span>
                            @endif
                            <i class="fa-solid fa-chevron-down docs-group-chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="docs-group-items" id="group-{{ $g['key'] }}">
                            @foreach ($items as $item)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is(ltrim($item['url'], '/')) ? 'active' : '' }}" href="{{ $item['url'] }}">
                                        @if ($item['svg'])
                                            <img src="{{ asset('asset_offline/icon/' . $item['svg']) }}" class="icon_sidebar me-3" style="max-width: 16px;" alt="">
                                        @else
                                            <i class="{{ $item['icon'] }} icon_sidebar_fa me-3" aria-hidden="true"></i>
                                        @endif
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach

            <li class="nav-item">
                <a class="nav-link {{ $isDocsActive ? 'active' : '' }}" href="/welcome-docs">
                    <i class="fa-solid fa-file-lines me-3"></i> Dokumentasi
                </a>
            </li>
        </ul>
        </div>
    </div>
</div>

<script>
    /* Accordion sidebar: maksimal satu kelompok terbuka. */
    (function () {
        var KEY = 'sidebar-open-group';
        var groups = Array.prototype.slice.call(document.querySelectorAll('.docs-group'));
        if (!groups.length) {
            return;
        }

        function setOpen(group, open) {
            var btn = group.querySelector('.docs-group-toggle');
            group.classList.toggle('open', open);
            if (btn) {
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        function stored() {
            try { return sessionStorage.getItem(KEY); } catch (e) { return null; }
        }
        function save(key) {
            try { sessionStorage.setItem(KEY, key || ''); } catch (e) { /* abaikan */ }
        }

        var hasServerActive = groups.some(function (g) { return g.classList.contains('open'); });

        groups.forEach(function (group) {
            var btn = group.querySelector('.docs-group-toggle');
            if (!btn) { return; }
            btn.addEventListener('click', function () {
                var willOpen = !group.classList.contains('open');
                groups.forEach(function (g) { setOpen(g, false); });
                if (willOpen) {
                    setOpen(group, true);
                    save(group.getAttribute('data-group'));
                } else {
                    save('');
                }
            });
        });

        if (!hasServerActive) {
            var wanted = stored();
            if (wanted) {
                var found = groups.filter(function (g) { return g.getAttribute('data-group') === wanted; })[0];
                if (found) {
                    groups.forEach(function (g) { setOpen(g, false); });
                    setOpen(found, true);
                    return;
                }
            }
        }
    })();
</script>

<script>
    (function () {
        var KEY = 'sidebar-scroll-top';

        function host() {
            return document.getElementById('sidebar');
        }

        function kandidat() {
            var h = host();
            if (!h) {
                return [];
            }
            var daftar = [];
            var simplebar = h.querySelector('.simplebar-content-wrapper');
            if (simplebar) {
                daftar.push(simplebar);
            }
            var menu = h.querySelector('.sidebar-menu');
            if (menu) {
                daftar.push(menu);
            }
            daftar.push(h);
            return daftar;
        }

        function scrollerAktif() {
            var daftar = kandidat();
            for (var i = 0; i < daftar.length; i++) {
                if (daftar[i].scrollHeight - daftar[i].clientHeight > 4) {
                    return daftar[i];
                }
            }
            return daftar.length ? daftar[0] : null;
        }

        function baca() {
            try {
                return sessionStorage.getItem(KEY);
            } catch (e) {
                return null;
            }
        }

        function simpan() {
            var el = scrollerAktif();
            if (!el) {
                return;
            }
            try {
                sessionStorage.setItem(KEY, String(el.scrollTop || 0));
            } catch (e) {
                /* storage tidak tersedia */
            }
        }

        function pulihkan() {
            var nilai = baca();
            if (nilai === null) {
                return;
            }
            var posisi = parseInt(nilai, 10) || 0;
            var daftar = kandidat();
            for (var i = 0; i < daftar.length; i++) {
                var el = daftar[i];
                if (el.scrollHeight - el.clientHeight > 4) {
                    el.scrollTop = posisi;
                }
            }
            var h = host();
            if (h && h.SimpleBar && typeof h.SimpleBar.getScrollElement === 'function') {
                try {
                    h.SimpleBar.getScrollElement().scrollTop = posisi;
                } catch (e) {
                    /* abaikan */
                }
            }
        }

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (target && target.closest && target.closest('#sidebar a')) {
                simpan();
            }
        }, true);

        document.addEventListener('scroll', function (event) {
            var daftar = kandidat();
            for (var i = 0; i < daftar.length; i++) {
                if (event.target === daftar[i]) {
                    simpan();
                    return;
                }
            }
        }, true);

        window.addEventListener('pagehide', simpan);
        window.addEventListener('beforeunload', simpan);

        var timer = null;

        function mulai() {
            pulihkan();
            if (timer !== null) {
                window.clearInterval(timer);
            }
            var n = 0;
            timer = window.setInterval(function () {
                pulihkan();
                if (++n >= 40) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }, 100);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mulai);
        } else {
            mulai();
        }

        window.addEventListener('load', mulai);
    })();
</script>
