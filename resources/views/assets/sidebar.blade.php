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
            <li class="nav-title">Main</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">
                    <i class="fa-solid fa-house me-3"></i> Dashboard
                </a>
            </li>



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

            @if (Auth::user()->level == 'admin' || Auth::user()->level == 'superadmin')
                <li class="nav-title">Manage Users</li>
                <li class="nav-item"><a class="nav-link {{ request()->is('users') ? 'active' : '' }}" href="/users"><i
                            class="fa-solid fa-users-rectangle me-3"></i> Users</a></li>
            @endif

            @foreach ($appMenuCat as $items)
                @php
                    $hasMenuItems = $appMenu->filter(function ($menu) use ($items) {
                        return $menu->appmenunew->appmenucat_idappmenucat == $items->idappmenucat;
                    })->isNotEmpty();
                @endphp

                @if ($hasMenuItems)
                    <li class="nav-title">{{ $items->appmenucat }}</li>
                    @foreach ($appMenu as $datas)
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