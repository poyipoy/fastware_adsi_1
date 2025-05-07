<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BtnStatus extends Model
{
    use HasFactory;

    protected $table = 'btn_status';

    protected $fillable = [
        'status',
        'modified_at',
    ];
}
