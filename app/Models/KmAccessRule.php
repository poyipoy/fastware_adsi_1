<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KmAccessRule extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id', 'ability', 'effect',
        'valid_from', 'valid_until', 'reason', 'created_by',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'created_by' => 'integer',
    ];
}
