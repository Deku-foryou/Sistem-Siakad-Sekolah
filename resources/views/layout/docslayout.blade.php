<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="SIAKAD Template - Dokumentasi Aplikasi Sistem Informasi Akademik">
    <meta name="author" content="SIAKAD">
    <meta name="keyword" content="SIAKAD,Template,Dokumentasi,Sistem Informasi Akademik,Sekolah">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Dokumentasi Aplikasi | @yield('title', 'Dokumentasi')</title>

    @include('assets.style')

    @livewireStyles
    @livewireScripts

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap"
        rel="stylesheet">

    {{-- Gaya konten artikel dokumentasi (Bootstrap, tampilan lama) --}}
    <style>
        .doc-article h2[id], .doc-article h3[id] { scroll-margin-top: 1rem; }
        .doc-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #198754; display: inline-flex; align-items: center; gap: .45rem; margin-bottom: .25rem; }
        .doc-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -.01em; color: #212529; margin: 0 0 .35rem; line-height: 1.2; }
        .doc-lead { font-size: 1rem; color: #6c757d; margin-bottom: 1rem; }
        .doc-article h2 { font-size: 1.15rem; font-weight: 700; margin: 1.35rem 0 .5rem; padding-bottom: .35rem; border-bottom: 1px solid #dee2e6; color: #212529; }
        .doc-article h3 { font-size: .95rem; font-weight: 700; margin: 1rem 0 .35rem; color: #212529; }
        .doc-article p, .doc-article li { font-size: .9rem; line-height: 1.7; }
        .doc-article a { color: #0d6efd; }

        .doc-quick { display: flex; flex-wrap: wrap; gap: .4rem; margin: .25rem 0 .9rem; }
        .doc-quick-item { display: inline-flex; gap: .4rem; align-items: center; font-size: .78rem; background: #e9ecef; color: #212529; border-radius: 999px; padding: .28rem .7rem; font-weight: 600; }

        .doc-note { display: block; font-size: .83rem; border-radius: .375rem; padding: .6rem .75rem .6rem 2.1rem; margin: .65rem 0; line-height: 1.55; border: 1px solid transparent; position: relative; }
        .doc-note > i.fa-solid { position: absolute; left: .75rem; top: .72rem; }
        .doc-note-info { background: #cff4fc; border-color: #9eeaf9; color: #055160; }
        .doc-note-warn { background: #fff3cd; border-color: #ffecb5; color: #664d03; }

        .doc-table { width: 100%; border-collapse: collapse; font-size: .82rem; margin: .6rem 0 1rem; background: #fff; border: 1px solid #dee2e6; }
        .doc-table-dense { font-size: .74rem; }
        .doc-table th { background: #f8f9fa; text-align: left; padding: .45rem .6rem; font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; color: #495057; border-bottom: 2px solid #dee2e6; }
        .doc-table td { padding: .45rem .6rem; border-bottom: 1px solid #dee2e6; vertical-align: top; }
        .doc-table tr:last-child td { border-bottom: 0; }

        .doc-figure { margin: .9rem 0 1.1rem; }
        .doc-shot { max-width: 100%; border: 1px solid #dee2e6; border-radius: .375rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
        .doc-figure figcaption { font-size: .78rem; color: #6c757d; margin-top: .4rem; }
        .doc-diagram { background: #f8f9fa; color: #212529; border: 1px solid #dee2e6; border-radius: .375rem; padding: .75rem .9rem; font-size: .72rem; overflow-x: auto; line-height: 1.65; }

        @media print {
            .docs-sidebar, .docs-toggler, .docs-prevnext, .docs-breadcrumb, .docs-toc { display: none !important; }
            .doc-figure { break-inside: avoid; }
        }
    </style>

<body style="font-family: Rethink Sans, sans-serif;">

    <!-- Sidebar -->
    @if ($docsAz ?? false)
        {{-- Sidebar Pusat Dokumentasi A–Z dirender oleh komponen Livewire (interaktif). --}}
    @else
        @include('assets.docs_sidebar')
    @endif
    @if (!($docsAz ?? false))
        <div class="container px-4 pt-4 d-block d-md-none">
            <button class="header-toggler d-flex justify-content-start align-items-center" type="button"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
                <i class="fa-solid fa-bars me-3"></i> Halo, {{ Auth::user()->name }}
            </button>
        </div>
    @endif
    <!-- Sidebar -->

    <div class="wrapper d-flex flex-column min-vh-100" style="background: #fff">
        <div class="container-fluid pb-3">
            @yield('content')
            {{ $slot ?? '' }}
        </div>
    </div>
    @include('assets.scripts')
</body>

</html>
