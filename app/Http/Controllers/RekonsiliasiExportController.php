<?php

namespace App\Http\Controllers;

use App\Models\RekonsiliasiHasil;
use App\Models\RekonsiliasiTemuan;
use App\Services\Keuangan\RekonsiliasiKeuanganService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV hasil rekonsiliasi (read-only).
 *
 * - Hanya admin & superadmin (backend authorization di setiap aksi).
 * - Export mengikuti filter (kategori/status) dan run terpilih.
 * - Anti formula-injection: nilai diawali = + - @ (atau TAB) di-prefix dengan satu tanda kutip.
 * - File tidak disimpan di direktori publik; dikirim sebagai stream download.
 */
class RekonsiliasiExportController extends Controller
{
    public function ringkasan(Request $request, int $id): StreamedResponse
    {
        $this->assertAdmin();

        $hasil = RekonsiliasiHasil::with('pembuat')->findOrFail($id);
        $ringkasan = RekonsiliasiKeuanganService::ringkasanExport($hasil);
        $dijalankan = $hasil->dijalankan_pada?->format('d/m/Y H:i') ?? '-';
        $tanggal = $hasil->dijalankan_pada?->format('YmdHis') ?? now()->format('YmdHis');

        $baris = [
            ['Rekonsiliasi Keuangan — Ringkasan Hasil'],
            ['Run', '#'.$hasil->id],
            ['Dijalankan pada', $dijalankan],
            ['Dijalankan oleh', $hasil->pembuat?->name ?? '-'],
            ['Periode', $hasil->periode ?: 'Semua'],
            [],
            ['Item', 'Nilai'],
            ['Jumlah pemeriksaan', $hasil->jumlah_pemeriksaan],
            ['Jumlah temuan (tersimpan)', $hasil->jumlah_temuan],
            ['Jumlah temuan (ditemukan)', $ringkasan['total_ditemukan']],
            ['Jumlah kritis', $hasil->jumlah_kritis],
            [],
            ['Kategori', 'Jumlah ditemukan'],
        ];

        foreach ($ringkasan['per_kategori'] as $kategori => $jumlah) {
            $baris[] = [RekonsiliasiKeuanganService::labelKategori($kategori), $jumlah];
        }

        if (! empty($ringkasan['terpotong'])) {
            $baris[] = [];
            $baris[] = ['PERINGATAN: hasil kategori berikut terpotong (batas 500 temuan)'];
            foreach ($ringkasan['terpotong'] as $kategori => $total) {
                $baris[] = [RekonsiliasiKeuanganService::labelKategori($kategori), 'Total sebenarnya '.$total.' (hanya '.min(500, (int) $total).' disimpan)'];
            }
        }

        return $this->csv($baris, "rekonsiliasi-ringkasan-run{$hasil->id}-{$tanggal}.csv");
    }

    public function temuan(Request $request, int $id): StreamedResponse
    {
        $this->assertAdmin();

        $hasil = RekonsiliasiHasil::with('pembuat')->findOrFail($id);

        $query = RekonsiliasiTemuan::query()
            ->where('rekonsiliasi_hasil_id', $hasil->id)
            ->with(['siswa', 'tagihan']);

        // Filter mengikuti yang dipilih di halaman (query string, tidak wajib)
        if (($kategori = trim((string) $request->query('kategori', ''))) !== '') {
            $query->where('kategori', $kategori);
        }

        if (($status = trim((string) $request->query('status', ''))) !== '') {
            $query->where('status', $status);
        }

        $tanggal = $hasil->dijalankan_pada?->format('YmdHis') ?? now()->format('YmdHis');
        $namaBerkas = "rekonsiliasi-temuan-run{$hasil->id}-{$tanggal}.csv";

        $header = ['No', 'Kategori', 'Tingkat', 'Referensi', 'Siswa', 'NIS', 'Tagihan', 'Periode Tagihan', 'Selisih (Rp)', 'Keterangan', 'Waktu Pemeriksaan', 'Status'];

        $callback = function () use ($query, $header, $hasil) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map([$this, 'cell'], $header));

            $no = 0;
            $query->chunk(200, function ($rows) use ($out, &$no, $hasil) {
                $dijalankan = $hasil->dijalankan_pada?->format('d/m/Y H:i') ?? '-';
                foreach ($rows as $row) {
                    $no++;
                    $baris = [
                        $no,
                        RekonsiliasiKeuanganService::labelKategori($row->kategori),
                        $row->status === 'kritis' ? 'Kritis' : ($row->status === 'perlu_ditinjau' ? 'Perlu ditinjau' : ($row->status === 'info' ? 'Info' : $row->status)),
                        ($row->referensi_tipe ?? '-').'#'.($row->referensi_id ?? '-'),
                        $row->siswa?->nama ?? '-',
                        $row->siswa?->nis ?? '-',
                        $row->tagihan ? strtoupper($row->tagihan->jenis).' '.$row->tagihan->periode : '-',
                        $row->tagihan?->periode ?? '-',
                        $row->nominal_selisih !== null ? (int) $row->nominal_selisih : '-',
                        $row->keterangan,
                        $dijalankan,
                        $row->status,
                    ];
                    fputcsv($out, array_map([$this, 'cell'], $baris));
                }
            });

            fclose($out);
        };

        return response()->streamDownload($callback, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Frontend-safe CSV cell: prefix formula characters dengan satu tanda kutip. */
    private function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value === '') {
            return '';
        }

        // Anti formula injection: =, +, -, @, TAB, CR
        $first = $value[0];
        if ($first === '=' || $first === '+' || $first === '-' || $first === '@' || $first === "\t" || $first === "\r") {
            return "'".$value;
        }

        return $value;
    }

    /** @param  array<int, array<int, mixed>>  $baris */
    private function csv(array $baris, string $filename): StreamedResponse
    {
        $callback = function () use ($baris) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel membuka UTF-8 dengan benar
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($baris as $line) {
                fputcsv($out, array_map([$this, 'cell'], $line));
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function assertAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }
}
