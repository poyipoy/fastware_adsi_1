<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportAdministration extends Model
{
    use hasFactory;

    protected $table = 'mst_adm_import';

    protected $fillable = [
        'no_document',
        'status',
        'supplier',
        'no_inv',
        'pl',
        'no_vo',
        'ls',
        'bl',
        'inv_final',
        'pl_final',
        'form_e',
        'asuransi',
        'no_aju',
        'pib_final',
        'e_bill',
        'created_at',
        'updated_at',
        'deleted_by',
    ];

}