<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSchedule extends Model
{
    protected $table = 'jadwal_maintenance';

    public $timestamps = false;

    protected $fillable = ['mesin_id', 'tanggal_rencana', 'tanggal_realisasi', 'tipe_maintenance', 'status', 'teknisi_id', 'catatan_perawatan', 'catatan_realisasi'];

    protected function casts(): array
    {
        return ['tanggal_rencana' => 'date', 'tanggal_realisasi' => 'date'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'mesin_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }
}