<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserJobPosition extends Model
{
    use HasFactory;

    protected $table = 'user_job_positions';

    protected $fillable = [
        'user_id',
        'mst_job_position_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- Relationships ---

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jobPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'mst_job_position_id');
    }
}
