<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BerandaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $idUser = auth()->user()->id;

        $userFind = User::findOrFail($idUser);

        return view('setting', compact(['userFind']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Penjaga IDOR: hanya pemilik akun yang boleh mengubah profilnya.
        abort_unless((int) $id === (int) auth()->id(), 403);

        // Audit struktur tabel users: name NOT NULL (max 255), email nullable (max 255, unique).
        $rules = [
            'name' => 'required|string|max:255',
        ];

        if ($request->filled('email')) {
            $rules['email'] = 'email|max:255|unique:users,email,'.$id;
        }

        // Foto profil: hanya JPG/JPEG/PNG maks. 5 MB; validasi backend.
        if ($request->hasFile('foto')) {
            $rules['foto'] = 'image|mimes:jpg,jpeg,png|max:5120';
        }

        $request->validate($rules);

        // Foto profil saat ini hanya tersedia untuk akun siswa (kolom siswa.foto,
        // 1:1 ke user lewat siswa.user_iduser). Role lain tidak diubah.
        $fotoUpload = $request->file('foto');
        if ($fotoUpload && $request->user()?->level !== 'siswa') {
            return redirect()->back()->with('error', 'Unggah foto profil hanya tersedia untuk akun siswa.');
        }

        $fotoPathBaruOuter = null;

        try {
            return DB::transaction(function () use ($request, $id, $fotoUpload, &$fotoPathBaruOuter) {
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                $siswa = null;
                $fotoPathBaru = null;
                $fotoLama = null;

                if ($fotoUpload) {
                    $siswa = $user->siswa()->lockForUpdate()->first();
                    if (! $siswa) {
                        return redirect()->back()->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
                    }

                    $ext = strtolower($fotoUpload->getClientOriginalExtension());
                    try {
                        $fotoPathBaru = $fotoUpload->storeAs('siswa', 'siswa-'.$siswa->idsiswa.'-'.now()->format('YmdHis').'-'.uniqid().'.'.$ext, 'public');
                    } catch (\Throwable $e) {
                        report($e);

                        return redirect()->back()->with('error', 'Gagal menyimpan foto. Silakan coba lagi.');
                    }

                    $fotoLama = $siswa->foto;
                    $siswa->foto = $fotoPathBaru;
                    $fotoPathBaruOuter = $fotoPathBaru;
                    $siswa->save();
                }

                $passwordChanged = false;

                $user->name = $request->name;
                // username read-only: tidak diubah dari form
                if ($request->filled('email')) {
                    $user->email = $request->email;
                }

                // Check if the provided last password matches the current password
                if (! Hash::check($request->lastPassword, $user->password)) {
                    return redirect()->back()->with('error', 'Password Lama Anda Salah');
                }

                if ($request->filled('password')) {
                    // Check if password and confirm password match
                    if ($request->password != $request->confirmpassword) {
                        return redirect()->back()->with('error', 'Konfirmasi Password tidak sama');
                    }

                    if (strlen($request->password) < 8) {
                        return redirect()->back()->with('error', 'Password baru minimal 8 karakter');
                    }

                    if (Hash::check($request->password, $user->password)) {
                        return redirect()->back()->with('error', 'Password baru tidak boleh sama dengan password lama');
                    }

                    // Hash and save the new password
                    $user->password = Hash::make($request->password);
                    $user->wajib_ganti_password = false;
                    $passwordChanged = true;
                }

                $user->save();

                // Foto lama baru dihapus setelah transaksi sukses (jangan hapus sebelum commit).
                if ($fotoLama) {
                    DB::afterCommit(fn () => Storage::disk('public')->delete($fotoLama));
                }

                $redirect = $passwordChanged ? BerandaUrl::untuk($user) : '/setting';

                return redirect($redirect)->with('success', $fotoPathBaru ? 'Foto profil berhasil diperbarui.' : 'Data Berhasil Diperbarui');
            });
        } catch (\Throwable $e) {
            // Bila transaksi gagal setelah file tersimpan, bersihkan file baru agar tidak orphan.
            if ($fotoPathBaruOuter) {
                Storage::disk('public')->delete($fotoPathBaruOuter);
            }

            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
