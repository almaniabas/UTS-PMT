@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Maintenance / Riwayat</p><h1>Log Catatan Buku</h1><p>Riwayat breakdown, tindakan perbaikan, dan spare part yang digunakan.</p></div>
    @if ($canCreate)<a class="btn btn-primary" href="{{ route('maintenance.logs.create') }}"><i class="bi bi-plus-lg"></i> Catat Perbaikan</a>@endif
</div>
<div class="maintenance-log-list">
    @forelse ($logs as $log)
        <article class="maintenance-log-card">
            <header class="maintenance-log-header">
                <div>
                    <div class="maintenance-log-machine"><strong>{{ $log->machine->kode_mesin }}</strong><span>{{ $log->tanggal->format('d/m/Y') }}</span></div>
                    <small>{{ $log->posisi_mesin }} · Idle {{ $log->status_idle }}</small>
                </div>
                <span class="status-pill status-{{ str_replace('_', '-', $log->status_perbaikan) }}">{{ $log->status_perbaikan_label }}</span>
            </header>
            <div class="maintenance-log-body">
                <section class="maintenance-log-problem">
                    <span class="maintenance-log-label">Problem / Kendala</span>
                    <p>{{ $log->problem }}</p>
                </section>
                <div class="maintenance-log-notes">
                    <section>
                        <span class="maintenance-log-label">Tindakan Perbaikan</span>
                        <p>{{ $log->action }}</p>
                    </section>
                    <section>
                        <span class="maintenance-log-label">Tindak Lanjut</span>
                        @if ($log->tindak_lanjut)
                            <p>{{ $log->tindak_lanjut }}</p>
                            <small>Oleh {{ $log->handler->nama_lengkap ?? 'Teknisi' }}</small>
                        @else
                            <p class="text-muted">Belum ada tindak lanjut.</p>
                        @endif
                    </section>
                </div>
                <div class="maintenance-log-meta">
                    <div><span class="maintenance-log-label">Kategori / Dampak</span><strong>{{ ucfirst($log->area_kerusakan) }} · {{ $log->jumlah_posisi_terdampak }} posisi</strong></div>
                    <div>
                        <span class="maintenance-log-label">Downtime</span>
                        <strong>{{ $log->downtime_menit !== null ? $log->downtime_menit . ' menit' : '-' }}</strong>
                        @if ($log->waktu_mulai)<small>{{ substr($log->waktu_mulai, 0, 5) }}–{{ $log->waktu_selesai ? substr($log->waktu_selesai, 0, 5) : 'berjalan' }}</small>@endif
                    </div>
                    <div>
                        <span class="maintenance-log-label">Teknisi</span>
                        <strong>{{ $log->handler->nama_lengkap ?? $log->technician->nama_lengkap ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="maintenance-log-label">Spare Part</span>
                        @forelse ($log->spareParts as $part)
                            <strong>{{ $part->nama_part }} <small>× {{ $part->jumlah }} {{ $part->satuan }}</small></strong>
                        @empty
                            <strong class="text-muted">-</strong>
                        @endforelse
                    </div>
                    <div class="maintenance-log-escalation">
                        <span class="maintenance-log-label">Eskalasi</span>
                        <span class="status-pill status-{{ $log->status_eskalasi === 'ditangani' ? 'running' : (in_array($log->status_eskalasi, ['perlu_dikirim', 'sudah_dikirim']) ? 'breakdown' : 'terjadwal') }}">{{ str_replace('_', ' ', $log->status_eskalasi) }}</span>
                        @if ($log->eskalasi_ke)<small>{{ $log->eskalasi_ke }}</small>@endif
                        @if (session('role') === 'manajemen' && in_array($log->area_kerusakan, ['elektrik', 'instrumen'], true) && $log->status_eskalasi === 'perlu_dikirim')
                            <form method="post" action="{{ route('maintenance.logs.send-escalation', $log->id) }}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-warning">Kirim eskalasi</button></form>
                    @elseif ($user->role === 'teknisi2' && $user->bidang_teknis === $log->area_kerusakan && $log->status_eskalasi === 'sudah_dikirim')
                            <form method="post" action="{{ route('maintenance.logs.resolve-escalation', $log->id) }}" class="mt-2" data-start="{{ $log->tanggal->format('Y-m-d') }}T{{ $log->waktu_mulai }}">@csrf<textarea class="form-control form-control-sm mb-2" name="tindak_lanjut" rows="2" placeholder="Catat hasil pemeriksaan/perbaikan" required>{{ old('tindak_lanjut') }}</textarea>@error('tindak_lanjut')<small class="d-block text-danger mb-2">{{ $message }}</small>@enderror<button class="btn btn-sm btn-outline-success">Tandai selesai</button></form>
                    @elseif ($canComplete && $log->area_kerusakan === 'mekanik' && $log->status_perbaikan === 'proses')
                            <details class="mt-2">
                                <summary class="btn btn-sm btn-outline-success">Catat hasil & tandai selesai</summary>
                                <form method="post" action="{{ route('maintenance.logs.complete', $log->id) }}" class="mt-2" data-start="{{ $log->tanggal->format('Y-m-d') }}T{{ $log->waktu_mulai }}">@csrf<textarea class="form-control form-control-sm mb-2" name="tindak_lanjut" rows="2" placeholder="Catat hasil pemeriksaan/perbaikan" required></textarea><button class="btn btn-sm btn-success">Tandai selesai</button></form>
                            </details>
                    @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="data-panel empty-state">Belum ada catatan maintenance.</div>
    @endforelse
</div>
<div class="data-panel px-3 py-3">{{ $logs->links() }}</div>
@include('maintenance.logs._completion-confirmation')
@endsection