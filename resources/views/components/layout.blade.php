<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIPUS-Del' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f3ff;
            color: #1f2937;
        }

        nav {
            background: #4C1D95;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 18px;
            font-weight: bold;
        }

        .nav-logout {
            background: none;
            border: none;
            color: white;
            font-weight: bold;
            font-size: inherit;
            font-family: inherit;
            cursor: pointer;
            padding: 0;
        }

        .brand {
            font-size: 21px;
        }

        main {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 22px;
        }

        h1 {
            color: #4C1D95;
            margin-top: 0;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            background: #4C1D95;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-secondary {
            background: #64748b;
        }

        .btn-danger {
            background: #dc2626;
        }

        .btn-warning {
            background: #d97706;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #ede9fe;
            color: #4C1D95;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
        }

        .error {
            color: #dc2626;
            font-size: 13px;
            margin-top: 5px;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .success,
        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .danger,
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #64748b;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        footer {
            text-align: center;
            padding: 24px;
            color: #64748b;
        }

        @media (max-width: 600px) {
            nav {
                padding: 16px 5%;
            }

            main {
                padding: 0 12px;
            }

            .card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="{{ route('buku.index') }}" class="brand">
            SIPUS-Del
        </a>

        <div>
            <a href="{{ route('buku.index') }}">Data Buku</a>
            <a href="{{ route('peminjaman.index') }}">Peminjaman</a>
            <a href="{{ route('kategori.index') }}">Kategori</a>

            @auth
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="nav-logout">
                        Logout ({{ auth()->user()->name }})
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endauth
        </div>
    </nav>

    <main>
        @if (session('success'))
            <div class="alert success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert danger">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer>
        SIPUS-Del &copy; {{ date('Y') }} — Sistem Informasi Perpustakaan Kampus
    </footer>
</body>
</html>