<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrsAttcCstm extends Model
{
    use HasFactory;
    protected $table = 'trx_pengajuan_subconts';
    protected $fillable = [
        'mst_id',
        'description',
        'created_at',
        'updated_at',
    ];

    public function mstPengajuanSubcont(): BelongsTo
    {
        return $this->belongsTo(MstPengajuanSubcont::class, 'mst_id');
    }
}