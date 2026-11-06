@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Analisis Maintenance</p><h1>Laporan Mesin</h1><p>Bandingkan kondisi, inspeksi, dan maintenance per mesin.</p></div>
</div>

<section class="form-panel mb-4">
    <form method="get" action="{{ route('machine-reports.index') }}" class="form-grid align-items-end">
        <div><label class="form-label" for="from">Dari tanggal</label><input class="form-control" id="from" type="date" name="from" value="{{ $from }}" required></div>
        <div><label class="form-label" for="to">Sampai tanggal</label><input class="form-control" id="to" type="date" name="to" value="{{ $to }}" required></div>
        <div><label class="form-label" for="mesin_id">Mesin</label><select class="form-select" id="mesin_id" name="mesin_id"><option value="">Semua mesin</option>@foreach ($machineOptions as $machine)<option value="{{ $machine->id }}" @selected(($filters['mesin_id'] ?? '') == $machine->id)>{{ $machine->kode_mesin }} · {{ $machine->nama_mesin }}</option>@endforeach</select></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Tampilkan</button><a class="btn btn-outline-secondary" href="{{ route('machine-reports.index') }}">30 hari terakhir</a></div>
    </form>
</section>

<section class="data-panel mb-4">
    <div class="panel-heading"><div><p class="eyebrow">{{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</p><h2>Ringkasan per Mesin</h2></div></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Mesin / Kondisi</th><th>Shift</th><th>Rata-rata EFF</th><th>Total Break</th><th>Kerusakan</th><th>Downtime</th><th>Inspeksi / Posisi Bermasalah</th><th>PM Selesai / Terlambat</th></tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                <td><strong>{{ $row->kode_mesin }}</strong><small class="d-block text-muted">{{ $row->nama_mesin }} · {{ str_replace('_', ' ', $row->status_kondisi) }}</small></td>
                <td>{{ $row->jumlah_shift }}</td><td>{{ number_format($row->rata_eff, 1) }}%</td><td>{{ $row->total_break }}</td>
                <td>{{ $row->jumlah_kerusakan }}<small class="d-block text-muted">{{ $row->pekerjaan_terbuka }} belum selesai</small></td>
                <td>{{ intdiv($row->downtime_menit, 60) }}j {{ $row->downtime_menit % 60 }}m</td>
                <td>{{ $row->jumlah_inspeksi }} / {{ $row->posisi_bermasalah }}</td>
                <td>{{ $row->pm_selesai }} / <span class="{{ $row->pm_terlambat ? 'text-danger fw-semibold' : '' }}">{{ $row->pm_terlambat }}</span></td>
            </tr>
        @empty
            <tr><td class="empty-state" colspan="8">Tidak ada mesin yang sesuai filter.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="data-panel">
    <div class="panel-heading"><div><p class="eyebrow">Riwayat terfilter</p><h2>Aktivitas Maintenance</h2></div></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Tanggal</th><th>Mesin / Posisi</th><th>Kategori</th><th>Problem / Tindakan</th><th>Teknisi</th><th>Status</th><th>Downtime</th></tr></thead>
        <tbody>
        @forelse ($maintenanceLogs as $log)
            <tr><td>{{ \Illuminate\Support\Carbon::parse($log->tanggal)->format('d/m/Y') }}</td><td><strong>{{ $log->kode_mesin }}</strong><small class="d-block text-muted">{{ $log->posisi_mesin }}</small></td><td>{{ ucfirst($log->area_kerusakan ?? '-') }}</td><td>{{ \Illuminate\Support\Str::limit($log->problem, 80) }}<small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($log->action, 80) }}</small>@if ($log->tindak_lanjut)<small class="d-block text-success">Hasil: {{ \Illuminate\Support\Str::limit($log->tindak_lanjut, 80) }}</small>@endif</td><td>{{ $log->nama_teknisi ?? '-' }}</td><td>{{ ['proses' => 'Sedang diperbaiki', 'selesai' => 'Selesai', 'pending_sparepart' => 'Menunggu spare part', 'monitoring' => 'Monitoring'][$log->status_perbaikan] ?? str_replace('_', ' ', $log->status_perbaikan) }}</td><td>{{ $log->downtime_menit !== null ? $log->downtime_menit . ' menit' : '-' }}</td></tr>
        @empty
            <tr><td class="empty-state" colspan="7">Tidak ada aktivitas maintenance pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="px-3 py-3">{{ $maintenanceLogs->links() }}</div>
</section>
@endsection
