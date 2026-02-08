<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstQst extends Model
{
    use HasFactory;
    
    protected $table = 'mst_qst';
    
    protected $fillable = [
        'user_name',
        'jabatan',
        'system_name',
        'core_metrics',
        'features',
        'obstacles',
        'suggestions',
        'is_active',
        'status',
        'modified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'core_metrics' => 'array',
        'features' => 'array',
        'is_active' => 'integer',
    ];
}
