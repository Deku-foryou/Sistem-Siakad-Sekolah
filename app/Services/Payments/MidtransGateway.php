<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;

/**
 * Integrasi Midtrans Snap (REST API langsung; tidak menambah package).
 *
 * Konfigurasi hanya dari `config('services.midtrans')` (di-isi environment);
 * tidak ada credential yang di-hardcode dan .env tidak dibaca langsung.
 *
 * Mode:
 * - sandbox    : app.sandbox.midtrans.com / api.sandbox.midtrans.com (default)
 * - production : app.midtrans.com / api.midtrans.com (hanya jika MIDTRANS_MODE=production)
 *
 * Jika SERVER_KEY / CLIENT_KEY kosong, gateway dianggap belum terkonfigurasi
 * (isConfigured() === false) dan transaksi/token tidak pernah dibuat.
 */
class MidtransGateway implements PaymentGatewayInterface
{
    public function provider(): string
    {
        return 'midtrans';
    }

    public function mode(): string
    {
        $mode = strtolower(trim((string) config('services.midtrans.mode', 'sandbox')));

        return $mode === 'production' ? 'production' : 'sandbox';
    }

    public function isConfigured(): bool
    {
        return $this->serverKey() !== '' && $this->clientKey() !== '';
    }

    public function clientKey(): string
    {
        return trim((string) config('services.midtrans.client_key', ''));
    }

    private function serverKey(): string
    {
        return trim((string) config('services.midtrans.server_key', ''));
    }

    private function appBaseUrl(): string
    {
        return $this->mode() === 'production'
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    private function apiBaseUrl(): string
    {
        return $this->mode() === 'production'
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    public function snapJsUrl(): string
    {
        return $this->appBaseUrl().'/snap/snap.js';
    }

    public function createSnapTransaction(array $data): array
    {
        $this->assertConfigured();

        $orderId = (string) $data['order_id'];
        $grossAmount = (int) $data['gross_amount'];

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(15)
            ->post($this->apiBaseUrl().'/snap/v1/transactions', array_filter([
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => array_filter([
                    'first_name' => $data['first_name'] ?? null,
                    'email' => $data['email'] ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
                'item_details' => [[
                    'id' => (string) ($data['item_id'] ?? $orderId),
                    'price' => $grossAmount,
                    'quantity' => 1,
                    'name' => $data['item_name'] ?? 'Pembayaran',
                ]],
            ], fn ($v) => ! empty($v)));

        if ($response->failed()) {
            throw new PaymentGatewayException('Gateway menolak pembuatan transaksi Snap (HTTP '.$response->status().').');
        }

        $json = $response->json();

        if (! is_array($json) || empty($json['token'])) {
            throw new PaymentGatewayException('Respons gateway tidak memuat token Snap.');
        }

        return [
            'token' => (string) $json['token'],
            'redirect_url' => $json['redirect_url'] ?? null,
            'raw' => $json,
        ];
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        if ($this->serverKey() === '') {
            return false; // fail-closed
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey());

        return hash_equals($expected, $signatureKey);
    }

    public function getTransactionStatus(string $orderId): array
    {
        $this->assertConfigured();

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(15)
            ->get($this->apiBaseUrl().'/v2/'.rawurlencode($orderId).'/status');

        if ($response->failed()) {
            throw new PaymentGatewayException('Gagal mengambil status transaksi (HTTP '.$response->status().').');
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new PaymentGatewayException('Respons status transaksi tidak valid.');
        }

        return $json;
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException('Integrasi Midtrans belum dikonfigurasi (MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY kosong; mode '.$this->mode().').');
        }
    }
}
