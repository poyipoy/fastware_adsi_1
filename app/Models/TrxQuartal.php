<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrxQuartal extends Model
{
    use HasFactory;

    protected $table = 'trx_quartal';

    protected $fillable = [
        'id_material',
        'thn',
        'q1_base',
        'q1_alloy',
        'q1_fob',
        'q1_cnf',
        'q1_freight',
        'q2_base',
        'q2_alloy',
        'q2_fob',
        'q2_cnf',
        'q2_freight',
        'q3_base',
        'q3_alloy',
        'q3_fob',
        'q3_cnf',
        'q3_freight',
        'q4_base',
        'q4_alloy',
        'q4_fob',
        'q4_cnf',
        'q4_freight',
        'update_by',
        'last_update',
        'created_at',
        'updated_at',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MstMaterial::class, 'id_material');
    }
}