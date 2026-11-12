<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tidak Mempunyai Akses</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
</head>
<!-- Style 1-->
<link rel="stylesheet" href="{{ asset('css/main.css') }}">
<script src="{{ asset('asset_offline/js/bootstrap.js') }}"></script>

<body>
    <!-- Error 404 Template 1 - Bootstrap Brain Component -->
    <section class="py-3 py-md-5 min-vh-100 d-flex justify-content-center align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="text-center">
                        <div class="mb-3">
                            <img src="{{ asset('img/403.gif') }}" alt="">
                        </div>
                        <h3 class="h2 mb-2">Tidak Mempunyai Akses.</h3>
                        <p class="mb-5">Maaf, Halaman Yang anda tuju tidak ditemukan !</p>



                        <a class="btn bsb-btn-5xl text-white fw-medium rounded-pill px-5 fs-6 m-0"
                            style="background: #359387" role="button"
                            href="{{ auth()->check() ? \App\Support\BerandaUrl::untuk(auth()->user()) : '/login' }}">Kembali ke
                            Halaman
                            Awal</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>
