<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $href = url("/api/v1/peminjamans/{$this->id}");

        // Riwayat juga tampil pada detail buku yang publik, jadi identitas peminjam
        // hanya dibuka utuh untuk pemegang Bearer Token.
        $terautentikasi = $request->user('sanctum') !== null;

        $links = [
            'self' => ['href' => $href, 'method' => 'GET'],
            'buku' => ['href' => url("/api/v1/bukus/{$this->buku_id}"), 'method' => 'GET'],
        ];

        // HATEOAS dinamis: tautan kembalikan hanya muncul selama buku masih dipinjam.
        if ($this->status === 'dipinjam') {
            $links['kembalikan_buku'] = ['href' => "{$href}/kembalikan", 'method' => 'PATCH'];
        }

        return [
            'id' => $this->id,
            'kode_pinjam' => $this->kode_pinjam,
            'nim_peminjam' => $terautentikasi ? $this->nim_peminjam : Str::mask($this->nim_peminjam, '*', 4),
            'nama_peminjam' => $terautentikasi ? $this->nama_peminjam : Str::mask($this->nama_peminjam, '*', 1),
            'tanggal_pinjam' => $this->tanggal_pinjam?->toDateString(),
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo?->toDateString(),
            'tanggal_kembali' => $this->tanggal_kembali?->toDateString(),
            'status' => $this->status,
            'buku' => $this->whenLoaded('buku', fn () => [
                'id' => $this->buku->id,
                'isbn' => $this->buku->isbn,
                'judul' => $this->buku->judul,
                'stok' => $this->buku->stok,
            ]),
            '_links' => $links,
        ];
    }
}