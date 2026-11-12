<?php

namespace App\Services\Keuangan;

use App\Models\Kuitansi;
use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class KuitansiService
{
    public static function snapshotSekolah(): array
    {
        return [
            'nama' => config('app.name', 'SIAKAD'),
            'alamat' => null,
            'telepon' => null,
            'email' => null,
            'diambil_pada' => now()->toISOString(),
        ];
    }

    public static function generateNomor(int $tahun): array
    {
        return DB::transaction(function () use ($tahun) {
            $bulan = (int) now()->format('m');
            // Nomor urut reset setiap tahun
            $last = Kuitansi::query()
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->orderByDesc('nomor_urut')
                ->first();

            $urut = $last ? ((int) $last->nomor_urut + 1) : 1;
            $nomor = sprintf('PAY/%d/%02d/%06d', $tahun, $bulan, $urut);

            return [
                'nomor' => $nomor,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'nomor_urut' => $urut,
            ];
        });
    }

    public static function buatDariPembayaran(Pembayaran $pembayaran, ?Authenticatable $pembuat = null): Kuitansi
    {
        if ($pembayaran->status !== 'success') {
            throw new \DomainException('Kuitansi hanya dapat dibuat untuk pembayaran terverifikasi.');
        }

        $existing = Kuitansi::where('pembayaran_idpembayaran', $pembayaran->idpembayaran)->first();
        if ($existing) {
            return $existing;
        }

        $pembayaran->loadMissing(['tagihan', 'siswa']);

        $tahun = now()->year;
        $nomorInfo = self::generateNomor($tahun);
        $snapshotSekolah = self::snapshotSekolah();

        $snapshotKuitansi = [
            'nomor' => $nomorInfo['nomor'],
            'tanggal' => $pembayaran->diverifikasi_pada?->format('Y-m-d') ?? $pembayaran->dibayar_pada?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'siswa_nama' => $pembayaran->siswa?->nama,
            'siswa_nis' => $pembayaran->siswa?->nis,
            'tagihan_jenis' => $pembayaran->tagihan?->jenis,
            'tagihan_periode' => $pembayaran->tagihan?->periode,
            'tagihan_nama' => $pembayaran->tagihan?->nama,
            'nominal' => (int) $pembayaran->nominal_dibayar,
            'metode' => $pembayaran->metode,
            'nama_pembayar' => $pembayaran->nama_pembayar ?? $pembayaran->payer_nama,
            'referensi' => $pembayaran->referensi ?? $pembayaran->provider_ref ?? $pembayaran->order_id,
            'verifikator_id' => $pembayaran->diverifikasi_oleh,
            'diverifikasi_pada' => $pembayaran->diverifikasi_pada?->toISOString(),
        ];

        $pdf = Pdf::loadView('pdf.kuitansi', [
            'kuitansi' => $snapshotKuitansi,
            'sekolah' => $snapshotSekolah,
            'pembayaran' => $pembayaran,
        ]);

        $filename = 'kuitansi-'.str_replace(['/', '\\'], '-', $nomorInfo['nomor']).'.pdf';
        $relativePath = 'pembayaran/kuitansi/'.$filename;
        $fullPath = storage_path('app/private/'.$relativePath);
        @mkdir(dirname($fullPath), 0755, true);
        file_put_contents($fullPath, $pdf->output());

        $checksum = hash_file('sha256', $fullPath);
        $ukuran = filesize($fullPath);

        return Kuitansi::create([
            'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
            'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
            'tagihan_idtagihan' => $pembayaran->tagihan_idtagihan,
            'nomor' => $nomorInfo['nomor'],
            'tahun' => $nomorInfo['tahun'],
            'bulan' => $nomorInfo['bulan'],
            'nomor_urut' => $nomorInfo['nomor_urut'],
            'snapshot_sekolah' => $snapshotSekolah,
            'snapshot_kuitansi' => $snapshotKuitansi,
            'file_path' => $relativePath,
            'checksum_sha256' => $checksum,
            'ukuran_file' => $ukuran,
            'dibuat_oleh' => $pembuat?->id ?? $pembayaran->diverifikasi_oleh,
        ]);
    }
}
