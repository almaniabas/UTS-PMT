<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = ['username', 'password', 'nama_lengkap', 'role', 'status', 'asal_tim', 'bidang_teknis'];

    protected $hidden = ['password'];

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class, 'teknisi_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'teknisi_id');
    }

    public function handledMaintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'ditangani_oleh');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(MachineOperation::class, 'operator_id');
    }

    public function dailyInspections(): HasMany
    {
        return $this->hasMany(DailyInspection::class, 'pc_id');
    }

}