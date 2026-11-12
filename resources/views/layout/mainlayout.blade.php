<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="SIAKAD Template - Sistem Informasi Akademik untuk sekolah">
    <meta name="author" content="SIAKAD">
    <meta name="keyword" content="SIAKAD,Template,Sistem Informasi Akademik,Sekolah,Akademik">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>SIAKAD Template | @yield('title')</title>

    @include('assets.style')

    @livewireStyles



</head>


<body style="font-family: 'DM Sans' , sans-serif;">


    <!-- Sidebar -->
    @include('assets.sidebar')
    <!-- Sidebar -->

    <div class="wrapper d-flex flex-column min-vh-100" style="background: #fff">

        <!-- Navbar -->
        @include('assets.navbar')
        <!-- Navbar -->

        <div class="container-fluid pb-3">
            @yield('content')
            {{ $slot ?? '' }}
        </div>


    </div>

    @include('assets.scripts')
    @livewireScripts
</body>

</html>
