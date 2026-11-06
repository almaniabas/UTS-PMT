<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceLog extends Model
{
    protected $table = 'log_maintenance_harian';

    public $timestamps = false;

    protected $fillable = ['tanggal', 'mesin_id', 'posisi_mesin', 'status_idle', 'problem', 'action', 'keterangan', 'teknisi_id', 'status_perbaikan', 'area_kerusakan', 'jumlah_posisi_terdampak', 'waktu_mulai', 'waktu_selesai', 'downtime_menit', 'eskalasi_ke', 'status_eskalasi', 'tindak_lanjut', 'ditangani_oleh'];

    public function getStatusPerbaikanLabelAttribute(): string
    {
        return [
            'proses' => 'Sedang diperbaiki',
            'selesai' => 'Selesai',
            'pending_sparepart' => 'Menunggu spare part',
            'monitoring' => 'Monitoring',
        ][$this->status_perbaikan] ?? str_replace('_', ' ', $this->status_perbaikan);
    }

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jumlah_posisi_terdampak' => 'integer', 'downtime_menit' => 'integer'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'mesin_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(MaintenanceLogSparePart::class, 'maintenance_log_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }
}