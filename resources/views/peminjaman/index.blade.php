<x-layout>
    <div class="container">
        <h1>Daftar Peminjaman</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2>Form Peminjaman Buku</h2>

        <form action="{{ route('peminjaman.store') }}" method="POST">
            @csrf

            <div>
                <label for="kode_pinjam">Kode Peminjaman</label>
                <input
                    type="text"
                    id="kode_pinjam"
                    name="kode_pinjam"
                    value="{{ old('kode_pinjam') }}"
                    required
                >
            </div>

            <div>
                <label for="buku_id">Buku</label>
                <select id="buku_id" name="buku_id" required>
                    <option value="">-- Pilih Buku --</option>
                    @foreach (\App\Models\Buku::orderBy('judul')->get() as $buku)
                        <option
                            value="{{ $buku->id }}"
                            @selected(old('buku_id') == $buku->id)
                        >
                            {{ $buku->judul }} (Stok: {{ $buku->stok }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="nim_peminjam">NIM Peminjam</label>
                <input
                    type="text"
                    id="nim_peminjam"
                    name="nim_peminjam"
                    value="{{ old('nim_peminjam') }}"
                    required
                >
            </div>

            <div>
                <label for="nama_peminjam">Nama Peminjam</label>
                <input
                    type="text"
                    id="nama_peminjam"
                    name="nama_peminjam"
                    value="{{ old('nama_peminjam') }}"
                    required
                >
            </div>

            <div>
                <label for="tanggal_pinjam">Tanggal Pinjam</label>
                <input
                    type="date"
                    id="tanggal_pinjam"
                    name="tanggal_pinjam"
                    value="{{ old('tanggal_pinjam', now()->toDateString()) }}"
                    required
                >
            </div>

            <div>
                <label for="tanggal_jatuh_tempo">Tanggal Jatuh Tempo</label>
                <input
                    type="date"
                    id="tanggal_jatuh_tempo"
                    name="tanggal_jatuh_tempo"
                    value="{{ old('tanggal_jatuh_tempo') }}"
                    required
                >
            </div>

            <button type="submit">Simpan Peminjaman</button>
        </form>

        <hr>

        <h2>Riwayat Peminjaman</h2>

        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peminjamans as $peminjaman)
                    <tr>
                        <td>{{ $peminjaman->kode_pinjam }}</td>
                        <td>{{ $peminjaman->nim_peminjam }}</td>
                        <td>{{ $peminjaman->nama_peminjam }}</td>
                        <td>{{ $peminjaman->buku->judul }}</td>
                        <td>{{ $peminjaman->tanggal_pinjam->format('d-m-Y') }}</td>
                        <td>{{ $peminjaman->tanggal_jatuh_tempo->format('d-m-Y') }}</td>
                        <td>
                            {{ $peminjaman->tanggal_kembali
                                ? $peminjaman->tanggal_kembali->format('d-m-Y')
                                : '-' }}
                        </td>
                        <td>{{ ucfirst($peminjaman->status) }}</td>
                        <td>
                            @if ($peminjaman->status === 'dipinjam')
                                <form action="{{ route('peminjaman.kembalikan', $peminjaman) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit">Kembalikan</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">Belum ada transaksi peminjaman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $peminjamans->links() }}
    </div>
</x-layout>