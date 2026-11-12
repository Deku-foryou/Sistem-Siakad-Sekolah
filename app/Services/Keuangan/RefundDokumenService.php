<?php

namespace App\Services\Keuangan;

use App\Models\Kuitansi;
use App\Models\RefundDokumen;
use App\Models\RefundPengajuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Dokumen resmi (PDF) untuk refund yang BERHASIL diproses.
 *
 * Aturan:
 * - PDF dibuat hanya setelah refund terproses dan reversal ledger tersimpan.
 *   Fungsi ini TIDAK PERNAH membuat reversal/refund baru — aman dipanggil ulang.
 * - Idempotent: refund yang sudah punya dokumen mengembalikan dokumen yang ada.
 *   generateUlang() adalah jalur resmi untuk membuat dokumen yang belum ada
 *   (mis. pembuatan PDF gagal saat proses) tanpa mengulang reversal.
 * - Snapshot diambil saat terbit dan disimpan; dokumen lama tidak berubah
 *   meski identitas sekolah / data terkait berubah.
 * - File disimpan di private storage; checksum SHA256 + ukuran dicatat.
 */
class RefundDokumenService
{
    /** Snapshot identitas sekolah — sama sumbernya dengan kuitansi (konsisten). */
    public static function snapshotSekolah(): array
    {
        return KuitansiService::snapshotSekolah();
    }

    /**
     * Buat dokumen refund (idempotent).
     *
     * @return RefundDokumen Dokumen yang sudah ada atau baru dibuat.
     */
    public static function buat(RefundPengajuan $refund): RefundDokumen
    {
        $refund = self::muatTerproses($refund);

        $dokumen = RefundDokumen::where('refund_pengajuan_id', $refund->id)->first();
        if ($dokumen) {
            return self::pastikanFile($dokumen, $refund);
        }

        return self::terbitkan($refund, null);
    }

    /**
     * Generate ulang dokumen secara idempotent berdasarkan refund yang sudah berhasil.
     * Tidak membuat reversal / refund / alokasi baru dalam keadaan apa pun.
     */
    public static function generateUlang(RefundPengajuan $refund, ?Authenticatable $aktor = null): RefundDokumen
    {
        $refund = self::muatTerproses($refund);

        $dokumen = RefundDokumen::where('refund_pengajuan_id', $refund->id)->first();
        if ($dokumen) {
            return self::pastikanFile($dokumen, $refund);
        }

        return self::terbitkan($refund, $aktor);
    }

    /**
     * Verifikasi integritas file PDF terhadap checksum tercatat.
     */
    public static function verifikasiIntegritas(RefundDokumen $dokumen): bool
    {
        $fullPath = storage_path('app/private/'.$dokumen->file_path);
        if (! is_file($fullPath)) {
            return false;
        }

        return hash_file('sha256', $fullPath) === $dokumen->checksum_sha256;
    }

    private static function muatTerproses(RefundPengajuan $refund): RefundPengajuan
    {
        $refund = RefundPengajuan::query()
            ->with(['pembayaran.siswa', 'pembayaran.tagihan', 'pengaju', 'pemberiPersetujuan', 'pemroses'])
            ->findOrFail($refund->id);

        if ($refund->status !== 'terproses') {
            throw new \DomainException('Dokumen refund hanya dapat dibuat untuk refund yang sudah terproses.');
        }

        if (! $refund->nomor_dokumen) {
            throw new \DomainException('Refund terproses tanpa nomor dokumen; hubungi superadmin.');
        }

        return $refund;
    }

