<?php

namespace App\Console\Commands;

use App\Models\PaymentAttempt;
use App\Services\Payments\MidtransGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pemeringsaan ulang status transaksi Midtrans.
 *
 * Tanpa argumen: memeriksa seluruh attempt berstatus created/pending.
 * Dengan order_id: memeriksa satu attempt secara spesifik.
 *
 * Status diambil dari API status Midtrans (server-side) dan diterapkan idempotently;
 * aman dijalankan berulang kali. Tidak membuat ledger tanpa verifikasi.
 *
 * Penggunaan lazim: dipanggil dari cron (mis. setiap 5 menit) sebagai pengaman bila
 * webhook tidak terkirim. Tidak menggantikan webhook — keduanya boleh hidup bersama.
 */
class CekStatusPembayaran extends Command
{
    protected $signature = 'pembayaran:cek-status {order_id? : Periksa satu order_id saja}';

    protected $description = 'Periksa & sinkronkan status transaksi Midtrans (created/pending) — idempotent.';

    public function handle(MidtransGateway $gateway): int
    {
        $orderId = $this->argument('order_id');

        if (! $gateway->isConfigured()) {
            Log::info('pembayaran:cek-status dilewati: integrasi Midtrans belum dikonfigurasi; tidak ada yang diperiksa.');

            return self::SUCCESS;
        }

        $query = PaymentAttempt::query()->whereIn('status', ['created', 'pending']);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $attempts = $query->orderBy('idpayment_attempt')->limit(500)->get();

        if ($attempts->isEmpty()) {
            $this->info('Tidak ada attempt created/pending untuk diperiksa.');

            return self::SUCCESS;
        }

        $hasil = ['success' => 0, 'pending' => 0, 'failed' => 0, 'expired' => 0, 'duplicate' => 0, 'ignored' => 0, 'error' => 0];

        foreach ($attempts as $attempt) {
            $state = PaymentService::sinkronStatusAttempt($attempt, $gateway);
            $hasil[$state] = ($hasil[$state] ?? 0) + 1;
        }

        Log::info('pembayaran:cek-status selesai', $hasil + ['total' => $attempts->count()]);
        $this->info('Diperiksa '.$attempts->count().' attempt: '.json_encode($hasil));

        return self::SUCCESS;
    }
}
