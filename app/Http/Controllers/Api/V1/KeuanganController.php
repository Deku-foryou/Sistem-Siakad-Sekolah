<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PembayaranResource;
use App\Http\Resources\Api\V1\PaymentAttemptResource;
use App\Http\Resources\Api\V1\TagihanResource;
use App\Models\Pembayaran;
use App\Models\PaymentAttempt;
use App\Models\Tagihan;
use App\Services\Payments\PaymentService;
use App\Services\Payments\MidtransGateway;
use App\Services\WalimuridService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
    private function pastikanSiswa(Request $request): ?\App\Models\Siswa
    {
        $user = $request->user();
        if ($user->level === 'siswa') {
            return $user->siswa()->where('status', 'aktif')->first();
        }
        
        if ($user->level === 'wali') {
            $siswaId = $request->input('siswa_id');
            if (!$siswaId) return null;

            $relasi = WalimuridService::relasiWaliAktifSiswa([$siswaId])
                ->where('user_iduser', $user->id)
                ->first();

            if (!$relasi) return null;
            return \App\Models\Siswa::find($siswaId);
        }

        return null;
    }

    public function ringkasan(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $tagihan = Tagihan::where('siswa_idsiswa', $siswa->idsiswa)->get();
        $totalTagihan = $tagihan->sum('total_tagihan');
        $terpakai = $tagihan->sum(fn($t) => PaymentService::terpakaiTagihan($t));
        $saldo = PaymentService::hitungSaldoTersedia($siswa->idsiswa);

        return ApiResponse::ok([
            'total_kewajiban' => $totalTagihan,
            'total_dibayar' => $terpakai,
            'sisa_kewajiban' => max(0, $totalTagihan - $terpakai),
            'saldo' => $saldo,
        ]);
    }

    public function tagihan(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $query = Tagihan::where('siswa_idsiswa', $siswa->idsiswa);
        
        if ($request->has('status')) $query->where('status', $request->status);
        
        $paginator = $query->paginate((int)$request->input('per_page', 15));

        return ApiResponse::ok(TagihanResource::collection($paginator->getCollection()), 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    public function tagihanDetail(Request $request, int $id): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $tagihan = Tagihan::where('idtagihan', $id)->where('siswa_idsiswa', $siswa->idsiswa)->firstOrFail();
        
        return ApiResponse::ok(new TagihanResource($tagihan));
    }

    public function saldo(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        return ApiResponse::ok(['saldo' => PaymentService::hitungSaldoTersedia($siswa->idsiswa)]);
    }

    public function pembayaran(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $paginator = Pembayaran::where('siswa_idsiswa', $siswa->idsiswa)
            ->latest()
            ->paginate((int)$request->input('per_page', 15));

        return ApiResponse::ok(PembayaranResource::collection($paginator->getCollection()), 'Berhasil', 200, $this->paginationMeta($paginator));
    }

    public function buatAttempt(Request $request): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $tagihan = Tagihan::where('idtagihan', $request->tagihan_id)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->firstOrFail();

        try {
            $gateway = app(MidtransGateway::class);
            $user = $request->user();
            
            $attempt = PaymentService::buatAttempt(
                $tagihan,
                $user->level === 'wali' ? PaymentService::PAYER_WALI : PaymentService::PAYER_SISWA,
                $user,
                null,
                $gateway
            );

            return ApiResponse::ok(new PaymentAttemptResource($attempt), 'Pembayaran dibuat.', 201);
        } catch (\Exception $e) {
            return ApiResponse::fail('Gateway pembayaran tidak tersedia saat ini.', 503);
        }
    }

    public function attemptStatus(Request $request, int $id): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $attempt = PaymentAttempt::where('idpayment_attempt', $id)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->firstOrFail();
            
        return ApiResponse::ok(new PaymentAttemptResource($attempt));
    }

    public function pembayaranDetail(Request $request, int $id): JsonResponse
    {
        $siswa = $this->pastikanSiswa($request);
        if (!$siswa) return ApiResponse::fail('Akses ditolak atau data tidak ditemukan.', 403);

        $pembayaran = Pembayaran::where('idpembayaran', $id)
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->firstOrFail();
            
        return ApiResponse::ok(new PembayaranResource($pembayaran));
    }
    
    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}
