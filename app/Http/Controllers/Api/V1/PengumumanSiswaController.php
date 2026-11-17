<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\PengumumanIndexRequest;
use App\Http\Resources\Api\V1\PengumumanResource;
use App\Services\PengumumanService;
use App\Services\RaporSiswaService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API pengumuman untuk siswa (read-only).
 * Visibilitas mengikuti PengumumanService::daftarAktif/riwayat/detailUntukSiswa — sama dengan website.
 */
class PengumumanSiswaController extends \App\Http\Controllers\Controller
{
    private function pastikanSiswa(Request $request): ?\App\Models\Siswa
    {
        try {
            return RaporSiswaService::pastikanSiswa($request->user());
        } catch (DomainException $e) {
            return null;
        }
    }

    public function index(PengumumanIndexRequest $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $service = app(PengumumanService::class);
        $tab = $request->input('tab', 'aktif');
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        $paginator = $tab === 'riwayat'
            ? $service->riwayat($siswa->idsiswa, $perPage, true, $page)
            : $service->daftarAktif($siswa->idsiswa, $perPage, true, $page);

        return ApiResponse::ok([
            'tab' => $tab,
            'items' => PengumumanResource::collection($paginator->getCollection()),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        $service = app(PengumumanService::class);

        try {
            $pengumuman = $service->detailUntukSiswa($siswa->idsiswa, $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::fail('Pengumuman tidak ditemukan.', 404, [], 'PENGUMUMAN_NOT_FOUND');
        }

        // Konsisten dengan website (buka detail → tandai dibaca, idempoten).
        $service->tandaiSudahDibaca($siswa->idsiswa, $id);

        // Muat ulang agar `dibaca_pada` yang baru tersimpan tampak di respons.
        $pengumuman = $service->detailUntukSiswa($siswa->idsiswa, $id);

        return ApiResponse::ok(new PengumumanResource($pengumuman), 'Berhasil');
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
