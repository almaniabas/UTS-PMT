<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineOperation extends Model
{
    protected $table = 'operasional_mesin';

    public $timestamps = false;

    protected $fillable = ['mesin_id', 'tanggal', 'shift', 'operator_id', 'eff_persen', 'no_dog_break', 'total_break', 'status_idle', 'vessel_status', 'anomali', 'jam_start', 'jam_stop', 'material'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'eff_persen' => 'decimal:2', 'total_break' => 'integer'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'mesin_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}