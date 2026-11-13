<?php

namespace App\Services\Keuangan;

use App\Models\KoreksiPengajuan;
use App\Models\Pembayaran;
use App\Models\PembayaranAlokasi;
use App\Models\RefundPengajuan;
use App\Models\Siswa;
use App\Models\Tagihan;
use DomainException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Refund & Koreksi keuangan — reversal engine (bukan edit histori).
 *
 * Aturan:
 * - Tidak ada baris ledger asli yang diubah/dihapus. Efek finansial dibuat via
 *   baris alokasi baru jenis reversal-refund / reversal-koreksi (pengurang).
 * - Biaya uang aktual keluar sekolah hanya tercatat setelah proses selesai
 *   (terproses). Proses dapat gagal → status gagal → tidak ada reversal.
 * - Cegah refund ganda: lockForUpdate pada siswa + pembayaran; pengurangan nominal
 *   refund terproses terhadap sumber dana per pembayaran.
 * - Admin hanya mengajukan & melihat; superadmin menyetujui/menolak + memproses.
 * - Saldo siswa tidak boleh menjadi negatif — diverifikasi SETELAH seluruh reversal + replacement
 *   (post-condition, kebijakan F-1): bila hasil akhir negatif, seluruh transaksi di-rollback.
 *   Replacement mengakreditasi kembali saldo ke siswa atas kelebihan yang masih berhak kepadanya.
 *   Saldo yang sudah dipakai untuk membayar tagihan lain tidak dikembalikan otomatis; lingkupnya
 *   hanya dibalik lewat reversal turunannya yang sah.
 * - Koreksi ditolak secara aman pada pembayaran yang alokasi turunannya tidak dapat ditelusuri:
 *   sudah dikoreksi (koreksi/replacement tersimpan) atau sudah direfund (dana keluar).
 * - Sumber dana refund dihitung per pembayaran:
 *     * alokasi saldo (kelebihan belum terpakai, belum direfund) — dibatasi saldo tersedia siswa;
 *     * alokasi tagihan (belum direfund/dikoreksi) — reversal membuka kembali tagihan.
 *   Konsumsi saldo lebih dulu (saldo = dana kelebihan yang belum mengikat kewajiban);
 *   tagihan dibuka hanya jika refund melebihi sumber saldo.
 *   Refund dari alokasi tagihan TIDAK mensyaratkan saldo siswa mencukupi.
 *   Saldo yang sudah dipakai untuk membayar tagihan lain TIDAK dikembalikan.
 * - Nomor dokumen refund memakai format RF/YYYY/MM/NNNNNN reset tahunan (audit trail).
 * - PDF dokumen refund dibuat setelah reversal tersimpan (RefundDokumenService),
 *   secara idempotent; kegagalan PDF tidak mengulang reversal.
 *
 * Urutan penguncian konsisten (menghindari deadlock):
 *   siswa → pembayaran → pembayaran_alokasi → tagihan → pengajuan.
 */
