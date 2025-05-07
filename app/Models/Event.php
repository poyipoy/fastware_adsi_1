<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Importer;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_mesin', 'nama_mesin', 'type',
        'no_mesin', 'mfg_date',
        'pic', 'start', 'end', 'status'
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function mesin()
    {
        return $this->belongsTo(Mesin::class, 'id_mesin', 'id');
    }

    public function detailPreventives()
    {
        return $this->hasMany(DetailPreventive::class, 'id_mesin', 'id_mesin');
    }
}
