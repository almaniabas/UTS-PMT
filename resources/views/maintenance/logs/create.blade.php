@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Maintenance / Catatan Buku</p><h1>Catat Perbaikan</h1><p>Catat kejadian breakdown, tindakan, dan spare part yang digunakan.</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('maintenance.logs') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<section class="form-panel">
    <form method="post" action="{{ route('maintenance.logs.store') }}">
        @csrf
        <div class="form-grid">
            <div><label class="form-label" for="tanggal">Tanggal *</label><input class="form-control" id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', today()->toDateString()) }}" required></div>
            <div><label class="form-label" for="mesin_id">Mesin *</label><select class="form-select" id="mesin_id" name="mesin_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected(old('mesin_id') == $machine->id)>{{ $machine->kode_mesin }} · {{ $machine->nama_mesin }}</option>@endforeach</select></div>
            <div><label class="form-label" for="posisi_mesin">Posisi Mesin *</label><input class="form-control" id="posisi_mesin" name="posisi_mesin" maxlength="50" value="{{ old('posisi_mesin') }}" required></div>
            <div><label class="form-label" for="status_idle">Status Idle *</label><select class="form-select" id="status_idle" name="status_idle" required>@foreach (['Permanen', 'Non-Permanen', 'None'] as $idle)<option value="{{ $idle }}" @selected(old('status_idle', 'Non-Permanen') === $idle)>{{ $idle }}</option>@endforeach</select></div>
            <div><label class="form-label" for="area_kerusakan">Bagian Kerusakan *</label><select class="form-select" id="area_kerusakan" name="area_kerusakan" required>@foreach ($areas as $value)<option value="{{ $value }}" @selected(old('area_kerusakan', $areas[0]) === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
            <div><label class="form-label" for="jumlah_posisi_terdampak">Posisi Terdampak *</label><input class="form-control" id="jumlah_posisi_terdampak" name="jumlah_posisi_terdampak" type="number" min="1" max="500" value="{{ old('jumlah_posisi_terdampak', 1) }}" required></div>
            <div><label class="form-label" for="waktu_mulai">Waktu Mulai *</label><input class="form-control" id="waktu_mulai" name="waktu_mulai" type="time" value="{{ old('waktu_mulai') }}" required><small class="form-hint">Catat waktu kerusakan diketahui atau mulai ditangani. Waktu selesai dicatat saat teknisi menyelesaikan tiket.</small></div>
            <div class="form-span"><label class="form-label" for="problem">Problem / Kendala *</label><textarea class="form-control" id="problem" name="problem" rows="3" required>{{ old('problem') }}</textarea></div>
            <div class="form-span"><label class="form-label" for="action">Action / Tindakan Perbaikan *</label><textarea class="form-control" id="action" name="action" rows="3" required>{{ old('action') }}</textarea></div>
            <div><label class="form-label" for="keterangan">Keterangan</label><input class="form-control" id="keterangan" name="keterangan" maxlength="255" value="{{ old('keterangan') }}"></div>
            <div><label class="form-label" for="eskalasi_ke">Tim Tujuan</label><input class="form-control" id="eskalasi_ke" value="{{ old('eskalasi_ke') }}" placeholder="Otomatis sesuai bagian kerusakan" readonly><small class="form-hint">Elektrik dan instrumen otomatis diarahkan ke tim Indorama.</small></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 mb-2"><h2 class="form-section-title">Spare Part Digunakan</h2><button class="btn btn-sm btn-outline-primary" type="button" id="add-spare-part"><i class="bi bi-plus"></i> Tambah Part</button></div>
        <div id="spare-part-rows"></div>
        <p class="text-muted small mb-0">Bagian ini boleh dikosongkan jika tidak ada spare part yang digunakan.</p>

        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan Log</button><a class="btn btn-outline-secondary" href="{{ route('maintenance.logs') }}">Batal</a></div>
    </form>
</section>
@endsection

@push('scripts')
<script>
    const damageArea = document.getElementById('area_kerusakan');
    const escalationTarget = document.getElementById('eskalasi_ke');
    damageArea.addEventListener('change', () => {
        if (['elektrik', 'instrumen'].includes(damageArea.value)) {
            escalationTarget.value = `Tim Indorama - ${damageArea.value}`;
        } else {
            escalationTarget.value = '';
        }
    });

    damageArea.dispatchEvent(new Event('change'));

    let sparePartRow = 0;
    document.getElementById('add-spare-part').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'form-grid reject-row mb-3';
        row.innerHTML = `<div><label class="form-label">Nama Part *</label><input class="form-control" name="spare_parts[${sparePartRow}][nama_part]" maxlength="120" required></div><div><label class="form-label">Nomor Part</label><input class="form-control" name="spare_parts[${sparePartRow}][nomor_part]" maxlength="60"></div><div><label class="form-label">Jumlah *</label><input class="form-control" type="number" min="0.01" step="0.01" value="1" name="spare_parts[${sparePartRow}][jumlah]" required></div><div><label class="form-label">Satuan *</label><input class="form-control" name="spare_parts[${sparePartRow}][satuan]" value="pcs" maxlength="20" required></div><div><label class="form-label">Harga Satuan</label><input class="form-control" type="number" min="0" step="0.01" name="spare_parts[${sparePartRow}][harga_satuan]"></div><div><label class="form-label">Keterangan</label><input class="form-control" name="spare_parts[${sparePartRow}][keterangan]" maxlength="255"></div><div class="form-span"><button class="btn btn-sm btn-outline-danger remove-part" type="button">Hapus part</button></div>`;
        row.querySelector('.remove-part').addEventListener('click', () => row.remove());
        document.getElementById('spare-part-rows').append(row);
        sparePartRow++;
    });
</script>
@endpush