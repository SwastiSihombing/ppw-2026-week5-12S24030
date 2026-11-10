<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/bukus
     * Koleksi buku dengan pencarian, filter, pengurutan, dan paginasi.
     */
    public function index(Request $request): JsonResponse
    {
        // Daftar kolom urut dibatasi agar ORDER BY tidak bisa disisipi input bebas.
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'kategori_id' => ['nullable', 'integer'],
            'tersedia' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['judul', 'penulis', 'tahun_terbit', 'stok', 'created_at'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        // with() dan withCount() mencegah N+1 query.
        $paginator = Buku::query()
            ->with('kategori')
            ->withCount('peminjamans')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($filters['kategori_id'] ?? null, fn ($query, $id) => $query->where('kategori_id', $id))
            ->when($request->boolean('tersedia'), fn ($query) => $query->where('stok', '>', 0))
            ->orderBy($filters['sort'] ?? 'judul', $filters['dir'] ?? 'asc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return $this->successResponse(
            data: BukuResource::collection($paginator->getCollection()),
            message: 'Daftar buku berhasil diambil.',
            meta: $this->paginationMeta($paginator),
        );
    }

    /**
     * GET /api/v1/bukus/{id}
     * Detail buku beserta kategori dan riwayat peminjamannya.
     */
    public function show(string $id): JsonResponse
    {
        $buku = Buku::with(['kategori', 'peminjamans' => fn ($query) => $query->latest()])
            ->withCount('peminjamans')
            ->find($id);

        if (! $buku) {
            return $this->notFound($id);
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil ditemukan.',
        );
    }

    /**
     * POST /api/v1/bukus
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $buku = Buku::create($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->createdResponse(
            data: new BukuResource($buku),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: 'Buku baru berhasil dibuat.',
        );
    }

    /**
     * PUT|PATCH /api/v1/bukus/{id}
     * PUT mengganti seluruh representasi, PATCH hanya mengubah atribut yang dikirim.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->notFound($id);
        }

        $isPut = $request->isMethod('put');
        $validated = $request->validate($this->rules($buku, partial: ! $isPut));

        if ($isPut) {
            // Semantik PUT: atribut opsional yang tidak dikirim ikut direset.
            $validated['sinopsis'] ??= null;
        } elseif ($validated === []) {
            return $this->problemResponse(
                title: 'Bad Request',
                detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                status: 400,
                errorSlug: 'empty-patch-payload',
            );
        }

        $buku->update($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            data: new BukuResource($buku),
            message: $isPut
                ? 'Seluruh representasi buku berhasil diganti (PUT).'
                : 'Atribut buku berhasil diperbarui sebagian (PATCH).',
        );
    }

    /**
     * DELETE /api/v1/bukus/{id}
     * Ditolak dengan 409 selama buku masih memiliki peminjaman aktif.
     */
    public function destroy(string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->notFound($id);
        }

        $pinjamanAktif = $buku->peminjamans()->where('status', 'dipinjam')->count();

        if ($pinjamanAktif > 0) {
            return $this->problemResponse(
                title: 'Conflict: Buku Masih Dipinjam',
                detail: "Buku '{$buku->judul}' tidak dapat dihapus karena masih memiliki {$pinjamanAktif} peminjaman aktif.",
                status: 409,
                errorSlug: 'buku-has-active-loans',
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }

    private function rules(?Buku $buku = null, bool $partial = false): array
    {
        $rules = [
            'isbn' => ['required', 'string', 'max:20', Rule::unique('bukus', 'isbn')->ignore($buku?->id)],
            'judul' => ['required', 'string', 'max:200'],
            'penulis' => ['required', 'string', 'max:150'],
            'penerbit' => ['required', 'string', 'max:150'],
            'tahun_terbit' => ['required', 'integer', 'min:1900', 'max:'.date('Y')],
            'stok' => ['required', 'integer', 'min:0'],
            'sinopsis' => ['nullable', 'string'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
        ];

        // PATCH: atribut hanya divalidasi jika dikirim (sometimes|required).
        return $partial
            ? array_map(fn (array $fieldRules) => ['sometimes', ...$fieldRules], $rules)
            : $rules;
    }

    private function notFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Buku dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'buku-not-found',
        );
    }
}
