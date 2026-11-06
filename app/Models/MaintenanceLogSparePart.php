<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLogSparePart extends Model
{
    protected $table = 'maintenance_log_spare_parts';

    public $timestamps = false;

    protected $fillable = ['maintenance_log_id', 'nama_part', 'nomor_part', 'jumlah', 'satuan', 'harga_satuan', 'keterangan'];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2', 'harga_satuan' => 'decimal:2'];
    }

    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(MaintenanceLog::class, 'maintenance_log_id');
    }
}