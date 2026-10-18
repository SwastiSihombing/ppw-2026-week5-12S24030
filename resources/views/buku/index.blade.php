<x-layout>
    <div class="card">
        <h1>Daftar Buku</h1>
        <p>Kelola koleksi buku perpustakaan kampus melalui halaman ini.</p>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Kartu statistik (DB::table) --}}
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin:16px 0;">
            <div style="flex:1; min-width:160px; padding:14px; border:1px solid #e5e0f5; border-radius:8px; background:#f7f3ff;">
                <div style="font-size:24px; font-weight:bold;">{{ $stats['total_judul'] }}</div>
                <div>Total Judul Buku</div>
            </div>
            <div style="flex:1; min-width:160px; padding:14px; border:1px solid #e5e0f5; border-radius:8px; background:#f7f3ff;">
                <div style="font-size:24px; font-weight:bold;">{{ $stats['total_stok'] }}</div>
                <div>Total Stok Tersedia</div>
            </div>
            <div style="flex:1; min-width:160px; padding:14px; border:1px solid #e5e0f5; border-radius:8px; background:#f7f3ff;">
                <div style="font-size:24px; font-weight:bold;">{{ $stats['sedang_dipinjam'] }}</div>
                <div>Sedang Dipinjam</div>
            </div>
            <div style="flex:1; min-width:160px; padding:14px; border:1px solid #e5e0f5; border-radius:8px; background:#f7f3ff;">
                <div style="font-size:24px; font-weight:bold;">{{ $stats['total_dikembalikan'] }}</div>
                <div>Total Dikembalikan</div>
            </div>
        </div>

        <a href="{{ route('buku.create') }}" class="btn">
            + Tambah Buku
        </a>

        {{-- Form pencarian dan filter dinamis --}}
        <form method="GET" action="{{ route('buku.index') }}" style="display:flex; gap:8px; flex-wrap:wrap; margin:16px 0;">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari judul / penulis / ISBN"
                style="flex:1; min-width:200px;"
            >

            <select name="kategori_id">
                <option value="">Semua Kategori</option>
                @foreach ($kategori as $k)
                    <option value="{{ $k->id }}" @selected(request('kategori_id') == $k->id)>
                        {{ $k->nama_kategori }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn">Cari</button>

            @if (request('search') || request('kategori_id'))
                <a href="{{ route('buku.index') }}" class="btn btn-warning">Reset</a>
            @endif
        </form>

        {{-- State dari Session dan Cookie --}}
        @if (session('riwayat_pencarian') || session('peminjaman_terakhir'))
            <div style="margin-bottom:16px; font-size:14px;">
                @if (session('riwayat_pencarian'))
                    <div>
                        <strong>Riwayat pencarian:</strong>
                        @foreach (session('riwayat_pencarian') as $kata)
                            <a href="{{ route('buku.index', ['search' => $kata]) }}">{{ $kata }}</a>@if (! $loop->last), @endif
                        @endforeach
                    </div>
                @endif

                @if (session('peminjaman_terakhir'))
                    <div>
                        <strong>Peminjaman terakhir:</strong>
                        {{ session('peminjaman_terakhir') }}
                    </div>
                @endif
            </div>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>ISBN</th>
                        <th>Judul Buku</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Tahun</th>
                        <th>Stok</th>
                        <th>Dipinjam</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($buku as $item)
                        <tr>
                            <td>
                                {{ $buku->firstItem() + $loop->index }}
                            </td>
                            <td>{{ $item->isbn }}</td>
                            <td>{{ $item->judul }}</td>
                            <td>{{ $item->penulis }}</td>
                            <td>
                                {{ $item->kategori?->nama_kategori ?? 'Tanpa kategori' }}
                            </td>
                            <td>{{ $item->tahun_terbit }}</td>
                            <td>{{ $item->stok }}</td>
                            <td>{{ $item->peminjamans_count }}x</td>
                            <td>
                                <div class="actions">
                                    <a
                                        href="{{ route('buku.show', $item) }}"
                                        class="btn"
                                    >
                                        Detail
                                    </a>

                                    <a
                                        href="{{ route('buku.edit', $item) }}"
                                        class="btn btn-warning"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="{{ route('buku.destroy', $item) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus buku ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">
                                Tidak ada buku yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $buku->links() }}
        </div>
    </div>
</x-layout>