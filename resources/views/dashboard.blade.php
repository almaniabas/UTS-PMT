@extends('layouts.app')

@section('content')
<div class="page-heading dashboard-heading">
    <div><p class="eyebrow">SIM-DTY / Overview</p><h1>Ringkasan Operasional</h1><p>Monitoring kondisi mesin, preventive maintenance, dan performa shift.</p></div>
    <span class="today-label"><i class="bi bi-calendar3"></i> {{ now()->translatedFormat('l, d F Y') }}</span>
</div>

<div class="kpi-grid">
    <article class="kpi-card kpi-green"><span>Mesin Running</span><strong>{{ $kpiMesin->total_running ?? 0 }}<small> / {{ $kpiMesin->total_mesin ?? 0 }} MC</small></strong><small>Mesin beroperasi normal</small><i class="bi bi-hdd-stack"></i></article>
    <article class="kpi-card kpi-red"><span>Mesin Breakdown</span><strong>{{ $kpiMesin->total_breakdown ?? 0 }}<small> MC</small></strong><small>Perlu penanganan</small><i class="bi bi-exclamation-octagon"></i></article>
    <article class="kpi-card kpi-amber"><span>Mesin Maintenance</span><strong>{{ $kpiMesin->total_maintenance ?? 0 }}<small> MC</small></strong><small>Sedang dalam perawatan</small><i class="bi bi-tools"></i></article>
    <article class="kpi-card kpi-blue"><span>Mesin Idle</span><strong>{{ $kpiMesin->total_idle ?? 0 }}<small> MC</small></strong><small>Tidak sedang beroperasi</small><i class="bi bi-pause-circle"></i></article>
    <article class="kpi-card kpi-amber"><span>PM Terlambat</span><strong>{{ $overdueSchedules }}</strong><small>Lewat dari tanggal rencana</small><i class="bi bi-calendar-x"></i></article>
    <article class="kpi-card kpi-red"><span>Downtime & Eskalasi</span><strong>{{ intdiv((int) $breakdownSummary->downtime_menit, 60) }}j {{ (int) $breakdownSummary->downtime_menit % 60 }}m</strong><small>{{ $breakdownSummary->total_breakdown }} breakdown hari ini · {{ $pendingEscalations }} eskalasi tertunda</small><a href="{{ route('maintenance.logs') }}">Lihat catatan <i class="bi bi-arrow-up-right"></i></a><i class="bi bi-clock-history"></i></article>
</div>

@if ($showSchedules)
<section class="data-panel dashboard-panel mb-4">
    <div class="panel-heading"><div><p class="eyebrow">Preventive Maintenance</p><h2>Pengingat PM Rutin Dua Bulanan</h2></div><a href="{{ route('maintenance.index') }}" class="text-link">Kelola jadwal <i class="bi bi-arrow-up-right"></i></a></div>
    <div class="pm-reminder-note"><i class="bi bi-info-circle"></i><span>Jatuh tempo 2 bulan setelah PM rutin terakhir. Pengingat tidak ditampilkan jika jadwal berikutnya sudah dibuat.</span></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mesin</th><th>PM Rutin Terakhir</th><th>Jatuh Tempo Berikutnya</th><th>Status</th></tr></thead><tbody>
        @forelse ($routinePmReminders as $reminder)
            <tr><td><strong>{{ $reminder->kode_mesin }}</strong><small class="d-block text-muted">{{ $reminder->nama_mesin }}</small></td><td>{{ $reminder->tanggal_pm_terakhir->format('d/m/Y') }}</td><td>{{ $reminder->tanggal_jatuh_tempo->format('d/m/Y') }}</td><td>@if ($reminder->tanggal_jatuh_tempo->lessThanOrEqualTo(today()))<span class="status-pill status-terlambat">Jatuh tempo</span>@else<span class="status-pill status-terjadwal">Belum jatuh tempo</span>@endif</td></tr>
        @empty
            <tr><td class="empty-state" colspan="4">Belum ada mesin yang memerlukan pengingat. Perhitungan dimulai setelah realisasi PM rutin pertama dan berhenti sementara jika jadwal berikutnya sudah dibuat.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endif

