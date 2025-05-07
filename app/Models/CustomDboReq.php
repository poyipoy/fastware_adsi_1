<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomDboReq extends Model
{
    protected $table = 'mst_custom_quote_req';
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'sales',
        'customer',
        'tgl_permintaan',
        'ket_drawing',
        'nama_project',
        'progress',
        'tgl_update',
        'ref_so',
        'remark',
        'marketing_id',
        'finance_id',
        'status'
    ];

        // CustomDboReq.php
        public function customers()
        {
            return $this->belongsTo(Customer::class, 'customer', 'id');
        }

}
