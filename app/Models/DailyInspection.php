<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyInspection extends Model
{
    protected $table = 'inspeksi_harian';

    public $timestamps = false;

    protected $fillable = ['mesin_id', 'pc_id', 'tanggal', 'total_posisi', 'posisi_bermasalah', 'keputusan_mesin', 'catatan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'total_posisi' => 'integer', 'posisi_bermasalah' => 'integer'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'mesin_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pc_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(InspectionPosition::class, 'inspeksi_id');
    }
}