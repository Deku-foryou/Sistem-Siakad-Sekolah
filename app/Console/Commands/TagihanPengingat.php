<?php

namespace App\Console\Commands;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanPengingat as TagihanPengingatModel;
use App\Models\Walimurid;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pengingat tagihan otomatis: H-7, H-3, H, H+1, lalu setiap 7 hari.
 *
 * Aturan:
 * - Hanya tagihan 'belum_dibayar'/'dibayar_sebagian' dengan sisa > 0.
 * - Berhenti (tidak dikirim) bila tagihan lunas/dibebaskan/dibatalkan.
 * - Dijeda bila ada pembayaran manual (pending/review) yang menutup sisa tagihan.
 * - Penerima: siswa terkait (bila memiliki akun & status aktif) + seluruh wali
 *   dengan relasi status_hubungan = verified dan tanggal_berhenti IS NULL.
 * - Idempotent: unik (tagihan, tahap, penerima); tahapan tersimpan di tagihan_pengingat.
 */
class TagihanPengingat extends Command
{
    protected $signature = 'tagihan:pengingat {tanggal? : Tanggal kirim YYYY-MM-DD (default: hari ini)}';

    protected $description = 'Kirim pengingat tagihan (H-7, H-3, H, H+1, lalu setiap 7 hari) — idempotent.';

    public function handle(): int
    {
        $tanggalArg = $this->argument('tanggal');
        try {
            $hari = $tanggalArg ? \Carbon\Carbon::parse((string) $tanggalArg)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            $this->error('Tanggal tidak valid. Gunakan YYYY-MM-DD.');

            return self::FAILURE;
        }

        $tagihanList = Tagihan::query()
            ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])
            ->with(['siswa'])
            ->get();

        $terkirim = 0;
        $dilewati = 0;
        $dijeda = 0;
        $diproses = 0;

        foreach ($tagihanList as $tagihan) {
            $diproses++;
            $siswa = $tagihan->siswa;
            if (! $siswa || $siswa->status !== 'aktif') {
                $dilewati++;

                continue;
            }

            $sisa = \App\Services\Payments\PaymentService::sisaTagihan($tagihan);
            if ($sisa <= 0) {
                $dilewati++;

                continue;
            }

            // Dijeda bila pembayaran manual pending/review sudah menutup sisa tagihan
            $tertangguh = (int) Pembayaran::query()
                ->where('tagihan_idtagihan', $tagihan->idtagihan)
                ->where('provider', 'manual')
                ->whereIn('status', ['pending', 'review'])
                ->sum('nominal_dibayar');
            if ($tertangguh >= $sisa) {
                $dijeda++;

                continue;
            }

            $diff = (int) $hari->diffInDays(\Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->startOfDay(), false); // + = hari menuju tempo; - = hari setelah tempo
            $tahap = $this->tahapUntuk($diff);
            $tahap = $this->tahapUntuk((int) $diff);
            if ($tahap === null) {
                $dilewati++;

                continue;
            }

            // Kumpulkan penerima: siswa (akun) + wali verified aktif
            $penerimaIds = collect([$siswa->user_iduser])->filter()->push(
                ...Walimurid::query()
                    ->where('siswa_idsiswa', $siswa->idsiswa)
                    ->where('status_hubungan', 'verified')
                    ->whereNull('tanggal_berhenti')
                    ->pluck('user_iduser')
            )->unique()->values()->all();

            foreach ($penerimaIds as $userId) {
                $sudahAda = TagihanPengingatModel::query()
                    ->where('tagihan_idtagihan', $tagihan->idtagihan)
                    ->where('tahap', $tahap)
                    ->where('penerima_user_iduser', $userId)
                    ->exists();
                if ($sudahAda) {
                    continue;
                }

                // Kirim notifikasi (best effort per penerima; gagal di satu penerima tidak menggagalkan yang lain)
                $tipe = \App\Models\Notifikasi::JENIS_PENGINGAT_TAGIHAN;
                try {
                    $notifikasi = NotifikasiService::buat([$userId], [
                        'judul' => 'Pengingat tagihan ('.$tahap.')',
                        'isi' => 'Tagihan '.strtoupper($tagihan->jenis).' '.$tagihan->periode.' sebesar Rp '.number_format($sisa, 0, ',', '.').' masih belum lunas. Jatuh tempo: '.\Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->format('d/m/Y').'.',
                        'jenis' => $tipe,
                        'prioritas' => $tahap === 'H+1' || $tahap === 'H' ? 'tinggi' : 'sedang',
                        'sumber_tabel' => 'tagihan',
                        'sumber_id' => $tagihan->idtagihan,
                        'link' => '/bayartagihan',
                    ], \App\Models\User::where('level', 'superadmin')->first() ?? \App\Models\User::where('level', 'admin')->first());
                } catch (\Throwable $e) {
                    Log::warning('tagihan:pengingat gagal membuat notifikasi', [
                        'tagihan_idtagihan' => $tagihan->idtagihan,
                        'tahap' => $tahap,
                        'penerima' => $userId,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }

                TagihanPengingatModel::create([
                    'tagihan_idtagihan' => $tagihan->idtagihan,
                    'tahap' => $tahap,
                    'siswa_idsiswa' => $siswa->idsiswa,
                    'penerima_user_iduser' => $userId,
                    'notifikasi_idnotifikasi' => $notifikasi?->idnotifikasi,
                    'dikirim_pada' => now(),
                ]);
                $terkirim++;
            }
        }

        $this->info("Pengingat diproses: {$diproses} tagihan, terkirim {$terkirim} notifikasi, dilewati {$dilewati}, dijeda (manual pending menutup) {$dijeda}.");
        Log::info('tagihan:pengingat selesai', [
            'tanggal' => $hari->toDateString(),
            'diproses' => $diproses,
            'terkirim' => $terkirim,
            'dilewati' => $dilewati,
            'dijeda' => $dijeda,
        ]);

        return self::SUCCESS;
    }

    /**
     * Tahap pengingat berdasarkan selisih hari ke jatuh tempo (+ sebelum tempo, − setelah tempo).
     * null bila bukan jadwal pengingat.
     */
    private function tahapUntuk(int $diff): ?string
    {
        if ($diff === 7) {
            return 'H-7';
        }
        if ($diff === 3) {
            return 'H-3';
        }
        if ($diff === 0) {
            return 'H';
        }
        $lewat = -$diff; // hari setelah tempo
        if ($lewat >= 1 && ($lewat - 1) % 7 === 0) {
            return 'H+'.$lewat;
        }

        return null;
    }
}