    /**
     * Terbitkan dokumen baru (hanya dipanggil bila dokumen belum ada).
     */
    private static function terbitkan(RefundPengajuan $refund, ?Authenticatable $aktor): RefundDokumen
    {
        $snapshotSekolah = self::snapshotSekolah();
        $snapshotDokumen = self::snapshotDokumen($refund);

        $pdf = Pdf::loadView('pdf.refund', [
            'dokumen' => $snapshotDokumen,
            'sekolah' => $snapshotSekolah,
        ]);

        $nomorBerkas = str_replace(['/', '\\'], '-', $refund->nomor_dokumen);
        $relativePath = 'pembayaran/refund/refund-'.$nomorBerkas.'.pdf';
        $fullPath = storage_path('app/private/'.$relativePath);
        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        file_put_contents($fullPath, $pdf->output());

        $checksum = hash_file('sha256', $fullPath);
        $ukuran = filesize($fullPath);

        try {
            return RefundDokumen::create([
                'refund_pengajuan_id' => $refund->id,
                'nomor_dokumen' => $refund->nomor_dokumen,
                'file_path' => $relativePath,
                'checksum_sha256' => $checksum,
                'ukuran_file' => $ukuran,
                'snapshot_sekolah' => $snapshotSekolah,
                'snapshot_dokumen' => $snapshotDokumen,
                'dibuat_oleh' => $aktor?->id ?? $refund->diproses_oleh,
            ]);
        } catch (QueryException $e) {
            // Balapan pembuatan dokumen untuk refund yang sama: ambil yang menang.
            $existing = RefundDokumen::where('refund_pengajuan_id', $refund->id)->first();
            if ($existing) {
                if (is_file($fullPath) && $existing->file_path !== $relativePath) {
                    @unlink($fullPath);
                }

                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Pastikan file dokumen ada. Bila baris ada tetapi file hilang, tulis ulang
     * dari snapshot tersimpan (konten identik — nomor & snapshot tidak berubah).
     */
    private static function pastikanFile(RefundDokumen $dokumen, RefundPengajuan $refund): RefundDokumen
    {
        $fullPath = storage_path('app/private/'.$dokumen->file_path);
        if (is_file($fullPath)) {
            return $dokumen;
        }

        $snapshotSekolah = (array) ($dokumen->snapshot_sekolah ?: self::snapshotSekolah());
        $snapshotDokumen = (array) ($dokumen->snapshot_dokumen ?: self::snapshotDokumen($refund));

        $pdf = Pdf::loadView('pdf.refund', [
            'dokumen' => $snapshotDokumen,
            'sekolah' => $snapshotSekolah,
        ]);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        file_put_contents($fullPath, $pdf->output());

        $dokumen->checksum_sha256 = hash_file('sha256', $fullPath);
        $dokumen->ukuran_file = filesize($fullPath);
        $dokumen->save();

        Log::warning('file PDF refund hilang; ditulis ulang dari snapshot', [
            'refund_dokumen_id' => $dokumen->id,
            'nomor_dokumen' => $dokumen->nomor_dokumen,
        ]);

        return $dokumen;
    }

    /**
     * Snapshot dokumen — disimpan saat terbit; tidak bergantung pada query ulang.
     */
    private static function snapshotDokumen(RefundPengajuan $refund): array
    {
        $pembayaran = $refund->pembayaran;
        $siswa = $pembayaran?->siswa;
        $tagihan = $pembayaran?->tagihan;

        $tagihanLabel = $tagihan
            ? strtoupper((string) $tagihan->jenis).' '.($tagihan->nama ?: $tagihan->periode)
            : null;

        $referensiProvider = $pembayaran?->provider_ref
            ?? $pembayaran?->order_id
            ?? $pembayaran?->referensi
            ?? null;

        return [
            'nomor' => $refund->nomor_dokumen,
            'tanggal' => ($refund->diproses_pada ?? now())->format('Y-m-d H:i:s'),
            'nominal_refund' => (int) $refund->nominal_refund,
            'metode_refund' => (string) $refund->metode,
            'alasan' => (string) $refund->alasan,
            'referensi_refund' => $refund->ref_provider ?: $refund->referensi,
            'siswa_idsiswa' => (int) $refund->siswa_idsiswa,
            'siswa_nama' => $siswa?->nama,
            'siswa_nis' => $siswa?->nis,
            'pembayaran_id' => (int) $refund->pembayaran_idpembayaran,
            'pembayaran_nominal' => (int) ($pembayaran?->nominal_dibayar ?? 0),
            'pembayaran_metode' => (string) ($pembayaran?->metode ?? ''),
            'pembayaran_provider' => (string) ($pembayaran?->provider ?? ''),
            'pembayaran_order_id' => $pembayaran?->order_id,
            'pembayaran_provider_ref' => $pembayaran?->provider_ref,
            'pembayaran_dibayar_pada' => $pembayaran?->dibayar_pada?->format('Y-m-d H:i:s'),
            'referensi_provider' => $referensiProvider,
            'tagihan_label' => $tagihanLabel,
            'tagihan_idtagihan' => $pembayaran?->tagihan_idtagihan,
            'pengaju_id' => $refund->diajukan_oleh,
            'pengaju_nama' => $refund->pengaju?->name,
            'persetujuan_id' => $refund->disetujui_oleh,
            'persetujuan_nama' => $refund->pemberiPersetujuan?->name,
            'persetujuan_pada' => $refund->disetujui_pada?->format('Y-m-d H:i:s'),
            'pemroses_id' => $refund->diproses_oleh,
            'pemroses_nama' => $refund->pemroses?->name,
            'diproses_pada' => $refund->diproses_pada?->format('Y-m-d H:i:s'),
        ];
    }
}
