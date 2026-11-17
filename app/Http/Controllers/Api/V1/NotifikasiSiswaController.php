<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\NotifikasiIndexRequest;
use App\Http\Resources\Api\V1\NotifikasiResource;
use App\Services\NotifikasiService;
use App\Services\RaporSiswaService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API notifikasi untuk siswa.
 * Ownership selalu dari user login (penerima); tidak ada parameter user dari request.
 * Detail tidak otomatis menandai dibaca (konsisten dengan website: tombol "Tandai dibaca").
 */
class NotifikasiSiswaController extends \App\Http\Controllers\Controller
{
    private function pastikanSiswa(Request $request): ?\App\Models\Siswa
    {
        try {
            return RaporSiswaService::pastikanSiswa($request->user());
        } catch (DomainException $e) {
            return null;
        }
    }

    public function index(NotifikasiIndexRequest $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
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
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        return ApiResponse::ok([
            'belum_dibaca' => NotifikasiService::hitungBelumDibaca($request->user()),
        ], 'Berhasil');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
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
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
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
        $siswa = $this->pastikanSiswa($request);
        if (! $siswa) {
            return ApiResponse::fail('Akun ini belum terhubung ke data siswa. Hubungi administrator.', 403, [], 'SISWA_NOT_LINKED');
        }

        // Update tunggal ber-scope user; transaksi implisit dalam satu statement.
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
