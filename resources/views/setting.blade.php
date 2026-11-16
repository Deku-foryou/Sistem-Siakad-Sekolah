@extends('layout.mainlayout')
@section('title','users')
@section('content')
@php
    $siswaSetting = $userFind->level === 'siswa' ? $userFind->siswa : null;
    $fotoSettingUrl = null;
    if ($siswaSetting && $siswaSetting->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($siswaSetting->foto)) {
        $fotoSettingUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($siswaSetting->foto);
    }
    $inisialSetting = collect(preg_split('/\s+/', trim((string) ($userFind->name ?? ''))))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<div class="container-fluid">

    <div class="container-fluid mt-3 bg-white border  p-3 rounded">
        <div class="d-flex justify-content-between align-items-center px-2">
            <p class="p-0 m-0 h5"><i class="fa-regular fa-user me-2"></i> Account Settings - Profile</p>
            <div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger my-0 mt-3" role="alert">
            <p class="mb-1 fw-semibold"><i class="fa-regular fa-circle-xmark me-2"></i>Periksa kembali data yang Anda isi:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($message = Session::get('success'))
        <div class="alert alert-success pb-0 my-0 mt-3">
            <p><i class="fa-regular fa-circle-check me-2 text-success"></i> {{ $message }}</p>
        </div>
    @elseif ($message = Session::get('error'))
        <div class="alert alert-danger pb-0 my-0 mt-3">
            <p><i class="fa-regular fa-circle-xmark me-2 text-danger"></i>{{ $message }}</p>
        </div>
    @endif

    @if ($userFind->wajib_ganti_password)
        <div class="alert alert-warning my-0 mt-3" role="alert">
            <p class="mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Wajib Ganti Password.</strong></p>
            <p class="mb-1">Untuk keamanan akun Anda, silakan buat password baru terlebih dahulu. Isi <strong>Password Lama</strong>, <strong>Password</strong> (minimal 8 karakter, tidak sama dengan password lama), dan <strong>Konfirmasi Password</strong>, lalu klik <strong>Save Change</strong>.</p>
            <p class="mb-0">Selama password belum diganti, Anda akan tetap diarahkan ke halaman ini dan belum dapat membuka Beranda, Jadwal Pelajaran, atau Histori Absensi. Setelah berhasil, Anda akan diteruskan ke Beranda.</p>
        </div>
    @endif


    <div class="row mt-4">
        <div class="col-lg-4">
            <div class="container-fluid  bg-white border  p-3 rounded-top">
                <div class="d-flex justify-content-between align-items-center px-2">
                    <p class="p-0 m-0 h6 text-primary">Foto Profil</p>
                    <div>
                    </div>
                </div>
            </div>
            <div class="modal-content p-4 rounded-bottom border bg-white text-center d-flex justify-content-center align-items-center"
                style="margin-top: -1px">
                <div>
                    <div class="avatar avatar-xl overflow-hidden mb-3 d-flex justify-content-center">
                        @if ($fotoSettingUrl)
                            <img id="fotoPreview" src="{{ $fotoSettingUrl }}" alt="Foto {{ $userFind->name }}" width="128" height="128"
                                style="width: 128px; height: 128px; object-fit: cover; border-radius: 50%;">
                        @else
                            <div id="fotoPreview" class="d-flex justify-content-center align-items-center" aria-hidden="true"
                                style="width: 128px; height: 128px; border-radius: 50%; background: #e5e7eb; color: #374151; font-size: 2.25rem; font-weight: 700;">{{ $inisialSetting ?: '?' }}</div>
                        @endif
                    </div>
                    @if ($userFind->level === 'siswa')
                        <label for="foto" class="btn btn-outline-primary btn-sm mb-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Pilih foto dari perangkat Anda"><i class="fa-solid fa-upload me-1" aria-hidden="true"></i>Pilih Foto</label>
                        <div id="fotoNama" class="text-secondary" style="font-size:12px; min-height: 1.2em;"></div>
                        <div class="text-secondary" style="font-size:14px;">JPG/JPEG/PNG, maksimal 5 MB</div>
                        <div class="text-muted mt-1" style="font-size:12px;">Foto disimpan setelah Anda klik <strong>Save Change</strong>.</div>
                    @else
                        <div class="text-secondary" style="font-size:14px;">Unggah foto profil saat ini hanya tersedia untuk akun siswa.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8 mt-4 mt-lg-0">
            <div class="container-fluid bg-white border  p-3 rounded-top">
                <div class="d-flex justify-content-between align-items-center px-2">
                    <p class="p-0 m-0 h6 text-primary">Account Detail</p>
                    <div>
                    </div>
                </div>
            </div>
            <div class="modal-content p-4 rounded-bottom border bg-white" style="margin-top: -1px">
                <form action="{{  route('profil.edit', $userFind->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if ($userFind->level === 'siswa')
                        <input type="file" name="foto" id="foto" class="d-none" accept=".jpg,.jpeg,.png,image/jpeg,image/png" aria-label="Pilih foto profil">
                    @endif

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" value="{{ $userFind->username }}" readonly>
                    </div>

                    <div class="form-group mt-3">
                        <label for="exampleInputname1">Nama</label>
                        <input type="name" class="form-control" name="name" value="{{  $userFind->name }}"
                            id="exampleInputname1" placeholder="Enter name">
                    </div>

                    <div class="form-group mt-3">
                        <label for="exampleInputEmail1">Email address</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" value="{{  $userFind->email }}"
                            name="email" aria-describedby="emailHelp" placeholder="Enter email">
                        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone
                            else.</small>
                    </div>

                    <div class="form-group mt-3">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" class="form-control" name="lastPassword" id="currentPassword"
                            placeholder="Enter Current Password" autocomplete="current-password">
                    </div>

                    <div class="form-group mt-3">
                        <label for="exampleInputPassword1">Password</label>
                        <input type="password" class="form-control" name="password" id="exampleInputPassword1"
                            placeholder="Password">
                    </div>

                    <div class="form-group mt-3">
                        <label for="exampleInputPassword2">Confirm Password</label>
                        <input type="password" class="form-control" name="confirmpassword" id="exampleInputPassword2"
                            placeholder="Password">
                        <small class="form-text text-muted">Masukan Kembali Password.</small>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3" data-bs-toggle="tooltip" data-bs-placement="top" title="Simpan">Save Change</button>

                </form>
            </div>
        </div>
    </div>


</div>

@if ($userFind->level === 'siswa')
<script>
    (function () {
        var input = document.getElementById('foto');
        var nama = document.getElementById('fotoNama');
        if (!input) { return; }
        input.addEventListener('change', function () {
            var preview = document.getElementById('fotoPreview');
            var file = input.files && input.files[0];
            if (!file) { if (nama) { nama.textContent = ''; } return; }
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran foto maksimal 5 MB.');
                input.value = '';
                if (nama) { nama.textContent = ''; }
                return;
            }
            if (nama) { nama.textContent = file.name; }
            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Pratinjau foto';
                img.style.cssText = 'width:128px;height:128px;object-fit:cover;border-radius:50%;';
                if (preview) { preview.replaceWith(img); }
            };
            reader.readAsDataURL(file);
        });
    })();
</script>
@endif

@endsection