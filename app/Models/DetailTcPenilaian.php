<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTcPenilaian extends Model
{
    use HasFactory;

    protected $table = 'detail_penilaian_tcs';

    protected $fillable = [
        'id_job_position',
        'name',
        'keterangan_sebelum',
        'keterangan_detail',
        'catatan',
        'modified_at',
    ];

    public function tcPenilaian()
    {
        return $this->belongsTo(TrsPenilaianTc::class, 'id_job_position');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
