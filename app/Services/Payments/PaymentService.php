<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;
use App\Models\Pembayaran;
use App\Models\PembayaranAlokasi;
use App\Models\Siswa;
use App\Models\Tagihan;
use DomainException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrasi pembayaran online: attempt -> notifikasi (webhook/API) -> ledger -> alokasi.
 *
 * Aturan kebenaran:
 * - Attempt/instruksi TIDAK mengurangi saldo tagihan.
 * - Hanya transaksi TERVERIFIKASI (webhook dengan signature valid + cek server-side,
 *   atau pemeriksaan status server-side oleh scheduler/perintah) yang menulis ledger.
 * - Ledger (pembayaran) ditulis maksimal satu baris per attempt (unik payment_attempt_id).
 * - Alokasi mengurangi sisa tagihan; kelebihan masuk alokasi 'saldo' siswa.
 * - Pembayaran gagal/pending/expired tidak menyentuh tagihan.
 * - Gagal/pending/expired attempt TIDAK menghapus histori (status + timestamps tetap ada).
 * - Semua nominal integer rupiah.
 */
class PaymentService
{
    public const PAYER_SISWA = 'siswa';

    public const PAYER_WALI = 'wali';

    public const PAYER_KETIGA = 'ketiga';

    public const PAYER_ADMIN = 'admin';

    // ------------------------------------------------------------------ Attempts

