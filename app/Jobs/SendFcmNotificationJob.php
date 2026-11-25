<?php

namespace App\Jobs;

use App\Models\FcmDeviceToken;
use App\Models\Notifikasi;
use App\Models\NotifikasiFcmDelivery;
use App\Services\FcmDeviceTokenService;
use App\Services\FcmSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Job pengiriman FCM (malas) — dipicu setelah Notifikasi Service commit.
 *
 * - Klaim atomik via Notifikasi.fcm_claimed_at (aman untuk 2 worker).
 * - Penerima = snapshot NotifikasiPenerima saat pembuatan (history tetap).
 * - Status per token di notifikasi_fcm_delivery: partial delivery tidak
 *   pernah ditandai sebagai seluruh notifikasi selesai.
 * - fcm_dispatched_at hanya diset bila tidak ada pengiriman yang gagal.
 * - Payload tanpa data sensitif (lihat FcmSenderService).
 */
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    private const CLAIM_TTL_SECONDS = 60;

    public function __construct(private readonly int $notificationId)
    {
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(FcmSenderService $sender): void
    {
        $notification = Notifikasi::query()->find($this->notificationId);
        if ($notification === null) {
            $this->delete();

            return;
        }
        if ($notification->fcm_dispatched_at !== null) {
            $this->delete();

            return;
        }

        // Klaim atomik: TTL lebih pendek dari retry_after queue (90 detik)
        // sehingga retry setelah worker mati selalu bisa mengambil alih klaim.
        $claimToken = Str::uuid()->toString();
        $claimed = Notifikasi::query()
            ->where('idnotifikasi', $this->notificationId)
            ->whereNull('fcm_dispatched_at')
            ->where(function ($query) {
                $query->whereNull('fcm_claimed_at')
                    ->orWhere('fcm_claimed_at', '<', now()->subSeconds(self::CLAIM_TTL_SECONDS));
            })
            ->update([
                'fcm_claimed_at' => now(),
                'fcm_claim_token' => $claimToken,
            ]);

        if ($claimed === 0) {
            throw new RuntimeException('Notifikasi FCM masih diklaim worker lain; job akan diulang.');
        }

        $hasFailure = false;
        $sent = 0;

        foreach ($notification->penerima as $recipient) {
            if (! $this->ownsClaim($claimToken)) {
                Log::warning('FCM claim lost before dispatch', [
                    'notification_id' => $this->notificationId,
                ]);

                return;
            }

            $tokens = FcmDeviceToken::query()
                ->where('user_iduser', $recipient->user_iduser)
                ->whereNull('revoked_at')
                ->get();

            foreach ($tokens as $deviceToken) {
                $delivery = NotifikasiFcmDelivery::query()->firstOrCreate([
                    'notifikasi_idnotifikasi' => $notification->idnotifikasi,
                    'fcm_token_id' => $deviceToken->idfcmdevicetokens,
                ], [
                    'status' => FcmSenderService::STATUS_FAILED,
                ]);

                // Idempoten per token: yang sudah terkirim tidak dikirim ulang.
                if ($delivery->status === FcmSenderService::STATUS_SENT) {
                    $sent++;

                    continue;
                }
                if ($delivery->status === FcmSenderService::STATUS_INVALID_TOKEN) {
                    continue;
                }

                if (! $this->ownsClaim($claimToken)) {
                    Log::warning('FCM claim lost before token send', [
                        'notification_id' => $this->notificationId,
                    ]);

                    return;
                }

                try {
                    $result = $sender->sendToTokenDetailed((string) $deviceToken->fcm_token, [
                        'notification_id' => $notification->idnotifikasi,
                        'notification_type' => $notification->jenis,
                    ]);

                    $status = $result['status'];
                    if ($status === FcmSenderService::STATUS_SENT) {
                        $sent++;
                    } elseif ($status === FcmSenderService::STATUS_INVALID_TOKEN) {
                        FcmDeviceTokenService::revokeInvalidTokenById($deviceToken->idfcmdevicetokens);
                    } else {
                        $hasFailure = true;
                    }

                    $delivery->update([
                        'status' => $status,
                        'message_id' => $result['message_id'],
                        'error' => $result['error'],
                    ]);
                } catch (\Throwable $e) {
                    // Satu perangkat gagal TIDAK boleh menghentikan perangkat lain.
                    $hasFailure = true;
                    $delivery->update([
                        'status' => FcmSenderService::STATUS_FAILED,
                        'error' => substr($e->getMessage(), 0, 255),
                    ]);
                    Log::warning('FCM dispatch failed', [
                        'notification_id' => $notification->idnotifikasi,
                        'error' => substr($e->getMessage(), 0, 200),
                    ]);
                }
            }
        }

        if ($hasFailure) {
            // Lepaskan klaim hanya bila worker ini masih pemiliknya.
            Notifikasi::query()
                ->where('idnotifikasi', $notification->idnotifikasi)
                ->where('fcm_claim_token', $claimToken)
                ->update(['fcm_claimed_at' => null, 'fcm_claim_token' => null]);

            throw new RuntimeException('Sebagian pengiriman FCM gagal; job akan diulang.');
        }

        $dispatched = Notifikasi::query()
            ->where('idnotifikasi', $notification->idnotifikasi)
            ->where('fcm_claim_token', $claimToken)
            ->update([
                'fcm_dispatched_at' => now(),
                'fcm_claimed_at' => null,
                'fcm_claim_token' => null,
            ]);

        if ($dispatched === 0) {
            Log::warning('FCM claim lost before dispatch commit', [
                'notification_id' => $notification->idnotifikasi,
            ]);

            return;
        }

        Log::debug('FCM dispatched', [
            'notification_id' => $notification->idnotifikasi,
            'type' => $notification->jenis,
            'sent' => $sent,
        ]);
    }

    private function ownsClaim(string $claimToken): bool
    {
        return Notifikasi::query()
            ->where('idnotifikasi', $this->notificationId)
            ->where('fcm_claim_token', $claimToken)
            ->whereNull('fcm_dispatched_at')
            ->exists();
    }
}
