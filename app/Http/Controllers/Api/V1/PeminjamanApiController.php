<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /**
     * Jumlah percobaan ulang transaksi jika terjadi deadlock pada database.
     */
    private const TRANSACTION_ATTEMPTS = 3;

    /**
     * GET /api/v1/peminjamans
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['dipinjam', 'dikembalikan'])],
            'nim' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = Peminjaman::with('buku')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['nim'] ?? null, fn ($query, $nim) => $query->where('nim_peminjam', strtoupper($nim)))
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return $this->successResponse(
            data: PeminjamanResource::collection($paginator->getCollection()),
            message: 'Daftar transaksi peminjaman berhasil diambil.',
            meta: $this->paginationMeta($paginator),
        );
    }

    /**
     * GET /api/v1/peminjamans/{id}
     */
    public function show(string $id): JsonResponse
    {
        $peminjaman = Peminjaman::with('buku')->find($id);

        if (! $peminjaman) {
            return $this->notFound($id);
        }

        return $this->successResponse(
            data: new PeminjamanResource($peminjaman),
            message: 'Detail transaksi peminjaman berhasil ditemukan.',
        );
    }

    /**
     * POST /api/v1/peminjamans
     * Baris buku dikunci sampai transaksi selesai, sehingga permintaan bersamaan
     * untuk buku yang sama diproses bergantian dan stok tidak bisa minus.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id' => ['required', 'integer', 'exists:bukus,id'],
            'nim_peminjam' => ['required', 'string', 'max:20'],
            'nama_peminjam' => ['required', 'string', 'max:150'],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));

        return DB::transaction(function () use ($validated, $nim) {
            $buku = Buku::whereKey($validated['buku_id'])->lockForUpdate()->firstOrFail();

            // Dicek sebelum stok: peminjam yang memegang eksemplar terakhir tetap
            // mendapat pesan konflik yang benar, bukan pesan stok habis.
            $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->exists();

            if ($masihDipinjam) {
                return $this->problemResponse(
                    title: 'Conflict: Peminjaman Ganda',
                    detail: "Mahasiswa dengan NIM '{$nim}' masih meminjam buku ini dan belum mengembalikannya.",
                    status: 409,
                    errorSlug: 'duplicate-loan',
                );
            }

            if ($buku->stok < 1) {
                return $this->problemResponse(
                    title: 'Unprocessable Content',
                    detail: 'Stok buku sedang habis.',
                    status: 422,
                    errorSlug: 'stock-exhausted',
                    errors: ['buku_id' => ['Stok buku sedang habis.']],
                );
            }

            $buku->decrement('stok');

            $peminjaman = Peminjaman::create([
                'kode_pinjam' => 'PJM-'.strtoupper(Str::random(8)),
                'buku_id' => $buku->id,
                'nim_peminjam' => $nim,
                'nama_peminjam' => $validated['nama_peminjam'],
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays((int) $validated['durasi_hari'])->toDateString(),
                'status' => 'dipinjam',
            ])->setRelation('buku', $buku);

            return $this->createdResponse(
                data: new PeminjamanResource($peminjaman),
                locationUrl: url("/api/v1/peminjamans/{$peminjaman->id}"),
                message: "Peminjaman berhasil dicatat. Sisa stok: {$buku->stok}.",
            );
        }, self::TRANSACTION_ATTEMPTS);
    }

    /**
     * PATCH /api/v1/peminjamans/{id}/kembalikan
     */
    public function kembalikan(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::whereKey($id)->lockForUpdate()->first();

            if (! $peminjaman) {
                return $this->notFound($id);
            }

            if ($peminjaman->status === 'dikembalikan') {
                return $this->problemResponse(
                    title: 'Conflict: Sudah Dikembalikan',
                    detail: "Peminjaman '{$peminjaman->kode_pinjam}' sudah berstatus dikembalikan.",
                    status: 409,
                    errorSlug: 'already-returned',
                );
            }

            $buku = Buku::whereKey($peminjaman->buku_id)->lockForUpdate()->firstOrFail();
            $buku->increment('stok');

            $peminjaman->update([
                'status' => 'dikembalikan',
                'tanggal_kembali' => now()->toDateString(),
            ]);
            $peminjaman->setRelation('buku', $buku);

            return $this->successResponse(
                data: new PeminjamanResource($peminjaman),
                message: "Buku berhasil dikembalikan. Stok kini: {$buku->stok}.",
            );
        }, self::TRANSACTION_ATTEMPTS);
    }

    private function notFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Transaksi peminjaman dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'peminjaman-not-found',
        );
    }
}