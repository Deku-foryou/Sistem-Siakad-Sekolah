<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\NotifikasiIndexRequest;
use App\Http\Resources\Api\V1\NotifikasiResource;
use App\Services\GuruApiService;
use App\Services\NotifikasiService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API notifikasi untuk guru.
 * Ownership selalu dari user login (penerima); tidak ada parameter user dari request.
 * Detail tidak otomatis menandai dibaca (konsisten dengan website & API siswa).
 */
class GuruNotifikasiController extends \App\Http\Controllers\Controller
{
    private function pastikanGuru(Request $request): bool
    {
        try {
            GuruApiService::pastikanGuru($request->user());

            return true;
        } catch (DomainException) {
            return false;
        }
    }

    private function gagalGuru(): JsonResponse
    {
        return ApiResponse::fail('Akun guru ini belum terhubung ke data guru atau nonaktif. Hubungi administrator.', 403, [], 'GURU_NOT_LINKED');
    }

    public function index(NotifikasiIndexRequest $request): JsonResponse
    {
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
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
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
        }

        return ApiResponse::ok([
            'belum_dibaca' => NotifikasiService::hitungBelumDibaca($request->user()),
        ], 'Berhasil');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
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
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
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
        if (! $this->pastikanGuru($request)) {
            return $this->gagalGuru();
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
