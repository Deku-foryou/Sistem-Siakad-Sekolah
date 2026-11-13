<!DOCTYPE html>
<html lang="en">

<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>SIAKAD Template | Login</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <!-- Vendors styles-->
    <link rel="stylesheet" href="{{ asset('css/simplebar.css') }}">
    <!-- Main styles for this application-->
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    <script src="{{ asset('asset_offline/js/font-awesome.js') }}"></script>
    {{-- font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap"
        rel="stylesheet">
</head>

<body style="font-family: 'DM Sans' , sans-serif;">

    <!-- Login 13 - Bootstrap Brain Component -->
    <section class="bg-light py-3 py-md-5 d-flex justify-content-center align-items-center" style="min-height: 100vh">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                    <div class="card border border-light-subtle rounded-3 shadow-sm">
                        <div class="card-body p-3 p-md-4 p-xl-5">
                            <div class="text-center mb-3">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <img src="{{ asset('ubhi.webp') }}" alt="SIAKAD Template" width="64" height="64" style="object-fit: contain; width:auto; height:64px; flex:0 0 auto;">
                                    <span class="lh-sm text-start" style="white-space: nowrap;">
                                        <span style="display:block; font-weight:800; font-size:1.5rem; color:#0b5f52; letter-spacing:.02em;">SIAKAD</span>
                                        <span style="display:block; font-size:.72rem; font-weight:700; color:#6b7280; letter-spacing:.12em; text-transform:uppercase;">Template</span>
                                    </span>
                                </div>
                            </div>
                            <h2 class="fs-6 fw-normal text-center text-secondary mb-4">Sign in to your account</h2>
                            
                            @if(session('error'))
                                <div class="alert alert-danger" style="font-size: 13px; padding: 10px; margin-bottom: 15px;">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form action="/login" method="POST">
                                @csrf
                                <div class="row gy-2 overflow-hidden">
                                    <div class="col-12">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" name="username" id="username"
                                                placeholder="Username" required>
                                            <label for="username" class="form-label">Username</label>
                                        </div>
                                        @error('username')
                                            <span class="text-danger mt-1"
                                                style="font-size: 11.5px">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating mb-3">
                                            <input type="password" class="form-control" name="password" id="password"
                                                value="" placeholder="Password" required>
                                            <label for="password" class="form-label">Password</label>
                                        </div>
                                        @error('password')
                                            <span class="text-danger mt-1"
                                                style="font-size: 11.5px">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <img src="{{ captcha_src('flat') }}" style="cursor:pointer;" class="w-100 mb-2"
                                            id="captcha-image" style="width: 100%">
                                        <div class=" d-flex justify-content-between align-items-center gap-1">
                                            <input type="text" name="captcha" id="captcha"
                                                placeholder="Masukkan CAPTCHA" class="form-control">
                                            <button type="button" class="btn  btn-refresh"
                                                style="background: #0e7d6a" data-bs-toggle="tooltip" data-bs-placement="top" title="Muat ulang" aria-label="Muat ulang"><i
                                                    class="fa-solid fa-arrows-rotate text-white"></i></button>
                                        </div>
                                        @error('captcha')
                                            <span class="text-danger mt-1"
                                                style="font-size: 11.5px">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex gap-2 justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value=""
                                                    name="rememberMe" id="rememberMe">
                                                <label class="form-check-label text-secondary" for="rememberMe">
                                                    Keep me logged in
                                                </label>
                                            </div>
                                            <a href="#!" class="link-primary text-decoration-none">Forgot
                                                password?</a>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-grid my-3">
                                            <button class="btn btn-lg text-white" style="background: #0e7d6a"
                                                type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Masuk">Log
                                                in</button>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="m-0 text-secondary text-center">Don't have an account? <a
                                                href="#!" class="link-primary text-decoration-none">Sign up</a></p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- CoreUI and necessary plugins-->
    <script src="{{ asset('js/core.js') }}"></script>
    <script src="{{ asset('js/simplebar.js') }}"></script>
    <script>
        document.querySelector('.btn-refresh').addEventListener('click', function() {
            fetch('/refresh-captcha')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('#captcha-image').src = data.captcha;
                });
        });
    </script>
</body>

</html>
