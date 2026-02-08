<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class  MstShape extends Model
{
    use HasFactory;
    protected $table = 'mst_shape';
    protected $fillable = [
        'name',
        'is_active',
        'updated_by',
        'last_update',
        'created_at',
        'updated_at',
    ];
}