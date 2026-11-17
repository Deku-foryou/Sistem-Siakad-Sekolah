<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\NotifikasiIndexRequest;
use App\Http\Resources\Api\V1\NotifikasiResource;
use App\Services\NotifikasiService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API notifikasi untuk wali murid.
 * Ownership selalu dari user login (penerima) — tidak ada parameter user dari request.
 */
class WaliNotifikasiController extends \App\Http\Controllers\Controller
{
    private function pastikanWali(Request $request): bool
    {
        $user = $request->user();

        return $user && $user->level === 'wali' && $user->status_akun === 'aktif';
    }

    private function gagalWali(): JsonResponse
    {
        return ApiResponse::fail('Akun wali tidak aktif. Hubungi admin sekolah.', 403, [], 'WALI_NOT_ACTIVE');
    }

    public function index(NotifikasiIndexRequest $request): JsonResponse
    {
        if (! $this->pastikanWali($request)) {
            return $this->gagalWali();
        }

        $user = $request->user();
        $status = $request->input('status');
        $jenis = $request->input('jenis');
        $perPage = (int) $request->input('per_page', 15);
        $tab = $status === 'read' ? 'read' : ($status === 'unread' ? 'unread' : null);

        $paginator = NotifikasiService::daftar($user, $jenis, $tab, $perPage);

        return ApiResponse::ok([
            'items' => NotifikasiResource::collection($paginator->getCollection()),
            'belum_dibaca' => NotifikasiService::hitungBelumDibaca($user),
        ], 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        if (! $this->pastikanWali($request)) {
            return $this->gagalWali();
        }

        return ApiResponse::ok([
            'belum_dibaca' => NotifikasiService::hitungBelumDibaca($request->user()),
        ], 'Berhasil');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $this->pastikanWali($request)) {
            return $this->gagalWali();
        }

        try {
            $notifikasi = NotifikasiService::detailUntukUser($request->user(), $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::fail('Notifikasi tidak ditemukan.', 404, [], 'NOTIFIKASI_NOT_FOUND');
        }

        return ApiResponse::ok(new NotifikasiResource($notifikasi), 'Berhasil');
    }

    public function read(Request $request, int $id): JsonResponse
    {
        if (! $this->pastikanWali($request)) {
            return $this->gagalWali();
        }

        $user = $request->user();
        $ditemukan = NotifikasiService::tandaiDibaca($user, $id);

        if (! $ditemukan) {
            return ApiResponse::fail('Notifikasi tidak ditemukan.', 404, [], 'NOTIFIKASI_NOT_FOUND');
        }

        $penerima = \App\Models\NotifikasiPenerima::query()
            ->where('notifikasi_idnotifikasi', $id)
            ->where('user_iduser', $user->id)
            ->first();

        return ApiResponse::ok([
            'id' => $id,
            'dibaca' => true,
            'dibaca_pada' => $penerima?->dibaca_pada?->toIso8601String(),
        ], 'Notifikasi ditandai sudah dibaca.');
    }

    public function readAll(Request $request): JsonResponse
    {
        if (! $this->pastikanWali($request)) {
            return $this->gagalWali();
        }

        $jumlah = NotifikasiService::tandaiSemuaDibaca($request->user());

        return ApiResponse::ok([
            'ditandai' => $jumlah,
            'belum_dibaca' => NotifikasiService::hitungBelumDibaca($request->user()),
        ], $jumlah.' notifikasi ditandai sudah dibaca.');
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
