@extends('layout.mainlayout')
@section('title','users')
@section('content')
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
                    <p class="p-0 m-0 h6 text-primary">Profile Picture</p>
                    <div>
                    </div>
                </div>
            </div>
            <div class="modal-content p-4 rounded-bottom border bg-white text-center d-flex justify-content-center align-items-center"
                style="margin-top: -1px">
                <div>
                    <div class="avatar avatar-xl overflow-hidden mb-3">
                        <img src="https://sb-admin-pro.startbootstrap.com/assets/img/illustrations/profiles/profile-1.png"
                            alt="" width="100%">
                    </div>
                    <div class="text-secondary" style="font-size:14px;">JPG or PNG no larger than 5 MB</div>
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

@endsection