<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $table = 'mesin';

    protected $fillable = ['kode_mesin', 'nama_mesin', 'merk_tipe', 'jumlah_posisi', 'tahun_instalasi', 'status_kondisi', 'catatan'];

    protected function casts(): array
    {
        return ['jumlah_posisi' => 'integer', 'tahun_instalasi' => 'integer'];
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class, 'mesin_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'mesin_id');
    }

    public function dailyInspections(): HasMany
    {
        return $this->hasMany(DailyInspection::class, 'mesin_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(MachineOperation::class, 'mesin_id');
    }

}