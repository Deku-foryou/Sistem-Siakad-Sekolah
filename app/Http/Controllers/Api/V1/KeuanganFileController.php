<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Pembayaran;
use App\Models\RefundDokumen;
use App\Models\Walimurid;
use App\Services\Keuangan\RefundDokumenService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * API unduh dokumen keuangan privat (siswa & wali).
 *
 * Aturan:
 * - Otorisasi: siswa hanya dokumen miliknya; wali hanya untuk anak dengan relasi
 *   verified tanpa tanggal_berhenti. Keduanya diperiksa server-side.
 * - File diambil dari private storage; path traversal ditolak (realpath compare).
 * - Tidak mengembalikan absolute filesystem path ke client.
 * - Dokumen refund diverifikasi checksum SHA-256-nya sebelum dikirim (konsisten web).
 * - Error selalu JSON (403/404); sukses mengembalikan file.
 */
class KeuanganFileController extends \App\Http\Controllers\Controller
{
    /**
     * GET /api/v1/keuangan/pembayaran/{id}/bukti — bukti pembayaran manual (bila ada).
     */
    public function bukti(Request $request, int $idpembayaran): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $pembayaran = Pembayaran::with(['siswa'])->findOrFail($idpembayaran);

        if (! $pembayaran->bukti_path) {
            return ApiResponse::fail('Bukti pembayaran tidak tersedia.', 404, [], 'BUKTI_NOT_FOUND');
        }

        $penolakan = $this->tolakBilaBukanAkses($request, $pembayaran->siswa_idsiswa);
        if ($penolakan !== null) {
            return $penolakan;
        }

        $fullPath = storage_path('app/private/'.$pembayaran->bukti_path);
        if (! is_file($fullPath) || ! $this->pastikanDalamFolder($fullPath)) {
            return ApiResponse::fail('Bukti pembayaran tidak ditemukan.', 404, [], 'BUKTI_NOT_FOUND');
        }

        $nama = $pembayaran->bukti_nama_asli ?: basename($fullPath);

        return response()->download($fullPath, $nama);
    }

    /**
     * GET /api/v1/keuangan/pembayaran/{id}/kuitansi — kuitansi resmi pembayaran.
     */
    public function kuitansi(Request $request, int $idpembayaran): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $pembayaran = Pembayaran::with(['siswa', 'kuitansi'])->findOrFail($idpembayaran);
        $kuitansi = $pembayaran->kuitansi;

        if (! $kuitansi) {
            return ApiResponse::fail('Kuitansi tidak tersedia.', 404, [], 'KUITANSI_NOT_FOUND');
        }

        $penolakan = $this->tolakBilaBukanAkses($request, $pembayaran->siswa_idsiswa);
        if ($penolakan !== null) {
            return $penolakan;
        }

        $fullPath = storage_path('app/private/'.$kuitansi->file_path);
        if (! is_file($fullPath) || ! $this->pastikanDalamFolder($fullPath)) {
            return ApiResponse::fail('Kuitansi tidak ditemukan.', 404, [], 'KUITANSI_NOT_FOUND');
        }

        $namaAman = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $kuitansi->nomor) ?: 'kuitansi';

        return response()->download($fullPath, 'Kuitansi-'.$namaAman.'.pdf');
    }

    /**
     * GET /api/v1/keuangan/refund/dokumen/{id} — PDF dokumen refund.
     * Checksum SHA-256 diverifikasi sebelum dikirim.
     */
    public function dokumen(Request $request, int $id): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $dokumen = RefundDokumen::with('refund.pembayaran')->findOrFail($id);
        $refund = $dokumen->refund;
        $pembayaran = $refund?->pembayaran;

        if (! $refund || ! $pembayaran) {
            return ApiResponse::fail('Dokumen refund tidak tersedia.', 404, [], 'DOKUMEN_NOT_FOUND');
        }

        $penolakan = $this->tolakBilaBukanAkses($request, $pembayaran->siswa_idsiswa);
        if ($penolakan !== null) {
            return $penolakan;
        }

        $fullPath = storage_path('app/private/'.$dokumen->file_path);
        if (! is_file($fullPath) || ! $this->pastikanDalamFolder($fullPath)) {
            return ApiResponse::fail('Dokumen refund tidak ditemukan.', 404, [], 'DOKUMEN_NOT_FOUND');
        }

        if (! RefundDokumenService::verifikasiIntegritas($dokumen)) {
            Log::critical('checksum PDF dokumen refund tidak cocok', [
                'refund_dokumen_id' => $dokumen->id,
                'nomor_dokumen' => $dokumen->nomor_dokumen,
            ]);

            return ApiResponse::fail('Integritas dokumen tidak dapat diverifikasi.', 500, [], 'DOKUMEN_INTEGRITAS');
        }

        $nama = 'Refund-'.str_replace(['/', '\\'], '-', (string) $dokumen->nomor_dokumen).'.pdf';

        return response()->download($fullPath, $nama);
    }

    /**
     * Return null bila request boleh mengakses dokumen anak, JsonResponse error bila tidak.
     */
    private function tolakBilaBukanAkses(Request $request, int $siswaId): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::fail('Belum terautentikasi atau token tidak valid.', 401, [], 'UNAUTHENTICATED');
        }

        if ($user->level === 'siswa') {
            $siswa = $user->siswa()->first();

            return ($siswa && (int) $siswa->idsiswa === (int) $siswaId)
                ? null
                : ApiResponse::fail('Anda tidak berhak mengakses dokumen ini.', 403, [], 'FORBIDDEN');
        }

        if ($user->level === 'wali') {
            $akses = Walimurid::query()
                ->where('user_iduser', $user->id)
                ->where('siswa_idsiswa', $siswaId)
                ->where('status_hubungan', 'verified')
                ->whereNull('tanggal_berhenti')
                ->exists();

            return $akses
                ? null
                : ApiResponse::fail('Anda tidak berhak mengakses dokumen ini.', 403, [], 'FORBIDDEN');
        }

        // Role lain (admin/superadmin) tidak memperoleh akses via API mobile.
        return ApiResponse::fail('Akses ditolak untuk role ini.', 403, [], 'ROLE_NOT_ALLOWED');
    }

    private function pastikanDalamFolder(string $path): bool
    {
        $directory = realpath(storage_path('app/private'));
        $realPath = realpath($path);

        if ($directory === false || $realPath === false) {
            return false;
        }

        return strncmp($realPath, $directory, strlen($directory)) === 0;
    }
}
