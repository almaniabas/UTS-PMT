@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div><p class="eyebrow">SIM-DTY / Data</p><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
    <div class="heading-actions">
        @if ($type === 'machines' && session('role') === 'manajemen')
            <a href="{{ route('machines.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Mesin</a>
        @endif
        @if ($type === 'schedules' && in_array(session('role'), ['teknisi1', 'manajemen']))
            <a href="{{ route('maintenance.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Jadwal</a>
        @endif
        @if ($type === 'maintenance-logs' && session('role') === 'manajemen')
            <a href="{{ route('maintenance.logs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tulis Catatan</a>
        @endif
        @if ($type === 'operations' && session('role') === 'manajemen')
            <a href="{{ route('operations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Input Shift</a>
        @endif
    </div>
</div>

@if ($type === 'machines')
    <nav class="filter-row" aria-label="Filter status mesin">
        @foreach (['' => 'Semua', 'running' => 'Running', 'breakdown' => 'Breakdown', 'maintenance' => 'Maintenance', 'idle' => 'Idle'] as $status => $label)
            <a class="filter-chip {{ request('status', '') === $status ? 'selected' : '' }}" href="{{ route('machines.index', $status ? ['status' => $status] : []) }}">{{ $label }}</a>
        @endforeach
    </nav>
@endif

@if ($type === 'schedules')
    @php
        $scheduleGroups = [
            [
                'title' => 'Belum Selesai',
                'icon' => 'bi-exclamation-circle',
                'class' => 'schedule-card-pending',
                'rows' => collect($rows)->reject(fn ($row) => data_get($row, 'status') === 'selesai'),
            ],
            [
                'title' => 'Sudah Selesai',
                'icon' => 'bi-check-circle',
                'class' => 'schedule-card-completed',
                'rows' => collect($rows)
                    ->filter(fn ($row) => data_get($row, 'status') === 'selesai')
                    ->sortByDesc(fn ($row) => data_get($row, 'selesai_pada') ?? ''),
            ],
        ];
    @endphp
    <div class="schedule-cards">
        @foreach ($scheduleGroups as $group)
            <section class="data-panel schedule-card {{ $group['class'] }}">
                <div class="panel-heading">
                    <div><h2><i class="bi {{ $group['icon'] }} me-2"></i>{{ $group['title'] }}</h2></div>
                    <span class="schedule-count">{{ $group['rows']->count() }} jadwal</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Mesin</th><th>Rencana</th><th>Realisasi</th><th>Jatuh Tempo PM Rutin</th><th>Tipe</th><th>Teknisi</th><th>Hasil / Spare Part Diganti</th><th>Status</th>@if (in_array(session('role'), ['teknisi1', 'manajemen']))<th class="text-end">Aksi</th>@endif</tr></thead>
                        <tbody>
                        @forelse ($group['rows'] as $row)
                            @php
                                $status = data_get($row, 'status');
                                $statusLabel = match ($status) {
                                    'selesai' => 'Selesai',
                                    'terlambat' => 'Terlambat',
                                    'proses' => 'Sedang dikerjakan',
                                    default => 'Belum selesai',
                                };
                            @endphp
                            <tr>
                                <td>{{ data_get($row, 'kode_mesin') ?: '-' }}</td>
                                <td>{{ data_get($row, 'tanggal_rencana') ?: '-' }}</td>
                                <td>{{ data_get($row, 'tanggal_realisasi') ?: '-' }}</td>
                                <td>{{ data_get($row, 'tanggal_pm_rutin_berikutnya') ?: '-' }}</td>
                                <td>{{ data_get($row, 'tipe_maintenance') === 'rutin_2bulan' ? 'Rutin' : ucfirst(str_replace('_', ' ', data_get($row, 'tipe_maintenance') ?? '-')) }}</td>
                                <td>{{ data_get($row, 'nama_teknisi') ?: '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit(data_get($row, 'catatan_realisasi') ?: '-', 120) }}</td>
                                <td><span class="status-pill status-{{ $status === 'selesai' ? 'selesai' : ($status === 'terlambat' ? 'terlambat' : ($status === 'proses' ? 'proses' : 'terjadwal')) }}">{{ $statusLabel }}</span></td>
                                @if (in_array(session('role'), ['teknisi1', 'manajemen']))
                                    <td class="text-end">
                                        @if ($status !== 'selesai')
                                            <details class="text-start">
                                                <summary class="btn btn-sm btn-danger"><i class="bi bi-check2-circle me-1"></i>Tandai selesai</summary>
                                                <form method="post" action="{{ route('maintenance.complete', data_get($row, 'id')) }}" class="mt-2">
                                                    @csrf
                                                    <label class="form-label small" for="realisasi-{{ data_get($row, 'id') }}">Hasil PM / spare part yang diganti</label>
                                                    <textarea class="form-control form-control-sm mb-2" id="realisasi-{{ data_get($row, 'id') }}" name="catatan_realisasi" rows="3" maxlength="5000" placeholder="Catat hasil pemeriksaan dan komponen yang diganti, atau tulis tidak ada penggantian."></textarea>
                                                    <button class="btn btn-sm btn-success">Simpan penyelesaian</button>
                                                </form>
                                            </details>
                                        @else
                                            <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="{{ in_array(session('role'), ['teknisi1', 'manajemen']) ? 9 : 8 }}"><i class="bi bi-inbox"></i><span>Belum ada jadwal di bagian ini.</span></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
@endif

@if ($type !== 'schedules')
<section class="data-panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    @foreach ($columns as $label => $key)
                        <th>{{ $label }}</th>
                    @endforeach
                    @if (($type === 'machines' && session('role') === 'manajemen') || $type === 'schedules')
                        <th class="text-end">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $label => $key)
                        <td>
                            @if ($key === 'status_kondisi' || $key === 'status' || $key === 'status_perbaikan')
                                @php($statusValue = data_get($row, $key) ?? 'unknown')
                                <span class="status-pill status-{{ str_replace('_', '-', $statusValue) }}">{{ $key === 'status_perbaikan' ? (['proses' => 'Sedang diperbaiki', 'selesai' => 'Selesai', 'pending_sparepart' => 'Menunggu spare part', 'monitoring' => 'Monitoring'][$statusValue] ?? str_replace('_', ' ', $statusValue)) : str_replace('_', ' ', $statusValue) }}</span>
                            @elseif ($key === 'eff_persen')
                                {{ number_format((float) data_get($row, $key), 2) }}%
                            @elseif (in_array($key, ['problem', 'action', 'catatan_perawatan', 'status_reason']))
                                {{ \Illuminate\Support\Str::limit(data_get($row, $key) ?? '-', 60) }}
                            @else
                                {{ data_get($row, $key) ?: '-' }}
                            @endif
                        </td>
                    @endforeach
                    @if ($type === 'machines' && session('role') === 'manajemen')
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('machines.edit', data_get($row, 'id')) }}" aria-label="Edit mesin"><i class="bi bi-pencil"></i></a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td class="empty-state" colspan="{{ count($columns) + ((($type === 'machines' && session('role') === 'manajemen') || $type === 'schedules') ? 1 : 0) }}">
                        <i class="bi bi-inbox"></i><span>Belum ada data untuk ditampilkan.</span>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection