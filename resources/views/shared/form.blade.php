@extends('layouts.app')

@section('content')
<div class="page-heading"><div><p class="eyebrow">SIM-DTY / Form</p><h1>{{ $title }}</h1><p>Isi data dengan benar sebelum disimpan.</p></div><a class="btn btn-outline-secondary" href="{{ url()->previous() }}"><i class="bi bi-arrow-left"></i> Kembali</a></div>
@php
    $editMachine = $formType === 'machine' && $record;
    $action = match ($formType) {
        'machine' => $editMachine ? route('machines.update', $record->id) : route('machines.store'),
        'schedule' => route('maintenance.store'),
        'maintenance-log' => route('maintenance.logs.store'),
        'operation' => route('operations.store'),
    };
@endphp
<section class="form-panel">
<form method="post" action="{{ $action }}">
    @csrf
    @if ($editMachine) @method('PUT') @endif
    @if ($formType === 'machine')
        <div class="form-grid">
            <div><label class="form-label">Kode Mesin *</label><input class="form-control" name="kode_mesin" required maxlength="30" value="{{ old('kode_mesin', $record->kode_mesin ?? '') }}"></div>
            <div><label class="form-label">Nama Mesin *</label><input class="form-control" name="nama_mesin" required maxlength="100" value="{{ old('nama_mesin', $record->nama_mesin ?? '') }}"></div>
            <div><label class="form-label">Merk / Tipe</label><input class="form-control" name="merk_tipe" value="{{ old('merk_tipe', $record->merk_tipe ?? '') }}"></div>
            <div><label class="form-label">Jumlah Posisi *</label><input class="form-control" type="number" min="1" name="jumlah_posisi" required value="{{ old('jumlah_posisi', $record->jumlah_posisi ?? 216) }}"></div>
            <div><label class="form-label">Tahun Instalasi</label><input class="form-control" type="number" name="tahun_instalasi" min="1900" max="2100" value="{{ old('tahun_instalasi', $record->tahun_instalasi ?? '') }}"></div>
            <div><label class="form-label">Status Kondisi *</label><select class="form-select" name="status_kondisi">@foreach (['running', 'breakdown', 'maintenance', 'idle'] as $value)<option @selected(old('status_kondisi', $record->status_kondisi ?? 'running') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="form-span"><label class="form-label">Catatan</label><textarea class="form-control" name="catatan" rows="3">{{ old('catatan', $record->catatan ?? '') }}</textarea></div>
        </div>
    @elseif ($formType === 'schedule')
        <div class="form-grid">
            <div><label class="form-label">Mesin *</label><select class="form-select" name="mesin_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected(old('mesin_id') == $machine->id)>{{ $machine->kode_mesin }} · {{ $machine->nama_mesin }}</option>@endforeach</select></div>
            <div><label class="form-label">Tanggal Rencana *</label><input class="form-control" type="date" name="tanggal_rencana" required value="{{ old('tanggal_rencana') }}"></div>
            <div><label class="form-label">Tipe Maintenance *</label><select class="form-select" name="tipe_maintenance"><option value="rutin_2bulan" @selected(old('tipe_maintenance', 'rutin_2bulan') === 'rutin_2bulan')>Rutin</option><option value="overhaul" @selected(old('tipe_maintenance') === 'overhaul')>Overhaul</option><option value="inspeksi_khusus" @selected(old('tipe_maintenance') === 'inspeksi_khusus')>Inspeksi khusus</option></select></div>
            <div><label class="form-label">Teknisi</label><select class="form-select" name="teknisi_id"><option value="">Belum ditugaskan</option>@foreach ($technicians as $technician)<option value="{{ $technician->id }}">{{ $technician->nama_lengkap }}</option>@endforeach</select></div>
            <div class="form-span"><label class="form-label">Rencana / Catatan Perawatan</label><textarea class="form-control" name="catatan_perawatan" rows="3" placeholder="Contoh: inspeksi posisi, belt, atau komponen yang perlu diperiksa">{{ old('catatan_perawatan') }}</textarea><small class="form-hint">Catat rencana atau masalah yang sudah diketahui. Hasil dan spare part yang benar-benar diganti dicatat saat jadwal diselesaikan.</small></div>
        </div>
    @elseif ($formType === 'maintenance-log')
        <div class="form-grid">
            <div><label class="form-label">Tanggal *</label><input class="form-control" type="date" name="tanggal" required value="{{ old('tanggal', today()->toDateString()) }}"></div>
            <div><label class="form-label">Mesin *</label><select class="form-select" name="mesin_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}">{{ $machine->kode_mesin }}</option>@endforeach</select></div>
            <div><label class="form-label">Posisi Mesin *</label><input class="form-control" name="posisi_mesin" required></div>
            <div><label class="form-label">Status Idle *</label><select class="form-select" name="status_idle"><option>Non-Permanen</option><option>Permanen</option><option>None</option></select></div>
            <div class="form-span"><label class="form-label">Problem *</label><textarea class="form-control" name="problem" rows="3" required></textarea></div>
            <div class="form-span"><label class="form-label">Tindakan *</label><textarea class="form-control" name="action" rows="3" required></textarea></div>
            <div><label class="form-label">Keterangan / Part</label><input class="form-control" name="keterangan"></div>
        </div>
    @elseif ($formType === 'operation')
        <div class="form-grid">
            <div><label class="form-label">Tanggal *</label><input class="form-control" type="date" name="tanggal" required value="{{ old('tanggal', today()->toDateString()) }}"></div>
            <div><label class="form-label">Mesin *</label><select class="form-select" name="mesin_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}">{{ $machine->kode_mesin }}</option>@endforeach</select></div>
            <div><label class="form-label">Shift *</label><select class="form-select" name="shift"><option>Shift 1</option><option>Shift 2</option><option>Shift 3</option></select></div>
            <div><label class="form-label">EFF (%) *</label><input class="form-control" type="number" min="0" max="100" step="0.01" name="eff_persen" required></div>
            <div><label class="form-label">No Dog Break</label><input class="form-control" name="no_dog_break"></div>
            <div><label class="form-label">Total Break *</label><input class="form-control" type="number" min="0" name="total_break" value="0" required></div>
            <div><label class="form-label">Status Idle *</label><select class="form-select" name="status_idle"><option>None</option><option>Permanen</option><option>Non-Permanen</option></select></div>
            <div><label class="form-label">Vessel Status</label><input class="form-control" name="vessel_status"></div>
            <div><label class="form-label">Jam Mulai *</label><input class="form-control" type="time" name="jam_start" value="07:00" required></div>
            <div><label class="form-label">Jam Selesai *</label><input class="form-control" type="time" name="jam_stop" value="15:00" required></div>
            <div class="form-span"><label class="form-label">Material (POY) *</label><input class="form-control" name="material" required></div>
            <div class="form-span"><label class="form-label">Anomali</label><textarea class="form-control" name="anomali" rows="2"></textarea></div>
        </div>
    @endif
    <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan Data</button><a class="btn btn-outline-secondary" href="{{ url()->previous() }}">Batal</a></div>
</form>
</section>
@endsection

@push('scripts')
@endpush