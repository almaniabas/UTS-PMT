<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="login-page">
    <main class="login-panel">
        <div class="login-brand"><i class="bi bi-gear-wide-connected"></i><span>SIM-DTY<small>Maintenance & Operations</small></span></div>
        <p class="eyebrow">PT Indorama Polychem · Divisi Tekstil</p>
        <h1>Masuk ke sistem</h1>
        <p class="login-copy">Kelola armada mesin, perawatan, pencatatan shift, dan distribusi.</p>
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form id="login-form" method="post" action="{{ route('login.store') }}">
            @csrf
            <label class="form-label" for="username">Username</label>
            <input class="form-control form-control-lg mb-3" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            <label class="form-label" for="password">Password</label>
            <input class="form-control form-control-lg mb-4" id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="btn btn-primary btn-lg w-100" type="submit">Masuk <i class="bi bi-arrow-right ms-1"></i></button>
        </form>
        <div class="demo-accounts"><span>Akun uji coba</span><div>
            @foreach (['manajemen' => 'manajemen', 'teknisi1' => 'teknisi1', 'teknisi2' => 'teknisi2'] as $username => $label)
                <button class="demo-account-button" type="button" data-username="{{ $username }}">{{ $label }}</button>
            @endforeach
        </div><small>Password: admin123</small></div>
    </main>
    <script>
        document.querySelectorAll('.demo-account-button').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('username').value = button.dataset.username;
                document.getElementById('password').value = 'admin123';
                document.getElementById('login-form').requestSubmit();
            });
        });
    </script>
</body>
</html>