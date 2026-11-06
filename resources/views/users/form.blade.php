@extends('layouts.app')

@section('content')
@php($editing = (bool) $user)
<div class="page-heading"><div><p class="eyebrow">Administrasi / Akun</p><h1>{{ $editing ? 'Edit User' : 'Tambah User' }}</h1><p>Role menentukan akses menu dan tindakan di aplikasi.</p></div><a class="btn btn-outline-secondary" href="{{ route('users.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a></div>
<section class="form-panel"><form method="post" action="{{ $editing ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="form-grid">
        <div><label class="form-label" for="nama_lengkap">Nama Lengkap *</label><input class="form-control" id="nama_lengkap" name="nama_lengkap" maxlength="100" value="{{ old('nama_lengkap', $user->nama_lengkap ?? '') }}" required></div>
        <div><label class="form-label" for="username">Username *</label><input class="form-control" id="username" name="username" maxlength="50" value="{{ old('username', $user->username ?? '') }}" required></div>
        <div><label class="form-label" for="role">Role *</label><select class="form-select" id="role" name="role" required>@foreach (['manajemen' => 'Manajemen', 'teknisi1' => 'Teknisi 1 · Mekanik DTY', 'teknisi2' => 'Teknisi 2 · Indorama'] as $value => $label)<option value="{{ $value }}" @selected(old('role', $user->role ?? 'teknisi1') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="form-label" for="status">Status *</label><select class="form-select" id="status" name="status" required>@foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $user->status ?? 'aktif') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div id="technician-specialty-field"><label class="form-label" for="bidang_teknis">Bidang Teknisi 2 *</label><select class="form-select" id="bidang_teknis" name="bidang_teknis"><option value="">Pilih bidang</option><option value="elektrik" @selected(old('bidang_teknis', $user->bidang_teknis ?? '') === 'elektrik')>Elektrik</option><option value="instrumen" @selected(old('bidang_teknis', $user->bidang_teknis ?? '') === 'instrumen')>Instrumen / Sensor</option></select></div>
        <div><label class="form-label" for="password">{{ $editing ? 'Password Baru' : 'Password *' }}</label><input class="form-control" id="password" name="password" type="password" minlength="8" {{ $editing ? '' : 'required' }} autocomplete="new-password"></div>
        <div><label class="form-label" for="password_confirmation">Konfirmasi Password {{ $editing ? '' : '*' }}</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" {{ $editing ? '' : 'required' }} autocomplete="new-password"></div>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan User</button><a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Batal</a></div>
</form></section>
@endsection

@push('scripts')
<script>
    const userRole = document.getElementById('role');
    const specialtySelect = document.getElementById('bidang_teknis');

    function updateTechnicianFields() {
        const isTechnician2 = userRole.value === 'teknisi2';
        document.getElementById('technician-specialty-field').hidden = !isTechnician2;
        specialtySelect.required = isTechnician2;
    }

    userRole.addEventListener('change', updateTechnicianFields);
    updateTechnicianFields();
</script>
@endpush