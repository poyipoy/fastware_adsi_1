<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeatTreatment extends Model
{
    use HasFactory;
    protected $table = 'wo_heat';

    protected $fillable = [
        'no_wo',
        'no_so',
        'tgl_wo',
        'deskripsi',
        'area',
        'kode',
        'cust',
        'proses',
        'pcs',
        'kg',
        'batch_heating',
        'mesin_heating',
        'tgl_heating',
        'batch_temper1',
        'mesin_temper1',
        'tgl_temper1',
        'batch_temper2',
        'mesin_temper2',
        'tgl_temper2',
        'batch_temper3',
        'mesin_temper3',
        'tgl_temper3',
        'status_wo',
        'no_do',
        'status_do',
        'tgl_st',
        'supir',
        'penerima',
        'tgl_terima',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
