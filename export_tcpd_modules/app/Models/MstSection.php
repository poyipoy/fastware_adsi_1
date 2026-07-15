<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstSection extends Model
{
    protected $table = 'mst_sections';

    protected $fillable = ['department_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function department()
    {
        return $this->belongsTo(MstDepartment::class, 'department_id');
    }

    public function jobPositions()
    {
        return $this->hasMany(MstJobPosition::class, 'section_id');
    }
}
