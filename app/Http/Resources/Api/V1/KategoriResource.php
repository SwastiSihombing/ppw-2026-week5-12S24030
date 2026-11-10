<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KategoriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_kategori' => $this->kode_kategori,
            'nama_kategori' => $this->nama_kategori,
            'deskripsi' => $this->deskripsi,
            'jumlah_buku' => $this->whenCounted('buku'),
            '_links' => [
                'self' => [
                    'href' => url("/api/v1/kategoris/{$this->id}"),
                    'method' => 'GET',
                ],
                'bukus' => [
                    'href' => url("/api/v1/bukus?kategori_id={$this->id}"),
                    'method' => 'GET',
                ],
            ],
        ];
    }
}