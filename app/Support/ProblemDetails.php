<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pembentuk respons galat berstandar RFC 7807 (application/problem+json).
 * Dipakai bersama oleh trait ApiResponse dan exception handler global.
 */
class ProblemDetails
{
    public static function response(
        Request $request,
        string $title,
        string $detail,
        int $status,
        string $errorSlug = 'general-error',
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        $problem = [
            'type' => url("/docs/errors/{$errorSlug}"),
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getRequestUri(),
        ];

        if ($errors !== []) {
            $problem['errors'] = $errors;
        }

        return response()
            ->json($problem, $status, $headers)
            ->header('Content-Type', 'application/problem+json');
    }
}