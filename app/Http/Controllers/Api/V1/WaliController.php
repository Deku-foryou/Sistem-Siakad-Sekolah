<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\BerandaWaliService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API profil & beranda wali murid.
 * Identitas wali selalu dari user yang terautentikasi.
 */
class WaliController extends \App\Http\Controllers\Controller
{
    public function profil(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->level !== 'wali' || $user->status_akun !== 'aktif') {
            return ApiResponse::fail('Akun wali tidak aktif. Hubungi admin sekolah.', 403, [], 'WALI_NOT_ACTIVE');
        }

        $relasi = \App\Models\Walimurid::query()
            ->where('user_iduser', $user->id)
            ->selectRaw('status_hubungan, COUNT(*) as jumlah')
            ->groupBy('status_hubungan')
            ->pluck('jumlah', 'status_hubungan');

        return ApiResponse::ok([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'status_akun' => $user->status_akun,
            'wajib_ganti_password' => (bool) $user->wajib_ganti_password,
            'relasi' => [
                'verified' => (int) ($relasi['verified'] ?? 0),
                'pending' => (int) ($relasi['pending'] ?? 0),
                'rejected' => (int) ($relasi['rejected'] ?? 0),
                'terminated' => (int) ($relasi['terminated'] ?? 0),
            ],
        ], 'Berhasil');
    }

    public function beranda(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->level !== 'wali' || $user->status_akun !== 'aktif') {
            return ApiResponse::fail('Akun wali tidak aktif. Hubungi admin sekolah.', 403, [], 'WALI_NOT_ACTIVE');
        }

        try {
            $data = BerandaWaliService::beranda($user);
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 403, [], 'WALI_NOT_ACTIVE');
        }

        $konteks = $data['konteks'];

        return ApiResponse::ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ],
            'akun' => [
                'username' => $user->username,
                'wajib_ganti_password' => (bool) $user->wajib_ganti_password,
            ],
            'konteks' => [
                'valid' => $konteks->valid(),
                'peringatan' => $konteks->pesanPeringatan(),
                'tahun_ajaran' => $konteks->tahunAjaran ? ['id' => $konteks->tahunAjaran->idtahunajaran, 'nama' => $konteks->tahunAjaran->nama] : null,
                'semester' => $konteks->semester ? ['id' => $konteks->semester->idsemester, 'nama' => $konteks->semester->nama] : null,
            ],
            'anak' => $data['anak'],
            'ringkasan' => $data['ringkasan'],
            'nama_hari' => $data['namaHariIni'],
        ], 'Berhasil');
    }
}
