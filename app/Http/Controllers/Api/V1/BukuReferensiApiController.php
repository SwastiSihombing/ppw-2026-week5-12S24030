<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SIPUS-Del sebagai REST Service Consumer: memperkaya metadata buku dari OpenLibrary.
 * Gangguan layanan luar tidak boleh menggagalkan endpoint, sehingga data lokal
 * tetap dikembalikan sebagai fallback.
 */
class BukuReferensiApiController extends Controller
{
    use ApiResponse;

    private const OPEN_LIBRARY_URL = 'https://openlibrary.org/api/books';

    /**
     * GET /api/v1/bukus/{id}/referensi-eksternal
     */
    public function show(string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found',
            );
        }

        $isbn = preg_replace('/[^0-9Xx]/', '', $buku->isbn);
        $bibKey = "ISBN:{$isbn}";

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->withUserAgent('SIPUS-Del/1.0')
                ->get(self::OPEN_LIBRARY_URL, [
                    'bibkeys' => $bibKey,
                    'format' => 'json',
                    'jscmd' => 'data',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenLibrary tidak dapat dihubungi: '.$e->getMessage(), ['buku_id' => $buku->id]);

            return $this->result($buku, 'tidak_terjangkau', 'Layanan OpenLibrary tidak dapat dihubungi atau melewati batas waktu. Menampilkan data lokal.');
        }

        if ($response->failed()) {
            return $this->result($buku, 'gangguan_layanan', "OpenLibrary merespons dengan status {$response->status()}. Menampilkan data lokal.");
        }

        $data = $response->json()[$bibKey] ?? null;

        if (! is_array($data)) {
            return $this->result($buku, 'tidak_ditemukan', "ISBN {$isbn} tidak ditemukan di OpenLibrary. Menampilkan data lokal.");
        }

        return $this->result($buku, 'ditemukan', 'Metadata eksternal berhasil diambil dari OpenLibrary.', [
            'judul' => $data['title'] ?? null,
            'penulis' => array_column($data['authors'] ?? [], 'name'),
            'penerbit' => array_column($data['publishers'] ?? [], 'name'),
            'jumlah_halaman' => $data['number_of_pages'] ?? null,
            'tanggal_terbit' => $data['publish_date'] ?? null,
            'sampul_url' => $data['cover']['medium'] ?? null,
            'halaman_url' => $data['url'] ?? null,
            'pratinjau_url' => $data['ebooks'][0]['preview_url'] ?? null,
        ]);
    }

    private function result(Buku $buku, string $status, string $message, ?array $referensi = null): JsonResponse
    {
        return $this->successResponse(
            data: [
                'buku' => [
                    'id' => $buku->id,
                    'isbn' => $buku->isbn,
                    'judul' => $buku->judul,
                    'penulis' => $buku->penulis,
                    'penerbit' => $buku->penerbit,
                    'tahun_terbit' => $buku->tahun_terbit,
                ],
                'sumber' => [
                    'penyedia' => 'OpenLibrary',
                    'status' => $status,
                ],
                'referensi_eksternal' => $referensi,
                '_links' => [
                    'buku' => ['href' => url("/api/v1/bukus/{$buku->id}"), 'method' => 'GET'],
                ],
            ],
            message: $message,
        );
    }
}