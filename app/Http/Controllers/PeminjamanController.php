<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PeminjamanController extends Controller
{
    public function index()
    {
        $peminjamans = Peminjaman::with('buku')
            ->latest()
            ->paginate(10);

        return view('peminjaman.index', compact('peminjamans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_pinjam' => ['required', 'string', 'max:255', 'unique:peminjamans,kode_pinjam'],
            'buku_id' => ['required', 'exists:bukus,id'],
            'nim_peminjam' => ['required', 'string', 'max:20'],
            'nama_peminjam' => ['required', 'string', 'max:150'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $buku = Buku::whereKey($validated['buku_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($buku->stok <= 0) {
                    throw ValidationException::withMessages([
                        'buku_id' => 'Stok buku habis. Peminjaman tidak dapat dilakukan.',
                    ]);
                }

                $peminjamanAktif = Peminjaman::where('nim_peminjam', $validated['nim_peminjam'])
                    ->where('buku_id', $buku->id)
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($peminjamanAktif) {
                    throw ValidationException::withMessages([
                        'nim_peminjam' => 'NIM ini masih memiliki peminjaman aktif untuk buku tersebut.',
                    ]);
                }

                $buku->decrement('stok');

                Peminjaman::create($validated + ['status' => 'dipinjam']);
            });
        } catch (QueryException $e) {
            Log::error('Gagal mencatat peminjaman: ' . $e->getMessage(), [
                'nim' => $validated['nim_peminjam'],
                'buku_id' => $validated['buku_id'],
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan database. Peminjaman dibatalkan.');
        }

        session()->put(
            'peminjaman_terakhir',
            $validated['nim_peminjam'] . ' - ' . $validated['kode_pinjam']
        );

        return redirect()
            ->route('peminjaman.index')
            ->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function kembalikan(Peminjaman $peminjaman)
    {
        try {
            DB::transaction(function () use ($peminjaman) {
                $pinjam = Peminjaman::whereKey($peminjaman->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($pinjam->status === 'dikembalikan') {
                    throw ValidationException::withMessages([
                        'status' => 'Buku ini sudah dikembalikan.',
                    ]);
                }

                Buku::whereKey($pinjam->buku_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->increment('stok');

                $pinjam->update([
                    'status' => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);
            });
        } catch (QueryException $e) {
            Log::error('Gagal mengembalikan buku: ' . $e->getMessage(), [
                'peminjaman_id' => $peminjaman->id,
            ]);

            return back()->with('error', 'Terjadi kesalahan database. Pengembalian dibatalkan.');
        }

        return back()->with('success', 'Buku berhasil dikembalikan.');
    }
}