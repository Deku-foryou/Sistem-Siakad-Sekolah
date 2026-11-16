<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Konvensi respons API SIAKAD (Fase 1).
 * Sukses: { success, message, data } · Gagal: { success, message, errors? }
 */
class ApiResponse
{
    public static function ok(mixed $data = null, string $message = 'Berhasil', int $status = 200, ?array $meta = null): JsonResponse
    {
        $body = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function created(mixed $data = null, string $message = 'Berhasil'): JsonResponse
    {
        return self::ok($data, $message, 201);
    }

    public static function fail(string $message, int $status = 400, array $errors = [], ?string $code = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        if ($code !== null) {
            $body['code'] = $code;
        }

        return response()->json($body, $status);
    }
}
