<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\GuruJadwalIndexRequest;
use App\Http\Resources\Api\V1\GuruJadwalResource;
use App\Services\GuruApiService;
use App\Services\KonteksAkademikService;
use App\Support\ApiResponse;
use App\Support\Hari;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API jadwal mengajar guru (kanonis: jadwalkelas + jamhari).
 * Hanya jadwal milik guru yang terautentikasi; guru_id dari request tidak pernah dipakai.
 */
class GuruJadwalController extends \App\Http\Controllers\Controller
{
    private function pastikanGuru(Request $request)
    {
        try {
            return GuruApiService::pastikanGuru($request->user());
        } catch (DomainException) {
            return null;
        }
    }

    public function index(GuruJadwalIndexRequest $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();

        // tanggal > hari > semua hari (tanggal menang bila keduanya diisi).
        $hari = null;
        $tanggal = $request->input('tanggal');

        if ($tanggal) {
            $hari = (int) \Carbon\Carbon::parse($tanggal)->dayOfWeekIso;
        } elseif ($request->input('hari')) {
            $hari = (int) $request->input('hari');
        }

        $items = app(\App\Services\JadwalSiswaService::class)->urutkan(
            GuruApiService::jadwalGuru($guru, $konteks, $hari)
        );

        return ApiResponse::ok([
            'konteks' => GuruApiService::konteksPayload($konteks),
            'filter' => [
                'tanggal' => $tanggal ?: null,
                'hari' => $hari,
                'nama_hari' => $hari !== null ? Hari::nama($hari) : null,
            ],
            'jadwal' => GuruJadwalResource::collection($items),
        ], 'Berhasil');
    }

    public function hariIni(Request $request): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();
        $tanggal = now()->format('Y-m-d');
        $hari = now()->dayOfWeekIso;

        $items = app(\App\Services\JadwalSiswaService::class)->urutkan(
            GuruApiService::jadwalGuru($guru, $konteks, $hari)
        );

        return ApiResponse::ok([
            'tanggal' => $tanggal,
            'hari' => $hari,
            'nama_hari' => Hari::nama($hari),
            'konteks' => GuruApiService::konteksPayload($konteks),
            'jadwal' => GuruJadwalResource::collection($items),
        ], 'Berhasil');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $guru = $this->pastikanGuru($request);
        if (! $guru) {
            return $this->gagalGuru();
        }

        $konteks = KonteksAkademikService::ambil();

        $jadwal = GuruApiService::jadwalGuru($guru, $konteks)
            ->firstWhere('idjadwalkelas', $id);

        if (! $jadwal) {
            return ApiResponse::fail('Jadwal tidak ditemukan.', 404, [], 'JADWAL_NOT_FOUND');
        }

        return ApiResponse::ok(new GuruJadwalResource($jadwal), 'Berhasil');
    }

    private function gagalGuru(): JsonResponse
    {
        return ApiResponse::fail('Akun guru ini belum terhubung ke data guru atau nonaktif. Hubungi administrator.', 403, [], 'GURU_NOT_LINKED');
    }
}
