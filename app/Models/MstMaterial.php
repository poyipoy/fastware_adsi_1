<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MstMaterial extends Model
{
    use HasFactory;
    protected $table = 'mst_material';
    protected $fillable = [
        'id_lc',
        'grade',
        'shape',
        'is_active',
        'update_by',
        'last_update',
        'created_at',
        'updated_at',
    ];

    public function mstQuartal(): BelongsTo
    {
        return $this->belongsTo(MstShape::class, 'id_lc');
    }
}