<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KmBadge extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'event_type', 'threshold', 'is_active'];
    protected $casts = ['threshold' => 'integer', 'is_active' => 'boolean'];
}
