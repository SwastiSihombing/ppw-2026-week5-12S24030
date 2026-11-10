<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BukuApiController;
use App\Http\Controllers\Api\V1\BukuReferensiApiController;
use App\Http\Controllers\Api\V1\KategoriApiController;
use App\Http\Controllers\Api\V1\PeminjamanApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Endpoint publik
    Route::post('/auth/login', [AuthTokenController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/kategoris', [KategoriApiController::class, 'index']);
    Route::get('/kategoris/{id}', [KategoriApiController::class, 'show'])->whereNumber('id');

    Route::get('/bukus', [BukuApiController::class, 'index']);
    Route::get('/bukus/{id}', [BukuApiController::class, 'show'])->whereNumber('id');
    Route::get('/bukus/{id}/referensi-eksternal', [BukuReferensiApiController::class, 'show'])->whereNumber('id');

    // Endpoint yang wajib memakai Bearer Token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me']);
        Route::post('/auth/logout', [AuthTokenController::class, 'logout']);

        Route::post('/bukus', [BukuApiController::class, 'store']);
        Route::match(['put', 'patch'], '/bukus/{id}', [BukuApiController::class, 'update'])->whereNumber('id');
        Route::delete('/bukus/{id}', [BukuApiController::class, 'destroy'])->whereNumber('id');

        Route::get('/peminjamans', [PeminjamanApiController::class, 'index']);
        Route::post('/peminjamans', [PeminjamanApiController::class, 'store']);
        Route::get('/peminjamans/{id}', [PeminjamanApiController::class, 'show'])->whereNumber('id');
        Route::patch('/peminjamans/{id}/kembalikan', [PeminjamanApiController::class, 'kembalikan'])->whereNumber('id');
    });
});