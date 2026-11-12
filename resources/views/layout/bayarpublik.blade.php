<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Pembayaran tagihan sekolah melalui SIAKAD — Midtrans Snap.">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>SIAKAD Template | @yield('title', 'Pembayaran Tagihan')</title>
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('asset_offline/fontawesome/css/all.css') }}">
    <style>
        body { background: #f1f5f9; font-family: 'DM Sans', system-ui, sans-serif; }
        .bp-wrap { max-width: 640px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        .bp-brand { display: flex; align-items: center; gap: .75rem; justify-content: center; margin-bottom: 1rem; color: #0f5132; }
        .bp-brand img { width: 44px; height: 44px; }
        .bp-brand span { font-weight: 800; font-size: 1.35rem; letter-spacing: .5px; }
        .bp-card { background: #fff; border-radius: 1rem; box-shadow: 0 8px 30px rgba(0,0,0,.08); padding: 1.75rem; }
        .bp-nominal { font-size: 1.9rem; font-weight: 800; color: #14532d; }
        .bp-row { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed #e2e8f0; }
        .bp-row:last-child { border-bottom: none; }
    </style>
    @livewireStyles
</head>
<body>
    <main class="bp-wrap">
        <div class="bp-brand" aria-hidden="true">
            <img src="{{ asset('img/brand/siakad-logo.svg') }}" alt="">
            <span>SIAKAD</span>
        </div>
        @yield('content')
        {{ $slot ?? '' }}
        <p class="text-center small text-secondary mt-3 mb-0">Satu tagihan per transaksi &middot; Pembayaran diproses oleh Midtrans Snap.<br>Tautan ini tidak menampilkan data pribadi siswa.</p>
    </main>
    @livewireScripts
</body>
</html>
