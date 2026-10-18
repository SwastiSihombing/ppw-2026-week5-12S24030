<x-layout>
    <div class="card" style="max-width:420px; margin:0 auto;">
        <h1>Login Pustakawan</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <div style="margin-bottom:12px;">
                <label for="email">Email</label><br>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required style="width:100%;">
            </div>

            <div style="margin-bottom:12px;">
                <label for="password">Password</label><br>
                <input type="password" id="password" name="password" required style="width:100%;">
            </div>

            <div style="margin-bottom:12px;">
                <label>
                    <input type="checkbox" name="remember" value="1"> Ingat saya
                </label>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</x-layout>