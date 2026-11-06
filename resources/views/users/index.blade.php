@extends('layouts.app')

@section('content')
<div class="page-heading"><div><p class="eyebrow">Administrasi / Akun</p><h1>Manajemen User</h1><p>Atur akun, role, dan status akses aplikasi.</p></div><a class="btn btn-primary" href="{{ route('users.create') }}"><i class="bi bi-person-plus"></i> Tambah User</a></div>
<section class="data-panel"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Nama</th><th>Username</th><th>Role / Spesialisasi</th><th>Tim</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($users as $user)
        <tr><td>{{ $user->nama_lengkap }}</td><td>{{ $user->username }}</td><td>{{ ['manajemen' => 'Manajemen', 'teknisi1' => 'Teknisi 1', 'teknisi2' => 'Teknisi 2'][$user->role] ?? $user->role }}@if ($user->bidang_teknis)<small class="d-block text-muted">{{ ucfirst($user->bidang_teknis) }}</small>@endif</td><td>{{ $user->asal_tim ? ucfirst($user->asal_tim) : '-' }}</td><td><span class="status-pill status-{{ $user->status === 'aktif' ? 'running' : 'breakdown' }}">{{ $user->status }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit', $user) }}" aria-label="Edit user"><i class="bi bi-pencil"></i></a></td></tr>
    @empty
        <tr><td class="empty-state" colspan="6">Belum ada user.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="px-3 py-3">{{ $users->links() }}</div></section>
@endsection