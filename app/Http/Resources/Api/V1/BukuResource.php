<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $href = url("/api/v1/bukus/{$this->id}");

        $links = [
            'self' => ['href' => $href, 'method' => 'GET'],
            'koleksi' => ['href' => url('/api/v1/bukus'), 'method' => 'GET'],
            'kategori' => ['href' => url("/api/v1/kategoris/{$this->kategori_id}"), 'method' => 'GET'],
            'referensi_eksternal' => ['href' => "{$href}/referensi-eksternal", 'method' => 'GET'],
            'update_penuh' => ['href' => $href, 'method' => 'PUT'],
            'update_sebagian' => ['href' => $href, 'method' => 'PATCH'],
            'hapus' => ['href' => $href, 'method' => 'DELETE'],
        ];

        // HATEOAS dinamis: tautan pinjam hanya muncul selama stok masih tersedia.
        if ($this->stok > 0) {
            $links['pinjam_buku'] = ['href' => url('/api/v1/peminjamans'), 'method' => 'POST'];
        }

        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'judul' => $this->judul,
            'penulis' => $this->penulis,
            'penerbit' => $this->penerbit,
            'tahun_terbit' => $this->tahun_terbit,
            'stok' => $this->stok,
            'status_ketersediaan' => $this->stok > 0 ? 'TERSEDIA' : 'HABIS',
            'sinopsis' => $this->sinopsis,
            'kategori' => new KategoriResource($this->whenLoaded('kategori')),
            'jumlah_peminjaman' => $this->whenCounted('peminjamans'),
            'riwayat_peminjaman' => PeminjamanResource::collection($this->whenLoaded('peminjamans')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            '_links' => $links,
        ];
    }
}