<div class="dashboard-grid {{ $showSchedules ? '' : 'single-panel' }}">
    @if ($showSchedules)
    <section class="data-panel dashboard-panel">
        <div class="panel-heading"><div><p class="eyebrow">Preventive Maintenance</p><h2>Jadwal Terdekat</h2></div><a href="{{ route('maintenance.index') }}" class="text-link">Semua jadwal <i class="bi bi-arrow-up-right"></i></a></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Mesin</th><th>Rencana</th><th>Teknisi</th><th>Status PM</th><th></th></tr></thead><tbody>
            @forelse ($jadwalList as $schedule)
                <tr><td><strong>{{ $schedule->machine->kode_mesin }}</strong><small class="d-block text-muted">{{ $schedule->machine->nama_mesin }}</small></td><td>{{ $schedule->tanggal_rencana->format('d/m/Y') }}</td><td>{{ $schedule->technician->nama_lengkap ?? 'Belum ditugaskan' }}</td><td><span class="status-pill status-{{ $schedule->status === 'terlambat' ? 'terlambat' : ($schedule->status === 'proses' ? 'proses' : 'terjadwal') }}">@if ($schedule->status === 'terlambat')<i class="bi bi-exclamation-circle-fill me-1"></i>Terlambat · belum selesai @elseif ($schedule->status === 'proses')<i class="bi bi-arrow-repeat me-1"></i>Sedang dikerjakan @else<i class="bi bi-hourglass-split me-1"></i>Belum selesai @endif</span></td><td><details><summary class="btn btn-sm btn-danger"><i class="bi bi-check2-circle me-1"></i>Tandai selesai</summary><form method="post" action="{{ route('maintenance.complete', $schedule->id) }}" class="mt-2">@csrf<label class="form-label small" for="dashboard-realisasi-{{ $schedule->id }}">Hasil PM / spare part diganti</label><textarea class="form-control form-control-sm mb-2" id="dashboard-realisasi-{{ $schedule->id }}" name="catatan_realisasi" rows="3" maxlength="5000" placeholder="Catat hasil pemeriksaan dan komponen yang diganti, atau tulis tidak ada penggantian."></textarea><button class="btn btn-sm btn-success">Simpan penyelesaian</button></form></details></td></tr>
            @empty<tr><td colspan="5" class="empty-state">Tidak ada jadwal tertunda.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    @endif
    <section class="data-panel dashboard-panel">
        <div class="panel-heading"><div><p class="eyebrow">Log Lapangan</p><h2>Catatan Maintenance Terbaru</h2></div><a href="{{ route('maintenance.logs') }}" class="text-link">Semua catatan <i class="bi bi-arrow-up-right"></i></a></div>
        <div class="recent-list">
            @forelse ($recentLogs as $log)
                <article class="recent-item"><div class="recent-date">{{ $log->tanggal->format('d/m') }}</div><div><strong>{{ $log->machine->kode_mesin }} · {{ $log->posisi_mesin }}</strong><p>{{ \Illuminate\Support\Str::limit($log->problem, 96) }}</p><small>{{ $log->handler->nama_lengkap ?? $log->technician->nama_lengkap ?? 'Teknisi' }} · {{ $log->status_perbaikan_label }}@if ($log->waktu_mulai) · {{ substr($log->waktu_mulai, 0, 5) }}–{{ $log->waktu_selesai ? substr($log->waktu_selesai, 0, 5) : 'berjalan' }}@endif</small>
                    @if ($canCompleteLogs && $log->area_kerusakan === 'mekanik' && $log->status_perbaikan === 'proses' && $log->status_eskalasi === 'tidak_perlu')
                        <details class="mt-2"><summary class="btn btn-sm btn-outline-success">Catat hasil & tandai selesai</summary><form method="post" action="{{ route('maintenance.logs.complete', $log->id) }}" class="mt-2" data-start="{{ $log->tanggal->format('Y-m-d') }}T{{ $log->waktu_mulai }}">@csrf<textarea class="form-control form-control-sm mb-2" name="tindak_lanjut" rows="2" placeholder="Catat hasil pemeriksaan/perbaikan" required></textarea><button class="btn btn-sm btn-success">Tandai selesai</button></form></details>
                    @elseif ($currentUser->role === 'teknisi2' && $currentUser->bidang_teknis === $log->area_kerusakan && $log->status_eskalasi === 'sudah_dikirim')
                        <form method="post" action="{{ route('maintenance.logs.resolve-escalation', $log->id) }}" class="mt-2" data-start="{{ $log->tanggal->format('Y-m-d') }}T{{ $log->waktu_mulai }}">@csrf<textarea class="form-control form-control-sm mb-2" name="tindak_lanjut" rows="2" placeholder="Catat hasil pemeriksaan/perbaikan" required></textarea><button class="btn btn-sm btn-outline-success">Tandai selesai</button></form>
                    @endif
                </div></article>
            @empty<div class="empty-state">Belum ada catatan maintenance.</div>@endforelse
        </div>
    </section>
</div>
<section class="quick-actions"><span class="eyebrow">Input Cepat</span>
    @if ($currentUser->role === 'manajemen')<a href="{{ route('maintenance.logs.create') }}"><i class="bi bi-journal-plus"></i> Catatan Buku</a>@endif
</section>
@include('maintenance.logs._completion-confirmation')
@endsection