<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Walimurid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PembayaranManualDownloadController extends Controller
{
    public function kuitansi(Request $request, int $idpembayaran): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $pembayaran = Pembayaran::with(['siswa', 'tagihan', 'kuitansi'])->findOrFail($idpembayaran);
        $kuitansi = $pembayaran->kuitansi;

        abort_unless($kuitansi, 404);

        // Authorization
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

        $fullPath = storage_path('app/private/'.$kuitansi->file_path);
        if (! file_exists($fullPath)) {
            abort(404);
        }

        $this->pastikanDalamFolder($fullPath);

        return response()->download($fullPath, 'Kuitansi-'.$kuitansi->nomor.'.pdf');
    }

    public function bukti(Request $request, int $idpembayaran): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $pembayaran = Pembayaran::with(['siswa'])->findOrFail($idpembayaran);
        abort_unless($pembayaran->bukti_path, 404);

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

        $fullPath = storage_path('app/private/'.$pembayaran->bukti_path);
        if (! file_exists($fullPath)) {
            abort(404);
        }

        $this->pastikanDalamFolder($fullPath);

        $nama = $pembayaran->bukti_nama_asli ?: basename($fullPath);

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
