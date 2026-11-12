<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Gagal komunikasi atau respons tidak valid dari gateway pembayaran.
 * Di-lemparkan saat transaksi online tidak dapat dibuat/diverifikasi.
 */
class PaymentGatewayException extends RuntimeException {}
