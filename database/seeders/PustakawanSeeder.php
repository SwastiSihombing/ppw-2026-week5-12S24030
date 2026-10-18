<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PustakawanSeeder extends Seeder
{
    public function run(): void
    {
        // Akun pustakawan uji
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name' => 'Pustakawan',
                'password' => Hash::make('password123'),
            ]
        );

        $kategori = Kategori::first();

        // Buku dengan stok 0 (untuk uji penolakan peminjaman)
        $habis = Buku::updateOrCreate(
            ['isbn' => '9780000000001'],
            [
                'judul' => 'Buku Uji Stok Habis',
                'penulis' => 'Penulis A',
                'penerbit' => 'Penerbit Uji',
                'tahun_terbit' => 2024,
                'stok' => 0,
                'kategori_id' => $kategori->id,
            ]
        );    
    }     
}   