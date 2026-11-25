<?php

namespace Tests\Unit;

use App\Services\FcmSenderService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Tests\TestCase;

class FcmSenderErrorClassificationTest extends TestCase
{
    private function service(array $queue): FcmSenderService
    {
        $handler = HandlerStack::create(new MockHandler($queue));
        $client = new Client(['handler' => $handler, 'http_errors' => true]);

        return new FcmSenderService(
            accessToken: 'oauth-access-token',
            projectId: 'test-project',
            httpClient: $client,
        );
    }

    private function fcmResponse(int $status, string $errorStatus, array $details = [], string $message = 'generic failure'): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode([
            'error' => [
                'status' => $errorStatus,
                'message' => $message,
                'details' => $details,
            ],
        ]));
    }

    private function detail(string $code): array
    {
        return ['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => $code];
    }

    public function test_notification_block_mengikuti_jenis_payload_tanpa_data_sensitif(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['name' => 'projects/test/messages/1'])),
        ]));
        $handler->push(Middleware::history($history));
        $service = new FcmSenderService(
            accessToken: 'oauth-access-token',
            projectId: 'test-project',
            httpClient: new Client(['handler' => $handler]),
        );

        $service->sendToTokenDetailed('token-jenis', [
            'notification_id' => 42,
            'notification_type' => 'pengingat_tagihan',
            'nama_siswa' => 'rahasia-siswa',
        ]);

        $raw = (string) $history[0]['request']->getBody();
        $payload = json_decode($raw, true);
        $this->assertSame('Notifikasi SIAKAD', $payload['message']['notification']['title']);
        $this->assertSame('Pengingat tagihan.', $payload['message']['notification']['body']);
        $this->assertSame('42', $payload['message']['data']['notification_id']);
        $this->assertSame('pengingat_tagihan', $payload['message']['data']['notification_type']);
        $this->assertStringEndsWith('/v1/projects/test-project/messages:send', (string) $history[0]['request']->getUri());
        $this->assertStringNotContainsString('rahasia-siswa', $raw);
    }

    public function test_jwt_oauth_mempunyai_scope_fcm_messaging(): void
    {
        $this->assertSame(
            'https://www.googleapis.com/auth/firebase.messaging',
            FcmSenderService::OAUTH_SCOPE,
        );
    }

    public function test_http_400_payload_error_tidak_menganggap_token_invalid(): void
    {
        $service = $this->service([
            $this->fcmResponse(400, 'INVALID_ARGUMENT', [$this->detail('INVALID_ARGUMENT')], 'token secret-token-abc malformed'),
        ]);

        try {
            $service->sendToTokenDetailed('secret-token-abc', ['notification_id' => 1]);
            $this->fail('Payload error seharusnya melempar exception.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('payload tidak valid', $e->getMessage());
            $this->assertStringContainsString('INVALID_ARGUMENT', $e->getMessage());
            $this->assertStringNotContainsString('secret-token-abc', $e->getMessage());
            $this->assertStringNotContainsString('malformed', $e->getMessage());
        }
    }

    public function test_http_400_dengan_unregistered_tetap_invalid_token(): void
    {
        $service = $this->service([
            $this->fcmResponse(400, 'INVALID_ARGUMENT', [$this->detail('UNREGISTERED')]),
        ]);

        $result = $service->sendToTokenDetailed('token-400-unregistered', ['notification_id' => 1]);

        $this->assertSame(FcmSenderService::STATUS_INVALID_TOKEN, $result['status']);
        $this->assertSame('UNREGISTERED', $result['error']);
    }

    public function test_http_404_tanpa_unregistered_bukan_invalid_token(): void
    {
        $service = $this->service([
            $this->fcmResponse(404, 'NOT_FOUND', [$this->detail('NOT_FOUND')]),
        ]);

        try {
            $service->sendToTokenDetailed('token-404-notfound', ['notification_id' => 1]);
            $this->fail('404 tanpa UNREGISTERED tidak boleh dianggap invalid token.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('resource tidak ditemukan', $e->getMessage());
            $this->assertStringContainsString('NOT_FOUND', $e->getMessage());
        }
    }

    public function test_http_404_dengan_unregistered_adalah_invalid_token(): void
    {
        $service = $this->service([
            $this->fcmResponse(404, 'NOT_FOUND', [$this->detail('UNREGISTERED')]),
        ]);

        $result = $service->sendToTokenDetailed('token-404-unregistered', ['notification_id' => 1]);

        $this->assertSame(FcmSenderService::STATUS_INVALID_TOKEN, $result['status']);
        $this->assertSame('UNREGISTERED', $result['error']);
    }

    public function test_http_401_dan_403_adalah_credential_error(): void
    {
        foreach ([401, 403] as $status) {
            $service = $this->service([
                $this->fcmResponse($status, $status === 401 ? 'UNAUTHENTICATED' : 'PERMISSION_DENIED', [
                    $this->detail($status === 401 ? 'UNAUTHENTICATED' : 'PERMISSION_DENIED'),
                ], 'private_key secret leaked here'),
            ]);

            try {
                $service->sendToTokenDetailed('token-credential', ['notification_id' => 1]);
                $this->fail("HTTP $status seharusnya melempar credential error.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('kredensial service account', $e->getMessage());
                $this->assertStringNotContainsString('private_key', $e->getMessage());
                $this->assertStringNotContainsString('secret leaked', $e->getMessage());
            }
        }
    }

    public function test_http_429_adalah_rate_limit(): void
    {
        $service = $this->service([
            $this->fcmResponse(429, 'RESOURCE_EXHAUSTED', [$this->detail('RESOURCE_EXHAUSTED')]),
        ]);

        try {
            $service->sendToTokenDetailed('token-rate', ['notification_id' => 1]);
            $this->fail('HTTP 429 seharusnya melempar rate limit error.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('rate limit', $e->getMessage());
            $this->assertStringContainsString('RESOURCE_EXHAUSTED', $e->getMessage());
        }
    }

    public function test_http_5xx_adalah_server_error(): void
    {
        $service = $this->service([
            $this->fcmResponse(503, 'UNAVAILABLE', [$this->detail('UNAVAILABLE')]),
        ]);

        try {
            $service->sendToTokenDetailed('token-server', ['notification_id' => 1]);
            $this->fail('HTTP 503 seharusnya melempar server error.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Server FCM', $e->getMessage());
            $this->assertStringContainsString('UNAVAILABLE', $e->getMessage());
        }
    }

    public function test_network_error_diklasifikasi_terpisah(): void
    {
        $service = $this->service([
            new ConnectException('Connection refused', new Request('POST', 'https://fcm.googleapis.com')),
        ]);

        try {
            $service->sendToTokenDetailed('token-network', ['notification_id' => 1]);
            $this->fail('Network error seharusnya melempar exception.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('jaringan', $e->getMessage());
            $this->assertStringContainsString('network_error', $e->getMessage());
            $this->assertStringNotContainsString('token-network', $e->getMessage());
        }
    }
}
