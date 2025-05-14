<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CustomDboReq extends Model
{
    protected $table = 'mst_custom_quote_req';
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'sales',
        'customer',
        'ket_drawing',
        'remark',
        'nama_project',
        'tgl_update',
        'attachment',
        'status',
        'harga_awal',
        'harga_akhir',
        'progress',
        'ref_so',
        'marketing_id',
        'approved_marketing',
        'finance_id',
        'approved_finance',
    ];

        // CustomDboReq.php
        public function customers()
        {
            return $this->belongsTo(Customer::class, 'customer', 'id');
        }
        public function finance()
        {
            return $this->belongsTo(User::class, 'finance_id', 'id');
        }

        public function marketing()
        {
            return $this->belongsTo(User::class, 'marketing_id', 'id');
        }

}