class KoreksiKeuanganService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_TERPROSES = 'terproses';

    public const STATUS_GAGAL = 'gagal';

    public const REFUND_METODE = ['provider_api', 'transfer_manual', 'tunai', 'lainnya'];

    public const KOREKSI_JENIS = ['nominal', 'alokasi', 'pencatatan_manual', 'hubungan_tagihan'];

    public const REVERSAL_REFUND = 'reversal-refund';

    public const REVERSAL_KOREKSI = 'reversal-koreksi';

    /**
     * Perhitungan refundable per sumber dana untuk satu pembayaran.
     *
     * - sumber tagihan : alokasi tagihan asli − reversal (refund/koreksi) atas tagihan.
     * - sumber saldo   : alokasi saldo asli − reversal atas saldo, dibatasi saldo tersedia siswa.
     *
     * Histori tidak diubah; yang dihitung hanyalah sisa yang sah untuk dikembalikan.
     *
     * @return array{tagihan:int, saldo:int, saldo_alokasi_tersisa:int, saldo_tersedia_siswa:int, total:int}
     */
    public static function refundableSumberDana(Pembayaran $pembayaran): array
    {
        $pembayaranId = $pembayaran->idpembayaran;
        $siswaId = $pembayaran->siswa_idsiswa;

        $alokasiTagihan = (int) PembayaranAlokasi::query()
            ->where('pembayaran_idpembayaran', $pembayaranId)
            ->where('jenis', 'tagihan')
            ->sum('nominal');

        $alokasiSaldo = (int) PembayaranAlokasi::query()
            ->where('pembayaran_idpembayaran', $pembayaranId)
            ->where('jenis', 'saldo')
            ->sum('nominal');

        $reversalTagihan = (int) PembayaranAlokasi::query()
            ->where('pembayaran_idpembayaran', $pembayaranId)
            ->whereIn('jenis', [self::REVERSAL_REFUND, self::REVERSAL_KOREKSI])
            ->whereNotNull('tagihan_idtagihan')
            ->sum('nominal');

        $reversalSaldo = (int) PembayaranAlokasi::query()
            ->where('pembayaran_idpembayaran', $pembayaranId)
            ->whereIn('jenis', [self::REVERSAL_REFUND, self::REVERSAL_KOREKSI])
            ->whereNull('tagihan_idtagihan')
            ->sum('nominal');

        $refundableTagihan = max(0, $alokasiTagihan - $reversalTagihan);
        $saldoAlokasiTersisa = max(0, $alokasiSaldo - $reversalSaldo);

        $saldoRaw = \App\Services\Payments\PaymentService::saldoSiswa($siswaId);
        if ($saldoRaw['saldo'] < 0) {
            Log::error('anomali saldo siswa saat menghitung refundable (ledger)', [
                'siswa_idsiswa' => $siswaId,
                'saldo_masuk' => $saldoRaw['masuk'],
                'saldo_dipakai' => $saldoRaw['dipakai'],
                'reversal' => $saldoRaw['reversal'],
                'saldo' => $saldoRaw['saldo'],
            ]);
        }
        $saldoTersedia = max(0, (int) $saldoRaw['saldo']);

        // Saldo belum terpakai saja yang boleh dikembalikan; yang sudah dipakai membayar tagihan tidak.
        $refundableSaldo = min($saldoAlokasiTersisa, $saldoTersedia);

        return [
            'tagihan' => $refundableTagihan,
            'saldo' => $refundableSaldo,
            'saldo_alokasi_tersisa' => $saldoAlokasiTersisa,
            'saldo_tersedia_siswa' => $saldoTersedia,
            'total' => $refundableTagihan + $refundableSaldo,
        ];
    }

    /**
     * Total maksimal yang dapat direfund untuk satu pembayaran (per sumber dana).
     */
    public static function refundable(Pembayaran $pembayaran): int
    {
        return self::refundableSumberDana($pembayaran)['total'];
    }

    /**
     * Apakah ada pengajuan koreksi aktif (pending/disetujui) pada pembayaran ini?
     */
    private static function adaKoreksiAktif(int $pembayaranId, ?int $kecualiKoreksiId = null): bool
    {
        $q = KoreksiPengajuan::query()
            ->where('pembayaran_idpembayaran', $pembayaranId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DISETUJUI]);
        if ($kecualiKoreksiId !== null) {
            $q->where('id', '!=', $kecualiKoreksiId);
        }

        return $q->exists();
    }

    /**
     * Apakah ada pengajuan refund aktif (pending/disetujui) pada pembayaran ini?
     */
    private static function adaRefundAktif(int $pembayaranId, ?int $kecualiRefundId = null): bool
    {
        $q = RefundPengajuan::query()
            ->where('pembayaran_idpembayaran', $pembayaranId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DISETUJUI]);
        if ($kecualiRefundId !== null) {
            $q->where('id', '!=', $kecualiRefundId);
        }

        return $q->exists();
    }

    /**
     * Pengajuan refund (admin/superadmin).
     */
    public static function ajukanRefund(
        Pembayaran $pembayaran,
        int $nominal,
        string $alasan,
        string $metode,
        ?string $referensi,
        Authenticatable $aktor
    ): RefundPengajuan {
        $aktorLevel = $aktor->level ?? null;
        if (! in_array($aktorLevel, ['superadmin', 'admin'], true)) {
            throw new DomainException('Hanya admin atau superadmin yang dapat mengajukan refund.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan refund wajib diisi.');
        }

        if (! in_array($metode, self::REFUND_METODE, true)) {
            throw new DomainException('Metode refund tidak valid.');
        }

        if ($nominal < 1) {
            throw new DomainException('Nominal refund tidak valid.');
        }

        if (self::adaKoreksiAktif($pembayaran->idpembayaran)) {
            throw new DomainException('Pembayaran ini memiliki pengajuan koreksi aktif; selesaikan koreksi terlebih dahulu.');
        }

        if ($nominal > self::refundable($pembayaran)) {
            throw new DomainException('Nominal refund melebihi sumber dana yang masih dapat dikembalikan (alokasi tagihan + saldo tersedia).');
        }

        return DB::transaction(function () use ($pembayaran, $nominal, $alasan, $metode, $referensi, $aktor) {
            // Urutan kunci konsisten: siswa → pembayaran → pengajuan
            $pembayaranPre = Pembayaran::query()->findOrFail($pembayaran->idpembayaran);
            Siswa::query()->lockForUpdate()->findOrFail($pembayaranPre->siswa_idsiswa);
            $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($pembayaran->idpembayaran);

            if ($pembayaran->status !== 'success') {
                throw new DomainException('Hanya pembayaran terverifikasi yang dapat diajukan refund.');
            }

            if (self::adaKoreksiAktif($pembayaran->idpembayaran)) {
                throw new DomainException('Pembayaran ini memiliki pengajuan koreksi aktif; selesaikan koreksi terlebih dahulu.');
            }

            // Cegah refund aktif ganda pada pembayaran yang sama
            $adaAktif = RefundPengajuan::query()
                ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DISETUJUI])
                ->lockForUpdate()
                ->exists();
            if ($adaAktif) {
                throw new DomainException('Masih ada pengajuan refund aktif untuk pembayaran ini.');
            }

            // Re-cek refundable sumber dana di dalam transaksi (anti drift)
            if ($nominal > self::refundable($pembayaran)) {
                throw new DomainException('Nominal refund melebihi sumber dana yang masih dapat dikembalikan (alokasi tagihan + saldo tersedia).');
            }

            return RefundPengajuan::create([
                'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                'tagihan_idtagihan' => $pembayaran->tagihan_idtagihan,
                'nominal_refund' => $nominal,
                'alasan' => $alasan,
                'metode' => $metode,
                'referensi' => $referensi ?: null,
                'status' => self::STATUS_PENDING,
                'diajukan_oleh' => $aktor->id,
            ]);
        });
    }

    /**
     * Setujui refund (superadmin saja).
     */
    public static function setujuiRefund(RefundPengajuan $refund, Authenticatable $aktor): RefundPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat menyetujui refund.');
        }

        return DB::transaction(function () use ($refund, $aktor) {
            $refund = RefundPengajuan::query()->lockForUpdate()->findOrFail($refund->id);

            if ($refund->status !== self::STATUS_PENDING) {
                throw new DomainException('Refund hanya dapat disetujui dari status menunggu.');
            }

            // Prevent: superadmin menyetujui refund yang diajukannya sendiri
            if ((int) $refund->diajukan_oleh === (int) $aktor->id) {
                throw new DomainException('Superadmin tidak dapat menyetujui pengajuan yang diajukan olehnya sendiri.');
            }

            // Urutan kunci konsisten: siswa → pembayaran → koreksi (cek aktif)
            $pembayaranPre = Pembayaran::query()->findOrFail($refund->pembayaran_idpembayaran);
            Siswa::query()->lockForUpdate()->findOrFail($pembayaranPre->siswa_idsiswa);
            $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($refund->pembayaran_idpembayaran);

            // Koreksi aktif pada pembayaran yang sama memblokir persetujuan refund
            if (self::adaKoreksiAktif($refund->pembayaran_idpembayaran)) {
                throw new DomainException('Pembayaran ini memiliki pengajuan koreksi aktif; selesaikan koreksi terlebih dahulu.');
            }

            // Validasi nominal refundable saat persetujuan (mengantisipasi refund paralel)
            if ($refund->nominal_refund > self::refundable($pembayaran)) {
                throw new DomainException('Nominal refund melebihi sumber dana yang masih dapat dikembalikan (alokasi tagihan + saldo tersedia).');
            }

            $refund->status = self::STATUS_DISETUJUI;
            $refund->disetujui_oleh = $aktor->id;
            $refund->disetujui_pada = now();
            $refund->save();

            return $refund->fresh();
        });
    }

    /**
     * Tolak refund (superadmin saja) — alasan wajib.
     */
    public static function tolakRefund(RefundPengajuan $refund, string $alasan, Authenticatable $aktor): RefundPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat menolak refund.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($refund, $alasan, $aktor) {
            $refund = RefundPengajuan::query()->lockForUpdate()->findOrFail($refund->id);

            if ($refund->status !== self::STATUS_PENDING && $refund->status !== self::STATUS_DISETUJUI) {
                throw new DomainException('Refund hanya dapat ditolak dari status menunggu atau disetujui.');
            }

            $refund->status = self::STATUS_DITOLAK;
            $refund->alasan_ditolak = $alasan;
            $refund->diproses_oleh = $aktor->id;
            $refund->diproses_pada = now();
            $refund->save();

            return $refund->fresh();
        });
    }

    /**
     * Proses refund yang sudah disetujui (transfer_manual/tunai/lainnya).
     *
     * Setelah reversal + refund tersimpan (commit), PDF dokumen dibuat secara
     * idempotent. Kegagalan PDF TIDAK membatalkan reversal dan TIDAK membuat
     * refund kedua; gunakan RefundDokumenService::generateUlang().
     */
    public static function prosesRefundManual(
        RefundPengajuan $refund,
        string $referensi,
        string $catatan,
        Authenticatable $aktor
    ): RefundPengajuan {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat memproses refund.');
        }

        $referensi = trim($referensi);
        if ($referensi === '') {
            throw new DomainException('Referensi proses refund wajib diisi.');
        }

        if ($refund->metode === 'provider_api') {
            throw new DomainException('Refund via provider_api belum tersedia pada integrasi ini. Gunakan transfer_manual/tunai/lainnya.');
        }

        $hasil = DB::transaction(function () use ($refund, $referensi, $catatan, $aktor) {
            $refund = RefundPengajuan::query()->lockForUpdate()->findOrFail($refund->id);

            if ($refund->status === self::STATUS_TERPROSES) {
                // Idempotency: sudah terproses — jangan buat reversal lagi
                return $refund->fresh();
            }

            if (! in_array($refund->status, [self::STATUS_DISETUJUI, self::STATUS_GAGAL], true)) {
                throw new DomainException('Refund hanya dapat diproses dari status disetujui atau gagal.');
            }

            // Urutan kunci konsisten: siswa → pembayaran → koreksi (cek aktif)
            $pembayaranPre = Pembayaran::query()->findOrFail($refund->pembayaran_idpembayaran);
            Siswa::query()->lockForUpdate()->findOrFail($pembayaranPre->siswa_idsiswa);
            $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($refund->pembayaran_idpembayaran);

            // Koreksi aktif pada pembayaran yang sama memblokir proses refund
            if (self::adaKoreksiAktif($refund->pembayaran_idpembayaran)) {
                throw new DomainException('Pembayaran ini memiliki pengajuan koreksi aktif; selesaikan koreksi terlebih dahulu.');
            }

            // Hitung sumber dana SETELAH seluruh lock diambil
            $sumber = self::refundableSumberDana($pembayaran);
            $nominalRefund = (int) $refund->nominal_refund;
            if ($nominalRefund > $sumber['total']) {
                throw new DomainException(sprintf(
                    'Nominal refund melebihi sumber dana yang tersedia (tagihan Rp %s, saldo Rp %s).',
                    number_format((int) $sumber['tagihan'], 0, ',', '.'),
                    number_format((int) $sumber['saldo'], 0, ',', '.')
                ));
            }

            $sisaRefund = $nominalRefund;
            $tagihanTersentuh = [];

            // ---- Sumber 1: alokasi saldo pembayaran ini (kelebihan belum terpakai) ----
            // Dipakai lebih dulu agar kewajiban tagihan siswa tidak terbuka kembali
            // selama masih ada dana kelebihan yang belum dipakai.
            if ($sisaRefund > 0) {
                $ambilSaldo = min($sisaRefund, $sumber['saldo']);
                if ($ambilSaldo > 0) {
                    PembayaranAlokasi::create([
                        'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                        'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                        'tagihan_idtagihan' => null,
                        'nominal' => $ambilSaldo,
                        'jenis' => self::REVERSAL_REFUND,
                        'referensi_tipe' => 'refund_pengajuan',
                        'referensi_id' => $refund->id,
                        'keterangan' => 'Refund pengajuan #'.$refund->id.' — reversal alokasi saldo.',
                    ]);
                    $sisaRefund -= $ambilSaldo;
                }
            }

            // ---- Sumber 2: alokasi tagihan pembayaran ini (belum direfund/dikoreksi) ----
            if ($sisaRefund > 0 && $sumber['tagihan'] > 0) {
                $ambilTagihan = min($sisaRefund, $sumber['tagihan']);
                $sisaDistribusi = $ambilTagihan;

                // Alokasi tagihan asli dari pembayaran ini, grouped per tagihan, urutan stabil
                $alokasiTagihan = PembayaranAlokasi::query()
                    ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                    ->where('jenis', 'tagihan')
                    ->lockForUpdate()
                    ->orderBy('idpembayaran_alokasi')
                    ->get()
                    ->groupBy('tagihan_idtagihan');

                $reversalPerTagihan = PembayaranAlokasi::query()
                    ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                    ->whereIn('jenis', [self::REVERSAL_REFUND, self::REVERSAL_KOREKSI])
                    ->whereNotNull('tagihan_idtagihan')
                    ->selectRaw('tagihan_idtagihan, SUM(nominal) as total')
                    ->groupBy('tagihan_idtagihan')
                    ->pluck('total', 'tagihan_idtagihan');

                foreach ($alokasiTagihan as $tagihanId => $rows) {
                    if ($sisaDistribusi <= 0) {
                        break;
                    }
                    $alokasiSum = (int) $rows->sum('nominal');
                    $reversalSum = (int) ($reversalPerTagihan[$tagihanId] ?? 0);
                    $available = max(0, $alokasiSum - $reversalSum);
                    $pakai = min($sisaDistribusi, $available);
                    if ($pakai <= 0) {
                        continue;
                    }
                    PembayaranAlokasi::create([
                        'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                        'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                        'tagihan_idtagihan' => (int) $tagihanId,
                        'nominal' => $pakai,
                        'jenis' => self::REVERSAL_REFUND,
                        'referensi_tipe' => 'refund_pengajuan',
                        'referensi_id' => $refund->id,
                        'keterangan' => 'Refund pengajuan #'.$refund->id.' — reversal alokasi tagihan.',
                    ]);
                    $sisaDistribusi -= $pakai;
                    $sisaRefund -= $pakai;
                    $tagihanTersentuh[] = (int) $tagihanId;
                }

                if ($sisaDistribusi !== 0) {
                    // Ketidakcocokan agregat vs distribusi — gagal demi integritas
                    throw new DomainException('Perhitungan reversal tagihan tidak konsisten; refund dibatalkan.');
                }
            }

            // ---- Sumber 3 (sudah dicakup): sisa yang tidak tercakup harus 0 ----
            if ($sisaRefund !== 0) {
                // Seharusnya tidak tercapai karena validasi nominalRefund <= sumber['total']
                throw new DomainException('Sumber dana refund tidak mencukupi; refund dibatalkan.');
            }

            // Verifikasi pasca-reversal: saldo siswa tidak boleh negatif
            $saldoSesudah = \App\Services\Payments\PaymentService::saldoSiswa($pembayaran->siswa_idsiswa);
            if ($saldoSesudah['saldo'] < 0) {
                Log::error('refund ditolak oleh guard saldo negatif pasca-reversal', [
                    'refund_pengajuan_id' => $refund->id,
                    'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                    'saldo' => $saldoSesudah['saldo'],
                ]);
                throw new DomainException('Refund dibatalkan: saldo siswa akan menjadi negatif.');
            }

            // Recompute status tagihan yang terkena reversal
            foreach (array_unique($tagihanTersentuh) as $tid) {
                $tag = Tagihan::query()->lockForUpdate()->find($tid);
                if ($tag) {
                    self::recomputeTagihan($tag);
                }
            }

            // Nomor dokumen refund (idempotent bila sudah pernah ada)
            if (! $refund->nomor_dokumen) {
                $refund->nomor_dokumen = self::generateNomorDokumen(now()->year);
            }

            $refund->status = self::STATUS_TERPROSES;
            $refund->ref_provider = $referensi;
            $refund->catatan_proses = $catatan;
            $refund->diproses_oleh = $aktor->id;
            $refund->diproses_pada = now();
            if (! $refund->disetujui_oleh) {
                $refund->disetujui_oleh = $aktor->id;
                $refund->disetujui_pada = now();
            }

            try {
                $refund->save();
            } catch (QueryException $e) {
                // Nomor dokumen unik — bentrok; retry oleh pengguna (tidak ada reversal baru yang tercatat karena rollback)
                throw new DomainException('Gagal menyimpan nomor dokumen refund yang unik; silakan proses ulang.');
            }

            Log::info('refund terproses', [
                'refund_pengajuan_id' => $refund->id,
                'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                'nominal_refund' => $nominalRefund,
                'sumber_tagihan' => $sumber['tagihan'],
                'sumber_saldo' => $sumber['saldo'],
                'nomor_dokumen' => $refund->nomor_dokumen,
                'oleh' => $aktor->id,
            ]);

            return $refund->fresh();
        });

        // PDF dibuat SETELAH reversal tersimpan — kegagalan PDF tidak mengulang reversal
        try {
            RefundDokumenService::buat($hasil->fresh());
        } catch (\Throwable $e) {
            Log::error('PDF dokumen refund gagal dibuat (refund tetap terproses; gunakan generate ulang)', [
                'refund_pengajuan_id' => $hasil->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $hasil->fresh(['dokumen']);
    }

    /**
     * Pengajuan koreksi (admin/superadmin). Snapshot sebelum koreksi disimpan.
     * Koreksi diizinkan untuk pembayaran online (midtrans), manual, saldo, dan replacement koreksi.
     * Koreksi hanya memengaruhi ledger internal — status settlement/transaksi Midtrans tidak disentuh.
     */
    public static function ajukanKoreksi(
        Pembayaran $pembayaran,
        string $jenis,
        string $alasan,
        array $usulan,
        Authenticatable $aktor
    ): KoreksiPengajuan {
        $aktorLevel = $aktor->level ?? null;
        if (! in_array($aktorLevel, ['superadmin', 'admin'], true)) {
            throw new DomainException('Hanya admin atau superadmin yang dapat mengajukan koreksi.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan koreksi wajib diisi.');
        }

        if (! in_array($jenis, self::KOREKSI_JENIS, true)) {
            throw new DomainException('Jenis koreksi tidak valid.');
        }

        $usulanNorm = self::normalisasiUsulan($jenis, $usulan);

        return DB::transaction(function () use ($pembayaran, $jenis, $alasan, $usulanNorm, $aktor) {
            // Urutan kunci konsisten: siswa → pembayaran → alokasi → pengajuan
            $pembayaranPre = Pembayaran::query()->findOrFail($pembayaran->idpembayaran);
            Siswa::query()->lockForUpdate()->findOrFail($pembayaranPre->siswa_idsiswa);
            $pembayaran = Pembayaran::query()->lockForUpdate()->with('alokasi')->findOrFail($pembayaran->idpembayaran);

            if ($pembayaran->status !== 'success') {
                throw new DomainException('Hanya pembayaran terverifikasi yang dapat dikoreksi.');
            }

            if (self::adaRefundAktif($pembayaran->idpembayaran)) {
                throw new DomainException('Pembayaran ini memiliki pengajuan refund aktif; selesaikan refund terlebih dahulu.');
            }

            $adaKoreksiTerproses = KoreksiPengajuan::query()
                ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                ->where('status', self::STATUS_TERPROSES)
                ->lockForUpdate()
                ->exists();
            if ($adaKoreksiTerproses) {
                throw new DomainException('Pembayaran ini sudah dikoreksi (replacement tersimpan); koreksi ulang harus diajukan pada pembayaran pengganti.');
            }

            $adaRefundTerproses = RefundPengajuan::query()
                ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                ->where('status', self::STATUS_TERPROSES)
                ->lockForUpdate()
                ->exists();
            if ($adaRefundTerproses) {
                throw new DomainException('Pembayaran ini sudah direfund; koreksi tidak dapat diterapkan pada transaksi yang dananya sudah dikembalikan.');
            }

            $adaAktif = KoreksiPengajuan::query()
                ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DISETUJUI])
                ->lockForUpdate()
                ->exists();
            if ($adaAktif) {
                throw new DomainException('Masih ada pengajuan koreksi aktif untuk pembayaran ini.');
            }

            $snapshot = [
                'pembayaran' => $pembayaran->getAttributes(),
                'alokasi' => $pembayaran->alokasi->map(fn ($a) => $a->getAttributes())->all(),
                'sumber' => [
                    'provider' => $pembayaran->provider,
                    'metode' => $pembayaran->metode,
                    'nominal_dibayar' => (int) $pembayaran->nominal_dibayar,
                    'order_id' => $pembayaran->order_id,
                    'provider_ref' => $pembayaran->provider_ref,
                    'payment_attempt_id' => $pembayaran->payment_attempt_id,
                    'referensi' => $pembayaran->referensi,
                    'payer_jenis' => $pembayaran->payer_jenis,
                    'payer_nama' => $pembayaran->payer_nama,
                    'nama_pembayar' => $pembayaran->nama_pembayar,
                    'dibayar_pada' => $pembayaran->dibayar_pada?->toISOString(),
                    'tanggal_pembayaran' => $pembayaran->tanggal_pembayaran?->format('Y-m-d'),
                ],
            ];

            return KoreksiPengajuan::create(array_merge($usulanNorm, [
                'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                'tagihan_idtagihan' => $pembayaran->tagihan_idtagihan,
                'jenis' => $jenis,
                'alasan' => $alasan,
                'snapshot_sebelum' => $snapshot,
                'status' => self::STATUS_PENDING,
                'diajukan_oleh' => $aktor->id,
            ]));
        });
    }

    private static function normalisasiUsulan(string $jenis, array $usulan): array
    {
        $out = [];
        switch ($jenis) {
            case 'nominal':
                $out['nominal_usulan'] = max(0, (int) ($usulan['nominal_usulan'] ?? 0));
                if ($out['nominal_usulan'] < 1) {
                    throw new DomainException('Nominal usulan (baru) wajib diisi dan > 0.');
                }
                break;
            case 'alokasi':
                // Tidak perlu field tambahan — dialokasikan ulang penuh; alasan menjelaskan sisanya
                $out['nominal_usulan'] = null;
                break;
            case 'pencatatan_manual':
                $out['metode_usulan'] = isset($usulan['metode_usulan']) ? trim((string) $usulan['metode_usulan']) : null;
                $out['nama_pembayar_usulan'] = isset($usulan['nama_pembayar_usulan']) ? trim((string) $usulan['nama_pembayar_usulan']) : null;
                $out['tanggal_pembayaran_usulan'] = isset($usulan['tanggal_pembayaran_usulan']) ? $usulan['tanggal_pembayaran_usulan'] : null;
                if (! $out['metode_usulan'] && ! $out['nama_pembayar_usulan'] && ! $out['tanggal_pembayaran_usulan']) {
                    throw new DomainException('Usulan koreksi harus mengisi minimal satu field pencatatan (metode/pembayar/tanggal).');
                }
                break;
            case 'hubungan_tagihan':
                $out['tagihan_pengganti_idtagihan'] = (int) ($usulan['tagihan_pengganti_idtagihan'] ?? 0) ?: null;
                if (! $out['tagihan_pengganti_idtagihan']) {
                    throw new DomainException('Tagihan pengganti wajib diisi untuk koreksi hubungan tagihan.');
                }
                break;
        }

        return $out;
    }

    /**
     * Setujui koreksi sekaligus terapkan (reversal + replacement) — superadmin saja.
     * Idempotent: koreksi yang sudah terproses mengembalikan fresh tanpa mengulang reversal.
     */
    public static function setujuiKoreksi(KoreksiPengajuan $koreksi, Authenticatable $aktor): KoreksiPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat menyetujui koreksi.');
        }

        return DB::transaction(function () use ($koreksi, $aktor) {
            $koreksi = KoreksiPengajuan::query()->lockForUpdate()->findOrFail($koreksi->id);

            if ($koreksi->status === self::STATUS_TERPROSES) {
                // Idempotency: sudah diterapkan — jangan buat reversal/replacement lagi
                Log::info('koreksi sudah terproses; tidak diterapkan ulang', ['koreksi_pengajuan_id' => $koreksi->id]);

                return $koreksi->fresh();
            }

            if ($koreksi->status !== self::STATUS_PENDING) {
                throw new DomainException('Koreksi hanya dapat disetujui dari status menunggu.');
            }

            if ((int) $koreksi->diajukan_oleh === (int) $aktor->id) {
                throw new DomainException('Superadmin tidak dapat menyetujui pengajuan koreksi yang diajukannya sendiri.');
            }

            // Urutan kunci konsisten: siswa → pembayaran → alokasi → tagihan
            $pembayaranPre = Pembayaran::query()->findOrFail($koreksi->pembayaran_idpembayaran);
            Siswa::query()->lockForUpdate()->findOrFail($pembayaranPre->siswa_idsiswa);
            $pembayaran = Pembayaran::query()->lockForUpdate()->with('alokasi')->findOrFail($koreksi->pembayaran_idpembayaran);

            // Refund aktif pada pembayaran yang sama memblokir koreksi
            if (self::adaRefundAktif($koreksi->pembayaran_idpembayaran)) {
                throw new DomainException('Pembayaran ini memiliki pengajuan refund aktif; selesaikan refund terlebih dahulu.');
            }

            // Integritas alokasi turunan: pembayaran yang sudah dikoreksi TIDAK dapat dikoreksi ulang
            // (koreksi kedua harus diajukan pada pembayaran pengganti; mencegah reversal ganda).
            $adaKoreksiTerproses = KoreksiPengajuan::query()
                ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                ->where('status', self::STATUS_TERPROSES)
                ->lockForUpdate()
                ->exists();
            if ($adaKoreksiTerproses) {
                throw new DomainException('Pembayaran ini sudah dikoreksi (replacement tersimpan); koreksi ulang harus diajukan pada pembayaran pengganti.');
            }

            // Integritas: pembayaran yang dananya sudah direfund TIDAK dapat dikoreksi lagi
            // (reversal alokasi akan menggandakan pembalikan dan merusak saldo/tagihan).
            $adaRefundTerproses = RefundPengajuan::query()
                ->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)
                ->where('status', self::STATUS_TERPROSES)
                ->lockForUpdate()
                ->exists();
            if ($adaRefundTerproses) {
                throw new DomainException('Pembayaran ini sudah direfund; koreksi tidak dapat diterapkan pada transaksi yang dananya sudah dikembalikan.');
            }

            foreach ($pembayaran->alokasi as $al) {
                // Hanya alokasi uang masuk ('tagihan'/'saldo') yang dibalik — jangan pernah
                // membalik baris reversal yang sudah ada (mencegah double-posting).
                if (! in_array($al->jenis, ['tagihan', 'saldo'], true)) {
                    continue;
                }
                PembayaranAlokasi::create([
                    'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                    'siswa_idsiswa' => $al->siswa_idsiswa,
                    'tagihan_idtagihan' => $al->tagihan_idtagihan,
                    'nominal' => $al->nominal,
                    'jenis' => self::REVERSAL_KOREKSI,
                    'referensi_tipe' => 'koreksi_pengajuan',
                    'referensi_id' => $koreksi->id,
                    'keterangan' => 'Koreksi pengajuan #'.$koreksi->id.' — reversal alokasi asli.',
                ]);
            }

            $replacementPembayaran = Pembayaran::create([
                'tagihan_idtagihan' => $koreksi->tagihan_pengganti_idtagihan ?: $pembayaran->tagihan_idtagihan,
                'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                'nominal_dibayar' => $koreksi->nominal_usulan ?: $pembayaran->nominal_dibayar,
                'biaya_gateway' => 0,
                'metode' => $koreksi->metode_usulan ?: $pembayaran->metode,
                'provider' => 'koreksi',
                'order_id' => null,
                'referensi' => 'KOREKSI-'.$koreksi->id,
                'status' => 'success',
                'dibayar_pada' => $koreksi->tanggal_pembayaran_usulan ?? $pembayaran->dibayar_pada,
                'tanggal_pembayaran' => $koreksi->tanggal_pembayaran_usulan ?? $pembayaran->tanggal_pembayaran,
                'diinput_oleh' => $aktor->id,
                'diverifikasi_oleh' => $aktor->id,
                'diverifikasi_pada' => now(),
                'payer_jenis' => 'admin',
                'payer_nama' => $koreksi->nama_pembayar_usulan ?? $pembayaran->payer_nama ?? $pembayaran->siswa?->nama,
                'payer_user_id' => $aktor->id,
                'nama_pembayar' => $koreksi->nama_pembayar_usulan ?? $pembayaran->nama_pembayar,
                'catatan' => 'Replacement dari koreksi pengajuan #'.$koreksi->id,
            ]);

            \App\Services\Payments\PaymentService::postingPembayaranSetelahVerifikasi($replacementPembayaran);

            $tagihanIds = [$koreksi->tagihan_idtagihan, $koreksi->tagihan_pengganti_idtagihan, $pembayaran->tagihan_idtagihan];
            foreach (array_filter(array_unique($tagihanIds)) as $tid) {
                if (! $tid) {
                    continue;
                }
                $tag = Tagihan::query()->lockForUpdate()->find($tid);
                if ($tag) {
                    self::recomputeTagihan($tag);
                }
            }

            // Post-condition: saldo akhir siswa SETELAH seluruh reversal + replacement tidak boleh negatif.
            // Kebijakan F-1 (audit pasca-implementasi): ditentukan di akhir alih-alih sebelum reversal,
            // karena replacement mengembalikan sebagian kelebihan saldo ke siswa. Bila negatif, rollback total.
            $saldoAkhir = \App\Services\Payments\PaymentService::saldoSiswa($pembayaran->siswa_idsiswa);
            if ($saldoAkhir['saldo'] < 0) {
                Log::error('koreksi dibatalkan oleh post-condition saldo negatif', [
                    'koreksi_pengajuan_id' => $koreksi->id,
                    'pembayaran_asli_id' => $pembayaran->idpembayaran,
                    'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                    'saldo_masuk' => $saldoAkhir['masuk'],
                    'saldo_dipakai' => $saldoAkhir['dipakai'],
                    'reversal' => $saldoAkhir['reversal'],
                    'saldo_akhir' => $saldoAkhir['saldo'],
                ]);
                throw new DomainException(sprintf('Koreksi dibatalkan: hasil akhir akan membuat saldo siswa negatif (saldo akhir -Rp %s).', number_format((int) abs($saldoAkhir['saldo']), 0, ',', '.')));
            }

            $koreksi->status = self::STATUS_TERPROSES;
            $koreksi->disetujui_oleh = $aktor->id;
            $koreksi->disetujui_pada = now();
            $koreksi->diproses_oleh = $aktor->id;
            $koreksi->diproses_pada = now();
            $koreksi->save();

            Log::info('koreksi keuangan diterapkan', [
                'koreksi_pengajuan_id' => $koreksi->id,
                'pembayaran_asli_id' => $pembayaran->idpembayaran,
                'pembayaran_replacement_id' => $replacementPembayaran->idpembayaran,
                'oleh' => $aktor->id,
            ]);

            return $koreksi->fresh();
        });
    }

    public static function tolakKoreksi(KoreksiPengajuan $koreksi, string $alasan, Authenticatable $aktor): KoreksiPengajuan
    {
        $aktorLevel = $aktor->level ?? null;
        if ($aktorLevel !== 'superadmin') {
            throw new DomainException('Hanya superadmin yang dapat menolak koreksi.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($koreksi, $alasan, $aktor) {
            $koreksi = KoreksiPengajuan::query()->lockForUpdate()->findOrFail($koreksi->id);

            if ($koreksi->status !== self::STATUS_PENDING && $koreksi->status !== self::STATUS_DISETUJUI) {
                throw new DomainException('Koreksi hanya dapat ditolak dari status menunggu atau disetujui.');
            }

            $koreksi->status = self::STATUS_DITOLAK;
            $koreksi->alasan_ditolak = $alasan;
            $koreksi->diproses_oleh = $aktor->id;
            $koreksi->diproses_pada = now();
            $koreksi->save();

            return $koreksi->fresh();
        });
    }

    public static function recomputeTagihan(Tagihan $tagihan): void
    {
        $terpakai = \App\Services\Payments\PaymentService::terpakaiTagihan($tagihan);

        if ($terpakai >= (int) $tagihan->total_tagihan && $tagihan->total_tagihan > 0) {
            $tagihan->status = 'lunas';
        } elseif ($terpakai > 0) {
            $tagihan->status = 'dibayar_sebagian';
        } else {
            $tagihan->status = 'belum_dibayar';
        }

        $tagihan->save();
    }

    /**
     * Nomor dokumen refund format RF/YYYY/MM/NNNNNN — urut reset tahunan.
     * Uniqueness dijamin oleh unique index nomor_dokumen; bentrok dilempar sebagai DomainException.
     */
    public static function generateNomorDokumen(int $tahun): string
    {
        $bulan = (int) now()->format('m');
        $last = RefundPengajuan::query()
            ->where('nomor_dokumen', 'like', 'RF/'.$tahun.'/%')
            ->whereNotNull('nomor_dokumen')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $urut = 1;
        if ($last && $last->nomor_dokumen) {
            $parts = explode('/', $last->nomor_dokumen);
            $urut = ((int) end($parts)) + 1;
        }

        return sprintf('RF/%d/%02d/%06d', $tahun, $bulan, $urut);
    }
}
