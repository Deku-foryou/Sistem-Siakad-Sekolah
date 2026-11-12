<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="SIAKAD Template - Dokumentasi Aplikasi Sistem Informasi Akademik">
    <meta name="author" content="SIAKAD">
    <meta name="keyword" content="SIAKAD,Template,Dokumentasi,Sistem Informasi Akademik,Sekolah">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Dokumentasi Aplikasi | @yield('title')</title>

    @include('assets.style')

    @livewireStyles
    @livewireScripts

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap"
        rel="stylesheet">


<body style="font-family: Rethink Sans, sans-serif;">

    <!-- Sidebar -->
    @include('assets.docs_sidebar')
    <!-- Sidebar -->

    <div class="container px-4 pt-4 d-block d-md-none">
        <button class="header-toggler d-flex justify-content-start align-items-center" type="button"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
            <i class="fa-solid fa-bars me-3"></i> Halo, {{ Auth::user()->name }}
        </button>
    </div>

    <div class="wrapper d-flex flex-column min-vh-100" style="background: #fff">



        <div class="container-fluid pb-3">
            @yield('content')
            {{ $slot ?? '' }}
        </div>


    </div>
    @include('assets.scripts')
</body>

</html>
