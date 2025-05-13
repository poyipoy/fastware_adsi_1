<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportAdministration extends Model
{
    use HasFactory;

    protected $table = 'mst_adm_import';

    protected $fillable = [
        'no_document',
        'status',
        'supplier',
        'no_inv',
        'pl',
        'no_vo',
        'novo_file',
        'vr',
        'ls',
        'bl',
        'inv_final',
        'pl_final',
        'form_e',
        'asuransi',
        'no_aju',
        'pib_final',
        'e_bill',
        'purchase_id',
        'purchase_updated_at',
        'admin_id',
        'admin_updated_at',
        'created_at',
        'updated_at',
        'deleted_by',
    ];

    // Relasi untuk purchase_id
    public function purchase()
    {
        return $this->belongsTo(User::class, 'purchase_id');
    }

    // Relasi untuk admin_id
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
