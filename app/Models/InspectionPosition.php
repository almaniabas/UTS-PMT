<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionPosition extends Model
{
    protected $table = 'inspeksi_posisi';

    public $timestamps = false;

    protected $fillable = ['inspeksi_id', 'nomor_posisi', 'status', 'jenis_problem', 'catatan'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(DailyInspection::class, 'inspeksi_id');
    }
}