    /**
     * Buat attempt baru (Snap token) untuk satu tagihan.
     *
     * @param  'siswa'|'wali'|'ketiga'  $payerJenis
     * @param  int|null  $payerUserId  user id pemrakarsa (null untuk pihak ketiga via tautan publik)
     * @param  string|null  $payerNama  nama tampilan pembayar; wajib untuk pihak ketiga
     */
    public static function buatAttempt(
        Tagihan $tagihan,
        string $payerJenis,
        ?Authenticatable $aktor,
        ?string $payerNama,
        PaymentGatewayInterface $gateway
    ): PaymentAttempt {
        $actorId = $aktor?->id;

        return DB::transaction(function () use ($tagihan, $payerJenis, $actorId, $payerNama, $gateway) {
            /** @var Tagihan $tagihan */
            $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->idtagihan);

            $sisa = self::sisaTagihan($tagihan);
            if ($sisa <= 0 || ! in_array($tagihan->status, ['belum_dibayar', 'dibayar_sebagian'], true)) {
                throw new DomainException('Tagihan ini tidak dapat dibayar (sudah lunas, dibebaskan, atau dibatalkan).');
            }

            if ($payerJenis === self::PAYER_KETIGA && trim((string) $payerNama) === '') {
                throw new DomainException('Nama pembayar (pihak ketiga) wajib diisi.');
            }

            $orderId = self::generateOrderId($tagihan->idtagihan);

            $attempt = PaymentAttempt::create([
                'siswa_idsiswa' => $tagihan->siswa_idsiswa,
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'order_id' => $orderId,
                'public_token' => Str::random(48),
                'provider' => $gateway->provider(),
                'nominal' => $sisa,
                'biaya_gateway' => null, // jujur: tidak diketahui sebelum checkout; tidak dikarang
                'status' => 'created',
                'payer_user_id' => $actorId,
                'payer_jenis' => $payerJenis,
                'payer_nama' => $payerNama ?: ($aktor?->name ?? null),
            ]);

            try {
                $snap = $gateway->createSnapTransaction([
                    'order_id' => $orderId,
                    'gross_amount' => $sisa,
                    'first_name' => $payerNama ?: ($aktor?->name ?? null),
                    'email' => $aktor?->email ?? null,
                    'item_id' => (string) $tagihan->idtagihan,
                    'item_name' => self::itemName($tagihan),
                ]);
            } catch (PaymentGatewayException $e) {
                // Attempt tercatat gagal — histori tidak dihapus; transaksi TIDAK dapat dibayar.
                $attempt->status = 'failed';
                $attempt->payload_response = ['error' => $e->getMessage()];
                $attempt->save();

                throw $e;
            }

            $attempt->snap_token = $snap['token'];
            $attempt->snap_redirect_url = $snap['redirect_url'] ?? null;
            $attempt->payload_request = [
                'order_id' => $orderId,
                'gross_amount' => $sisa,
            ];
            $attempt->expired_pada = now()->addDay(); // fallback; Snap default 24 jam
            $attempt->save();

            return $attempt->refresh();
        });
    }

    private static function generateOrderId(int $tagihanId): string
    {
        do {
            $orderId = 'ORD-'.$tagihanId.'-'.now()->format('ymdHis').'-'.strtoupper(Str::random(6));
        } while (PaymentAttempt::where('order_id', $orderId)->exists());

        return $orderId;
    }

    private static function itemName(Tagihan $tagihan): string
    {
        $jenis = $tagihan->jenis === 'spp' ? 'SPP' : ucfirst($tagihan->jenis);

        return $tagihan->nama ?: ($jenis.' '.$tagihan->periode);
    }

    // ------------------------------------------------------------------ Sisa tagihan

    /**
     * Sisa tagihan = total_tagihan - (alokasi tagihan - reversal alokasi tagihan).
     * Reversal (reversal-refund/reversal-koreksi) mengurangi terpakai; histori asli tidak diubah.
     */
    public static function sisaTagihan(Tagihan $tagihan): int
    {
        return max(0, (int) $tagihan->total_tagihan - self::terpakaiTagihan($tagihan));
    }

    /** Terpakai = SUM(jenis='tagihan') - SUM(jenis reversal yang mengacu tagihan ini). */
    public static function terpakaiTagihan(Tagihan $tagihan): int
    {
        $masuk = (int) PembayaranAlokasi::query()
            ->where('tagihan_idtagihan', $tagihan->idtagihan)
            ->where('jenis', 'tagihan')
            ->sum('nominal');

        $reversal = (int) PembayaranAlokasi::query()
            ->where('tagihan_idtagihan', $tagihan->idtagihan)
            ->whereIn('jenis', ['reversal-refund', 'reversal-koreksi'])
            ->sum('nominal');

        return max(0, $masuk - $reversal);
    }

    /**
     * Saldo tersedia siswa.
     *
     * Masuk = alokasi jenis 'saldo' tanpa tagihan_idtagihan (kelebihan pembayaran).
     * Dipakai = alokasi jenis 'tagihan' yang berasal dari pembayaran provider 'saldo'.
     * Penggunaan saldo tidak mengubah atau menghapus transaksi keuangan yang sudah diposting;
     * pemakaian dicatat sebagai pembayaran baru ber-provider 'saldo'.
     *
     * Integritas: bila hasil negatif, itu MENANDAKAN ANOMALI LEDGER (mis. alokasi
     * saldo diposting ganda atau saldo masuk tidak tercatat). Kondisi itu TIDAK
     * disembunyikan — dicatat pada log error dan fungsi mengembalikan 0
     * (siswa tidak dapat menggunakan saldo yang tidak tersedia).
     * Histori yang sudah diposting tidak diubah.
     */
    public static function hitungSaldoTersedia(int $siswaId): int
    {
        $mentah = self::saldoSiswa($siswaId);

        if ($mentah['saldo'] < 0) {
            Log::error('anomali saldo siswa (ledger): hasil negatif', [
                'siswa_idsiswa' => $siswaId,
                'saldo_masuk' => $mentah['masuk'],
                'saldo_dipakai' => $mentah['dipakai'],
                'saldo' => $mentah['saldo'],
            ]);

            return 0;
        }

        return $mentah['saldo'];
    }

    /**
     * Detail saldo tanpa penanganan masker — digunakan untuk audit/log dan
     * memastikan perhitungan 'hitungSaldoTersedia' tidak menyembunyikan anomali.
     * Reversal (reversal-refund/reversal-koreksi) mengurangi saldo.
     *
     * @return array{masuk:int, dipakai:int, reversal:int, saldo:int}
     */
    public static function saldoSiswa(int $siswaId): array
    {
        $masuk = (int) PembayaranAlokasi::query()
            ->where('siswa_idsiswa', $siswaId)
            ->where('jenis', 'saldo')
            ->whereNull('tagihan_idtagihan')
            ->sum('nominal');

        $dipakai = (int) PembayaranAlokasi::query()
            ->where('siswa_idsiswa', $siswaId)
            ->where('jenis', 'tagihan')
            ->whereHas('pembayaran', function ($q) {
                $q->where('provider', 'saldo');
            })
            ->sum('nominal');

        $reversal = (int) PembayaranAlokasi::query()
            ->where('siswa_idsiswa', $siswaId)
            ->whereIn('jenis', ['reversal-refund', 'reversal-koreksi'])
            ->whereNull('tagihan_idtagihan')
            ->sum('nominal');

        return ['masuk' => $masuk, 'dipakai' => $dipakai, 'reversal' => $reversal, 'saldo' => $masuk - $dipakai - $reversal];
    }

    // ------------------------------------------------------------------ Webhook & status

    /**
     * Proses notifikasi Midtrans (webhook).
     *
     * @return 'success'|'pending'|'failed'|'expired'|'ignored'|'duplicate'|'invalid'|'not_found'|'unverified'|'error'
     */
    public static function prosesNotifikasi(array $payload, PaymentGatewayInterface $gateway): string
    {
        $orderId = isset($payload['order_id']) ? (string) $payload['order_id'] : null;
        $statusCode = isset($payload['status_code']) ? (string) $payload['status_code'] : '';
        $grossAmount = isset($payload['gross_amount']) ? (string) $payload['gross_amount'] : '';
        $signatureKey = isset($payload['signature_key']) ? (string) $payload['signature_key'] : '';

        if (! $orderId || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            Log::warning('midtrans webhook: payload tidak lengkap', ['order_id' => $orderId]);

            return 'invalid';
        }

        // (1) Verifikasi signature — tanpa membaca kredensial dari luar config.
        if (! $gateway->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('midtrans webhook: signature tidak valid', ['order_id' => $orderId]);

            return 'invalid';
        }

        $attempt = PaymentAttempt::query()->where('order_id', $orderId)->first();
        if (! $attempt) {
            Log::warning('midtrans webhook: order_id tidak dikenal', ['order_id' => $orderId]);

            return 'not_found';
        }

        // (2) Verifikasi server-side (Get Status API) — sumber kebenaran.
        try {
            $status = $gateway->getTransactionStatus($orderId);
        } catch (PaymentGatewayException $e) {
            Log::error('midtrans webhook: gagal verifikasi server-side', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return 'unverified'; // caller membalas 500 -> Midtrans retry
        }

        // (3) Cross-check nominal & order_id (pertahanan berlapis; signature tetap wajib).
        $apiOrderId = isset($status['order_id']) ? (string) $status['order_id'] : $orderId;
        if ($apiOrderId !== $orderId) {
            Log::warning('midtrans webhook: order_id API tidak cocok', ['order_id' => $orderId]);

            return 'invalid';
        }

        $apiGross = isset($status['gross_amount']) ? (int) round((float) $status['gross_amount']) : null;
        if ($apiGross !== null && $apiGross !== (int) round((float) $grossAmount)) {
            Log::warning('midtrans webhook: gross_amount API tidak cocok', ['order_id' => $orderId]);

            return 'invalid';
        }

        if ($apiGross !== null && (int) $attempt->nominal !== $apiGross) {
            Log::warning('midtrans webhook: gross_amount API tidak cocok dengan nominal attempt', ['order_id' => $orderId]);

            return 'invalid';
        }

        return self::settle($attempt, $status, $payload);
    }

    /**
     * Sinkronkan status attempt secara eksplisit (perintah `pembayaran:cek-status` / tombol periksa status).
     *
     * @return 'success'|'pending'|'failed'|'expired'|'ignored'|'duplicate'|'error'
     */
    public static function sinkronStatusAttempt(PaymentAttempt $attempt, PaymentGatewayInterface $gateway): string
    {
        try {
            $status = $gateway->getTransactionStatus($attempt->order_id);
        } catch (PaymentGatewayException $e) {
            Log::error('midtrans sinkron status: gagal', ['order_id' => $attempt->order_id, 'error' => $e->getMessage()]);

            return 'error';
        }

        // Cross-check nominal (pertahanan berlapis; panggilan status tidak boleh mengada-adakan uang).
        $apiGross = isset($status['gross_amount']) ? (int) round((float) $status['gross_amount']) : null;
        if ($apiGross !== null && $apiGross !== (int) $attempt->nominal) {
            Log::warning('midtrans sinkron status: gross_amount API tidak cocok dengan nominal attempt', ['order_id' => $attempt->order_id]);

            return 'error';
        }

        return self::settle($attempt, $status, []);
    }

    /**
     * Terapkan status terverifikasi ke attempt + ledger + alokasi (dalam 1 transaksi, lock).
     *
     * @param  array<string, mixed>  $status  hasil status API (atau payload bila hanya webhook tanpa API)
     * @param  array<string, mixed>  $payloadFallback  payload webhook (untuk audit; di-whitelist)
     */
    private static function settle(PaymentAttempt $attempt, array $status, array $payloadFallback): string
    {
        return DB::transaction(function () use ($attempt, $status, $payloadFallback) {
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->idpayment_attempt);

            // Idempotensi: attempt yang sudah success tidak pernah diproses ulang.
            if ($attempt->status === 'success') {
                return 'duplicate';
            }

            $transactionStatus = strtolower((string) ($status['transaction_status'] ?? null));
            $fraudStatus = strtolower((string) ($status['fraud_status'] ?? null));
            $metode = self::mapMetode((string) ($status['payment_type'] ?? ''), isset($status['bank']) ? (string) $status['bank'] : null);
            $grossAmount = isset($status['gross_amount']) ? (int) round((float) $status['gross_amount']) : (int) $attempt->nominal;
            $state = self::state($transactionStatus, $fraudStatus);

            // Sanitasi: whitelist field untuk disimpan; tidak menyimpan kartu/VA mentah bila ada.
            $attempt->payload_response = self::sanitizePayload($status, $payloadFallback);
            $attempt->metode = $metode;

            switch ($state) {
                case 'success':
                    $ledger = Pembayaran::create([
                        'payment_attempt_id' => $attempt->idpayment_attempt,
                        'tagihan_idtagihan' => $attempt->tagihan_idtagihan,
                        'siswa_idsiswa' => $attempt->siswa_idsiswa,
                        'nominal_dibayar' => $grossAmount,
                        'biaya_gateway' => 0, // tidak diketahui/tidak dikenakan sebelum checkout tidak dapat dipastikan; BUKAN hasil karang-mengarang fee
                        'metode' => $metode,
                        'provider' => 'midtrans',
                        'order_id' => $attempt->order_id,
                        'provider_ref' => isset($status['transaction_id']) ? (string) $status['transaction_id'] : null,
                        'status' => 'success',
                        'dibayar_pada' => isset($status['transaction_time']) ? self::parseWaktu((string) $status['transaction_time']) : now(),
                        'payer_jenis' => $attempt->payer_jenis,
                        'payer_nama' => $attempt->payer_nama,
                        'payer_user_id' => $attempt->payer_user_id,
                    ]);

                    self::postingPembayaranSetelahVerifikasi($ledger);

                    $attempt->status = 'success';
                    $attempt->save();

                    return 'success';

                case 'pending':
                    $attempt->status = 'pending';
                    $attempt->save();

                    return 'pending';

                case 'failed':
                    $attempt->status = 'failed';
                    $attempt->save();

                    return 'failed';

                case 'expired':
                    $attempt->status = 'expired';
                    $attempt->save();

                    return 'expired';

                default:
                    // refund/chargeback/status tak dikenal: jangan sentuh ledger & tagihan;
                    // dicatat untuk tindak lanjut manual oleh superadmin.
                    Log::warning('midtrans settle: status tidak dikenali', ['order_id' => $attempt->order_id, 'transaction_status' => $transactionStatus]);
                    $attempt->save();

                    return 'ignored';
            }
        });
    }

    private static function sanitizePayload(array $status, array $fallback): array
    {
        $ambil = static function (array $src, array $keys): array {
            $out = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, $src) && $src[$key] !== null && $src[$key] !== '') {
                    $out[$key] = $src[$key];
                }
            }

            return $out;
        };

        $keys = [
            'transaction_status', 'fraud_status', 'status_code', 'gross_amount',
            'payment_type', 'bank', 'transaction_id', 'transaction_time',
            'settlement_time', 'expiry_time',
        ];

        $payload = $ambil($status, $keys);
        $dariPayload = $ambil($fallback, ['order_id']);

        return array_merge($dariPayload, $payload);
    }

    /**
     * Mapping transaction_status Midtrans -> state terminal.
     */
    private static function state(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'challenge' ? 'pending' : 'success',
            'settlement' => 'success',
            'pending' => 'pending',
            'deny', 'cancel' => 'failed',
            'expire' => 'expired',
            default => 'ignored', // refund/partial_refund/chargeback/dll: tindak lanjut manual
        };
    }

    /**
     * Mapping payment_type Midtrans -> enum metode lokal.
     * bank_transfer & *_va -> va; QRIS -> qris; kartu -> credit_card;
     * e-money/e-wallet (gopay/shopeepay/dana) -> ewallet; cstore (minimarket) -> cstore;
     * echannel (Mandiri) -> echannel; selain itu -> lainnya.
     */
    private static function mapMetode(string $paymentType, ?string $bank): string
    {
        $type = strtolower(trim($paymentType));

        return match (true) {
            $type === 'bank_transfer' || str_ends_with($type, '_va') || in_array($type, ['cimb_va', 'danamon_va', 'bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va', 'echannel'], true) => $type === 'echannel' ? 'echannel' : 'va',
            $type === 'qris' => 'qris',
            $type === 'credit_card' => 'credit_card',
            $type === 'cstore' => 'cstore',
            in_array($type, ['gopay', 'shopeepay', 'dana'], true) => 'ewallet',
            default => 'lainnya',
        };
    }

    private static function parseWaktu(string $waktu): \Illuminate\Support\Carbon
    {
        try {
            return \Illuminate\Support\Carbon::parse($waktu);
        } catch (\Throwable) {
            return now();
        }
    }

    /**
     * Posting pembayaran (manual atau hasil verifikasi) ke ledger + alokasi.
     * Digunakan bersama oleh alur online (settle) dan pembayaran manual (verifikasi).
     * Harus dipanggil dalam konteks transaksi DB dengan lock yang sesuai.
     */
    public static function postingPembayaranSetelahVerifikasi(Pembayaran $pembayaran, bool $perbaruiTagihanStatus = true): void
    {
        $pembayaran->loadMissing(['tagihan']);

        $tagihanId = $pembayaran->tagihan_idtagihan;
        if (! $tagihanId) {
            return;
        }

        // Cegah alokasi ganda jika sudah pernah dialokasikan.
        if (PembayaranAlokasi::query()->where('pembayaran_idpembayaran', $pembayaran->idpembayaran)->exists()) {
            return;
        }

        $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($tagihanId);
        $sisaSebelum = self::sisaTagihan($tagihan);

        $nominal = (int) $pembayaran->nominal_dibayar;
        $dialokasikan = min($nominal, $sisaSebelum);

        if ($dialokasikan > 0) {
            PembayaranAlokasi::create([
                'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'nominal' => $dialokasikan,
                'jenis' => 'tagihan',
            ]);
        }

        $kelebihan = $nominal - $dialokasikan;
        if ($kelebihan > 0) {
            PembayaranAlokasi::create([
                'pembayaran_idpembayaran' => $pembayaran->idpembayaran,
                'siswa_idsiswa' => $pembayaran->siswa_idsiswa,
                'tagihan_idtagihan' => null,
                'nominal' => $kelebihan,
                'jenis' => 'saldo',
            ]);
        }

        if (! $perbaruiTagihanStatus) {
            return;
        }

        $sisaSesudah = self::sisaTagihan($tagihan);
        if ($sisaSesudah <= 0) {
            $tagihan->status = 'lunas';
        } elseif (self::terpakaiTagihan($tagihan) > 0) {
            $tagihan->status = 'dibayar_sebagian';
        }
        $tagihan->save();
    }

    /**
     * Verifikasi dan posting pembayaran manual.
     */
    public static function verifikasiPembayaranManual(Pembayaran $pembayaran, Authenticatable $verifikator): Pembayaran
    {
        return DB::transaction(function () use ($pembayaran, $verifikator) {
            $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($pembayaran->idpembayaran);

            if ($pembayaran->status === 'success') {
                throw new DomainException('Pembayaran sudah diverifikasi.');
            }

            if ($pembayaran->status === 'refunded' || $pembayaran->status === 'cancelled') {
                throw new DomainException('Pembayaran tidak dapat diverifikasi.');
            }

            if ($pembayaran->status !== 'pending' && $pembayaran->status !== 'review') {
                throw new DomainException('Pembayaran tidak dalam status menunggu verifikasi.');
            }

            $pembayaran->status = 'success';
            $pembayaran->diverifikasi_oleh = $verifikator->id;
            $pembayaran->diverifikasi_pada = now();
            if (! $pembayaran->dibayar_pada) {
                $pembayaran->dibayar_pada = now();
            }
            $pembayaran->save();

            self::postingPembayaranSetelahVerifikasi($pembayaran);

            return $pembayaran->fresh(['tagihan', 'siswa', 'verifikator']);
        });
    }

    /**
     * Tolak pembayaran manual.
     */
    public static function tolakPembayaranManual(Pembayaran $pembayaran, Authenticatable $verifikator, string $alasan): Pembayaran
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($pembayaran, $verifikator, $alasan) {
            $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($pembayaran->idpembayaran);

            if ($pembayaran->status === 'success') {
                throw new DomainException('Pembayaran yang sudah diverifikasi tidak dapat ditolak.');
            }

            if ($pembayaran->status === 'failed' || $pembayaran->status === 'cancelled' || $pembayaran->status === 'refunded') {
                throw new DomainException('Pembayaran sudah tidak dalam status menunggu verifikasi.');
            }

            $pembayaran->status = 'failed';
            $pembayaran->alasan_gagal = $alasan;
            $pembayaran->diverifikasi_oleh = $verifikator->id;
            $pembayaran->diverifikasi_pada = now();
            $pembayaran->save();

            return $pembayaran->fresh(['tagihan', 'siswa', 'verifikator']);
        });
    }

    /**
     * Gunakan saldo siswa untuk membayar tagihan.
     * Alokasi otomatis: jatuh tempo terdekat dulu, jika sama tanggal terbit tertua; pokok didahulukan.
     *
     * @param  array  $tagihanIds  Jika kosong, bayar semua tagihan yang bisa dibayar
     */
    public static function gunakanSaldo(\App\Models\Siswa $siswa, int $jumlahDiminta, ?Authenticatable $aktor = null, array $tagihanIds = []): array
    {
        return DB::transaction(function () use ($siswa, $jumlahDiminta, $aktor, $tagihanIds) {
            $siswaLocked = \App\Models\Siswa::query()->lockForUpdate()->findOrFail($siswa->idsiswa);

            $saldoTersedia = self::hitungSaldoTersedia($siswaLocked->idsiswa);
            if ($jumlahDiminta < 1) {
                throw new DomainException('Jumlah penggunaan saldo tidak valid.');
            }

            // Anomali ledger (saldo negatif) → eksplisit dibatalkan, log tercatat, histori tidak diubah.
            $mentahSaldo = self::saldoSiswa($siswaLocked->idsiswa);
            if ($jumlahDiminta > $saldoTersedia) {
                if ($mentahSaldo['saldo'] < 0) {
                    Log::error('penggunaan saldo dibatalkan karena anomali ledger siswa', [
                        'siswa_idsiswa' => $siswaLocked->idsiswa,
                        'saldo_masuk' => $mentahSaldo['masuk'],
                        'saldo_dipakai' => $mentahSaldo['dipakai'],
                        'saldo' => $mentahSaldo['saldo'],
                    ]);

                    throw new DomainException('Penggunaan saldo dibatalkan: terdeteksi anomali saldo (hubungi superadmin).');
                }

                throw new DomainException('Saldo tidak mencukupi.');
            }

            $query = Tagihan::query()->lockForUpdate()
                ->where('siswa_idsiswa', $siswaLocked->idsiswa)
                ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian']);

            if (! empty($tagihanIds)) {
                $query->whereIn('idtagihan', $tagihanIds);
            }

            // Kebijakan alokasi: jatuh tempo terdekat lebih dahulu; jika sama,
            // tanggal penerbitan tertua; pokok tagihan (tanpa denda) didahulukan
            // sebelum tagihan ber-denda; lalu urutan id sebagai tie-breaker stabil.
            $tagihans = $query->get()->sortBy([
                ['tanggal_jatuh_tempo', 'asc'],
                ['tanggal_terbit', 'asc'],
                ['denda_id', 'asc'],
                ['idtagihan', 'asc'],
            ])->values();

            $sisa = $jumlahDiminta;
            $hasil = [];

            foreach ($tagihans as $tagihan) {
                if ($sisa <= 0) {
                    break;
                }
                $tagihan = Tagihan::query()->lockForUpdate()->find($tagihan->idtagihan);
                if (! $tagihan) {
                    continue;
                }
                if (! in_array($tagihan->status, ['belum_dibayar', 'dibayar_sebagian'], true)) {
                    continue;
                }
                $sisaTagihan = self::sisaTagihan($tagihan);
                if ($sisaTagihan <= 0) {
                    continue;
                }
                $pakai = min($sisa, $sisaTagihan);

                $pembayaran = Pembayaran::create([
                    'tagihan_idtagihan' => $tagihan->idtagihan,
                    'siswa_idsiswa' => $siswaLocked->idsiswa,
                    'nominal_dibayar' => $pakai,
                    'biaya_gateway' => 0,
                    'metode' => 'saldo',
                    'provider' => 'saldo',
                    'status' => 'success',
                    'dibayar_pada' => now(),
                    'diinput_oleh' => $aktor?->id,
                    'diverifikasi_oleh' => $aktor?->id,
                    'diverifikasi_pada' => now(),
                    'payer_jenis' => $aktor && $aktor->level === 'wali' ? 'wali' : ($aktor && $aktor->level === 'siswa' ? 'siswa' : 'admin'),
                    'payer_nama' => $aktor?->name,
                    'payer_user_id' => $aktor?->id,
                    'catatan' => 'Penggunaan saldo siswa',
                ]);

                self::postingPembayaranSetelahVerifikasi($pembayaran);

                $sisa -= $pakai;
                $hasil[] = [
                    'pembayaran_id' => $pembayaran->idpembayaran,
                    'tagihan_id' => $tagihan->idtagihan,
                    'nominal' => $pakai,
                ];
            }

            if ($sisa > 0) {
                // Tidak ada tagihan yang dapat dibayar sama sekali — seluruh transaksi dibatalkan.
                if ($hasil === [] || collect($hasil)->sum('nominal') <= 0) {
                    throw new DomainException('Tidak ada tagihan yang dapat dibayar dengan saldo tersebut.');
                }
                // Jika hanya sebagian yang bisa dialokasikan, sisanya tetap menjadi saldo siswa
                // (tidak dibatalkan); transaksi yang sudah Dialokasikan tetap sah.
                Log::info('penggunaan saldo sebagian dialokasikan', [
                    'siswa_idsiswa' => $siswaLocked->idsiswa,
                    'jumlah_diminta' => $jumlahDiminta,
                    'dialokasikan' => $jumlahDiminta - $sisa,
                    'tertahan_sebagai_saldo' => $sisa,
                    'jumlah_tagihan_terbayar' => count($hasil),
                ]);
            }

            return $hasil;
        });
    }

    public static function riwayatPenggunaanSaldo(int $siswaId)
    {
        return Pembayaran::query()
            ->with(['tagihan', 'siswa', 'inputer', 'verifikator'])
            ->where('siswa_idsiswa', $siswaId)
            ->where('provider', 'saldo')
            ->latest('idpembayaran')
            ->get();
    }
}
