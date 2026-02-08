<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPreventif extends Model
{
    protected $table = 'jadwal_preventif';
    use HasFactory;
    protected $fillable = [
        'id', 'nomor_mesin', 'tipe', 'jadwal_rencana', 'jadwal_aktual', 'status',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
