<?php

namespace App\Traits;

use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Respons sukses standar (default: 200 OK).
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Permintaan berhasil diproses.',
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Respons sukses pembuatan resource baru (201 Created + header Location).
     */
    protected function createdResponse(
        mixed $data,
        string $locationUrl,
        string $message = 'Resource baru berhasil dibuat.',
    ): JsonResponse {
        return $this->successResponse($data, $message, 201)
            ->header('Location', $locationUrl);
    }

    /**
     * Respons sukses tanpa isi (204 No Content).
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Respons galat berstandar RFC 7807.
     */
    protected function problemResponse(
        string $title,
        string $detail,
        int $status,
        string $errorSlug = 'general-error',
        array $errors = [],
    ): JsonResponse {
        return ProblemDetails::response(request(), $title, $detail, $status, $errorSlug, $errors);
    }

    /**
     * Blok meta paginasi yang seragam untuk seluruh endpoint koleksi.
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total_items' => $paginator->total(),
            'total_pages' => $paginator->lastPage(),
            'links' => [
                'first' => $paginator->url(1),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
                'last' => $paginator->url($paginator->lastPage()),
            ],
        ];
    }
}