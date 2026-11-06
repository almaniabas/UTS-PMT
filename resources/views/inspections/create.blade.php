@extends('layouts.app')

@section('content')
<div class="page-heading"><div><p class="eyebrow">Operasional / Inspeksi</p><h1>Inspeksi Spindle Harian</h1><p>Catat hanya posisi yang bermasalah; posisi yang tidak dimasukkan dianggap normal.</p></div><a class="btn btn-outline-secondary" href="{{ route('inspections.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a></div>
<section class="form-panel">
    <form method="post" action="{{ route('inspections.store') }}" id="inspection-form">
        @csrf
        <div class="form-grid">
            <div><label class="form-label" for="tanggal">Tanggal *</label><input class="form-control" id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', today()->toDateString()) }}" required></div>
            <div><label class="form-label" for="mesin_id">Mesin *</label><select class="form-select" id="mesin_id" name="mesin_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" data-positions="{{ $machine->jumlah_posisi }}" @selected(old('mesin_id') == $machine->id)>{{ $machine->kode_mesin }} · {{ $machine->nama_mesin }}</option>@endforeach</select></div>
            <div><label class="form-label" for="total_posisi">Total Posisi Mesin *</label><input class="form-control" id="total_posisi" name="total_posisi" type="number" value="{{ old('total_posisi') }}" readonly required></div>
            <div><label class="form-label" for="keputusan_mesin">Keputusan Mesin *</label><select class="form-select" id="keputusan_mesin" name="keputusan_mesin" required><option value="running">Tetap running</option><option value="stop">Stop untuk perbaikan</option></select><small class="form-hint" id="stop-advice">Pilih mesin dan catat posisi bermasalah untuk melihat saran.</small></div>
            <div class="form-span"><label class="form-label" for="catatan">Catatan Inspeksi</label><textarea class="form-control" id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 mb-2"><h2 class="form-section-title">Posisi Bermasalah</h2><button class="btn btn-sm btn-outline-primary" type="button" id="add-position"><i class="bi bi-plus"></i> Tambah Posisi</button></div>
        <div id="position-rows"></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan Inspeksi</button><a class="btn btn-outline-secondary" href="{{ route('inspections.index') }}">Batal</a></div>
    </form>
</section>
@endsection

@push('scripts')
<script>
    let positionRow = 0;
    const machineSelect = document.getElementById('mesin_id');
    const totalPositions = document.getElementById('total_posisi');
    const stopAdvice = document.getElementById('stop-advice');
    const positionRows = document.getElementById('position-rows');

    function updateMachineAdvice() {
        const option = machineSelect.selectedOptions[0];
        totalPositions.value = option?.dataset.positions || '';
        const problemCount = positionRows.querySelectorAll('.position-row').length;
        stopAdvice.textContent = problemCount > 20
            ? `${problemCount} posisi bermasalah. Wawancara menyebut mesin biasanya dihentikan jika lebih dari 20 posisi terdampak.`
            : `${problemCount} posisi dicatat bermasalah. Keputusan akhir tetap mengikuti kondisi mesin.`;
    }

    machineSelect.addEventListener('change', updateMachineAdvice);
    document.getElementById('add-position').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'form-grid reject-row position-row mb-3';
        row.innerHTML = `<div><label class="form-label">Nomor Posisi *</label><input class="form-control" type="number" min="1" max="${totalPositions.value || 500}" name="positions[${positionRow}][nomor_posisi]" required></div><div><label class="form-label">Jenis Problem *</label><select class="form-select" name="positions[${positionRow}][jenis_problem]" required><option value="tension_tinggi">Tension tinggi</option><option value="tension_rendah">Tension rendah</option><option value="broken_filament">Broken filament</option><option value="cross_winding">Cross winding</option><option value="over_diameter">Over diameter</option><option value="kilap">Kilap / mengkilat</option><option value="lainnya">Lainnya</option></select></div><div class="form-span"><label class="form-label">Catatan</label><div class="d-flex gap-2"><input class="form-control" name="positions[${positionRow}][catatan]" maxlength="255"><button class="btn btn-outline-danger remove-position" type="button" aria-label="Hapus posisi"><i class="bi bi-trash"></i></button></div></div>`;
        row.querySelector('.remove-position').addEventListener('click', () => { row.remove(); updateMachineAdvice(); });
        positionRows.append(row);
        positionRow++;
        updateMachineAdvice();
    });
</script>
@endpush