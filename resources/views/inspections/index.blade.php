@extends('layouts.app')

@section('content')
<div class="page-heading"><div><p class="eyebrow">Operasional / Inspeksi</p><h1>Inspeksi Spindle Harian</h1><p>Riwayat pemeriksaan posisi dan keputusan operasi mesin.</p></div><a class="btn btn-primary" href="{{ route('inspections.create') }}"><i class="bi bi-plus-lg"></i> Catat Inspeksi</a></div>
<section class="data-panel"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Tanggal</th><th>Mesin</th><th>Posisi Diperiksa</th><th>Temuan Posisi</th><th>Keputusan</th><th>Dicatat Oleh</th><th>Catatan</th></tr></thead>
    <tbody>
    @forelse ($inspections as $inspection)
        <tr><td>{{ $inspection->tanggal->format('d/m/Y') }}</td><td><strong>{{ $inspection->machine->kode_mesin }}</strong></td><td>{{ $inspection->total_posisi }}</td><td>@forelse ($inspection->positions as $position)<div>Pos {{ $position->nomor_posisi }} · {{ str_replace('_', ' ', $position->jenis_problem) }}@if ($position->catatan)<small class="d-block text-muted">{{ $position->catatan }}</small>@endif</div>@empty<span class="text-muted">Semua normal</span>@endforelse</td><td><span class="status-pill status-{{ $inspection->keputusan_mesin === 'stop' ? 'breakdown' : 'running' }}">{{ $inspection->keputusan_mesin === 'stop' ? 'Stop' : 'Running' }}</span></td><td>{{ $inspection->inspector->nama_lengkap ?? 'Akun dihapus' }}</td><td>{{ $inspection->catatan ?: '-' }}</td></tr>
    @empty
        <tr><td class="empty-state" colspan="7">Belum ada inspeksi spindle.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="px-3 py-3">{{ $inspections->links() }}</div></section>
@endsection