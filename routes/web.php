<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('buku.index'));

// Login (hanya untuk tamu)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Semua rute mutasi dan transaksi dilindungi auth
Route::middleware('auth')->group(function () {
    Route::resource('buku', BukuController::class)->except(['index', 'show']);
    Route::resource('kategori', KategoriController::class)->except(['index', 'show']);

    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::patch('/peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikan'])
        ->name('peminjaman.kembalikan');
});

// Katalog publik (dibaca tanpa login). Didefinisikan SETELAH buku/create
Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');
Route::get('/buku/{buku}', [BukuController::class, 'show'])->name('buku.show');
Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
Route::get('/kategori/{kategori}', [KategoriController::class, 'show'])->name('kategori.show');