<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\GuruBerandaResource;
use App\Services\BerandaGuruService;
use App\Services\GuruApiService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API profil & beranda guru.
 * Identitas guru selalu dari user yang terautentikasi; tidak ada parameter guru dari request.
 */
class GuruController extends \App\Http\Controllers\Controller
{
    public function profil(Request $request): JsonResponse
    {
        try {
            $guru = GuruApiService::pastikanGuru($request->user());
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 403, [], 'GURU_NOT_LINKED');
        }

        $guru->load('mapel:idmapel,kode,nama');

        return ApiResponse::ok([
            'id' => $guru->idguru,
            'nip' => $guru->nip,
            'kode_guru' => $guru->kode_guru,
            'nama' => $guru->nama,
            'jenis_kelamin' => $guru->jenis_kelamin,
            'tempat_lahir' => $guru->tempat_lahir,
            'tanggal_lahir' => $guru->tanggal_lahir?->format('Y-m-d'),
            'telepon' => $guru->telepon,
            'alamat' => $guru->alamat,
            'status' => $guru->status,
            'mapel' => $guru->mapel->map(fn ($mapel) => [
                'id' => $mapel->idmapel,
                'kode' => $mapel->kode,
                'nama' => $mapel->nama,
            ])->values(),
            'akun' => [
                'username' => $request->user()->username ?: '-',
                'wajib_ganti_password' => (bool) $request->user()->wajib_ganti_password,
            ],
        ], 'Berhasil');
    }

    public function beranda(Request $request): JsonResponse
    {
        try {
            $guru = GuruApiService::pastikanGuru($request->user());
        } catch (DomainException $e) {
            return ApiResponse::fail($e->getMessage(), 403, [], 'GURU_NOT_LINKED');
        }

        return ApiResponse::ok(
            new GuruBerandaResource(BerandaGuruService::beranda($request->user())),
            'Berhasil'
        );
    }
}
