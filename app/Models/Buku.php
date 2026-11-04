<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buku extends Model
{
    use HasFactory;
    
    protected $table = 'bukus';

    protected $fillable = [
        'isbn',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'stok',
        'sinopsis',
        'kategori_id',
    ];

    protected function casts(): array
    {
        return [
            'tahun_terbit' => 'integer',
            'stok' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}
