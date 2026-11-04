<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BukuController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $kategoriId = $request->query('kategori_id');

        $buku = Buku::with('kategori')
            ->withCount('peminjamans')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($kategoriId, fn ($query, $id) => $query->where('kategori_id', $id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total_judul'        => DB::table('bukus')->count(),
            'total_stok'         => DB::table('bukus')->sum('stok'),
            'sedang_dipinjam'    => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'total_dikembalikan' => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
        ];

        // State pengguna di Session dan Cookie (poin 4)
        if ($search) {
            $riwayat = collect(session('riwayat_pencarian', []))
                ->prepend($search)
                ->unique()
                ->take(5)
                ->values()
                ->all();

            session()->put('riwayat_pencarian', $riwayat);
            Cookie::queue('last_search', $search, 60);
        }

        $kategori = Kategori::orderBy('nama_kategori')->get();

        return view('buku.index', compact('buku', 'stats', 'kategori'));
    }

    public function create()
    {
        $kategori = Kategori::orderBy('nama_kategori')->get();

        return view('buku.create', compact('kategori'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn' => ['required', 'string', 'max:20', 'unique:bukus,isbn'],
            'judul' => ['required', 'string', 'max:200'],
            'penulis' => ['required', 'string', 'max:150'],
            'penerbit' => ['required', 'string', 'max:150'],
            'tahun_terbit' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
            'stok' => ['required', 'integer', 'min:0'],
            'sinopsis' => ['nullable', 'string'],
            'kategori_id' => ['required', 'exists:kategoris,id'],
        ]);

        Buku::create($validated);

        return redirect()
            ->route('buku.index')
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    public function show(Buku $buku)
    {
        $buku->load('kategori');

        return view('buku.show', compact('buku'));
    }

    public function edit(Buku $buku)
    {
        $kategori = Kategori::orderBy('nama_kategori')->get();

        return view('buku.edit', compact('buku', 'kategori'));
    }

    public function update(Request $request, Buku $buku)
    {
        $validated = $request->validate([
            'isbn' => [
                'required',
                'string',
                'max:20',
                Rule::unique('bukus', 'isbn')->ignore($buku->id),
            ],
            'judul' => ['required', 'string', 'max:200'],
            'penulis' => ['required', 'string', 'max:150'],
            'penerbit' => ['required', 'string', 'max:150'],
            'tahun_terbit' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
            'stok' => ['required', 'integer', 'min:0'],
            'sinopsis' => ['nullable', 'string'],
            'kategori_id' => ['required', 'exists:kategoris,id'],
        ]);

        $buku->update($validated);

        return redirect()
            ->route('buku.index')
            ->with('success', 'Buku berhasil diperbarui.');
    }

    public function destroy(Buku $buku)
    {
        $buku->delete();

        return redirect()
            ->route('buku.index')
            ->with('success', 'Buku berhasil dihapus.');
    }
}