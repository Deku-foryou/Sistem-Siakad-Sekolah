<?php

namespace App\Http\Controllers;

use App\Services\Payments\MidtransGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook Midtrans (payment notification).
 *
 * - TIDAK memakai autentikasi sesi pengguna.
 * - Keamanan sepenuhnya melalui verifikasi signature_key (SHA512 dari
 *   order_id + status_code + gross_amount + SERVER_KEY) plus verifikasi
 *   server-side ke API status Midtrans. Gagal verifikasi => 403/404/500.
 * - Selalu mengembalikan JSON; tidak pernah mengandalkan browser redirect
 *   sebagai bukti pembayaran.
 * - Idempotent: notifikasi berulang untuk attempt yang sudah success => 200 OK
 *   tanpa efek samping (ledger/alokasi tidak digandakan).
 */
class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransGateway $gateway): JsonResponse
    {
        $hasil = PaymentService::prosesNotifikasi($request->all(), $gateway);

        return match ($hasil) {
            'success', 'pending', 'failed', 'expired', 'ignored', 'duplicate' => response()->json(['status' => $hasil], 200),
            'invalid' => response()->json(['status' => 'invalid'], 403),
            'not_found' => response()->json(['status' => 'not_found'], 404),
            'unverified' => response()->json(['status' => 'unverified'], 500),
            default => response()->json(['status' => $hasil], 200),
        };
    }
}
