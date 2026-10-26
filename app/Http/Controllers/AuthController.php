<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $idUser = Auth()->user()->id;

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
        $user = User::findOrFail($id);

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
        }

        $user->save();

        return redirect('/setting')->with('success', 'Data Berhasil Diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
