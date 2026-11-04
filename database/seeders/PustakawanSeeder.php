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

        // Buku dengan stok 1 (untuk uji pinjam sampai stok jadi 0)
        Buku::updateOrCreate(
            ['isbn' => '9780000000002'],
            [
                'judul' => 'Buku Uji Stok Satu',
                'penulis' => 'Penulis B',
                'penerbit' => 'Penerbit Uji',
                'tahun_terbit' => 2024,
                'stok' => 1,
                'kategori_id' => $kategori->id,
            ]
        );

        // Sampel transaksi peminjaman
        Peminjaman::updateOrCreate(
            ['kode_pinjam' => 'PJM-SEED0001'],
            [
                'buku_id' => $habis->id,
                'nim_peminjam' => '12S24001',
                'nama_peminjam' => 'Mahasiswa Contoh',
                'tanggal_pinjam' => now()->subDays(3)->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays(4)->toDateString(),
                'status' => 'dipinjam',
            ]
        );
    }
}