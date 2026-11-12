<?php

namespace App\Http\Controllers;

use App\Models\RefundDokumen;
use App\Models\Walimurid;
use App\Services\Keuangan\RefundDokumenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundDokumenDownloadController extends Controller
{
    /**
     * Unduh PDF dokumen refund.
     *
     * Otorisasi mengikuti pola kuitansi: admin/superadmin boleh;
     * siswa hanya untuk transaksi miliknya; wali hanya relasi terverifikasi aktif.
     * File diverifikasi checksumnya sebelum dikirim.
     */
    public function dokumen(Request $request, int $id): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $dokumen = RefundDokumen::with('refund.pembayaran')->findOrFail($id);
        $refund = $dokumen->refund;
        $pembayaran = $refund?->pembayaran;

        abort_unless($refund && $pembayaran, 404);

        if ($user->level === 'superadmin' || $user->level === 'admin') {
            // allowed
        } elseif ($user->level === 'siswa') {
            $siswa = $user->siswa()->first();
            abort_unless($siswa && $siswa->idsiswa === $pembayaran->siswa_idsiswa, 403);
        } elseif ($user->level === 'wali') {
            $hasAccess = Walimurid::query()
                ->where('user_iduser', $user->id)
                ->where('siswa_idsiswa', $pembayaran->siswa_idsiswa)
                ->where('status_hubungan', 'verified')
                ->whereNull('tanggal_berhenti')
                ->exists();
            abort_unless($hasAccess, 403);
        } else {
            abort(403);
        }

        $fullPath = storage_path('app/private/'.$dokumen->file_path);
        if (! is_file($fullPath)) {
            abort(404);
        }

        $this->pastikanDalamFolder($fullPath);

        if (! RefundDokumenService::verifikasiIntegritas($dokumen)) {
            \Illuminate\Support\Facades\Log::critical('checksum PDF dokumen refund tidak cocok', [
                'refund_dokumen_id' => $dokumen->id,
                'nomor_dokumen' => $dokumen->nomor_dokumen,
            ]);
            abort(500, 'Integritas dokumen tidak dapat diverifikasi.');
        }

        $nama = 'Refund-'.str_replace(['/', '\\'], '-', $dokumen->nomor_dokumen).'.pdf';

        return response()->download($fullPath, $nama);
    }

    private function pastikanDalamFolder(string $path): void
    {
        $directory = realpath(storage_path('app/private'));
        $realPath = realpath($path);

        if ($directory === false || $realPath === false || strncmp($realPath, $directory, strlen($directory)) !== 0) {
            abort(404);
        }
    }
}
