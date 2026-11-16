<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Fase 1: seluruh error API dikembalikan sebagai JSON konsisten.
        // Web mempertahankan perilaku eksisting (render() di bawah tidak diubah).
        $this->renderable(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::fail('Validasi gagal.', 422, $e->errors());
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::fail('Belum terautentikasi atau token tidak valid.', 401, [], 'UNAUTHENTICATED');
        });

        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::fail('Tidak memiliki kewenangan.', 403, [], 'FORBIDDEN');
        });

        $this->renderable(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::fail('Resource tidak ditemukan.', 404, [], 'NOT_FOUND');
        });

        $this->renderable(function (TooManyRequestsHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $response = ApiResponse::fail('Terlalu banyak percobaan. Coba lagi nanti.', 429, [], 'TOO_MANY_REQUESTS');

            if ($retryAfter = $e->getHeaders()['Retry-After'] ?? null) {
                $response->headers->set('Retry-After', $retryAfter);
            }

            return $response;
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Jangan bocorkan detail internal ke client; detail tersimpan di log.
            return ApiResponse::fail('Kesalahan server. Silakan coba lagi.', 500, [], 'SERVER_ERROR');
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof UnauthorizedException) {
            return response()->view('errors.index', ['exception' => $e->getMessage()], 403);
        }

        return parent::render($request, $e);
    }
}
