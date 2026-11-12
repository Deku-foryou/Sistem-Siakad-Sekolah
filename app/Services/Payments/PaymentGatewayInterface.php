<?php

namespace App\Services\Payments;

/**
 * Kontrak payment gateway (Snap Midtrans dst.).
 * Semua operasi bersifat network I/O dan HARUS dapat di-fake dalam pengujian.
 */
interface PaymentGatewayInterface
{
    public function isConfigured(): bool;

    public function provider(): string;

    public function mode(): string;

    public function clientKey(): string;

    public function snapJsUrl(): string;

    /**
     * Buat transaksi Snap untuk order.
     *
     * @param  array{order_id:string, gross_amount:int, first_name?:string|null, email?:string|null, item_name?:string, item_id?:string}  $data
     * @return array{token:string, redirect_url:?string, raw:array<string,mixed>}
     *
     * @throws PaymentGatewayException bila gagal / respons tidak valid
     */
    public function createSnapTransaction(array $data): array;

    /**
     * Verifikasi signature notifikasi (SHA512 order_id+status_code+gross_amount+SERVER_KEY).
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool;

    /**
     * Status transaksi server-side (sumber kebenaran), mis. GET {api}/v2/{order_id}/status.
     *
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException bila tidak dapat diverifikasi
     */
    public function getTransactionStatus(string $orderId): array;
}
