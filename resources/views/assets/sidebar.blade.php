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
</style>

<div class="sidebar sidebar-fixed bg-white border-end" id="sidebar" style="border-right: 1px solid #f3f4f6;">
    <div class="padding_sidebar">
        <div class="sidebar-brand d-none d-md-flex justify-content-start px-lg-4 my-3 bg-white">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-boxes-stacked fa-2x" style="color: #0E7D6A;"></i>
                <div class="fw-bold mx-2" style="font-size: 18px; color: #111827;">
                    Wakibota <span
                        style="font-size: 11px; display: block; color: #0E7D6A; font-weight: 500;">Template</span>
                </div>
            </div>
        </div>

        <div class="sidebar-menu" data-simplebar>
        <ul class="sidebar-nav p-0 mt-3" data-coreui="navigation">
            @php
                // Menu khusus peran lain tidak relevan (dan 404) untuk superadmin.
                $menuKhususRoleLain = ['berandaguru', 'berandasiswa', 'jadwalsiswa', 'absensi-siswa', 'profil-siswa'];
                $sembunyikanMenuRoleLain = Auth::user()->level === 'superadmin';
            @endphp

            <li class="nav-title">Main</li>

            @if (Auth::user()->level === 'superadmin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">
                        <i class="fa-solid fa-house me-3"></i> Dashboard
                    </a>
                </li>
            @endif

            @if (in_array(Auth::user()?->level, ['guru', 'superadmin'], true))
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('jadwalguru') ? 'active' : '' }}" href="/jadwalguru">
                        <i class="fa-solid fa-calendar-week me-3"></i> Jadwal Mengajar
                    </a>
                </li>
            @endif

            @if (Auth::user()?->level === 'guru')
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('absensi-perjam') ? 'active' : '' }}" href="/absensi-perjam">
                        <i class="fa-solid fa-list-check me-3"></i> Absensi Per Jam
                    </a>
                </li>
            @endif


            <li class="nav-item">
                <a class="nav-link {{ request()->is('welcome-docs') ? 'active' : '' }}" href="/welcome-docs">
                    <i class="fa-solid fa-file-lines me-3"></i> Docs
                </a>
            </li>



            {{-- Keep original dynamic menus below for functionality --}}
            @if (Auth::user()->level == 'superadmin')
                <li class="nav-title">Menu Management</li>
                <li class="nav-item"><a class="nav-link {{ request()->is('rolecat') ? 'active' : '' }}" href="/rolecat"><i
                            class="fa-regular fa-folder-open me-3"></i> Paket Menu</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('appmenucat') ? 'active' : '' }}"
                        href="/appmenucat"><i class="fa-solid fa-folder-tree me-3"></i> Kategori Menu</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('appmenunew') ? 'active' : '' }}"
                        href="/appmenunew"><i class="fa-regular fa-folder-closed me-3"></i> Kelola Menu</a></li>
            @endif

            @if (Auth::user()->level == 'superadmin')
                <li class="nav-title">Manage Users</li>
                <li class="nav-item"><a class="nav-link {{ request()->is('users') ? 'active' : '' }}" href="/users"><i
                            class="fa-solid fa-users-rectangle me-3"></i> Users</a></li>
            @endif

            @foreach ($appMenuCat as $items)
                @php
                    $hasMenuItems = $appMenu->filter(function ($menu) use ($items) {
                        return $menu->appmenunew->url !== 'semester'
                            && $menu->appmenunew->appmenucat_idappmenucat == $items->idappmenucat;
                    })->isNotEmpty();
                @endphp

                @if ($hasMenuItems)
                    <li class="nav-title">{{ $items->appmenucat }}</li>
                    @foreach ($appMenu as $datas)
                        @if ($datas->appmenunew->url === 'semester')
                            @continue
                        @endif
                        @if ($sembunyikanMenuRoleLain && in_array($datas->appmenunew->url, $menuKhususRoleLain, true))
                            @continue
                        @endif
                        @if ($datas->appmenunew->appmenucat_idappmenucat == $items->idappmenucat)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is($datas->appmenunew->url) ? 'active' : '' }}"
                                    href="/{{ $datas->appmenunew->url }}">
                                    @php($menuIcon = trim((string) $datas->appmenunew->icon))
                                    @if (str_starts_with($menuIcon, 'fa-') || str_starts_with($menuIcon, 'fa '))
                                        <i class="{{ $menuIcon }} icon_sidebar_fa me-3" aria-hidden="true"></i>
                                    @elseif (str_ends_with(strtolower($menuIcon), '.svg'))
                                        <img src="{{ asset('asset_offline/icon/' . $menuIcon) }}" class="icon_sidebar me-3"
                                            style="max-width: 16px;">
                                    @else
                                        <i class="fa-regular fa-folder-open me-3"></i>
                                    @endif
                                    {{ $datas->appmenunew->appmenunew }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ul>
        </div>
    </div>
</div>

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