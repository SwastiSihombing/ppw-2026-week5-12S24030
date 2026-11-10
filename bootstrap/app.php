<?php

use App\Support\ProblemDetails;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pemanggil SOAP adalah mesin, bukan form browser yang membawa token @csrf.
        $middleware->validateCsrfTokens(except: [
            'soap/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Seluruh galat /api/* dikembalikan sebagai application/problem+json (RFC 7807).
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ProblemDetails::response(
                    $request,
                    'Unprocessable Content',
                    'Satu atau lebih atribut input tidak memenuhi aturan validasi.',
                    422,
                    'validation-failed',
                    $e->errors(),
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ProblemDetails::response(
                    $request,
                    'Unauthorized',
                    'Akses ditolak. Sertakan Bearer Token yang valid pada header Authorization.',
                    401,
                    'unauthenticated',
                );
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();

                [$slug, $detail] = match ($status) {
                    403 => ['forbidden', 'Anda tidak memiliki hak akses ke resource ini.'],
                    404 => ['endpoint-not-found', 'Alamat endpoint atau resource yang diminta tidak ditemukan.'],
                    405 => ['method-not-allowed', "Metode {$request->method()} tidak diizinkan pada endpoint ini."],
                    429 => ['too-many-requests', 'Terlalu banyak permintaan. Coba lagi beberapa saat.'],
                    default => ['http-error', 'Permintaan tidak dapat diproses.'],
                };

                return ProblemDetails::response(
                    $request,
                    Response::$statusTexts[$status] ?? 'HTTP Error',
                    $detail,
                    $status,
                    $slug,
                    headers: $e->getHeaders(),
                );
            }
        });

        // Galat tak terduga tetap tercatat di log, tetapi rinciannya tidak dibocorkan ke klien.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return ProblemDetails::response(
                    $request,
                    'Internal Server Error',
                    'Terjadi kesalahan pada server.',
                    500,
                    'internal-server-error',
                );
            }
        });
    })->create();