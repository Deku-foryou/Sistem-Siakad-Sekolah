<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Keuangan\TagihanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Terbitkan tagihan SPP bulanan untuk seluruh siswa aktif.
 *
 * Prinsip:
 * - Idempotent: siswa + SPP + periode yang sudah memiliki tagihan tidak dibuat ulang.
 * - Hanya siswa dengan status 'aktif'.
 * - Hanya periode sesuai tanggal berjalan (bila dipanggil tanpa argumen).
 * - Tagihan yang sudah diterbitkan tidak diubah.
 */
class SppTerbit extends Command
{
    protected $signature = 'spp:terbit {periode? : Periode YYYY-MM (default: bulan berjalan)}';

    protected $description = 'Terbitkan tagihan SPP bulanan untuk seluruh siswa aktif (idempotent).';

    public function handle(): int
    {
        $periode = (string) ($this->argument('periode') ?: now()->format('Y-m'));

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $this->error('Format periode tidak valid. Gunakan YYYY-MM.');

            return self::FAILURE;
        }

        $aktif = Siswa::where('status', 'aktif')->count();
        if ($aktif === 0) {
            $this->info('Tidak ada siswa aktif. Tidak ada tagihan yang dibuat.');
            Log::info('spp:terbit: tidak ada siswa aktif', ['periode' => $periode]);

            return self::SUCCESS;
        }

        $hasil = TagihanService::terbitSppBulanan($periode);
        $tanpaTarif = (int) ($hasil['tanpa_tarif'] ?? 0);

        $this->info("Periode {$periode}: {$hasil['dibuat']} dibuat, {$hasil['dilewati']} dilewati, {$hasil['dibebaskan']} dibebaskan, {$tanpaTarif} tanpa tarif (tidak dibuatkan tagihan).");
        Log::info('spp:terbit selesai', [
            'periode' => $periode,
            'dibuat' => $hasil['dibuat'],
            'dilewati' => $hasil['dilewati'],
            'dibebaskan' => $hasil['dibebaskan'],
            'tanpa_tarif' => $tanpaTarif,
        ]);

        return self::SUCCESS;
    }
}
