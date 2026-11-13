<style>
    /* Warna sidebar Head */
    .sidebar_bg_docs .nav-title {
        color: #272727 !important;
        font-weight: 800 !important;
        font-size: 16px !important;
    }

    /* Warna sidebar All */
    .sidebar_bg_docs {
        background: #ececec;
    }

    .padding_sidebar {
        padding: 0px 8px;
    }


    .sidebar_bg_docs .nav-item .nav-link.active {
        color: #292929;

    }

    /* Warna sidebar active */
    .sidebar_bg_docs .nav-link.active {
        background: #99b1ef4b;
        font-weight: 500;
        border-radius: 10px;
    }


    /* Warna sidebar Group */
    .sidebar-nav .nav-group.show .nav-group-toggle {
        background: #e2e2e2;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        font-weight: 500;
        color: #717176;
    }

    /* Warna sidebar Group */
    .sidebar-nav .nav-group.show {
        background: #717176;
    }

    /* Warna sidebar Group All Items */
    .sidebar_bg_docs .nav-group-items .nav-item {
        background: #717176;

    }

    /* Warna sidebar Group Items Focus */
    .sidebar_bg_docs .nav-item .nav-link.sidebar_items_text.active {
        background: #14B615;
        border-radius: 0;
    }


    .sidebar_bg_docs .nav-item .nav-link {
        color: #2778f1;
        padding: 5px 10px;
        margin: 2px 0px
    }

    .sidebar_bg_docs .nav-group .nav-link {

        color: #7F9F80;
    }

    .sidebar-nav .nav-group-toggle::after {
        filter: invert(100%);
    }
</style>

<div class="sidebar sidebar_bg_docs sidebar-fixed" id="sidebar" style="overflow-x: hidden">
    <div class="padding_sidebar">
        <div class="sidebar-brand d-md-flex fw-medium justify-content-start px-lg-4 my-3" style="background: #ececec">
            {{-- <img src="{{ asset('/img/logo_tulungagung.png') }}" alt="Logo Dinas KB" class="img-fluid"
                                style="max-width: 50px"> --}}
            {{-- <i class="fa-solid fa-s" style="min-width: 50px"></i> --}}
            <div class="d-block">
                <div class="nav-title fw-bold mx-2 text-black " style="font-size: 15px">
                    Dokumentasi <br> Aplikasi
                </div>
            </div>
        </div>


        <style>
            .sidebar-nav .nav-link {
                white-space: normal;
                /* Allows the text to wrap to the next line */
                word-wrap: break-word;
                /* Breaks long words if needed */
                overflow-wrap: break-word;
                /* Prevents long words from overflowing */
                text-overflow: ellipsis;
                /* Adds ellipsis (...) if necessary */
            }
        </style>

        <ul class="sidebar-nav p-2" data-coreui="navigation">

            <!-- Navbar -->
            <!-- Your existing header content goes here -->
            <a href="{{ \App\Support\BerandaUrl::untuk(auth()->user()) }}" class="btn btn-outline-secondary text-black mb-3" data-bs-toggle="tooltip" data-bs-placement="top" title="Kembali"><i
                    class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
            <!-- Navbar -->

            <li class="nav-title fw-medium"
                style="margin-top: 7px; margin-left: 8px; font-size:14px !important;font-weight: 700 !important;">
                Documentation
            </li>

            <li class="nav-item"><a class="nav-link " href="/welcome-docs">
                    <i class="fa-solid fa-house-fire me-2"></i>Dokumentasi</a>
            </li>

            @if (in_array(auth()->user()?->level, ['superadmin', 'admin'], true))
                <li class="nav-item"><a class="nav-link " href="/docscat">
                        <i class="fa-solid fa-folder-tree me-2"></i> Kategori Dokumentasi</a>
                </li>

                <li class="nav-item"><a class="nav-link " href="/docs">
                        <i class="fa-solid fa-folder-plus me-2"></i> Buat Dokumentasi</a>
                </li>
            @endif


            @foreach ($docsCat as $items)
                @php
                    // Cek apakah ada item menu yang sesuai dengan kategori ini
                    $hasMenuItems = $sidebarDocs
                        ->filter(function ($menu) use ($items) {
                            return $menu->docscat_iddocscat == $items->iddocscat;
                        })
                        ->isNotEmpty();

                @endphp

                @if ($hasMenuItems)
                    <li class="nav-title fw-medium"
                        style="margin-top: 7px; margin-left: 8px; font-size:14px !important;font-weight: 700 !important;">
                        {{ $items->docscat }}
                    </li>

                    {{-- Looping App Menu --}}
                    @foreach ($sidebarDocs->where('docscat_iddocscat', $items->iddocscat) as $datas)
                        <li class="nav-item"><a class="nav-link  position-relative" href="/docs/{{ $datas->iddocs }}">
                                <i class="fa-solid fa-square-arrow-up-right top-1 position-absolute"
                                    style="top: 9px"></i>
                                <span class="ms-4">{{ $datas->title }}</span></a>
                        </li>
                    @endforeach
                @endif
            @endforeach

        </ul>
    </div>
</div>
