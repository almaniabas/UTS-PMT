<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><i class="bi bi-gear-wide-connected"></i><span><strong>SIM-DTY</strong><small>Maintenance & Operations</small></span></a>
        <div class="side-label">Menu Utama</div>
        <a class="side-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i>Ringkasan</a>
        @if (session('role') === 'manajemen')
            <a class="side-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people"></i>Manajemen User</a>
        @endif
        <a class="side-link {{ request()->routeIs('machines.*') ? 'active' : '' }}" href="{{ route('machines.index') }}"><i class="bi bi-hdd-network"></i>Mesin DTY</a>
        <a class="side-link {{ request()->routeIs('machine-reports.*') ? 'active' : '' }}" href="{{ route('machine-reports.index') }}"><i class="bi bi-graph-up"></i>Laporan Mesin</a>
        @if (in_array(session('role'), ['manajemen', 'teknisi1'], true))
            <div class="side-label">Maintenance</div>
            <a class="side-link {{ request()->routeIs('maintenance.index', 'maintenance.create') ? 'active' : '' }}" href="{{ route('maintenance.index') }}"><i class="bi bi-calendar2-check"></i>Jadwal</a>
        @endif
        <a class="side-link {{ request()->routeIs('maintenance.logs*') ? 'active' : '' }}" href="{{ route('maintenance.logs') }}"><i class="bi bi-journal-text"></i>Catatan Buku
            @if ($newEscalationCount > 0)<span class="notification-badge">{{ $newEscalationCount }}</span>@endif
        </a>
        <div class="sidebar-bottom"><span>{{ session('nama_lengkap') }}</span><small>@if (in_array(session('role'), ['teknisi1', 'teknisi2'], true)){{ strtoupper(session('bidang_teknis') ?? session('role')) }} · {{ strtoupper(session('asal_tim') ?? '') }}@else{{ strtoupper(session('role')) }}@endif</small></div>
    </aside>

    <div class="main-shell">
        <header class="topbar">
            <div><span class="topbar-kicker">PT Indorama Polychem</span><strong>Divisi Tekstil DTY</strong></div>
            <div class="user-block"><span>{{ session('nama_lengkap') }} <small>@if (in_array(session('role'), ['teknisi1', 'teknisi2'], true)){{ strtoupper(session('bidang_teknis') ?? session('role')) }} · {{ strtoupper(session('asal_tim') ?? '') }}@else{{ strtoupper(session('role')) }}@endif</small></span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-danger" title="Logout"><i class="bi bi-box-arrow-right"></i><span class="ms-1">Keluar</span></button></form>
            </div>
        </header>
        <main class="page-content">
            @if (session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if (session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if ($errors->any())<div class="alert alert-danger"><strong>Periksa kembali data berikut:</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if ($newEscalationCount > 0)
                <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3" role="status">
                    <span><i class="bi bi-bell-fill me-2"></i>Ada {{ $newEscalationCount }} tiket eskalasi baru yang menunggu penanganan Anda.</span>
                    <a class="btn btn-sm btn-warning flex-shrink-0" href="{{ route('maintenance.logs') }}">Lihat tiket</a>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>