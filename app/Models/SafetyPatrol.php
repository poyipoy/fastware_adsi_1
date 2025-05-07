<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyPatrol extends Model
{
    use HasFactory;

    protected $table = 'safety_patrol';
    use HasFactory;
    protected $fillable = [
        'id', 'tanggal_patrol', 'area_patrol', 'pic_area',
        'petugas_patrol', 'kategori_1', 'kategori_2',
        'kategori_3', 'kategori_4', 'kategori_5', 'kategori_catatan',
        'safety_1', 'safety_2', 'safety_3', 'safety_4', 'safety_5',
        'safety_catatan', 'lingkungan_1', 'lingkungan_2', 'lingkungan_3',
        'lingkungan_4', 'lingkungan_catatan'
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }
}
